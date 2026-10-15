import { t } from './i18n.js';

export const isMotionPaused = () => document.documentElement.classList.contains('is-motion-paused');

export function initMotionControls(reducedMotion) {
  let saved = false;
  try { saved = localStorage.getItem('imperial-motion-paused') === '1'; } catch { /* Настройка остаётся в текущем окне. */ }
  const button = document.querySelector('[data-motion-toggle]');
  const update = (paused) => {
    document.documentElement.classList.toggle('is-motion-paused', paused);
    if (!button) return;
    const label = reducedMotion
      ? t('Анимация отключена в настройках устройства')
      : t(paused ? 'Продолжить анимацию' : 'Приостановить анимацию');
    button.setAttribute('aria-pressed', String(paused));
    button.setAttribute('aria-label', label);
    button.title = label;
    button.disabled = reducedMotion;
  };
  update(reducedMotion || saved);
  button?.addEventListener('click', () => {
    const paused = !isMotionPaused();
    update(paused);
    try { localStorage.setItem('imperial-motion-paused', paused ? '1' : '0'); } catch { /* Настройка остаётся в текущем окне. */ }
    document.dispatchEvent(new CustomEvent('imperial:motion-change'));
  });
}

export function bindSliderMotion(swiper, reducedMotion) {
  if (reducedMotion) return;
  const update = () => {
    if (isMotionPaused()) {
      const position = swiper.getTranslate();
      swiper.autoplay.stop();
      swiper.setTransition(0);
      swiper.setTranslate(position);
    } else {
      swiper.autoplay.start();
    }
  };
  document.addEventListener('imperial:motion-change', update);
  if (isMotionPaused()) update();
}
