<?php
namespace Components\Drawer;

/**
 * Slide-in Drawer component (add / edit / view).
 *
 * Usage in Alpine page:
 *   x-data="{ drawer: '', drawerMode: 'add', drawerData: {} }"
 *
 *   <!-- Open draw Add -->
 *   @click="drawerData = {}; drawerMode = 'add'; drawer = 'location-drawer'"
 *
 *   <!-- Open for Edit -->
 *   @click="drawerData = {id:1,name:'X',...}; drawerMode = 'edit'; drawer = 'location-drawer'"
 *
 *   <!-- Open for View -->
 *   @click="drawerData = {id:1,name:'X',...}; drawerMode = 'view'; drawer = 'location-drawer'"
 *
 * Usage:
 *   echo Drawer::render('location-drawer', 'Location', $formHtml, [
 *       'side' => 'right',
 *       'size' => 'md',   // sm | md | lg | full
 *   ]);
 *
 * Size variants:
 *   sm   → max-w-md    (≈ 28rem / 448px)
 *   md   → max-w-2xl   (≈ 42rem / 672px)
 *   lg   → 600px wide (up to 90vw on smaller screens)
 *   full → w-full      (full screen width)
 */
final class Drawer
{
    const SIZES = [
        'sm'   => 'w-full max-w-md',
        'md'   => 'w-full max-w-2xl',
        'lg'   => 'w-[600px] max-w-[90vw]',
        'full' => 'w-full',
    ];

    public static function render(
        string $id,
        string $title,
        string $body,
        array $opts = []
    ): string {
        $side = $opts['side'] ?? 'right';
        $size = $opts['size'] ?? 'md';
        $width = $opts['width'] ?? (self::SIZES[$size] ?? self::SIZES['md']);
        $footer = $opts['footer'] ?? '';

        $isRight = $side === 'right';
        $enterFrom = $isRight ? 'translate-x-full' : '-translate-x-full';
        $leaveTo = $isRight ? 'translate-x-full' : '-translate-x-full';

        return <<<HTML
<div id="{$id}"
     x-show="drawer === '{$id}'"
     x-cloak
     class="fixed inset-0 z-[9999]"
    >
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-[2px] transition-opacity"
         @click="drawer = ''"
         x-show="drawer === '{$id}'"
         x-transition:enter="transition-opacity duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
    </div>

    <!-- Panel -->
    <div class="fixed inset-y-0 {$side}-0 z-10 flex"
         x-show="drawer === '{$id}'"
         x-transition:enter="transition-transform duration-300 ease-out"
         x-transition:enter-start="{$enterFrom}"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition-transform duration-200 ease-in"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="{$leaveTo}">
        <div class="flex h-full {$width} flex-col bg-white shadow-2xl">
            <!-- Header -->
            <div class="flex shrink-0 items-center justify-between border-b border-slate-200 bg-white px-8 py-5">
                <h3 class="text-lg font-bold text-slate-900 truncate pr-4">
                    <span x-text="drawerMode === 'view' ? 'View' : (drawerMode === 'edit' ? 'Edit' : 'Add')"></span>
                    <span class="text-emerald-700">{$title}</span>
                </h3>
                <button type="button" @click="drawer = ''"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto bg-white px-8 py-6">
                {$body}
            </div>

            <!-- Footer -->
            {$footer}
        </div>
    </div>
</div>
HTML;
    }
}
