import {defineConfig} from 'vite';
import laravel, {refreshPaths} from 'laravel-vite-plugin';
import tailwindcss from 'tailwindcss';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.ts'],
      refresh: [
        ...refreshPaths,
        'app/Livewire/**',
      ],
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
  ],

  resolve: {
    alias: {
      vue: 'vue/dist/vue.esm-bundler.js',
    },
  },

  css: {
    postcss: {
      plugins: [
        tailwindcss()
      ],
    },
  }
});
