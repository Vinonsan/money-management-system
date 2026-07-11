<?php
// src/Views/layouts/app_layout.php
function renderAppLayout(string $title, string $contentView, array $data = [], string $activeNav = ''): void
{
    extract($data);
    $userName = $_SESSION['user_name'] ?? 'Guest';
    $userRole = $_SESSION['user_role'] ?? 'guest';
    $isLoggedIn = !empty($_SESSION['user_id']);

    $navItems = [
        'dashboard' => ['label' => 'Dashboard', 'href' => BASE_URL . '/admin'],
    ];
?>
<!DOCTYPE html>
<html lang="en" x-data="{ sidebarOpen: true }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> | <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-gray-50 text-gray-800 antialiased">

<?php if ($isLoggedIn): ?>
<div class="flex h-screen overflow-hidden">
    <aside class="hidden lg:flex lg:flex-col w-64 bg-white border-r border-gray-200 overflow-y-auto">
        <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-100">
            <div class="w-8 h-8 rounded-lg bg-emerald-700 flex items-center justify-center text-white text-sm font-bold">M</div>
            <span class="text-lg font-bold text-gray-900"><?= APP_NAME ?></span>
        </div>
        <nav class="flex-1 px-3 py-4 space-y-1">
            <?php foreach ($navItems as $key => $item): ?>
                <a href="<?= $item['href'] ?>"
                   class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition
                   <?= $activeNav === $key ? 'bg-emerald-50 text-emerald-800' : 'text-gray-600 hover:bg-gray-100' ?>">
                    <?= e($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="border-t border-gray-100 p-4">
            <p class="px-3 text-sm font-semibold text-gray-800 truncate"><?= e($userName) ?></p>
            <p class="px-3 text-xs text-gray-400"><?= e($userRole) ?></p>
            <a href="<?= BASE_URL ?>/logout" class="mt-2 flex items-center rounded-lg px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Logout</a>
        </div>
    </aside>
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-800"><?= e($title) ?></h2>
        </header>
        <main class="flex-1 overflow-y-auto p-6">
            <?php require_once $contentView; ?>
        </main>
    </div>
</div>
<?php else: ?>
<div class="min-h-screen">
    <header class="border-b border-gray-200 bg-white">
        <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="<?= BASE_URL ?>/" class="text-xl font-bold text-emerald-800"><?= APP_NAME ?></a>
            <a href="<?= BASE_URL ?>/login" class="text-sm font-semibold text-gray-600 hover:text-emerald-800">Login</a>
        </div>
    </header>
    <main>
        <?php require_once $contentView; ?>
    </main>
</div>
<?php endif; ?>

</body>
</html>
<?php
}
