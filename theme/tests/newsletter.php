<?php
declare(strict_types=1);

use ImperialFuture\Newsletter\CommentNotificationService;
use ImperialFuture\Newsletter\DigestScheduler;
use ImperialFuture\Newsletter\DigestService;
use ImperialFuture\Newsletter\NewsletterHandler;
use ImperialFuture\Newsletter\NewsletterLock;
use ImperialFuture\Newsletter\SubscriptionService;

if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

define('DISABLE_WP_CRON', true);
$root = dirname(__DIR__, 4);
require_once $root . '/wp-includes/plugin.php';

// Перехват действует уже при загрузке WP, включая сторонние почтовые хуки.
$mail = [];
$failEmail = '';
$capture = static function (mixed $result, array $attributes) use (&$mail, &$failEmail): bool {
    $mail[] = $attributes;
    return $failEmail === '' || $attributes['to'] !== $failEmail;
};
add_filter('pre_wp_mail', $capture, PHP_INT_MAX, 2);
require_once $root . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';

$options = [];
$postIds = [];
$commentIds = [];
$userIds = [];
$rateKeys = [];
$checks = 0;
$failure = null;
$suffix = bin2hex(random_bytes(6));
$guest = 'newsletter-' . $suffix . '@example.test';
$failed = 'newsletter-fail-' . $suffix . '@example.test';
$ip = 'newsletter-test-' . $suffix;
$originalServer = $_SERVER;
$originalGet = $_GET;
$originalPost = $_POST;
$originalRequest = $_REQUEST;
$originalUser = get_current_user_id();
$originalNewsletterMod = get_theme_mod('imperial_newsletter_enabled', null);
$disableCovers = static fn (): string => '0';
add_filter('theme_mod_imperial_auto_featured_image_enabled', $disableCovers);

$saveOption = static function (string $name) use (&$options): void {
    $missing = new stdClass();
    $value = get_option($name, $missing);
    $options[$name] = ['exists' => $value !== $missing, 'value' => $value];
};
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (! $condition) {
        throw new RuntimeException($message);
    }
    ++$checks;
    echo 'OK ' . $checks . ': ' . $message . PHP_EOL;
};
$trackRate = static function (string $scope, string $identity) use (&$rateKeys, $saveOption): void {
    $key = 'imperial_rate_limit_' . md5($scope . '|' . $identity);
    $rateKeys[] = $key;
    $saveOption('_transient_' . $key);
    $saveOption('_transient_timeout_' . $key);
};
$urlArgs = static function (string $message): array {
    preg_match('~https?://[^\s]+~', $message, $match);
    parse_str((string) parse_url($match[0] ?? '', PHP_URL_QUERY), $args);
    return $args;
};
$endpoint = static function (string $action, array $get = [], array $post = []): string {
    $_GET = $get;
    $_POST = $post;
    $_REQUEST = array_merge($get, $post);
    $_SERVER['HTTP_REFERER'] = home_url('/');
    $redirect = static function (string $location): never {
        throw new RuntimeException('redirect:' . $location);
    };
    add_filter('wp_redirect', $redirect, PHP_INT_MAX);

    try {
        do_action((is_user_logged_in() ? 'admin_post_' : 'admin_post_nopriv_') . $action);
        throw new RuntimeException('No redirect');
    } catch (RuntimeException $exception) {
        if (! str_starts_with($exception->getMessage(), 'redirect:')) {
            throw $exception;
        }
        parse_str((string) parse_url(substr($exception->getMessage(), 9), PHP_URL_QUERY), $args);
        return (string) ($args['newsletter_status'] ?? '');
    } finally {
        remove_filter('wp_redirect', $redirect, PHP_INT_MAX);
    }
};
$newPost = static function (string $title, array $attributes = []) use (&$postIds): int {
    $id = wp_insert_post(array_merge([
        'post_title' => $title,
        'post_type' => 'post',
        'post_status' => 'publish',
        'post_content' => 'Newsletter integration fixture.',
        'post_date' => wp_date('Y-m-d H:i:s', time() - HOUR_IN_SECONDS),
        'post_date_gmt' => gmdate('Y-m-d H:i:s', time() - HOUR_IN_SECONDS),
    ], $attributes), true);

    if (is_wp_error($id)) {
        throw new RuntimeException($id->get_error_message());
    }
    $postIds[] = (int) $id;
    return (int) $id;
};
$onlyFixtureUsers = static function (WP_User_Query $query) use (&$userIds): void {
    if (($query->query_vars['meta_key'] ?? '') === SubscriptionService::COMMENTS_META) {
        $query->set('include', $userIds ?: [0]);
    }
};

