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
$startDate = \Models\Setting::get('collection_start_date', date('Y-m-d'));
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

$baseUrl = '/admin/payments/members?status=' . urlencode($status);
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
                <a href="/admin/payments/members?status=unpaid<?= $tabQs ?>"
                   class="rounded-md px-3.5 py-1.5 text-xs font-semibold transition <?= $status === 'unpaid' ? 'bg-rose-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-800' ?>">Unpaid</a>
                <a href="/admin/payments/members?status=paid<?= $tabQs ?>"
                   class="rounded-md px-3.5 py-1.5 text-xs font-semibold transition <?= $status === 'paid' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-800' ?>">Paid</a>
                <a href="/admin/payments/members?status=all<?= $tabQs ?>"
                   class="rounded-md px-3.5 py-1.5 text-xs font-semibold transition <?= $status === 'all' ? 'bg-slate-700 text-white shadow-sm' : 'text-slate-600 hover:text-slate-800' ?>">All</a>
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
                class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/10">
        </div>
        <select x-model="locationId" @change="apply()"
            class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/10">
            <?= $locOpts ?>
        </select>
        <select x-model="wardId" @change="apply()"
            class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/10">
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
            class="ml-auto rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 transition">
            <svg class="inline h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            Schedule Message
        </button>
    </div>

    <?php
    echo DataTable::render([
        'columns' => [
            [
                'label' => '#',
                'field' => null,
                'width' => '50px',
                'align' => 'center',
                'format' => function ($row) {
                    return '<input type="checkbox" value="' . (int) ($row['id'] ?? 0) . '"
                        class="member-checkbox rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                        style="accent-color: #059669;">';
                },
            ],
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
</div>

<script>
function memberManager() {
    return {
        search: '<?= htmlspecialchars($search, ENT_QUOTES) ?>',
        locationId: <?= $locationId ?>,
        wardId: <?= $wardId ?>,
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
            const checked = document.querySelectorAll('.member-checkbox:checked');
            const ids = Array.from(checked).map(cb => parseInt(cb.value)).filter(id => id > 0);
            if (ids.length === 0) {
                alert('Please select at least one member.');
                return;
            }
            if (!confirm('Send SMS to ' + ids.length + ' selected member(s)?')) return;

            fetch('/admin/payments/schedule-message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    user_ids: ids,
                    type: '<?= $status === 'paid' ? "reminder" : "due" ?>',
                    _csrf: '<?= e($_SESSION['csrf_token'] ?? '') ?>',
                }),
            })
            .then(r => r.json())
            .then(r => {
                if (r.success) alert(r.message);
                else alert(r.error || 'Failed.');
            })
            .catch(() => alert('Network error.'));
        }
    };
}
</script>
