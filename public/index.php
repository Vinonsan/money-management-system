<?php
require_once __DIR__ . '/../config/config.php';

$route = isset($_GET['route']) ? rtrim($_GET['route'], '/') : '';

$routes = [
    // Public
    ''          => ['Controller' => 'HomeController', 'Action' => 'index'],
    'home'      => ['Controller' => 'HomeController', 'Action' => 'index'],
    'about'     => ['Controller' => 'AboutController', 'Action' => 'index'],
    'contact'   => ['Controller' => 'ContactController', 'Action' => 'index'],
    'booking'   => ['Controller' => 'BookingController', 'Action' => 'index'],

    // Auth
    'login'     => ['Controller' => 'AuthController', 'Action' => 'showLogin'],
    'register'  => ['Controller' => 'AuthController', 'Action' => 'showRegister'],
    'logout'    => ['Controller' => 'AuthController', 'Action' => 'logout'],

    // Admin (Protected)
    'admin'             => ['Controller' => 'AdminController', 'Action' => 'index', 'Middleware' => 'StaffAuth'],
    'admin/users'       => ['Controller' => 'AdminController', 'Action' => 'users', 'Middleware' => 'StaffAuth'],
    'admin/billing'     => ['Controller' => 'BillingController', 'Action' => 'index', 'Middleware' => 'StaffAuth'],
    'admin/inventory'   => ['Controller' => 'InventoryController', 'Action' => 'index', 'Middleware' => 'StaffAuth'],

    // Portal (Protected)
    'portal'    => ['Controller' => 'PortalController', 'Action' => 'index', 'Middleware' => 'CustomerAuth'],
];

if (array_key_exists($route, $routes)) {
    $info = $routes[$route];
    if (isset($info['Middleware'])) {
        $mwClass = 'Middleware\\' . $info['Middleware'];
        if (class_exists($mwClass)) (new $mwClass())->handle();
    }
    $ctrlClass = 'Controllers\\' . $info['Controller'];
    if (class_exists($ctrlClass)) {
        (new $ctrlClass())->{$info['Action']}();
    } else {
        http_response_code(500);
        echo "<h1>Controller Not Found</h1>";
    }
} else {
    http_response_code(404);
    require_once __DIR__ . '/../src/Views/layouts/app_layout.php';
    renderAppLayout('404', __DIR__ . '/../src/Views/public/404.php');
}
