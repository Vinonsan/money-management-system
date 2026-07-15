<?php
namespace Components\Base;

/**
 * Six single-digit OTP boxes. Collects into a hidden input named $name.
 * Requires Alpine.js on the page.
 */
final class OtpInput
{
    public static function render(string $name = 'otp', int $length = 6, array $options = []): string
    {
        $length = max(4, min(8, $length));
        $autofocus = !empty($options['autofocus']);
        $label = $options['label'] ?? 'OTP Code';
        $idPrefix = $options['id'] ?? 'otp';

        $boxes = '';
        for ($i = 0; $i < $length; $i++) {
            $focusAttr = ($autofocus && $i === 0) ? ' autofocus' : '';
            $boxes .= sprintf(
                '<input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]"
                    id="%s-%d" data-otp-index="%d"
                    class="otp-digit w-11 h-12 sm:w-12 sm:h-14 text-center text-lg font-bold rounded-xl border border-primary-200 bg-primary-50/30 text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:border-primary-600 focus:bg-white transition"
                    x-ref="digit%d"
                    @input="onInput($event, %d)"
                    @keydown.backspace="onBackspace($event, %d)"
                    @paste.prevent="onPaste($event)"%s>',
                htmlspecialchars($idPrefix, ENT_QUOTES),
                $i,
                $i,
                $i,
                $i,
                $i,
                $focusAttr
            );
        }

        $labelHtml = $label !== ''
            ? '<label class="block text-sm font-semibold text-primary-800 mb-3">' . htmlspecialchars($label, ENT_QUOTES) . '</label>'
            : '';

        return <<<HTML
<div
    x-data="otpInput({ length: {$length}, name: '{$name}' })"
    x-init="syncHidden()"
    class="otp-input-component"
>
    {$labelHtml}
    <div class="flex items-center justify-between gap-2 sm:gap-3">
        {$boxes}
    </div>
    <input type="hidden" name="{$name}" x-model="code" x-ref="hidden" value="">
</div>
<script>
document.addEventListener('alpine:init', () => {
    if (window.__otpInputRegistered) return;
    window.__otpInputRegistered = true;
    Alpine.data('otpInput', (cfg) => ({
        length: cfg.length || 6,
        name: cfg.name || 'otp',
        digits: Array(cfg.length || 6).fill(''),
        code: '',
        syncHidden() {
            this.code = this.digits.join('');
        },
        onInput(e, i) {
            const raw = (e.target.value || '').replace(/\\D/g, '');
            const digit = raw.slice(-1);
            e.target.value = digit;
            this.digits[i] = digit;
            this.syncHidden();
            if (digit && i < this.length - 1) {
                this.\$refs['digit' + (i + 1)]?.focus();
            }
        },
        onBackspace(e, i) {
            if (!e.target.value && i > 0) {
                this.\$refs['digit' + (i - 1)]?.focus();
            }
            queueMicrotask(() => {
                this.digits[i] = (e.target.value || '').replace(/\\D/g, '').slice(-1);
                this.syncHidden();
            });
        },
        onPaste(e) {
            const text = (e.clipboardData?.getData('text') || '').replace(/\\D/g, '').slice(0, this.length);
            if (!text) return;
            for (let i = 0; i < this.length; i++) {
                const d = text[i] || '';
                this.digits[i] = d;
                const el = this.\$refs['digit' + i];
                if (el) el.value = d;
            }
            this.syncHidden();
            const focusAt = Math.min(text.length, this.length) - 1;
            if (focusAt >= 0) this.\$refs['digit' + focusAt]?.focus();
        }
    }));
});
</script>
HTML;
    }

    public static function echo(string $name = 'otp', int $length = 6, array $options = []): void
    {
        echo self::render($name, $length, $options);
    }
}
