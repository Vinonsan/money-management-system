<?php
namespace Middleware;

class StaffAuth
{
    public function handle(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $role = $_SESSION['user_role'] ?? '';
        // Only super_admin and admin roles have access to admin panel
        if (!in_array($role, ['super_admin', 'admin'], true)) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }
}
