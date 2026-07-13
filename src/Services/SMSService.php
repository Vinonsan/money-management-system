<?php
namespace Services;

/**
 * SMS gateway stub. Replace send() body with real SMS API later.
 * Until then, OTP is always OTP_DEV_CODE (111111).
 */
class SMSService
{
    public function sendOtp(string $phone, string $otp): bool
    {
        // TODO: integrate SMS API here.
        // For now log locally and succeed so login works with OTP_DEV_CODE.
        $line = sprintf(
            "[%s] OTP to %s: %s%s",
            date('Y-m-d H:i:s'),
            $phone,
            $otp,
            PHP_EOL
        );
        @file_put_contents(__DIR__ . '/../../sms_log.txt', $line, FILE_APPEND);

        return true;
    }

    public function generateOtp(): string
    {
        return OTP_DEV_CODE;
    }

    public function send(string $phone, string $message): bool
    {
        $line = sprintf(
            "[%s] SMS to %s: %s%s",
            date('Y-m-d H:i:s'),
            $phone,
            $message,
            PHP_EOL
        );
        @file_put_contents(__DIR__ . '/../../sms_log.txt', $line, FILE_APPEND);

        // Deduct SMS balance
        try {
            $cost = (float) (\Models\Setting::get('sms_cost_per_message', '0.62'));
            $balance = (float) (\Models\Setting::get('sms_balance', '0'));
            $newBalance = max(0, $balance - $cost);
            \Models\Setting::set('sms_balance', (string) $newBalance);
        } catch (\Exception $e) {
            // Don't block SMS if balance deduction fails
        }

        return true;
    }
}
