<?php
namespace Components\Layouts;

final class AuthLayout
{
    public static function render(string $title, string $content): void
    {
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> | <?= \APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-gray-50 text-gray-800 antialiased">
    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <!-- Brand -->
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900"><?= \APP_NAME ?></h1>
                <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($title) ?></p>
            </div>
            <!-- Card -->
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-8">
                <?= $content ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php
    }
}