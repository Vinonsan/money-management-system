<?php
use Components\Base\Badge;
use Components\Base\DataTable;
use Components\Base\Input;
use Components\Base\Select;
use Components\Drawer\Drawer;
use Components\Modal\Modal;

$pageTitle = 'Members';
$searchVal = htmlspecialchars($search, ENT_QUOTES, 'UTF-8');

// ─── Location options for filter & form ────────────────────────────────
$locOptions = [];
foreach ($locations as $loc) {
    $locOptions[(int) $loc['id']] = htmlspecialchars($loc['name'] ?? '', ENT_QUOTES);
}
$locFilterOpts = '<option value="0">All Locations</option>';
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

// ─── Ward → Location mapping for auto-select ──────────────────────────
$wardLocMap = [];
foreach ($wards as $w) {
    $wid = (int) ($w['id'] ?? 0);
    $ids = $w['location_ids'] ?? '';
    $wardLocMap[$wid] = $ids !== '' ? array_map('intval', explode(',', $ids)) : [];
}
$wardLocJson = json_encode($wardLocMap, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$locNamesJson = json_encode($locOptions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

// ─── Build wards JSON for Alpine autocomplete ────────────────────────
$wardJsonData = [];
foreach ($wards as $w) {
    $wardJsonData[] = [
        'id' => (int) ($w['id'] ?? 0),
        'ward_number' => (int) ($w['ward_number'] ?? 0),
        'location_names' => htmlspecialchars($w['location_names'] ?? '', ENT_QUOTES),
        'location_ids' => $w['location_ids'] ?? '',
    ];
}
$wardJson = json_encode($wardJsonData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

// ─── Pre-build ward options HTML for heredoc (fallback) ──────────────
$wardOptsHtml = '';
foreach ($wardOpts as $id => $label) {
    $wardOptsHtml .= '<option value="' . $id . '">' . $label . '</option>';
}
// ─── Build locations JSON for Alpine filtering ───────────────────────
$locJsonData = [];
foreach ($locOptions as $id => $name) {
    $locJsonData[] = ['id' => $id, 'name' => $name];
}
$locJson = json_encode($locJsonData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

// ─── Shared Icon SVGs ─────────────────────────────────────────────────
$iconEye   = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>';
$iconEdit  = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>';
$iconTrash = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>';

$columns = [
    ['label' => 'Name', 'field' => 'name', 'sortable' => true],
    [
        'label' => 'Monthly (Rs)',
        'field' => 'monthly_amount',
        'sortable' => true,
        'format' => fn ($v) => '<span class="font-semibold text-slate-700">Rs. ' . number_format((float) ($v ?? 0), 2) . '</span>',
    ],
    ['label' => 'Phone', 'field' => 'phone', 'sortable' => true],
    [
        'label' => 'Card',
        'field' => 'card_number',
        'format' => fn ($v) => $v ? '<span class="font-mono font-semibold text-slate-700">' . (int) $v . '</span>' : '<span class="text-slate-300 italic">\u2014</span>',
    ],
    [
        'label' => 'Ward',
        'field' => 'ward_number',
        'format' => fn ($v) => $v ? 'Ward #' . (int) $v : '<span class="text-slate-300 italic">\u2014</span>',
    ],
    [
        'label' => 'Location',
        'field' => 'location_name',
        'format' => fn ($v) => $v ? htmlspecialchars($v, ENT_QUOTES) : '<span class="text-slate-300 italic">\u2014</span>',
    ],
    [
        'label' => 'Action',
        'field' => null,
        'width' => '160px',
        'align' => 'center',
        'format' => function ($row) use ($iconEye, $iconEdit, $iconTrash) {
            $id = (int) ($row['id'] ?? 0);
            $name = htmlspecialchars($row['name'] ?? '', ENT_QUOTES);
            $rowJsonEsc = htmlspecialchars(json_encode($row), ENT_COMPAT, 'UTF-8');

            $viewBtn = '<button type="button" onclick="event.stopPropagation(); openDrawer(\'view\', ' . $rowJsonEsc . ')" class="p-1.5 rounded-lg text-slate-400 hover:text-primary-600 hover:bg-primary-50 transition-colors" title="View">' . $iconEye . '</button>';
            $editBtn = '<button type="button" onclick="event.stopPropagation(); openDrawer(\'edit\', ' . $rowJsonEsc . ')" class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors" title="Edit">' . $iconEdit . '</button>';
            $deleteBtn = '<button type="button" onclick="event.stopPropagation(); openDeleteModal(' . $id . ', \'' . htmlspecialchars($name, ENT_COMPAT, 'UTF-8') . '\')" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Delete">' . $iconTrash . '</button>';

            return '<div class="inline-flex items-center justify-center gap-1 bg-slate-50 p-1 rounded-xl border border-slate-100">' . $viewBtn . $editBtn . $deleteBtn . '</div>';
        },
    ],
];
?>

<div class="mx-auto max-w-7xl space-y-6 py-2">
    <div class="flex flex-col justify-between gap-4 rounded-2xl border border-slate-200/80 bg-white/60 p-6 shadow-sm backdrop-blur-md sm:flex-row sm:items-center">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Member Management</h1>
                <span class="rounded-full bg-primary-50 px-3 py-1 text-sm font-bold text-primary-700"><?= (int) $total ?> Members</span>
            </div>
            <p class="mt-1 text-sm font-medium text-slate-500">Add and manage registered members.</p>
        </div>
        <button type="button" onclick="openAddMember()"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-primary-600/20 transition-all hover:bg-primary-700">
            <svg class="h-4 w-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Member
        </button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xl shadow-slate-100/50">
        <!-- Filter bar -->
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 bg-slate-50/30 px-6 py-4"
             x-data="memberFilters('<?= $searchVal ?>', <?= $locationId ?>, <?= $wardId ?>)">

            <div class="relative min-w-[180px] max-w-xs flex-1">
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" x-model="search" @input.debounce.500ms="apply()"
                    placeholder="Search by name or phone..."
                    class="w-full rounded-lg border border-primary-200 bg-primary-50/30 py-2 pl-9 pr-3 text-sm text-slate-700 placeholder-slate-400 transition-all focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20 focus:bg-white">
            </div>

            <select x-model="locationId" @change="apply()"
                class="rounded-lg border border-primary-200 bg-primary-50/30 px-3 py-2 text-sm text-slate-700 font-medium focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20 focus:bg-white">
                <?= $locFilterOpts ?>
            </select>

            <select x-model="wardId" @change="apply()"
                class="rounded-lg border border-primary-200 bg-primary-50/30 px-3 py-2 text-sm text-slate-700 font-medium focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20 focus:bg-white">
                <?= $wardFilterOpts ?>
            </select>

            <button type="button" @click="reset()"
                class="inline-flex items-center gap-1.5 rounded-lg border border-primary-200 bg-white px-3.5 py-2 text-sm font-semibold text-primary-600 shadow-sm transition hover:bg-primary-50 hover:text-primary-800">
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
            'rows' => $members,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'search' => $search,
            'sortField' => $sortField,
            'sortDir' => $sortDir,
            'baseUrl' => '/admin/members',
            'searchable' => false,
            'emptyMessage' => 'No members found. Click "Add Member" to create one.',
        ]);
        ?>
    </div>
</div>

<script>
const wardLocMap = <?= $wardLocJson ?>;
const locNames = <?= $locNamesJson ?>;
const rawLocs = <?= $locJson ?>;
const rawWards = <?= $wardJson ?>;
window.wardLocMap = wardLocMap;
window.rawLocs = rawLocs;
window.rawWards = rawWards;

function getAlpine() {
    return Alpine.$data(document.querySelector('[x-data]'));
}
function openDrawer(mode, data) {
    const app = getAlpine();
    // Clear errors and reset ward
    app.memberErrors = {};
    app.drawerData = data;
    app.drawerMode = mode;
    app.drawer = 'member-drawer';
    // Reset location when ward changes (handled by onWardChange on select)
}

async function openAddMember() {
    const app = getAlpine();
    app.memberErrors = {};
    app.drawerData = {};
    app.drawerMode = 'add';

    try {
        const response = await fetch(BASE_URL + '/admin/members/form-options', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const result = await response.json();
        if (!result.success) throw new Error('Unable to load member options.');

        window.rawWards = result.wards || [];
        window.rawLocs = result.locations || [];
        window.wardLocMap = {};

        result.wards.forEach(ward => {
            const ids = String(ward.location_ids || '')
                .split(',')
                .filter(Boolean)
                .map(Number);
            window.wardLocMap[ward.id] = ids;
        });

        const wardSelect = document.getElementById('member-ward-select');
        wardSelect.replaceChildren(new Option('Select ward...', ''));
        result.wards.forEach(ward => {
            const locations = ward.location_names ? ' (' + ward.location_names + ')' : '';
            wardSelect.add(new Option('Ward #' + ward.ward_number + locations, String(ward.id)));
        });

        const locationSelect = document.getElementById('member-location-select');
        locationSelect.replaceChildren(new Option('Select location...', ''));
        result.locations.forEach(location => {
            locationSelect.add(new Option(location.name, String(location.id)));
        });
    } catch (error) {
        showToast('Could not refresh wards. Showing the currently loaded list.', 'warning');
    }

    app.drawer = 'member-drawer';
}

function openDeleteModal(id, name) {
    const app = getAlpine();
    app.modalData = { id: id, name: name };
    app.modal = 'delete-member';
}

function memberFilters(initialSearch, initialLoc, initialWard) {
    return {
        search: initialSearch,
        locationId: initialLoc,
        wardId: initialWard,
        apply() {
            const params = new URLSearchParams();
            if (this.search) params.set('search', this.search);
            if (this.locationId > 0) params.set('location_id', this.locationId);
            if (this.wardId > 0) params.set('ward_id', this.wardId);
            window.location.href = '/admin/members' + (params.toString() ? '?' + params.toString() : '');
        },
        reset() {
            window.location.href = '/admin/members';
        }
    };
}

function submitMember() {
    const data = Alpine.$data(document.querySelector('[x-data]'));
    data.memberErrors = {};

    const name = (data.drawerData.name || '').trim();
    const phone = (data.drawerData.phone || '').trim();
    let hasError = false;

    if (!name) {
        data.memberErrors['name'] = 'Name is required.';
        hasError = true;
    }
    if (!phone) {
        data.memberErrors['phone'] = 'Phone number is required.';
        hasError = true;
    } else if (!/^[+0-9][+0-9()\- ]{6,19}$/.test(phone)) {
        data.memberErrors['phone'] = 'Enter a valid phone number.';
        hasError = true;
    }
    if (!data.drawerData.ward_id) {
        data.memberErrors['ward_id'] = 'Please select a ward.';
        hasError = true;
    }

    if (hasError) return;

    const formData = {
        id: data.drawerData.id || null,
        name: name,
        phone: phone,
        card_number: parseInt(data.drawerData.card_number) || null,
        location_id: data.drawerData.location_id || null,
        ward_id: data.drawerData.ward_id || null,
        monthly_amount: parseFloat(data.drawerData.monthly_amount) || 0,
        _csrf: '<?= e($_SESSION['csrf_token'] ?? '') ?>',
    };

    const baseUrl = '<?= BASE_URL ?>';
    const isEdit = !!formData.id;
    const url = baseUrl + (isEdit ? '/admin/members/update' : '/admin/members/create');

    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(formData),
    })
    .then(response => response.json())
    .then(response => {
        if (response.success) {
            showToast(response.message || 'User saved!');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            const err = (response.error || '').toLowerCase();
            if (err.includes('name')) data.memberErrors['name'] = response.error;
            else if (err.includes('phone')) data.memberErrors['phone'] = response.error;
            else showToast(response.error || 'Unable to save user.', 'error');
        }
    })
    .catch(() => showToast('Network error. Please try again.', 'error'));
}

function onWardChange() {
    const data = Alpine.$data(document.querySelector('[x-data]'));
    const wardId = data.drawerData.ward_id;
    const map = window.wardLocMap || {};
    if (wardId && map[wardId] && map[wardId].length > 0) {
        data.drawerData.location_id = map[wardId][0]; // auto-select first location
    } else {
        data.drawerData.location_id = null;
    }
}

function deleteMember() {
    const data = Alpine.$data(document.querySelector('[x-data]'));
    const id = data.modalData?.id;
    if (!id) return;

    fetch(BASE_URL + '/admin/members/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ id, _csrf: '<?= e($_SESSION['csrf_token'] ?? '') ?>' }),
    })
    .then(r => r.json())
    .then(r => {
        if (r.success) {
            showToast(r.message || 'User deleted!');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast(r.error || 'Something went wrong.', 'error');
        }
    })
    .catch(() => showToast('Network error.', 'error'));
}
</script>

