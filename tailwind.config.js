import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

// Fasilkom Trust Palette: Universitas Subang Royal Blue & Academic Gold Identity
const unsubBlue = {
    50: '#eff6ff',
    100: '#dbeafe',
    200: '#bfdbfe',
    300: '#93c5fd',
    400: '#60a5fa',
    500: '#3b82f6',
    600: '#2563eb', // Royal Blue (Interactive buttons, active states)
    700: '#1d4ed8', // UNSUB Primary Brand (Institutional Trust & Authority)
    800: '#1e40af', // Deep Institutional Navy
    900: '#1e3a8a', // Dark Academic Navy
    950: '#0f172a', // Midnight Navy Surface
};

const unsubGold = {
    50: '#fffbeb',
    100: '#fef3c7',
    200: '#fde68a',
    300: '#fcd34d',
    400: '#fbbf24',
    500: '#f59e0b', // UNSUB Torch Gold / Academic Excellence
    600: '#d97706', // Deep Gold Accent
    700: '#b45309',
    800: '#92400e',
    900: '#78350f',
    950: '#451a03',
};

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: unsubBlue,
                unsub: unsubBlue,
                gold: unsubGold,
                // Aliasing legacy brown and default indigo to unsubBlue transforms all existing UI elements
                // seamlessly into the official Universitas Subang Royal Blue & Trust palette
                brown: unsubBlue,
                indigo: unsubBlue,
            },
        },
    },

    plugins: [forms],
};
