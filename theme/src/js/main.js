import { t } from './i18n.js';
import { iconMarkup } from './icons.js';
import { initAccount } from './account.js';
import '../scss/main.scss';
import './fonts.js';
import { initNotices, showNotice } from './notices.js';
import 'swiper/css';
import 'swiper/css/effect-fade';

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const select = (selector, scope = document) => scope.querySelector(selector);
const selectAll = (selector, scope = document) => [...scope.querySelectorAll(selector)];
const decodeHash = (hash) => {
  if (!hash || hash === '#') {
    return '';
  }

  const value = String(hash).replace(/^#/, '');

  try {
    return decodeURIComponent(value);
  } catch (error) {
    return value;
  }
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', boot);
} else {
  boot();
}

function boot() {
  initThemeToggle();
  initPreloader();
  initReadingProgress();
  initRevealAnimations();
  initParallax();
  initTelegraph();
  initTableOfContents();
  initDustCanvas();
  initDragCaptcha();
  initModals();
  initAuthTabs();
  initNotices();
  initAccount();
  initLiveSearch();
  initRatings();
  initCommentVotes();
  initMarkdownEditors();
  initPasswordStrength();
  initShareMenus();
  initShareCopy();
  initCardShare();
  initBookmarks();
  initContentLightbox();
  initCodeBlocks();
  initExternalExit();
  initOutsideDismiss();
}

function initThemeToggle() {
  const button = select('[data-theme-toggle]');
  let storedTheme;
  try {
    storedTheme = localStorage.getItem('imperial-theme');
  } catch {
    storedTheme = null;
  }
  const systemTheme = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
  const initialTheme = ['light', 'dark'].includes(storedTheme) ? storedTheme : systemTheme;

  document.documentElement.dataset.theme = initialTheme;

  if (!button) {
    return;
  }

  button.setAttribute('aria-pressed', String(initialTheme === 'light'));

  button.addEventListener('click', () => {
    const nextTheme = document.documentElement.dataset.theme === 'light' ? 'dark' : 'light';

    document.documentElement.dataset.theme = nextTheme;
    try {
      localStorage.setItem('imperial-theme', nextTheme);
    } catch {
      // В приватном режиме настройка действует до закрытия страницы.
    }
    button.setAttribute('aria-pressed', String(nextTheme === 'light'));
  });
}

function initPreloader() {
  const preloader = select('[data-preloader]');

  if (!preloader) {
    return;
  }

  const hide = () => {
    preloader.classList.add('is-leaving');
    window.setTimeout(() => preloader.classList.add('is-hidden'), prefersReducedMotion ? 0 : 460);
  };

  hide();
}

function initReadingProgress() {
  const progress = select('[data-reading-progress]');

  if (!progress) {
    return;
  }

  const update = () => {
    const documentHeight = document.documentElement.scrollHeight - window.innerHeight;
    const ratio = documentHeight > 0 ? window.scrollY / documentHeight : 0;

    progress.style.transform = `scaleX(${Math.min(1, Math.max(0, ratio))})`;
  };

  update();
  window.addEventListener('scroll', update, { passive: true });
  window.addEventListener('resize', update);
}

function initRevealAnimations() {
  const items = selectAll('[data-reveal]');

  if (!items.length) {
    return;
  }

  document.documentElement.classList.add('reveal-pending');

  if (prefersReducedMotion || !('IntersectionObserver' in window)) {
    items.forEach((item) => item.classList.add('is-revealed'));
    return;
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) {
        return;
      }

      entry.target.classList.add('is-revealed');
      observer.unobserve(entry.target);
    });
  }, { rootMargin: '0px 0px -12% 0px' });

  items.forEach((item) => observer.observe(item));
}

function initParallax() {
  const items = selectAll('[data-parallax]');

  if (!items.length || prefersReducedMotion) {
    return;
  }

  runWhenVisible(items[0], async () => {
    const [{ default: gsap }, { ScrollTrigger }] = await Promise.all([
      import('gsap'),
      import('gsap/ScrollTrigger')
    ]);

    gsap.registerPlugin(ScrollTrigger);

    items.forEach((item) => {
      gsap.to(item, {
        yPercent: Number(item.dataset.parallax || -8),
        ease: 'none',
        scrollTrigger: {
          trigger: item.parentElement || item,
          start: 'top bottom',
          end: 'bottom top',
          scrub: true
        }
      });
    });
  });
}

