<?php
// src/Views/layouts/auth_layout.php
function renderAuthLayout(string $title, string $contentView, array $data = []): void
{
    extract($data);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> | <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gradient-to-br from-emerald-50 via-white to-slate-50 text-gray-800 antialiased">
    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <div class="mx-auto mb-4 w-12 h-12 rounded-xl bg-emerald-700 text-white flex items-center justify-center text-lg font-bold">M</div>
                <h1 class="text-2xl font-bold text-gray-900"><?= APP_NAME ?></h1>
                <p class="text-sm text-gray-500 mt-1"><?= e($title) ?></p>
            </div>
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-8">
                <?php require $contentView; ?>
            </div>
            <p class="mt-6 text-center text-xs text-gray-400">
                <a href="<?= BASE_URL ?>/" class="hover:text-emerald-800">← Back to home</a>
            </p>
        </div>
    </div>
</body>
</html>
<?php
}
