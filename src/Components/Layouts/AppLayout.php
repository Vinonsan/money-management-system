<?php
namespace Components\Layouts;

/**
 * Admin shell: Sidebar + Navbar + page content.
 */
final class AppLayout
{
    public static function render(string $title, string $contentView, array $data = [], string $activeNav = ''): void
    {
        // Auth token verification: redirect to login if not authenticated (skip for public pages)
        $isPublicPage = str_contains($contentView, '/public/') || str_contains($contentView, 'public' . DIRECTORY_SEPARATOR);
        if (!$isPublicPage && empty($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        extract($data);
        $isLoggedIn = !empty($_SESSION['user_id']);
        $isSuperAdmin = str_starts_with($activeNav, 'super_admin');
        $openParents = SidebarRouter::initiallyOpenParents($activeNav);
        $openParentsJson = $openParents === []
            ? '{}'
            : json_encode($openParents, JSON_UNESCAPED_SLASHES);
        $themeClass = $isSuperAdmin ? ' super-admin-theme' : '';
        $tailwindConfig = $isSuperAdmin
            ? \superAdminTailwindColorsJs()
            : \adminTailwindColorsJs();
        ?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> | <?= APP_NAME ?></title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/logo.png">
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/img/favicon.svg">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>/assets/img/logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: <?= $tailwindConfig ?> } };
        const BASE_URL = '<?= BASE_URL ?>';
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }
        .toast-enter { animation: toastIn 0.3s ease-out; }
        .toast-exit { animation: toastOut 0.3s ease-in forwards; }
        @keyframes toastIn { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes toastOut { from { transform: translateY(0); opacity: 1; } to { transform: translateY(-20px); opacity: 0; } }
        /* Hide scrollbar globally */
        ::-webkit-scrollbar { display: none; }
        * { scrollbar-width: none; -ms-overflow-style: none; }
        /* ─── Super Admin Premium Purple Theme ─────── */
        .super-admin-theme::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 99999;
            height: 3px;
            background: linear-gradient(90deg, #28153a, #5a3c85, #af8bcf, #5a3c85, #28153a);
            background-size: 200% 100%;
            animation: premiumPurpleGradient 3s ease infinite;
            pointer-events: none;
        }
        @keyframes premiumPurpleGradient {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        /* Super admin sidebar active item accent */
        .super-admin-theme .sa-sidebar-accent {
            border-left: 3px solid #5a3c85 !important;
        }
    </style>
</head>
<body class="h-full bg-white text-brand-charcoal antialiased<?= $themeClass ?>">

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
        memberErrors: {},
        get filteredLocs() {
            if (!this.drawerData?.ward_id) return window.rawLocs || [];
            const ids = window.wardLocMap?.[this.drawerData.ward_id] || [];
            return (window.rawLocs || []).filter(loc => ids.includes(loc.id));
        },
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
            <a href="<?= BASE_URL ?>/" class="flex items-center gap-2">
                <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="<?= APP_NAME ?>" class="h-8 w-auto">
                <span class="text-xl font-bold text-primary-600"><?= APP_NAME ?></span>
            </a>
            <a href="<?= BASE_URL ?>/login" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600 transition">Login</a>
        </div>
    </header>
    <main>
        <?php require $contentView; ?>
    </main>
</div>
<?php endif; ?>

<!-- Toast Container -->
<div id="toast-container" class="fixed top-4 right-4 z-[99999] flex flex-col gap-2 pointer-events-none"></div>

<script>
function showToast(message, type) {
    type = type || 'success';
    const container = document.getElementById('toast-container');
    if (!container) return;

    const colors = {
        success: { bg: 'bg-primary-600', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>' },
        error:   { bg: 'bg-red-600',   icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>' },
        warning: { bg: 'bg-amber-500',  icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>' },
    };
    const cfg = colors[type] || colors.success;

    const toast = document.createElement('div');
    toast.className = cfg.bg + ' text-white px-5 py-3 rounded-xl shadow-xl flex items-center gap-3 pointer-events-auto toast-enter min-w-[280px] max-w-sm';
    toast.innerHTML = '<svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">' + cfg.icon + '</svg><span class="text-sm font-medium">' + message + '</span>';

    container.appendChild(toast);

    setTimeout(() => {
        toast.className = toast.className.replace('toast-enter', 'toast-exit');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
</script>

</body>
</html>
<?php
    }
}
