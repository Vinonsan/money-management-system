<?php
namespace Models;

class OtpCode
{
    public static function create(string $phone, string $code): void
    {
        $db = Database::connect();
        // Invalidate previous unused codes for this phone
        $db->execute(
            'UPDATE otp_codes SET is_used = 1 WHERE phone = ? AND is_used = 0',
            [$phone]
        );

        $expires = date('Y-m-d H:i:s', time() + (OTP_EXPIRY_MINUTES * 60));
        $db->insert(
            'INSERT INTO otp_codes (phone, code, expires_at) VALUES (?, ?, ?)',
            [$phone, $code, $expires]
        );
    }

    public static function verify(string $phone, string $code): bool
    {
        $row = Database::connect()->fetch(
            'SELECT * FROM otp_codes
             WHERE phone = ? AND code = ? AND is_used = 0 AND expires_at >= NOW()
             ORDER BY id DESC LIMIT 1',
            [$phone, $code]
        );

        if (!$row) {
            return false;
        }

        Database::connect()->execute(
            'UPDATE otp_codes SET is_used = 1 WHERE id = ?',
            [(int) $row['id']]
        );

        return true;
    }
}
