const http = require('node:http');
const fs = require('node:fs/promises');
const lighthouse = require('/usr/src/app/node_modules/lighthouse');
const chromeLauncher = require('/usr/src/app/node_modules/chrome-launcher');

// Сохраняем исходный localhost origin внутри контейнера, не меняя адрес WordPress.
const proxy = http.createServer((request, response) => {
  const upstream = http.request({
    hostname: 'host.docker.internal', port: 19080, path: request.url,
    method: request.method, headers: { ...request.headers, host: 'localhost:19080' },
  }, (result) => {
    response.writeHead(result.statusCode, result.headers);
    result.pipe(response);
  });
  upstream.on('error', (error) => { response.writeHead(502); response.end(error.message); });
  request.pipe(upstream);
});

(async () => {
  await new Promise((resolve) => proxy.listen(19080, '127.0.0.1', resolve));
  let chrome;
  try {
    chrome = await chromeLauncher.launch({ chromeFlags: ['--headless', '--no-sandbox', '--disable-dev-shm-usage'] });
    const result = await lighthouse('http://localhost:19080/', {
      port: chrome.port, output: ['json', 'html'],
      onlyCategories: ['performance', 'accessibility', 'best-practices', 'seo'],
      logLevel: 'error',
    });
    await fs.writeFile('/reports/home-mobile.report.json', result.report[0]);
    await fs.writeFile('/reports/home-mobile.report.html', result.report[1]);
    process.stdout.write(JSON.stringify(Object.fromEntries(Object.entries(result.lhr.categories).map(([key, value]) => [key, value.score]))) + '\n');
    if (result.lhr.runtimeError) throw new Error(result.lhr.runtimeError.message);
  } finally {
    if (chrome) await chrome.kill();
    await new Promise((resolve) => proxy.close(resolve));
  }
})().catch((error) => { process.stderr.write(error.stack + '\n'); process.exitCode = 1; });
