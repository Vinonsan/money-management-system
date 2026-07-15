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

/**
 * Premium Red palette for Super Admin section
 * Dark, bold red — luxurious & authoritative.
 * Replaces the orange primary with a rich red scale.
 */
function superAdminTailwindColorsJs(): string {
    return <<<'JS'
{
    colors: {
        primary: {
            50: '#fef2f2',
            100: '#ffe1e1',
            200: '#ffc7c7',
            300: '#ffa0a0',
            400: '#ff6b6b',
            500: '#f83e3e',
            600: '#e51d1d',
            700: '#c21414',
            800: '#a01414',
            900: '#841818',
            950: '#480a0a',
        },
        brand: {
            orange: '#F68B1F',
            'orange-bright': '#FF9F1A',
            charcoal: '#1E1E1E',
            gray: '#2F2F32',
            shadow: '#4A4A4A',
        },
    }
}
JS;
}