<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Branding and customization
            $table->string('logo_url')->nullable()->after('name');
            $table->string('primary_color', 7)->default('#6366f1')->after('logo_url');
            $table->string('secondary_color', 7)->default('#8b5cf6')->after('primary_color');
            $table->json('branding_settings')->nullable()->after('secondary_color');

            // Business information
            $table->string('business_type')->nullable()->after('branding_settings');
            $table->string('industry')->nullable()->after('business_type');
            $table->string('company_size')->nullable()->after('industry');
            $table->json('contact_info')->nullable()->after('company_size');

            // Subscription and features
            $table->string('subscription_tier')->default('trial')->after('contact_info');
            $table->json('feature_flags')->nullable()->after('subscription_tier');
            $table->decimal('monthly_revenue', 10, 2)->default(0)->after('feature_flags');
            $table->integer('user_limit')->default(5)->after('monthly_revenue');
            $table->integer('billboard_limit')->default(100)->after('user_limit');

            // Analytics and tracking
            $table->json('usage_stats')->nullable()->after('billboard_limit');
            $table->timestamp('last_activity_at')->nullable()->after('usage_stats');
            $table->integer('total_bookings')->default(0)->after('last_activity_at');
            $table->decimal('total_revenue', 12, 2)->default(0)->after('total_bookings');

            // Compliance and security
            $table->json('compliance_settings')->nullable()->after('total_revenue');
            $table->string('data_region')->default('mw')->after('compliance_settings');
            $table->boolean('requires_2fa')->default(false)->after('data_region');

            // Onboarding and setup
            $table->json('onboarding_progress')->nullable()->after('requires_2fa');
            $table->boolean('setup_completed')->default(false)->after('onboarding_progress');
            $table->timestamp('activated_at')->nullable()->after('setup_completed');

            // Indexes for performance
            $table->index('subscription_tier');
            $table->index('business_type');
            $table->index(['is_active', 'setup_completed']);
            $table->index('last_activity_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'logo_url', 'primary_color', 'secondary_color', 'branding_settings',
                'business_type', 'industry', 'company_size', 'contact_info',
                'subscription_tier', 'feature_flags', 'monthly_revenue', 'user_limit', 'billboard_limit',
                'usage_stats', 'last_activity_at', 'total_bookings', 'total_revenue',
                'compliance_settings', 'data_region', 'requires_2fa',
                'onboarding_progress', 'setup_completed', 'activated_at'
            ]);
        });
    }
};