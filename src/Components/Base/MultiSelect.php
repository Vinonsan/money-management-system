<?php
namespace Components\Base;

/**
 * Dropdown-style Multi-Select component with checkboxes.
 * Uses Alpine.js for interactivity.
 *
 * Usage:
 *   echo MultiSelect::render('location_ids', [
 *       'label'       => 'Locations',
 *       'options'     => [1 => 'Main Masjid', 2 => 'Branch Masjid'],
 *       'model'       => 'drawerData.location_ids',
 *       'placeholder' => 'Select locations...',
 *       'required'    => true,
 *       'help'        => 'Choose one or more locations.',
 *   ]);
 */
final class MultiSelect
{
    public static function render(string $name, array $opts = []): string
    {
        $label       = $opts['label'] ?? '';
        $options     = $opts['options'] ?? [];
        $model       = $opts['model'] ?? $name;
        $placeholder = htmlspecialchars($opts['placeholder'] ?? 'Select...', ENT_QUOTES, 'UTF-8');
        $required    = !empty($opts['required']);
        $help        = htmlspecialchars($opts['help'] ?? '', ENT_QUOTES, 'UTF-8');
        $disabled    = !empty($opts['disabled']);
        $nameEsc     = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $id          = 'ms-' . $nameEsc;

        $reqBadge = $required ? ' <span class="text-rose-500">*</span>' : '';

        $optionsHtml = '';
        foreach ($options as $key => $optLabel) {
            $val     = htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8');
            $optName = htmlspecialchars((string) $optLabel, ENT_QUOTES, 'UTF-8');
            $optionsHtml .= <<<OPT
            <label class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 hover:bg-emerald-50 cursor-pointer transition rounded-md">
                <input type="checkbox" value="{$val}" x-model="{$model}"
                    class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" style="accent-color: #059669;">
                <span>{$optName}</span>
            </label>
OPT;
        }

        $disabledAttr = $disabled ? 'disabled' : '';
        $helpHtml = $help !== '' ? '<p class="mt-1 text-xs text-slate-400">' . $help . '</p>' : '';

        return <<<HTML
        <div class="multi-select-component" x-data="multiSelect('{$id}', '{$placeholder}')">
            <label class="block mb-1.5 text-sm font-semibold text-slate-700">{$label}{$reqBadge}</label>

            <div class="relative">
                <button type="button"
                    id="{$id}"
                    {$disabledAttr}
                    @click="toggle()"
                    @keydown.escape="open = false"
                    class="w-full flex items-center justify-between gap-2 rounded-lg border bg-white px-3 py-2.5 text-sm text-left transition focus:outline-none focus:ring-2 border-slate-300 focus:border-emerald-600 focus:ring-emerald-600/30">

                    <span class="truncate flex-1" x-text="selectedText"
                          :class="selectedText === placeholder ? 'text-slate-400' : 'text-slate-800'"></span>

                    <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200"
                         :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="open" x-cloak
                    @click.away="open = false"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="absolute z-50 mt-1 w-full rounded-xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-300/30 max-h-56 overflow-y-auto">
                    <div class="space-y-0.5">
                        {$optionsHtml}
                    </div>
                </div>
            </div>

            {$helpHtml}
        </div>
        HTML;
    }
}
