# Laravel Echo Configuration Guide

This guide shows how to properly configure Laravel Echo with the new Laravel 12.x approach using `@laravel/echo-vue`.

## Current Status
- Broadcasting is currently disabled (`BROADCAST_CONNECTION=null`)
- Echo configuration is commented out in `app.ts`
- Old echo setup in `realtime.js` is not imported

## To Enable Real-time Features

### 1. Set up Pusher Credentials

Add these to your `.env` file:
```env
BROADCAST_CONNECTION=pusher

# Pusher credentials (get from pusher.com)
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_cluster

# For client-side (add to .env for Vite)
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

### 2. Alternative: Use Laravel Reverb (Laravel's WebSocket server)

Instead of Pusher, you can use Laravel Reverb:
```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### 3. Enable Echo in app.ts

Uncomment and update the Echo configuration in `resources/js/app.ts`:

```typescript
// For Pusher
import { configureEcho } from '@laravel/echo-vue';

configureEcho({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
});

// OR for Reverb
configureEcho({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

### 4. Enable Real-time Manager

Uncomment the import in `resources/js/app.ts`:
```typescript
// Real-time features
import './realtime';
```

### 5. Update Real-time Manager

The existing `realtime.js` file will need to be updated to work with the new Echo setup. The current implementation uses the old Echo approach.

## Laravel 12.x Echo Vue Integration

The new `@laravel/echo-vue` package provides:
- Automatic Echo configuration
- Vue 3 composition API integration
- Better TypeScript support
- Simplified setup

### Usage in Vue Components

```vue
<script setup>
import { useEcho } from '@laravel/echo-vue'

const echo = useEcho()

// Listen to channels
echo.private('orders')
    .listen('OrderShipped', (e) => {
        console.log(e.order)
    })

// Or use the composable approach
const { listen, leave } = useEcho()

onMounted(() => {
    listen('orders', 'OrderShipped', (e) => {
        console.log(e.order)
    })
})

onUnmounted(() => {
    leave('orders')
})
</script>
```

## Current Military-Grade Architecture

The current real-time setup includes:
- Tenant-based channel isolation
- Dashboard metrics updates
- Billboard availability changes
- Booking status updates
- Security notifications
- User-specific notifications

All of this is ready to be activated once proper WebSocket credentials are configured.