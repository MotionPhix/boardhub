import './bootstrap';
import '../css/app.css';

import {createApp, h} from 'vue';
import {createInertiaApp} from '@inertiajs/vue3';
import {resolvePageComponent} from 'laravel-vite-plugin/inertia-helpers';
import {ZiggyVue} from 'ziggy-js';
import {createPinia} from 'pinia';
import {renderApp, putConfig} from '@inertiaui/modal-vue';

// Real-time features
import './realtime';

const appName = import.meta.env.VITE_APP_NAME || 'AdPro';
const pinia = createPinia();

// Configure InertiaUI Modal defaults
putConfig({
  modal: {
    closeButton: true,
    closeExplicitly: false,
    maxWidth: '2xl',
    paddingClasses: 'p-6',
    panelClasses: 'bg-white rounded-lg shadow-2xl',
    position: 'center',
  },
  slideover: {
    closeButton: true,
    closeExplicitly: false,
    maxWidth: 'md',
    paddingClasses: 'p-6',
    panelClasses: 'bg-white min-h-screen shadow-2xl',
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
      .use(pinia);

    // Make Echo available globally
    app.config.globalProperties.$echo = window.Echo;

    return app.mount(el);
  },
  progress: {
    color: '#4f46e5',
  },
});
