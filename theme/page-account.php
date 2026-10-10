<?php
declare(strict_types=1);

use ImperialFuture\Auth\SocialAuthHandler;
use ImperialFuture\Bookmark\BookmarkService;
use ImperialFuture\Newsletter\SubscriptionService;
use ImperialFuture\SEO\Breadcrumbs;
use ImperialFuture\Template\AccountViewModel;
use ImperialFuture\Template\TemplateTags;
use ImperialFuture\Support\Icon;

if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>
<header class="archive-header account-hero">
    <div class="container">
        <?php Breadcrumbs::render(); ?>
        <span class="section-kicker"><?php esc_html_e('Кабинет читателя', 'imperial-future'); ?></span>
        <h1 class="archive-header__title"><?php esc_html_e('Личный кабинет', 'imperial-future'); ?></h1>
    </div>
</header>

<section class="account-page container">
    <?php if (! is_user_logged_in()) : ?>
        <div class="account-card account-card--intro">
            <span class="section-kicker"><?php esc_html_e('Доступ закрыт', 'imperial-future'); ?></span>
            <h2><?php esc_html_e('Войдите в архив', 'imperial-future'); ?></h2>
            <p><?php esc_html_e('Кабинет доступен после входа или регистрации. Внутри будут профиль, закладки, подписки и безопасная смена пароля.', 'imperial-future'); ?></p>
            <button class="button" type="button" data-auth-open><?php esc_html_e('Открыть вход', 'imperial-future'); ?></button>
        </div>
    <?php else : ?>
        <?php
        $user = wp_get_current_user();
        $userId = (int) $user->ID;
        $stats = AccountViewModel::stats($user);
        $bookmarks = BookmarkService::posts($userId);
        $recentComments = AccountViewModel::recentComments($user);
        ?>

        <section class="account-dashboard" aria-label="<?php esc_attr_e('Сводка личного кабинета', 'imperial-future'); ?>">
            <div class="account-dashboard__identity">
                <div class="account-dashboard__avatar"><?php echo get_avatar($userId, 128); ?></div>
                <div class="account-dashboard__copy">
                    <span class="section-kicker"><?php esc_html_e('Паспорт архива', 'imperial-future'); ?></span>
                    <h2><?php echo esc_html($user->display_name ?: $user->user_login); ?></h2>
                    <p><?php echo esc_html($user->user_email); ?></p>
                    <small><?php echo esc_html(AccountViewModel::memberSince($user)); ?></small>
                </div>
            </div>

            <div class="account-dashboard__stats">
                <?php foreach ($stats as $stat) : ?>
                    <div class="account-stat">
                        <strong><?php echo esc_html($stat['value']); ?></strong>
                        <span><?php echo esc_html($stat['label']); ?></span>
                        <small><?php echo esc_html($stat['detail']); ?></small>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="account-dashboard__actions">
                <?php if (current_user_can('edit_posts')) : ?>
                    <a class="button button--ghost" href="<?php echo esc_url(admin_url('post-new.php')); ?>"><?php echo Icon::render('edit'); ?> <?php esc_html_e('Новая публикация', 'imperial-future'); ?></a>
                    <a class="account-editor-link" href="<?php echo esc_url(admin_url('edit.php')); ?>"><?php esc_html_e('Мои публикации', 'imperial-future'); ?></a>
                <?php endif; ?>
                <a class="account-logout" href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>"><?php echo Icon::render('logout'); ?> <?php esc_html_e('Выйти', 'imperial-future'); ?></a>
            </div>
        </section>

        <div class="account-workspace" data-account-workspace>
        <nav class="account-nav" aria-label="<?php esc_attr_e('Разделы кабинета', 'imperial-future'); ?>">
            <a href="#account-profile" data-account-view="profile"><?php echo Icon::render('account'); ?><?php esc_html_e('Профиль и подписки', 'imperial-future'); ?></a>
            <a href="#account-security" data-account-view="security"><?php echo Icon::render('security'); ?><?php esc_html_e('Безопасность', 'imperial-future'); ?></a>
            <a href="#account-bookmarks" data-account-view="bookmarks"><?php echo Icon::render('bookmark'); ?><?php esc_html_e('Закладки', 'imperial-future'); ?></a>
            <a href="#account-activity" data-account-view="activity"><?php echo Icon::render('comment'); ?><?php esc_html_e('Комментарии', 'imperial-future'); ?></a>
        </nav>

        <div class="account-layout">
            <form id="account-profile" data-account-section="profile" class="account-card account-card--profile" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                <div class="account-card__head">
                    <span class="section-kicker"><?php esc_html_e('Профиль', 'imperial-future'); ?></span>
                    <h2><?php esc_html_e('Данные читателя', 'imperial-future'); ?></h2>
                    <p><?php esc_html_e('Имя и email используются в комментариях, уведомлениях и дайджесте.', 'imperial-future'); ?></p>
                </div>

                <input type="hidden" name="action" value="imperial_profile_save">
                <?php wp_nonce_field('imperial_profile'); ?>

                <div class="account-field-grid">
                    <label class="account-field">
                        <span><?php esc_html_e('Публичное имя', 'imperial-future'); ?></span>
                        <input type="text" name="display_name" value="<?php echo esc_attr($user->display_name); ?>" autocomplete="name">
                    </label>
                    <label class="account-field">
                        <span><?php esc_html_e('Email', 'imperial-future'); ?></span>
                        <input type="email" name="email" value="<?php echo esc_attr($user->user_email); ?>" autocomplete="email">
                    </label>
                </div>

                <div id="account-subscriptions" class="account-preferences">
                    <div class="account-section-head">
                        <h3><?php esc_html_e('Подписки и уведомления', 'imperial-future'); ?></h3>
                        <p><?php esc_html_e('Выберите только те сигналы, которые действительно полезны.', 'imperial-future'); ?></p>
                    </div>

                    <label class="account-toggle">
                        <?php echo Icon::render('mail'); ?>
                        <span>
                            <strong><?php esc_html_e('Еженедельный дайджест', 'imperial-future'); ?></strong>
                            <small><?php esc_html_e('Лучшие публикации недели одним письмом.', 'imperial-future'); ?></small>
                        </span>
                        <input type="checkbox" name="digest" value="1" role="switch" <?php checked(SubscriptionService::userDigestEnabled($userId)); ?>>
                    </label>
                    <label class="account-toggle">
                        <?php echo Icon::render('comment'); ?>
                        <span>
                            <strong><?php esc_html_e('Новые комментарии к публикациям', 'imperial-future'); ?></strong>
                            <small><?php esc_html_e('Уведомления о новых обсуждениях под материалами.', 'imperial-future'); ?></small>
                        </span>
                        <input type="checkbox" name="comments" value="1" role="switch" <?php checked(SubscriptionService::userCommentsEnabled($userId)); ?>>
                    </label>
                    <label class="account-toggle">
                        <?php echo Icon::render('reply'); ?>
                        <span>
                            <strong><?php esc_html_e('Ответы на мои комментарии', 'imperial-future'); ?></strong>
                            <small><?php esc_html_e('Только прямые ответы, без лишнего шума.', 'imperial-future'); ?></small>
                        </span>
                        <input type="checkbox" name="replies" value="1" role="switch" <?php checked(SubscriptionService::userRepliesEnabled($userId)); ?>>
                    </label>
                </div>

                <div class="account-card__actions">
                    <button type="submit"><?php esc_html_e('Сохранить изменения', 'imperial-future'); ?></button>
                </div>
            </form>

            <form id="account-security" data-account-section="security" class="account-card" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                <div class="account-card__head">
                    <span class="section-kicker"><?php esc_html_e('Безопасность', 'imperial-future'); ?></span>
                    <h2><?php esc_html_e('Сменить пароль', 'imperial-future'); ?></h2>
                    <p><?php esc_html_e('После смены пароля текущий сеанс останется активным.', 'imperial-future'); ?></p>
                </div>

                <input type="hidden" name="action" value="imperial_password_change">
                <?php wp_nonce_field('imperial_profile'); ?>
                <label class="account-field"><span><?php esc_html_e('Текущий пароль', 'imperial-future'); ?></span><input type="password" name="current_password" autocomplete="current-password" required></label>
                <label class="account-field"><span><?php esc_html_e('Новый пароль', 'imperial-future'); ?></span><input type="password" name="new_password" minlength="12" autocomplete="new-password" data-password-input required></label>
                <div class="imperial-password-meter account-password-meter" data-password-meter aria-live="polite">
                    <span></span>
                    <strong><?php esc_html_e('Сила пароля: не указана', 'imperial-future'); ?></strong>
                </div>
                <label class="account-field"><span><?php esc_html_e('Повторите пароль', 'imperial-future'); ?></span><input type="password" name="repeat_password" minlength="12" autocomplete="new-password" required></label>
                <div class="account-card__actions">
                    <button type="submit"><?php esc_html_e('Обновить пароль', 'imperial-future'); ?></button>
                </div>
            </form>

            <form id="account-avatar" data-account-section="profile" class="account-card account-card--avatar" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data">
                <div class="account-card__head">
                    <span class="section-kicker"><?php esc_html_e('Печать', 'imperial-future'); ?></span>
                    <h2><?php esc_html_e('Аватар', 'imperial-future'); ?></h2>
                    <p><?php esc_html_e('Изображение будет обрезано в квадрат и сохранено как личная печать.', 'imperial-future'); ?></p>
                </div>

                <div class="account-avatar-preview"><?php echo get_avatar($userId, 144); ?></div>
                <input type="hidden" name="action" value="imperial_avatar_upload">
                <?php wp_nonce_field('imperial_profile'); ?>
                <label class="account-file">
                    <span><?php esc_html_e('PNG, JPG или WebP', 'imperial-future'); ?></span>
                    <input class="screen-reader-text" type="file" name="avatar" accept="image/png,image/jpeg,image/webp" data-avatar-file>
                    <span class="account-file__trigger"><?php esc_html_e('Выбрать изображение', 'imperial-future'); ?></span>
                    <small data-avatar-file-name aria-live="polite"><?php esc_html_e('Файл не выбран', 'imperial-future'); ?></small>
                </label>
                <div class="account-card__actions">
                    <button type="submit"><?php esc_html_e('Обновить аватар', 'imperial-future'); ?></button>
                </div>
            </form>

            <section id="account-social" data-account-section="security" class="account-card">
                <div class="account-card__head">
                    <span class="section-kicker"><?php esc_html_e('Ключи', 'imperial-future'); ?></span>
                    <h2><?php esc_html_e('Социальный вход', 'imperial-future'); ?></h2>
                    <p><?php esc_html_e('Привяжите быстрый вход, чтобы не вспоминать пароль на каждом устройстве.', 'imperial-future'); ?></p>
                </div>

                <div class="account-social-status">
                    <div class="account-social-status__row">
                        <strong>VK</strong>
                        <?php if ((string) get_user_meta($userId, 'imperial_social_vk_id', true) !== '') : ?>
                            <span><?php esc_html_e('Привязан', 'imperial-future'); ?></span>
                        <?php elseif (SocialAuthHandler::visible('vk')) : ?>
                            <a class="button button--ghost" href="<?php echo esc_url(SocialAuthHandler::buttonUrl('vk')); ?>"><?php esc_html_e('Привязать VK', 'imperial-future'); ?></a>
                        <?php else : ?>
                            <span><?php esc_html_e('Отключён', 'imperial-future'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="account-social-status__row">
                        <strong><?php esc_html_e('Яндекс', 'imperial-future'); ?></strong>
                        <?php if ((string) get_user_meta($userId, 'imperial_social_yandex_id', true) !== '') : ?>
                            <span><?php esc_html_e('Привязан', 'imperial-future'); ?></span>
                        <?php elseif (SocialAuthHandler::visible('yandex')) : ?>
                            <a class="button button--ghost" href="<?php echo esc_url(SocialAuthHandler::buttonUrl('yandex')); ?>"><?php esc_html_e('Привязать Яндекс', 'imperial-future'); ?></a>
                        <?php else : ?>
                            <span><?php esc_html_e('Отключён', 'imperial-future'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section id="account-activity" data-account-section="activity" class="account-card account-card--activity account-card--wide">
                <div class="account-card__head">
                    <span class="section-kicker"><?php esc_html_e('Активность', 'imperial-future'); ?></span>
                    <h2><?php esc_html_e('Мои реплики', 'imperial-future'); ?></h2>
                    <p><?php esc_html_e('Последние одобренные комментарии из архива.', 'imperial-future'); ?></p>
                </div>

                <?php if ($recentComments === []) : ?>
                    <p class="account-empty"><?php esc_html_e('Пока нет опубликованных комментариев. Самое время оставить первую архивную пометку.', 'imperial-future'); ?></p>
                <?php else : ?>
                    <div class="account-activity-list">
                        <?php foreach ($recentComments as $comment) : ?>
                            <a class="account-activity" href="<?php echo esc_url(get_comment_link($comment)); ?>">
                                <small><?php echo esc_html(get_the_title((int) $comment->comment_post_ID)); ?></small>
                                <strong><?php echo esc_html(wp_trim_words(wp_strip_all_tags((string) $comment->comment_content), 18)); ?></strong>
                                <time datetime="<?php echo esc_attr(get_comment_date('c', (int) $comment->comment_ID)); ?>"><?php echo esc_html(get_comment_date('j F Y', (int) $comment->comment_ID)); ?></time>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section id="account-bookmarks" data-account-section="bookmarks" class="account-card account-card--wide">
                <div class="account-card__head account-card__head--row">
                    <div>
                        <span class="section-kicker"><?php esc_html_e('Закладки', 'imperial-future'); ?></span>
                        <h2><?php esc_html_e('Сохранённые публикации', 'imperial-future'); ?></h2>
                    </div>
                    <p><?php esc_html_e('Личный список материалов, к которым удобно вернуться позже.', 'imperial-future'); ?></p>
                </div>

                <?php if ($bookmarks === []) : ?>
                    <p class="account-empty"><?php esc_html_e('Здесь появятся материалы, которые вы отметите звездой под публикацией.', 'imperial-future'); ?></p>
                <?php else : ?>
                    <div class="account-bookmarks">
                        <?php foreach ($bookmarks as $bookmark) : ?>
                            <a class="account-bookmark" href="<?php echo esc_url(get_permalink($bookmark)); ?>">
                                <img src="<?php echo esc_url(TemplateTags::featuredImageUrl((int) $bookmark->ID, 'imperial-card')); ?>" alt="" loading="lazy" decoding="async">
                                <span>
                                    <em><?php echo esc_html(TemplateTags::primaryCategory((int) $bookmark->ID)); ?></em>
                                    <strong><?php echo esc_html(get_the_title($bookmark)); ?></strong>
                                    <small><?php echo esc_html(wp_trim_words(get_the_excerpt($bookmark), 18)); ?></small>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
        </div>
    <?php endif; ?>
</section>
<?php
get_footer();
