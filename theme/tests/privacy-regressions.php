<?php
declare(strict_types=1);

use ImperialFuture\Comments\CommentVoteAjaxController;
use ImperialFuture\Comments\CommentVoteService;
use ImperialFuture\Comments\MarkdownService;
use ImperialFuture\Comments\PopularCommentService;
use ImperialFuture\Media\DefaultAvatar;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

define('DISABLE_WP_CRON', true);
define('WP_IMPORTING', true);
define('DOING_AJAX', true);
$root = dirname(__DIR__, 4);
require_once $root . '/wp-includes/plugin.php';

// Не отправляем письма и не обращаемся во внешнюю сеть даже при загрузке WP.
$mail = [];
$captureMail = static function (mixed $result, array $attributes) use (&$mail): bool {
    $mail[] = $attributes;
    return true;
};
$blockHttp = static fn (): WP_Error => new WP_Error('privacy_test_network_disabled');
add_filter('pre_wp_mail', $captureMail, PHP_INT_MAX, 2);
add_filter('pre_http_request', $blockHttp, PHP_INT_MAX);
require_once $root . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$checks = 0;
$failure = null;
$postIds = [];
$commentIds = [];
$userIds = [];
$attachmentIds = [];
$files = [];
$originalUser = get_current_user_id();
$originalPost = $_POST;
$originalRequest = $_REQUEST;
$originalCookie = $_COOKIE;
$originalServer = $_SERVER;
$_SERVER['REMOTE_ADDR'] ??= '127.0.0.1';
$suffix = bin2hex(random_bytes(6));
$disableCovers = static fn (): string => '0';
add_filter('theme_mod_imperial_auto_featured_image_enabled', $disableCovers);
add_filter('comment_flood_filter', '__return_false', PHP_INT_MAX);
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (! $condition) {
        throw new RuntimeException($message);
    }
    echo 'OK ' . ++$checks . ': ' . $message . PHP_EOL;
};
$newPost = static function (array $attributes = []) use (&$postIds, $suffix): int {
    $id = wp_insert_post(array_merge([
        'post_title' => 'Privacy fixture ' . $suffix,
        'post_content' => 'Privacy fixture.',
        'post_status' => 'publish',
        'comment_status' => 'open',
    ], $attributes), true);
    if (is_wp_error($id)) {
        throw new RuntimeException($id->get_error_message());
    }
    $postIds[] = (int) $id;
    return (int) $id;
};
$newComment = static function (int $postId, string $content, int $userId = 0) use (&$commentIds): int {
    $id = wp_new_comment(wp_slash([
        'comment_post_ID' => $postId,
        'comment_author' => 'Privacy fixture',
        'comment_author_email' => $userId > 0 ? get_userdata($userId)->user_email : '',
        'comment_author_url' => '',
        'comment_content' => $content,
        'comment_approved' => 1,
        'user_id' => $userId,
    ]), true);
    if (is_wp_error($id)) {
        throw new RuntimeException($id->get_error_message());
    }
    $commentIds[] = (int) $id;
    wp_set_comment_status((int) $id, 'approve');
    return (int) $id;
};
$vote = static function (int $commentId): array {
    $_POST = ['comment_id' => $commentId, 'value' => 'up', 'nonce' => wp_create_nonce('imperial_vote_comment')];
    $_REQUEST = $_POST;
    $die = static fn (): Closure => static function (): never { throw new RuntimeException('privacy_json'); };
    add_filter('wp_die_ajax_handler', $die, PHP_INT_MAX);
    ob_start();
    try {
        CommentVoteAjaxController::handle();
        throw new RuntimeException('AJAX did not terminate.');
    } catch (RuntimeException $exception) {
        if ($exception->getMessage() !== 'privacy_json') {
            throw $exception;
        }
        $result = json_decode((string) ob_get_contents(), true, 512, JSON_THROW_ON_ERROR);
        return $result;
    } finally {
        ob_end_clean();
        remove_filter('wp_die_ajax_handler', $die, PHP_INT_MAX);
    }
};

