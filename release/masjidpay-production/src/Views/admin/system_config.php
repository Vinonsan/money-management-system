<?php
use Components\Base\Input;

/**
 * @var string  $startDate    Current collection start date (Y-m-d)
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
                    class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-primary-600/20 transition hover:bg-primary-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save Configuration
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
</script>
