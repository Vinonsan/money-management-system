<?php
/**
 * @var string $smsBalance   Current SMS balance
 * @var string $smsCost      Cost per SMS
 * @var int    $remainingSms Remaining messages
 * @var array  $requests     Admin's refill requests
 * @var string $requestError
 * @var string $requestSuccess
 */
$pageTitle = 'SMS Manager';
$error = $requestError ?? null;
$success = $requestSuccess ?? null;
?>
<div class="mx-auto max-w-4xl space-y-6 py-2">

    <?php if ($error): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="rounded-lg border border-primary-200 bg-primary-50 px-4 py-3 text-sm text-primary-800"><?= e($success) ?></div>
    <?php endif; ?>

    <!-- Header -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary-700 via-primary-800 to-primary-950 p-6 shadow-xl sm:p-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.08),transparent_60%)]"></div>
        <div class="relative">
            <h1 class="text-2xl font-bold tracking-tight text-white"><?= e($pageTitle) ?></h1>
            <p class="mt-1 text-sm font-medium text-white/70">View SMS balance, request refill from Super Admin.</p>
        </div>
    </div>

    <!-- Balance Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-primary-700 to-primary-900 p-5 shadow-md shadow-primary-200/30 text-center">
            <div class="absolute -top-4 -right-4 h-16 w-16 rounded-full bg-primary-500/10 blur-xl"></div>
            <div class="relative">
                <p class="text-xs font-semibold text-primary-200 uppercase tracking-wider">Balance</p>
                <p class="mt-1 text-3xl font-bold text-white">Rs. <?= number_format((float) $smsBalance, 2) ?></p>
            </div>
        </div>
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-primary-600 to-primary-800 p-5 shadow-md shadow-primary-200/30 text-center">
            <div class="relative">
                <p class="text-xs font-semibold text-primary-200 uppercase tracking-wider">Cost / SMS</p>
                <p class="mt-1 text-3xl font-bold text-white">Rs. <?= number_format((float) $smsCost, 2) ?></p>
            </div>
        </div>
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 p-5 shadow-md shadow-primary-200/30 text-center">
            <div class="relative">
                <p class="text-xs font-semibold text-primary-200 uppercase tracking-wider">Remaining SMS</p>
                <p class="mt-1 text-3xl font-bold text-white"><?= (int) $remainingSms ?> msgs</p>
            </div>
        </div>
    </div>

    <!-- Request Refill -->
    <div class="rounded-2xl border border-primary-200/60 bg-white p-6 shadow-sm shadow-primary-100/20">
        <div class="flex items-center gap-2 mb-5">
            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-primary-100 text-primary-700">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
            </span>
            <h3 class="text-base font-bold text-primary-800">Request Refill</h3>
        </div>
        <form method="post" action="<?= BASE_URL ?>/admin/sms/request-refill" class="max-w-lg space-y-4">
            <div>
                <label class="block text-sm font-semibold text-primary-800 mb-1.5">Amount (Rs)</label>
                <input type="number" name="amount" min="1" step="0.01" required
                    placeholder="e.g. 500"
                    class="w-full rounded-lg border border-primary-200 bg-primary-50/30 px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20 focus:bg-white transition">
            </div>
            <div>
                <label class="block text-sm font-semibold text-primary-800 mb-1.5">Note / Receipt Info</label>
                <textarea name="message" rows="3" placeholder="Optional: Add payment receipt or note..."
                    class="w-full rounded-lg border border-primary-200 bg-primary-50/30 px-3 py-2.5 text-sm focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20 focus:bg-white transition"></textarea>
            </div>
            <button type="submit"
                class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-primary-600/20 hover:bg-primary-700 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Submit Refill Request
            </button>
        </form>
    </div>

    <!-- Request History -->
    <div class="rounded-2xl border border-primary-200/60 bg-white p-6 shadow-sm shadow-primary-100/20">
        <div class="flex items-center gap-2 mb-5">
            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-primary-100 text-primary-700">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </span>
            <h3 class="text-base font-bold text-primary-800">Your Requests</h3>
        </div>
        <?php if (empty($requests)): ?>
            <div class="py-8 text-center">
                <p class="text-sm text-primary-400">No refill requests yet.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto rounded-xl border border-primary-100">
                <table class="min-w-full divide-y divide-primary-50">
                    <thead class="bg-gradient-to-r from-primary-50 to-primary-100/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-primary-700">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-primary-700">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-primary-700">Note</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-primary-700">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-primary-50">
                        <?php foreach ($requests as $req): ?>
                            <?php
                            $status = $req['status'] ?? 'pending';
                            $badge = match($status) {
                                'approved' => '<span class="inline-flex rounded-full bg-primary-100 px-2.5 py-0.5 text-xs font-semibold text-primary-700">Approved</span>',
                                'rejected' => '<span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700">Rejected</span>',
                                default    => '<span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Pending</span>',
                            };
                            ?>
                            <tr class="hover:bg-primary-50/50 transition">
                                <td class="px-4 py-3 text-sm text-slate-600 whitespace-nowrap"><?= e(date('d M Y', strtotime($req['created_at'] ?? ''))) ?></td>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-800">Rs. <?= number_format((float) ($req['amount'] ?? 0), 2) ?></td>
                                <td class="px-4 py-3 text-sm text-slate-500"><?= e(mb_substr($req['message'] ?? '', 0, 50)) ?></td>
                                <td class="px-4 py-3"><?= $badge ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
