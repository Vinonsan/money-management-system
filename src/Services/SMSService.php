<?php
namespace Services;

class SMSService {
    public static function sendSMS($recipient, $message) {
        return ['success' => true, 'response' => ['status' => 'placeholder']];
    }
}