import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['selector', '[data-theme="dark"]'],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                bg:            'rgb(var(--bg) / <alpha-value>)',
                surface:       'rgb(var(--surface) / <alpha-value>)',
                fg:            'rgb(var(--fg) / <alpha-value>)',
                muted:         'rgb(var(--muted) / <alpha-value>)',
                border:        'rgb(var(--border) / <alpha-value>)',
                'border-soft': 'rgb(var(--border-soft) / <alpha-value>)',
                accent:        'rgb(var(--accent) / <alpha-value>)',
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [],
};
