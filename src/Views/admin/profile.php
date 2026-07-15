<?php
$userName  = $_SESSION['user_name'] ?? '';
$userEmail = $user['email'] ?? '';
$userPhone = $_SESSION['user_phone'] ?? ($user['phone'] ?? '');
$userRole  = $_SESSION['user_role'] ?? '';
$userInitial = strtoupper(substr($userName !== '' ? $userName : 'U', 0, 1));
$avatarUrl = $avatarUrl ?? null;
$uploadError = $uploadError ?? null;
$uploadSuccess = $uploadSuccess ?? null;
?>
<div class="mx-auto max-w-2xl space-y-6">

    <?php if ($uploadError): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= $uploadError ?></div>
    <?php endif; ?>
    <?php if ($uploadSuccess): ?>
        <div class="rounded-lg border border-primary-200 bg-primary-50 px-4 py-3 text-sm text-primary-800"><?= e($uploadSuccess) ?></div>
    <?php endif; ?>

    <!-- Profile Info -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-gradient-to-r from-primary-700 to-primary-800 px-6 py-8">
            <div class="flex items-center gap-4">
                <?php if ($avatarUrl): ?>
                    <img src="<?= e($avatarUrl) ?>" alt="Avatar"
                         class="h-16 w-16 rounded-2xl object-cover ring-2 ring-white/30">
                <?php else: ?>
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/15 text-2xl font-bold text-white ring-2 ring-white/30">
                        <?= e($userInitial) ?>
                    </div>
                <?php endif; ?>
                <div>
                    <h2 class="text-xl font-bold text-white"><?= e($userName) ?></h2>
                    <p class="mt-0.5 text-sm capitalize text-primary-100"><?= e(str_replace('_', ' ', $userRole)) ?></p>
                </div>
            </div>
        </div>
        <div class="space-y-4 px-6 py-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Email</p>
                    <p class="mt-1 text-sm font-medium text-slate-800"><?= e($userEmail !== '' ? $userEmail : '—') ?></p>
                </div>
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Phone</p>
                    <p class="mt-1 text-sm font-medium text-slate-800"><?= e($userPhone !== '' ? $userPhone : '—') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Avatar -->
    <div class="rounded-2xl border border-primary-200/60 bg-white p-6 shadow-sm shadow-primary-100/20">
        <div class="flex items-center gap-2 mb-5">
            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-primary-100 text-primary-700">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </span>
            <h3 class="text-base font-bold text-primary-800">Profile Picture</h3>
        </div>

        <!-- Current Avatar Preview -->
        <div class="mb-5 flex items-center gap-4 rounded-xl bg-primary-50/50 p-4 border border-primary-100">
            <?php if ($avatarUrl): ?>
                <img src="<?= e($avatarUrl) ?>" alt="Avatar"
                     class="h-20 w-20 rounded-xl object-cover ring-2 ring-primary-200 shadow-sm">
            <?php else: ?>
                <div class="flex h-20 w-20 items-center justify-center rounded-xl bg-gradient-to-br from-primary-600 to-primary-800 text-3xl font-bold text-white ring-2 ring-primary-200 shadow-sm">
                    <?= e($userInitial) ?>
                </div>
            <?php endif; ?>
            <div>
                <p class="text-sm font-semibold text-primary-800"><?= e($userName) ?></p>
                <p class="text-xs text-primary-500"><?= $avatarUrl ? 'Click below to change photo' : 'No profile photo set' ?></p>
            </div>
        </div>

        <form method="post" action="<?= BASE_URL ?>/admin/profile/upload-avatar" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-primary-800 mb-1.5">Choose New Image</label>
                <div class="relative">
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" required
                           class="block w-full rounded-lg border border-primary-200 bg-primary-50/30 text-sm text-primary-700 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-600 file:text-white hover:file:bg-primary-700 transition file:cursor-pointer cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                </div>
                <p class="mt-1 text-xs text-primary-400">JPG, PNG, GIF or WebP. Max 2MB.</p>
            </div>
            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-primary-600/20 hover:bg-primary-700 hover:shadow-lg transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Upload Picture
            </button>
        </form>
    </div>
</div>
