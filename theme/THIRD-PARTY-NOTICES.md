# Сторонние компоненты Imperial Future

Собственный код темы распространяется по GPL-2.0-or-later, см. [LICENSE](LICENSE).
Автор темы: Кирилл Александров, https://kodalexandrova.ru/.
Это не меняет лицензии библиотек, шрифтов, значков и других сторонних материалов.
Весь JavaScript bundle не объявляется GPL-compatible: GSAP имеет отдельную
[Standard License](https://gsap.com/standard-license/).

## JavaScript, шрифты и значки

Полные тексты из установленных npm-пакетов сохранены в `licenses/npm/`.
Версии, исходные пути и SHA-256 перечислены в [реестре](licenses/index.json),
читаемый список - в [licenses/README.md](licenses/README.md).
Реестр включает установленные инструменты сборки; это не означает, что их
исполняемые файлы поставляются в ZIP. `node_modules` в архив не входит.

| Компонент | Назначение | Условия |
| --- | --- | --- |
| GSAP, ScrollTrigger | Анимации | Standard License, не MIT и не GPL |
| Swiper | Слайдеры | MIT |
| EasyMDE | Редактор комментариев | MIT |
| CodeMirror, Marked, codemirror-spell-checker | Зависимости EasyMDE | MIT |
| Typo.js | Зависимость проверки орфографии EasyMDE | BSD-3-Clause |
| Lucide Static | SVG-значки в `assets/dist/icons.svg` | ISC, часть значков имеет MIT-уведомление Feather |
| Inter, Oranienbaum, Forum, Prata, PT Sans, IBM Plex Sans | Локальные шрифты через Fontsource | SIL Open Font License 1.1 |

Полный файл лицензии Lucide сохранён вместе с исходным списком значков Feather
и уведомлением Cole Bemis. Имена правообладателей шрифтов, условия OFL и
зарезервированные имена сохранены в исходных файлах LICENSE каждого пакета.
Шрифты поставляются локально в `assets/dist/media`, без запросов Google Fonts.
Словари Typo.js и Font Awesome не поставляются; проверка орфографии Typo.js
и автоматическая загрузка Font Awesome в EasyMDE отключены в настройках темы.

В npm-пакетах GSAP 3.15.0 и Typo.js 1.3.2 нет полного текста лицензии.
Для них сохранены отдельные upstream-тексты:

- [GSAP Standard License](licenses/supplemental/gsap-3.15.0-Standard-License.txt):
  https://gsap.com/community/standard-license/; действует с 30 апреля 2025,
  изменение от 30 мая 2025. Условия отсылают также к
  [Webflow Terms of Service](https://webflow.com/legal/terms).
- [Typo.js BSD License](licenses/supplemental/typo-js-1.3.2-license.txt):
  https://raw.githubusercontent.com/cfinke/Typo.js/a08c1583d03a9e065d8240b7018beaaadc6f4ab7/license.txt.

Тексты закреплены для указанных версий. Сборщик не подменяет их новой
лицензией из сети автоматически. После изменения версии требуется проверка
нового пакета и актуальных upstream-условий.

## PHP и Composer

PHP-зависимости входят в ZIP вместе с `vendor/autoload.php` и собственными
лицензионными файлами. Точные версии закреплены в `composer.lock`.

| Пакет | Лицензия | Полный текст в архиве |
| --- | --- | --- |
| dflydev/dot-access-data | MIT | `vendor/dflydev/dot-access-data/LICENSE` |
| league/commonmark | BSD-3-Clause | `vendor/league/commonmark/LICENSE` |
| league/config | BSD-3-Clause | `vendor/league/config/LICENSE.md` |
| league/html-to-markdown | MIT | `vendor/league/html-to-markdown/LICENSE` |
| nette/schema | BSD-3-Clause или GPL-2.0/GPL-3.0 | `vendor/nette/schema/license.md` |
| nette/utils | BSD-3-Clause или GPL-2.0/GPL-3.0 | `vendor/nette/utils/license.md` |
| psr/event-dispatcher | MIT | `vendor/psr/event-dispatcher/LICENSE` |
| symfony/deprecation-contracts | MIT | `vendor/symfony/deprecation-contracts/LICENSE` |
| symfony/polyfill-php80 | MIT | `vendor/symfony/polyfill-php80/LICENSE` |
| Composer autoloader | MIT | `vendor/composer/LICENSE` |

Эти файлы сохраняются без замены лицензией темы. Для Nette исходный файл
содержит полный текст BSD и ссылки на альтернативные лицензии GPL.

## Изображения и социальные обозначения

Семь редакционных иллюстраций подготовлены для Imperial Future
и поставляются локально в `assets/images/editorial/` в формате WebP:
`hero`, `digital-workshop`, `home-laboratory`, `independent-archive`, `access-keys`,
`quiet-telegraph`, `readable-interface`. Это собственный стартовый визуальный
набор темы, а не фотографии из стороннего фотостока.
Бренд-монограмма «И» в светлой и тёмной версиях, SVG/PNG-значки сайта,
favicon и apple-touch-icon находятся в `assets/images/brand/`.
Растровая картинка для публикации ссылки - `assets/images/og-imperial.png`.
Исходные SVG демоизображений также могут формироваться кодом темы локально.
Собственные графические материалы предоставляются в составе темы на условиях
её лицензии, если рядом с конкретным файлом не указаны иные условия.
Это не меняет лицензии сторонних библиотек и шрифтов и не предоставляет
прав на сторонние товарные знаки.

Значки социальных сетей в `assets/icons/social/` служат обозначением сервисов.
Права на названия и товарные знаки принадлежат соответствующим владельцам;
лицензия темы не предоставляет прав на их бренды и не означает одобрение темы.
Отдельные сведения об upstream-источнике этих социальных SVG в проекте
не зафиксированы. Перед отдельным распространением этих значков нужно
уточнить происхождение и правила использования брендов; GPL-совместимость
этих обозначений здесь не заявляется.

## Обновление реестра

Из корня проекта: `docker compose exec -T node node tools/collect-licenses.mjs`.
Скрипт читает установленный `node_modules` и `package-lock.json`, копирует
лицензии побайтно и пишет стабильный реестр без текущих дат и абсолютных путей.
Для платформенных пакетов esbuild и Rollup используются лицензии из их
основных npm-пакетов; исходные пути явно записаны в реестре.
Composer-лицензии проверяются, но остаются на месте в `vendor`.