<?php
echo Modal::confirm('delete-member', 'Delete Member', [
    'body' => <<<HTML
    <div class="flex items-start gap-4 p-2">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600 border border-rose-100">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
        </div>
        <div>
            <h4 class="font-semibold text-slate-900">Confirm Deletion</h4>
            <p class="mt-1 text-sm text-slate-500 leading-relaxed">
                Are you sure you want to delete <strong class="text-slate-800 font-semibold" x-text="modalData.name"></strong>? This record will be permanently removed.
            </p>
        </div>
    </div>
    HTML,
    'confirmText' => 'Yes, Delete',
    'confirmVariant' => 'danger',
    'confirmAction' => 'deleteMember()',
    'size' => 'sm',
]);

$drawerBody = <<<HTML
<form id="user-form" @submit.prevent="submitMember()">
    <input type="hidden" name="id" x-model="drawerData.id">
    <div class="space-y-5 py-2">
        <!-- 1. Name -->
        <div x-show="drawerMode !== 'view'">
HTML
. Input::render('name', [
    'label' => 'Full Name <span class="text-rose-500">*</span>',
    'required' => true,
    'placeholder' => 'Enter full name',
    'attrs' => ['x-model' => 'drawerData.name', ':disabled' => "drawerMode === 'view'"],
]) .
<<<HTML
            <p x-show="memberErrors?.name" x-text="memberErrors.name" class="mt-1 text-xs font-medium text-red-600"></p>
        </div>
        <div x-show="drawerMode === 'view'">
