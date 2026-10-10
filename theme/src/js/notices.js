export function showNotice(message, type = 'error') {
  const container = document.querySelector('[data-site-notices]');
  if (!container || !message) return;
  const notice = document.createElement('div');
  notice.className = `site-notice site-notice--${type === 'success' ? 'success' : 'error'}`;
  notice.setAttribute('role', type === 'success' ? 'status' : 'alert');
  const text = document.createElement('p');
  text.textContent = message;
  const close = document.createElement('button');
  close.type = 'button';
  close.dataset.noticeDismiss = '';
  close.setAttribute('aria-label', window.imperialFuture?.i18n?.noticeClose || 'Закрыть уведомление');
  close.textContent = '×';
  notice.append(text, close);
  container.replaceChildren(notice);
}

export function initNotices() {
  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-notice-dismiss]');
    if (button) button.closest('.site-notice')?.remove();
  });
  const url = new URL(window.location.href);
  ['imperial_auth_status', 'contact_status', 'newsletter_status'].forEach((key) => url.searchParams.delete(key));
  if (url.href !== window.location.href) history.replaceState(history.state, '', url);
}
