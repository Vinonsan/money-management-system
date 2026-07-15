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
            ? 'text-white bg-primary-700 shadow-md shadow-primary-700/20'
            : 'text-slate-600 hover:text-primary-700 hover:bg-primary-50/50';
        $iconWrap = $active
            ? 'text-white'
            : 'text-slate-400 group-hover:text-primary-600';

        $activeIndicator = $active
            ? '<span class="absolute left-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-r-full bg-primary-400"></span>'
            : '';

        $labelQuoted = "'{$label}'";

        return <<<HTML
        <a href="{$href}"
           class="group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-all duration-200 {$linkClass}"
           :title="collapsed ? {$labelQuoted} : ''">
            {$activeIndicator}
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
            ? 'text-white'
            : 'text-slate-400 group-hover:text-primary-600';
        $btnClass = $parentActive
            ? 'text-white bg-primary-700 shadow-md shadow-primary-700/20'
            : 'text-slate-600 hover:text-primary-700 hover:bg-primary-50/50';

        $activeIndicator = $parentActive
            ? '<span class="absolute left-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-r-full bg-primary-400"></span>'
            : '';

        $gridClosedClass = "grid-rows-[0fr]";
        $gridOpenClass = "grid-rows-[1fr]";
        $keyJsQuoted = "'{$keyJs}'";
        $arrowColor = $parentActive ? 'text-primary-300' : 'text-slate-400';
        $borderColor = $parentActive ? 'border-primary-300' : 'border-slate-200';

        $childrenHtml = '';
        $flyoutChildren = '';
        foreach ($item['children'] as $childKey => $child) {
            $childActive = SidebarRouter::isChildActive($key, $childKey, $activeNav);
            $cLabel = htmlspecialchars($child['label'], ENT_QUOTES, 'UTF-8');
            $cHref = htmlspecialchars($child['href'], ENT_QUOTES, 'UTF-8');
            $cClass = $childActive
                ? 'text-primary-700 font-semibold bg-primary-700/10'
                : 'text-slate-500 hover:text-primary-700';
            $cDot = $childActive
                ? '<span class="ml-auto h-2 w-2 rounded-full bg-primary-600 ring-2 ring-primary-200"></span>'
                : '';
            $bulletClass = $childActive ? 'bg-primary-600' : 'bg-slate-300';

            $childrenHtml .= <<<HTML
            <a href="{$cHref}"
               class="group/child flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition-all duration-200 {$cClass}">
                <span class="h-1.5 w-1.5 shrink-0 rounded-full {$bulletClass}"></span>
                <span>{$cLabel}</span>
                {$cDot}
            </a>
            HTML;

            $fClass = $childActive
                ? 'text-primary-700 font-semibold'
                : 'text-slate-600 hover:text-primary-700';
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
                    @click="collapsed ? (flyout = !flyout) : toggleOpen({$keyJsQuoted})"
                    @mouseenter="if (collapsed) flyout = true"
                    :title="collapsed ? '{$label}' : ''"
                    :aria-expanded="isOpen({$keyJsQuoted})">
                {$activeIndicator}
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg transition {$iconWrap}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">{$item['icon']}</svg>
                </span>
                <span class="flex-1 truncate text-left transition-all duration-200"
                      :class="collapsed ? 'lg:hidden' : ''">{$label}</span>
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md transition-all duration-300 {$arrowColor}"
                      :class="[isOpen({$keyJsQuoted}) ? 'rotate-180' : '', collapsed ? 'lg:hidden' : '']">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </span>
            </button>

            <!-- Modern grid accordion (0fr → 1fr) -->
            <div class="grid transition-[grid-template-rows] duration-300 ease-out"
                 :class="(!collapsed && isOpen({$keyJsQuoted})) ? '{$gridOpenClass}' : '{$gridClosedClass}'">
                <div class="min-h-0 overflow-hidden">
                    <div class="relative mt-2 ml-4 space-y-1.5 border-l-2 {$borderColor} pl-3 pb-1.5">
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
