<?php
// src/Views/layouts/app_layout.php — thin wrapper around Components\Layouts\AppLayout

use Components\Layouts\AppLayout;

function renderAppLayout(string $title, string $contentView, array $data = [], string $activeNav = ''): void
{
    AppLayout::render($title, $contentView, $data, $activeNav);
}
