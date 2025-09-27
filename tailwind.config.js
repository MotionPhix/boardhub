import defaultTheme from 'tailwindcss/defaultTheme';
import colors from 'tailwindcss/colors';

/** @type {import('tailwindcss').Config} */
export default {
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
  plugins: [],
}

