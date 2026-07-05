<?php
namespace Components\Base;

final class Badge
{
    public static function render(string $text, string $color = 'gray'): string
    {
        $colors = [
            'green' => 'bg-green-100 text-green-700',
            'blue' => 'bg-blue-100 text-blue-700',
            'red' => 'bg-red-100 text-red-700',
            'orange' => 'bg-orange-100 text-orange-700',
            'purple' => 'bg-purple-100 text-purple-700',
            'gray' => 'bg-gray-100 text-gray-600',
        ];
        $class = $colors[$color] ?? $colors['gray'];
        return '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ' . $class . '">' . htmlspecialchars($text) . '</span>';
    }
}