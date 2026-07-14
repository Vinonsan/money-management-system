<?php
/**
 * @var int   $totalMembers   Active members with monthly_amount > 0
 * @var float $monthlyTarget  Sum of all monthly amounts (target per month)
 * @var float $yearlyTarget   Monthly target * 12
 * @var float $thisMonth      Amount collected this month
 * @var float $thisYear       Amount collected this year
 * @var float $totalCollected Total collection overall
 * @var int   $unpaidCount    Members with payment due
 * @var float $smsBalance     Current SMS balance (Rs.)
 * @var float $smsCost        Cost per SMS (Rs.)
 * @var int   $remainingSms   Estimated remaining SMS count
 */
$pageTitle = 'Dashboard';
?>

<div class="mx-auto max-w-7xl space-y-6 py-2">
    <!-- Welcome -->
    <div class="rounded-2xl border border-slate-200/80 bg-white/60 p-6 shadow-sm backdrop-blur-md">
        <p class="text-sm font-medium text-slate-500">Welcome back,</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900"><?= e($_SESSION['user_name'] ?? 'Admin') ?></h2>
        <p class="mt-1 text-sm text-slate-500">MasjidPay collection dashboard — overview at a glance.</p>
    </div>

    <!-- Collection Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Members</p>
                    <p class="text-2xl font-bold text-slate-900"><?= (int) $totalMembers ?></p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Monthly Target</p>
                    <p class="text-2xl font-bold text-slate-900">Rs. <?= number_format($monthlyTarget, 2) ?></p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Yearly Target</p>
                    <p class="text-2xl font-bold text-slate-900">Rs. <?= number_format($yearlyTarget, 2) ?></p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">This Month</p>
                    <p class="text-2xl font-bold text-emerald-700">Rs. <?= number_format($thisMonth, 2) ?></p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 text-purple-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">This Year</p>
                    <p class="text-2xl font-bold text-indigo-700">Rs. <?= number_format($thisYear, 2) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- SMS Balance (priority - above progress) -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
        <div class="flex items-start justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">SMS Balance</h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Cost per message: Rs. <?= number_format($smsCost, 2) ?>
                    <span class="mx-1.5">·</span>
                    Gateway:
                    <?php if ($smsConfigured ?? false): ?>
                        <span class="inline-flex items-center gap-1 text-green-600 font-medium">
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-green-500"></span>
                            SMSlenz
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1 text-amber-600 font-medium">
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            Not configured
                        </span>
                        <a href="/admin/system-config" class="text-blue-600 hover:underline ml-1">Configure</a>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
            <div class="rounded-xl bg-slate-50 border border-slate-100 p-4">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Current Balance</p>
                <p class="text-xl font-bold text-slate-900 mt-1">Rs. <?= number_format($smsBalance, 2) ?></p>
            </div>
            <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-4">
                <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Remaining SMS</p>
                <p class="text-xl font-bold text-emerald-700 mt-1"><?= (int) $remainingSms ?></p>
            </div>
            <div class="rounded-xl bg-amber-50 border border-amber-100 p-4">
                <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Unpaid Recipients</p>
                <p class="text-xl font-bold text-amber-700 mt-1"><?= (int) $unpaidCount ?></p>
                <?php if ($unpaidCount > $remainingSms): ?>
                    <p class="text-xs text-amber-600 mt-1">⚠️ Need top-up to message all unpaid members</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Progress & Unpaid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Monthly Progress -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <h3 class="text-base font-bold text-slate-900 mb-3">Monthly Progress</h3>
            <?php
            $mpct = $monthlyTarget > 0 ? round(($thisMonth / $monthlyTarget) * 100, 1) : 0;
            $mColor = $mpct >= 100 ? 'bg-emerald-500' : ($mpct >= 50 ? 'bg-blue-500' : 'bg-amber-500');
            ?>
            <div class="flex items-baseline gap-2 mb-2">
                <span class="text-3xl font-bold text-slate-900"><?= $mpct ?>%</span>
                <span class="text-sm text-slate-500">of monthly target</span>
            </div>
            <div class="h-3 w-full rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full rounded-full <?= $mColor ?> transition-all" style="width: <?= min($mpct, 100) ?>%"></div>
            </div>
            <div class="flex justify-between mt-1.5 text-xs text-slate-400">
                <span>Rs. <?= number_format($thisMonth, 2) ?> collected</span>
                <span>Target: Rs. <?= number_format($monthlyTarget, 2) ?></span>
            </div>
        </div>

        <!-- Yearly Progress -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <h3 class="text-base font-bold text-slate-900 mb-3">Yearly Progress</h3>
            <?php
            $ypct = $yearlyTarget > 0 ? round(($thisYear / $yearlyTarget) * 100, 1) : 0;
            $yColor = $ypct >= 100 ? 'bg-emerald-500' : ($ypct >= 50 ? 'bg-blue-500' : 'bg-amber-500');
            ?>
            <div class="flex items-baseline gap-2 mb-2">
                <span class="text-3xl font-bold text-slate-900"><?= $ypct ?>%</span>
                <span class="text-sm text-slate-500">of yearly target</span>
            </div>
            <div class="h-3 w-full rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full rounded-full <?= $yColor ?> transition-all" style="width: <?= min($ypct, 100) ?>%"></div>
            </div>
            <div class="flex justify-between mt-1.5 text-xs text-slate-400">
                <span>Rs. <?= number_format($thisYear, 2) ?> collected</span>
                <span>Target: Rs. <?= number_format($yearlyTarget, 2) ?></span>
            </div>
        </div>

        <!-- Unpaid Members & Total Collected -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <h3 class="text-base font-bold text-slate-900 mb-3">Unpaid Members</h3>
            <div class="flex items-baseline gap-2 mb-3">
                <span class="text-3xl font-bold text-rose-600"><?= (int) $unpaidCount ?></span>
                <span class="text-sm text-slate-500">members with dues</span>
            </div>
            <a href="/admin/payments/members?status=unpaid"
                class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-700 transition mb-5">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                View Unpaid Members
            </a>
            <div class="border-t border-slate-100 pt-4">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Total Collected</p>
                <p class="text-2xl font-bold text-slate-900">Rs. <?= number_format($totalCollected, 2) ?></p>
            </div>
        </div>
    </div>
</div>
