<?php
// Log errors without exposing details in the browser.
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../config/config.php';

$route = isset($_GET['route']) ? rtrim($_GET['route'], '/') : '';

$routes = [
    // Public
    ''      => ['Controller' => 'HomeController', 'Action' => 'index'],
    'home'  => ['Controller' => 'HomeController', 'Action' => 'index'],

    // Auth (OTP login)
    'login'                       => ['Controller' => 'AuthController', 'Action' => 'redirectToAdminLogin'],
    'admin/login'                 => ['Controller' => 'AuthController', 'Action' => 'showLogin'],
    'admin/login/request-otp'     => ['Controller' => 'AuthController', 'Action' => 'requestOtp'],
    'admin/login/verify-otp'      => ['Controller' => 'AuthController', 'Action' => 'verifyOtp'],
    'logout'                      => ['Controller' => 'AuthController', 'Action' => 'logout'],

    // Super Admin Auth (Red-themed login)
    'super-admin/login'                   => ['Controller' => 'AuthController', 'Action' => 'showSuperAdminLogin'],
    'super-admin/login/request-otp'       => ['Controller' => 'AuthController', 'Action' => 'requestSuperAdminOtp'],
    'super-admin/login/verify-otp'        => ['Controller' => 'AuthController', 'Action' => 'verifySuperAdminOtp'],

    // Admin (Protected)
    'admin'         => ['Controller' => 'AdminController', 'Action' => 'index', 'Middleware' => 'StaffAuth'],
    'admin/profile'       => ['Controller' => 'AdminController', 'Action' => 'profile', 'Middleware' => 'StaffAuth'],

    // Location Management
    'admin/locations'          => ['Controller' => 'AdminController', 'Action' => 'locations', 'Middleware' => 'StaffAuth'],
    'admin/locations/create'   => ['Controller' => 'AdminController', 'Action' => 'createLocation', 'Middleware' => 'StaffAuth'],
    'admin/locations/update'   => ['Controller' => 'AdminController', 'Action' => 'updateLocation', 'Middleware' => 'StaffAuth'],
    'admin/locations/delete'   => ['Controller' => 'AdminController', 'Action' => 'deleteLocation', 'Middleware' => 'StaffAuth'],
    'admin/wards'          => ['Controller' => 'AdminController', 'Action' => 'wards', 'Middleware' => 'StaffAuth'],
    'admin/wards/create'   => ['Controller' => 'AdminController', 'Action' => 'createWard', 'Middleware' => 'StaffAuth'],
    'admin/wards/update'   => ['Controller' => 'AdminController', 'Action' => 'updateWard', 'Middleware' => 'StaffAuth'],
    'admin/wards/delete'   => ['Controller' => 'AdminController', 'Action' => 'deleteWard', 'Middleware' => 'StaffAuth'],

    // Users
    'admin/members'        => ['Controller' => 'AdminController', 'Action' => 'membersList', 'Middleware' => 'StaffAuth'],
    'admin/members/create' => ['Controller' => 'AdminController', 'Action' => 'createMember', 'Middleware' => 'StaffAuth'],
    'admin/members/form-options' => ['Controller' => 'AdminController', 'Action' => 'memberFormOptions', 'Middleware' => 'StaffAuth'],
    'admin/members/update' => ['Controller' => 'AdminController', 'Action' => 'updateMember', 'Middleware' => 'StaffAuth'],
    'admin/members/delete' => ['Controller' => 'AdminController', 'Action' => 'deleteMember', 'Middleware' => 'StaffAuth'],

    // Payments
    'admin/payments/update'              => ['Controller' => 'AdminController', 'Action' => 'paymentUpdate', 'Middleware' => 'StaffAuth'],
    'admin/payments/members'             => ['Controller' => 'AdminController', 'Action' => 'paymentMembers', 'Middleware' => 'StaffAuth'],
    'admin/payments/schedule-message'    => ['Controller' => 'AdminController', 'Action' => 'scheduleMessage', 'Middleware' => 'StaffAuth'],
    'admin/payments/process-scheduled'   => ['Controller' => 'AdminController', 'Action' => 'processScheduledMessages', 'Middleware' => 'StaffAuth'],
    'admin/payments/search-member'         => ['Controller' => 'AdminController', 'Action' => 'searchMember', 'Middleware' => 'StaffAuth'],
    'admin/payments/member-info'           => ['Controller' => 'AdminController', 'Action' => 'getMemberPaymentInfo', 'Middleware' => 'StaffAuth'],
    'admin/payments/calculate'           => ['Controller' => 'AdminController', 'Action' => 'calculatePayment', 'Middleware' => 'StaffAuth'],
    'admin/payments/create'              => ['Controller' => 'AdminController', 'Action' => 'createPayment', 'Middleware' => 'StaffAuth'],

    // System Config
    'admin/system-config'              => ['Controller' => 'AdminController', 'Action' => 'systemConfig', 'Middleware' => 'StaffAuth'],
    'admin/system-config/save'         => ['Controller' => 'AdminController', 'Action' => 'saveSystemConfig', 'Middleware' => 'StaffAuth'],
    'admin/system-config/messages'     => ['Controller' => 'AdminController', 'Action' => 'systemMessages', 'Middleware' => 'StaffAuth'],
    'admin/system-config/messages/save' => ['Controller' => 'AdminController', 'Action' => 'saveSystemMessages', 'Middleware' => 'StaffAuth'],

    // Super Admin (Protected - super_admin role only)
    'super-admin'                   => ['Controller' => 'SuperAdminController', 'Action' => 'index', 'Middleware' => 'SuperAdminAuth'],
    'super-admin/admins'            => ['Controller' => 'SuperAdminController', 'Action' => 'admins', 'Middleware' => 'SuperAdminAuth'],
    'super-admin/admins/create'     => ['Controller' => 'SuperAdminController', 'Action' => 'createAdmin', 'Middleware' => 'SuperAdminAuth'],
    'super-admin/admins/update'     => ['Controller' => 'SuperAdminController', 'Action' => 'updateAdmin', 'Middleware' => 'SuperAdminAuth'],
    'super-admin/admins/delete'     => ['Controller' => 'SuperAdminController', 'Action' => 'deleteAdmin', 'Middleware' => 'SuperAdminAuth'],
    'super-admin/sms'               => ['Controller' => 'SuperAdminController', 'Action' => 'smsConfig', 'Middleware' => 'SuperAdminAuth'],
    'super-admin/sms/save-config'   => ['Controller' => 'SuperAdminController', 'Action' => 'saveSmsConfig', 'Middleware' => 'SuperAdminAuth'],
    'super-admin/sms/refill'        => ['Controller' => 'SuperAdminController', 'Action' => 'refillSms', 'Middleware' => 'SuperAdminAuth'],
    'super-admin/sms/refill-requests' => ['Controller' => 'SuperAdminController', 'Action' => 'refillRequests', 'Middleware' => 'SuperAdminAuth'],
    'super-admin/sms/approve-refill'  => ['Controller' => 'SuperAdminController', 'Action' => 'approveRefill', 'Middleware' => 'SuperAdminAuth'],

    // Admin SMS Management
    'admin/sms'                       => ['Controller' => 'AdminController', 'Action' => 'smsManager', 'Middleware' => 'StaffAuth'],
    'admin/sms/request-refill'        => ['Controller' => 'AdminController', 'Action' => 'requestRefill', 'Middleware' => 'StaffAuth'],

    // Schedule Report
    'admin/schedule-report'           => ['Controller' => 'AdminController', 'Action' => 'scheduleReport', 'Middleware' => 'StaffAuth'],

    // Reports
    'admin/reports'                       => ['Controller' => 'AdminController', 'Action' => 'reports', 'Middleware' => 'StaffAuth'],

    // Bulk Import
    'admin/bulk-import'                    => ['Controller' => 'AdminController', 'Action' => 'bulkImport', 'Middleware' => 'StaffAuth'],
    'admin/bulk-import/process'            => ['Controller' => 'AdminController', 'Action' => 'processBulkImport', 'Middleware' => 'StaffAuth'],
    'admin/bulk-import/template'           => ['Controller' => 'AdminController', 'Action' => 'downloadTemplate', 'Middleware' => 'StaffAuth'],

    // Admin Transfer
    'admin/transfer'                      => ['Controller' => 'AdminController', 'Action' => 'transfer', 'Middleware' => 'StaffAuth'],
    'admin/transfer/send-old-otp'         => ['Controller' => 'AdminController', 'Action' => 'transferSendOldOtp', 'Middleware' => 'StaffAuth'],
    'admin/transfer/verify-old-otp'       => ['Controller' => 'AdminController', 'Action' => 'transferVerifyOldOtp', 'Middleware' => 'StaffAuth'],
    'admin/transfer/send-new-otp'         => ['Controller' => 'AdminController', 'Action' => 'transferSendNewOtp', 'Middleware' => 'StaffAuth'],
    'admin/transfer/verify-new-otp'       => ['Controller' => 'AdminController', 'Action' => 'transferVerifyNewOtp', 'Middleware' => 'StaffAuth'],
    'admin/transfer/complete'             => ['Controller' => 'AdminController', 'Action' => 'transferComplete', 'Middleware' => 'StaffAuth'],
];

if (array_key_exists($route, $routes)) {
    $info = $routes[$route];
    if (isset($info['Middleware'])) {
        $mwClass = 'Middleware\\' . $info['Middleware'];
        if (class_exists($mwClass)) {
            (new $mwClass())->handle();
        }
    }
    $ctrlClass = 'Controllers\\' . $info['Controller'];
    if (class_exists($ctrlClass)) {
        (new $ctrlClass())->{$info['Action']}();
    } else {
        http_response_code(500);
        echo '<h1>Controller Not Found</h1>';
    }
} else {
    http_response_code(404);
    $expectsJson = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
        || str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
        || str_contains(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json');
    if ($expectsJson) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'API route not found.']);
    } else {
        require_once __DIR__ . '/../src/Views/layouts/app_layout.php';
        renderAppLayout('404', __DIR__ . '/../src/Views/public/404.php');
    }
}
