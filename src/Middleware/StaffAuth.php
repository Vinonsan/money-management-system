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
        // Admin panel roles (super_admin reserved for future)
        if (!in_array($role, ['super_admin', 'admin', 'collector'], true)) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }
}
