<?php $pageTitle = 'Refill Requests'; ?>
<div class="mx-auto max-w-5xl space-y-6 py-2" x-data="refillManager()">
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary-700 via-primary-800 to-primary-950 p-6 shadow-xl sm:p-8">
        <div class="relative">
            <h1 class="text-2xl font-bold text-white"><?= e($pageTitle) ?></h1>
            <p class="mt-1 text-sm text-white/70">Approve or reject refill requests from admins.</p>
        </div>
    </div>

    <?php if (empty($requests)): ?>
        <div class="rounded-2xl border border-primary-200/60 bg-white p-10 text-center shadow-sm">
            <p class="text-primary-400 font-medium">No refill requests yet.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($requests as $req):
                $status = $req['status'] ?? 'pending';
                $badge = match($status) {
                    'approved' => '<span class="inline-flex rounded-full bg-primary-100 px-3 py-1 text-xs font-semibold text-primary-700 ring-1 ring-primary-300">Approved</span>',
                    'rejected' => '<span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700 ring-1 ring-red-300">Rejected</span>',
                    default    => '<span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-300">Pending</span>',
                };
                $reqId = (int) ($req['id'] ?? 0);
                $amount = (float) ($req['amount'] ?? 0);
            ?>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-lg font-bold text-slate-900">Rs. <?= number_format($amount, 2) ?></span>
                            <?= $badge ?>
                        </div>
                        <p class="text-sm text-slate-500">
                            <strong class="text-slate-700"><?= e($req['admin_name'] ?? '') ?></strong>
                            (<?= e($req['admin_phone'] ?? '') ?>) —
                            <?= e(date('d M Y h:i A', strtotime($req['created_at'] ?? ''))) ?>
                        </p>
                        <?php if (!empty($req['message'])): ?>
                            <p class="mt-2 text-sm text-slate-600 bg-slate-50 rounded-lg px-3 py-2"><?= e($req['message']) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if ($status === 'pending'): ?>
                        <div class="flex items-center gap-2 shrink-0">
                            <button @click="approve(<?= $reqId ?>)"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                                Approve
                            </button>
                            <button @click="reject(<?= $reqId ?>)"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 transition">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Reject
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function refillManager() {
    return {
        approve(id) {
            if (!confirm('Approve this refill request?')) return;
            fetch(BASE_URL + '/super-admin/sms/approve-refill', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, action: 'approve', _csrf: '<?= e($_SESSION['csrf_token'] ?? '') ?>' }),
            })
            .then(r => r.json())
            .then(r => {
                showToast(r.message || 'Approved!');
                if (r.success) setTimeout(() => window.location.reload(), 1000);
            })
            .catch(() => showToast('Network error.', 'error'));
        },
        reject(id) {
            if (!confirm('Reject this refill request?')) return;
            fetch(BASE_URL + '/super-admin/sms/approve-refill', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, action: 'reject', _csrf: '<?= e($_SESSION['csrf_token'] ?? '') ?>' }),
            })
            .then(r => r.json())
            .then(r => {
                showToast(r.message || 'Rejected.');
                if (r.success) setTimeout(() => window.location.reload(), 1000);
            })
            .catch(() => showToast('Network error.', 'error'));
        },
    };
}
</script>
