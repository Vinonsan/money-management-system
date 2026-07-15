<?php
namespace Components\Layouts;

/**
 * Red-themed auth layout for Super Admin login.
 * Uses the premium red palette from superAdminTailwindColorsJs().
 */
final class SuperAdminAuthLayout
{
    public static function render(string $title, string $contentView, array $data = []): void
    {
        extract($data);
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> | <?= \APP_NAME ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= \BASE_URL ?>/assets/img/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: <?= \superAdminTailwindColorsJs() ?> } };
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }

        /* Premium Red Top Bar Animation */
        .sa-auth::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 99999;
            height: 3px;
            background: linear-gradient(90deg, #a01414, #e51d1d, #ff6b6b, #e51d1d, #a01414);
            background-size: 200% 100%;
            animation: premiumRedGradient 3s ease infinite;
            pointer-events: none;
        }
        @keyframes premiumRedGradient {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        /* Red glow on focus */
        .sa-input:focus {
            box-shadow: 0 0 0 3px rgba(248, 62, 62, 0.15);
            border-color: #f83e3e !important;
        }

        /* Secure badge pulse */
        .sa-badge-pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-red-50 via-white to-red-100 text-brand-charcoal antialiased sa-auth">
    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <!-- Brand -->
            <div class="text-center mb-8">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-600 to-primary-800 shadow-lg shadow-primary-200">
                    <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-primary-700"><?= \APP_NAME ?></h1>
                <p class="text-sm text-primary-600/70 mt-1"><?= htmlspecialchars($title) ?></p>
            </div>

            <!-- Card -->
            <div class="relative bg-white border border-primary-200 rounded-2xl shadow-xl shadow-primary-200/30 p-8">
                <!-- Decorative corner accent -->
                <div class="absolute -top-1 -right-1 h-12 w-12 overflow-hidden rounded-tr-2xl">
                    <div class="absolute -top-6 -right-6 h-12 w-12 rotate-45 bg-gradient-to-br from-primary-500 to-primary-700"></div>
                </div>

                <?php require $contentView; ?>
            </div>

            <!-- Secure login badge -->
            <div class="mt-6 flex items-center justify-center gap-2 text-xs text-primary-400 sa-badge-pulse">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <span>Secured with OTP verification</span>
            </div>

            <p class="mt-4 text-center text-xs text-primary-400">
                <a href="<?= \BASE_URL ?>/" class="hover:text-primary-600 transition-colors">← Back to home</a>
                <span class="mx-2">•</span>
                <a href="<?= \BASE_URL ?>/login" class="hover:text-primary-600 transition-colors">Staff Login</a>
            </p>
        </div>
    </div>
</body>
</html>
<?php
    }
}
