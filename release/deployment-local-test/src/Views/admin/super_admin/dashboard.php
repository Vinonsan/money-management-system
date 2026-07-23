<?php
/**
 * @var int    $totalAdmins  Total admin users
 * @var string $smsBalance   Current SMS balance
 * @var string $smsCost      Cost per SMS message
 * @var int    $remainingSms Approximate remaining SMS count
 */
$pageTitle = 'Super Admin Dashboard';
?>

<div class="mx-auto max-w-5xl space-y-6 py-2">
    <!-- Premium Welcome Header -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary-700 via-primary-800 to-primary-950 p-6 shadow-xl sm:p-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.08),transparent_60%)]"></div>
        <div class="absolute -bottom-6 -right-6 h-32 w-32 rounded-full bg-primary-500/10 blur-2xl"></div>
        <div class="relative">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 text-white backdrop-blur-sm">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold uppercase tracking-widest text-white/70">Super Admin Panel</p>
            </div>
            <h2 class="mt-3 text-2xl font-bold tracking-tight text-white sm:text-3xl"><?= e($_SESSION['user_name'] ?? 'Super Admin') ?></h2>
            <p class="mt-1.5 text-sm text-white/70">Manage system admins and SMS configuration.</p>
        </div>
    </div>

    <!-- Admin Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <a href="<?= BASE_URL ?>/super-admin/admins"
           class="group rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition-all duration-300 hover:border-primary-300 hover:shadow-lg hover:shadow-primary-100/30 hover:-translate-y-0.5">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-primary-50 text-primary-600 transition-all duration-300 group-hover:bg-primary-100 group-hover:scale-110">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Admins</p>
                    <p class="text-3xl font-bold text-slate-900"><?= (int) $totalAdmins ?></p>
                </div>
                <svg class="ml-auto h-5 w-5 text-slate-300 transition-all duration-300 group-hover:text-primary-500 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>

        <a href="<?= BASE_URL ?>/super-admin/sms"
           class="group rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition-all duration-300 hover:border-primary-300 hover:shadow-lg hover:shadow-primary-100/30 hover:-translate-y-0.5">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-primary-50 text-primary-600 transition-all duration-300 group-hover:bg-primary-100 group-hover:scale-110">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">SMS Balance</p>
                    <p class="text-3xl font-bold text-slate-900">Rs. <?= number_format((float) $smsBalance, 2) ?></p>
                </div>
                <svg class="ml-auto h-5 w-5 text-slate-300 transition-all duration-300 group-hover:text-primary-500 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>
    </div>

    <!-- Quick Links -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <a href="<?= BASE_URL ?>/super-admin/admins"
           class="group relative rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition-all duration-300 hover:border-primary-400 hover:shadow-xl hover:shadow-primary-100/40 hover:-translate-y-0.5">
            <div class="absolute right-0 top-0 h-full w-1 rounded-r-2xl bg-gradient-to-b from-primary-500 to-primary-700 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-50 text-primary-600 transition-all duration-300 group-hover:bg-primary-100 group-hover:scale-110 group-hover:text-primary-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 group-hover:text-primary-700 transition">Manage Admins</h3>
                    <p class="text-sm text-slate-500">Create, edit, and remove admin accounts</p>
                </div>
                <svg class="ml-auto h-5 w-5 text-slate-300 transition-all duration-300 group-hover:text-primary-500 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>

        <a href="<?= BASE_URL ?>/super-admin/sms"
           class="group relative rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition-all duration-300 hover:border-primary-400 hover:shadow-xl hover:shadow-primary-100/40 hover:-translate-y-0.5">
            <div class="absolute right-0 top-0 h-full w-1 rounded-r-2xl bg-gradient-to-b from-primary-500 to-primary-700 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-50 text-primary-600 transition-all duration-300 group-hover:bg-primary-100 group-hover:scale-110 group-hover:text-primary-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 group-hover:text-primary-700 transition">SMS Management</h3>
                    <p class="text-sm text-slate-500">Refill balance, configure costs &amp; sender ID</p>
                </div>
                <svg class="ml-auto h-5 w-5 text-slate-300 transition-all duration-300 group-hover:text-primary-500 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>
    </div>
</div>