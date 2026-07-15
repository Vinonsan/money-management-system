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
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
        <div class="flex items-center gap-2 mb-4">
            <div class="h-1.5 w-1.5 rounded-full bg-primary-500"></div>
            <h3 class="text-base font-bold text-slate-900">Current Balance</h3>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-xl bg-gradient-to-br from-primary-50 to-white p-5 border border-primary-200 text-center">
                <p class="text-xs font-semibold text-primary-600 uppercase tracking-wider">Balance</p>
                <p class="mt-1 text-3xl font-bold text-primary-700">Rs. <?= number_format((float) $smsBalance, 2) ?></p>
            </div>
            <div class="rounded-xl bg-slate-50 p-5 border border-slate-100 text-center">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Cost / SMS</p>
                <p class="mt-1 text-3xl font-bold text-slate-900">Rs. <?= number_format((float) $smsCost, 2) ?></p>
            </div>
            <div class="rounded-xl bg-gradient-to-br from-primary-50 to-white p-5 border border-primary-200 text-center">
                <p class="text-xs font-semibold text-primary-600 uppercase tracking-wider">Remaining SMS</p>
                <?php $remaining = (float)$smsCost > 0 ? floor((float)$smsBalance / (float)$smsCost) : 0; ?>
                <p class="mt-1 text-3xl font-bold text-primary-700"><?= (int) $remaining ?></p>
            </div>
        </div>
    </div>

    <!-- ─── Refill Form ─────────────────────────────────────── -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md hover:shadow-primary-100/20">
        <div class="flex items-center gap-2 mb-4">
            <div class="h-1.5 w-1.5 rounded-full bg-primary-500"></div>
            <h3 class="text-base font-bold text-slate-900">Refill SMS Balance</h3>
        </div>
        <form @submit.prevent="refill()" class="max-w-sm space-y-4">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Amount (Rs)</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-primary-600">Rs.</span>
                    <input type="number" x-model="refillAmount" min="1" step="0.01" placeholder="e.g. 1000"
                        class="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                </div>
                <template x-if="refillError">
                    <p class="mt-1 text-xs text-primary-500" x-text="refillError"></p>
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
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md hover:shadow-primary-100/20">
        <div class="flex items-center gap-2 mb-4">
            <div class="h-1.5 w-1.5 rounded-full bg-primary-500"></div>
            <h3 class="text-base font-bold text-slate-900">SMS Configuration</h3>
        </div>
        <form @submit.prevent="saveConfig()" class="max-w-lg space-y-4">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Cost Per Message (Rs)</label>
                <input type="number" x-model="smsCost" min="0.01" step="0.01"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                <template x-if="configError">
                    <p class="mt-1 text-xs text-primary-500" x-text="configError"></p>
                </template>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Sender ID</label>
                <input type="text" x-model="senderId"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                    placeholder="e.g. SMSlenzDEMO">
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
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
        <div class="flex items-center gap-2 mb-4">
            <div class="h-1.5 w-1.5 rounded-full bg-primary-500"></div>
            <h3 class="text-base font-bold text-slate-900">Transaction History</h3>
        </div>
        <?php if (empty($history)): ?>
            <div class="py-8 text-center">
                <svg class="mx-auto h-10 w-10 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-sm text-slate-400">No transactions recorded yet.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Date / Time</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($history as $txn): ?>
                            <tr class="transition hover:bg-slate-50/50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <p class="text-sm text-slate-500"><?= e(date('d M Y h:i A', strtotime($txn['updated_at'] ?? ''))) ?></p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-sm text-slate-700"><?= e($txn['value'] ?? '') ?></p>
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
