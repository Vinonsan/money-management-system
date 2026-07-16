<?php
/**
 * @var array $admins  List of admin users
 * @var int   $total   Total admin count
 * @var int   $page    Current page number
 * @var int   $perPage Items per page
 */
$pageTitle = 'Manage Admins';
?>

<div class="mx-auto max-w-7xl space-y-6 py-2"
     x-data="adminManager()">

    <!-- Header -->
    <div class="relative flex flex-col justify-between gap-4 overflow-hidden rounded-2xl bg-gradient-to-br from-primary-700 via-primary-800 to-primary-950 p-6 shadow-xl sm:flex-row sm:items-center sm:p-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.08),transparent_60%)]"></div>
        <div class="relative">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-white"><?= e($pageTitle) ?></h1>
                <span class="rounded-full bg-white/15 px-3 py-1 text-sm font-bold text-white backdrop-blur-sm"><?= (int) $total ?> Admins</span>
            </div>
            <p class="mt-1 text-sm font-medium text-white/70">Create and manage administrator accounts.</p>
        </div>
        <button type="button" @click="openAddDrawer()"
            class="relative inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-primary-700 shadow-lg transition-all hover:bg-primary-50 hover:scale-105 active:scale-95">
            <svg class="h-4 w-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Admin
        </button>
    </div>

    <!-- Admins List -->
    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xl shadow-slate-100/50">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Name</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Business</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Phone</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Email</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Created</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($admins)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <p class="text-sm text-slate-400">No admins found. Click "Add Admin" to create one.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($admins as $admin): ?>
                            <?php
                            $id = (int) ($admin['id'] ?? 0);
                            $name = htmlspecialchars($admin['name'] ?? '', ENT_QUOTES);
                            $businessName = htmlspecialchars($admin['business_name'] ?? '', ENT_QUOTES);
                            $email = htmlspecialchars($admin['email'] ?? '', ENT_QUOTES);
                            $phone = htmlspecialchars($admin['phone'] ?? '', ENT_QUOTES);
                            $isActive = !empty($admin['is_active']);
                            $created = htmlspecialchars($admin['created_at'] ?? '', ENT_QUOTES);
                            $rowJson = htmlspecialchars(json_encode($admin), ENT_COMPAT, 'UTF-8');
                            ?>
                            <tr class="transition hover:bg-slate-50/50">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-slate-800"><?= $name ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-slate-600"><?= $businessName ?: '<span class="text-slate-300 italic">—</span>' ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-slate-600"><?= $phone ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-slate-600"><?= $email ?: '<span class="text-slate-300 italic">—</span>' ?></p>
                                </td>
                                    <p class="text-sm text-slate-600"><?= $phone ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($isActive): ?>
                                        <span class="inline-flex rounded-full bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-700 ring-1 ring-inset ring-primary-600/20">Active</span>
                                    <?php else: ?>
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-slate-500"><?= date('d M Y', strtotime($created)) ?></p>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="inline-flex items-center justify-center gap-1 bg-slate-50 p-1 rounded-xl border border-slate-100">
                                        <button type="button" @click="openEditDrawer(<?= $rowJson ?>)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors" title="Edit">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <button type="button" @click="openDeleteModal(<?= $id ?>, '<?= htmlspecialchars($name, ENT_COMPAT, 'UTF-8') ?>')"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Delete">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total > $perPage): ?>
            <?php
            $totalPages = (int) ceil($total / $perPage);
            ?>
            <div class="flex items-center justify-between border-t border-slate-100 px-6 py-4">
                <p class="text-sm text-slate-500">
                    Showing <strong class="text-slate-700"><?= (($page - 1) * $perPage) + 1 ?></strong> to <strong class="text-slate-700"><?= min($page * $perPage, $total) ?></strong> of <strong class="text-slate-700"><?= $total ?></strong>
                </p>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50 transition">Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50 transition">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- ─── Add / Edit Drawer ──────────────────────────────── -->
    <?php
    echo \Components\Drawer\Drawer::render('admin-drawer', 'Admin Form', '
        <div class="space-y-5">
            <div>
                <label class="block text-sm font-semibold text-primary-800 mb-1.5">Full Name <span class="text-primary-500">*</span></label>
                <input type="text" x-model="form.name"
                    class="w-full rounded-lg border border-primary-200 bg-primary-50/30 px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20 focus:bg-white transition"
                    placeholder="e.g. John Doe">
                <template x-if="errors.name">
                    <p class="mt-1 text-xs text-primary-500" x-text="errors.name"></p>
                </template>
            </div>
            <div>
                <label class="block text-sm font-semibold text-primary-800 mb-1.5">Business Name</label>
                <input type="text" x-model="form.business_name"
                    class="w-full rounded-lg border border-primary-200 bg-primary-50/30 px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20 focus:bg-white transition"
                    placeholder="e.g. MasjidPay Owner">
            </div>
            <div>
                <label class="block text-sm font-semibold text-primary-800 mb-1.5">Email</label>
                <input type="email" x-model="form.email"
                    class="w-full rounded-lg border border-primary-200 bg-primary-50/30 px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20 focus:bg-white transition"
                    placeholder="admin@example.com">
            </div>
            <div>
                <label class="block text-sm font-semibold text-primary-800 mb-1.5">Phone <span class="text-primary-500">*</span></label>
                <input type="text" x-model="form.phone"
                    class="w-full rounded-lg border border-primary-200 bg-primary-50/30 px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20 focus:bg-white transition"
                    placeholder="e.g. +94 77 123 4567">
                <template x-if="errors.phone">
                    <p class="mt-1 text-xs text-primary-500" x-text="errors.phone"></p>
                </template>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Password
                    <span class="text-xs font-normal text-slate-400" x-text="isEditing ? \'(leave blank to keep current)\' : \'(* required)\'"></span>
                </label>
                <input type="password" x-model="form.password"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                    placeholder="Enter password">
            </div>
        </div>
        <div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
            <button type="button" @click="drawer = \'\'"
                class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Cancel</button>
            <button type="button" @click="submit()"
                class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-primary-600/20 transition-all hover:bg-primary-700 hover:shadow-lg hover:shadow-primary-600/30 active:scale-[0.97]">
                <template x-if="!saving">
                    <span x-text="isEditing ? \'Update Admin\' : \'Create Admin\'"></span>
                </template>
                <template x-if="saving">
                    <span>Saving...</span>
                </template>
            </button>
        </div>
    ');
    ?>

    <!-- ─── Delete Confirmation Modal ─────────────────────── -->
    <?php
    echo \Components\Modal\Modal::confirm('delete-admin-modal', 'Delete Admin', [
        'body' => '<p class="text-sm text-slate-600">Are you sure you want to delete admin <strong class="text-slate-900" x-text="deleteTarget?.name || \'\'"></strong>?</p>
            <p class="mt-2 text-xs text-red-500">This action cannot be undone.</p>',
        'confirmText' => 'Delete',
        'confirmVariant' => 'danger',
        'confirmAction' => 'confirmDelete()',
        'size' => 'sm',
    ]);
    ?>
</div>

<script>
function adminManager() {
    return {
        drawer: '',
        modal: '',
        form: { id: null, name: '', business_name: '', email: '', phone: '', password: '' },
        errors: {},
        isEditing: false,
        saving: false,
        deleting: false,
        deleteTarget: null,

        openAddDrawer() {
            this.form = { id: null, name: '', email: '', phone: '', password: '' };
            this.errors = {};
            this.isEditing = false;
            this.drawer = 'admin-drawer';
        },

        openEditDrawer(admin) {
            this.form = {
                id: admin.id || null,
                name: admin.name || '',
                email: admin.email || '',
                phone: admin.phone || '',
                password: '',
            };
            this.errors = {};
            this.isEditing = true;
            this.drawer = 'admin-drawer';
        },

        submit() {
            this.errors = {};
            this.saving = true;

            const name = this.form.name.trim();
            const phone = this.form.phone.trim();
            let hasError = false;

            if (!name) {
                this.errors['name'] = 'Name is required.';
                hasError = true;
            }
            if (!phone) {
                this.errors['phone'] = 'Phone is required.';
                hasError = true;
            }
            if (!this.isEditing && !this.form.password) {
                this.errors['password'] = 'Password is required for new admin.';
                hasError = true;
            }

            if (hasError) {
                this.saving = false;
                return;
            }

            const payload = {
                id: this.form.id,
                name: name,
                business_name: this.form.business_name.trim() || null,
                email: this.form.email.trim() || null,
                phone: phone,
                password: this.form.password,
                _csrf: '<?= e($_SESSION['csrf_token'] ?? '') ?>',
            };

            const isEdit = !!this.form.id;
            const url = BASE_URL + (isEdit ? '/super-admin/admins/update' : '/super-admin/admins/create');

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(payload),
            })
            .then(r => r.json())
            .then(r => {
                this.saving = false;
                if (r.success) {
                    showToast(r.message || 'Admin saved!');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    const err = (r.error || '').toLowerCase();
                    if (err.includes('name')) this.errors['name'] = r.error;
                    else if (err.includes('phone')) this.errors['phone'] = r.error;
                    else showToast(r.error || 'Unable to save admin.', 'error');
                }
            })
            .catch(() => {
                this.saving = false;
                showToast('Network error. Please try again.', 'error');
            });
        },

        openDeleteModal(id, name) {
            this.deleteTarget = { id, name };
            this.modal = 'delete-admin-modal';
        },

        confirmDelete() {
            const id = this.deleteTarget?.id;
            if (!id) return;

            this.deleting = true;

            fetch(BASE_URL + '/super-admin/admins/delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ id, _csrf: '<?= e($_SESSION['csrf_token'] ?? '') ?>' }),
            })
            .then(r => r.json())
            .then(r => {
                this.deleting = false;
                if (r.success) {
                    showToast(r.message || 'Admin deleted.');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast(r.error || 'Unable to delete admin.', 'error');
                }
            })
            .catch(() => {
                this.deleting = false;
                showToast('Network error.', 'error');
            });
        },
    };
}
</script>
