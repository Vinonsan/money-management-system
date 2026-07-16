<?php
/**
 * @var string $smsBalance  Current SMS balance
 * @var string $smsCost     Cost per SMS message
 * @var string $smsSenderId SMSLenz sender ID
 * @var array  $history     Transaction history log
 */
$pageTitle = 'SMS Management';
?>

<div class="mx-auto max-w-4xl space-y-6 py-2"
     x-data="smsManager()">

    <!-- Header -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary-700 via-primary-800 to-primary-950 p-6 shadow-xl sm:p-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.08),transparent_60%)]"></div>
        <div class="relative">
            <h1 class="text-2xl font-bold tracking-tight text-white"><?= e($pageTitle) ?></h1>
            <p class="mt-1 text-sm font-medium text-white/70">Manage SMS balance, costs, and sender configuration.</p>
        </div>
    </div>

    <!-- ─── Current Balance Display ─────────────────────────── -->
    <div class="rounded-2xl border border-primary-200/60 bg-white p-6 shadow-sm shadow-primary-100/20">
        <div class="flex items-center gap-2 mb-5">
            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-primary-100 text-primary-700">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </span>
            <h3 class="text-base font-bold text-primary-800">Current Balance</h3>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-primary-700 to-primary-900 p-5 text-center shadow-md shadow-primary-200/30">
                <div class="absolute -top-4 -right-4 h-16 w-16 rounded-full bg-primary-500/10 blur-xl"></div>
                <div class="relative">
                    <p class="text-xs font-semibold text-primary-200 uppercase tracking-wider">Balance</p>
                    <p class="mt-1 text-3xl font-bold text-white">Rs. <?= number_format((float) $smsBalance, 2) ?></p>
                </div>
            </div>
            <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-primary-600 to-primary-800 p-5 text-center shadow-md shadow-primary-200/30">
                <div class="absolute -top-4 -right-4 h-16 w-16 rounded-full bg-primary-400/10 blur-xl"></div>
                <div class="relative">
                    <p class="text-xs font-semibold text-primary-200 uppercase tracking-wider">Cost / SMS</p>
                    <p class="mt-1 text-3xl font-bold text-white">Rs. <?= number_format((float) $smsCost, 2) ?></p>
                </div>
            </div>
            <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 p-5 text-center shadow-md shadow-primary-200/30">
                <div class="absolute -top-4 -right-4 h-16 w-16 rounded-full bg-white/10 blur-xl"></div>
                <div class="relative">
                    <p class="text-xs font-semibold text-primary-200 uppercase tracking-wider">Remaining SMS</p>
                    <?php $remaining = (float)$smsCost > 0 ? floor((float)$smsBalance / (float)$smsCost) : 0; ?>
                    <p class="mt-1 text-3xl font-bold text-white"><?= (int) $remaining ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── Refill Form ─────────────────────────────────────── -->
    <div class="rounded-2xl border border-primary-200/60 bg-white p-6 shadow-sm shadow-primary-100/20 transition-all duration-300 hover:shadow-md hover:shadow-primary-200/30">
        <div class="flex items-center gap-2 mb-5">
            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-primary-100 text-primary-700">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
            </span>
            <h3 class="text-base font-bold text-primary-800">Refill SMS Balance</h3>
        </div>
        <form @submit.prevent="refill()" class="max-w-sm space-y-4">
            <div>
                <label class="block text-sm font-semibold text-primary-800 mb-1.5">Amount (Rs)</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-primary-600">Rs.</span>
                    <input type="number" x-model="refillAmount" min="1" step="0.01" placeholder="e.g. 1000"
                        class="w-full rounded-lg border border-primary-200 bg-primary-50/30 pl-9 pr-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:bg-white transition">
                </div>
                <template x-if="refillError">
                    <p class="mt-1 text-xs text-primary-600" x-text="refillError"></p>
                </template>
            </div>
            <button type="submit" :disabled="refilling"
                class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-primary-600/20 transition-all hover:bg-primary-700 hover:shadow-lg hover:shadow-primary-600/30 active:scale-[0.97] disabled:opacity-60 disabled:cursor-not-allowed">
                <template x-if="!refilling">
                    <>
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        Refill Balance
                    </>
                </template>
                <template x-if="refilling">
                    <span>Processing...</span>
                </template>
            </button>
        </form>
    </div>

    <!-- ─── SMS Configuration ─────────────────────────── -->
    <div class="rounded-2xl border border-primary-200/60 bg-white p-6 shadow-sm shadow-primary-100/20 transition-all duration-300 hover:shadow-md hover:shadow-primary-200/30">
        <div class="flex items-center gap-2 mb-5">
            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-primary-100 text-primary-700">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </span>
            <h3 class="text-base font-bold text-primary-800">SMS Configuration</h3>
        </div>
        <form @submit.prevent="saveConfig()" class="max-w-lg space-y-4">
            <div>
                <label class="block text-sm font-semibold text-primary-800 mb-1.5">Cost Per Message (Rs)</label>
                <input type="number" x-model="smsCost" min="0.01" step="0.01"
                    class="w-full rounded-lg border border-primary-200 bg-primary-50/30 px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:bg-white transition">
                <template x-if="configError">
                    <p class="mt-1 text-xs text-primary-600" x-text="configError"></p>
                </template>
            </div>
            <div>
                <label class="block text-sm font-semibold text-primary-800 mb-1.5">Sender ID</label>
                <input type="text" x-model="senderId"
                    class="w-full rounded-lg border border-primary-200 bg-primary-50/30 px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:bg-white transition"
                    placeholder="e.g. ExGenX9920">
                <p class="mt-1.5 text-xs text-primary-600 flex items-start gap-1.5">
                    <svg class="h-3.5 w-3.5 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>If SMS not sending, check your approved Sender ID on <a href="https://smslenz.lk" target="_blank" class="underline font-semibold hover:text-primary-800">smslenz.lk</a>.</span>
                </p>
            </div>
            <div class="flex items-center justify-end pt-2">
                <button type="submit" :disabled="configSaving"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-primary-600/20 transition-all hover:bg-primary-700 hover:shadow-lg hover:shadow-primary-600/30 active:scale-[0.97] disabled:opacity-60 disabled:cursor-not-allowed">
                    <template x-if="!configSaving">
                        <>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            Save Configuration
                        </>
                    </template>
                    <template x-if="configSaving">
                        <span>Saving...</span>
                    </template>
                </button>
            </div>
        </form>
    </div>

    <!-- ─── Transaction History ─────────────────────────── -->
    <div class="rounded-2xl border border-primary-200/60 bg-white p-6 shadow-sm shadow-primary-100/20">
        <div class="flex items-center gap-2 mb-5">
            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-primary-100 text-primary-700">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </span>
            <h3 class="text-base font-bold text-primary-800">Transaction History</h3>
        </div>
        <?php if (empty($history)): ?>
            <div class="py-10 text-center">
                <svg class="mx-auto h-12 w-12 text-primary-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-sm font-medium text-primary-400">No transactions recorded yet.</p>
                <p class="text-xs text-primary-300 mt-1">Refill your SMS balance to see history here.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto rounded-xl border border-primary-100">
                <table class="min-w-full divide-y divide-primary-100">
                    <thead class="bg-gradient-to-r from-primary-50 to-primary-100/50">
                        <tr>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-primary-700">Date / Time</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-primary-700">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-primary-50">
                        <?php foreach ($history as $txn): ?>
                            <tr class="transition hover:bg-primary-50/50">
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-700">
                                        <svg class="h-3.5 w-3.5 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <?= e(date('d M Y h:i A', strtotime($txn['updated_at'] ?? ''))) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <p class="text-sm text-slate-600"><?= e($txn['value'] ?? '') ?></p>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function smsManager() {
    return {
        // Refill
        refillAmount: '',
        refillError: '',
        refilling: false,

        // Config
        smsCost: '<?= e($smsCost) ?>',
        senderId: '<?= e($smsSenderId) ?>',
        configError: '',
        configSaving: false,

        // ─── Refill ─────────────────────────────────
        refill() {
            this.refillError = '';
            const amount = parseFloat(this.refillAmount);

            if (!amount || amount <= 0) {
                this.refillError = 'Please enter a valid amount greater than 0.';
                return;
            }

            this.refilling = true;

            fetch(BASE_URL + '/super-admin/sms/refill', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({
                    amount: amount,
                    _csrf: '<?= e($_SESSION['csrf_token'] ?? '') ?>',
                }),
            })
            .then(r => r.json())
            .then(r => {
                this.refilling = false;
                if (r.success) {
                    showToast(r.message || 'Balance refilled successfully.');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast(r.error || 'Refill failed.', 'error');
                }
            })
            .catch(() => {
                this.refilling = false;
                showToast('Network error.', 'error');
            });
        },

        // ─── Save Config ────────────────────────────
        saveConfig() {
            this.configError = '';
            const cost = parseFloat(this.smsCost);

            if (!cost || cost <= 0) {
                this.configError = 'Cost per message must be greater than 0.';
                return;
            }

            this.configSaving = true;

            fetch(BASE_URL + '/super-admin/sms/save-config', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({
                    sms_cost_per_message: this.smsCost,
                    smslenz_sender_id: this.senderId,
                    _csrf: '<?= e($_SESSION['csrf_token'] ?? '') ?>',
                }),
            })
            .then(r => r.json())
            .then(r => {
                this.configSaving = false;
                if (r.success) {
                    showToast(r.message || 'Configuration saved.');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast(r.error || 'Save failed.', 'error');
                }
            })
            .catch(() => {
                this.configSaving = false;
                showToast('Network error.', 'error');
            });
        },
    };
}
</script>
