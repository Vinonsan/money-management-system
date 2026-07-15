<?php
namespace Models;

class LoginRateLimit
{
    public static function get(string $phone): array
    {
        $row = Database::connect()->fetch(
            'SELECT * FROM login_rate_limits WHERE phone = ? LIMIT 1',
            [$phone]
        );

        if ($row) {
            return $row;
        }

        Database::connect()->insert(
            'INSERT INTO login_rate_limits (phone) VALUES (?)',
            [$phone]
        );

        return [
            'phone' => $phone,
            'otp_request_count' => 0,
            'failed_login_count' => 0,
            'lock_tier' => 0,
            'locked_until' => null,
        ];
    }

    public static function isLocked(string $phone): ?string
    {
        $row = self::get($phone);
        if (empty($row['locked_until'])) {
            return null;
        }

        $until = strtotime((string) $row['locked_until']);
        if ($until === false || $until <= time()) {
            // Lock expired — clear counters for a fresh window
            Database::connect()->execute(
                'UPDATE login_rate_limits
                 SET locked_until = NULL, otp_request_count = 0, failed_login_count = 0
                 WHERE phone = ?',
                [$phone]
            );
            return null;
        }

        $seconds = $until - time();
        if ($seconds >= 3600) {
            $label = ceil($seconds / 3600) . ' hour(s)';
        } elseif ($seconds >= 60) {
            $label = ceil($seconds / 60) . ' minute(s)';
        } else {
            $label = $seconds . ' second(s)';
        }

        return 'Too many attempts. Try again in ' . $label . '.';
    }

    /** Record OTP request; lock 1 minute after 3 requests. */
    public static function recordOtpRequest(string $phone): ?string
    {
        $lockMsg = self::isLocked($phone);
        if ($lockMsg !== null) {
            return $lockMsg;
        }

        $row = self::get($phone);
        $count = (int) $row['otp_request_count'] + 1;

        // 3 OTPs allowed; 4th request triggers 1-minute lock
        if ($count > OTP_MAX_REQUESTS) {
            $until = date('Y-m-d H:i:s', time() + OTP_LOCK_SECONDS);
            Database::connect()->execute(
                'UPDATE login_rate_limits
                 SET otp_request_count = 0, locked_until = ?, lock_tier = GREATEST(lock_tier, 1), updated_at = NOW()
                 WHERE phone = ?',
                [$until, $phone]
            );
            return 'OTP requested too many times. Try again in 1 minute.';
        }

        Database::connect()->execute(
            'UPDATE login_rate_limits SET otp_request_count = ?, updated_at = NOW() WHERE phone = ?',
            [$count, $phone]
        );

        return null;
    }

    /** Record failed OTP verify; 2 fails → 5 min, then escalate to 1 hour. */
    public static function recordFailedLogin(string $phone): string
    {
        $row = self::get($phone);
        $fails = (int) $row['failed_login_count'] + 1;
        $tier = (int) $row['lock_tier'];

        if ($fails >= LOGIN_FAIL_MAX) {
            // First fail-lock (or after OTP lock): 5 minutes. Next: 1 hour.
            if ($tier < 2) {
                $seconds = LOGIN_FAIL_LOCK_SECONDS;
                $tier = 2;
                $msg = 'Login failed too many times. Try again in 5 minutes.';
            } else {
                $seconds = LOGIN_ESCALATED_LOCK_SECONDS;
                $tier = 3;
                $msg = 'Login failed too many times. Try again in 1 hour.';
            }

            $until = date('Y-m-d H:i:s', time() + $seconds);
            Database::connect()->execute(
                'UPDATE login_rate_limits
                 SET failed_login_count = 0, locked_until = ?, lock_tier = ?, updated_at = NOW()
                 WHERE phone = ?',
                [$until, $tier, $phone]
            );

            return $msg;
        }

        Database::connect()->execute(
            'UPDATE login_rate_limits SET failed_login_count = ?, updated_at = NOW() WHERE phone = ?',
            [$fails, $phone]
        );

        $left = LOGIN_FAIL_MAX - $fails;
        return 'Invalid OTP. ' . $left . ' attempt(s) left before temporary lock.';
    }

    public static function clearOnSuccess(string $phone): void
    {
        Database::connect()->execute(
            'UPDATE login_rate_limits
             SET otp_request_count = 0, failed_login_count = 0, lock_tier = 0, locked_until = NULL, updated_at = NOW()
             WHERE phone = ?',
            [$phone]
        );
    }
}
