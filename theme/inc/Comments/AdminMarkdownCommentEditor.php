<?php
declare(strict_types=1);

namespace ImperialFuture\Comments;

if (! defined('ABSPATH')) {
    exit;
}

final class AdminMarkdownCommentEditor
{
    public static function registerHooks(): void
    {
        add_action('admin_enqueue_scripts', [self::class, 'enqueue']);
    }

    public static function enqueue(string $hookSuffix): void
    {
        if ($hookSuffix !== 'comment.php') {
            return;
        }

        wp_enqueue_script('quicktags');
        wp_add_inline_script('quicktags', self::script(), 'after');

        wp_register_style('imperial-admin-comment-md', false, [], (string) wp_get_theme()->get('Version'));
        wp_enqueue_style('imperial-admin-comment-md');
        wp_add_inline_style('imperial-admin-comment-md', self::style());
    }

    private static function script(): string
    {
        $labels = wp_json_encode([
            'boldLabel' => __('Ж', 'imperial-future'),
            'boldTitle' => __('Жирный: **текст**', 'imperial-future'),
            'italicLabel' => __('К', 'imperial-future'),
            'italicTitle' => __('Курсив: *текст*', 'imperial-future'),
            'linkTitle' => __('Ссылка: [текст](url)', 'imperial-future'),
            'quoteLabel' => __('Цитата', 'imperial-future'),
            'quoteTitle' => __('Цитата: > текст', 'imperial-future'),
            'unorderedLabel' => __('• список', 'imperial-future'),
            'unorderedTitle' => __('Маркированный список', 'imperial-future'),
            'orderedLabel' => __('1. список', 'imperial-future'),
            'orderedTitle' => __('Нумерованный список', 'imperial-future'),
            'codeTitle' => __('Код: `код` или ```блок```', 'imperial-future'),
            'hint' => __('Комментарий сохраняется в Markdown: **жирный**, *курсив*, [ссылка](https://example.com), > цитата, списки и `код`. HTML-теги больше не вставляются.', 'imperial-future'),
            'linkPrompt' => __('URL ссылки', 'imperial-future'),
            'linkPlaceholder' => __('текст ссылки', 'imperial-future'),
            'quotePlaceholder' => __('цитата', 'imperial-future'),
            'codePlaceholder' => __('код', 'imperial-future'),
            'textPlaceholder' => __('текст', 'imperial-future'),
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        return "(() => {\n  const labels = " . $labels . ";\n" . <<<'JS'
  const actions = [
    { label: labels.boldLabel, title: labels.boldTitle, action: 'bold' },
    { label: labels.italicLabel, title: labels.italicTitle, action: 'italic' },
    { label: 'URL', title: labels.linkTitle, action: 'link' },
    { label: labels.quoteLabel, title: labels.quoteTitle, action: 'quote' },
    { label: labels.unorderedLabel, title: labels.unorderedTitle, action: 'ul' },
    { label: labels.orderedLabel, title: labels.orderedTitle, action: 'ol' },
    { label: '{}', title: labels.codeTitle, action: 'code' }
  ];

  const setup = () => {
    const textarea = document.getElementById('content');
    const toolbar = document.getElementById('qt_content_toolbar');

    if (!textarea || !toolbar) {
      return;
    }

    if (isMarkdownToolbar(toolbar)) {
      ensureHint(toolbar);
      ensureObserver(toolbar);
      return;
    }

    toolbar.classList.add('imperial-admin-md-toolbar');

    const fragment = document.createDocumentFragment();

    actions.forEach((item) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'button button-small imperial-admin-md-toolbar__button';
      button.dataset.imperialMarkdownAction = item.action;
      button.textContent = item.label;
      button.title = item.title;
      button.addEventListener('click', () => applyAction(textarea, item.action));
      fragment.appendChild(button);
    });

    toolbar.replaceChildren(fragment);
    toolbar.dataset.imperialMarkdownReady = '1';
    ensureHint(toolbar);
    ensureObserver(toolbar);
  };

  const isMarkdownToolbar = (toolbar) => {
    const buttons = Array.from(toolbar.querySelectorAll('button'));

    return buttons.length === actions.length
      && buttons.every((button, index) => button.dataset.imperialMarkdownAction === actions[index].action);
  };

  const ensureHint = (toolbar) => {
    const existingHint = toolbar.parentElement?.querySelector('.imperial-admin-md-toolbar__hint');

    if (existingHint) {
      return;
    }

    const hint = document.createElement('p');
    hint.className = 'description imperial-admin-md-toolbar__hint';
    hint.textContent = labels.hint;
    toolbar.insertAdjacentElement('afterend', hint);
  };

  const ensureObserver = (toolbar) => {
    if (window.imperialMarkdownCommentObserver) {
      return;
    }

    let replacing = false;
    window.imperialMarkdownCommentObserver = new MutationObserver(() => {
      if (replacing || isMarkdownToolbar(toolbar)) {
        return;
      }

      replacing = true;
      window.setTimeout(() => {
        toolbar.dataset.imperialMarkdownReady = '0';
        setup();
        replacing = false;
      }, 0);
    });
    window.imperialMarkdownCommentObserver.observe(toolbar, { childList: true, subtree: true });
  };

  const applyAction = (textarea, action) => {
    const start = textarea.selectionStart || 0;
    const end = textarea.selectionEnd || 0;
    const value = textarea.value;
    const selected = value.slice(start, end);
    const fallback = selected || placeholder(action);
    let replacement = fallback;
    let selectOffset = 0;

    if (action === 'bold') {
      replacement = `**${fallback}**`;
      selectOffset = 2;
    } else if (action === 'italic') {
      replacement = `*${fallback}*`;
      selectOffset = 1;
    } else if (action === 'link') {
      const url = window.prompt(labels.linkPrompt, 'https://');
      if (!url) {
        return;
      }
      replacement = `[${fallback}](${url})`;
      selectOffset = 1;
    } else if (action === 'quote') {
      replacement = prefixLines(fallback, '> ');
    } else if (action === 'ul') {
      replacement = prefixLines(fallback, '- ');
    } else if (action === 'ol') {
      replacement = prefixNumberedLines(fallback);
    } else if (action === 'code') {
      replacement = fallback.includes('\n') ? `\`\`\`\n${fallback}\n\`\`\`` : `\`${fallback}\``;
      selectOffset = fallback.includes('\n') ? 4 : 1;
    }

    textarea.value = `${value.slice(0, start)}${replacement}${value.slice(end)}`;
    textarea.focus();
    textarea.setSelectionRange(start + selectOffset, start + selectOffset + fallback.length);
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
  };

  const placeholder = (action) => {
    if (action === 'link') return labels.linkPlaceholder;
    if (action === 'quote') return labels.quotePlaceholder;
    if (action === 'code') return labels.codePlaceholder;

    return labels.textPlaceholder;
  };

  const prefixLines = (text, prefix) => text
    .split('\n')
    .map((line) => line.trim() === '' ? line : `${prefix}${line}`)
    .join('\n');

  const prefixNumberedLines = (text) => text
    .split('\n')
    .map((line, index) => line.trim() === '' ? line : `${index + 1}. ${line}`)
    .join('\n');

  document.addEventListener('DOMContentLoaded', setup);
  window.addEventListener('load', setup);
  [0, 75, 250, 600, 1200].forEach((delay) => window.setTimeout(setup, delay));
})();
JS;
    }

    private static function style(): string
    {
        return <<<'CSS'
.imperial-admin-md-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  padding: 8px 6px !important;
}

.imperial-admin-md-toolbar__button {
  min-height: 30px;
  border-color: #b78a22 !important;
  color: #2a1f18 !important;
  font-weight: 700;
}

.imperial-admin-md-toolbar__hint {
  margin: 4px 0 8px;
  color: #5f421a;
}
CSS;
    }
}
