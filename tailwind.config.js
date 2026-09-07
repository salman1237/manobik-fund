import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    // Client decision (2026-09-07): light theme everywhere, no dark mode.
    // 'selector' (class-based) instead of the default 'media' strategy so
    // dark: utility variants never activate from OS prefers-color-scheme -
    // the public site never adds a `dark` class, so it always renders light.
    darkMode: 'selector',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                // Public Sans for UI/body text (replaces the default Breeze
                // Figtree); Newsreader is the warm serif used for headlines
                // and emotional copy (campaign titles, patient stories).
                sans: ['Public Sans', ...defaultTheme.fontFamily.sans],
                serif: ['Newsreader', 'Georgia', 'serif'],
            },
            // 2026-09-07 redesign tokens - see the "Manobik Fund Design
            // Plan" artifact for the source mockups. Deep teal primary +
            // warm terracotta accent on a warm cream background, replacing
            // the generic default-Tailwind emerald/white look.
            colors: {
                primary: {
                    DEFAULT: 'oklch(45% 0.10 175)',
                    dark: 'oklch(37% 0.10 175)',
                    light: 'oklch(94% 0.03 175)',
                },
                accent: {
                    DEFAULT: 'oklch(62% 0.15 40)',
                    dark: 'oklch(45% 0.15 38)',
                    light: 'oklch(95% 0.035 40)',
                },
                warm: {
                    bg: 'oklch(98% 0.012 75)',
                    surface: 'oklch(100% 0.004 75)',
                    alt: 'oklch(96% 0.016 75)',
                    border: 'oklch(90% 0.016 75)',
                    'border-soft': 'oklch(93% 0.014 75)',
                },
                ink: {
                    DEFAULT: 'oklch(23% 0.02 55)',
                    muted: 'oklch(48% 0.02 55)',
                    faint: 'oklch(56% 0.018 55)',
                },
                danger: {
                    DEFAULT: 'oklch(55% 0.18 25)',
                    light: 'oklch(95% 0.03 25)',
                },
                warn: {
                    DEFAULT: 'oklch(70% 0.14 70)',
                    dark: 'oklch(45% 0.13 70)',
                    light: 'oklch(95% 0.035 70)',
                },
            },
        },
    },

    plugins: [forms],
};
