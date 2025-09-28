import { toast } from './utils/notifications';

// Real-time event handlers
class RealTimeManager {
    constructor() {
        this.tenantId = this.getTenantId();
        this.userId = this.getUserId();
        this.channels = new Map();

        if (this.tenantId) {
            this.initializeTenantChannels();
        }
    }

    getTenantId() {
        // Extract tenant ID from URL or page props
        const path = window.location.pathname;
        const match = path.match(/\/t\/([a-f0-9\-]{36})/);
        return match ? match[1] : null;
    }

    getUserId() {
        // Get user ID from meta tag or page props
        const meta = document.querySelector('meta[name="user-id"]');
        return meta ? parseInt(meta.getAttribute('content')) : null;
    }

    initializeTenantChannels() {
        // Dashboard metrics channel
        this.subscribeToChannel(`tenant.${this.tenantId}.dashboard`, {
            'dashboard.metrics.updated': this.handleMetricsUpdate,
        });

        // Billboard availability channel
        this.subscribeToChannel(`tenant.${this.tenantId}.billboards`, {
            'billboard.availability.changed': this.handleBillboardAvailabilityChange,
        });

        // Booking updates channel
        this.subscribeToChannel(`tenant.${this.tenantId}.bookings`, {
            'booking.status.updated': this.handleBookingStatusUpdate,
        });

        // Notifications channel
        this.subscribeToChannel(`tenant.${this.tenantId}.notifications`, {
            'notification.sent': this.handleNotification,
        });

        // User-specific notifications if logged in
        if (this.userId) {
            this.subscribeToChannel(`user.${this.userId}.notifications`, {
                'notification.sent': this.handlePersonalNotification,
            });
        }
    }

    subscribeToChannel(channelName, events) {
        // For Laravel 12, we'll use a simpler approach that works with @laravel/echo-vue
        // The actual subscription will be handled by Vue components using useEcho hooks
        this.channels.set(channelName, { events, subscribed: false });
        console.log(`Prepared to subscribe to channel: ${channelName}`);
    }

    handleMetricsUpdate(data) {
        // Update dashboard metrics in real-time
        const event = new CustomEvent('dashboard-metrics-updated', {
            detail: data
        });
        window.dispatchEvent(event);

        // Update any dashboard components
        this.updateDashboardMetrics(data);
    }

    handleBillboardAvailabilityChange(data) {
        console.log('Billboard availability changed:', data);

        // Show notification
        const statusText = data.new_status === 'available' ? 'Available' : 'Occupied';
        toast.info(`${data.billboard.name} is now ${statusText}`);

        // Update billboard listings if visible
        const event = new CustomEvent('billboard-availability-changed', {
            detail: data
        });
        window.dispatchEvent(event);
    }

    handleBookingStatusUpdate(data) {
        console.log('Booking status updated:', data);

        // Show appropriate notification
        const messages = {
            confirmed: `Booking confirmed for ${data.booking.billboard_name}! 🎉`,
            rejected: `Booking rejected for ${data.booking.billboard_name}`,
            cancelled: `Booking cancelled for ${data.booking.billboard_name}`,
            completed: `Booking completed for ${data.booking.billboard_name}`,
        };

        const message = messages[data.new_status];
        if (message) {
            const type = data.new_status === 'confirmed' ? 'success' :
                        data.new_status === 'rejected' ? 'error' : 'info';
            toast[type](message);
        }

        // Update booking components
        const event = new CustomEvent('booking-status-updated', {
            detail: data
        });
        window.dispatchEvent(event);
    }

    handleNotification(data) {
        console.log('Real-time notification:', data);

        // Show toast notification
        const toastType = {
            urgent: 'error',
            high: 'warning',
            normal: 'info',
            low: 'info'
        }[data.priority] || 'info';

        toast[toastType](data.message, {
            title: data.title,
            duration: data.priority === 'urgent' ? 0 : 5000, // Urgent notifications stay until dismissed
            actions: data.action_buttons || []
        });

        // Update notification center
        this.addToNotificationCenter(data);
    }

    handlePersonalNotification(data) {
        // Handle personal notifications (same as regular but might have different styling)
        this.handleNotification(data);

        // Could add personal notification badge update here
        this.updateNotificationBadge();
    }

    updateDashboardMetrics(data) {
        // Find and update dashboard metric elements
        const elements = document.querySelectorAll(`[data-metric-type="${data.metric_type}"]`);

        elements.forEach(element => {
            const metric = element.getAttribute('data-metric-key');
            if (data.metrics[metric] !== undefined) {
                const valueEl = element.querySelector('.metric-value');
                if (valueEl) {
                    // Animate the change
                    valueEl.classList.add('updating');
                    setTimeout(() => {
                        valueEl.textContent = this.formatMetricValue(data.metrics[metric], metric);
                        valueEl.classList.remove('updating');
                        valueEl.classList.add('updated');
                        setTimeout(() => valueEl.classList.remove('updated'), 1000);
                    }, 200);
                }
            }
        });
    }

    formatMetricValue(value, type) {
        if (type.includes('revenue') || type.includes('price')) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(value);
        }

        if (type.includes('percentage') || type.includes('rate')) {
            return `${value}%`;
        }

        return new Intl.NumberFormat('en-US').format(value);
    }

    addToNotificationCenter(notification) {
        // Add notification to in-app notification center
        const event = new CustomEvent('notification-received', {
            detail: notification
        });
        window.dispatchEvent(event);
    }

    updateNotificationBadge() {
        // Update notification badge count
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            const count = parseInt(badge.textContent || '0') + 1;
            badge.textContent = count;
            badge.classList.add('animate-bounce');
            setTimeout(() => badge.classList.remove('animate-bounce'), 1000);
        }
    }

    // Method to manually subscribe to specific billboard updates
    subscribeToBillboard(billboardId) {
        const channelName = `billboard.${billboardId}`;
        if (!this.channels.has(channelName)) {
            this.subscribeToChannel(channelName, {
                'billboard.availability.changed': this.handleBillboardAvailabilityChange,
            });
        }
    }

    // Method to subscribe to specific booking updates
    subscribeToBooking(bookingId) {
        const channelName = `booking.${bookingId}`;
        if (!this.channels.has(channelName)) {
            this.subscribeToChannel(channelName, {
                'booking.status.updated': this.handleBookingStatusUpdate,
            });
        }
    }

    // Clean up channels when leaving pages
    unsubscribeFromChannel(channelName) {
        const channel = this.channels.get(channelName);
        if (channel && channelName) {
            try {
                window.Echo.leave(channelName);
                this.channels.delete(channelName);
            } catch (error) {
                console.warn('Error unsubscribing from channel:', channelName, error);
            }
        }
    }

    destroy() {
        // Clean up all channels
        this.channels.forEach((channel, channelName) => {
            this.unsubscribeFromChannel(channelName);
        });
    }
}

// Initialize real-time manager
const realTimeManager = new RealTimeManager();

// Make it globally available
window.realTimeManager = realTimeManager;

// Expose methods for Vue components
window.subscribeToRealTime = {
    billboard: (id) => realTimeManager.subscribeToBillboard(id),
    booking: (id) => realTimeManager.subscribeToBooking(id),
    unsubscribe: (channel) => realTimeManager.unsubscribeFromChannel(channel),
};

// Clean up when page unloads
window.addEventListener('beforeunload', () => {
    realTimeManager.destroy();
});