try {
    foreach (['imperial_newsletter_subscribers', DigestService::ISSUE_OPTION, DigestService::PREVIOUS_OPTION, 'imperial_newsletter_lock_' . md5('subscriptions'), 'imperial_newsletter_lock_' . md5('digest'), '_transient_imperial_demo_import_running', '_transient_timeout_imperial_demo_import_running'] as $name) {
        $saveOption($name);
    }
    $cronBefore = _get_cron_array();
    $trackRate('newsletter_subscribe', 'ip:' . $ip);
    $trackRate('newsletter_email', $guest);
    $trackRate('newsletter_email', $failed);
    $_SERVER['REMOTE_ADDR'] = $ip;
    wp_set_current_user(0);
    update_option('imperial_newsletter_subscribers', [], false);
    delete_option(DigestService::ISSUE_OPTION);
    delete_option(DigestService::PREVIOUS_OPTION);
    delete_transient('imperial_demo_import_running');
    DigestScheduler::registerHooks();
    NewsletterHandler::registerHooks();
    CommentNotificationService::registerHooks();
    add_action('pre_get_users', $onlyFixtureUsers);
    set_theme_mod('imperial_newsletter_enabled', '1');
    DigestScheduler::clear();
    DigestScheduler::sync();

    // Проверяем публичную форму, одноразовость, TTL и отсутствие выдачи адреса.
    $mail = [];
    $form = ['_wpnonce' => wp_create_nonce('imperial_newsletter'), 'newsletter_email' => $guest];
    $assert($endpoint('imperial_newsletter_subscribe', [], []) === 'error', 'Форма отклоняет запрос без nonce');
    $assert($endpoint('imperial_newsletter_subscribe', [], $form) === 'confirmation_sent', 'Гостю возвращается confirmation_sent');
    $assert(count($mail) === 1 && SubscriptionService::confirmedSubscribers() === [], 'До подтверждения адрес не активен');
    $confirmation = $urlArgs($mail[0]['message']);
    $assert(isset($confirmation['token']) && strlen($confirmation['token']) === 64, 'Письмо содержит случайный токен');
    $stored = SubscriptionService::subscribers()[$guest];
    $assert($stored['confirmation_hash'] !== $confirmation['token'], 'В базе хранится хеш, а не токен');
    $assert($endpoint('imperial_newsletter_confirm', ['token' => str_repeat('0', 64)]) === 'error', 'Поддельное подтверждение отклоняется');
    $assert($endpoint('imperial_newsletter_confirm', $confirmation) === 'subscribed', 'Ссылка активирует подписку без входа');
    $assert($endpoint('imperial_newsletter_confirm', $confirmation) === 'error', 'Одноразовый токен не принимается повторно');
    $assert($endpoint('imperial_newsletter_subscribe', [], $form) === 'confirmation_sent', 'Активный адрес получает тот же публичный ответ');
    $expired = $urlArgs($mail[array_key_last($mail)]['message']);
    $records = SubscriptionService::subscribers();
    $records[$guest]['confirmation_expires'] = time() - 1;
    update_option('imperial_newsletter_subscribers', $records, false);
    $assert(! SubscriptionService::confirm($expired['token']), 'Истёкший токен отклоняется');
    $assert(isset(SubscriptionService::confirmedSubscribers()[$guest]), 'Истечение повторного подтверждения не отменяет активную подписку');
    SubscriptionService::requestConfirmation($guest);
    $before = count($mail);
    $assert(SubscriptionService::requestConfirmation($guest) && count($mail) === $before, 'Лимит адреса не отправляет лишнее письмо и не выдаёт наличие адреса');
    $failEmail = $failed;
    $assert(! SubscriptionService::requestConfirmation($failed), 'Отказ wp_mail не маскируется под отправку подтверждения');
    $assert(empty(SubscriptionService::subscribers()[$failed]['confirmation_hash']), 'Неотправленный токен не остаётся действующим');
    $failEmail = '';
    $assert(SubscriptionService::requestConfirmation($failed), 'После сбоя можно повторить подтверждение');
    $expiredPending = $urlArgs($mail[array_key_last($mail)]['message']);
    $records = SubscriptionService::subscribers();
    $records[$failed]['confirmation_expires'] = time() - 1;
    update_option('imperial_newsletter_subscribers', $records, false);
    $assert(! SubscriptionService::confirm($expiredPending['token']) && ! isset(SubscriptionService::confirmedSubscribers()[$failed]), 'TTL не позволяет активировать неподтверждённый адрес');
    for ($i = 0; $i < 6; ++$i) {
        NewsletterHandler::subscriptionStatus($form);
    }
    $assert(NewsletterHandler::subscriptionStatus($form) === 'error', 'Лимит IP действует на публичную форму');

    // Сначала доказываем владение email, затем сохраняем настройки без повторного письма.
    $userId = wp_insert_user(['user_login' => 'newsletter_' . $suffix, 'user_email' => 'newsletter-user-' . $suffix . '@example.test', 'user_pass' => wp_generate_password()]);
    if (is_wp_error($userId)) {
        throw new RuntimeException($userId->get_error_message());
    }
    $userIds[] = (int) $userId;
    $user = get_userdata((int) $userId);
    $mail = [];
    $assert(! SubscriptionService::updateUserPreferences((int) $userId, true, true, true) && $mail === [], 'Неподтверждённый аккаунт не активирует уведомления и не запускает скрытое подтверждение');
    wp_set_current_user((int) $userId);
    $trackRate('newsletter_email', $user->user_email);
    $assert(SubscriptionService::requestConfirmation($user->user_email, 'email'), 'Кабинет запрашивает отдельное подтверждение email');
    $emailConfirmation = $urlArgs($mail[0]['message']);
    $assert($endpoint('imperial_newsletter_confirm', $emailConfirmation) === 'email_confirmed', 'Ссылка подтверждает email без подписки на дайджест');
    $assert(! isset(SubscriptionService::confirmedSubscribers()[$user->user_email]) && ! SubscriptionService::userDigestEnabled((int) $userId), 'Подтверждение email не создаёт дайджест');
    $mail = [];
    SubscriptionService::updateUserPreferences((int) $userId, true, true, true);
    $assert(SubscriptionService::userDigestEnabled((int) $userId) && isset(SubscriptionService::confirmedSubscribers()[$user->user_email]) && $mail === [], 'Подтверждённый владелец явно включает дайджест без повторного письма');
    parse_str((string) parse_url(SubscriptionService::unsubscribeUrl($user->user_email), PHP_URL_QUERY), $unsubscribe);
    $assert(! SubscriptionService::unsubscribeSigned($user->user_email, str_repeat('0', 64)), 'Поддельная подпись не отписывает');
    $assert($endpoint('imperial_newsletter_unsubscribe', $unsubscribe) === 'unsubscribed' && ! SubscriptionService::userDigestEnabled((int) $userId), 'Ссылка отписывает без входа и обновляет digest preference');
    $assert(SubscriptionService::userCommentsEnabled((int) $userId) && SubscriptionService::userRepliesEnabled((int) $userId), 'Отписка от дайджеста не меняет комментарии и ответы');
    $assert($endpoint('imperial_newsletter_unsubscribe', $unsubscribe) === 'unsubscribed', 'Повторная отписка возвращает тот же статус');
    SubscriptionService::updateUserPreferences((int) $userId, true, true, true);
    parse_str((string) parse_url(SubscriptionService::unsubscribeUrl($user->user_email), PHP_URL_QUERY), $newUnsubscribe);
    $subscriptionLock = NewsletterLock::acquire('subscriptions');
    $assert($endpoint('imperial_newsletter_unsubscribe', $newUnsubscribe) === 'error', 'Сбой блокировки не выдаётся за успешную отписку');
    NewsletterLock::release('subscriptions', $subscriptionLock);
    $assert(! SubscriptionService::unsubscribeSigned($user->user_email, $unsubscribe['signature']), 'Старая ссылка не отменяет новую подписку');
    SubscriptionService::updateUserPreferences((int) $userId, false, true, true);
    $assert(! isset(SubscriptionService::confirmedSubscribers()[$user->user_email]), 'Preferences отключают дайджест');
    $records = SubscriptionService::subscribers();
    $records[$user->user_email] = ['email' => $user->user_email, 'created_at' => current_time('mysql')];
    $records['legacy-guest@example.test'] = ['email' => 'legacy-guest@example.test'];
    update_option('imperial_newsletter_subscribers', $records, false);
    update_user_meta($userId, SubscriptionService::DIGEST_META, '1');
    DigestScheduler::sync();
    $assert(isset(SubscriptionService::confirmedSubscribers()[$user->user_email]) && ! isset(SubscriptionService::confirmedSubscribers()['legacy-guest@example.test']), 'Старая подписка из кабинета сохраняется, гостевой адрес не активируется');
    SubscriptionService::updateUserPreferences((int) $userId, false, true, true);
    $trackRate('newsletter_email', $user->user_email);
    update_user_meta($userId, SubscriptionService::DIGEST_META, '1');
    SubscriptionService::requestConfirmation($user->user_email);
    DigestScheduler::sync();
    $assert(! isset(SubscriptionService::confirmedSubscribers()[$user->user_email]), 'Новый гостевой opt-in не активируется миграцией старого preference');
    SubscriptionService::updateUserPreferences((int) $userId, false, true, true);

    // Неподтверждённый профиль не получает права на гостевые подписки.
    $subscriptionsBeforePrivacy = SubscriptionService::subscribers();
    $victim = 'privacy-guest-' . $suffix . '@example.test';
    $secondVictim = 'privacy-second-' . $suffix . '@example.test';
    SubscriptionService::subscribe($victim);
    SubscriptionService::subscribe($secondVictim);
    $privacyUserId = wp_insert_user(['user_login' => 'privacy-' . $suffix, 'user_email' => $victim, 'user_pass' => wp_generate_password(24), 'role' => 'subscriber']);
    if (is_wp_error($privacyUserId)) {
        throw new RuntimeException($privacyUserId->get_error_message());
    }
    $userIds[] = (int) $privacyUserId;
    wp_set_current_user((int) $privacyUserId);
    $guestBefore = SubscriptionService::subscribers()[$victim];
    $assert(SubscriptionService::updateUserPreferences((int) $privacyUserId, false, false, false), 'Регистрация сохраняет выключенные уведомления');
    $assert(SubscriptionService::subscribers()[$victim] === $guestBefore, 'Регистрация на чужой email не отписывает гостя');
    $assert(! SubscriptionService::updateUserPreferences((int) $privacyUserId, true, true, true), 'Неподтверждённый профиль не включает почтовые уведомления');
    $assert(\ImperialFuture\Auth\ProfileService::save((int) $privacyUserId, ['display_name' => 'Читатель', 'email' => $secondVictim]), 'Профиль без уведомлений допускает смену email');
    // После редиректа WordPress загружает текущего пользователя заново.
    wp_set_current_user(0);
    wp_set_current_user((int) $privacyUserId);
    $assert(SubscriptionService::subscribers()[$victim] === $guestBefore && isset(SubscriptionService::confirmedSubscribers()[$secondVictim]), 'Смена email не меняет гостевые подписки старого и нового адреса');
    $trackRate('newsletter_email', $secondVictim);
    set_theme_mod('imperial_newsletter_enabled', '0');
    $assert(SubscriptionService::requestConfirmation($secondVictim, 'email'), 'Email подтверждается независимо от включения дайджеста');
    $privacyToken = $urlArgs($mail[array_key_last($mail)]['message'])['token'] ?? '';
    $assert(SubscriptionService::confirm($privacyToken) && SubscriptionService::isEmailVerified((int) $privacyUserId), 'Одноразовый токен подтверждает адрес его владельцу');
    $assert(! SubscriptionService::confirm($privacyToken), 'Токен подтверждения email нельзя использовать повторно');
    $assert(SubscriptionService::updateUserPreferences((int) $privacyUserId, false, true, true), 'Уведомления работают при отключённом дайджесте');
    $assert(! isset(SubscriptionService::confirmedSubscribers()[$secondVictim]), 'Подтверждённый владелец может снять старую гостевую подписку');
    $assert(SubscriptionService::userCommentsEnabled((int) $privacyUserId) && SubscriptionService::userRepliesEnabled((int) $privacyUserId), 'Комментарии и ответы включаются отдельно от дайджеста');
    $assert(! SubscriptionService::requestConfirmation('disabled-' . $suffix . '@example.test'), 'Отключённый дайджест не принимает гостевой opt-in');
    $freshEmail = 'privacy-fresh-' . $suffix . '@example.test';
    wp_update_user(['ID' => $privacyUserId, 'user_email' => $freshEmail]);
    wp_set_current_user(0);
    wp_set_current_user((int) $privacyUserId);
    $trackRate('newsletter_email', $freshEmail);
    $assert(! SubscriptionService::userCommentsEnabled((int) $privacyUserId), 'Смена email прекращает уведомления до нового подтверждения');
    $assert(SubscriptionService::requestConfirmation($freshEmail, 'email'), 'Новый адрес получает отдельное подтверждение');
    $freshToken = $urlArgs($mail[array_key_last($mail)]['message'])['token'] ?? '';
    $assert(SubscriptionService::confirm($freshToken), 'Новый адрес подтверждается при выключенном дайджесте');
    $assert(! isset(SubscriptionService::confirmedSubscribers()[$freshEmail]) && ! SubscriptionService::userDigestEnabled((int) $privacyUserId), 'Подтверждение нового адреса само не создаёт подписку');
    wp_set_current_user(0);
    $assert(! SubscriptionService::requestConfirmation($freshEmail, 'email'), 'Гость не может выпускать подтверждение владения для аккаунта');
    SubscriptionService::updateUserPreferences((int) $privacyUserId, false, false, false);
    update_option('imperial_newsletter_subscribers', $subscriptionsBeforePrivacy, false);
    set_theme_mod('imperial_newsletter_enabled', '1');
    wp_set_current_user((int) $userId);

    // Расписание не дублируется и очищается при отключении и смене темы.
    DigestScheduler::sync();
    $event = wp_get_scheduled_event(DigestScheduler::WEEKLY_HOOK);
    $assert($event !== false && $event->schedule === 'weekly', 'Cron использует weekly');
    DigestScheduler::sync();
    $assert(wp_next_scheduled(DigestScheduler::WEEKLY_HOOK) === $event->timestamp, 'Повторная регистрация не создаёт второе событие');
    DigestScheduler::scheduleBatch();
    set_theme_mod('imperial_newsletter_enabled', '0');
    $assert(! wp_next_scheduled(DigestScheduler::WEEKLY_HOOK) && ! wp_next_scheduled(DigestScheduler::BATCH_HOOK), 'Отключение очищает weekly и партии сразу');
    $assert(! SubscriptionService::requestConfirmation($failed), 'Отключённая рассылка не принимает opt-in');
    set_theme_mod('imperial_newsletter_enabled', '1');
    do_action('after_switch_theme');
    $assert(! wp_next_scheduled(DigestScheduler::WEEKLY_HOOK), 'after_switch_theme очищает cron');
    DigestScheduler::sync();
    do_action('switch_theme', 'integration fixture');
    $assert(! wp_next_scheduled(DigestScheduler::WEEKLY_HOOK), 'switch_theme очищает cron до выгрузки старой темы');
    DigestScheduler::sync();

    // Только опубликованные общедоступные post за последние семь суток.
    $publicPost = $newPost('Newsletter public ' . $suffix);
    $passwordPost = $newPost('Newsletter password ' . $suffix, ['post_password' => 'secret']);
    $privatePost = $newPost('Newsletter private ' . $suffix, ['post_status' => 'private']);
    $draftPost = $newPost('Newsletter draft ' . $suffix, ['post_status' => 'draft']);
    $oldPost = $newPost('Newsletter old ' . $suffix, ['post_date' => wp_date('Y-m-d H:i:s', time() - 8 * DAY_IN_SECONDS), 'post_date_gmt' => gmdate('Y-m-d H:i:s', time() - 8 * DAY_IN_SECONDS)]);
    $page = $newPost('Newsletter page ' . $suffix, ['post_type' => 'page']);
    $withdrawn = $newPost('Newsletter withdrawn ' . $suffix);
    update_option('imperial_newsletter_subscribers', ['legacy@example.test' => ['email' => 'legacy@example.test']], false);
    for ($i = 0; $i < 12; ++$i) {
        SubscriptionService::subscribe('batch-' . $i . '-' . $suffix . '@example.test');
    }
    $batchFailed = 'batch-0-' . $suffix . '@example.test';
    $removed = 'batch-11-' . $suffix . '@example.test';
    $mail = [];
    do_action(DigestScheduler::WEEKLY_HOOK);
    $issue = get_option(DigestService::ISSUE_OPTION);
    $assert(in_array($publicPost, $issue['posts'], true) && ! array_intersect([$passwordPost, $privatePost, $draftPost, $oldPost, $page], $issue['posts']), 'Выпуск содержит только разрешённые публикации');
    $assert(count($issue['recipients']) === 12 && ! isset($issue['recipients']['legacy@example.test']), 'Неподтверждённые старые адреса исключены');
    wp_update_post(['ID' => $withdrawn, 'post_status' => 'draft']);
    $failEmail = $batchFailed;
    do_action(DigestScheduler::BATCH_HOOK);
    $issue = get_option(DigestService::ISSUE_OPTION);
    $assert(count($mail) === DigestService::BATCH_SIZE, 'Партия ограничена десятью письмами');
    $assert($issue['recipients'][$batchFailed]['status'] === 'retry', 'Сбой сохранён для повторной попытки');
    $assert(str_contains($mail[0]['message'], get_permalink($publicPost)) && ! str_contains($mail[0]['message'], get_the_title($withdrawn)), 'Видимость повторно проверяется перед отправкой');
    $assert(str_contains($mail[0]['message'], 'imperial_newsletter_unsubscribe'), 'В дайджесте есть защищённая ссылка отписки');
    SubscriptionService::unsubscribe($removed);
    do_action(DigestScheduler::BATCH_HOOK);
    $assert(count($mail) === 11, 'Отписавшийся между партиями не получает выпуск');
    for ($i = 0; $i < 2; ++$i) {
        $issue = get_option(DigestService::ISSUE_OPTION);
        $issue['recipients'][$batchFailed]['next_at'] = 0;
        update_option(DigestService::ISSUE_OPTION, $issue, false);
        do_action(DigestScheduler::BATCH_HOOK);
    }
    $issue = get_option(DigestService::ISSUE_OPTION);
    $assert($issue['status'] === 'completed_with_errors' && $issue['recipients'][$batchFailed]['attempts'] === 3 && $issue['recipients'][$batchFailed]['status'] === 'failed', 'Три неудачи завершаются сохранённой ошибкой');
    $assert(! wp_next_scheduled(DigestScheduler::BATCH_HOOK), 'Завершённый выпуск не оставляет партии');
    $count = count($mail);
    do_action(DigestScheduler::WEEKLY_HOOK);
    do_action(DigestScheduler::BATCH_HOOK);
    $assert(count($mail) === $count, 'Повторный weekly и batch не дублируют выпуск');
    $failEmail = '';

    // При обрыве отправки не повторяем письмо с неизвестным результатом.
    $issue['status'] = 'sending';
    $issue['recipients'][$batchFailed]['status'] = 'sending';
    update_option(DigestService::ISSUE_OPTION, $issue, false);
    do_action(DigestScheduler::BATCH_HOOK);
    $issue = get_option(DigestService::ISSUE_OPTION);
    $assert(count($mail) === $count && $issue['recipients'][$batchFailed]['status'] === 'uncertain', 'Прерванная передача отмечается явно, без дубля');
    $lock = NewsletterLock::acquire('digest');
    $assert($lock !== false && NewsletterLock::acquire('digest') === false, 'Блокировка не допускает параллельную обработку');
    NewsletterLock::release('digest', $lock);
    set_theme_mod('imperial_newsletter_enabled', '0');
    do_action(DigestScheduler::WEEKLY_HOOK);
    do_action(DigestScheduler::BATCH_HOOK);
    $assert(count($mail) === $count, 'Отключённый cron не отправляет письма');
    set_theme_mod('imperial_newsletter_enabled', '1');

    // Одобрение после модерации, повторное одобрение, ответы и демоимпорт.
    $mail = [];
    $parent = wp_insert_comment(['comment_post_ID' => $publicPost, 'user_id' => $userId, 'comment_author_email' => $user->user_email, 'comment_content' => 'Parent', 'comment_approved' => 1]);
    $commentIds[] = (int) $parent;
    $comment = wp_insert_comment(['comment_post_ID' => $publicPost, 'comment_parent' => $parent, 'comment_author_email' => 'author-' . $suffix . '@example.test', 'comment_content' => 'Pending reply', 'comment_approved' => 0]);
    $commentIds[] = (int) $comment;
    $assert($mail === [], 'Неодобренный комментарий не отправляет уведомления');
    wp_set_comment_status($comment, 'approve');
    $assert(count($mail) === 1 && $mail[0]['to'] === $user->user_email, 'Одобрение уведомляет подписчика и автора родителя без дубля');
    do_action('comment_post', $comment, 1, []);
    wp_set_comment_status($comment, 'hold');
    wp_set_comment_status($comment, 'approve');
    $assert(count($mail) === 1, 'Повторное одобрение и comment_post не дублируют письмо');
    $immediate = wp_insert_comment(['comment_post_ID' => $publicPost, 'comment_author_email' => 'author-' . $suffix . '@example.test', 'comment_content' => 'Immediate', 'comment_approved' => 1]);
    $commentIds[] = (int) $immediate;
    do_action('comment_post', $immediate, 1, []);
    $assert(count($mail) === 2, 'Сразу одобренный comment_post по-прежнему работает');
    set_transient('imperial_demo_import_running', '1', MINUTE_IN_SECONDS);
    $demo = wp_insert_comment(['comment_post_ID' => $publicPost, 'comment_author_email' => 'demo@example.test', 'comment_content' => 'Demo', 'comment_approved' => 0]);
    $commentIds[] = (int) $demo;
    wp_set_comment_status($demo, 'approve');
    do_action('comment_post', $demo, 1, []);
    add_comment_meta($demo, '_imperial_seed_comment', '1');
    delete_transient('imperial_demo_import_running');
    wp_set_comment_status($demo, 'hold');
    wp_set_comment_status($demo, 'approve');
    $assert(count($mail) === 2, 'Демоимпорт и последующее одобрение демо не шлют писем');
    $badComment = wp_insert_comment(['comment_post_ID' => $publicPost, 'comment_author_email' => 'author-' . $suffix . '@example.test', 'comment_content' => 'Mail failure', 'comment_approved' => 0]);
    $commentIds[] = (int) $badComment;
    $failEmail = $user->user_email;
    wp_set_comment_status($badComment, 'approve');
    $deliveries = get_comment_meta($badComment, '_imperial_notification_deliveries', true);
    $assert(($deliveries[hash('sha256', $user->user_email)]['status'] ?? '') === 'failed', 'Ошибка уведомления комментария сохранена');
    $failEmail = '';
    wp_set_comment_status($badComment, 'hold');
    wp_set_comment_status($badComment, 'approve');
    $deliveries = get_comment_meta($badComment, '_imperial_notification_deliveries', true);
    $assert($deliveries[hash('sha256', $user->user_email)]['status'] === 'sent', 'Повторное одобрение повторяет только неудавшееся уведомление');
} catch (Throwable $exception) {
    $failure = $exception;
} finally {
    // Возвращаем исходные настройки и только наши cron-хуки; чужие события сохраняем.
    remove_action('pre_get_users', $onlyFixtureUsers);
    foreach ($commentIds as $id) {
        wp_delete_comment($id, true);
        delete_option('imperial_newsletter_lock_' . md5('comment:' . $id));
    }
    foreach ($postIds as $id) {
        wp_delete_post($id, true);
    }
    foreach ($userIds as $id) {
        wp_delete_user($id);
    }
    foreach ($rateKeys as $key) {
        delete_transient($key);
    }
    foreach ($options as $name => $saved) {
        if ($saved['exists']) {
            update_option($name, $saved['value'], false);
        } else {
            delete_option($name);
        }
    }
    if ($originalNewsletterMod === null) {
        remove_theme_mod('imperial_newsletter_enabled');
    } else {
        set_theme_mod('imperial_newsletter_enabled', $originalNewsletterMod);
    }
    foreach ([DigestScheduler::WEEKLY_HOOK, DigestScheduler::BATCH_HOOK] as $hook) {
        wp_unschedule_hook($hook);
        foreach ($cronBefore ?? [] as $timestamp => $hooks) {
            foreach ($hooks[$hook] ?? [] as $event) {
                if ($event['schedule']) {
                    wp_schedule_event((int) $timestamp, $event['schedule'], $hook, $event['args']);
                } else {
                    wp_schedule_single_event((int) $timestamp, $hook, $event['args']);
                }
            }
        }
    }
    wp_set_current_user($originalUser);
    $_SERVER = $originalServer;
    $_GET = $originalGet;
    $_POST = $originalPost;
    $_REQUEST = $originalRequest;
    remove_filter('theme_mod_imperial_auto_featured_image_enabled', $disableCovers);
    remove_filter('pre_wp_mail', $capture, PHP_INT_MAX);
}

