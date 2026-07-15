<?php
/**
 * @var int   $totalAdmins       Total admin users
 * @var int   $totalCollectors   Total collector users
 * @var int   $totalMembers      Active members with monthly_amount > 0
 * @var float $totalCollection   Total collection overall
 * @var string $smsBalance       Current SMS balance
 * @var string $smsCost          Cost per SMS message
 * @var int   $remainingSms      Approximate remaining SMS count
 */
$pageTitle = 'Super Admin Dashboard';
?>

<div class="mx-auto max-w-7xl space-y-6 py-2">
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
            <p class="mt-1.5 text-sm text-white/70">Full system oversight — manage admins, monitor collections, and control SMS balance.</p>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-4">
        <!-- Total Admins -->
        <div class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition-all duration-300 hover:border-primary-300 hover:shadow-lg hover:shadow-primary-100/30 hover:-translate-y-0.5">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-600 transition-all duration-300 group-hover:bg-primary-100 group-hover:scale-110">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Admins</p>
                    <p class="text-2xl font-bold text-slate-900"><?= (int) $totalAdmins ?></p>
                </div>
            </div>
        </div>

        <!-- Total Collectors -->
        <div class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition-all duration-300 hover:border-primary-300 hover:shadow-lg hover:shadow-primary-100/30 hover:-translate-y-0.5">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-600 transition-all duration-300 group-hover:bg-primary-100 group-hover:scale-110">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Collectors</p>
                    <p class="text-2xl font-bold text-slate-900"><?= (int) $totalCollectors ?></p>
                </div>
            </div>
        </div>

        <!-- Total Members -->
        <div class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition-all duration-300 hover:border-primary-300 hover:shadow-lg hover:shadow-primary-100/30 hover:-translate-y-0.5">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-600 transition-all duration-300 group-hover:bg-primary-100 group-hover:scale-110">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Members</p>
                    <p class="text-2xl font-bold text-slate-900"><?= (int) $totalMembers ?></p>
                </div>
            </div>
        </div>

        <!-- Total Collection -->
        <div class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition-all duration-300 hover:border-primary-300 hover:shadow-lg hover:shadow-primary-100/30 hover:-translate-y-0.5">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-600 transition-all duration-300 group-hover:bg-primary-100 group-hover:scale-110">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Collection</p>
                    <p class="text-2xl font-bold text-slate-900">Rs. <?= number_format($totalCollection, 2) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- SMS Balance Summary -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-2 mb-4">
            <div class="h-1.5 w-1.5 rounded-full bg-primary-500"></div>
            <h3 class="text-base font-bold text-slate-900">SMS Balance</h3>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-xl bg-gradient-to-br from-primary-50 to-white p-4 border border-primary-200">
                <p class="text-xs font-semibold text-primary-600 uppercase tracking-wider">Current Balance</p>
                <p class="text-xl font-bold text-primary-800">Rs. <?= number_format((float) $smsBalance, 2) ?></p>
            </div>
            <div class="rounded-xl bg-slate-50 p-4 border border-slate-100">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Cost Per SMS</p>
                <p class="text-xl font-bold text-slate-900">Rs. <?= number_format((float) $smsCost, 2) ?></p>
            </div>
            <div class="rounded-xl bg-gradient-to-br from-primary-50 to-white p-4 border border-primary-200">
                <p class="text-xs font-semibold text-primary-600 uppercase tracking-wider">Remaining SMS</p>
                <p class="text-xl font-bold text-primary-800"><?= (int) $remainingSms ?> messages</p>
            </div>
        </div>
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
