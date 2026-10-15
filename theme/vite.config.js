import { defineConfig } from 'vite';
import { readFileSync } from 'node:fs';
import postcss from 'postcss';
import valueParser from 'postcss-value-parser';

const icons = ['message-circle', 'bookmark', 'share', 'ellipsis', 'thumbs-up', 'thumbs-down', 'chevron-down', 'chevron-right', 'link', 'search', 'user-round', 'sun', 'moon', 'menu', 'copy', 'mail', 'reply', 'shield-check', 'pen-line', 'log-out', 'bold', 'italic', 'quote', 'code', 'list', 'list-ordered', 'eye', 'check', 'x', 'chevron-left', 'pause', 'play'];

export default defineConfig({
  base: './',
  plugins: [{
    name: 'imperial-icons',
    generateBundle() {
      const symbols = icons.map((name) => {
        const svg = readFileSync(new URL(`./node_modules/lucide-static/icons/${name}.svg`, import.meta.url), 'utf8');
        const content = svg.replace(/^[\s\S]*?<svg[^>]*>/, '').replace(/<\/svg>[\s\S]*$/, '');
        return `<symbol id="imperial-icon-${name}" viewBox="0 0 24 24">${content}</symbol>`;
      }).join('');
      this.emitFile({ type: 'asset', fileName: 'icons.svg', source: `<svg xmlns="http://www.w3.org/2000/svg">${symbols}</svg>` });
    }
  }, {
    name: 'imperial-critical-styles',
    generateBundle(_, bundle) {
      const styles = Object.values(bundle).filter((file) => file.type === 'asset' && file.fileName.endsWith('.css'));
      const critical = styles.find((file) => file.fileName.startsWith('css/critical-'));
      const main = styles.find((file) => file.fileName.startsWith('css/main-'));
      if (!critical || !main) throw new Error('Missing critical or main stylesheet');

      const root = postcss.parse(String(critical.source));
      const mainRoot = postcss.parse(String(main.source));
      const fonts = [];
      mainRoot.walkAtRules('font-face', (rule) => { fonts.push(rule.clone()); rule.remove(); });
      const fontRoot = postcss.root({ nodes: fonts });
      fontRoot.walkAtRules('font-face', (rule) => {
        const family = rule.nodes.find((node) => node.prop === 'font-family')?.value.replaceAll('"', '').replaceAll("'", '');
        if (!['Oranienbaum', 'Forum', 'Prata'].includes(family)) return;
        const source = rule.nodes.find((node) => node.prop === 'src');
        const groups = [[]];
        for (const node of valueParser(source.value).nodes) {
          if (node.type === 'div' && node.value === ',') groups.push([]);
          else groups.at(-1).push(node);
        }
        const size = (group) => {
          const url = group.find((node) => node.type === 'function' && node.value === 'url')?.nodes[0].value;
          const file = new URL(url, `https://assets.invalid/${main.fileName}`).pathname.slice(1);
          return bundle[file]?.source.length ?? Infinity;
        };
        groups.sort((left, right) => size(left) - size(right));
        source.value = groups.map((nodes) => valueParser.stringify(nodes)).join(',');
      });
      this.emitFile({ type: 'asset', fileName: 'css/fonts-all.css', source: fontRoot.toString() });
      main.source = mainRoot.toString();
      // Встроенный CSS использует адрес каталога темы, а не относительный адрес HTML-страницы.
      for (const sheet of [root, fontRoot]) sheet.walkDecls((declaration) => {
        const value = valueParser(declaration.value);
        value.walk((node) => {
          if (node.type !== 'function' || node.value !== 'url' || node.nodes.length !== 1) return;
          const source = node.nodes[0].value;
          if (/^(?:data:|https?:|\/|#)/.test(source)) return;
          const resolved = new URL(source, `https://assets.invalid/${critical.fileName}`);
          node.nodes[0].value = `__IMPERIAL_DIST_URL__${resolved.pathname}`;
        });
        declaration.value = value.toString();
      });
      const families = {};
      fontRoot.walkAtRules('font-face', (rule) => {
        const family = valueParser(rule.nodes.find((node) => node.prop === 'font-family').value).nodes
          .filter((node) => node.type !== 'space').map((node) => node.value).join(' ');
        families[family] = (families[family] || '') + rule.toString();
      });
      this.emitFile({ type: 'asset', fileName: 'css/fonts-inline.json', source: JSON.stringify(families) });
      this.emitFile({ type: 'asset', fileName: 'css/critical-inline.css', source: root.toString() });
    }
  }],
  build: {
    manifest: true,
    outDir: 'assets/dist',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        main: 'src/js/main.js',
        critical: 'src/scss/critical.scss',
        comments: 'src/scss/comments.scss',
        account: 'src/scss/account.scss'
      },
      output: {
        manualChunks: {
          motion: ['gsap', 'gsap/ScrollTrigger']
        },
        entryFileNames: 'js/[name]-[hash].js',
        chunkFileNames: 'js/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          const name = assetInfo.name || '';

          if (name.endsWith('.css')) {
            return 'css/[name]-[hash][extname]';
          }

          return 'media/[name]-[hash][extname]';
        }
      }
    }
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true
  }
});
