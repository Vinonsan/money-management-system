<?php
namespace Components\Base;

/**
 * Reusable Input component with label, error, and icon support.
 *
 * Usage:
 *   echo Input::render('name', [
 *       'label'       => 'Full Name',
 *       'value'       => old('name'),
 *       'placeholder' => 'Enter your name',
 *       'type'        => 'text',           // text | email | tel | password | number | date
 *       'required'    => true,
 *       'disabled'    => false,
 *       'readonly'    => false,
 *       'error'       => $errors['name'] ?? '',
 *       'help'        => 'Enter your first and last name.',
 *       'icon'        => '<svg>...</svg>',  // optional leading icon
 *       'class'       => '',                // extra wrapper classes
 *       'attrs'       => ['maxlength' => 100, 'autofocus' => true],
 *   ]);
 */
final class Input
{
    public static function render(string $name, array $opts = []): string
    {
        $label     = $opts['label'] ?? '';
        $value     = $opts['value'] ?? '';
        $placeholder = $opts['placeholder'] ?? '';
        $type      = $opts['type'] ?? 'text';
        $required  = !empty($opts['required']);
        $disabled  = !empty($opts['disabled']);
        $readonly  = !empty($opts['readonly']);
        $error     = $opts['error'] ?? '';
        $help      = $opts['help'] ?? '';
        $icon      = $opts['icon'] ?? '';
        $class     = $opts['class'] ?? '';
        $attrs     = $opts['attrs'] ?? [];

        $hasError = $error !== '';
        $nameEsc = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $id = 'input-' . $nameEsc;

        // ─── Build input attributes ─────────────────────────────────────
        $inputAttrs = [
            'type'        => htmlspecialchars($type, ENT_QUOTES),
            'name'        => $nameEsc,
            'id'          => $id,
            'value'       => htmlspecialchars((string)$value, ENT_QUOTES),
            'placeholder' => htmlspecialchars($placeholder, ENT_QUOTES),
            'class'       => 'w-full rounded-lg border bg-primary-50/30 px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 transition focus:outline-none focus:ring-2 focus:bg-white',
        ];

        if ($required) {
            $inputAttrs['required'] = 'required';
        }
        if ($disabled) {
            $inputAttrs['disabled'] = 'disabled';
            $inputAttrs['class'] .= ' opacity-60 cursor-not-allowed';
        }
        if ($readonly) {
            $inputAttrs['readonly'] = 'readonly';
        }
        if ($hasError) {
            $inputAttrs['class'] .= ' border-red-300 focus:border-red-500 focus:ring-red-500/30';
        } else {
            $inputAttrs['class'] .= ' border-primary-200 focus:border-primary-600 focus:ring-primary-600/30';
        }
        if ($icon) {
            $inputAttrs['class'] .= ' pl-10';
        }

        foreach ($attrs as $k => $v) {
            $inputAttrs[htmlspecialchars((string)$k, ENT_QUOTES)] = htmlspecialchars((string)$v, ENT_QUOTES);
        }

        $attrStr = '';
        foreach ($inputAttrs as $k => $v) {
            $attrStr .= ' ' . $k . '="' . $v . '"';
        }

        // ─── Label ──────────────────────────────────────────────────────
        $labelHtml = '';
        if ($label !== '') {
            $reqBadge = $required
                ? ' <span class="text-red-500">*</span>'
                : '';
            $labelHtml = <<<HTML
            <label for="{$id}" class="block mb-1.5 text-sm font-semibold text-primary-800">{$label}{$reqBadge}</label>
            HTML;
        }

        // ─── Icon ───────────────────────────────────────────────────────
        $iconHtml = $icon
            ? '<div class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">' . $icon . '</div>'
            : '';

        // ─── Error & Help ───────────────────────────────────────────────
        $errorHtml = $hasError
            ? '<p class="mt-1 text-xs font-medium text-red-600">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</p>'
            : '';
        $helpHtml = $help && !$hasError
            ? '<p class="mt-1 text-xs text-primary-400">' . htmlspecialchars($help, ENT_QUOTES, 'UTF-8') . '</p>'
            : '';

        $wrapperClass = trim('input-component ' . $class);

        return <<<HTML
        <div class="{$wrapperClass}">
            {$labelHtml}
            <div class="relative">
                {$iconHtml}
                <input{$attrStr}>
            </div>
            {$errorHtml}
            {$helpHtml}
        </div>
        HTML;
    }
}
