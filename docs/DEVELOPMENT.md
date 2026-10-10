# Разработка и сборка

## Структура репозитория

```text
theme/
  assets/          исходные изображения, иконки и production dist
  src/js/          интерфейс и анимации
  src/scss/        стили
  inc/             PHP-классы по ответственности
  parts/           части шаблонов
  templates/       дополнительные шаблоны
  languages/       POT, ru_RU PO/MO
  vendor/          установленные production Composer-зависимости
  licenses/        полные тексты сторонних лицензий и реестр
  tests/           локальные проверки, не входят в установочный ZIP
  tools/           инструменты темы
  functions.php
  style.css
  theme.json
  vite.config.js
scripts/           сборка ZIP и справочника настроек
docs/              русская документация
```

Это классическая PHP-тема с поддержкой Gutenberg, не block/FSE-тема и не frontend-конструктор. Функции интеграций намеренно остаются внутри темы для собственного сайта. Контроллеры делегируют сервисам, а не содержат всю бизнес-логику. Локальный Docker сайта, `.env`, база и резервные копии не входят в публичный репозиторий.

## Ресурсы

В окружении разработки нужны Node.js 22, npm, PHP 8.3 с необходимыми расширениями и Composer. Выполняй из `theme/`:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
node tools/collect-licenses.mjs
```

`npm run dev` запускает служебную страницу Vite, но WordPress `AssetLoader` читает production manifest: для проверки изменений на самом WordPress выполни `npm run build`. См. `inc/Support/AssetLoader.php` и `vite.config.js`. Публичному сайту dev server не нужен. В репозиторий включены готовые `assets/dist` и `vendor`, поэтому исходный снимок можно изучить без пересборки. При изменении зависимостей обновляй lock-файлы, автозагрузку, production-ресурсы и лицензии согласованно.

GSAP, Swiper и EasyMDE имеют разные роли: эффекты, слайдеры, Markdown-редактор. League CommonMark используется на сервере. Не подменяй серверную обработку Markdown только клиентским предпросмотром. Иконки интерфейса берутся из Lucide, а не из случайных Unicode-символов.

## Переводы

Text Domain: `imperial-future`. Каталог: `theme/languages/`. Русский интерфейс является исходным; файлы POT/PO/MO включены. Для обновления шаблона можно использовать WP-CLI i18n:

```sh
wp i18n make-pot theme theme/languages/imperial-future.pot --domain=imperial-future --exclude=vendor,node_modules,assets/dist,tests
wp i18n make-mo theme/languages
```

После добавления строк обнови PO перед компиляцией MO. Проверяй строки PHP и клиентский словарь `FrontendStrings`, не правь только готовый JS.

## Справочник настроек

Экспортёр читает определения двух регистрационных классов Customizer, **не сохранённые theme_mod**, и заменяет динамические email/название сайта нейтральными подписями. На тестовом WordPress с активной темой:

```sh
wp --path=/path/to/wordpress eval-file /path/to/repository/scripts/export-settings.php > /path/to/repository/output/settings-definitions.json
```

Каталог `output/` предварительно должен существовать. Из корня репозитория в PowerShell 7.2+:

```powershell
./scripts/build-settings-guide.ps1
```

Получается `docs/SETTINGS.md`. Не заменяй определения выгрузкой действующих настроек сайта: там могут находиться OAuth-секреты и пользовательские контакты.

## Проверки

На отдельном тестовом WordPress с установленной темой и из его корня:

```sh
php wp-content/themes/imperial-future/tests/theme-smoke.php
php wp-content/themes/imperial-future/tests/newsletter.php
```

Проверки создают временные объекты, затрагивают настройки и перехватывают почту. **Не запускай их на производственной базе.** Проверяй синтаксис изменённых PHP-файлов, production-сборку, `npm audit` и `composer audit`. Тесты не входят в пользовательский ZIP.

Для UI проверь обычным браузером вход/выход, ошибки форм, смену пароля, EasyMDE «ВИД» и возврат, реакцию, закладку, копирование, галерею и кнопки клавиатуры. Для вёрстки - 320, 390, 768, 1200 и 1440 px, светлую и тёмную темы. Lighthouse запускай без параллельной нагрузки; указывай версию инструмента и реальные результаты.

## Установочный архив

Из корня проекта, PowerShell 7.2+:

```powershell
./scripts/package-theme.ps1
Get-FileHash output/imperial-future-1.1.1.zip -Algorithm SHA256
```

Скрипт читает `theme/` напрямую, проверяет manifest, Composer, снимок и реестр лицензий, исключает секреты/тесты/временные файлы. В ZIP один верхний каталог `imperial-future/`. Установочные файлы не копируются во второй checkout. Порядок и даты записей фиксированы, ZIP без сжатия обеспечивает воспроизводимость в одной среде .NET/PowerShell. `.gitattributes` запрещает автоматическую замену переносов строк: тексты лицензий и lock-файлы должны сохранять байты, по которым проверяется SHA-256. Не меняй исходники одновременно с упаковкой.

При новой версии согласованно обнови заголовок `style.css`, package metadata, readme, документацию, упаковщик, CHANGELOG и имя артефакта. Загрузи ZIP и файл SHA-256 в GitHub Releases; автоматический Source code ZIP не заменяет установочный. Не публикуй `.env`, SQL, реальные OAuth/SMTP-ключи, тестовые дампы или личные снимки кабинета.
