<?php
namespace Components\Layouts;

final class AppLayout
{
    public static function render(string $title, string $content, string $activeNav = ''): void
    {
        $userName = $_SESSION['user_name'] ?? 'User';
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> | <?= \APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-gray-50 text-gray-800 antialiased" x-data="{ sidebarOpen: true, open: '', drawer: '' }">

<div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside class="hidden lg:flex lg:flex-col w-64 bg-white border-r border-gray-200 overflow-y-auto"
           :class="sidebarOpen ? 'lg:flex' : 'lg:hidden'">
        <div class="flex items-center gap-2 px-6 py-5 border-b border-gray-100">
            <span class="text-lg font-bold text-gray-900"><?= \APP_NAME ?></span>
        </div>
        <nav class="flex-1 px-4 py-4 space-y-1">
            <?= self::navItem('Dashboard', '/admin', 'layout-dashboard', $activeNav === 'dashboard') ?>
            <?= self::navItem('Users', '/admin/users', 'users', $activeNav === 'users') ?>
        </nav>
        <div class="border-t border-gray-100 p-4">
            <a href="/logout" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50 transition">
                Logout
            </a>
        </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Nav -->
        <header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-gray-700 lg:hidden">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h2 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($title) ?></h2>
            </div>
            <div class="flex items-center gap-3 text-sm text-gray-600">
                <span><?= htmlspecialchars($userName) ?></span>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto p-6">
            <?= $content ?>
        </main>
    </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>
</body>
</html>
<?php
    }

    private static function navItem(string $label, string $href, string $icon, bool $active): string
    {
        $activeClass = $active ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-100';
        return '<a href="' . $href . '" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition ' . $activeClass . '">'
             . '<span>' . $label . '</span></a>';
    }
}