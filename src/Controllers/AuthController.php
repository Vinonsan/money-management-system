<?php
namespace Controllers;

class AuthController
{
    public function showLogin()
    {
        require_once __DIR__ . '/../Views/layouts/auth_layout.php';
        renderAuthLayout('Sign In', __DIR__ . '/../Views/auth/login.php');
    }

    public function showRegister()
    {
        require_once __DIR__ . '/../Views/layouts/auth_layout.php';
        renderAuthLayout('Create Account', __DIR__ . '/../Views/auth/register.php');
    }

    public function logout()
    {
        session_destroy();
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
}