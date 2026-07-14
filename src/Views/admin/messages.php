<?php
/**
 * @var string $msgConfirm Current confirmation message template
 * @var string $msgDue     Current due reminder message template
 */

$pageTitle = 'Message Configuration';
?>

<div class="mx-auto max-w-3xl space-y-6 py-2">
    <div class="rounded-2xl border border-slate-200/80 bg-white/60 p-6 shadow-sm backdrop-blur-md">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900"><?= e($pageTitle) ?></h1>
            <p class="mt-1 text-sm font-medium text-slate-500">Configure SMS message templates for payment notifications.</p>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
        <form id="msg-form" @submit.prevent="saveMessages()" class="space-y-6"
              x-data="msgForm()">

            <!-- Confirmation Message -->
            <div class="rounded-xl border border-primary-100 bg-primary-50/50 p-5">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 shrink-0 mt-0.5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="flex-1">
                        <h3 class="font-semibold text-primary-800">Confirmation Message</h3>
                        <p class="mt-1 text-sm text-primary-600">Sent to a member after they make a payment.</p>
                        <div class="mt-3">
                            <textarea x-model="msgConfirm" maxlength="150" rows="3"
                                class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/10"
                                placeholder="Dear [Name], Rs.[Amount] paid for [Period]. Next due: [NextDue]. Total paid: Rs.[TotalPaid]. Thank you!"></textarea>
                            <div class="mt-1.5 flex items-center justify-between">
                                <span class="text-xs text-slate-400">Available: <code class="rounded bg-slate-100 px-1">[Name]</code> <code class="rounded bg-slate-100 px-1">[Amount]</code> <code class="rounded bg-slate-100 px-1">[Period]</code> <code class="rounded bg-slate-100 px-1">[MonthlyAmount]</code> <code class="rounded bg-slate-100 px-1">[NextDue]</code> <code class="rounded bg-slate-100 px-1">[PaidUpTo]</code> <code class="rounded bg-slate-100 px-1">[TotalPaid]</code> <code class="rounded bg-slate-100 px-1">[ExtraAmount]</code> <code class="rounded bg-slate-100 px-1">[MonthsCovered]</code></span>
                                <span class="text-xs font-medium" x-text="msgConfirm.length + '/150'" :class="msgConfirm.length > 150 ? 'text-red-500' : 'text-slate-400'"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Due Reminder Message -->
            <div class="rounded-xl border border-amber-100 bg-amber-50/50 p-5">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 shrink-0 mt-0.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="flex-1">
                        <h3 class="font-semibold text-amber-800">Due Reminder Message</h3>
                        <p class="mt-1 text-sm text-amber-600">Sent to unpaid members as a payment reminder.</p>
                        <div class="mt-3">
                            <textarea x-model="msgDue" maxlength="150" rows="3"
                                class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/10"
                                placeholder="Dear [Name], your monthly contribution of Rs.[MonthlyAmount] for [Month] is due. Kindly pay before [Date]."></textarea>
                            <div class="mt-1.5 flex items-center justify-between">
                                <span class="text-xs text-slate-400">Available: <code class="rounded bg-slate-100 px-1">[Name]</code> <code class="rounded bg-slate-100 px-1">[Amount]</code> <code class="rounded bg-slate-100 px-1">[MonthlyAmount]</code> <code class="rounded bg-slate-100 px-1">[Month]</code> <code class="rounded bg-slate-100 px-1">[Date]</code></span>
                                <span class="text-xs font-medium" x-text="msgDue.length + '/150'" :class="msgDue.length > 150 ? 'text-red-500' : 'text-slate-400'"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-primary-600/20 transition hover:bg-primary-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save Templates
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function msgForm() {
    return {
        msgConfirm: '<?= htmlspecialchars($msgConfirm, ENT_QUOTES) ?>',
        msgDue: '<?= htmlspecialchars($msgDue, ENT_QUOTES) ?>',
        saveMessages() {
            if (!this.msgConfirm || !this.msgDue) {
                showToast('Both message templates are required.', 'warning');
                return;
            }
            if (this.msgConfirm.length > 150 || this.msgDue.length > 150) {
                showToast('Messages must be 150 characters or less.', 'warning');
                return;
            }

            fetch(BASE_URL + '/admin/system-config/messages/save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({
                    msg_confirmation: this.msgConfirm,
                    msg_due: this.msgDue,
                    _csrf: '<?= e($_SESSION['csrf_token'] ?? '') ?>',
                }),
            })
            .then(r => r.json())
            .then(r => {
                if (r.success) {
                    showToast(r.message);
                } else {
                    showToast(r.error || 'Failed to save.', 'error');
                }
            })
            .catch(() => showToast('Network error.', 'error'));
        }
    };
}
</script>
