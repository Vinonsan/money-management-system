<?php
namespace Controllers;

class PortalController
{
    public function index()
    {
        require_once __DIR__ . '/../Views/layouts/app_layout.php';
        renderAppLayout('Portal', __DIR__ . '/../Views/public/home.php');
    }
}