<?php
namespace Components\Modal;

/**
 * Confirmation / alert Modal component.
 *
 * Usage in Alpine page:
 *   x-data="{ modal: '' }"
 *
 *   <!-- Open delete confirm -->
 *   @click="modal = 'delete-location'; modalData = {id:1,name:'X'}"
 *
 * Usage:
 *   echo Modal::confirm('delete-location', 'Delete Location', [
 *       'body' => 'Are you sure you want to delete <strong>{name}</strong>?',
 *       'confirmText' => 'Delete',
 *       'confirmVariant' => 'danger',
 *       'size' => 'sm',
 *   ]);
 */
final class Modal
{
    /**
     * Render a confirmation modal.
     */
    public static function confirm(string $id, string $title, array $opts = []): string
    {
        $body = $opts['body'] ?? 'Are you sure?';
        $confirmText = $opts['confirmText'] ?? 'Confirm';
        $cancelText = $opts['cancelText'] ?? 'Cancel';
        $confirmVariant = $opts['confirmVariant'] ?? 'danger';
        $size = $opts['size'] ?? 'sm';
        $confirmAction = $opts['confirmAction'] ?? '';

        $width = match ($size) {
            'sm' => 'max-w-md',
            'lg' => 'max-w-3xl',
            'xl' => 'max-w-5xl',
            default => 'max-w-lg',
        };

        $btnVariant = match ($confirmVariant) {
            'danger'  => 'bg-red-600 text-white hover:bg-red-700',
            'primary' => 'bg-primary-700 text-white hover:bg-primary-800',
            'warning' => 'bg-amber-500 text-white hover:bg-amber-600',
            default   => 'bg-red-600 text-white hover:bg-red-700',
        };

        $attrs = $confirmAction
            ? ' @click="' . htmlspecialchars($confirmAction, ENT_QUOTES) . '"'
            : '';

        return <<<HTML
<div id="{$id}"
     x-show="modal === '{$id}'"
     x-cloak
     class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
    >
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"
         @click="modal = ''"
         x-show="modal === '{$id}'"
         x-transition:enter="transition-opacity duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
    </div>

    <!-- Panel -->
    <div class="relative {$width} w-full"
         x-show="modal === '{$id}'"
         x-transition:enter="transition-all duration-200 ease-out"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition-all duration-150 ease-in"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <div class="overflow-hidden rounded-2xl bg-white shadow-2xl">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-bold text-slate-900">{$title}</h3>
                <button type="button" @click="modal = ''"
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="px-6 py-5 text-sm text-slate-600 leading-relaxed">
                {$body}
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-3 border-t border-slate-100 px-6 py-4">
                <button type="button" @click="modal = ''"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    {$cancelText}
                </button>
                <button type="button"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition {$btnVariant}"{$attrs}>
                    {$confirmText}
                </button>
            </div>
        </div>
    </div>
</div>
HTML;
    }
}