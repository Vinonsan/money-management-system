<?php
use Components\Base\Badge;
use Components\Base\DataTable;

/**
 * @var array  $members   Members for current page
 * @var int    $total     Total matching members
 * @var int    $page      Current page
 * @var int    $perPage   Per page
 * @var string $search
 * @var int    $locationId
 * @var int    $wardId
 * @var string $status    'all' | 'paid' | 'unpaid'
 * @var array  $locations
 * @var array  $wards
 */

$pageTitle = 'Members';
$startDate = \Models\Setting::getCollectionStartDate((int) ($_SESSION['user_id'] ?? 0));
$today = date('Y-m-d');

$locOpts = '<option value="0">All Locations</option>';
foreach ($locations as $loc) {
    $sel = $locationId === (int) $loc['id'] ? ' selected' : '';
    $locOpts .= '<option value="' . (int) $loc['id'] . '"' . $sel . '>' . htmlspecialchars($loc['name'] ?? '', ENT_QUOTES) . '</option>';
}
$wardOpts = '<option value="0">All Wards</option>';
foreach ($wards as $w) {
    $wid = (int) ($w['id'] ?? 0);
    $wn = (int) ($w['ward_number'] ?? 0);
    $sel = $wardId === $wid ? ' selected' : '';
    $wardOpts .= '<option value="' . $wid . '"' . $sel . '>Ward #' . $wn . '</option>';
}

$baseUrl = BASE_URL . '/admin/payments/members?status=' . urlencode($status);
if ($search) $baseUrl .= '&search=' . urlencode($search);
if ($locationId > 0) $baseUrl .= '&location_id=' . $locationId;
if ($wardId > 0) $baseUrl .= '&ward_id=' . $wardId;

$qsSearch = $search ? '&search=' . urlencode($search) : '';
$qsLoc = $locationId > 0 ? '&location_id=' . $locationId : '';
$qsWard = $wardId > 0 ? '&ward_id=' . $wardId : '';
$tabQs = $qsSearch . $qsLoc . $qsWard;
?>

