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
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/img/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: <?= \themeTailwindColorsJs() ?> } };
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-primary-50/40 text-brand-charcoal antialiased">
    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="<?= APP_NAME ?>"
                     class="mx-auto h-16 w-auto mb-3">
                <h1 class="text-2xl font-bold text-primary-700"><?= APP_NAME ?></h1>
                <p class="text-sm text-brand-gray mt-1"><?= e($title) ?></p>
            </div>
            <div class="bg-white border border-primary-200 rounded-2xl shadow-lg shadow-brand-shadow/10 p-8">
                <?php require $contentView; ?>
            </div>
            <p class="mt-6 text-center text-xs text-brand-gray">
                <a href="<?= BASE_URL ?>/" class="hover:text-primary-700">← Back to home</a>
            </p>
        </div>
    </div>
</body>
</html>
<?php
}
