import defaultTheme from 'tailwindcss/defaultTheme';
import colors from 'tailwindcss/colors';
import scrollbar from 'tailwind-scrollbar';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';
import animate from 'tailwindcss-animate';

/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
    './node_modules/@inertiaui/modal-vue/src/**/*.{js,vue}',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Geist Mono', ...defaultTheme.fontFamily.mono],
      },
      colors: {
        danger: colors.rose,
        primary: colors.amber,
        success: colors.emerald,
        warning: colors.orange,
      },
    },
  },
  plugins: [
    forms,
    typography,
    scrollbar,
    animate
  ],
}