HTML
. Input::render('name_view', [
    'label' => 'Full Name',
    'value' => '',
    'disabled' => true,
    'attrs' => ['x-model' => 'drawerData.name'],
]) .
<<<HTML
        </div>

        <!-- 2. Monthly Amount -->
        <div x-show="drawerMode !== 'view'">
HTML
. Input::render('monthly_amount', [
    'label' => 'Monthly Amount (Rs)',
    'type' => 'number',
    'placeholder' => 'e.g. 500.00',
    'attrs' => ['x-model' => 'drawerData.monthly_amount', ':disabled' => "drawerMode === 'view'", 'min' => '0', 'step' => '0.01'],
]) .
<<<HTML
        </div>
        <div x-show="drawerMode === 'view'">
HTML
. Input::render('monthly_view', [
    'label' => 'Monthly Amount (Rs)',
    'value' => '',
    'disabled' => true,
    'attrs' => [':value' => "'Rs. ' + (parseFloat(drawerData.monthly_amount || 0).toFixed(2))"],
]) .
<<<HTML
        </div>

        <!-- 3. Phone -->
        <div x-show="drawerMode !== 'view'">
HTML
. Input::render('phone', [
    'label' => 'Phone Number <span class="text-rose-500">*</span>',
    'type' => 'tel',
    'required' => true,
    'placeholder' => 'e.g. 0771234567',
    'attrs' => ['x-model' => 'drawerData.phone', ':disabled' => "drawerMode === 'view'"],
]) .
<<<HTML
            <p x-show="memberErrors?.phone" x-text="memberErrors.phone" class="mt-1 text-xs font-medium text-red-600"></p>
        </div>
        <div x-show="drawerMode === 'view'">
