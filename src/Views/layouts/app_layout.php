<?php
// src/Views/layouts/app_layout.php
function renderAppLayout(string $title, string $contentView, array $data = [], string $activeNav = ''): void
{
    extract($data);
    $userName = $_SESSION['user_name'] ?? 'Guest';
    $userRole = $_SESSION['user_role'] ?? 'user';

    $navItems = [
        'dashboard' => ['label' => 'Dashboard',  'icon' => 'layout-dashboard', 'href' => BASE_URL . '/admin'],
        'users'     => ['label' => 'Users',      'icon' => 'users',           'href' => BASE_URL . '/admin/users'],
        'billing'   => ['label' => 'Billing',    'icon' => 'receipt',         'href' => BASE_URL . '/admin/billing'],
        'inventory' => ['label' => 'Inventory',  'icon' => 'package',         'href' => BASE_URL . '/admin/inventory'],
    ];
?>
<!DOCTYPE html>
<html lang="en" x-data="{ sidebarOpen: true, modal: '', drawer: '' }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> | <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-gray-50 text-gray-800 antialiased">

<div class="flex h-screen overflow-hidden">
    <!-- ===== SIDEBAR ===== -->
    <aside class="hidden lg:flex lg:flex-col w-64 bg-white border-r border-gray-200 overflow-y-auto"
           :class="sidebarOpen ? 'lg:flex' : 'lg:hidden'">
        <!-- Brand -->
        <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-100">
            <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white text-sm font-bold">A</div>
            <span class="text-lg font-bold text-gray-900"><?= APP_NAME ?></span>
        </div>

        <!-- Nav -->
        <nav class="flex-1 px-3 py-4 space-y-1">
            <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Main Menu</p>
            <?php foreach ($navItems as $key => $item): ?>
                <a href="<?= $item['href'] ?>"
                   class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition
                   <?= $activeNav === $key ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-100' ?>">
                    <span><?= $item['label'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- User footer -->
        <div class="border-t border-gray-100 p-4">
            <div class="flex items-center gap-3 px-3 py-2">
                <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600">
                    <?= strtoupper(substr($userName, 0, 1)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-800 truncate"><?= htmlspecialchars($userName) ?></p>
                    <p class="text-xs text-gray-400"><?= htmlspecialchars($userRole) ?></p>
                </div>
            </div>
            <a href="<?= BASE_URL ?>/logout" class="mt-2 flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 transition">Logout</a>
        </div>
    </aside>

    <!-- ===== MAIN ===== -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Nav Bar -->
        <header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h2 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($title) ?></h2>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-500"><?= date('M d, Y') ?></span>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto p-6">
            <?php if (!empty($success_message)): ?>
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>
            <?php require_once $contentView; ?>
        </main>
    </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>
</body>
</html>
<?php
}