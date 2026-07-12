<?php
namespace Components\Base;

/**
 * Reusable Select (dropdown) component with label, error, and option groups.
 *
 * Usage:
 *   echo Select::render('role', [
 *       'label'    => 'Role',
 *       'options'  => ['' => 'Select...', 'admin' => 'Admin', 'collector' => 'Collector'],
 *       'value'    => old('role'),
 *       'required' => true,
 *       'error'    => $errors['role'] ?? '',
 *       'help'     => 'Choose a role for this user.',
 *       'disabled' => false,
 *       'multiple' => false,              // true for a multi-select list
 *       'placeholder' => 'Select an option',
 *       'class'    => '',
 *       'attrs'    => ['data-role' => 'user'],
 *   ]);
 *
 * For optgroups:
 *   'options' => [
 *       'Admins' => ['admin_1' => 'Admin One', 'admin_2' => 'Admin Two'],
 *       'Staff'  => ['staff_1' => 'Staff One'],
 *   ],
 */
final class Select
{
    public static function render(string $name, array $opts = []): string
    {
        $label      = $opts['label'] ?? '';
        $options    = $opts['options'] ?? [];
        $value      = $opts['value'] ?? '';
        $required   = !empty($opts['required']);
        $error      = $opts['error'] ?? '';
        $help       = $opts['help'] ?? '';
        $disabled   = !empty($opts['disabled']);
        $multiple   = !empty($opts['multiple']);
        $placeholder = $opts['placeholder'] ?? '';
        $class      = $opts['class'] ?? '';
        $attrs      = $opts['attrs'] ?? [];

        $hasError = $error !== '';
        $nameEsc = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $id = 'select-' . $nameEsc;

        // ─── Build select attributes ────────────────────────────────────
        $selectAttrs = [
            'name' => $nameEsc,
            'id'   => $id,
            'class' => 'w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-slate-800 transition focus:outline-none focus:ring-2 appearance-none',
        ];

        if ($required) {
            $selectAttrs['required'] = 'required';
        }
        if ($disabled) {
            $selectAttrs['disabled'] = 'disabled';
            $selectAttrs['class'] .= ' opacity-60 cursor-not-allowed';
        }
        if ($multiple) {
            $selectAttrs['multiple'] = 'multiple';
            $selectAttrs['class'] .= ' min-h-36';
        }
        if ($hasError) {
            $selectAttrs['class'] .= ' border-red-300 focus:border-red-500 focus:ring-red-500/30';
        } else {
            $selectAttrs['class'] .= ' border-slate-300 focus:border-emerald-600 focus:ring-emerald-600/30';
        }

        foreach ($attrs as $k => $v) {
            $selectAttrs[htmlspecialchars((string)$k, ENT_QUOTES)] = htmlspecialchars((string)$v, ENT_QUOTES);
        }

        $attrStr = '';
        foreach ($selectAttrs as $k => $v) {
            $attrStr .= ' ' . $k . '="' . $v . '"';
        }

        // ─── Build options ─────────────────────────────────────────────
        $optionsHtml = self::buildOptions($options, $value, $placeholder, $multiple);

        // ─── Label ──────────────────────────────────────────────────────
        $labelHtml = '';
        if ($label !== '') {
            $reqBadge = $required
                ? ' <span class="text-red-500">*</span>'
                : '';
            $labelHtml = <<<HTML
            <label for="{$id}" class="block mb-1.5 text-sm font-semibold text-slate-700">{$label}{$reqBadge}</label>
            HTML;
        }

        // ─── Chevron icon ───────────────────────────────────────────────
        $chevron = <<<HTML
        <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
        HTML;

        // ─── Error & Help ───────────────────────────────────────────────
        $errorHtml = $hasError
            ? '<p class="mt-1 text-xs font-medium text-red-600">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</p>'
            : '';
        $helpHtml = $help && !$hasError
            ? '<p class="mt-1 text-xs text-slate-400">' . htmlspecialchars($help, ENT_QUOTES, 'UTF-8') . '</p>'
            : '';

        $wrapperClass = trim('select-component ' . $class);

        return <<<HTML
        <div class="{$wrapperClass}">
            {$labelHtml}
            <div class="relative">
                <select{$attrStr}>{$optionsHtml}</select>
                {$chevron}
            </div>
            {$errorHtml}
            {$helpHtml}
        </div>
        HTML;
    }

    /**
     * Recursively build <option> / <optgroup> HTML.
     */
    private static function buildOptions(array $options, mixed $value, string $placeholder, bool $multiple = false): string
    {
        $html = '';

        if (!$multiple && $placeholder !== '') {
            $sel = $value === '' ? ' selected' : '';
            $html .= '<option value=""' . $sel . ' disabled>' . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . '</option>';
        }

        foreach ($options as $key => $opt) {
            if (is_array($opt)) {
                // Optgroup
                $groupLabel = htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8');
                $children = self::buildFlatOptions($opt, $value);
                $html .= '<optgroup label="' . $groupLabel . '">' . $children . '</optgroup>';
            } else {
                $val = (string)$key;
                $selected = self::isSelected($value, $val) ? ' selected' : '';
                $html .= '<option value="' . htmlspecialchars($val, ENT_QUOTES) . '"' . $selected . '>'
                       . htmlspecialchars((string)$opt, ENT_QUOTES, 'UTF-8') . '</option>';
            }
        }

        return $html;
    }

    private static function buildFlatOptions(array $options, mixed $value): string
    {
        $html = '';
        foreach ($options as $key => $label) {
            $val = (string)$key;
            $selected = self::isSelected($value, $val) ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars($val, ENT_QUOTES) . '"' . $selected . '>'
                   . htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') . '</option>';
        }
        return $html;
    }

    private static function isSelected(mixed $value, string $optionValue): bool
    {
        if (is_array($value)) {
            return in_array($optionValue, array_map('strval', $value), true);
        }

        return $value !== '' && (string) $value === $optionValue;
    }
}
