<?php
use Components\Base\Badge;
use Components\Base\Input;
use Components\Base\MultiSelect;
use Components\Base\Select;
use Components\Drawer\Drawer;
use Components\Modal\Modal;

/**
 * @var array  $wards     List of ward rows
 * @var int    $total     Total records
 * @var int    $page      Current page
 * @var int    $perPage   Records per page
 * @var array  $locations List of active locations for dropdown
 */

$pageTitle = 'Ward Management';

$iconEye   = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>';
$iconEdit  = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>';
$iconTrash = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>';
?>

<div class="space-y-6 max-w-7xl mx-auto py-2">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white/60 backdrop-blur-md p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900"><?= e($pageTitle) ?></h1>
            <p class="mt-1 text-sm text-slate-500 font-medium">Manage wards under each location.</p>
        </div>
        <button type="button"
                @click="drawerData = { location_ids: [], is_active: 1 }; drawerMode = 'add'; drawer = 'ward-drawer'"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-primary-600/20 transition-all hover:bg-primary-700 hover:shadow-primary-600/30 active:scale-[0.98]">
            <svg class="h-4 w-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Add Ward</span>
        </button>
    </div>

    <?php if (empty($wards)): ?>
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <div class="h-16 w-16 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-300 mb-4">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-slate-500">No wards found</p>
            <p class="mt-1 text-xs text-slate-400">Click "Add Ward" to create one.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php foreach ($wards as $ward):
                $id = (int) ($ward['id'] ?? 0);
                $locationNames = htmlspecialchars($ward['location_names'] ?? '', ENT_QUOTES);
                $isActive = !empty($ward['is_active']);
                $statusBadge = $isActive
                    ? Badge::render('Active', 'green', ['size' => 'xs'])
                    : Badge::render('Inactive', 'red', ['size' => 'xs']);
                $rowJsonEsc = htmlspecialchars(json_encode($ward), ENT_COMPAT, 'UTF-8');
            ?>
            <div class="group relative rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition-all hover:shadow-md hover:border-primary-200/80 hover:-translate-y-0.5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <h3 class="text-base font-semibold text-slate-900 truncate">Ward - <?= htmlspecialchars((string) ($ward['ward_number'] ?? ''), ENT_QUOTES) ?></h3>
                        <?php if ($locationNames !== ''): ?>
                            <p class="mt-0.5 text-xs text-slate-400 truncate"><?= $locationNames ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="shrink-0"><?= $statusBadge ?></div>
                </div>

                <div class="mt-4 flex items-center gap-1.5 pt-3 border-t border-slate-100">
                    <button type="button" onclick="event.stopPropagation(); openDrawer('view', <?= $rowJsonEsc ?>)"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-lg bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500 transition hover:bg-primary-50 hover:text-primary-700" title="View">
                        <?= $iconEye ?>
                        <span>View</span>
                    </button>
                    <button type="button" onclick="event.stopPropagation(); openDrawer('edit', <?= $rowJsonEsc ?>)"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-lg bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500 transition hover:bg-blue-50 hover:text-blue-600" title="Edit">
                        <?= $iconEdit ?>
                        <span>Edit</span>
                    </button>
                    <button type="button" onclick="event.stopPropagation(); openDeleteModal(<?= $id ?>, <?= htmlspecialchars(json_encode('Ward ' . ($ward['ward_number'] ?? '')), ENT_QUOTES) ?>)"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-lg bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500 transition hover:bg-rose-50 hover:text-rose-600" title="Delete">
                        <?= $iconTrash ?>
                        <span>Delete</span>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php
        $totalPages = max(1, (int) ceil($total / $perPage));
        ?>
        <?php if ($totalPages > 1): ?>
        <div class="flex items-center justify-between gap-4 mt-6 pt-4 border-t border-slate-100">
            <p class="text-xs text-slate-500 font-medium">
                Showing <strong class="text-slate-700"><?= (($page - 1) * $perPage + 1) ?></strong>
                - <strong class="text-slate-700"><?= min($page * $perPage, $total) ?></strong>
                of <strong class="text-slate-700"><?= $total ?></strong>
            </p>
            <div class="flex items-center gap-1.5">
                <?php if ($page > 1): ?>
                    <a href="<?= BASE_URL ?>/admin/wards?page=<?= $page - 1 ?>"
                       class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-slate-600 shadow-sm hover:bg-slate-50 transition-all">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++):
                    $active = $i === $page;
                    $activeClass = $active
                        ? 'bg-primary-600 text-white font-semibold shadow-sm shadow-primary-600/30'
                        : 'text-slate-600 hover:bg-slate-100';
                ?>
                    <a href="<?= BASE_URL ?>/admin/wards?page=<?= $i ?>"
                       class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-xs transition-all <?= $activeClass ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="<?= BASE_URL ?>/admin/wards?page=<?= $page + 1 ?>"
                       class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-slate-600 shadow-sm hover:bg-slate-50 transition-all">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$locOptions = [];
