<?php
namespace Components\Base;

final class Card
{
    public static function render(string $title = '', string $body = '', array $opts = []): string
    {
        $class = 'overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm ' . ($opts['class'] ?? '');
        $html = '<div class="' . trim($class) . '">';
        if ($title) {
            $html .= '<div class="border-b border-gray-100 px-5 py-4"><h3 class="text-sm font-bold text-gray-800">' . htmlspecialchars($title) . '</h3></div>';
        }
        $html .= '<div class="p-5">' . $body . '</div>';
        $html .= '</div>';
        return $html;
    }
}