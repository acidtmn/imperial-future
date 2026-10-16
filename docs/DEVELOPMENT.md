# Разработка и сборка Imperial Future 1.1.4

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

Это классическая PHP-тема с поддержкой Gutenberg, не block/FSE-тема и не frontend-конструктор. Функции интеграций намеренно остаются внутри темы для собственного сайта. Контроллеры делегируют сервисам, а не содержат всю бизнес-логику. Приватный `docker-compose.yml` локального сайта, `.env`, база и резервные копии не публикуются и не входят в установочный ZIP. Отдельный Dockerfile CLI-аудитора находится в `tools/lighthouse/` и не требует этого compose-файла.

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

Vite выделяет entry `critical`, `comments` и `account`. Сборка создаёт `css/critical-inline.css`, `css/fonts-inline.json` и `css/fonts-all.css`; `CriticalStyles` встраивает критический слой и объявления выбранных шрифтов. Основной CSS откладывается только при `is_front_page() && is_home()` и успешно подключённом критическом слое; без JavaScript его подключает `noscript`. `AssetLoader` добавляет CSS комментариев для записей, кабинета - для шаблона `page-account.php`. Swiper импортируется через `src/js/slider.js` при приближении к видимой области, его CSS остаётся в основном файле. EasyMDE и его CSS загружаются лениво вместе.

`src/js/motion.js` управляет паузой бегущей строки, пыли и автопрокрутки Swiper. Ключ браузерного хранилища - `imperial-motion-paused`; системное уменьшение движения имеет приоритет. `imperial_hero_autoplay_delay` задаёт минимум 5-30 секунд, по умолчанию 8. `main.js` увеличивает время конкретного слайда по объёму текста из расчёта 180 слов в минуту, с верхней границей 30 секунд.

`ResponsiveImageFormats` использует штатные фильтры редактора WordPress: при поддержке сервером производные размеры JPEG/PNG/WebP сохраняются в AVIF, оригиналы не заменяются. Качество AVIF ограничено значением 60 без повышения уже заданного меньшего значения. Явные преобразования других расширений сохраняют приоритет; существующие миниатюры сами не пересоздаются.

## PHP-кеш после обновления

Локальная конфигурация `docker/php-performance.ini` содержит `opcache.enable_file_override=1`, `opcache.revalidate_freq=60` и **`opcache.validate_timestamps=0`**. При таком режиме PHP не проверяет изменения исходников: после развёртывания PHP-кода обязательно перезапусти обслуживающий сайт PHP, в локальном Docker - контейнер WordPress. Одной пересборки Vite или обновления файлов недостаточно; значение `revalidate_freq` не включает проверку при `validate_timestamps=0`. Если используешь эту настройку на другом сервере, включи перезапуск PHP в его процедуру обновления.

## Переводы

Text Domain: `imperial-future`. Каталог: `theme/languages/`. Русский интерфейс является исходным; файлы POT/PO/MO включены. Для обновления шаблона можно использовать WP-CLI i18n:

```sh
wp i18n make-pot theme theme/languages/imperial-future.pot --domain=imperial-future --exclude=vendor,node_modules,assets/dist,tests
wp --path=/path/to/wordpress eval-file /path/to/repository/theme/tools/update-russian-translations.php --use-include
```

Инструмент сохраняет существующие русские переводы, добавляет новые исходные русские строки и проверяет обратное чтение каждой записи MO. Новые формы множественного числа требуют ручного перевода трёх форм. Для других языков обнови соответствующий PO и выполни `wp i18n make-mo theme/languages`. Проверяй строки PHP и клиентский словарь `FrontendStrings`, не правь только готовый JS.

## Справочник настроек

В версии 1.1.4 Customizer содержит 114 настроек темы, включая минимальный интервал смены hero-слайдов. Экспортёр читает определения `Customizer` и `FeatureCustomizer`, **не сохранённые theme_mod**, и заменяет динамические email/название сайта нейтральными подписями. Значения по умолчанию берутся из `ThemeOptions`. На тестовом WordPress с активной темой:

```sh
wp --path=/path/to/wordpress eval-file /path/to/repository/scripts/export-settings.php --use-include > /path/to/repository/output/settings-definitions.json
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
php wp-content/themes/imperial-future/tests/privacy-regressions.php
php wp-content/themes/imperial-future/tests/image-formats.php
php wp-content/themes/imperial-future/tests/embed-security.php
```