try {
    foreach ($options as $name => $saved) {
        $missing = new stdClass();
        $restored = get_option($name, $missing);
        $assert($saved['exists'] ? $restored === $saved['value'] : $restored === $missing, 'Восстановлена настройка ' . $name);
    }
    $assert(get_theme_mod('imperial_newsletter_enabled', null) === $originalNewsletterMod, 'Восстановлено исходное включение рассылки');
    $assert(! array_filter($postIds, 'get_post') && ! array_filter($commentIds, 'get_comment') && ! array_filter($userIds, 'get_userdata'), 'Тестовые записи, комментарии и пользователь удалены');
    $cronAfter = _get_cron_array();
    foreach ([DigestScheduler::WEEKLY_HOOK, DigestScheduler::BATCH_HOOK] as $hook) {
        $before = [];
        $after = [];
        foreach ($cronBefore as $timestamp => $hooks) {
            if (isset($hooks[$hook])) {
                $before[$timestamp] = $hooks[$hook];
            }
        }
        foreach ($cronAfter as $timestamp => $hooks) {
            if (isset($hooks[$hook])) {
                $after[$timestamp] = $hooks[$hook];
            }
        }
        $assert($before === $after, 'Восстановлены исходные события ' . $hook);
    }
} catch (Throwable $exception) {
    $failure ??= $exception;
}

if ($failure !== null) {
    fwrite(STDERR, 'FAIL: ' . $failure->getMessage() . PHP_EOL . $failure->getTraceAsString() . PHP_EOL);
    exit(1);
}
echo 'PASS: ' . $checks . ' проверок; письма перехвачены, данные восстановлены.' . PHP_EOL;