function initTelegraph() {
  const slider = select('.telegraph-swiper');
  const heroSlider = select('.hero-swiper');

  if (!slider && !heroSlider) {
    return;
  }

  const initialize = async (target) => {
    const [{ Swiper }, { Autoplay, FreeMode, Pagination, EffectFade, A11y }] = await Promise.all([
      import('swiper'),
      import('swiper/modules')
    ]);

    if (target === slider) {
      new Swiper(slider, {
        modules: [Autoplay, FreeMode],
        slidesPerView: 'auto',
        spaceBetween: 18,
        speed: 9000,
        loop: true,
        freeMode: {
          enabled: true,
          momentum: false
        },
        autoplay: prefersReducedMotion
          ? false
          : {
              delay: 0,
              disableOnInteraction: false
            }
      });
    }

    if (target === heroSlider) {
      const loadSlideImage = (swiper) => {
        const image = swiper.slides[swiper.activeIndex]?.querySelector('[data-deferred-image]');
        if (!image) return;
        image.sizes = image.sizes.replace(/^auto,\s*/, '');
        image.loading = 'eager';
        if (image.dataset.deferredSrcset) image.srcset = image.dataset.deferredSrcset;
        image.src = image.dataset.deferredSrc;
        image.removeAttribute('data-deferred-image');
      };
      new Swiper(heroSlider, {
        modules: [Autoplay, Pagination, EffectFade, A11y],
        a11y: {
          containerMessage: t('Избранные публикации'),
          paginationBulletMessage: t('Перейти к слайду {{index}}'),
        },
        slidesPerView: 1,
        speed: 850,
        loop: true,
        effect: 'fade',
        on: { init: loadSlideImage, slideChange: loadSlideImage },
        pagination: {
          el: select('[data-hero-pagination]', heroSlider),
          clickable: true
        },
        autoplay: prefersReducedMotion
          ? false
          : {
              delay: 5200,
              disableOnInteraction: false
          }
      });
    }
  };

  if (slider) runWhenVisible(slider, () => initialize(slider));
  if (heroSlider) runWhenVisible(heroSlider, () => initialize(heroSlider));
}

function initTableOfContents() {
  const links = selectAll('[data-toc-link]');

  if (!links.length) {
    return;
  }

  links.forEach((link) => {
    link.addEventListener('click', (event) => {
      const target = hashTarget(link.getAttribute('href'));

      if (!target) {
        return;
      }

      event.preventDefault();
      target.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth', block: 'start' });
      history.replaceState(null, '', link.getAttribute('href'));
    });
  });

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) {
          return;
        }

        links.forEach((link) => {
          link.classList.toggle('is-active', decodeHash(link.hash) === entry.target.id);
        });
      });
    },
    { rootMargin: '-20% 0px -68% 0px' }
  );

  links.forEach((link) => {
    const target = hashTarget(link.hash);

    if (target) {
      observer.observe(target);
    }
  });
}

function hashTarget(hash) {
  const id = decodeHash(hash);

  if (!id) {
    return null;
  }

  // Кириллические ID в браузере часто приходят percent-encoded, а querySelector
  // трактует их как CSS-селектор. Ищем узел напрямую по ID, чтобы не ломать JS.
  return document.getElementById(id);
}

function initDustCanvas() {
  const canvas = select('[data-dust-canvas]');

  if (!canvas || prefersReducedMotion) {
    return;
  }

  const context = canvas.getContext('2d');
  const animated = window.matchMedia('(hover: hover)').matches;
  const particles = Array.from({ length: animated ? 28 : 16 }, () => createParticle());
  let width = 0;
  let height = 0;
  let lastFrame = 0;

  const resize = () => {
    const ratio = Math.min(window.devicePixelRatio || 1, animated ? 1.5 : 1);

    width = canvas.offsetWidth;
    height = canvas.offsetHeight;
    canvas.width = Math.floor(width * ratio);
    canvas.height = Math.floor(height * ratio);
    context.setTransform(ratio, 0, 0, ratio, 0, 0);
  };

  const render = (time = 0) => {
    if (animated) requestAnimationFrame(render);
    if (document.hidden || (animated && time - lastFrame < 66)) return;
    lastFrame = time;
    context.clearRect(0, 0, width, height);
    context.fillStyle = 'rgba(244, 231, 197, .34)';

    particles.forEach((particle) => {
      particle.x += particle.speedX;
      particle.y += particle.speedY;

      if (particle.x > width + 8) particle.x = -8;
      if (particle.y > height + 8) particle.y = -8;

      context.globalAlpha = particle.alpha;
      context.beginPath();
      context.arc(particle.x, particle.y, particle.radius, 0, Math.PI * 2);
      context.fill();
    });

  };

  resize();
  render();
  window.addEventListener('resize', () => { resize(); if (!animated) render(); });
}

function createParticle() {
  return {
    x: Math.random() * window.innerWidth,
    y: Math.random() * Math.max(window.innerHeight, 760),
    radius: Math.random() * 1.8 + 0.45,
    alpha: Math.random() * 0.28 + 0.08,
    speedX: Math.random() * 0.18 + 0.03,
    speedY: Math.random() * 0.12 + 0.02
  };
}

