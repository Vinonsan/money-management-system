<?php
namespace Controllers;

class HomeController
{
    public function index(): void
    {
        require_once __DIR__ . '/../Views/layouts/app_layout.php';
        renderAppLayout('Home', __DIR__ . '/../Views/public/home.php', [], 'home');
    }
}
