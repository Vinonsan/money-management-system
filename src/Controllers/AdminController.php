<?php
namespace Controllers;

class AdminController
{
    public function index(): void
    {
        require_once __DIR__ . '/../Views/layouts/app_layout.php';
        renderAppLayout('Dashboard', __DIR__ . '/../Views/admin/dashboard.php', [], 'dashboard');
    }
}
