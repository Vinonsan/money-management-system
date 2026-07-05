<?php
namespace Controllers;

class AdminController
{
    public function index()
    {
        require_once __DIR__ . '/../Views/layouts/app_layout.php';
        renderAppLayout('Dashboard', __DIR__ . '/../Views/admin/dashboard.php', [], 'dashboard');
    }

    public function users()
    {
        require_once __DIR__ . '/../Views/layouts/app_layout.php';
        renderAppLayout('Manage Users', __DIR__ . '/../Views/admin/users.php', [], 'users');
    }
}