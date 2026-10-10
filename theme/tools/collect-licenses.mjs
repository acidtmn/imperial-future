import { createHash } from 'node:crypto';
import { existsSync, lstatSync, mkdirSync, readFileSync, readdirSync, writeFileSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const licenseName = /^(?:licen[sc]e|copying|notice|copyright|ofl)(?:[._-]|$)/i;
const supplemental = {
  gsap: {
    version: '3.15.0',
    path: 'licenses/supplemental/gsap-3.15.0-Standard-License.txt',
    url: 'https://gsap.com/community/standard-license/',
    requiredText: 'VI. MISCELLANEOUS PROVISIONS',
  },
  'typo-js': {
    version: '1.3.2',
    path: 'licenses/supplemental/typo-js-1.3.2-license.txt',
    url: 'https://raw.githubusercontent.com/cfinke/Typo.js/a08c1583d03a9e065d8240b7018beaaadc6f4ab7/license.txt',
    requiredText: 'THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS',
  },
};

function sha256(content) {
  return createHash('sha256').update(content).digest('hex');
}

function readJson(path) {
  return JSON.parse(readFileSync(join(root, path), 'utf8'));
}

function licenseFiles(path) {
  return readdirSync(join(root, path), { withFileTypes: true })
    .filter((entry) => entry.isFile() && licenseName.test(entry.name))
    .map((entry) => `${path}/${entry.name}`)
    .sort();
}

function assertSafePath(path) {
  if (path.startsWith('/') || path.includes('\\') || path.split('/').includes('..')) {
    throw new Error(`Недопустимый путь: ${path}`);
  }
}

function checkedContent(path) {
  assertSafePath(path);
  const full = join(root, path);
  if (!lstatSync(full).isFile()) {
    throw new Error(`Ожидался обычный файл: ${path}`);
  }
  const content = readFileSync(full);
  if (!content.length) throw new Error(`Пустой текст лицензии: ${path}`);
  return content;
}

const lockBytes = readFileSync(join(root, 'package-lock.json'));
const lock = JSON.parse(lockBytes.toString('utf8'));
const packageJson = readJson('package.json');
const output = new Map();
const packages = [];
const missingOptional = [];

// Проверяем установленные версии до записи, чтобы не сохранить неполный реестр.
for (const [path, expected] of Object.entries(lock.packages).sort(([a], [b]) => a < b ? -1 : a > b ? 1 : 0)) {
  if (!path) continue;
  assertSafePath(path);
  if (!path.startsWith('node_modules/')) throw new Error(`Не поддерживается локальная зависимость: ${path}`);
  if (!existsSync(join(root, path, 'package.json'))) {
    if (!expected.optional) throw new Error(`Не установлен пакет: ${path}. Выполни npm ci.`);
    missingOptional.push(path);
    continue;
  }

  const metadata = readJson(`${path}/package.json`);
  if (metadata.version !== expected.version) {
    throw new Error(`Версия ${metadata.name}: установлена ${metadata.version}, lock требует ${expected.version}`);
  }
  const sources = licenseFiles(path);

  // Платформенные бинарные пакеты используют лицензию основного пакета той же версии.
  if (!sources.length && (metadata.name.startsWith('@esbuild/') || metadata.name.startsWith('@rollup/rollup-'))) {
    const parent = metadata.name.startsWith('@esbuild/') ? 'esbuild' : 'rollup';
    if (readJson(`node_modules/${parent}/package.json`).version !== metadata.version) {
      throw new Error(`Не совпала версия лицензии ${parent} для ${metadata.name}`);
    }
    sources.push(...licenseFiles(`node_modules/${parent}`));
  }

  const notices = [];
  for (const source of sources) {
    const relative = source.slice('node_modules/'.length);
    const target = `licenses/npm/${relative}`;
    const content = checkedContent(source);
    output.set(target, content);
    notices.push({ path: target, source, sha256: sha256(content) });
  }

  if (supplemental[metadata.name]) {
    const extra = supplemental[metadata.name];
    if (metadata.version !== extra.version) {
      throw new Error(`Проверь upstream-лицензию ${metadata.name} ${metadata.version}: закреплена ${extra.version}`);
    }
    const content = checkedContent(extra.path);
    if (!content.toString('utf8').includes(extra.requiredText)) {
      throw new Error(`Неполная дополнительная лицензия: ${extra.path}`);
    }
    notices.push({ path: extra.path, source: extra.url, sha256: sha256(content) });
  }

  if (!notices.length) throw new Error(`Нет полного текста лицензии: ${metadata.name}`);
  packages.push({
    name: metadata.name,
    version: metadata.version,
    installedPath: path,
    license: metadata.license ?? expected.license ?? 'Не указана в метаданных пакета',
    role: metadata.name === 'lucide-static' ? 'Значки в сборке' : expected.dev ? 'Инструмент сборки' : 'Зависимость интерфейса',
    repository: typeof metadata.repository === 'string' ? metadata.repository : metadata.repository?.url ?? null,
    notices,
  });
}

for (const name of [...Object.keys(packageJson.dependencies ?? {}), ...Object.keys(packageJson.devDependencies ?? {})]) {
  if (!packages.some((entry) => entry.name === name && entry.installedPath === `node_modules/${name}`)) {
    throw new Error(`Отсутствует прямая зависимость: ${name}`);
  }
}

const composerLockBytes = readFileSync(join(root, 'composer.lock'));
const composerLock = JSON.parse(composerLockBytes.toString('utf8'));
const installedComposer = readJson('vendor/composer/installed.json');
const composerPackages = [];
for (const expected of composerLock.packages) {
  const installed = installedComposer.packages.find((entry) => entry.name === expected.name);
  if (!installed || installed.version !== expected.version) {
    throw new Error(`Composer-пакет ${expected.name} не соответствует composer.lock`);
  }
  const files = licenseFiles(`vendor/${expected.name}`);
  if (!files.length) throw new Error(`Нет лицензии Composer-пакета ${expected.name}`);
  composerPackages.push({
    name: expected.name,
    version: expected.version,
    license: expected.license,
    notices: files.map((path) => ({ path, source: path, sha256: sha256(checkedContent(path)) })),
  });
}
const composerAutoloader = { path: 'vendor/composer/LICENSE', sha256: sha256(checkedContent('vendor/composer/LICENSE')) };

const index = {
  schemaVersion: 1,
  packageLockSha256: sha256(lockBytes),
  composerLockSha256: sha256(composerLockBytes),
  npm: packages,
  missingOptional,
  composer: composerPackages,
  composerAutoloader,
};
output.set('licenses/index.json', Buffer.from(`${JSON.stringify(index, null, 2)}\n`));
output.set('licenses/README.md', Buffer.from([
  '# Реестр лицензий',
  '',
  'Создан `node tools/collect-licenses.mjs` из установленных зависимостей и lock-файлов.',
  'Полные тексты npm скопированы без изменений. PHP-лицензии остаются в `vendor`.',
  'SHA-256, исходные пути и отсутствующие платформенные optional-пакеты: [index.json](index.json).',
  'Инструменты сборки перечислены для учёта, их бинарные файлы в ZIP не входят.',
  'GSAP Standard License не заменяется GPL темы. Upstream-тексты GSAP и Typo.js закреплены по версиям.',
  '',
  '| npm-пакет | Версия | Назначение | Полные тексты |',
  '| --- | --- | --- | --- |',
  ...packages.map((entry) => `| ${entry.name} | ${entry.version} | ${entry.role} | ${entry.notices.map((notice) => `[${notice.path.split('/').pop()}](${notice.path.slice('licenses/'.length)})`).join(', ')} |`),
  '',
  '| Composer-пакет | Версия | Полные тексты |',
  '| --- | --- | --- |',
  ...composerPackages.map((entry) => `| ${entry.name} | ${entry.version} | ${entry.notices.map((notice) => `[${notice.path.split('/').pop()}](../${notice.path})`).join(', ')} |`),
  '',
  '[Composer autoloader](../vendor/composer/LICENSE): MIT.',
  '',
].join('\n')));

// Не меняем время уже совпадающих файлов и не затрагиваем исходники зависимостей.
for (const [path, content] of output) {
  const full = join(root, path);
  if (existsSync(full) && readFileSync(full).equals(content)) continue;
  mkdirSync(dirname(full), { recursive: true });
  writeFileSync(full, content);
}
console.log(`Сохранены полные лицензии: npm ${packages.length}, Composer ${composerPackages.length}; реестр licenses/index.json.`);
