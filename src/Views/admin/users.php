<?php
use Components\Base\DataTable;
use Components\Base\Input;
use Components\Base\Select;
use Components\Drawer\Drawer;

$pageTitle = 'Users';
$searchVal = htmlspecialchars($search, ENT_QUOTES, 'UTF-8');

// ─── Location options for filter & form ────────────────────────────────
$locOptions = [];
foreach ($locations as $loc) {
    $locOptions[(int) $loc['id']] = htmlspecialchars($loc['name'] ?? '', ENT_QUOTES);
}
$locFilterOpts = '<option value="0">All Streets</option>';
foreach ($locOptions as $id => $name) {
    $sel = $locationId === $id ? ' selected' : '';
    $locFilterOpts .= '<option value="' . $id . '"' . $sel . '>' . $name . '</option>';
}

// ─── Ward options for filter & form ────────────────────────────────────
$wardOpts = [];
foreach ($wards as $w) {
    $wid = (int) ($w['id'] ?? 0);
    $wn = (int) ($w['ward_number'] ?? 0);
    $locNames = htmlspecialchars($w['location_names'] ?? '', ENT_QUOTES);
    $wardOpts[$wid] = 'Ward #' . $wn . ($locNames ? ' (' . $locNames . ')' : '');
}
$wardFilterOpts = '<option value="0">All Wards</option>';
foreach ($wardOpts as $id => $label) {
    $sel = $wardId === $id ? ' selected' : '';
    $wardFilterOpts .= '<option value="' . $id . '"' . $sel . '>' . $label . '</option>';
}

$columns = [
    ['label' => 'Name', 'field' => 'name', 'sortable' => true],
    ['label' => 'Phone', 'field' => 'phone', 'sortable' => true],
    [
        'label' => 'Street',
        'field' => 'location_name',
        'format' => fn ($v) => $v ? htmlspecialchars($v, ENT_QUOTES) : '<span class="text-slate-300 italic">\u2014</span>',
    ],
    [
        'label' => 'Ward',
        'field' => 'ward_number',
        'format' => fn ($v) => $v ? 'Ward #' . (int) $v : '<span class="text-slate-300 italic">\u2014</span>',
    ],
    [
        'label' => 'Monthly (Rs)',
        'field' => 'monthly_amount',
        'sortable' => true,
        'format' => fn ($v) => '<span class="font-semibold text-slate-700">Rs. ' . number_format((float) ($v ?? 0), 2) . '</span>',
    ],
    [
        'label' => 'Status',
        'field' => 'is_active',
        'sortable' => true,
        'format' => static fn ($v): string => (int) $v === 1
            ? '<span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Active</span>'
            : '<span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Inactive</span>',
    ],
];
?>

<div class="mx-auto max-w-7xl space-y-6 py-2">
    <div class="flex flex-col justify-between gap-4 rounded-2xl border border-slate-200/80 bg-white/60 p-6 shadow-sm backdrop-blur-md sm:flex-row sm:items-center">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">User Management</h1>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-bold text-emerald-700"><?= (int) $total ?> Users</span>
            </div>
            <p class="mt-1 text-sm font-medium text-slate-500">Add and manage registered users.</p>
        </div>
        <button type="button" @click="drawerData = {}; drawerMode = 'add'; drawer = 'user-drawer'"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-600/20 transition-all hover:bg-emerald-700">
            <svg class="h-4 w-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add User
        </button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xl shadow-slate-100/50">
        <!-- Filter bar -->
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 bg-slate-50/30 px-6 py-4"
             x-data="userFilters('<?= $searchVal ?>', <?= $locationId ?>, <?= $wardId ?>)">

            <div class="relative min-w-[180px] max-w-xs flex-1">
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" x-model="search" @input.debounce.500ms="apply()"
                    placeholder="Search by name or phone..."
                    class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm text-slate-700 placeholder-slate-400 transition-all focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/10">
            </div>

            <select x-model="locationId" @change="apply()"
                class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 font-medium focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/10">
                <?= $locFilterOpts ?>
            </select>

            <select x-model="wardId" @change="apply()"
                class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 font-medium focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/10">
                <?= $wardFilterOpts ?>
            </select>

            <button type="button" @click="reset()"
                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-rose-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span>Reset</span>
            </button>

            <span class="ml-auto whitespace-nowrap text-xs font-medium text-slate-500">
                <strong class="text-slate-700"><?= $total ?></strong> user<?= $total !== 1 ? 's' : '' ?>
            </span>
        </div>

        <?php
        echo DataTable::render([
            'columns' => $columns,
            'rows' => $users,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'search' => $search,
            'sortField' => $sortField,
            'sortDir' => $sortDir,
            'baseUrl' => '/admin/users',
            'searchable' => false,
            'emptyMessage' => 'No users found. Click "Add User" to create one.',
        ]);
        ?>
    </div>
