<?php
// src/Views/layouts/super_admin_auth_layout.php
// Red-themed auth layout for Super Admin login

use Components\Layouts\SuperAdminAuthLayout;

function renderSuperAdminAuthLayout(string $title, string $contentView, array $data = []): void
{
    SuperAdminAuthLayout::render($title, $contentView, $data);
}
