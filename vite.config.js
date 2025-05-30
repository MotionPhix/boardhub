import {defineConfig} from 'vite';
import laravel, {refreshPaths} from 'laravel-vite-plugin';
import tailwindcss from 'tailwindcss';

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: [
        ...refreshPaths,
        'app/Livewire/**',
      ],
    }),
  ],

  css: {
    postcss: {
      plugins: [tailwindcss()],
    },
  }
});
