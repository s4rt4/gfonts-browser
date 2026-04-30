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
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                card:        '0 1px 2px 0 rgb(var(--fg) / 0.04)',
                'card-hover':'0 4px 14px -2px rgb(var(--fg) / 0.08)',
                popover:     '0 8px 32px -4px rgb(var(--fg) / 0.16)',
            },
            keyframes: {
                'shimmer': {
                    '0%':   { backgroundPosition: '-200% 0' },
                    '100%': { backgroundPosition: '200% 0' },
                },
                'toast-in': {
                    '0%':   { opacity: '0', transform: 'translateY(8px) scale(0.98)' },
                    '100%': { opacity: '1', transform: 'translateY(0) scale(1)' },
                },
                'fade-in': {
                    '0%':   { opacity: '0' },
                    '100%': { opacity: '1' },
                },
            },
            animation: {
                shimmer:  'shimmer 1.4s linear infinite',
                'toast-in':'toast-in 180ms cubic-bezier(0.16, 1, 0.3, 1)',
                'fade-in':'fade-in 200ms ease-out',
            },
        },
    },
    plugins: [],
};
