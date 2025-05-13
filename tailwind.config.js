import defaultTheme from 'tailwindcss/defaultTheme';
import preset from './vendor/filament/support/tailwind.config.preset'
import colors from 'tailwindcss/colors';

/** @type {import('tailwindcss').Config} */
export default {
  preset: [preset],
  content: [
    './app/Filament/**/*.php',
    './resources/views/filament/**/*.blade.php',
    './vendor/filament/**/*.blade.php',
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
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