Проверки создают временные объекты, затрагивают настройки и перехватывают почту. **Не запускай их на производственной базе.** Проверяй синтаксис изменённых PHP-файлов, production-сборку, `npm audit` и `composer audit`. Тесты не входят в пользовательский ZIP.

`image-formats.php` проверяет приоритет форматов, качество AVIF, поведение без поддержки AVIF и сохранение аватара без изменения исходного файла. `embed-security.php` перехватывает HTTP без реальных сетевых запросов: проверяет публичные и относительные редиректы, блокировку непубличных адресов, циклы, лимит переходов и кеш карточек. Это описание проверок, а не утверждение об их прохождении в каждой установке.

Для UI проверь обычным браузером вход/выход, ошибки форм, смену пароля, EasyMDE «ВИД» и возврат, реакцию, закладку, копирование, галерею и кнопки клавиатуры. Проверь паузу/воспроизведение, сохранение выбора после перезагрузки, системное уменьшение движения и интервал hero. Для вёрстки - 320, 390, 768, 1200 и 1440 px, светлую и тёмную темы.

## Lighthouse в Docker

Действующий CLI-аудитор - `theme/tests/lighthouse-audit.mjs`, образ из `tools/lighthouse/Dockerfile` с Node.js 22, Chromium и фиксированным Lighthouse **13.5.0**. Старый `lighthouse-audit.cjs` не используй для новых замеров. Из корня репозитория в PowerShell, при работающем локальном WordPress:

```powershell
docker build --file tools/lighthouse/Dockerfile --tag imperial-lighthouse:13.5.0 .
New-Item -ItemType Directory -Force output/lighthouse | Out-Null
$reports = (Resolve-Path output/lighthouse).Path
docker run --rm --env AUDIT_URL=http://localhost:19080/ --env AUDIT_MODE=mobile --env AUDIT_LABEL=home-mobile --mount "type=bind,source=$reports,target=/reports" imperial-lighthouse:13.5.0
docker run --rm --env AUDIT_URL=http://localhost:19080/ --env AUDIT_MODE=desktop --env AUDIT_LABEL=home-desktop --mount "type=bind,source=$reports,target=/reports" imperial-lighthouse:13.5.0
```

`AUDIT_URL` принимает только `http://localhost` с нужным портом и путём; `AUDIT_MODE` - `mobile` или `desktop`. `AUDIT_LABEL` состоит из строчных латинских букв, цифр и дефисов, задаёт имена JSON/HTML в `/reports`; повтор с той же меткой перезаписывает отчёты. По умолчанию проверяется главная в мобильном профиле. Прокси внутри контейнера обращается к `host.docker.internal`, сохраняя localhost origin и Host WordPress. Мобильный профиль использует стандартную конфигурацию Lighthouse, десктопный - штатный `desktop-config.js`, без отключения функций сайта или изменения throttling.

Проверяются Performance, Accessibility, Best Practices и SEO; CLI выводит версию, адрес, профиль, оценки и метрики. Запускай замеры последовательно, без параллельной нагрузки, для главной, статьи, рубрики и контактов. Для повторов главной используй разные метки. Итоговые оценки и условия замеров сохраняй в [совместимости](COMPATIBILITY.md), а не подменяй их ожидаемыми значениями.

## Установочный архив

Из корня проекта, PowerShell 7.2+:

```powershell
./scripts/package-theme.ps1
Get-FileHash output/imperial-future-1.1.4.zip -Algorithm SHA256
```

Скрипт читает `theme/` напрямую, проверяет manifest, Composer, снимок и реестр лицензий, исключает секреты/тесты/временные файлы. В ZIP один верхний каталог `imperial-future/`. Установочные файлы не копируются во второй checkout. Порядок и даты записей фиксированы, ZIP без сжатия обеспечивает воспроизводимость в одной среде .NET/PowerShell. `.gitattributes` запрещает автоматическую замену переносов строк: тексты лицензий и lock-файлы должны сохранять байты, по которым проверяется SHA-256. Не меняй исходники одновременно с упаковкой.

При новой версии согласованно обнови заголовок `style.css`, package metadata, readme, документацию, упаковщик, CHANGELOG и имя артефакта. Загрузи ZIP и файл SHA-256 в GitHub Releases; автоматический Source code ZIP не заменяет установочный. Не публикуй `.env`, SQL, реальные OAuth/SMTP-ключи, тестовые дампы или личные снимки кабинета.
