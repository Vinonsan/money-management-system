<?php
$userName  = $_SESSION['user_name'] ?? '';
$userEmail = $user['email'] ?? '';
$userPhone = $_SESSION['user_phone'] ?? ($user['phone'] ?? '');
$userRole  = $_SESSION['user_role'] ?? '';
$userInitial = strtoupper(substr($userName !== '' ? $userName : 'U', 0, 1));
?>
<div class="mx-auto max-w-2xl space-y-6">

    <!-- Profile Info -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-gradient-to-r from-primary-700 to-primary-800 px-6 py-8">
            <div class="flex items-center gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/15 text-2xl font-bold text-white ring-2 ring-white/30">
                    <?= e($userInitial) ?>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-white"><?= e($userName) ?></h2>
                    <p class="mt-0.5 text-sm capitalize text-primary-100"><?= e(str_replace('_', ' ', $userRole)) ?></p>
                </div>
            </div>
        </div>
        <div class="space-y-4 px-6 py-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Email</p>
                    <p class="mt-1 text-sm font-medium text-slate-800"><?= e($userEmail !== '' ? $userEmail : '—') ?></p>
                </div>
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Phone</p>
                    <p class="mt-1 text-sm font-medium text-slate-800"><?= e($userPhone !== '' ? $userPhone : '—') ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
