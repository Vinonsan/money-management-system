<?php
namespace Middleware;

class SuperAdminAuth
{
    public function handle(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        if (($_SESSION['user_role'] ?? '') !== 'super_admin') {
            header('Location: ' . BASE_URL . '/admin');
            exit;
        }
    }
}
