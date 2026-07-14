<?php
/**
 * MasjidPay Theme Configuration
 *
 * Primary Orange : #F68B1F  (246, 139, 31)
 * Bright Orange  : #FF9F1A  (255, 159, 26)
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
            50: '#fef5e7',
            100: '#fde8cc',
            200: '#fbd199',
            300: '#f9ba66',
            400: '#f7a333',
            500: '#F68B1F',
            600: '#c46f19',
            700: '#935313',
            800: '#62380c',
            900: '#311c06',
        },
        brand: {
            orange: '#F68B1F',
            'orange-bright': '#FF9F1A',
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