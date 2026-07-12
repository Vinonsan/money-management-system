<?php
namespace Components\Base;

/**
 * Reusable Button component with emerald theme variants, size, icon, and loading state.
 *
 * Usage:
 *   echo Button::render('Save', 'primary', ['type' => 'submit']);
 *   echo Button::render('Delete', 'danger', ['class' => 'ml-2']);
 *   echo Button::render('Edit', 'ghost', ['icon' => '<svg>...</svg>']);
 *   echo Button::render('Loading...', 'primary', ['loading' => true]);
 */
final class Button
{
    public static function render(string $label, string $variant = 'primary', array $attrs = []): string
    {
        $variants = [
            'primary'   => 'bg-emerald-700 text-white hover:bg-emerald-800 shadow-sm shadow-emerald-600/20',
            'secondary' => 'bg-slate-100 text-slate-700 hover:bg-slate-200 hover:text-slate-900',
            'danger'    => 'bg-red-600 text-white hover:bg-red-700 shadow-sm shadow-red-600/20',
            'ghost'     => 'bg-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-900',
            'outline'   => 'border border-emerald-600 text-emerald-700 hover:bg-emerald-50 bg-transparent',
            'success'   => 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm shadow-emerald-600/20',
            'warning'   => 'bg-amber-500 text-white hover:bg-amber-600 shadow-sm shadow-amber-500/20',
        ];

        $sizes = [
            'xs'     => 'px-2.5 py-1 text-xs',
            'sm'     => 'px-3 py-1.5 text-sm',
            'md'     => 'px-4 py-2 text-sm',
            'lg'     => 'px-5 py-2.5 text-base',
            'xl'     => 'px-6 py-3 text-base',
        ];

        $variantClass = $variants[$variant] ?? $variants['primary'];
        $size = $attrs['size'] ?? 'md';
        $sizeClass = $sizes[$size] ?? $sizes['md'];
        $loading = !empty($attrs['loading']);
        $icon = $attrs['icon'] ?? '';

        // Remove custom attrs that are not HTML attributes
        $customAttrs = $attrs['attrs'] ?? [];
        $extraClass = $attrs['class'] ?? '';
        unset($attrs['size'], $attrs['loading'], $attrs['icon'], $attrs['attrs'], $attrs['class']);

        $extra = '';
        foreach ($attrs as $key => $val) {
            $extra .= ' ' . $key . '="' . htmlspecialchars((string)$val, ENT_QUOTES) . '"';
        }
        foreach ($customAttrs as $key => $val) {
            $extra .= ' ' . $key . '="' . htmlspecialchars((string)$val, ENT_QUOTES) . '"';
        }

        $disabledClass = !empty($attrs['disabled']) ? ' opacity-60 cursor-not-allowed' : '';

        $iconHtml = '';
        if ($icon) {
            $iconHtml = '<span class="shrink-0">' . $icon . '</span>';
        }

        $spinner = '';
        if ($loading) {
            $spinner = <<<HTML
            <svg class="animate-spin h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            HTML;
        }

        return sprintf(
            '<button class="inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition %s %s%s %s"%s>%s%s%s</button>',
            $variantClass,
            $sizeClass,
            $disabledClass,
            $extraClass,
            $extra,
            $spinner,
            $iconHtml,
            htmlspecialchars($label, ENT_QUOTES)
        );
    }
}