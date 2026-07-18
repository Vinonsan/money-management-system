<?php
use Components\Base\OtpInput;

$error = $error ?? null;
$success = $success ?? null;
$step = $step ?? 'phone';
$phone = $phone ?? '';
$otp_sent_at = (int)($otp_sent_at ?? 0);
$lock_seconds = (int)($lock_seconds ?? 0);
$otp_expiry = OTP_EXPIRY_MINUTES * 60;
?>

<div id="countdown-container"
     data-step="<?= e($step) ?>"
     data-otp-sent-at="<?= $otp_sent_at ?>"
     data-otp-expiry="<?= $otp_expiry ?>"
     data-lock-seconds="<?= $lock_seconds ?>"></div>

<?php if (!empty($error)): ?>
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= e($error) ?></div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="mb-4 rounded-lg border border-primary-200 bg-primary-50 px-4 py-3 text-sm text-primary-800"><?= e($success) ?></div>
<?php endif; ?>

<?php if ($step === 'otp'): ?>
    <form method="post" action="<?= BASE_URL ?>/login/verify-otp" class="space-y-5"
          x-data
          @submit="if (!$el.querySelector('[name=otp]').value || $el.querySelector('[name=otp]').value.length < 6) { $event.preventDefault() }">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
        <input type="hidden" name="phone" value="<?= e($phone) ?>">

        <?= OtpInput::render('otp', 6, ['label' => 'Enter OTP', 'autofocus' => true]) ?>

        <!-- OTP Expiry Countdown -->
        <div id="otp-timer" class="text-center text-sm font-medium <?= $otp_sent_at > 0 ? '' : 'hidden' ?>">
            <span class="text-gray-500">OTP expires in </span>
            <span id="otp-countdown" class="text-primary-700 font-bold"><?= $otp_expiry / 60 ?>:00</span>
        </div>

        <button type="submit" id="verify-btn"
                class="w-full bg-primary-600 hover:bg-primary-700 text-white font-bold py-2.5 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
            Verify &amp; Login
        </button>

        <a href="<?= BASE_URL ?>/login?reset=1" class="block text-center text-sm text-gray-500 hover:text-primary-700">
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
                   id="phone-input"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>

        <!-- Lock Countdown -->
        <div id="lock-timer" class="text-center text-sm font-medium <?= $lock_seconds > 0 ? '' : 'hidden' ?>">
            <span class="text-red-600">Locked. Try again in </span>
            <span id="lock-countdown" class="text-red-700 font-bold"><?= gmdate('i:s', $lock_seconds) ?></span>
        </div>

        <button type="submit" id="send-otp-btn"
                class="w-full bg-primary-600 hover:bg-primary-700 text-white font-bold py-2.5 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
            Send OTP
        </button>
    </form>
<?php endif; ?>

<script>
(function() {
    const step = '<?= e($step) ?>';
    const otpSentAt = <?= $otp_sent_at ?>;
    const otpExpiry = <?= $otp_expiry ?>;
    let lockSeconds = <?= $lock_seconds ?>;

    function pad(n) { return n.toString().padStart(2, '0'); }

    function formatDuration(secs) {
        if (secs >= 3600) {
            const h = Math.floor(secs / 3600);
            const m = Math.floor((secs % 3600) / 60);
            return h + 'h ' + m + 'm';
        }
        return Math.floor(secs / 60) + ':' + pad(secs % 60);
    }

    // ─── OTP Expiry Countdown ──────────────────────────────────────
    if (step === 'otp' && otpSentAt > 0) {
        const otpTimer = document.getElementById('otp-timer');
        const otpCountdown = document.getElementById('otp-countdown');
        const verifyBtn = document.getElementById('verify-btn');

        function updateOtpCountdown() {
            const now = Math.floor(Date.now() / 1000);
            const elapsed = now - otpSentAt;
            const remaining = Math.max(0, otpExpiry - elapsed);

            if (remaining <= 0) {
                otpCountdown.textContent = 'Expired';
                otpTimer.classList.remove('hidden');
                otpTimer.className = 'text-center text-sm font-medium text-red-600';
                if (verifyBtn) {
                    verifyBtn.disabled = true;
                    verifyBtn.textContent = 'OTP Expired — Request New OTP';
                }
                return;
            }

            otpCountdown.textContent = formatDuration(remaining);
            otpTimer.classList.remove('hidden');

            // Warn when less than 30 seconds
            if (remaining <= 30) {
                otpTimer.className = 'text-center text-sm font-medium text-red-600 animate-pulse';
            }
        }

        updateOtpCountdown();
        setInterval(updateOtpCountdown, 1000);
    }

    // ─── Lock Countdown ────────────────────────────────────────────
    if (step === 'phone' && lockSeconds > 0) {
        const lockTimer = document.getElementById('lock-timer');
        const lockCountdown = document.getElementById('lock-countdown');
        const sendBtn = document.getElementById('send-otp-btn');
        const phoneInput = document.getElementById('phone-input');

        function updateLockCountdown() {
            if (lockSeconds <= 0) {
                lockTimer.classList.add('hidden');
                if (sendBtn) {
                    sendBtn.disabled = false;
                    sendBtn.textContent = 'Send OTP';
                }
                if (phoneInput) phoneInput.disabled = false;
                return;
            }

            lockCountdown.textContent = formatDuration(lockSeconds);
            lockTimer.classList.remove('hidden');
            if (sendBtn) {
                sendBtn.disabled = true;
                sendBtn.textContent = 'Locked — Wait ' + formatDuration(lockSeconds);
            }
            if (phoneInput) phoneInput.disabled = true;

            lockSeconds--;
        }

        updateLockCountdown();
        setInterval(updateLockCountdown, 1000);
    }
})();
</script>
