<?php
namespace Components\Base;

/**
 * Reusable Badge component with multiple colors, sizes, and dot variant.
 *
 * Usage:
 *   echo Badge::render('Active', 'green');
 *   echo Badge::render('Pending', 'yellow', ['size' => 'sm', 'dot' => true]);
 *   echo Badge::render('Inactive', 'red', ['pill' => false]);
 */
final class Badge
{
    public static function render(string $text, string $color = 'gray', array $opts = []): string
    {
        $colors = [
            'green'  => 'bg-white text-emerald-700 ring-1 ring-emerald-300',
            'blue'   => 'bg-white text-blue-700 ring-1 ring-blue-300',
            'red'    => 'bg-white text-red-700 ring-1 ring-red-300',
            'yellow' => 'bg-white text-amber-700 ring-1 ring-amber-300',
            'orange' => 'bg-white text-orange-700 ring-1 ring-orange-300',
            'purple' => 'bg-white text-purple-700 ring-1 ring-purple-300',
            'cyan'   => 'bg-white text-cyan-700 ring-1 ring-cyan-300',
            'pink'   => 'bg-white text-pink-700 ring-1 ring-pink-300',
            'gray'   => 'bg-white text-slate-600 ring-1 ring-slate-300',
            'white'  => 'bg-white text-slate-700 ring-1 ring-slate-300',
        ];

        $sizes = [
            'xs' => 'px-1.5 py-0.5 text-[10px]',
            'sm' => 'px-2 py-0.5 text-xs',
            'md' => 'px-2.5 py-1 text-xs',
            'lg' => 'px-3 py-1 text-sm',
        ];

        $colorClass = $colors[$color] ?? $colors['gray'];
        $size = $opts['size'] ?? 'sm';
        $sizeClass = $sizes[$size] ?? $sizes['sm'];
        $dot = !empty($opts['dot']);
        $pill = $opts['pill'] ?? true;
        $roundedClass = $pill ? 'rounded-full' : 'rounded-md';

        $dotHtml = '';
        if ($dot) {
            $dotColors = [
                'green'  => 'bg-emerald-500',
                'blue'   => 'bg-blue-500',
                'red'    => 'bg-red-500',
                'yellow' => 'bg-amber-500',
                'orange' => 'bg-orange-500',
                'purple' => 'bg-purple-500',
                'cyan'   => 'bg-cyan-500',
                'pink'   => 'bg-pink-500',
                'gray'   => 'bg-slate-400',
                'white'  => 'bg-slate-400',
            ];
            $dotColor = $dotColors[$color] ?? $dotColors['gray'];
            $dotHtml = '<span class="mr-1.5 inline-block h-1.5 w-1.5 rounded-full ' . $dotColor . '"></span>';
        }

        return sprintf(
            '<span class="inline-flex items-center %s %s %s">%s%s</span>',
            $roundedClass,
            $colorClass,
            $sizeClass,
            $dotHtml,
            htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );
    }
}