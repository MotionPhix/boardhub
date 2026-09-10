import Echo from 'laravel-echo';

import Pusher from 'pusher-js';

window.Pusher = Pusher;

const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;

if (pusherKey) {
  try {
    window.Echo = new Echo({
      broadcaster: 'pusher',
      key: pusherKey,
      cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
      forceTLS: true
    });

    if (window.userId) {
      // Listen for notifications on the user's private channel
      window.Echo.private(`App.Models.User.${window.userId}`)
        .notification((notification) => {
          window.dispatchEvent(new CustomEvent('notificationReceived', {
            detail: {
              id: notification.id,
              title: notification.title,
              message: notification.message,
              status: notification.status,
              icon: notification.icon,
              iconColor: notification.iconColor,
              duration: 5000,
            },
          }));
        });

      // Also listen for specific contract events if needed
      window.Echo.private('contracts')
        .listen('ContractExpiringEvent', (e) => {
          window.$wireui?.notify({
            title: 'Contract Expiring',
            message: e.message,
            icon: 'warning',
            iconColor: 'warning',
            duration: 5000,
          });
        });
    }
  } catch (e) {
    console.warn('Realtime notifications disabled:', e.message);
  }
}