foreach ($locations as $loc) {
    $locOptions[(int) $loc['id']] = htmlspecialchars($loc['name'] ?? '', ENT_QUOTES);
}

$drawerBody = <<<HTML
<form id="ward-form" @submit.prevent="submitWard()">
    <input type="hidden" name="id" x-model="drawerData.id">

    <div class="space-y-5 py-2">
        <!-- 1. Ward Number -->
        <div x-show="drawerMode !== 'view'">
            <label class="block mb-1.5 text-sm font-semibold text-primary-800">Ward Number <span class="text-rose-500">*</span></label>
            <input type="text" name="ward_number" x-model="drawerData.ward_number" required maxlength="100"
                placeholder="e.g. 1, A, North"
                :class="'w-full rounded-lg border bg-primary-50/30 px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 transition focus:outline-none focus:ring-2 focus:bg-white ' + (wardError ? 'border-red-300 focus:border-red-500 focus:ring-red-500/30' : 'border-primary-200 focus:border-primary-600 focus:ring-primary-600/30')">
            <p x-show="wardError" x-text="wardError" class="mt-1 text-xs font-medium text-red-600"></p>
        </div>
        <div x-show="drawerMode === 'view'">
HTML
. Input::render('ward_number_view', [
    'label' => 'Ward Number',
    'value' => '',
    'disabled' => true,
    'attrs' => ['x-model' => 'drawerData.ward_number'],
]) .
<<<HTML
        </div>

        <!-- 2. Location -->
        <div x-show="drawerMode !== 'view'">
HTML
. MultiSelect::render('location_ids', [
    'label' => 'Locations',
    'required' => true,
    'options' => $locOptions,
    'model' => 'drawerData.location_ids',
    'placeholder' => 'Select locations...',
    'help' => 'Choose one or more locations.',
]) .
<<<HTML
        </div>
        <div x-show="drawerMode === 'view'">
HTML
. Input::render('locations_view', [
    'label' => 'Locations',
    'value' => '',
    'disabled' => true,
    'attrs' => ['x-model' => 'drawerData.location_names'],
]) .
<<<HTML
        </div>

        <!-- 3. Status -->
        <div x-show="drawerMode !== 'view'">
HTML
. Select::render('is_active', [
    'label' => 'Status',
    'options' => [1 => 'Active', 0 => 'Inactive'],
    'attrs' => ['x-model' => 'drawerData.is_active', ':disabled' => "drawerMode === 'view'"],
]) .
<<<HTML
        </div>
        <div x-show="drawerMode === 'view'">
HTML
. Select::render('is_active_view', [
    'label' => 'Status',
    'options' => [1 => 'Active', 0 => 'Inactive'],
    'disabled' => true,
    'attrs' => ['x-model' => 'drawerData.is_active'],
]) .
<<<HTML
        </div>

        <template x-if="drawerMode === 'view' && drawerData.created_at">
            <div class="rounded-xl bg-slate-50 border border-slate-200/80 p-4 text-xs text-slate-500 space-y-2">
                <div class="flex justify-between"><span class="font-semibold text-slate-600">Created:</span> <span x-text="drawerData.created_at"></span></div>
                <div class="flex justify-between"><span class="font-semibold text-slate-600">Last Updated:</span> <span x-text="drawerData.updated_at"></span></div>
            </div>
        </template>
    </div>
</form>
HTML;

$drawerFooter = <<<HTML
<div class="flex items-center justify-end gap-3 bg-white px-6 py-4 border-t border-slate-100">
    <button type="button" @click="drawer = ''"
        class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
        Cancel
    </button>
    <button type="button" x-show="drawerMode !== 'view'" :disabled="wardSaving"
            @click="submitWard()"
        class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-primary-600/20 transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60">
        <span x-text="wardSaving ? 'Saving...' : (drawerMode === 'edit' ? 'Update Ward' : 'Save Ward')"></span>
    </button>
</div>
HTML;

