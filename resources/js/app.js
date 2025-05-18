import './bootstrap';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
  broadcaster: 'pusher',
  key: import.meta.env.VITE_PUSHER_APP_KEY,
  cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
  forceTLS: true
});

window.Echo.private('contracts')
  .listen('ContractExpiringEvent', (e) => {
    // Handle the notification
    Filament.notify({
      title: 'Contract Expiring',
      message: e.message,
      icon: 'heroicon-o-exclamation-triangle',
      iconColor: 'warning',
      duration: 5000,
    });
  });
