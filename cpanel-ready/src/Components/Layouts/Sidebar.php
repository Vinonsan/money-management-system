<?php
namespace Components\Layouts;

/**
 * Admin sidebar shell — higher visual weight + collapse on navbar border.
 */
final class Sidebar
{
    public static function href(string $key): string
    {
        return SidebarRouter::href($key);
    }

    public static function render(string $activeNav = ''): string
    {
        $appName = APP_NAME;
        $logoSrc = BASE_URL . '/public/assets/img/logo.png';

        $itemsHtml = '';
        $currentRole = $_SESSION['user_role'] ?? '';
        foreach (SidebarRouter::routes() as $key => $item) {
            // Skip items that require a specific role
            if (!empty($item['role']) && $item['role'] !== $currentRole) {
                continue;
            }
            // Super admin should only see super_admin-items, not regular admin menu
            if ($currentRole === 'super_admin' && empty($item['role'])) {
                continue;
            }
            // Premium divider before super admin section
            if ($key === 'super_admin') {
                $itemsHtml .= <<<HTML
                <div class="my-3 border-t border-slate-200" 
                     :class="collapsed ? 'lg:opacity-0 lg:my-1' : ''">
                    <div class="mt-1.5 mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400"
                         :class="collapsed ? 'lg:hidden' : ''">
                        <span class="flex items-center gap-1.5">
                            <span class="h-1.5 w-1.5 rounded-full bg-primary-500"></span>
                            Privileged
                        </span>
                    </div>
                </div>
                HTML;
            }
            $itemsHtml .= SidebarItem::render($key, $item, $activeNav);
        }

        return <<<HTML
        <div
            x-show="mobileOpen"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-40 bg-white/80 backdrop-blur-[2px] lg:hidden"
            @click="mobileOpen = false"
        ></div>

        <aside
            class="fixed inset-y-0 left-0 z-50 flex flex-col border-r border-slate-300/90 bg-white shadow-[4px_0_24px_-4px_rgba(15,23,42,0.12)] transition-all duration-300 ease-out lg:static lg:translate-x-0"
            :class="[
                collapsed ? 'lg:w-[4.5rem]' : 'lg:w-72',
                mobileOpen ? 'translate-x-0 w-72' : '-translate-x-full w-72'
            ]"
        >
            <div class="relative flex h-16 items-center gap-3 border-b border-slate-200 bg-white px-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden">
                    <img src="{$logoSrc}" alt="MasjidPay" class="h-full w-full object-contain">
                </div>
                <div class="min-w-0 overflow-hidden transition-all duration-200"
                     :class="collapsed ? 'lg:opacity-0 lg:w-0' : 'opacity-100'">
                    <p class="truncate text-base font-bold tracking-tight text-brand-charcoal">{$appName}</p>
                    <p class="truncate text-[11px] font-medium uppercase tracking-wider text-brand-gray">Admin Panel</p>
                </div>

                <button
                    type="button"
                    @click="toggle()"
                    class="absolute right-0 top-1/2 z-50 hidden h-8 w-8 -translate-y-1/2 translate-x-1/2 items-center justify-center rounded-full border-2 border-slate-300 bg-white text-slate-500 shadow-lg transition hover:bg-slate-100 lg:inline-flex"
                    :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                    aria-label="Toggle sidebar"
                >
                    <svg class="h-3.5 w-3.5 transition-transform duration-300" :class="collapsed ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
            </div>

            <nav class="flex-1 space-y-1.5 overflow-y-auto overflow-x-visible bg-white px-3 py-4">
                <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400"
                   :class="collapsed ? 'lg:opacity-0 lg:h-0 lg:mb-0 lg:overflow-hidden' : ''">Menu</p>
                {$itemsHtml}
            </nav>
        </aside>
        HTML;
    }

    public static function echo(string $activeNav = ''): void
    {
        echo self::render($activeNav);
    }
}
