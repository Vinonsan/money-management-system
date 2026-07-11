<div class="max-w-xl">
    <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
    <p class="mt-2 text-gray-500">
        Welcome, <?= e($_SESSION['user_name'] ?? 'User') ?>
        <span class="text-gray-400">(<?= e($_SESSION['user_role'] ?? '') ?>)</span>
    </p>
    <p class="mt-4 text-sm text-gray-500">MasjidPay admin dashboard — collection modules next.</p>
</div>
