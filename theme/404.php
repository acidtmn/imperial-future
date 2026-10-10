<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>
<section class="not-found">
    <div class="container">
        <span class="section-kicker"><?php esc_html_e('Ошибка 404', 'imperial-future'); ?></span>
        <h1><?php esc_html_e('Эта папка архива исчезла в электрическом тумане', 'imperial-future'); ?></h1>
        <p class="section-lead"><?php esc_html_e('Можно вернуться на главную или отправить новый поисковый запрос.', 'imperial-future'); ?></p>
        <div class="hero__actions">
            <a class="button" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('На главную', 'imperial-future'); ?></a>
            <a class="button button--ghost" href="<?php echo esc_url(home_url('/?s=')); ?>"><?php esc_html_e('Искать в архиве', 'imperial-future'); ?></a>
        </div>
    </div>
</section>
<?php
get_footer();
