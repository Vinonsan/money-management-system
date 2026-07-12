<?php
namespace Components\Base;

/**
 * Reusable Alpine-powered multi-select dropdown.
 *
 * Example:
 *   echo MultiSelect::render('location_ids', [
 *       'label' => 'Locations',
 *       'options' => [1 => 'Main Masjid', 2 => 'Branch Masjid'],
 *       'model' => 'drawerData.location_ids',
 *       'required' => true,
 *   ]);
 */
final class MultiSelect
{
    public static function render(string $name, array $opts = []): string
    {
        $label = $opts['label'] ?? '';
        $options = $opts['options'] ?? [];
        $model = $opts['model'] ?? '';
        $required = !empty($opts['required']);
        $help = $opts['help'] ?? '';
        $placeholder = $opts['placeholder'] ?? 'Select options...';

        if ($model === '') {
            throw new \InvalidArgumentException('MultiSelect requires an Alpine model.');
        }

        $id = 'multi-select-' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $labelHtml = $label === '' ? '' : '<label id="' . $id . '-label" class="mb-1.5 block text-sm font-semibold text-slate-700">'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
            . ($required ? ' <span class="text-red-500">*</span>' : '')
            . '</label>';

        $optionsHtml = '';
        foreach ($options as $value => $optionLabel) {
            $valueEsc = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
            $optionEsc = htmlspecialchars((string) $optionLabel, ENT_QUOTES, 'UTF-8');
            $optionsHtml .= <<<HTML
<label class="flex cursor-pointer items-center gap-3 px-3 py-2.5 text-sm text-slate-700 transition hover:bg-emerald-50">
    <input type="checkbox" value="{$valueEsc}" x-model="{$model}"
           class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
    <span class="flex-1">{$optionEsc}</span>
    <svg x-show="Array.isArray({$model}) && {$model}.map(String).includes('{$valueEsc}')" class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
    </svg>
</label>
HTML;
        }

        $placeholderEsc = htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8');
        $helpHtml = $help === '' ? '' : '<p class="mt-1 text-xs text-slate-400">' . htmlspecialchars($help, ENT_QUOTES, 'UTF-8') . '</p>';

        return <<<HTML
<div class="multi-select-component" x-data="{ open: false }" @keydown.escape.window="open = false">
    {$labelHtml}
    <div class="relative">
        <button id="{$id}" type="button" @click="open = !open" aria-haspopup="listbox" :aria-expanded="open"
            class="flex w-full items-center justify-between rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-left text-sm text-slate-800 transition focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/30">
            <span x-text="Array.isArray({$model}) && {$model}.length ? {$model}.length + ' location(s) selected' : '{$placeholderEsc}'"></span>
            <svg class="h-4 w-4 shrink-0 text-slate-400 transition" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-cloak x-show="open" @click.outside="open = false" x-transition
             class="absolute z-30 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg" role="listbox" aria-multiselectable="true">
            {$optionsHtml}
        </div>
    </div>
    {$helpHtml}
</div>
HTML;
    }
}
