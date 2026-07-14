<?php
use Components\Base\Input;

/**
 * @var string  $startDate    Current collection start date (Y-m-d)
 * @var string  $smsUserId    SMSlenz User ID
 * @var string  $smsApiKey    SMSlenz API Key
 * @var string  $smsSenderId  SMSlenz Sender ID
 * @var string  $smsCost      Cost per SMS (Rs.)
 * @var string  $smsBalance   Local SMS balance
 * @var float|null $liveBalance Live balance from API
 * @var string|null $livePlan Active plan name
 * @var bool    $smsConfigured Whether SMS is configured
 */

$pageTitle = 'System Configuration';
?>

<div class="mx-auto max-w-3xl space-y-6 py-2">
    <div class="rounded-2xl border border-slate-200/80 bg-white/60 p-6 shadow-sm backdrop-blur-md">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900"><?= e($pageTitle) ?></h1>
            <p class="mt-1 text-sm font-medium text-slate-500">Configure system-wide settings for the collection system.</p>
        </div>
    </div>

    <!-- ─── Collection Start Date ──────────────────────────── -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
        <form id="config-form" @submit.prevent="saveConfig()" class="space-y-6"
              x-data="configForm()">

            <div class="flex items-start gap-4 p-4 rounded-xl bg-amber-50 border border-amber-200">
                <svg class="h-5 w-5 shrink-0 mt-0.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <div>
                    <h3 class="font-semibold text-amber-800">Collection Start Date</h3>
                    <p class="mt-1 text-sm text-amber-700">
                        This date determines when money collection history begins for all users.
                        New users added to the system will have their payment history start from this date.
                        You can edit individual user start dates later if needed.
                    </p>
                </div>
            </div>

            <div class="max-w-xs">
                <?= Input::render('collection_start_date', [
                    'label' => 'Collection Start Date',
                    'type' => 'date',
                    'required' => true,
                    'value' => htmlspecialchars($startDate, ENT_QUOTES),
                    'attrs' => ['x-model' => 'startDate'],
                ]) ?>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-600/20 transition hover:bg-emerald-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save Configuration
                </button>
            </div>
        </form>
    </div>

    <!-- ─── SMSlenz Configuration ──────────────────────────── -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
        <form id="sms-config-form" @submit.prevent="saveSmsConfig()" class="space-y-6"
              x-data="smsForm()">

            <div class="flex items-start gap-4 p-4 rounded-xl bg-blue-50 border border-blue-200">
                <svg class="h-5 w-5 shrink-0 mt-0.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                </svg>
                <div>
                    <h3 class="font-semibold text-blue-800">SMSlenz Gateway</h3>
                    <p class="mt-1 text-sm text-blue-700">
                        Configure your <a href="https://smslenz.lk" target="_blank" class="underline">SMSlenz.lk</a> API credentials.
                        Get your <strong>User ID</strong> and <strong>API Key</strong> from
                        <a href="https://smslenz.lk/account/api-key" target="_blank" class="underline">smslenz.lk/account/api-key</a>.
                    </p>
                </div>
            </div>

            <!-- Connection status -->
            <div class="flex items-center gap-3 px-4">
                <div class="flex items-center gap-2">
                    <span class="inline-block h-2.5 w-2.5 rounded-full <?= $smsConfigured ? 'bg-green-500' : 'bg-red-400' ?>"></span>
                    <span class="text-sm font-medium text-slate-600"><?= $smsConfigured ? 'Connected' : 'Not configured' ?></span>
                </div>
                <?php if ($liveBalance !== null): ?>
                    <span class="text-xs text-slate-400">|</span>
                    <span class="text-sm text-slate-600">Live Balance: <strong>Rs. <?= number_format($liveBalance, 2) ?></strong></span>
                    <?php if ($livePlan): ?>
                        <span class="text-xs text-slate-400">(<?= e($livePlan) ?>)</span>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($smsConfigured): ?>
                    <button type="button" @click="syncBalance()"
                        class="ml-auto text-xs font-medium text-blue-600 hover:text-blue-800 transition">
                        ↻ Sync Balance
                    </button>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?= Input::render('smslenz_user_id', [
                    'label' => 'SMSlenz User ID',
                    'type' => 'text',
                    'required' => true,
                    'value' => htmlspecialchars($smsUserId, ENT_QUOTES),
                    'placeholder' => 'e.g. 1234',
                    'attrs' => ['x-model' => 'smsUserId'],
                ]) ?>

                <?= Input::render('smslenz_api_key', [
                    'label' => 'API Key',
                    'type' => 'password',
                    'required' => true,
                    'value' => htmlspecialchars($smsApiKey, ENT_QUOTES),
                    'placeholder' => 'e.g. xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
                    'attrs' => ['x-model' => 'smsApiKey'],
                ]) ?>

                <?= Input::render('smslenz_sender_id', [
                    'label' => 'Sender ID',
                    'type' => 'text',
                    'required' => true,
                    'value' => htmlspecialchars($smsSenderId, ENT_QUOTES),
                    'placeholder' => 'SMSlenzDEMO',
                    'attrs' => ['x-model' => 'smsSenderId'],
                ]) ?>

                <?= Input::render('sms_cost_per_message', [
                    'label' => 'Cost Per Message (Rs.)',
                    'type' => 'number',
                    'required' => true,
                    'value' => htmlspecialchars($smsCost, ENT_QUOTES),
                    'placeholder' => '0.62',
                    'attrs' => ['x-model' => 'smsCost', 'step' => '0.01', 'min' => '0.01'],
                ]) ?>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-blue-600/20 transition hover:bg-blue-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save SMS Configuration
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function configForm() {
    return {
        startDate: '<?= htmlspecialchars($startDate, ENT_QUOTES) ?>',
        saveConfig() {
            if (!this.startDate) {
                showToast('Please select a start date.', 'warning');
                return;
            }

            fetch(BASE_URL + '/admin/system-config/save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({
                    collection_start_date: this.startDate,
                    _csrf: '<?= e($_SESSION['csrf_token'] ?? '') ?>',
                }),
            })
            .then(r => r.json())
            .then(r => {
                if (r.success) {
                    showToast('Configuration saved successfully.');
                } else {
                    showToast(r.error || 'Failed to save configuration.', 'error');
                }
            })
            .catch(() => showToast('Network error.', 'error'));
        }
    };
}

