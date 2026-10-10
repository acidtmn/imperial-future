export function initAccount() {
  const workspace = document.querySelector('[data-account-workspace]');
  if (!workspace) return;
  workspace.querySelectorAll('[data-avatar-file]').forEach((input) => {
    const caption = input.closest('.account-file')?.querySelector('[data-avatar-file-name]');
    if (!caption) return;
    const empty = caption.textContent;
    input.addEventListener('change', () => { caption.textContent = input.files[0]?.name || empty; });
  });
  const tabs = [...workspace.querySelectorAll('[data-account-view]')];
  const sections = [...workspace.querySelectorAll('[data-account-section]')];
  const nav = workspace.querySelector('.account-nav');
  nav.setAttribute('role', 'tablist');
  tabs.forEach((tab) => {
    tab.id = `tab-${tab.dataset.accountView}`;
    tab.setAttribute('role', 'tab');
    tab.setAttribute('aria-controls', `account-${tab.dataset.accountView}`);
  });
  sections.forEach((section) => {
    section.setAttribute('role', 'tabpanel');
    section.setAttribute('aria-labelledby', `tab-${section.dataset.accountSection}`);
  });
  const activate = (view, updateUrl = false) => {
    tabs.forEach((tab) => {
      const active = tab.dataset.accountView === view;
      tab.setAttribute('aria-selected', String(active));
      tab.tabIndex = active ? 0 : -1;
    });
    sections.forEach((section) => { section.hidden = section.dataset.accountSection !== view; });
    if (updateUrl) history.replaceState(history.state, '', `#account-${view}`);
  };
  tabs.forEach((tab, index) => {
    tab.addEventListener('click', (event) => {
      event.preventDefault();
      activate(tab.dataset.accountView, true);
    });
    tab.addEventListener('keydown', (event) => {
      if (!['ArrowRight', 'ArrowLeft', 'ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;
      event.preventDefault();
      const next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (index + (['ArrowRight', 'ArrowDown'].includes(event.key) ? 1 : -1) + tabs.length) % tabs.length;
      tabs[next].click();
      tabs[next].focus();
    });
  });
  const selectHash = () => {
    const section = sections.find((item) => `#${item.id}` === window.location.hash);
    activate(section?.dataset.accountSection || 'profile');
  };
  window.addEventListener('hashchange', selectHash);
  selectHash();
}