function initDragCaptcha() {
  selectAll('[data-drag-captcha]').forEach((captcha) => {
    const handle = select('[data-drag-handle]', captcha);
    const input = captcha.parentElement?.querySelector('[data-captcha-value]');
    const track = captcha.querySelector('.imperial-drag-captcha__track');
    const status = select('[data-captcha-status]', captcha);

    if (!handle || !input || !track) {
      return;
    }

    let active = false;

    const move = (clientX) => {
      const rect = track.getBoundingClientRect();
      const width = track.clientWidth;
      if (!width) return;
      const half = handle.offsetWidth / 2;
      const x = Math.min(width - half, Math.max(half, clientX - rect.left - track.clientLeft));
      const percent = x / width * 100;

      handle.style.left = `${percent}%`;
      input.value = percent.toFixed(1);
      handle.setAttribute('aria-valuenow', percent.toFixed(1));

      if (status) {
        status.textContent = t('Положение выбрано. Печать проверится при отправке.');
      }
    };

    track.addEventListener('click', (event) => {
      if (handle.contains(event.target) || event.detail === 0) {
        return;
      }

      move(event.clientX);
    });

    handle.addEventListener('pointerdown', (event) => {
      active = true;
      handle.setPointerCapture(event.pointerId);
      move(event.clientX);
    });

    handle.addEventListener('pointermove', (event) => {
      if (!active) {
        return;
      }

      move(event.clientX);
    });

    handle.addEventListener('pointerup', () => {
      active = false;
    });
    handle.addEventListener('pointercancel', () => { active = false; });
    handle.addEventListener('lostpointercapture', () => { active = false; });
    handle.addEventListener('keydown', (event) => {
      if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
      event.preventDefault();
      const value = event.key === 'Home' ? 0 : event.key === 'End' ? 100 : Number(handle.getAttribute('aria-valuenow')) + (event.key === 'ArrowRight' ? 2 : -2);
      const rect = track.getBoundingClientRect();
      move(rect.left + track.clientLeft + track.clientWidth * Math.max(0, Math.min(100, value)) / 100);
    });
  });
}

function initModals() {
  bindModal('[data-auth-modal]', '[data-auth-open]', '[data-auth-close]');
  bindModal('[data-search-modal]', '[data-search-open]', '[data-search-close]', () => {
    const input = select('[data-live-search-input]');

    if (input) {
      window.setTimeout(() => input.focus(), 40);
    }
  });
}

function bindModal(modalSelector, openSelector, closeSelector, onOpen) {
  const modal = select(modalSelector);
  const openers = selectAll(openSelector);

  if (!modal) {
    return;
  }

  let previousFocus = null;
  const open = () => {
    previousFocus = document.activeElement;
    modal.hidden = false;
    document.body.classList.add('modal-open');
    modal.querySelector('input:not([type="hidden"]), button')?.focus();

    if (typeof onOpen === 'function') {
      onOpen();
    }
  };

  const close = () => {
    modal.hidden = true;
    document.body.classList.remove('modal-open');
    previousFocus?.focus();
  };

  openers.forEach((button) => button.addEventListener('click', open));
  if (modalSelector === '[data-auth-modal]') document.addEventListener('imperial:auth-open', open);
  selectAll(closeSelector, modal).forEach((button) => button.addEventListener('click', close));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !modal.hidden) {
      close();
    }
    if (event.key === 'Tab' && !modal.hidden) {
      const controls = selectAll('a[href], button, input:not([type="hidden"]), textarea, select, [tabindex="0"]', modal)
        .filter((element) => !element.disabled && element.getClientRects().length);
      const first = controls[0];
      const last = controls.at(-1);
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
    }
  });
}

function initAuthTabs() {
  const modal = select('[data-auth-modal]');

  if (!modal) {
    return;
  }

  const tabs = selectAll('[data-auth-tab]', modal);
  const panels = selectAll('[data-auth-panel]', modal);

  const activate = (name) => {
    tabs.forEach((tab) => tab.classList.toggle('is-active', tab.dataset.authTab === name));
    panels.forEach((panel) => panel.classList.toggle('is-active', panel.dataset.authPanel === name));
  };

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => activate(tab.dataset.authTab));
  });

  activate('login');
  const status = new URL(window.location.href).searchParams.get('imperial_auth_status');
  if (['login_error', 'register_error', 'login_required', 'security_error', 'too_many_requests', 'social_error'].includes(status)) {
    openAuthModal();
    if (status === 'register_error' && tabs.some((tab) => tab.dataset.authTab === 'register')) activate('register');
  }
}

function initLiveSearch() {
  const input = select('[data-live-search-input]');
  const results = select('[data-live-search-results]');
  const status = select('[data-live-search-status]');
  const formElement = select('[data-live-search-form]');

  if (!input || !results || !status || !window.imperialFuture?.ajaxUrl) {
    return;
  }

  let timer = 0;
  let controller = null;

  const search = async () => {
    const term = input.value.trim();

    if (term.length < 3) {
      results.innerHTML = '';
      status.textContent = '';
      return;
    }

    if (controller) {
      controller.abort();
    }

    controller = new AbortController();
    status.textContent = window.imperialFuture.i18n?.searchLoading || t('Поиск...');

    const form = new FormData();

    form.set('action', 'imperial_live_search');
    form.set('nonce', window.imperialFuture.searchNonce || '');
    form.set('term', term);

    try {
      const response = await fetch(window.imperialFuture.ajaxUrl, {
        method: 'POST',
        body: form,
        signal: controller.signal
      });
      const payload = await response.json();
      const items = payload?.data?.items || [];

      renderSearchResults(items, results);
      status.textContent = items.length ? '' : (window.imperialFuture.i18n?.searchEmpty || '');
    } catch (error) {
      if (error.name !== 'AbortError') {
        status.textContent = window.imperialFuture.i18n?.searchEmpty || '';
      }
    }
  };

  if (formElement) {
    formElement.addEventListener('submit', (event) => {
      if (input.value.trim().length >= 3 && results.children.length > 0) {
        event.preventDefault();
        results.querySelector('a')?.click();
      }
    });
  }

  input.addEventListener('input', () => {
    window.clearTimeout(timer);
    timer = window.setTimeout(search, 220);
  });

  selectAll('[data-search-example]').forEach((button) => {
    button.addEventListener('click', () => {
      input.value = button.dataset.searchExample || '';
      search();
    });
  });
}