function smsForm() {
    return {
        smsUserId: '<?= htmlspecialchars($smsUserId, ENT_QUOTES) ?>',
        smsApiKey: '<?= htmlspecialchars($smsApiKey, ENT_QUOTES) ?>',
        smsSenderId: '<?= htmlspecialchars($smsSenderId, ENT_QUOTES) ?>',
        smsCost: '<?= htmlspecialchars($smsCost, ENT_QUOTES) ?>',
        saveSmsConfig() {
            if (!this.smsUserId) { showToast('SMSlenz User ID is required.', 'warning'); return; }
            if (!this.smsApiKey) { showToast('API Key is required.', 'warning'); return; }
            if (!this.smsSenderId) { showToast('Sender ID is required.', 'warning'); return; }
            if (!this.smsCost || parseFloat(this.smsCost) <= 0) { showToast('Valid cost per message is required.', 'warning'); return; }

            fetch(BASE_URL + '/admin/system-config/sms-save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({
                    smslenz_user_id: this.smsUserId,
                    smslenz_api_key: this.smsApiKey,
                    smslenz_sender_id: this.smsSenderId,
                    sms_cost_per_message: this.smsCost,
                    _csrf: '<?= e($_SESSION['csrf_token'] ?? '') ?>',
                }),
            })
            .then(r => r.json())
            .then(r => {
                if (r.success) {
                    showToast('SMS configuration saved. Balance synced.');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(r.error || 'Failed to save.', 'error');
                }
            })
            .catch(() => showToast('Network error.', 'error'));
        },
        syncBalance() {
            fetch(BASE_URL + '/admin/system-config/sms-save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({
                    smslenz_user_id: this.smsUserId,
                    smslenz_api_key: this.smsApiKey,
                    smslenz_sender_id: this.smsSenderId,
                    sms_cost_per_message: this.smsCost,
                    _csrf: '<?= e($_SESSION['csrf_token'] ?? '') ?>',
                }),
            })
            .then(r => r.json())
            .then(r => {
                if (r.success) {
                    showToast('Balance synced with SMSlenz.');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(r.error || 'Sync failed.', 'error');
                }
            })
            .catch(() => showToast('Network error.', 'error'));
        }
    };
}
</script>
