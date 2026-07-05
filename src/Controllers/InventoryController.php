<?php
namespace Controllers;

class InventoryController
{
    public function index()
    {
        require_once __DIR__ . '/../Views/layouts/app_layout.php';
        renderAppLayout('Inventory', __DIR__ . '/../Views/admin/inventory.php', [], 'inventory');
    }
}