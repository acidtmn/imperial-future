import assert from 'node:assert/strict';
import { readFileSync, existsSync } from 'node:fs';
import { resolve, dirname, relative } from 'node:path';
import { fileURLToPath } from 'node:url';
import postcss from 'postcss';
import valueParser from 'postcss-value-parser';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '../assets/dist');
const manifest = JSON.parse(readFileSync(resolve(root, '.vite/manifest.json'), 'utf8'));
const checkFile = (file) => {
  const path = resolve(root, file);
  assert(!relative(root, path).startsWith('..'), `Asset outside dist: ${file}`);
  assert(existsSync(path), `Missing asset: ${file}`);
};
for (const entry of Object.values(manifest)) {
  for (const file of [entry.file, ...(entry.css || []), ...(entry.assets || [])]) checkFile(file);
}
for (const file of ['css/critical-inline.css', 'css/fonts-all.css', 'css/fonts-inline.json', 'icons.svg']) checkFile(file);

const fonts = JSON.parse(readFileSync(resolve(root, 'css/fonts-inline.json'), 'utf8'));
for (const family of ['Inter Variable', 'Oranienbaum', 'Forum', 'Prata', 'PT Sans', 'IBM Plex Sans']) {
  assert(fonts[family], `Missing font family: ${family}`);
  postcss.parse(fonts[family]).walkAtRules('font-face', (rule) => {
    assert(rule.nodes.some((node) => node.prop === 'unicode-range'), `Missing Unicode range: ${family}`);
  });
}

// Проверяем адреса перед подстановкой реального каталога WordPress.
for (const css of [readFileSync(resolve(root, 'css/critical-inline.css'), 'utf8'), ...Object.values(fonts)]) {
  assert(!/<\/style/i.test(css), 'Unsafe inline style terminator');
  postcss.parse(css).walkDecls((declaration) => {
    valueParser(declaration.value).walk((node) => {
      if (node.type !== 'function' || node.value !== 'url') return;
      const url = node.nodes[0].value;
      if (url.startsWith('data:')) return;
      assert(url.startsWith('__IMPERIAL_DIST_URL__/'), `Invalid inline asset URL: ${url}`);
      checkFile(url.slice('__IMPERIAL_DIST_URL__/'.length));
    });
  });
}

const sprite = readFileSync(resolve(root, 'icons.svg'), 'utf8');
const symbols = [...sprite.matchAll(/<symbol id="([^"]+)"/g)].map((match) => match[1]);
assert(symbols.length === new Set(symbols).size && symbols.length >= 30, 'Invalid icon sprite');
assert(symbols.every((name) => name.startsWith('imperial-icon-')), 'Unprefixed icon symbol');
console.log(`PASS: ${Object.keys(manifest).length} manifest entries, 6 font families, ${symbols.length} icons; inline CSS URLs valid.`);