<div class="mx-auto max-w-7xl space-y-6 py-2"
     x-data="memberManager()">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-2xl border border-slate-200/80 bg-white/60 p-6 shadow-sm backdrop-blur-md">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900"><?= e($pageTitle) ?></h1>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-bold text-slate-600"><?= (int) $total ?> Members</span>
            </div>
            <p class="mt-1 text-sm font-medium text-slate-500">View all members, filter by payment status.</p>
        </div>
        <div class="flex items-center gap-2">
            <div class="inline-flex rounded-lg border border-slate-200 bg-white p-0.5 shadow-sm">
                <a href="<?= BASE_URL ?>/admin/payments/members?status=unpaid<?= $tabQs ?>"
                   class="rounded-md px-3.5 py-1.5 text-xs font-semibold transition <?= $status === 'unpaid' ? 'bg-primary-700 text-white shadow-sm' : 'text-slate-600 hover:text-slate-800' ?>">Unpaid</a>
                <a href="<?= BASE_URL ?>/admin/payments/members?status=paid<?= $tabQs ?>"
                   class="rounded-md px-3.5 py-1.5 text-xs font-semibold transition <?= $status === 'paid' ? 'bg-primary-700 text-white shadow-sm' : 'text-slate-600 hover:text-slate-800' ?>">Paid</a>
                <a href="<?= BASE_URL ?>/admin/payments/members?status=all<?= $tabQs ?>"
                   class="rounded-md px-3.5 py-1.5 text-xs font-semibold transition <?= $status === 'all' ? 'bg-primary-700 text-white shadow-sm' : 'text-slate-600 hover:text-slate-800' ?>">All</a>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
        <div class="relative min-w-[180px] max-w-xs flex-1">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" x-model="search" @input.debounce.500ms="apply()"
                placeholder="Search by name or card..."
                class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/10">
        </div>
        <select x-model="locationId" @change="apply()"
            class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/10">
            <?= $locOpts ?>
        </select>
        <select x-model="wardId" @change="apply()"
            class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/10">
            <?= $wardOpts ?>
        </select>
        <button type="button" @click="reset()"
            class="rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-600 shadow-sm hover:bg-slate-50 hover:text-rose-600 transition">
            <svg class="inline h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Reset
        </button>
        <button type="button" @click="scheduleMessage()"
            class="ml-auto rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 transition">
            <svg class="inline h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            Schedule Message
        </button>
    </div>

    <?php
    echo DataTable::render([
        'columns' => [
            ['label' => 'Name', 'field' => 'name', 'sortable' => true],
            ['label' => 'Card', 'field' => 'card_number'],
            ['label' => 'Phone', 'field' => 'phone'],
            [
                'label' => 'Location',
                'field' => 'location_name',
                'format' => fn ($v) => $v ? htmlspecialchars($v, ENT_QUOTES) : '<span class="text-slate-300">\u2014</span>',
            ],
            [
                'label' => 'Ward',
                'field' => 'ward_number',
                'format' => fn ($v) => $v ? 'Ward #' . (int) $v : '<span class="text-slate-300">\u2014</span>',
            ],
            [
                'label' => 'Monthly',
                'field' => 'monthly_amount',
                'format' => fn ($v) => 'Rs. ' . number_format((float) ($v ?? 0), 2),
            ],
            [
                'label' => 'Status',
                'field' => null,
                'width' => '130px',
                'align' => 'center',
                'format' => function ($row) use ($today, $startDate) {
                    $lastPaid = $row['last_paid_to'] ?? '0000-00-00';
                    $isPaid = $lastPaid !== '0000-00-00' && $lastPaid >= $today;
                    if ($isPaid) return Badge::render('Paid', 'green', ['size' => 'xs']);

                    $nextDue = $lastPaid !== '0000-00-00'
                        ? date('Y-m-01', strtotime($lastPaid . ' +1 month'))
                        : date('Y-m-01', strtotime($startDate));
                    $dueTs = strtotime($nextDue);
                    $nowTs = strtotime(date('Y-m-01'));
                    $mp = 0;
                    while ($dueTs <= $nowTs) { $mp++; $dueTs = strtotime(date('Y-m-01', strtotime('+' . $mp . ' months', strtotime($nextDue)))); }

                    if ($mp > 0) return Badge::render($mp . ' Month' . ($mp > 1 ? 's' : '') . ' Due', 'red', ['size' => 'xs']);
                    return Badge::render('Pending', 'yellow', ['size' => 'xs']);
                },
            ],
            [
                'label' => 'Last Paid',
                'field' => 'last_paid_to',
                'format' => fn ($v) => $v && $v !== '0000-00-00' ? $v : '<span class="text-slate-300 italic">Never</span>',
            ],
            [
                'label' => 'Total Paid',
                'field' => 'total_paid',
                'format' => fn ($v) => 'Rs. ' . number_format((float) ($v ?? 0), 2),
            ],
        ],
        'rows' => $members,
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage,
        'search' => $search,
        'sortField' => $sortField ?? 'name',
        'sortDir' => $sortDir ?? 'asc',
        'baseUrl' => $baseUrl,
        'searchable' => false,
        'emptyMessage' => 'No members found. Try adjusting your filters.',
    ]);
    ?>

    <!-- Schedule Message Drawer -->
    <div id="schedule-drawer"
         x-show="scheduleDrawer"
         x-cloak
         class="fixed inset-0 z-[9999]">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-[2px] transition-opacity"
             @click="scheduleDrawer = false"
             x-show="scheduleDrawer"
             x-transition:enter="transition-opacity duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
        </div>

        <!-- Panel -->
        <div class="fixed inset-y-0 right-0 z-10 flex"
             x-show="scheduleDrawer"
             x-transition:enter="transition-transform duration-300 ease-out"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition-transform duration-200 ease-in"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full">
            <div class="flex h-full w-full max-w-lg flex-col bg-white shadow-2xl">
                <!-- Header -->
                <div class="flex shrink-0 items-center justify-between border-b border-slate-200 bg-white px-8 py-5">
                    <h3 class="text-lg font-bold text-slate-900">
                        <span class="text-primary-700">Schedule Message</span>
                    </h3>
                    <button type="button" @click="scheduleDrawer = false"
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="flex-1 overflow-y-auto bg-white px-8 py-6">
                    <div class="space-y-5">
                        <!-- Date Picker -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Schedule Date <span class="text-rose-500">*</span></label>
                            <input type="date"
                                   x-model="scheduleDate"
                                   :min="scheduleDateMin"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/10">
                        </div>

                        <!-- Time Picker -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Schedule Time
                                <span class="ml-1.5 text-xs font-normal text-slate-400">(optional)</span>
                            </label>
                            <input type="time"
                                   x-model="scheduleTime"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/10">
                            <p class="mt-2 text-xs text-slate-400">
                                Messages will be sent to all unpaid members on the selected date
                                <span x-show="scheduleTime">at <strong x-text="scheduleTime"></strong></span>.
                                <span x-show="!scheduleTime">If no time is set, messages go out as soon as the date arrives.</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-8 py-4">
                    <button type="button" @click="scheduleDrawer = false"
                        class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="button" @click="confirmSchedule()"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-primary-600/20 transition hover:bg-primary-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Schedule
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function memberManager() {
    return {
        search: '<?= htmlspecialchars($search, ENT_QUOTES) ?>',
        locationId: <?= $locationId ?>,
        wardId: <?= $wardId ?>,
        scheduleDrawer: false,
        scheduleDate: '',
        scheduleTime: '',
        scheduleDateMin: '',
        init() {
            const today = new Date();
            const y = today.getFullYear();
            const m = String(today.getMonth() + 1).padStart(2, '0');
            const d = String(today.getDate()).padStart(2, '0');
            this.scheduleDateMin = y + '-' + m + '-' + d;
            // Default to 20th of current month
            const defMonth = String(today.getMonth() + 1).padStart(2, '0');
            const defDay = '20';
            this.scheduleDate = y + '-' + defMonth + '-' + defDay;
        },
        apply() {
            const p = new URLSearchParams();
            p.set('status', '<?= $status ?>');
            if (this.search) p.set('search', this.search);
            if (this.locationId > 0) p.set('location_id', this.locationId);
            if (this.wardId > 0) p.set('ward_id', this.wardId);
            window.location.href = '/admin/payments/members?' + p.toString();
        },
        reset() {
            window.location.href = '/admin/payments/members';
        },
        scheduleMessage() {
            this.scheduleDrawer = true;
        },
        confirmSchedule() {
            if (!this.scheduleDate) {
                showToast('Please select a schedule date.', 'warning');
                return;
            }
            fetch(BASE_URL + '/admin/payments/schedule-message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    status: '<?= $status ?>',
                    search: this.search || '',
                    location_id: this.locationId,
                    ward_id: this.wardId,
                    schedule_date: this.scheduleDate,
                    schedule_time: this.scheduleTime,
                    _csrf: '<?= e($_SESSION['csrf_token'] ?? '') ?>',
                }),
            })
            .then(r => r.json())
            .then(r => {
                if (r.success) {
                    showToast(r.message);
                    this.scheduleDrawer = false;
                } else {
                    showToast(r.error || 'Failed.', 'error');
                }
            })
            .catch(() => showToast('Network error.', 'error'));
        }
    };
}
</script>
