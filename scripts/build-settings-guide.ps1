#Requires -Version 7.2
[CmdletBinding()]
param([string] $DefinitionsPath = (Join-Path $PSScriptRoot '../output/settings-definitions.json'))

$ErrorActionPreference = 'Stop'
$sections = Get-Content -LiteralPath $DefinitionsPath -Raw | ConvertFrom-Json
$lines = [Collections.Generic.List[string]]::new()
$lines.Add('# Все настройки Imperial Future 1.1.3')
$lines.Add('')
$lines.Add('Путь: **Внешний вид -> Настроить -> Imperial Future: настройки темы**. Ниже перечислены все 114 зарегистрированных полей в 13 разделах. Это исходные значения темы, не настройки или секреты конкретного сайта. Импорт демо может изменить часть оформления.')
$lines.Add('')
$lines.Add('Справочник сформирован из определений WordPress Customizer, без чтения сохранённых значений. «Включено/выключено» относится к переключателю; «пусто» означает отсутствие своего значения. Изображения выбираются из медиатеки и хранятся как ID. Текст кнопки и ссылка - разные поля. После изменения нажми «Опубликовать» в Customizer.')
$lines.Add('')
$lines.Add('Практические пояснения, подключение внешних сервисов и стандартные настройки WordPress: [установка](INSTALLATION.md), [возможности](FEATURES.md).')
$lines.Add('')
$types = @{ checkbox='Переключатель'; text='Текст'; textarea='Многострочный текст / код'; email='Email'; number='Число'; select='Выбор'; color='Цвет'; media='Изображение' }

function ConvertTo-TableText([object] $Value) {
    return [Net.WebUtility]::HtmlEncode([string]$Value).Replace('|', '&#124;').Replace("`r", '').Replace("`n", '<br>')
}

foreach ($section in $sections) {
    $lines.Add("## $($section.title)")
    $lines.Add('')
    $lines.Add('| Поле и ключ | Тип / варианты | По умолчанию |')
    $lines.Add('| --- | --- | --- |')
    foreach ($control in $section.controls) {
        $kind = $types[$control.type]
        if (-not $kind) { $kind = $control.type }
        $choices = @($control.choices.PSObject.Properties | Where-Object MemberType -eq NoteProperty)
        if ($choices.Count -gt 0) {
            $kind += ': ' + (($choices | ForEach-Object { "$($_.Value) [$($_.Name)]" }) -join '; ')
        }
        if ($control.type -eq 'number') {
            $kind += " ($($control.input_attrs.min)-$($control.input_attrs.max))"
        }
        $default = [string]$control.default
        if ($control.type -eq 'checkbox') { $default = if ($default -eq '1') { 'Включено' } else { 'Выключено' } }
        elseif ([string]::IsNullOrEmpty($default)) { $default = 'Пусто' }
        $label = ConvertTo-TableText $control.label
        $lines.Add('| ' + $label + '<br>`' + $control.id + '` | ' + (ConvertTo-TableText $kind) + ' | ' + (ConvertTo-TableText $default) + ' |')
    }
    $lines.Add('')
}
$lines.Add('## Стандартные настройки WordPress')
$lines.Add('')
$lines.Add('- **Название, краткое описание, язык, часовой пояс, email администратора:** Настройки -> Общие. Для русских дат выбери ru_RU и свой часовой пояс.')
$lines.Add('- **Регистрация:** Настройки -> Общие -> Членство. Роль нового пользователя - Подписчик. Переключатель кабинета сам по себе регистрацию не разрешает.')
$lines.Add('- **Значок сайта:** стандартный «Значок сайта» WordPress имеет приоритет над favicon темы. Рекомендуется квадратное изображение 512x512.')
$lines.Add('- **Обсуждение:** комментарии, модерация, вложенность и уведомления администратору задаются в Настройки -> Обсуждение, а также у каждой записи.')
$lines.Add('- **Главная и индексация:** Настройки -> Чтение. Перед публичным запуском отключи запрет индексации поисковиками; на тестовом сайте оставь его включённым.')
$lines.Add('- **URL:** Настройки -> Постоянные ссылки. Установи желаемую структуру до публикации, старые адреса меняй только с редиректами.')
$lines.Add('- **Меню и виджеты:** Внешний вид -> Меню / Виджеты. Доступны два меню и три области, перечисленные в справочнике возможностей.')
$lines.Add('- **Избранное hero:** закрепи нужные записи штатным флажком WordPress. Без закреплённых слайдер использует свежие публикации.')
$lines.Add('')
$lines.Add('## Что не настраивается отдельным полем')
$lines.Add('')
$lines.Add('SMTP-host/порт/пароль, ручной crop аватара, собственная схема CAPTCHA, время отправки дайджеста, отдельный выключатель каждого стандартного Gutenberg-блока, число символов старта поиска и произвольные новые позиции виджетов не имеют отдельных полей Customizer. SMTP подключается сервером/плагином; остальные изменения требуют разработки. Не все элементы страницы являются отдельными настройками.')
$target = Join-Path $PSScriptRoot '../docs/SETTINGS.md'
[IO.File]::WriteAllText($target, ($lines -join "`n") + "`n", [Text.UTF8Encoding]::new($false))
"Справочник: $($sections.Count) разделов, $(($sections.controls | Measure-Object).Count) полей."
