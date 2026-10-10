<?php
declare(strict_types=1);

use ImperialFuture\Rating\RatingService;
use ImperialFuture\Settings\ThemeOptions;
use ImperialFuture\Support\RussianPluralizer;

if (! defined('ABSPATH') || ! ThemeOptions::enabled('imperial_rating_enabled')) {
    return;
}

$postId = get_the_ID();
$mode = RatingService::mode();
$options = RatingService::options();
$counts = RatingService::counts($postId);
$total = RatingService::total($counts);
$average = RatingService::average($counts);
$voted = RatingService::votedValue($postId);
$voters = RatingService::voters($postId);
$isLoggedIn = is_user_logged_in();
$fallbackValue = (string) array_key_first($options);
$primaryValue = $voted !== '' ? $voted : $fallbackValue;
$primaryLabel = (string) ($options[$primaryValue] ?? '♥');
$scaleWidth = $mode === 'stars' ? min(100, max(0, ($average / 5) * 100)) : 0;
?>
<section
    class="imperial-rating imperial-rating--<?php echo esc_attr($mode); ?> <?php echo $isLoggedIn ? '' : 'imperial-rating--locked'; ?>"
    data-rating-panel
    data-post-id="<?php echo esc_attr((string) $postId); ?>"
    data-rating-mode="<?php echo esc_attr($mode); ?>"
    data-rating-locked="<?php echo esc_attr($isLoggedIn ? '0' : '1'); ?>"
>
    <?php if ($mode === 'stars') : ?>
        <div class="imperial-rating__head">
            <span><?php esc_html_e('Оценка публикации', 'imperial-future'); ?></span>
            <strong data-rating-summary><?php echo esc_html($average > 0 ? sprintf(__('%s из 5', 'imperial-future'), number_format_i18n($average, 1)) : RussianPluralizer::votes($total)); ?></strong>
        </div>

        <div class="imperial-rating-scale" role="group" aria-label="<?php esc_attr_e('Поставить оценку', 'imperial-future'); ?>">
            <span class="imperial-rating-scale__rail" aria-hidden="true">
                <span data-rating-scale-fill style="width: <?php echo esc_attr((string) $scaleWidth); ?>%;"></span>
            </span>

            <?php foreach ($options as $value => $label) : ?>
                <?php $value = (string) $value; ?>
                <button
                    class="imperial-rating-scale__step <?php echo $voted === $value ? 'is-selected' : ''; ?>"
                    type="button"
                    data-rating-value="<?php echo esc_attr($value); ?>"
                    aria-label="<?php echo esc_attr(sprintf(__('Оценить на %s из 5', 'imperial-future'), $value)); ?>"
                    aria-pressed="<?php echo esc_attr($voted === $value ? 'true' : 'false'); ?>"
                >
                    <span aria-hidden="true"><?php echo esc_html((string) $label); ?></span>
                    <em data-rating-count="<?php echo esc_attr($value); ?>"><?php echo esc_html(number_format_i18n((int) ($counts[$value] ?? 0))); ?></em>
                </button>
            <?php endforeach; ?>
        </div>
    <?php elseif ($mode === 'likes') : ?>
        <div class="imperial-rating-likes" role="group" aria-label="<?php esc_attr_e('Оценить публикацию', 'imperial-future'); ?>">
            <?php foreach ($options as $value => $label) : ?>
                <?php $value = (string) $value; ?>
                <button
                    class="imperial-rating-thumb imperial-rating-thumb--<?php echo esc_attr($value); ?> <?php echo $voted === $value ? 'is-selected' : ''; ?>"
                    type="button"
                    data-rating-value="<?php echo esc_attr($value); ?>"
                    aria-label="<?php echo esc_attr($value === 'up' ? __('Нравится', 'imperial-future') : __('Не нравится', 'imperial-future')); ?>"
                    aria-pressed="<?php echo esc_attr($voted === $value ? 'true' : 'false'); ?>"
                >
                    <span aria-hidden="true"><?php echo esc_html((string) $label); ?></span>
                    <em data-rating-count="<?php echo esc_attr($value); ?>"><?php echo esc_html(number_format_i18n((int) ($counts[$value] ?? 0))); ?></em>
                </button>
            <?php endforeach; ?>
            <strong class="imperial-rating-likes__summary" data-rating-summary><?php echo esc_html(RussianPluralizer::votes($total)); ?></strong>
        </div>
    <?php else : ?>
        <div class="imperial-rating__reactor">
            <button
                class="imperial-rating-heart <?php echo $voted !== '' ? 'is-selected' : ''; ?>"
                type="button"
                data-rating-value="<?php echo esc_attr($primaryValue); ?>"
                data-rating-primary
                aria-label="<?php esc_attr_e('Поставить реакцию', 'imperial-future'); ?>"
                aria-pressed="<?php echo esc_attr($voted !== '' ? 'true' : 'false'); ?>"
            >
                <span aria-hidden="true" data-rating-primary-label><?php echo esc_html($primaryLabel); ?></span>
                <strong data-rating-total><?php echo esc_html(number_format_i18n($total)); ?></strong>
            </button>

            <?php if (count($options) > 1) : ?>
                <div class="imperial-rating-popover" data-rating-menu>
                    <?php foreach ($options as $value => $label) : ?>
                        <?php $value = (string) $value; ?>
                        <button
                            class="imperial-rating-emoji <?php echo $voted === $value && $primaryValue !== $value ? 'is-selected' : ''; ?> <?php echo $primaryValue === $value ? 'is-primary-option' : ''; ?>"
                            type="button"
                            data-rating-value="<?php echo esc_attr($value); ?>"
                            aria-label="<?php echo esc_attr((string) $label); ?>"
                            aria-pressed="<?php echo esc_attr($voted === $value ? 'true' : 'false'); ?>"
                            <?php echo $primaryValue === $value ? 'hidden' : ''; ?>
                        >
                            <span aria-hidden="true"><?php echo esc_html((string) $label); ?></span>
                            <em data-rating-count="<?php echo esc_attr($value); ?>"><?php echo esc_html(number_format_i18n((int) ($counts[$value] ?? 0))); ?></em>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($voters !== []) : ?>
        <div class="imperial-rating-voters" data-rating-voters aria-label="<?php esc_attr_e('Кто оценил', 'imperial-future'); ?>">
            <?php foreach ($voters as $voter) : ?>
                <a
                    class="imperial-rating-voter"
                    href="<?php echo esc_url((string) $voter['url']); ?>"
                    title="<?php echo esc_attr((string) $voter['name']); ?>"
                    data-voter-name="<?php echo esc_attr((string) $voter['name']); ?>"
                    aria-label="<?php echo esc_attr(sprintf(__('Профиль: %s', 'imperial-future'), (string) $voter['name'])); ?>"
                >
                    <img src="<?php echo esc_url((string) $voter['avatar']); ?>" alt="" loading="lazy" width="28" height="28">
                    <strong aria-hidden="true"><?php echo esc_html((string) $voter['reaction']); ?></strong>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <span class="imperial-rating__message" data-rating-message>
        <?php echo esc_html($isLoggedIn ? '' : __('Войдите, чтобы оценить', 'imperial-future')); ?>
    </span>
</section>
