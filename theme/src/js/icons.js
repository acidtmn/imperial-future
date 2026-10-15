const names = new Set(['bold', 'italic', 'quote', 'code', 'list', 'list-ordered', 'link', 'eye', 'copy', 'check', 'x', 'chevron-left', 'chevron-right']);

export function iconMarkup(name) {
  if (!names.has(name)) return '';
  const namespace = 'http://www.w3.org/2000/svg';
  const svg = document.createElementNS(namespace, 'svg');
  const attributes = { class: 'imperial-icon', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.8', 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'aria-hidden': 'true', focusable: 'false' };
  Object.entries(attributes).forEach(([key, value]) => svg.setAttribute(key, value));
  const use = document.createElementNS(namespace, 'use');
  use.setAttribute('href', `#imperial-icon-${name}`);
  svg.append(use);
  return svg.outerHTML;
}
