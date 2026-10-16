<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

$_SERVER['REMOTE_ADDR'] ??= '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] ??= 'Imperial Future smoke test';

require dirname(__DIR__, 4) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';

use ImperialFuture\Auth\OAuthStateService;
use ImperialFuture\Auth\ProfileService;
use ImperialFuture\Comments\MarkdownService;
use ImperialFuture\Contact\CaptchaService;
use ImperialFuture\Newsletter\SubscriptionService;
use ImperialFuture\Seed\SeedCoordinator;
use ImperialFuture\Support\SvgSanitizer;

$results = [];
$check = static function (bool $condition, string $message) use (&$results): void {
    $results[] = [$condition ? 'PASS' : 'FAIL', $message];
};
$messages = [];
$mail = static function (mixed $result, array $attributes) use (&$messages): bool {
    $messages[] = $attributes;
    return true;
};
add_filter('pre_wp_mail', $mail, 10, 2);
$newsletterEnabled = static fn (): string => '1';
add_filter('theme_mod_imperial_newsletter_enabled', $newsletterEnabled);
$userIds = [];
$commentId = 0;
$temp = [];
$subscribers = get_option('imperial_newsletter_subscribers', []);
$originalUser = get_current_user_id();

try {
    $html = MarkdownService::render("**Жирный** и *курсив*\n\n> Цитата\n\n- Один\n- Два\n\n```html\n<div>пример</div>\n```\n\n`x < y`\n");
    $check(str_contains($html, '<strong>Жирный</strong>') && str_contains($html, '<blockquote>') && str_contains($html, '<ul>'), 'Markdown: выделение, цитата, список');
    $check(str_contains($html, '&lt;div&gt;пример&lt;/div&gt;') && str_contains($html, 'x &lt; y'), 'Markdown: HTML и операторы внутри кода не теряются');
    $check(str_contains(MarkdownService::render('<strong>Да</strong> и <em>нет</em>'), '<strong>Да</strong>'), 'Старые HTML-комментарии совместимы');
    $post = get_posts(['numberposts' => 1, 'post_type' => 'post'])[0];
    $preview = \ImperialFuture\Comments\PopularCommentService::excerpt(new WP_Comment((object) ['comment_post_ID' => $post->ID, 'comment_content' => "**Жирный**\n\n> Цитата\n\n`x < y`"]));
    $check(str_contains($preview, 'Жирный') && ! str_contains($preview, '**') && str_contains($preview, 'x < y'), 'Популярный комментарий: Markdown преобразован в читаемый текст без потери кода');
    $unsafe = MarkdownService::render('[опасно](javascript:alert(1)) <img src=x onerror=alert(1)> <script>alert(1)</script>');
    $check(! preg_match('/<script|<img[^>]+onerror|href="javascript:/i', $unsafe), 'Markdown: XSS и javascript-ссылки не исполняются');

    $safeSvg = tempnam(sys_get_temp_dir(), 'imperial-svg-');
    $temp[] = $safeSvg;
    file_put_contents($safeSvg, '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h10v10z"/></svg>');
    $check(SvgSanitizer::isSafeFile($safeSvg), 'SVG: безопасная геометрия разрешена');
    file_put_contents($safeSvg, '<svg xmlns="http://www.w3.org/2000/svg"><use href="java&#x73;cript:alert(1)"/></svg>');
    $check(! SvgSanitizer::isSafeFile($safeSvg), 'SVG: закодированный javascript отклонён');
    file_put_contents($safeSvg, '<!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]><svg xmlns="http://www.w3.org/2000/svg"><text>&x;</text></svg>');
    $check(! SvgSanitizer::isSafeFile($safeSvg), 'SVG: внешние XML-сущности отклонены');

    $state = OAuthStateService::issue('yandex');
    $check(! OAuthStateService::consume('yandex', $state), 'OAuth: без cookie запрос отклонён');
    $_COOKIE['imperial_oauth_state'] = $state;
    $check(OAuthStateService::consume('yandex', $state), 'OAuth: правильный browser-state принят');
    $check(! OAuthStateService::consume('yandex', $state), 'OAuth: state нельзя повторить');

    $suffix = bin2hex(random_bytes(4));
    foreach (['a', 'b'] as $key) {
        $id = wp_insert_user(['user_login' => 'qa-' . $suffix . $key, 'user_pass' => wp_generate_password(24), 'user_email' => 'qa-' . $suffix . $key . '@example.test', 'role' => 'subscriber']);
        if (is_wp_error($id)) throw new RuntimeException($id->get_error_message());
        $userIds[] = (int) $id;
    }
    $user = get_userdata($userIds[0]);
    wp_set_current_user($userIds[0]);
    $check(SubscriptionService::requestConfirmation($user->user_email, 'email'), 'Почта: отдельное подтверждение запрошено');
    preg_match('~https?://[^\s]+~', $messages[array_key_last($messages)]['message'], $confirmationUrl);
    parse_str((string) parse_url($confirmationUrl[0], PHP_URL_QUERY), $confirmation);
    $check(SubscriptionService::confirm($confirmation['token']), 'Почта: одноразовая ссылка подтверждает владение');
    $check(ProfileService::save($userIds[0], ['display_name' => 'Проверка профиля', 'email' => $user->user_email, 'digest' => '1']), 'Профиль: корректные поля сохраняются');
    $check(isset(SubscriptionService::subscribers()[$user->user_email]), 'Дайджест: включённая подписка сохраняется');
    $check(! ProfileService::save($userIds[0], ['display_name' => 'Неверная правка', 'email' => get_userdata($userIds[1])->user_email]), 'Профиль: занятая почта не даёт ложного успеха');
    $check(get_userdata($userIds[0])->display_name === 'Проверка профиля', 'Профиль не перезаписан при ошибке');
    ProfileService::save($userIds[0], ['display_name' => 'Проверка профиля', 'email' => $user->user_email]);
    $check(! isset(SubscriptionService::subscribers()[$user->user_email]), 'Дайджест: снятие галочки удаляет подписку');

    wp_set_current_user($userIds[0]);
    $content = "**Тест**\n\n```html\n<div>пример</div>\n```";
    $commentId = (int) wp_new_comment(wp_slash(['comment_post_ID' => $post->ID, 'comment_author' => 'QA', 'comment_author_email' => $user->user_email, 'comment_author_url' => '', 'comment_content' => $content, 'user_id' => $userIds[0]]));
    $check($commentId > 0 && str_contains(MarkdownService::render(get_comment($commentId)->comment_content), '&lt;div&gt;пример&lt;/div&gt;'), 'Комментарий: код сохраняется при отправке');
    wp_update_comment(wp_slash(['comment_ID' => $commentId, 'comment_content' => $content . "\n\n*Правка*"]));
    $saved = MarkdownService::render(get_comment($commentId)->comment_content);
    $check(str_contains($saved, '&lt;div&gt;пример&lt;/div&gt;') && str_contains($saved, '<em>Правка</em>'), 'Комментарий: редактор админки сохраняет Markdown и код');

    $captcha = CaptchaService::create();
    $check(! isset($captcha['target']), 'Капча: ответ не передаётся в HTML');
    $payload = CaptchaService::payload($captcha['token']);
    $payload['created_at'] = time() - 3;
    set_transient('imperial_contact_captcha_' . sanitize_key($captcha['token']), $payload, 60);
    $check(CaptchaService::validate($captcha['token'], '', (string) $payload['target']), 'Капча: корректное положение принимается');
    $check(! CaptchaService::validate($captcha['token'], '', (string) $payload['target']), 'Капча: повторное использование запрещено');
    $captcha = CaptchaService::create();
    $payload = CaptchaService::payload($captcha['token']);
    $payload['created_at'] = time() - 3;
    set_transient('imperial_contact_captcha_' . sanitize_key($captcha['token']), $payload, 60);
    $check(! CaptchaService::validate($captcha['token'], '', '0'), 'Капча: неверное положение отклонено');
    $captcha = CaptchaService::create();
    $payload = CaptchaService::payload($captcha['token']);
    $payload['created_at'] = time() + 1;
    set_transient('imperial_contact_captcha_' . sanitize_key($captcha['token']), $payload, 60);
    $check(! CaptchaService::validate($captcha['token'], '', (string) $payload['target']), 'Капча: мгновенная отправка отклонена');

    wp_set_current_user(1);
    SeedCoordinator::seed();
    $snapshot = static fn () => wp_json_encode([
        get_posts(['numberposts' => -1, 'post_status' => 'any']),
        get_option('sidebars_widgets'), get_option('widget_block'), get_nav_menu_locations(),
        get_option('show_on_front'), get_option('users_can_register'),
    ]);
    $before = $snapshot();
    SeedCoordinator::seed();
    $check($before === $snapshot(), 'Демо: повторный импорт не меняет записи, меню, виджеты и настройки');
} finally {
    if ($commentId > 0) wp_delete_comment($commentId, true);
    foreach ($userIds as $id) wp_delete_user($id);
    foreach ($temp as $file) unlink($file);
    update_option('imperial_newsletter_subscribers', $subscribers, false);
    wp_set_current_user($originalUser);
    remove_filter('pre_wp_mail', $mail);
    remove_filter('theme_mod_imperial_newsletter_enabled', $newsletterEnabled);
    delete_transient('imperial_rate_limit_' . md5('newsletter_email|' . $user->user_email));
}
foreach ($results as [$status, $message]) echo "$status: $message\n";
exit(count(array_filter($results, static fn ($result) => $result[0] === 'FAIL')) > 0 ? 1 : 0);