function renderSearchResults(items, container) {
  container.innerHTML = items.map((item) => `
    <a class="imperial-search-result" href="${escapeAttribute(item.url)}">
      <img src="${escapeAttribute(item.image)}" alt="" loading="lazy" decoding="async">
      <div>
        <span>${escapeHtml(item.category)} · ${escapeHtml(item.date)}</span>
        <h3>${escapeHtml(item.title)}</h3>
        <p>${escapeHtml(item.excerpt)}</p>
      </div>
    </a>
  `).join('');
}

function initRatings() {
  selectAll('[data-rating-panel]').forEach((panel) => {
    const postId = panel.dataset.postId;
    const locked = panel.dataset.ratingLocked === '1';
    const reactor = select('.imperial-rating__reactor', panel);
    const openMenu = () => panel.classList.add('is-rating-menu-open');

    if (reactor) {
      reactor.addEventListener('pointerenter', openMenu);
      reactor.addEventListener('focusin', openMenu);
    }

    if (locked) {
      selectAll('[data-rating-value]', panel).forEach((button) => {
        button.addEventListener('click', () => openAuthModal());
      });
      return;
    }

    const sendVote = async (value, control) => {
      const form = new FormData();

      form.set('action', 'imperial_rate_post');
      form.set('nonce', window.imperialFuture?.ratingNonce || '');
      form.set('post_id', postId);
      form.set('value', value || '');

      if (control) {
        control.disabled = true;
      }

      try {
        const response = await fetch(window.imperialFuture.ajaxUrl, {
          method: 'POST',
          body: form
        });
        const payload = await response.json();

        if (payload?.success) {
          updateRatingPanel(panel, payload.data);
          panel.classList.remove('is-rating-menu-open');
          return;
        }

        if (payload?.data?.loginRequired) {
          openAuthModal();
        }

        const message = select('[data-rating-message]', panel);

        if (message && payload?.data?.message) {
          message.textContent = payload.data.message;
        }
      } catch (error) {
        showNotice(window.imperialFuture?.i18n?.networkError || t('Не удалось связаться с сервером. Повтори попытку.'));
      } finally {
        if (control) {
          control.disabled = false;
        }
      }
    };

    selectAll('[data-rating-value]', panel).forEach((button) => {
      button.addEventListener('click', () => {
        sendVote(button.dataset.ratingValue || '', button);
      });
    });
  });
}

function openAuthModal() {
  document.dispatchEvent(new CustomEvent('imperial:auth-open'));
}

function initPasswordStrength() {
  selectAll('[data-password-input]').forEach((input) => {
    const meter = input.closest('form')?.querySelector('[data-password-meter]');

    if (!meter) {
      return;
    }

    const bar = select('span', meter);
    const label = select('strong', meter);

    const update = () => {
      const score = passwordScore(input.value);
      const labels = [t('не указана'), t('слабая'), t('надёжная'), t('крепкая'), t('императорская')];

      meter.dataset.strength = String(score);

      if (bar) {
        bar.style.transform = `scaleX(${score / 4})`;
      }

      if (label) {
        label.textContent = t('Сила пароля: %s').replace('%s', labels[score]);
      }
    };

    input.addEventListener('input', update);
    update();
  });
}

function passwordScore(value) {
  if (!value) {
    return 0;
  }

  let score = value.length >= 8 ? 1 : 0;

  if (value.length >= 12) score += 1;
  if (/[A-ZА-ЯЁ]/.test(value) && /[a-zа-яё]/.test(value)) score += 1;
  if (/\d/.test(value) && /[^A-Za-zА-Яа-яЁё0-9]/.test(value)) score += 1;

  return Math.min(4, score);
}

