<?php
require_once __DIR__ . '/../config/config.php';

$route = isset($_GET['route']) ? rtrim($_GET['route'], '/') : '';

$routes = [
    // Public
    ''      => ['Controller' => 'HomeController', 'Action' => 'index'],
    'home'  => ['Controller' => 'HomeController', 'Action' => 'index'],

    // Auth (OTP login)
    'login'              => ['Controller' => 'AuthController', 'Action' => 'showLogin'],
    'login/request-otp'  => ['Controller' => 'AuthController', 'Action' => 'requestOtp'],
    'login/verify-otp'   => ['Controller' => 'AuthController', 'Action' => 'verifyOtp'],
    'logout'             => ['Controller' => 'AuthController', 'Action' => 'logout'],

    // Admin (Protected)
    'admin'         => ['Controller' => 'AdminController', 'Action' => 'index', 'Middleware' => 'StaffAuth'],
    'admin/profile' => ['Controller' => 'AdminController', 'Action' => 'profile', 'Middleware' => 'StaffAuth'],

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
    'admin/users'     => ['Controller' => 'AdminController', 'Action' => 'usersList', 'Middleware' => 'StaffAuth'],
    'admin/users/create' => ['Controller' => 'AdminController', 'Action' => 'createUser', 'Middleware' => 'StaffAuth'],
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
    require_once __DIR__ . '/../src/Views/layouts/app_layout.php';
    renderAppLayout('404', __DIR__ . '/../src/Views/public/404.php');
}
