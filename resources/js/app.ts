import './bootstrap';
import '../css/app.css';

import {createApp, h} from 'vue';
import {createInertiaApp} from '@inertiajs/vue3';
import {resolvePageComponent} from 'laravel-vite-plugin/inertia-helpers';
import {ZiggyVue} from 'ziggy-js';
import {createPinia} from 'pinia';
import {renderApp, putConfig} from '@inertiaui/modal-vue';
import VueApexCharts from 'vue3-apexcharts';

// Real-time features with Laravel Echo
import { configureEcho } from '@laravel/echo-vue';
import './realtime';

configureEcho({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
});

const appName = import.meta.env.VITE_APP_NAME || 'AdPro';
const pinia = createPinia();

// Configure InertiaUI Modal defaults with dark mode support
putConfig({
  modal: {
    closeButton: false,
    closeExplicitly: false,
    maxWidth: '2xl',
    paddingClasses: 'p-0',
    panelClasses: 'bg-white dark:bg-gray-800 rounded-xl shadow-2xl ring-1 ring-gray-900/10 dark:ring-gray-700 overflow-hidden',
    position: 'center',
  },
  slideover: {
    closeButton: false,
    closeExplicitly: false,
    maxWidth: 'md',
    paddingClasses: 'p-0',
    panelClasses: 'bg-white dark:bg-gray-800 min-h-screen shadow-2xl ring-1 ring-gray-900/10 dark:ring-gray-700',
    position: 'right',
  },
});

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob('./pages/**/*.vue')),
  setup({el, App, props, plugin}) {
    const app = createApp({render: renderApp(App, props)})
      .use(plugin)
      .use(ZiggyVue)
      .use(pinia)
      .use(VueApexCharts);

    return app.mount(el);
  },
  progress: {
    color: '#4f46e5',
  },
});