function updateRatingPanel(panel, data) {
  const voted = String(data.voted || '');
  const primary = select('[data-rating-primary]', panel);

  if (primary && voted) {
    primary.dataset.ratingValue = voted;

    const primaryLabel = select('[data-rating-primary-label]', primary);

    if (primaryLabel) {
      primaryLabel.textContent = data.votedLabel || voted;
    }
  }

  Object.entries(data.counts || {}).forEach(([value, count]) => {
    const counter = select(`[data-rating-count="${cssEscape(value)}"]`, panel);

    if (counter) {
      counter.textContent = String(count);
    }
  });

  selectAll('.imperial-rating-emoji', panel).forEach((button) => {
    const isPrimary = Boolean(primary?.dataset.ratingValue) && button.dataset.ratingValue === primary.dataset.ratingValue;

    button.hidden = isPrimary;
    button.classList.toggle('is-primary-option', isPrimary);
  });

  selectAll('[data-rating-value]', panel).forEach((button) => {
    const selected = button.dataset.ratingValue === voted && !button.hidden;

    button.classList.toggle('is-selected', selected);
    button.setAttribute('aria-pressed', String(selected));
  });

  const summary = select('[data-rating-summary]', panel);
  const total = select('[data-rating-total]', panel);
  const message = select('[data-rating-message]', panel);
  const scaleFill = select('[data-rating-scale-fill]', panel);

  if (total) {
    total.textContent = String(data.total || 0);
  }

  if (scaleFill) {
    const average = Number(data.average) || 0;

    scaleFill.style.width = `${Math.min(100, Math.max(0, (average / 5) * 100))}%`;
  }

  if (summary) {
    summary.textContent = panel.dataset.ratingMode === 'stars' && data.average > 0
      ? t('%s из 5').replace('%s', data.average)
      : formatVotes(data.total);
  }

  if (message) {
    message.textContent = window.imperialFuture?.i18n?.ratingSaved || '';
  }

  if (Array.isArray(data.voters)) {
    renderRatingVoters(panel, data.voters);
  }
}

function renderRatingVoters(panel, voters) {
  let container = select('[data-rating-voters]', panel);

  if (!voters.length) {
    container?.remove();
    return;
  }

  if (!container) {
    container = document.createElement('div');
    container.className = 'imperial-rating-voters';
    container.dataset.ratingVoters = '';
    container.setAttribute('aria-label', t('Кто оценил'));

    const message = select('[data-rating-message]', panel);

    panel.insertBefore(container, message || null);
  }

  container.innerHTML = voters.map((voter) => `
    <a class="imperial-rating-voter" href="${escapeAttribute(voter.url)}" title="${escapeAttribute(voter.name)}" data-voter-name="${escapeAttribute(voter.name)}" aria-label="${escapeAttribute(t('Профиль: %s').replace('%s', voter.name))}">
      <img src="${escapeAttribute(voter.avatar)}" alt="" loading="lazy" width="28" height="28">
      <strong aria-hidden="true">${escapeHtml(voter.reaction)}</strong>
    </a>
  `).join('');
}

function formatVotes(total) {
  const value = Math.abs(Number(total) || 0);
  const mod10 = value % 10;
  const mod100 = value % 100;
  let word = t('голосов');

  if (mod10 === 1 && mod100 !== 11) {
    word = t('голос');
  } else if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) {
    word = t('голоса');
  }

  return `${value} ${word}`;
}

function initCommentVotes() {
  selectAll('[data-comment-vote-panel]').forEach((panel) => {
    const commentId = panel.dataset.commentId || '';
    const locked = panel.dataset.commentVoteLocked === '1';

    selectAll('[data-comment-vote]', panel).forEach((button) => {
      button.addEventListener('click', async () => {
        if (locked) {
          openAuthModal();
          return;
        }

        const form = new FormData();

        form.set('action', 'imperial_vote_comment');
        form.set('nonce', window.imperialFuture?.commentVoteNonce || '');
        form.set('comment_id', commentId);
        form.set('value', button.dataset.commentVote || '');
        button.disabled = true;

        try {
          const response = await fetch(window.imperialFuture.ajaxUrl, {
            method: 'POST',
            body: form
          });
          const payload = await response.json();

          if (payload?.data?.loginRequired) {
            openAuthModal();
            return;
          }

          if (payload?.success) {
            updateCommentVotePanel(panel, payload.data);
          } else {
            const message = select('[data-comment-vote-message]', panel);

            if (message && payload?.data?.message) {
              message.textContent = payload.data.message;
            }
          }
        } catch (error) {
          showNotice(window.imperialFuture?.i18n?.networkError || t('Не удалось связаться с сервером. Повтори попытку.'));
        } finally {
          button.disabled = false;
        }
      });
    });
  });
}

function updateCommentVotePanel(panel, data) {
  Object.entries(data.counts || {}).forEach(([value, count]) => {
    const counter = select(`[data-comment-vote-count="${cssEscape(value)}"]`, panel);

    if (counter) {
      counter.textContent = String(count);
    }
  });

  const message = select('[data-comment-vote-message]', panel);
  const voted = String(data.voted || '');

  selectAll('[data-comment-vote]', panel).forEach((button) => {
    const selected = button.dataset.commentVote === voted;

    button.classList.toggle('is-selected', selected);
    button.setAttribute('aria-pressed', String(selected));
  });

  if (message) {
    message.textContent = window.imperialFuture?.i18n?.commentVoteSaved || '';
  }
}

function initMarkdownEditors() {
  const editors = selectAll('[data-md-editor]');

  if (!editors.length) {
    return;
  }

  runWhenVisible(editors[0], () => loadMarkdownEditors(editors));
}

