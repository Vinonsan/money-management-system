<?php
namespace Controllers;

class BillingController
{
    public function index()
    {
        require_once __DIR__ . '/../Views/layouts/app_layout.php';
        renderAppLayout('Billing', __DIR__ . '/../Views/admin/billing.php', [], 'billing');
    }
}