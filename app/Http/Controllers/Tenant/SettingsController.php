<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $tenant = app('tenant');

        return Inertia::render('tenant/settings/Index', [
            'tenant' => $tenant->load(['subscriptions' => function ($query) {
                $query->active()->latest();
            }]),
            'settings' => $this->getOrganizationSettings($tenant),
            'integrations' => $this->getIntegrationSettings($tenant),
            'notifications' => $this->getNotificationSettings($tenant)
        ]);
    }

    public function general(Request $request)
    {
        $tenant = app('tenant');

        if ($request->isMethod('post')) {
            return $this->updateGeneral($request);
        }

        return Inertia::render('tenant/settings/General', [
            'tenant' => $tenant,
            'settings' => $this->getOrganizationSettings($tenant),
            'timezones' => $this->getTimezones(),
            'currencies' => $this->getCurrencies(),
            'countries' => $this->getCountries()
        ]);
    }

    public function branding(Request $request)
    {
        $tenant = app('tenant');

        if ($request->isMethod('post')) {
            return $this->updateBranding($request);
        }

        return Inertia::render('tenant/settings/Branding', [
            'tenant' => $tenant,
            'settings' => $this->getBrandingSettings($tenant),
            'colorPresets' => $this->getColorPresets()
        ]);
    }

    public function billing(Request $request)
    {
        $tenant = app('tenant');

        $subscription = $tenant->subscriptions()->active()->first();
        $paymentMethods = $this->getPaymentMethods($tenant);
        $invoices = $this->getInvoices($tenant);

        return Inertia::render('tenant/settings/Billing', [
            'tenant' => $tenant,
            'subscription' => $subscription,
            'paymentMethods' => $paymentMethods,
            'invoices' => $invoices,
            'plans' => $this->getAvailablePlans()
        ]);
    }

    public function integrations(Request $request)
    {
        $tenant = app('tenant');

        if ($request->isMethod('post')) {
            return $this->updateIntegrations($request);
        }

        return Inertia::render('tenant/settings/Integrations', [
            'tenant' => $tenant,
            'integrations' => $this->getIntegrationSettings($tenant),
            'availableIntegrations' => $this->getAvailableIntegrations()
        ]);
    }

    public function notifications(Request $request)
    {
        $tenant = app('tenant');

        if ($request->isMethod('post')) {
            return $this->updateNotifications($request);
        }

        return Inertia::render('tenant/settings/Notifications', [
            'tenant' => $tenant,
            'settings' => $this->getNotificationSettings($tenant),
            'channels' => $this->getNotificationChannels()
        ]);
    }

    public function security(Request $request)
    {
        $tenant = app('tenant');

        if ($request->isMethod('post')) {
            return $this->updateSecurity($request);
        }

        return Inertia::render('tenant/settings/Security', [
            'tenant' => $tenant,
            'settings' => $this->getSecuritySettings($tenant),
            'sessions' => $this->getActiveSessions($tenant),
            'auditLogs' => $this->getAuditLogs($tenant)
        ]);
    }

    private function updateGeneral(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'website' => 'nullable|url',
            'phone' => 'nullable|string|max:20',
            'timezone' => 'required|string',
            'currency' => 'required|string|size:3',
            'language' => 'required|string|size:2',
            'contact_info' => 'nullable|array',
            'contact_info.address' => 'nullable|string|max:500',
            'contact_info.city' => 'nullable|string|max:100',
            'contact_info.state' => 'nullable|string|max:100',
            'contact_info.country' => 'nullable|string|max:100',
            'contact_info.postal_code' => 'nullable|string|max:20'
        ]);

        $tenant->update($validated);

        return back()->with('success', 'General settings updated successfully!');
    }

    private function updateBranding(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'logo_url' => 'nullable|url',
            'primary_color' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
            'secondary_color' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
            'accent_color' => 'nullable|string|regex:/^#[0-9A-F]{6}$/i',
            'font_family' => 'nullable|string|max:100',
            'custom_css' => 'nullable|string|max:5000',
            'branding_settings' => 'nullable|array'
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($tenant->logo_url) {
                $oldLogoPath = str_replace('/storage/', '', parse_url($tenant->logo_url, PHP_URL_PATH));
                Storage::disk('public')->delete($oldLogoPath);
            }

            $logoPath = $request->file('logo')->store('tenant-logos', 'public');
            $validated['logo_url'] = Storage::url($logoPath);
            unset($validated['logo']);
        }

        $tenant->update($validated);

        return back()->with('success', 'Branding settings updated successfully!');
    }

    private function updateIntegrations(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'integrations' => 'required|array',
            'integrations.google_analytics.enabled' => 'boolean',
            'integrations.google_analytics.tracking_id' => 'nullable|string',
            'integrations.facebook_pixel.enabled' => 'boolean',
            'integrations.facebook_pixel.pixel_id' => 'nullable|string',
            'integrations.stripe.enabled' => 'boolean',
            'integrations.stripe.public_key' => 'nullable|string',
            'integrations.stripe.secret_key' => 'nullable|string',
            'integrations.mailchimp.enabled' => 'boolean',
            'integrations.mailchimp.api_key' => 'nullable|string',
            'integrations.slack.enabled' => 'boolean',
            'integrations.slack.webhook_url' => 'nullable|url'
        ]);

        $settings = $tenant->settings ?? [];
        $settings['integrations'] = $validated['integrations'];

        $tenant->update(['settings' => $settings]);

        return back()->with('success', 'Integration settings updated successfully!');
    }

    private function updateNotifications(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'notifications' => 'required|array',
            'notifications.email_enabled' => 'boolean',
            'notifications.sms_enabled' => 'boolean',
            'notifications.slack_enabled' => 'boolean',
            'notifications.booking_notifications' => 'boolean',
            'notifications.payment_notifications' => 'boolean',
            'notifications.team_notifications' => 'boolean',
            'notifications.maintenance_notifications' => 'boolean',
            'notifications.marketing_notifications' => 'boolean'
        ]);

        $settings = $tenant->settings ?? [];
        $settings['notifications'] = $validated['notifications'];

        $tenant->update(['settings' => $settings]);

        return back()->with('success', 'Notification settings updated successfully!');
    }

    private function updateSecurity(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'security' => 'required|array',
            'security.two_factor_required' => 'boolean',
            'security.session_timeout' => 'integer|min:15|max:1440',
            'security.password_policy.min_length' => 'integer|min:6|max:50',
            'security.password_policy.require_uppercase' => 'boolean',
            'security.password_policy.require_lowercase' => 'boolean',
            'security.password_policy.require_numbers' => 'boolean',
            'security.password_policy.require_symbols' => 'boolean',
            'security.ip_whitelist' => 'nullable|array',
            'security.ip_whitelist.*' => 'ip'
        ]);

        $settings = $tenant->settings ?? [];
        $settings['security'] = $validated['security'];

        $tenant->update(['settings' => $settings]);

        return back()->with('success', 'Security settings updated successfully!');
    }

    private function getOrganizationSettings($tenant): array
    {
        return [
            'name' => $tenant->name,
            'description' => $tenant->description,
            'website' => $tenant->website,
            'phone' => $tenant->phone,
            'timezone' => $tenant->timezone ?? 'UTC',
            'currency' => $tenant->currency ?? 'USD',
            'language' => $tenant->language ?? 'en',
            'contact_info' => $tenant->contact_info ?? []
        ];
    }

    private function getBrandingSettings($tenant): array
    {
        return [
            'logo_url' => $tenant->logo_url,
            'primary_color' => $tenant->primary_color ?? '#6366f1',
            'secondary_color' => $tenant->secondary_color ?? '#8b5cf6',
            'accent_color' => $tenant->accent_color ?? '#06b6d4',
            'font_family' => $tenant->font_family ?? 'Inter',
            'custom_css' => $tenant->custom_css,
            'branding_settings' => $tenant->branding_settings ?? []
        ];
    }

    private function getIntegrationSettings($tenant): array
    {
        $settings = $tenant->settings ?? [];
        return $settings['integrations'] ?? [
            'google_analytics' => ['enabled' => false],
            'facebook_pixel' => ['enabled' => false],
            'stripe' => ['enabled' => false],
            'mailchimp' => ['enabled' => false],
            'slack' => ['enabled' => false]
        ];
    }

    private function getNotificationSettings($tenant): array
    {
        $settings = $tenant->settings ?? [];
        return $settings['notifications'] ?? [
            'email_enabled' => true,
            'sms_enabled' => false,
            'slack_enabled' => false,
            'booking_notifications' => true,
            'payment_notifications' => true,
            'team_notifications' => true,
            'maintenance_notifications' => true,
            'marketing_notifications' => false
        ];
    }

    private function getSecuritySettings($tenant): array
    {
        $settings = $tenant->settings ?? [];
        return $settings['security'] ?? [
            'two_factor_required' => false,
            'session_timeout' => 60,
            'password_policy' => [
                'min_length' => 8,
                'require_uppercase' => true,
                'require_lowercase' => true,
                'require_numbers' => true,
                'require_symbols' => false
            ],
            'ip_whitelist' => []
        ];
    }

    private function getTimezones(): array
    {
        return [
            'UTC' => 'UTC',
            'America/New_York' => 'Eastern Time (ET)',
            'America/Chicago' => 'Central Time (CT)',
            'America/Denver' => 'Mountain Time (MT)',
            'America/Los_Angeles' => 'Pacific Time (PT)',
            'Europe/London' => 'Greenwich Mean Time (GMT)',
            'Europe/Paris' => 'Central European Time (CET)',
            'Asia/Tokyo' => 'Japan Standard Time (JST)',
            'Asia/Shanghai' => 'China Standard Time (CST)',
            'Australia/Sydney' => 'Australian Eastern Time (AET)'
        ];
    }

    private function getCurrencies(): array
    {
        return [
            'USD' => 'US Dollar ($)',
            'EUR' => 'Euro (€)',
            'GBP' => 'British Pound (£)',
            'JPY' => 'Japanese Yen (¥)',
            'CAD' => 'Canadian Dollar (C$)',
            'AUD' => 'Australian Dollar (A$)',
            'ZMW' => 'Zambian Kwacha (ZK)'
        ];
    }

    private function getCountries(): array
    {
        return [
            'US' => 'United States',
            'CA' => 'Canada',
            'GB' => 'United Kingdom',
            'DE' => 'Germany',
            'FR' => 'France',
            'JP' => 'Japan',
            'AU' => 'Australia',
            'ZM' => 'Zambia'
        ];
    }

    private function getColorPresets(): array
    {
        return [
            'indigo' => ['primary' => '#6366f1', 'secondary' => '#8b5cf6'],
            'blue' => ['primary' => '#3b82f6', 'secondary' => '#06b6d4'],
            'green' => ['primary' => '#10b981', 'secondary' => '#059669'],
            'purple' => ['primary' => '#8b5cf6', 'secondary' => '#a855f7'],
            'pink' => ['primary' => '#ec4899', 'secondary' => '#f43f5e'],
            'orange' => ['primary' => '#f59e0b', 'secondary' => '#ea580c']
        ];
    }

    private function getAvailableIntegrations(): array
    {
        return [
            'google_analytics' => [
                'name' => 'Google Analytics',
                'description' => 'Track website traffic and user behavior',
                'icon' => 'google-analytics',
                'category' => 'Analytics'
            ],
            'facebook_pixel' => [
                'name' => 'Facebook Pixel',
                'description' => 'Track conversions and create custom audiences',
                'icon' => 'facebook',
                'category' => 'Marketing'
            ],
            'stripe' => [
                'name' => 'Stripe',
                'description' => 'Accept credit card payments',
                'icon' => 'stripe',
                'category' => 'Payments'
            ],
            'mailchimp' => [
                'name' => 'Mailchimp',
                'description' => 'Email marketing and automation',
                'icon' => 'mailchimp',
                'category' => 'Marketing'
            ],
            'slack' => [
                'name' => 'Slack',
                'description' => 'Team notifications and updates',
                'icon' => 'slack',
                'category' => 'Communication'
            ]
        ];
    }

    private function getNotificationChannels(): array
    {
        return [
            'email' => [
                'name' => 'Email',
                'description' => 'Receive notifications via email',
                'icon' => 'mail'
            ],
            'sms' => [
                'name' => 'SMS',
                'description' => 'Receive notifications via text message',
                'icon' => 'message-circle'
            ],
            'slack' => [
                'name' => 'Slack',
                'description' => 'Receive notifications in Slack channels',
                'icon' => 'slack'
            ]
        ];
    }

    private function getPaymentMethods($tenant): array
    {
        // This would typically fetch from Stripe or other payment processor
        return [];
    }

    private function getInvoices($tenant): array
    {
        // This would typically fetch invoices from billing system
        return [];
    }

    private function getAvailablePlans(): array
    {
        return [
            'starter' => [
                'name' => 'Starter',
                'price' => 29,
                'interval' => 'month',
                'features' => [
                    'Up to 5 billboards',
                    'Basic analytics',
                    'Email support',
                    '1 team member'
                ]
            ],
            'professional' => [
                'name' => 'Professional',
                'price' => 79,
                'interval' => 'month',
                'features' => [
                    'Up to 25 billboards',
                    'Advanced analytics',
                    'Priority support',
                    '5 team members',
                    'Custom branding'
                ]
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'price' => 199,
                'interval' => 'month',
                'features' => [
                    'Unlimited billboards',
                    'Full analytics suite',
                    'Dedicated support',
                    'Unlimited team members',
                    'Custom integrations',
                    'API access'
                ]
            ]
        ];
    }

    private function getActiveSessions($tenant): array
    {
        // This would fetch active user sessions for the tenant
        return [];
    }

    private function getAuditLogs($tenant): array
    {
        // This would fetch recent audit log entries
        return [];
    }
}
