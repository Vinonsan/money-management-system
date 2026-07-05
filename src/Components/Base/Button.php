<?php
namespace Components\Base;

final class Button
{
    public static function render(string $label, string $variant = 'primary', array $attrs = []): string
    {
        $variantClass = match ($variant) {
            'secondary' => 'bg-gray-100 text-gray-700 hover:bg-gray-200',
            'danger' => 'bg-red-600 text-white hover:bg-red-700',
            'ghost' => 'bg-transparent text-gray-600 hover:bg-gray-100',
            default => 'bg-blue-600 text-white hover:bg-blue-700',
        };

        $extra = '';
        foreach ($attrs as $key => $val) {
            $extra .= ' ' . $key . '="' . htmlspecialchars((string)$val, ENT_QUOTES) . '"';
        }

        return sprintf(
            '<button class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg transition %s"%s>%s</button>',
            $variantClass,
            $extra,
            htmlspecialchars($label, ENT_QUOTES)
        );
    }
}