echo Drawer::render('ward-drawer', 'Ward Details', $drawerBody, [
    'side' => 'right',
    'size' => 'lg',
    'footer' => $drawerFooter,
]);
?>

<?php
echo Modal::confirm('delete-ward', 'Delete Ward', [
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
    'confirmAction' => 'deleteWard()',
    'size' => 'sm',
]);
?>

<script>
function getAlpine() {
    return Alpine.$data(document.querySelector('[x-data]'));
}
function openDrawer(mode, data) {
    const app = getAlpine();
    data.location_ids = data.location_ids ? data.location_ids.split(',') : [];
    app.drawerData = data;
    app.drawerMode = mode;
    app.drawer = 'ward-drawer';
}
function openDeleteModal(id, name) {
    const app = getAlpine();
    app.modalData = { id: id, name: name };
    app.modal = 'delete-ward';
}

function submitWard() {
    const data = Alpine.$data(document.querySelector('[x-data]'));
    if (data.wardSaving) return;
    data.wardError = '';

    const wardNum = String(data.drawerData.ward_number || '').trim();
    if (!wardNum || wardNum.length > 100) {
        data.wardError = 'Ward name/number is required (maximum 100 characters).';
        return;
    }

    const locIds = data.drawerData.location_ids || [];
    if (!locIds.length) {
        data.wardError = 'Please select at least one location.';
        return;
    }

    const formData = {
        id: data.drawerData.id || null,
        location_ids: locIds,
        ward_number: wardNum,
        is_active: data.drawerData.is_active !== undefined
            ? (Number(data.drawerData.is_active) === 1 ? 1 : 0)
            : 1,
        _csrf: '<?= e($_SESSION['csrf_token'] ?? '') ?>',
    };

    const isEdit = !!formData.id;
    const url = BASE_URL + (isEdit ? '/admin/wards/update' : '/admin/wards/create');
    data.wardSaving = true;

    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(formData),
    })
    .then(async response => {
        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            throw new Error(response.redirected
                ? 'Your session expired. Please sign in again.'
                : 'The server returned an invalid response.');
        }
        const result = await response.json();
        if (response.status === 401) {
            showToast(result.error || 'Your session expired. Please sign in again.', 'error');
            setTimeout(() => {
                window.location.href = BASE_URL + '/admin/login';
            }, 1200);
            return null;
        }
        if (!response.ok && !result.error) {
            throw new Error('Ward could not be saved.');
        }
        return result;
    })
    .then(r => {
        if (!r) return;
        if (r.success) {
            showToast(r.message || 'Ward saved!');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            const err = (r.error || '').toLowerCase();
            if (err.includes('ward number') || err.includes('already exists')) {
                data.wardError = r.error;
            } else {
                showToast(r.error || 'Something went wrong.', 'error');
            }
        }
    })
    .catch(error => showToast(error.message || 'Network error. Please try again.', 'error'))
    .finally(() => {
        data.wardSaving = false;
    });
}

function multiSelect(id, placeholderText) {
    return {
        open: false,
        placeholder: placeholderText,
        toggle() { this.open = !this.open; },
        get selectedText() {
            const root = document.querySelector('[x-data]');
            if (!root) return this.placeholder;
            try {
                const data = Alpine.$data(root);
                const ids = data.drawerData?.location_ids;
                if (!Array.isArray(ids) || ids.length === 0) return this.placeholder;
                // Get checkbox labels from the dropdown
                const panel = document.getElementById(id)?.closest('.multi-select-component');
                if (panel) {
                    const labels = [];
                    panel.querySelectorAll('input[type="checkbox"]:checked').forEach(cb => {
                        const span = cb.closest('label')?.querySelector('span');
                        labels.push(span?.textContent?.trim() || cb.value);
                    });
                    if (labels.length > 0) return labels.join(', ');
                }
                return ids.length + ' selected';
            } catch (e) {
                return this.placeholder;
            }
        }
    };
}

function deleteWard() {
    const data = Alpine.$data(document.querySelector('[x-data]'));
    const id = data.modalData?.id;
    if (!id) return;

    fetch(BASE_URL + '/admin/wards/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ id, _csrf: '<?= e($_SESSION['csrf_token'] ?? '') ?>' }),
    })
    .then(r => r.json())
    .then(r => {
        if (r.success) {
            showToast(r.message || 'Ward deleted!');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast(r.error || 'Something went wrong.', 'error');
        }
    })
    .catch(() => showToast('Network error.', 'error'));
}
</script>
