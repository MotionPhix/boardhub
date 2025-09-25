// Toast notification system
class ToastManager {
    constructor() {
        this.container = null;
        this.init();
    }

    init() {
        // Create toast container if it doesn't exist
        if (!document.getElementById('toast-container')) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.className = 'fixed top-4 right-4 z-50 space-y-2';
            document.body.appendChild(this.container);
        } else {
            this.container = document.getElementById('toast-container');
        }
    }

    show(message, options = {}) {
        const {
            type = 'info',
            title = null,
            duration = 5000,
            actions = [],
            persistent = false
        } = options;

        const toast = document.createElement('div');
        const toastId = `toast-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;

        toast.id = toastId;
        toast.className = this.getToastClasses(type);
        toast.innerHTML = this.getToastHTML(type, title, message, actions, toastId);

        // Add to container
        this.container.appendChild(toast);

        // Animate in
        setTimeout(() => {
            toast.classList.add('translate-x-0', 'opacity-100');
            toast.classList.remove('translate-x-full', 'opacity-0');
        }, 100);

        // Set up action handlers
        actions.forEach(action => {
            const button = toast.querySelector(`[data-action="${action.action}"]`);
            if (button) {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (action.handler) {
                        action.handler();
                    }
                    this.dismiss(toastId);
                });
            }
        });

        // Set up dismiss button
        const dismissBtn = toast.querySelector('.dismiss-btn');
        if (dismissBtn) {
            dismissBtn.addEventListener('click', () => this.dismiss(toastId));
        }

        // Auto-dismiss unless persistent
        if (!persistent && duration > 0) {
            setTimeout(() => this.dismiss(toastId), duration);
        }

        return toastId;
    }

    dismiss(toastId) {
        const toast = document.getElementById(toastId);
        if (toast) {
            toast.classList.add('translate-x-full', 'opacity-0');
            toast.classList.remove('translate-x-0', 'opacity-100');

            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }
    }

    getToastClasses(type) {
        const baseClasses = 'transform transition-all duration-300 ease-in-out translate-x-full opacity-0 max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden';

        const typeClasses = {
            success: 'border-l-4 border-green-400',
            error: 'border-l-4 border-red-400',
            warning: 'border-l-4 border-yellow-400',
            info: 'border-l-4 border-blue-400',
        };

        return `${baseClasses} ${typeClasses[type] || typeClasses.info}`;
    }

    getToastHTML(type, title, message, actions, toastId) {
        const iconHTML = this.getIconHTML(type);
        const titleHTML = title ? `<p class="text-sm font-medium text-gray-900">${title}</p>` : '';
        const actionsHTML = actions.length > 0 ? this.getActionsHTML(actions) : '';

        return `
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        ${iconHTML}
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        ${titleHTML}
                        <p class="text-sm text-gray-500">${message}</p>
                        ${actionsHTML}
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button class="dismiss-btn bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    getIconHTML(type) {
        const icons = {
            success: `<svg class="h-6 w-6 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>`,
            error: `<svg class="h-6 w-6 text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>`,
            warning: `<svg class="h-6 w-6 text-yellow-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>`,
            info: `<svg class="h-6 w-6 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>`,
        };

        return icons[type] || icons.info;
    }

    getActionsHTML(actions) {
        if (actions.length === 0) return '';

        const buttonsHTML = actions.map(action => {
            const styleClasses = {
                primary: 'bg-indigo-600 hover:bg-indigo-700 text-white',
                success: 'bg-green-600 hover:bg-green-700 text-white',
                warning: 'bg-yellow-600 hover:bg-yellow-700 text-white',
                secondary: 'bg-gray-300 hover:bg-gray-400 text-gray-700',
            };

            const classes = `mt-3 text-sm font-medium px-3 py-1.5 rounded-md ${styleClasses[action.style] || styleClasses.secondary}`;

            return `<button data-action="${action.action}" class="${classes}">${action.label}</button>`;
        }).join(' ');

        return `<div class="mt-2 flex space-x-2">${buttonsHTML}</div>`;
    }

    // Convenience methods
    success(message, options = {}) {
        return this.show(message, { ...options, type: 'success' });
    }

    error(message, options = {}) {
        return this.show(message, { ...options, type: 'error' });
    }

    warning(message, options = {}) {
        return this.show(message, { ...options, type: 'warning' });
    }

    info(message, options = {}) {
        return this.show(message, { ...options, type: 'info' });
    }

    // Clear all toasts
    clear() {
        const toasts = this.container.querySelectorAll('[id^="toast-"]');
        toasts.forEach(toast => {
            this.dismiss(toast.id);
        });
    }
}

// Create global toast instance
const toast = new ToastManager();

export { toast };