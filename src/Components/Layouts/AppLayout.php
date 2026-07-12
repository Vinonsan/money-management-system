<?php
namespace Components\Layouts;

/**
 * Admin shell: Sidebar + Navbar + page content.
 */
final class AppLayout
{
    public static function render(string $title, string $contentView, array $data = [], string $activeNav = ''): void
    {
        extract($data);
        $isLoggedIn = !empty($_SESSION['user_id']);
        $openParents = SidebarRouter::initiallyOpenParents($activeNav);
        $openParentsJson = $openParents === []
            ? '{}'
            : json_encode($openParents, JSON_UNESCAPED_SLASHES);
        ?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> | <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="h-full bg-white text-slate-800 antialiased">

<?php if ($isLoggedIn): ?>
<div
    class="flex h-full min-h-screen"
    x-data="{
        collapsed: localStorage.getItem('mp_sidebar') === '1',
        mobileOpen: false,
        userMenu: false,
        openParents: JSON.parse('<?= e($openParentsJson) ?>'),
        drawer: '',
        drawerMode: 'add',
        drawerData: {},
        modal: '',
        modalData: {},
        nameError: '',
        wardError: '',
        userErrors: {},
        toggle() {
            this.collapsed = !this.collapsed;
            localStorage.setItem('mp_sidebar', this.collapsed ? '1' : '0');
        },
        isOpen(key) { return !!this.openParents[key]; },
        toggleOpen(key) {
            // Toggle only the clicked parent, others stay as they are
            this.openParents[key] = !this.openParents[key];
        }
    }"
    @keydown.escape.window="userMenu = false; mobileOpen = false"
>
    <?= Sidebar::render($activeNav) ?>

    <div class="flex min-w-0 flex-1 flex-col">
        <?= Navbar::render($title) ?>
        <main class="flex-1 overflow-y-auto p-4 sm:p-6">
            <?php require $contentView; ?>
        </main>
    </div>
</div>
<?php else: ?>
<div class="min-h-screen">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
            <a href="<?= BASE_URL ?>/" class="text-xl font-bold text-emerald-800"><?= APP_NAME ?></a>
            <a href="<?= BASE_URL ?>/login" class="text-sm font-semibold text-slate-600 hover:text-emerald-800">Login</a>
        </div>
    </header>
    <main>
        <?php require $contentView; ?>
    </main>
</div>
<?php endif; ?>

</body>
</html>
<?php
    }
}
