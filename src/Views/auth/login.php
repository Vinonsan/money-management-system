<?php
use Components\Base\OtpInput;

$error = $error ?? null;
$success = $success ?? null;
$step = $step ?? 'phone';
$phone = $phone ?? '';
?>

<?php if (!empty($error)): ?>
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= e($error) ?></div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><?= e($success) ?></div>
<?php endif; ?>

<?php if ($step === 'otp'): ?>
    <form method="post" action="<?= BASE_URL ?>/login/verify-otp" class="space-y-5"
          x-data
          @submit="if (!$el.querySelector('[name=otp]').value || $el.querySelector('[name=otp]').value.length < 6) { $event.preventDefault() }">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
        <input type="hidden" name="phone" value="<?= e($phone) ?>">

        <?= OtpInput::render('otp', 6, ['label' => 'Enter OTP', 'autofocus' => true]) ?>

        <button type="submit"
                class="w-full bg-emerald-700 hover:bg-emerald-800 text-white font-bold py-2.5 rounded-lg transition">
            Verify &amp; Login
        </button>

        <a href="<?= BASE_URL ?>/login?reset=1" class="block text-center text-sm text-gray-500 hover:text-emerald-800">
            Change phone number
        </a>
    </form>
<?php else: ?>
    <form method="post" action="<?= BASE_URL ?>/login/request-otp" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Phone Number</label>
            <input type="tel" name="phone" required autofocus
                   value="<?= e($phone) ?>"
                   placeholder="07XXXXXXXX"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
            <p class="mt-1.5 text-xs text-gray-400">Admin staff login with OTP.</p>
        </div>

        <button type="submit"
                class="w-full bg-emerald-700 hover:bg-emerald-800 text-white font-bold py-2.5 rounded-lg transition">
            Send OTP
        </button>
    </form>
<?php endif; ?>