HTML
. Input::render('phone_view', [
    'label' => 'Phone Number',
    'value' => '',
    'disabled' => true,
    'attrs' => ['x-model' => 'drawerData.phone'],
]) .
<<<HTML
        </div>

        <!-- 4. Card Number -->
        <div x-show="drawerMode !== 'view'">
HTML
. Input::render('card_number', [
    'label' => 'Card Number',
    'required' => false,
    'placeholder' => 'e.g. 1001',
    'attrs' => ['x-model' => 'drawerData.card_number', ':disabled' => "drawerMode === 'view'"],
]) .
<<<HTML
        </div>
        <div x-show="drawerMode === 'view'">
HTML
. Input::render('card_number_view', [
    'label' => 'Card Number',
    'value' => '',
    'disabled' => true,
    'attrs' => ['x-model' => 'drawerData.card_number'],
]) .
<<<HTML
        </div>

        <!-- 5. Ward -->
        <div x-show="drawerMode !== 'view'">
            <label class="block mb-1.5 text-sm font-semibold text-primary-800">Ward <span class="text-rose-500">*</span></label>
            <select id="member-ward-select" x-model="drawerData.ward_id" @change="onWardChange()" :disabled="drawerMode === 'view'" required
                :class="'w-full rounded-lg border bg-primary-50/30 px-3 py-2.5 text-sm text-slate-800 transition focus:outline-none focus:ring-2 appearance-none focus:bg-white ' + (memberErrors?.ward_id ? 'border-red-300 focus:border-red-500 focus:ring-red-500/30' : 'border-primary-200 focus:border-primary-600 focus:ring-primary-600/30')">
                <option value="">Select ward...</option>
                <?= $wardOptsHtml ?>
            </select>
            <p x-show="memberErrors?.ward_id" x-text="memberErrors.ward_id" class="mt-1 text-xs font-medium text-red-600"></p>
        </div>
        <div x-show="drawerMode === 'view'">
