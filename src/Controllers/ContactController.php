<?php
namespace Controllers;

class ContactController
{
    public function index()
    {
        require_once __DIR__ . '/../Views/layouts/app_layout.php';
        renderAppLayout('Contact', __DIR__ . '/../Views/public/contact.php');
    }
}