async function loadMarkdownEditors(editors) {
  try {
    const [{ default: EasyMDE }] = await Promise.all([
      import('easymde'),
      import('easymde/dist/easymde.min.css')
    ]);

    editors.forEach((editor) => {
      const textarea = select('[data-md-textarea]', editor);

      if (!textarea || textarea.dataset.easyMdeReady === '1') {
        return;
      }

      textarea.dataset.easyMdeReady = '1';

      const instance = new EasyMDE({
        element: textarea,
        autoDownloadFontAwesome: false,
        forceSync: true,
        maxHeight: '420px',
        minHeight: '190px',
        nativeSpellcheck: true,
        placeholder: t('Напишите комментарий. Markdown поддерживается.'),
        previewRender: (content, preview) => {
          const requestId = String(Date.now());
          preview.dataset.requestId = requestId;
          const body = new FormData();
          body.set('action', 'imperial_markdown_preview');
          body.set('nonce', window.imperialFuture.commentPreviewNonce);
          body.set('content', content);
          fetch(window.imperialFuture.ajaxUrl, { method: 'POST', body })
            .then((response) => response.json())
            .then((payload) => {
              if (preview.dataset.requestId !== requestId) return;
              if (payload.success) {
                preview.innerHTML = payload.data.html;
              } else {
                preview.textContent = content;
              }
            })
            .catch(() => { preview.textContent = content; });
          return t('Подготовка предпросмотра...');
        },
        renderingConfig: {
          singleLineBreaks: false
        },
        spellChecker: false,
        status: false,
        toolbar: [
          { name: 'bold', action: EasyMDE.toggleBold, text: '', title: t('Жирный текст'), className: 'imperial-mde-action imperial-mde-action--bold' },
          { name: 'italic', action: EasyMDE.toggleItalic, text: '', title: t('Курсив'), className: 'imperial-mde-action imperial-mde-action--italic' },
          '|',
          { name: 'quote', action: EasyMDE.toggleBlockquote, text: '', title: t('Цитата'), className: 'imperial-mde-action imperial-mde-action--quote' },
          { name: 'code', action: EasyMDE.toggleCodeBlock, text: '', title: t('Блок кода'), className: 'imperial-mde-action imperial-mde-action--code' },
          '|',
          { name: 'unordered-list', action: EasyMDE.toggleUnorderedList, text: '', title: t('Маркированный список'), className: 'imperial-mde-action imperial-mde-action--list' },
          { name: 'ordered-list', action: EasyMDE.toggleOrderedList, text: '', title: t('Нумерованный список'), className: 'imperial-mde-action imperial-mde-action--ordered' },
          '|',
          { name: 'link', action: EasyMDE.drawLink, text: '', title: t('Ссылка'), className: 'imperial-mde-action imperial-mde-action--link' },
          { name: 'preview', action: EasyMDE.togglePreview, text: '', title: t('Предпросмотр'), className: 'imperial-mde-action imperial-mde-action--preview', noDisable: true }
        ],
        toolbarTips: true
      });

      Object.entries({ bold: 'bold', italic: 'italic', quote: 'quote', code: 'code', 'unordered-list': 'list', 'ordered-list': 'list-ordered', link: 'link', preview: 'eye' }).forEach(([name, icon]) => {
        instance.toolbarElements[name].innerHTML = iconMarkup(icon);
      });

      const form = textarea.closest('form');

      if (form) {
        form.addEventListener('submit', () => instance.codemirror.save());
      }
    });
  } catch (error) {
    console.warn(t('EasyMDE не загрузился, оставлен обычный textarea.'), error);
  }
}

function initShareCopy() {
  selectAll('[data-share-copy]').forEach((button) => {
    button.addEventListener('click', async () => {
      const url = button.dataset.shareCopy || window.location.href;
      const label = select('.social-link__label', button);
      const initialText = label?.textContent || '';

      try {
        await navigator.clipboard.writeText(url);

        if (label) {
          label.textContent = t('Скопировано');
          window.setTimeout(() => {
            label.textContent = initialText;
          }, 1800);
        }

        closeInteractivePopovers();
      } catch (error) {
        window.prompt(t('Скопируйте ссылку'), url);
      }
    });
  });
}

function initCardShare() {
  selectAll('[data-card-share]').forEach((button) => {
    button.addEventListener('click', async () => {
      const url = button.dataset.shareUrl || window.location.href;
      const title = button.closest('.post-card')?.querySelector('.post-card__title')?.textContent?.trim() || document.title;

      if (navigator.share) {
        try {
          await navigator.share({ title, url });
          return;
        } catch (error) {
          if (error?.name === 'AbortError') {
            return;
          }
        }
      }

      try {
        await navigator.clipboard.writeText(url);
        button.classList.add('is-copied');
        window.setTimeout(() => button.classList.remove('is-copied'), 1400);
      } catch (error) {
        window.prompt(t('Скопируйте ссылку'), url);
      }
    });
  });
}

function initShareMenus() {
  selectAll('[data-share-menu]').forEach((menu) => {
    const button = select('.article-actionbar__button', menu);
    const open = () => menu.classList.add('is-share-menu-open');

    menu.addEventListener('pointerenter', open);
    menu.addEventListener('focusin', open);

    if (button) {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        open();
      });
    }
  });
}

