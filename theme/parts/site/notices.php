<?php
use ImperialFuture\Support\UserNotice;
if (! defined('ABSPATH')) { exit; }
$notice = UserNotice::current();
?>
<div class="site-notices" data-site-notices role="region" aria-label="<?php esc_attr_e('Уведомления', 'imperial-future'); ?>">
    <?php if ($notice !== null) : ?>
        <div class="site-notice site-notice--<?php echo esc_attr($notice['type']); ?>" role="<?php echo $notice['type'] === 'error' ? 'alert' : 'status'; ?>">
            <p><?php echo esc_html($notice['message']); ?></p>
            <button type="button" data-notice-dismiss aria-label="<?php esc_attr_e('Закрыть уведомление', 'imperial-future'); ?>">×</button>
        </div>
    <?php endif; ?>
</div>
