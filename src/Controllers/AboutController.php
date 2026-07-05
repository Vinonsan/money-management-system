<?php
namespace Controllers;

class AboutController
{
    public function index()
    {
        require_once __DIR__ . '/../Views/layouts/app_layout.php';
        renderAppLayout('About', __DIR__ . '/../Views/public/about.php');
    }
}