function initOutsideDismiss() {
  document.addEventListener('click', (event) => {
    const target = event.target instanceof Element ? event.target : null;

    if (!target) {
      closeInteractivePopovers();
      return;
    }

    if (!target.closest('[data-rating-panel]')) {
      selectAll('[data-rating-panel].is-rating-menu-open').forEach((panel) => {
        panel.classList.remove('is-rating-menu-open');
      });
    }

    if (!target.closest('[data-share-menu]')) {
      selectAll('[data-share-menu].is-share-menu-open').forEach((menu) => {
        menu.classList.remove('is-share-menu-open');
      });
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeInteractivePopovers();
    }
  });
}

function closeInteractivePopovers() {
  selectAll('[data-rating-panel].is-rating-menu-open').forEach((panel) => {
    panel.classList.remove('is-rating-menu-open');
  });

  selectAll('[data-share-menu].is-share-menu-open').forEach((menu) => {
    menu.classList.remove('is-share-menu-open');
  });
}

function initBookmarks() {
  selectAll('[data-bookmark-panel]').forEach((panel) => {
    const button = select('[data-bookmark-toggle]', panel);
    const label = select('[data-bookmark-label]', panel);
    const icon = select('[data-bookmark-icon]', panel);

    if (!button) {
      return;
    }

    button.addEventListener('click', async () => {
      if (button.dataset.bookmarkLocked === '1') {
        openAuthModal();
        return;
      }

      const form = new FormData();

      form.set('action', 'imperial_toggle_bookmark');
      form.set('nonce', window.imperialFuture?.bookmarkNonce || '');
      form.set('post_id', panel.dataset.postId || '');
      button.disabled = true;

      try {
        const response = await fetch(window.imperialFuture.ajaxUrl, {
          method: 'POST',
          body: form
        });
        const payload = await response.json();

        if (payload?.data?.loginRequired) {
          openAuthModal();
          return;
        }

        if (payload?.success) {
          const bookmarked = Boolean(payload.data.bookmarked);

          button.classList.toggle('is-selected', bookmarked);
          button.setAttribute('aria-pressed', String(bookmarked));
          button.setAttribute('aria-label', bookmarked ? t('Удалить из закладок') : t('Добавить в закладки'));

          if (label) {
            label.textContent = bookmarked
              ? (window.imperialFuture?.i18n?.bookmarkSaved || t('Сохранено'))
              : (window.imperialFuture?.i18n?.bookmarkRemoved || t('Закладка'));
          }

          if (icon) {
            icon.textContent = bookmarked ? '★' : '☆';
          }
          showNotice(bookmarked ? t('Сохранено') : t('Закладка удалена'), 'success');
        } else {
          showNotice(payload?.data?.message || t('Не удалось выполнить действие. Попробуй ещё раз.'));
        }
      } catch (error) {
        showNotice(t('Не удалось связаться с сервером. Повтори попытку.'));
      } finally {
        button.disabled = false;
      }
    });
  });
}

function initContentLightbox() {
  const images = selectAll('.entry-content .wp-block-image img, .entry-content .wp-block-gallery img');

  if (!images.length) {
    return;
  }

  const lightbox = document.createElement('div');

  lightbox.className = 'imperial-lightbox';
  lightbox.hidden = true;
  lightbox.innerHTML = `
    <button class="imperial-lightbox__close" type="button" aria-label="${escapeAttribute(t('Закрыть изображение'))}">${iconMarkup('x')}</button>
    <button class="imperial-lightbox__nav imperial-lightbox__nav--prev" type="button" data-lightbox-prev aria-label="${escapeAttribute(t('Предыдущее изображение'))}">${iconMarkup('chevron-left')}</button>
    <figure class="imperial-lightbox__figure">
      <img alt="">
      <figcaption></figcaption>
    </figure>
    <button class="imperial-lightbox__nav imperial-lightbox__nav--next" type="button" data-lightbox-next aria-label="${escapeAttribute(t('Следующее изображение'))}">${iconMarkup('chevron-right')}</button>
  `;
  document.body.appendChild(lightbox);

  const lightboxImage = select('img', lightbox);
  const caption = select('figcaption', lightbox);
  const closeButton = select('.imperial-lightbox__close', lightbox);
  const prevButton = select('[data-lightbox-prev]', lightbox);
  const nextButton = select('[data-lightbox-next]', lightbox);
  let activeIndex = 0;

  if (images.length < 2) {
    prevButton?.setAttribute('hidden', '');
    nextButton?.setAttribute('hidden', '');
  }

  const imageData = (image) => {
    const link = image.closest('a');

    return {
      source: link?.href || image.currentSrc || image.src,
      text: image.getAttribute('alt') || image.closest('figure')?.querySelector('figcaption')?.textContent || ''
    };
  };

  const show = (index) => {
    const normalizedIndex = (index + images.length) % images.length;
    const { source, text } = imageData(images[normalizedIndex]);

    if (!source || !lightboxImage || !caption) {
      return;
    }

    activeIndex = normalizedIndex;
    lightboxImage.src = source;
    lightboxImage.alt = text;
    caption.textContent = text;
    caption.hidden = text.trim() === '';
  };

  const open = (index) => {
    show(index);
    lightbox.hidden = false;
    document.body.classList.add('lightbox-open');
    closeButton?.focus();
  };

  const close = () => {
    lightbox.hidden = true;
    document.body.classList.remove('lightbox-open');

    if (lightboxImage) {
      lightboxImage.removeAttribute('src');
    }
  };

  const move = (step) => {
    if (lightbox.hidden || images.length < 2) {
      return;
    }

    show(activeIndex + step);
  };

  images.forEach((image, index) => {
    image.dataset.lightboxImage = '';
    image.tabIndex = 0;
    image.setAttribute('role', 'button');

    image.addEventListener('click', (event) => {
      event.preventDefault();
      open(index);
    });

    image.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter' && event.key !== ' ') {
        return;
      }

      event.preventDefault();
      open(index);
    });
  });

  closeButton?.addEventListener('click', close);
  prevButton?.addEventListener('click', () => move(-1));
  nextButton?.addEventListener('click', () => move(1));
  lightbox.addEventListener('click', (event) => {
    if (event.target === lightbox) {
      close();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !lightbox.hidden) {
      close();
    }

    if (event.key === 'ArrowLeft') {
      move(-1);
    }

    if (event.key === 'ArrowRight') {
      move(1);
    }
  });
}

