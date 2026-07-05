<?php
namespace Components\Drawer;

final class Drawer
{
    public static function render(string $id, string $title, string $body, string $side = 'right'): string
    {
        $position = $side === 'left' ? 'left-0 -translate-x-full' : 'right-0 translate-x-full';
        $openPos = $side === 'left' ? 'left-0 translate-x-0' : 'right-0 translate-x-0';

        return <<<HTML
<div id="$id" class="fixed inset-0 z-[9999] hidden bg-black/40"
     x-show="drawer === '$id'" x-cloak
     @click.away="drawer = ''" x-transition>
    <div class="fixed top-0 $side-0 h-full w-80 max-w-[90vw] bg-white shadow-2xl overflow-y-auto"
         :class="{ '$openPos': drawer === '$id', '$position': drawer !== '$id' }"
         x-transition:enter="transition duration-300" x-transition:leave="transition duration-300">
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
            <h3 class="text-lg font-bold text-gray-900">$title</h3>
            <button @click="drawer = ''" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <div class="p-6">$body</div>
    </div>
</div>
HTML;
    }
}