try {
    $userId = wp_insert_user([
        'user_login' => 'privacy-' . $suffix,
        'user_email' => 'privacy-' . $suffix . '@example.test',
        'user_pass' => wp_generate_password(24),
        'role' => 'subscriber',
    ]);
    if (is_wp_error($userId)) {
        throw new RuntimeException($userId->get_error_message());
    }
    $userId = (int) $userId;
    $userIds[] = $userId;
    $public = $newPost();
    $protected = $newPost(['post_password' => 'privacy-password']);
    $private = $newPost(['post_status' => 'private']);
    $draft = $newPost(['post_status' => 'draft']);
    $comment = $newComment($public, 'Visible privacy comment', $userId);
    $hidden = [];
    foreach ([$protected, $private, $draft] as $postId) {
        $hidden[$postId] = $newComment($postId, 'Secret privacy comment ' . $postId);
    }

    // Проверяем сервис и прямой вызов excerpt без доверия к шаблону.
    unset($_COOKIE['wp-postpass_' . COOKIEHASH]);
    foreach ([0, $userId] as $reader) {
        wp_set_current_user($reader);
        $assert(PopularCommentService::forPost($public)?->comment_ID === (string) $comment, 'Открытый комментарий доступен читателю ' . $reader);
        foreach ($hidden as $postId => $commentId) {
            $assert(PopularCommentService::forPost($postId) === null, 'Недоступная запись не выдаёт популярный комментарий: ' . $postId);
            $assert(PopularCommentService::excerpt(get_comment($commentId)) === '', 'Прямой excerpt не раскрывает закрытый комментарий: ' . $commentId);
        }
    }
    wp_set_current_user($userId);
    foreach ($hidden as $commentId) {
        $before = CommentVoteService::counts($commentId);
        $assert($vote($commentId)['success'] === false, 'AJAX запрещает голос за недоступный комментарий ' . $commentId);
        $assert(CommentVoteService::counts($commentId) === $before && CommentVoteService::userVote($commentId, $userId) === '', 'Отказ не меняет голоса');
    }
    $assert($vote($comment)['success'] === true && CommentVoteService::userVote($comment, $userId) === 'up', 'AJAX сохраняет голос за открытый комментарий');
    require_once ABSPATH . WPINC . '/class-phpass.php';
    $_COOKIE['wp-postpass_' . COOKIEHASH] = (new PasswordHash(8, true))->HashPassword('wrong-password');
    $assert(PopularCommentService::forPost($protected) === null && $vote($hidden[$protected])['success'] === false, 'Неправильный пароль не открывает комментарий и голосование');
    $_COOKIE['wp-postpass_' . COOKIEHASH] = (new PasswordHash(8, true))->HashPassword('privacy-password');
    $assert(PopularCommentService::forPost($protected) instanceof WP_Comment, 'Правильный пароль открывает популярный комментарий');
    $assert($vote($hidden[$protected])['success'] === true, 'Правильный пароль разрешает голосование');
    unset($_COOKIE['wp-postpass_' . COOKIEHASH]);

    // Сохранение проходит настоящий pre_comment_content, включая KSES подписчика.
    $code = '<strong>literal</strong> <div>example</div> x < y';
    $markdownCases = [
        'tilde' => "~~~html\n" . $code . "\n~~~",
        'long-tilde' => "~~~~html\n" . $code . "\n~~~\n~~~~~",
        'backtick' => "```html\n" . $code . "\n```",
        'long-backtick' => "````html\n```\n" . $code . "\n````",
        'unclosed' => "~~~html\n" . $code,
        'quote' => "> ~~~html\n> " . $code . "\n> ~~~",
        'crlf' => "Before\r\n\r\n~~~html\r\n" . $code . "\r\n~~~",
        'cr' => "Before\r\r~~~html\r" . $code . "\r~~~",
        'inline' => '``<strong>literal</strong> `nested` <div>example</div> x < y``',
    ];
    foreach ($markdownCases as $label => $content) {
        $preview = MarkdownService::render($content);
        $id = $newComment($public, $content . "\n\n" . $label, $userId);
        $saved = MarkdownService::render(get_comment($id)->comment_content);
        $assert(str_contains($preview, '&lt;strong&gt;literal&lt;/strong&gt;') && str_contains($preview, '&lt;div&gt;example&lt;/div&gt;'), 'Предпросмотр сохраняет код: ' . $label);
        $assert(str_contains($saved, '&lt;strong&gt;literal&lt;/strong&gt;') && str_contains($saved, '&lt;div&gt;example&lt;/div&gt;'), 'KSES не удаляет код: ' . $label);
        wp_update_comment(wp_slash(['comment_ID' => $id, 'comment_content' => $content . "\n\nUpdated " . $label]));
        $assert(str_contains(MarkdownService::render(get_comment($id)->comment_content), '&lt;div&gt;example&lt;/div&gt;'), 'Правка комментария сохраняет код: ' . $label);
    }
    $unsafe = MarkdownService::render('[bad](javascript:alert(1)) <img src=x onerror=alert(1)> <script>alert(1)</script>');
    $assert(! preg_match('/<script|<img[^>]+onerror|href="javascript:/i', $unsafe), 'Защита Markdown от XSS сохранена');
    $assert(str_contains(MarkdownService::render('<strong>Legacy</strong> и <em>HTML</em>'), '<strong>Legacy</strong>'), 'Старый HTML остаётся совместимым');

    // Проверяем полный get_avatar_data, а не только функцию подстановки URL.
    $image = imagecreatetruecolor(32, 32);
    ob_start();
    imagepng($image);
    $png = (string) ob_get_clean();
    imagedestroy($image);
    $upload = wp_upload_bits('privacy-avatar-' . $suffix . '.png', null, $png);
    if ($upload['error']) {
        throw new RuntimeException($upload['error']);
    }
    $files[] = $upload['file'];
    $attachment = wp_insert_attachment(['post_mime_type' => 'image/png', 'post_title' => 'Privacy avatar ' . $suffix], $upload['file'], 0, true);
    if (is_wp_error($attachment)) {
        throw new RuntimeException($attachment->get_error_message());
    }
    $attachmentIds[] = (int) $attachment;
    update_user_meta($userId, 'imperial_avatar_id', (string) $attachment);
    $expected = wp_get_attachment_image_url((int) $attachment, 'thumbnail');
    foreach ([$userId, get_userdata($userId), get_userdata($userId)->user_email, get_comment($comment)] as $identity) {
        $assert(get_avatar_url($identity) === $expected, 'Стандартный get_avatar_url возвращает загруженный аватар');
    }
    $assert(get_avatar_url($userId, ['force_default' => true]) === DefaultAvatar::url(), 'force_default сохраняет заглушку');
    delete_user_meta($userId, 'imperial_avatar_id');
    $assert(get_avatar_url($userId) === DefaultAvatar::url(), 'Без загрузки остаётся локальная заглушка');
} catch (Throwable $exception) {
    $failure = $exception;
} finally {
    // Удаляем только собственные данные; исходные пользователи и подписки не затрагиваются.
    foreach ($commentIds as $id) {
        wp_delete_comment($id, true);
    }
    foreach ($postIds as $id) {
        wp_delete_post($id, true);
    }
    foreach ($attachmentIds as $id) {
        wp_delete_attachment($id, true);
    }
    foreach ($files as $file) {
        wp_delete_file($file);
    }
    foreach ($userIds as $id) {
        delete_transient('imperial_rate_limit_' . md5('comment_vote|user:' . $id));
        wp_delete_user($id);
    }
    wp_set_current_user($originalUser);
    $_POST = $originalPost;
    $_REQUEST = $originalRequest;
    $_COOKIE = $originalCookie;
    $_SERVER = $originalServer;
    remove_filter('theme_mod_imperial_auto_featured_image_enabled', $disableCovers);
    remove_filter('comment_flood_filter', '__return_false', PHP_INT_MAX);
    remove_filter('pre_wp_mail', $captureMail, PHP_INT_MAX);
    remove_filter('pre_http_request', $blockHttp, PHP_INT_MAX);
}

try {
    $assert(! array_filter($postIds, static fn (int $id): bool => get_post($id) instanceof WP_Post), 'Тестовые записи удалены');
    $assert(! array_filter($commentIds, static fn (int $id): bool => get_comment($id) instanceof WP_Comment), 'Тестовые комментарии удалены');
    $assert(! array_filter($userIds, static fn (int $id): bool => get_userdata($id) instanceof WP_User), 'Тестовые пользователи удалены');
    $assert(! array_filter($attachmentIds, static fn (int $id): bool => get_post($id) instanceof WP_Post) && ! array_filter($files, 'is_file'), 'Тестовый аватар и вложение удалены');
} catch (Throwable $exception) {
    $failure ??= $exception;
}

if ($failure !== null) {
    fwrite(STDERR, 'FAIL: ' . $failure->getMessage() . PHP_EOL . $failure->getTraceAsString() . PHP_EOL);
    exit(1);
}
echo 'PASS: ' . $checks . ' privacy checks; fixtures removed.' . PHP_EOL;