function initCodeBlocks() {
  selectAll('.entry-content pre, .imperial-comment__content pre').filter((block) => block.classList.contains('wp-block-code') || select('code', block)).forEach((block) => {
    if (block.dataset.codeEnhanced === '1') {
      return;
    }

    block.dataset.codeEnhanced = '1';
    block.classList.add('imperial-code-shell');

    const button = document.createElement('button');

    button.type = 'button';
    button.className = 'imperial-code-shell__copy';
    button.innerHTML = iconMarkup('copy');
    button.title = t('Копировать');
    button.setAttribute('aria-label', t('Копировать'));
    block.appendChild(button);

    button.addEventListener('click', async () => {
      const code = select('code', block)?.textContent || block.textContent.replace(button.textContent, '');

      try {
        await copyTextToClipboard(code.trim());
        button.innerHTML = iconMarkup('check');
        button.setAttribute('aria-label', t('Скопировано'));
        window.setTimeout(() => {
          button.innerHTML = iconMarkup('copy');
          button.setAttribute('aria-label', t('Копировать'));
        }, 1600);
      } catch (error) {
        window.prompt(t('Скопируйте код'), code.trim());
      }
    });
  });
}

async function copyTextToClipboard(text) {
  if (navigator.clipboard?.writeText) {
    await navigator.clipboard.writeText(text);
    return;
  }

  const field = document.createElement('textarea');

  field.value = text;
  field.setAttribute('readonly', '');
  field.style.position = 'fixed';
  field.style.opacity = '0';
  document.body.appendChild(field);
  field.select();
  const copied = document.execCommand('copy');
  field.remove();
  if (!copied) throw new Error('clipboard_unavailable');
}

function initExternalExit() {
  const page = select('[data-external-exit]');

  if (!page) {
    return;
  }

  const target = page.dataset.externalTarget || '';
  const countdown = select('[data-external-countdown]', page);
  const button = select('[data-external-continue]', page);
  let left = Math.max(0, Number(page.dataset.externalDelay || 0));

  button?.addEventListener('click', (event) => {
    if (left > 0) {
      event.preventDefault();
    }
  });

  const unlock = () => {
    button?.classList.remove('is-disabled');

    if (target) {
      window.location.href = target;
    }
  };

  if (left <= 0) {
    unlock();
    return;
  }

  const timer = window.setInterval(() => {
    left -= 1;

    if (countdown) {
      countdown.textContent = String(Math.max(0, left));
    }

    if (left <= 0) {
      window.clearInterval(timer);
      unlock();
    }
  }, 1000);
}

function escapeHtml(value) {
  const holder = document.createElement('span');

  holder.textContent = String(value || '');
  return holder.innerHTML;
}

function escapeAttribute(value) {
  return escapeHtml(value).replace(/"/g, '&quot;');
}

function cssEscape(value) {
  if (window.CSS?.escape) {
    return window.CSS.escape(value);
  }

  return String(value).replace(/"/g, '\\"');
}

function runAfterLoad(callback) {
  const run = () => window.setTimeout(callback, 900);

  if (document.readyState === 'complete') {
    run();
    return;
  }

  window.addEventListener('load', run, { once: true });
}

function runWhenVisible(element, callback) {
  if (!('IntersectionObserver' in window)) {
    runAfterLoad(callback);
    return;
  }

  const observer = new IntersectionObserver((entries) => {
    const isVisible = entries.some((entry) => entry.isIntersecting);

    if (!isVisible) {
      return;
    }

    observer.disconnect();
    callback();
  }, { rootMargin: '420px 0px' });

  observer.observe(element);
}
