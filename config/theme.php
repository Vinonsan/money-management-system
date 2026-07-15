<?php
/**
 * MasjidPay Theme Configuration
 *
 * Primary Purple : #321E48  (50, 30, 72)
 * Charcoal Black : #1E1E1E  (30, 30, 30)
 * Dark Gray      : #2F2F32  (47, 47, 50)
 * White          : #FFFFFF  (255, 255, 255)
 * Soft Shadow    : #4A4A4A  (74, 74, 74)
 */

function themeTailwindColorsJs(): string {
    return <<<'JS'
{
    colors: {
        primary: {
            50: '#f5f0fa',
            100: '#ebe2f3',
            200: '#d7c5e7',
            300: '#c3a8db',
            400: '#af8bcf',
            500: '#8259b2',
            600: '#5a3c85',
            700: '#321E48',
            800: '#28153a',
            900: '#1e0d2c',
            950: '#14061e',
        },
        brand: {
            purple: '#321E48',
            charcoal: '#1E1E1E',
            gray: '#2F2F32',
            shadow: '#4A4A4A',
        }
    }
}
JS;
}

function adminTailwindColorsJs(): string {
    return themeTailwindColorsJs();
}

/**
 * Premium Purple palette for Super Admin section
 * Uses the same #321E48 brand color consistently across all UI.
 */
function superAdminTailwindColorsJs(): string {
    return themeTailwindColorsJs();
}