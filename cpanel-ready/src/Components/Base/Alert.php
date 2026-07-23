<?php
namespace Components\Base;

final class Alert
{
    public static function render(string $message, string $type = 'info'): string
    {
        $colors = [
            'success' => 'border-green-200 bg-green-50 text-green-700',
            'error' => 'border-red-200 bg-red-50 text-red-700',
            'warning' => 'border-orange-200 bg-orange-50 text-orange-700',
            'info' => 'border-blue-200 bg-blue-50 text-blue-700',
        ];
        $class = $colors[$type] ?? $colors['info'];
        return '<div class="mb-4 flex items-center gap-2 rounded-lg border px-4 py-3 text-sm font-medium ' . $class . '">' . htmlspecialchars($message) . '</div>';
    }
}