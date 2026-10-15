import http from 'node:http';
import fs from 'node:fs/promises';
import lighthouse from 'lighthouse';
import { launch } from 'chrome-launcher';
import desktop from 'lighthouse/core/config/desktop-config.js';

const url = new URL(process.env.AUDIT_URL || 'http://localhost:19080/');
const mode = process.env.AUDIT_MODE || 'mobile';
const label = process.env.AUDIT_LABEL || `home-${mode}`;
if (url.hostname !== 'localhost' || url.protocol !== 'http:' || !['mobile', 'desktop'].includes(mode) || !/^[a-z0-9-]+$/.test(label)) {
  throw new Error('Invalid audit URL, mode or label');
}

// Сохраняем origin WordPress внутри контейнера, не изменяя страницу или условия Lighthouse.
const proxy = http.createServer((request, response) => {
  const upstream = http.request({
    hostname: 'host.docker.internal', port: url.port || 80, path: request.url,
    method: request.method, headers: { ...request.headers, host: url.host },
  }, (result) => {
    response.writeHead(result.statusCode, result.headers);
    result.pipe(response);
  });
  upstream.on('error', (error) => { response.writeHead(502); response.end(error.message); });
  request.pipe(upstream);
});

let chrome;
try {
  await new Promise((resolve) => proxy.listen(Number(url.port || 80), '127.0.0.1', resolve));
  chrome = await launch({ chromeFlags: ['--headless', '--no-sandbox', '--disable-dev-shm-usage'] });
  const result = await lighthouse(url.href, {
    port: chrome.port, output: ['json', 'html'], logLevel: 'error',
    onlyCategories: ['performance', 'accessibility', 'best-practices', 'seo'],
  }, mode === 'desktop' ? desktop : undefined);
  if (result.lhr.runtimeError) throw new Error(result.lhr.runtimeError.message);
  await fs.writeFile(`/reports/${label}.report.json`, result.report[0]);
  await fs.writeFile(`/reports/${label}.report.html`, result.report[1]);
  console.log(JSON.stringify({
    version: result.lhr.lighthouseVersion, url: result.lhr.finalDisplayedUrl, mode,
    scores: Object.fromEntries(Object.entries(result.lhr.categories).map(([key, value]) => [key, Math.round(value.score * 100)])),
    metrics: Object.fromEntries(['first-contentful-paint', 'largest-contentful-paint', 'speed-index', 'total-blocking-time', 'cumulative-layout-shift'].map((key) => [key, result.lhr.audits[key].numericValue])),
  }));
} finally {
  if (chrome) await chrome.kill();
  await new Promise((resolve) => proxy.close(resolve));
}
