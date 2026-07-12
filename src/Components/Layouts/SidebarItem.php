<?php
namespace Components\Layouts;

/**
 * Renders one sidebar entry — leaf link or parent with modern accordion / flyout.
 */
final class SidebarItem
{
    public static function render(string $key, array $item, string $activeNav): string
    {
        if (SidebarRouter::hasChildren($item)) {
            return self::renderParent($key, $item, $activeNav);
        }

        return self::renderLeaf($key, $item, $activeNav);
    }

    private static function renderLeaf(string $key, array $item, string $activeNav): string
    {
        $active = $activeNav === $key;
        $label = htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');
        $href = htmlspecialchars((string) ($item['href'] ?? '#'), ENT_QUOTES, 'UTF-8');
        $linkClass = $active
            ? 'text-emerald-700 bg-emerald-50/50 ring-1 ring-emerald-200'
            : 'text-slate-700 hover:text-emerald-700';
        $iconWrap = $active
            ? 'bg-emerald-100 text-emerald-700'
            : 'text-slate-400 group-hover:text-emerald-600';

        return <<<HTML
        <a href="{$href}"
           class="group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-all duration-200 {$linkClass}"
           :title="collapsed ? '{$label}' : ''">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg transition {$iconWrap}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">{$item['icon']}</svg>
            </span>
            <span class="truncate transition-all duration-200"
                  :class="collapsed ? 'lg:opacity-0 lg:w-0 lg:overflow-hidden' : 'opacity-100'">
                {$label}
            </span>
        </a>
        HTML;
    }

    private static function renderParent(string $key, array $item, string $activeNav): string
    {
        $parentActive = SidebarRouter::isParentActive($key, $activeNav);
        $label = htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');
        $keyJs = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
        $iconWrap = $parentActive
            ? 'bg-emerald-100 text-emerald-700'
            : 'text-slate-400 group-hover:text-emerald-600';
        $btnClass = $parentActive
            ? 'text-emerald-700'
            : 'text-slate-700 hover:text-emerald-700';

        $childrenHtml = '';
        $flyoutChildren = '';
        foreach ($item['children'] as $childKey => $child) {
            $childActive = SidebarRouter::isChildActive($key, $childKey, $activeNav);
            $cLabel = htmlspecialchars($child['label'], ENT_QUOTES, 'UTF-8');
            $cHref = htmlspecialchars($child['href'], ENT_QUOTES, 'UTF-8');
            $cClass = $childActive
                ? 'text-emerald-700 font-semibold bg-emerald-50/50'
                : 'text-slate-600 hover:text-emerald-700';
            $cDot = $childActive
                ? '<span class="ml-auto h-1.5 w-1.5 rounded-full bg-emerald-500"></span>'
                : '';
            $bulletClass = $childActive ? 'bg-emerald-500' : 'bg-slate-300';

            $childrenHtml .= <<<HTML
            <a href="{$cHref}"
               class="group/child flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition-all duration-200 {$cClass}">
                <span class="h-1.5 w-1.5 shrink-0 rounded-full {$bulletClass}"></span>
                <span>{$cLabel}</span>
                {$cDot}
            </a>
            HTML;

            $fClass = $childActive
                ? 'text-emerald-700 font-semibold'
                : 'text-slate-600 hover:text-emerald-700';
            $flyoutChildren .= <<<HTML
            <a href="{$cHref}" class="block rounded-lg px-3 py-2 text-sm transition {$fClass}">{$cLabel}</a>
            HTML;
        }

        return <<<HTML
        <div class="relative"
             x-data="{ flyout: false }"
             @mouseleave="flyout = false">
            <button type="button"
                    class="group relative flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-all duration-200 {$btnClass}"
                    :class="collapsed ? 'lg:justify-center' : ''"
                    @click="collapsed ? (flyout = !flyout) : toggleOpen('{$keyJs}')"
                    @mouseenter="if (collapsed) flyout = true"
                    :title="collapsed ? '{$label}' : ''"
                    :aria-expanded="isOpen('{$keyJs}')">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg transition {$iconWrap}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">{$item['icon']}</svg>
                </span>
                <span class="flex-1 truncate text-left transition-all duration-200"
                      :class="collapsed ? 'lg:hidden' : ''">{$label}</span>
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-slate-400 transition-all duration-300"
                      :class="[isOpen('{$keyJs}') ? 'rotate-180 text-emerald-600' : '', collapsed ? 'lg:hidden' : '']">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </span>
            </button>

            <!-- Modern grid accordion (0fr → 1fr) -->
            <div class="grid transition-[grid-template-rows] duration-300 ease-out"
                 :class="(!collapsed && isOpen('{$keyJs}')) ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
                <div class="min-h-0 overflow-hidden">
                    <div class="relative mt-2 ml-4 space-y-1.5 border-l-2 border-slate-200 pl-3 pb-1.5">
                        {$childrenHtml}
                    </div>
                </div>
            </div>

            <!-- Collapsed flyout -->
            <div class="absolute left-full top-0 z-[60] ml-3 hidden w-52 rounded-xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-300/40 lg:block"
                 x-show="collapsed && flyout"
                 x-cloak
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-x-1"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
                <p class="mb-1 px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-400">{$label}</p>
                {$flyoutChildren}
            </div>
        </div>
        HTML;
    }
}
