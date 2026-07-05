<?php
namespace Components\Modal;

final class Modal
{
    public static function render(string $id, string $title, string $body, string $size = 'md'): string
    {
        $width = match ($size) {
            'sm' => 'max-w-md',
            'lg' => 'max-w-3xl',
            'xl' => 'max-w-5xl',
            default => 'max-w-lg',
        };

        return <<<HTML
<div id="$id" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
     x-show="open === '$id'" x-cloak
     @click.away="open = ''" x-transition>
    <div class="$width w-full max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
            <h3 class="text-lg font-bold text-gray-900">$title</h3>
            <button @click="open = ''" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <div class="p-6">$body</div>
    </div>
</div>
HTML;
    }
}