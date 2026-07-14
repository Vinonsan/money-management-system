<?php
namespace Components\Layouts;

/**
 * Admin top navbar — page title + user dropdown (profile / logout).
 */
final class Navbar
{
    public static function render(string $title = ''): string
    {
        $userName = htmlspecialchars($_SESSION['user_name'] ?? 'Guest', ENT_QUOTES, 'UTF-8');
        $userRole = htmlspecialchars(str_replace('_', ' ', $_SESSION['user_role'] ?? 'guest'), ENT_QUOTES, 'UTF-8');
        $userInitial = htmlspecialchars(strtoupper(substr($_SESSION['user_name'] ?? 'G', 0, 1)), ENT_QUOTES, 'UTF-8');
        $titleSafe = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $profileHref = htmlspecialchars(SidebarRouter::href('profile'), ENT_QUOTES, 'UTF-8');
        $logoutHref = htmlspecialchars(BASE_URL . '/logout', ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <header class="sticky top-0 z-30 flex h-16 items-center justify-between gap-4 border-b border-slate-200/80 bg-white/90 px-4 backdrop-blur-md sm:px-6">
            <div class="flex items-center gap-3">
                <button type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 lg:hidden"
                        @click="mobileOpen = !mobileOpen"
                        aria-label="Open menu">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <h1 class="text-base font-bold text-slate-900 sm:text-lg">{$titleSafe}</h1>
            </div>

            <div class="relative" @click.outside="userMenu = false">
                <button type="button"
                        @click="userMenu = !userMenu"
                        class="flex items-center gap-2.5 rounded-xl border border-slate-200 bg-white py-1.5 pl-1.5 pr-3 text-left shadow-sm transition hover:border-slate-300 hover:bg-slate-50">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-600 text-xs font-bold text-white">
                        {$userInitial}
                    </span>
                    <span class="hidden min-w-0 sm:block">
                        <span class="block max-w-[10rem] truncate text-sm font-semibold text-slate-800">{$userName}</span>
                        <span class="block truncate text-[11px] font-medium capitalize text-slate-400">{$userRole}</span>
                    </span>
                    <svg class="h-4 w-4 text-slate-400 transition" :class="userMenu ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div
                    x-show="userMenu"
                    x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-1"
                    class="absolute right-0 mt-2 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg shadow-slate-200/60"
                >
                    <div class="border-b border-slate-100 px-4 py-3 sm:hidden">
                        <p class="truncate text-sm font-semibold text-slate-800">{$userName}</p>
                        <p class="truncate text-xs capitalize text-slate-400">{$userRole}</p>
                    </div>
                    <a href="{$profileHref}"
                       class="flex items-center gap-2.5 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        View Profile
                    </a>
                    <a href="{$logoutHref}"
                       class="flex items-center gap-2.5 px-4 py-2.5 text-sm font-medium text-red-600 transition hover:bg-red-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Logout
                    </a>
                </div>
            </div>
        </header>
        HTML;
    }

    public static function echo(string $title = ''): void
    {
        echo self::render($title);
    }
}