HTML
. Input::render('ward_view', [
    'label' => 'Ward',
    'value' => '',
    'disabled' => true,
    'attrs' => [':value' => "'Ward #' + (drawerData.ward_number || '')"],
]) .
<<<HTML
        </div>

        <!-- 6. Location -->
        <div x-show="drawerMode !== 'view'">
            <label class="block mb-1.5 text-sm font-semibold text-primary-800">Location</label>
            <select id="member-location-select" x-model="drawerData.location_id" :disabled="drawerMode === 'view'"
                :class="'w-full rounded-lg border bg-primary-50/30 px-3 py-2.5 text-sm text-slate-800 transition focus:outline-none focus:ring-2 appearance-none focus:bg-white border-primary-200 focus:border-primary-600 focus:ring-primary-600/30'">
                <option value="">Select location...</option>
                <template x-for="loc in filteredLocs" :key="loc.id">
                    <option :value="loc.id" x-text="loc.name"></option>
                </template>
            </select>
        </div>
        <div x-show="drawerMode === 'view'">
HTML
. Input::render('location_view', [
    'label' => 'Location',
    'value' => '',
    'disabled' => true,
    'attrs' => ['x-model' => 'drawerData.location_name'],
]) .
<<<HTML
        </div>

        <template x-if="drawerMode === 'view' && drawerData.created_at">
            <div class="rounded-xl bg-slate-50 border border-slate-200/80 p-4 text-xs text-slate-500 space-y-2">
                <div class="flex justify-between"><span class="font-semibold text-slate-600">Created:</span> <span x-text="drawerData.created_at"></span></div>
                <div class="flex justify-between"><span class="font-semibold text-slate-600">Updated:</span> <span x-text="drawerData.updated_at"></span></div>
            </div>
        </template>
    </div>
</form>
HTML;

$drawerFooter = <<<HTML
<div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-white px-6 py-4">
    <button type="button" @click="drawer = ''" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Cancel</button>
    <button type="button" x-show="drawerMode !== 'view'" @click="submitMember()" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-primary-600/20 transition hover:bg-primary-700">
        <span x-text="drawerMode === 'edit' ? 'Update Member' : 'Save Member'"></span>
    </button>
</div>
HTML;

echo Drawer::render('member-drawer', 'Member Details', $drawerBody, [
    'side' => 'right',
    'size' => 'lg',
    'footer' => $drawerFooter,
]);
?>
