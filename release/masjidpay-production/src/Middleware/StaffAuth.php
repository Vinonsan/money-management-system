<?php
namespace Middleware;

class StaffAuth
{
    public function handle(): void
    {
        if (empty($_SESSION['user_id'])) {
            if ($this->expectsJson()) {
                $this->jsonUnauthorized('Your session has expired. Please sign in again.');
            }
            header('Location: ' . BASE_URL . '/admin/login');
            exit;
        }

        $role = $_SESSION['user_role'] ?? '';
        // Only super_admin and admin roles have access to admin panel
        if (!in_array($role, ['super_admin', 'admin'], true)) {
            if ($this->expectsJson()) {
                $this->jsonUnauthorized('You are not authorized to perform this action.');
            }
            header('Location: ' . BASE_URL . '/admin/login');
            exit;
        }
    }

    private function expectsJson(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
            || str_contains(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json');
    }

    private function jsonUnauthorized(string $message): never
    {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $message]);
        exit;
    }
}