</div>

<script>
function userFilters(initialSearch, initialLoc, initialWard) {
    return {
        search: initialSearch,
        locationId: initialLoc,
        wardId: initialWard,
        apply() {
            const params = new URLSearchParams();
            if (this.search) params.set('search', this.search);
            if (this.locationId > 0) params.set('location_id', this.locationId);
            if (this.wardId > 0) params.set('ward_id', this.wardId);
            window.location.href = '/admin/users' + (params.toString() ? '?' + params.toString() : '');
        },
        reset() {
            window.location.href = '/admin/users';
        }
    };
}

function submitUser() {
    const data = Alpine.$data(document.querySelector('[x-data]'));
    const formData = {
        name: (data.drawerData.name || '').trim(),
        phone: (data.drawerData.phone || '').trim(),
        location_id: data.drawerData.location_id || null,
        ward_id: data.drawerData.ward_id || null,
        monthly_amount: parseFloat(data.drawerData.monthly_amount) || 0,
        _csrf: '<?= e($_SESSION['csrf_token'] ?? '') ?>',
    };

    if (!formData.name || !formData.phone) {
        alert('Name and phone number are required.');
        return;
    }

    fetch('/admin/users/create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(formData),
    })
    .then(response => response.json())
    .then(response => {
        if (response.success) {
            window.location.reload();
        } else {
            alert(response.error || 'Unable to add user.');
        }
    })
    .catch(() => alert('Network error. Please try again.'));
}
</script>

<?php
$drawerBody = <<<HTML
<form id="user-form" @submit.prevent="submitUser()">
    <input type="hidden" name="id" x-model="drawerData.id">
    <div class="space-y-5 py-2">
HTML
. Input::render('name', [
    'label' => 'Full Name',
    'required' => true,
    'placeholder' => 'Enter full name',
    'attrs' => ['x-model' => 'drawerData.name'],
]) .
Input::render('phone', [
    'label' => 'Phone Number',
    'type' => 'tel',
    'required' => true,
    'placeholder' => 'e.g. 0771234567',
    'attrs' => ['x-model' => 'drawerData.phone'],
]) .
Select::render('location_id', [
    'label' => 'Street / Location',
    'required' => false,
    'options' => $locOptions,
    'placeholder' => 'Select street...',
    'attrs' => ['x-model' => 'drawerData.location_id'],
]) .
Select::render('ward_id', [
    'label' => 'Ward',
    'required' => false,
    'options' => $wardOpts,
    'placeholder' => 'Select ward...',
    'attrs' => ['x-model' => 'drawerData.ward_id'],
]) .
Input::render('monthly_amount', [
    'label' => 'Monthly Amount (Rs)',
    'type' => 'number',
    'required' => false,
    'placeholder' => 'e.g. 500.00',
    'attrs' => ['x-model' => 'drawerData.monthly_amount', 'min' => '0', 'step' => '0.01'],
]) .
<<<HTML
    </div>
</form>
HTML;

$drawerFooter = <<<HTML
<div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-white px-6 py-4">
    <button type="button" @click="drawer = ''" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Cancel</button>
    <button type="button" @click="submitUser()" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-600/20 transition hover:bg-emerald-700">Save User</button>
</div>
HTML;

echo Drawer::render('user-drawer', 'User Details', $drawerBody, [
    'side' => 'right',
    'size' => 'lg',
    'footer' => $drawerFooter,
]);
?>
