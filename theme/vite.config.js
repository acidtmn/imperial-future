import { defineConfig } from 'vite';
import { readFileSync } from 'node:fs';

const icons = ['message-circle', 'bookmark', 'share', 'ellipsis', 'thumbs-up', 'thumbs-down', 'chevron-down', 'chevron-right', 'link', 'search', 'user-round', 'sun', 'moon', 'menu', 'copy', 'mail', 'reply', 'shield-check', 'pen-line', 'log-out', 'bold', 'italic', 'quote', 'code', 'list', 'list-ordered', 'eye', 'check', 'x', 'chevron-left'];

export default defineConfig({
  base: './',
  plugins: [{
    name: 'imperial-icons',
    generateBundle() {
      const symbols = icons.map((name) => {
        const svg = readFileSync(new URL(`./node_modules/lucide-static/icons/${name}.svg`, import.meta.url), 'utf8');
        const content = svg.replace(/^[\s\S]*?<svg[^>]*>/, '').replace(/<\/svg>[\s\S]*$/, '');
        return `<symbol id="${name}" viewBox="0 0 24 24">${content}</symbol>`;
      }).join('');
      this.emitFile({ type: 'asset', fileName: 'icons.svg', source: `<svg xmlns="http://www.w3.org/2000/svg">${symbols}</svg>` });
    }
  }],
  build: {
    manifest: true,
    outDir: 'assets/dist',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        main: 'src/js/main.js'
      },
      output: {
        manualChunks: {
          motion: ['gsap', 'gsap/ScrollTrigger'],
          slider: ['swiper', 'swiper/modules']
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
