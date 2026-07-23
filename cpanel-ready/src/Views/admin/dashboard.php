<?php
/**
 * @var int   $totalMembers   Active members with monthly_amount > 0
 * @var float $monthlyTarget  Sum of all monthly amounts (target per month)
 * @var float $yearlyTarget   Monthly target * 12
 * @var float $thisMonth      Amount collected this month
 * @var float $thisYear       Amount collected this year
 * @var float $totalCollected Total collection overall
 * @var int   $unpaidCount    Members with payment due
 * @var int   $paidCount      Members paid through the current date
 */
$pageTitle = 'Dashboard';
?>

<div class="mx-auto max-w-7xl space-y-6 py-2">
    <!-- Welcome Header -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary-700 via-primary-800 to-primary-950 p-6 shadow-xl sm:p-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.08),transparent_60%)]"></div>
        <div class="absolute -bottom-6 -right-6 h-32 w-32 rounded-full bg-primary-500/10 blur-2xl"></div>
        <div class="relative">
            <p class="text-sm font-medium text-white/70">Welcome back,</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-white sm:text-3xl"><?= e($_SESSION['user_name'] ?? 'Admin') ?></h2>
            <p class="mt-1 text-sm text-white/70">MasjidPay collection dashboard — overview at a glance.</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
        <!-- Total Members -->
        <div class="group relative overflow-hidden rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm transition-all duration-300 hover:shadow-lg hover:shadow-primary-100/20 hover:-translate-y-1 hover:border-primary-200">
            <div class="absolute top-0 right-0 h-20 w-20 -mr-6 -mt-6 rounded-full bg-primary-50/50 transition-all duration-300 group-hover:scale-150"></div>
            <div class="relative">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 text-white shadow-md shadow-primary-200/30 mb-3">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Members</p>
                <p class="mt-1 text-3xl font-bold text-slate-900"><?= (int) $totalMembers ?></p>
            </div>
        </div>

        <!-- Monthly Target -->
        <div class="group relative overflow-hidden rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm transition-all duration-300 hover:shadow-lg hover:shadow-primary-100/20 hover:-translate-y-1 hover:border-primary-200">
            <div class="absolute top-0 right-0 h-20 w-20 -mr-6 -mt-6 rounded-full bg-primary-50/50 transition-all duration-300 group-hover:scale-150"></div>
            <div class="relative">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 text-white shadow-md shadow-primary-200/30 mb-3">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Monthly Target</p>
                <p class="mt-1 text-3xl font-bold text-slate-900">Rs. <?= number_format($monthlyTarget, 2) ?></p>
            </div>
        </div>

        <!-- Yearly Target -->
        <div class="group relative overflow-hidden rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm transition-all duration-300 hover:shadow-lg hover:shadow-primary-100/20 hover:-translate-y-1 hover:border-primary-200">
            <div class="absolute top-0 right-0 h-20 w-20 -mr-6 -mt-6 rounded-full bg-primary-50/50 transition-all duration-300 group-hover:scale-150"></div>
            <div class="relative">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 text-white shadow-md shadow-primary-200/30 mb-3">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Yearly Target</p>
                <p class="mt-1 text-3xl font-bold text-slate-900">Rs. <?= number_format($yearlyTarget, 2) ?></p>
            </div>
        </div>

        <!-- This Month -->
        <div class="group relative overflow-hidden rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm transition-all duration-300 hover:shadow-lg hover:shadow-primary-100/20 hover:-translate-y-1 hover:border-primary-200">
            <div class="absolute top-0 right-0 h-20 w-20 -mr-6 -mt-6 rounded-full bg-primary-50/50 transition-all duration-300 group-hover:scale-150"></div>
            <div class="relative">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 text-white shadow-md shadow-primary-200/30 mb-3">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">This Month</p>
                <p class="mt-1 text-3xl font-bold text-primary-700">Rs. <?= number_format($thisMonth, 2) ?></p>
            </div>
        </div>

        <!-- This Year -->
        <div class="group relative overflow-hidden rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm transition-all duration-300 hover:shadow-lg hover:shadow-primary-100/20 hover:-translate-y-1 hover:border-primary-200">
            <div class="absolute top-0 right-0 h-20 w-20 -mr-6 -mt-6 rounded-full bg-primary-50/50 transition-all duration-300 group-hover:scale-150"></div>
            <div class="relative">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 text-white shadow-md shadow-primary-200/30 mb-3">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">This Year</p>
                <p class="mt-1 text-3xl font-bold text-primary-700">Rs. <?= number_format($thisYear, 2) ?></p>
            </div>
        </div>
    </div>

    <!-- Progress & Unpaid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Monthly Progress -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition-all duration-300 hover:shadow-md hover:border-primary-200">
            <div class="flex items-center gap-2 mb-4">
                <div class="h-2 w-2 rounded-full bg-primary-500"></div>
                <h3 class="text-sm font-bold text-slate-800">Monthly Progress</h3>
            </div>
            <?php
            $mpct = $monthlyTarget > 0 ? round(($thisMonth / $monthlyTarget) * 100, 1) : 0;
            $mColor = $mpct >= 100 ? 'bg-primary-600' : ($mpct >= 50 ? 'bg-primary-500' : 'bg-primary-400');
            ?>
            <div class="flex items-baseline gap-2 mb-3">
                <span class="text-3xl font-bold text-slate-900"><?= $mpct ?>%</span>
                <span class="text-xs text-slate-500">of Rs. <?= number_format($monthlyTarget, 0) ?></span>
            </div>
            <div class="h-2.5 w-full rounded-full bg-primary-50 overflow-hidden">
                <div class="h-full rounded-full <?= $mColor ?> transition-all duration-700" style="width: <?= min($mpct, 100) ?>%"></div>
            </div>
            <div class="flex justify-between mt-2 text-xs text-slate-400">
                <span>Rs. <?= number_format($thisMonth, 2) ?> collected</span>
                <span>Target: Rs. <?= number_format($monthlyTarget, 2) ?></span>
            </div>
        </div>

        <!-- Yearly Progress -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition-all duration-300 hover:shadow-md hover:border-primary-200">
            <div class="flex items-center gap-2 mb-4">
                <div class="h-2 w-2 rounded-full bg-primary-500"></div>
                <h3 class="text-sm font-bold text-slate-800">Yearly Progress</h3>
            </div>
            <?php
            $ypct = $yearlyTarget > 0 ? round(($thisYear / $yearlyTarget) * 100, 1) : 0;
            $yColor = $ypct >= 100 ? 'bg-primary-600' : ($ypct >= 50 ? 'bg-primary-500' : 'bg-primary-400');
            ?>
            <div class="flex items-baseline gap-2 mb-3">
                <span class="text-3xl font-bold text-slate-900"><?= $ypct ?>%</span>
                <span class="text-xs text-slate-500">of Rs. <?= number_format($yearlyTarget, 0) ?></span>
            </div>
            <div class="h-2.5 w-full rounded-full bg-primary-50 overflow-hidden">
                <div class="h-full rounded-full <?= $yColor ?> transition-all duration-700" style="width: <?= min($ypct, 100) ?>%"></div>
            </div>
            <div class="flex justify-between mt-2 text-xs text-slate-400">
                <span>Rs. <?= number_format($thisYear, 2) ?> collected</span>
                <span>Target: Rs. <?= number_format($yearlyTarget, 2) ?></span>
            </div>
        </div>

        <!-- Unpaid Members & Total Collected -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition-all duration-300 hover:shadow-md hover:border-primary-200">
            <div class="flex items-center gap-2 mb-4">
                <div class="h-2 w-2 rounded-full bg-primary-500"></div>
                <h3 class="text-sm font-bold text-slate-800">Unpaid Members</h3>
            </div>
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div>
                    <span class="block text-3xl font-bold text-primary-700"><?= (int) $unpaidCount ?></span>
                    <span class="text-xs text-slate-500">with dues</span>
                </div>
                <div class="border-l border-slate-200 pl-3">
                    <span class="block text-3xl font-bold text-emerald-600"><?= (int) ($paidCount ?? 0) ?></span>
                    <span class="text-xs text-slate-500">paid up</span>
                </div>
            </div>
            <a href="<?= BASE_URL ?>/admin/payments/members?status=unpaid"
                class="inline-flex items-center justify-center gap-2 w-full rounded-xl bg-gradient-to-r from-primary-600 to-primary-700 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-primary-600/20 hover:shadow-lg hover:shadow-primary-600/30 transition-all mb-4">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                View Unpaid Members
            </a>
            <div class="border-t border-slate-100 pt-4">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Total Collected All Time</p>
                <p class="text-2xl font-bold text-slate-900">Rs. <?= number_format($totalCollected, 2) ?></p>
            </div>
        </div>
    </div>
</div>
