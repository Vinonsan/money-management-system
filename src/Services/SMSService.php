<?php
namespace Services;

/**
 * SMSlenz Gateway Integration
 *
 * Uses SMSlenz.lk REST API to send SMS messages.
 * API docs: https://smslenz.lk/developers/api
 */
class SMSService
{
    private string $baseUrl = 'https://smslenz.lk/api';
    private string $userId;
    private string $apiKey;
    private string $senderId;

    public function __construct()
    {
        $this->userId   = \Models\Setting::get('smslenz_user_id', '');
        $this->apiKey   = \Models\Setting::get('smslenz_api_key', '');
        $this->senderId = \Models\Setting::get('smslenz_sender_id', 'SMSlenzDEMO');
    }

    /**
     * Check if SMSlenz is configured with valid credentials.
     */
    public function isConfigured(): bool
    {
        return $this->userId !== '' && $this->apiKey !== '';
    }

    /**
     * Send OTP via SMSlenz API.
     */
    public function sendOtp(string $phone, string $otp): bool
    {
        $message = "Your OTP is: {$otp}. Valid for " . OTP_EXPIRY_MINUTES . " minutes.";

        // Also log for development reference
        $line = sprintf(
            "[%s] OTP to %s: %s%s",
            date('Y-m-d H:i:s'),
            $phone,
            $otp,
            PHP_EOL
        );
        @file_put_contents(__DIR__ . '/../../sms_log.txt', $line, FILE_APPEND);

        return $this->send($phone, $message);
    }

    /**
     * Generate a cryptographically secure random 6-digit OTP.
     * Uses random_int() for secure unpredictability.
     */
    public function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Send an SMS via SMSlenz API.
     *
     * @param string $phone   Recipient phone number (e.g. 0754476969 or +94754476969)
     * @param string $message Message content (max 1500 chars)
     * @return bool
     */
    public function send(string $phone, string $message): bool
    {
        // Append branding
        $message = rtrim($message) . ' - MasjidPay';

        // Normalize phone to international format (+94XXXXXXXXX)
        $phone = $this->normalizePhone($phone);

        // Log locally regardless of API success
        $line = sprintf(
            "[%s] SMS to %s: %s%s",
            date('Y-m-d H:i:s'),
            $phone,
            $message,
            PHP_EOL
        );
        @file_put_contents(__DIR__ . '/../../sms_log.txt', $line, FILE_APPEND);

        // If not configured, just log (no real send)
        if (!$this->isConfigured()) {
            return true;
        }

        try {
            $url = $this->baseUrl . '/send-sms';

            $params = [
                'user_id'   => $this->userId,
                'api_key'   => $this->apiKey,
                'sender_id' => $this->senderId,
                'contact'   => $phone,
                'message'   => $message,
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url . '?' . http_build_query($params),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error    = curl_error($ch);
            curl_close($ch);

            if ($error !== '') {
                throw new \RuntimeException('cURL error: ' . $error);
            }

            $result = json_decode($response, true);
            $apiSuccess = ($result['success'] ?? false) && $httpCode === 200;

            // Log API response for debugging
            if (!$apiSuccess) {
                $apiMsg = $result['message'] ?? 'Unknown API error';
                $errLine = sprintf(
                    "[%s] SMS FAILED to %s (HTTP %d): %s | Response: %s%s",
                    date('Y-m-d H:i:s'),
                    $phone,
                    $httpCode,
                    $apiMsg,
                    mb_substr($response, 0, 500),
                    PHP_EOL
                );
                @file_put_contents(__DIR__ . '/../../sms_log.txt', $errLine, FILE_APPEND);
                return false;
            }

            // Deduct SMS balance from local tracking on success
            $this->deductBalance();

            return true;
        } catch (\Exception $e) {
            // Log error but don't block the application
            $errLine = sprintf(
                "[%s] SMS ERROR to %s: %s%s",
                date('Y-m-d H:i:s'),
                $phone,
                $e->getMessage(),
                PHP_EOL
            );
            @file_put_contents(__DIR__ . '/../../sms_log.txt', $errLine, FILE_APPEND);

            return false;
        }
    }

    /**
     * Get account balance from SMSlenz.
     *
     * @return array{success: bool, balance: float, plan: string}
     */
    public function getAccountStatus(): array
    {
        $default = ['success' => false, 'balance' => 0.0, 'plan' => 'Unknown'];

        if (!$this->isConfigured()) {
            return $default;
        }

        try {
            $params = [
                'user_id' => $this->userId,
                'api_key' => $this->apiKey,
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $this->baseUrl . '/account-status?' . http_build_query($params),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            $error    = curl_error($ch);
            curl_close($ch);

            if ($error !== '') {
                return $default;
            }

            $result = json_decode($response, true);
            if (!($result['success'] ?? false)) {
                return $default;
            }

            $balance = 0.0;
            if (!empty($result['data']['sms_credit_balance'])) {
                $balance = (float) str_replace(',', '', $result['data']['sms_credit_balance']);
            }

            return [
                'success' => true,
                'balance' => $balance,
                'plan'    => $result['data']['active_plan'] ?? 'Unknown',
            ];
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Normalize phone to +94XXXXXXXXX format.
     * Handles: 0754476969, +94754476969, 94754476969, 754476969 (9 digits without 0)
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', $phone) ?? '';
        // If starts with 0, replace with +94 (e.g. 0754476969 → +94754476969)
        if (preg_match('/^0(\d{9})$/', $phone, $m)) {
            return '+94' . $m[1];
        }
        // If already +94 format (e.g. +94754476969)
        if (preg_match('/^\+94\d{9}$/', $phone)) {
            return $phone;
        }
        // If just digits starting with 94 (e.g. 94754476969)
        if (preg_match('/^94(\d{9})$/', $phone, $m)) {
            return '+94' . $m[1];
        }
        // If 9 digits without leading 0 or 94 (e.g. 754476969 → +94754476969)
        if (preg_match('/^(\d{9})$/', $phone, $m)) {
            return '+94' . $m[1];
        }
        // Return as is (might fail at API)
        return $phone;
    }

    /**
     * Deduct one SMS from local balance tracking.
     */
    private function deductBalance(): void
    {
        try {
            $cost = (float) (\Models\Setting::get('sms_cost_per_message', '0.62'));
            $balance = (float) (\Models\Setting::get('sms_balance', '0'));
            $newBalance = max(0, $balance - $cost);
            \Models\Setting::set('sms_balance', (string) $newBalance);
        } catch (\Exception $e) {
            // Don't block SMS if balance deduction fails
        }
    }
}
