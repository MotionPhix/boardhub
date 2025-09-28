<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BillingPlansSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $plans = [
      [
        'name' => 'trial',
        'display_name' => 'Free Trial',
        'description' => '14-day free trial with basic features',
        'price' => 0.00,
        'annual_price' => 0.00,
        'trial_days' => 14,
        'is_popular' => false,
        'is_active' => true,
        'features' => [
          'Basic campaign management',
          'Up to 3 active campaigns',
          'Basic analytics dashboard',
          'Email support',
          'Standard templates'
        ],
        'limits' => [
          'max_campaigns' => 3,
          'max_team_members' => 1,
          'max_monthly_impressions' => 10000,
          'storage_gb' => 1,
        ],
        'sort_order' => 1,
      ],
      [
        'name' => 'basic',
        'display_name' => 'Basic Plan',
        'description' => 'Perfect for small businesses and startups getting started',
        'price' => 29.00,
        'annual_price' => 290.00, // 2 months free
        'trial_days' => 7,
        'is_popular' => false,
        'is_active' => true,
        'features' => [
          'Everything in Trial',
          'Up to 15 active campaigns',
          'Advanced analytics',
          'Team management (up to 3 members)',
          'Priority email support',
          'Custom templates',
          'Basic automation'
        ],
        'limits' => [
          'max_campaigns' => 15,
          'max_team_members' => 3,
          'max_monthly_impressions' => 100000,
          'storage_gb' => 10,
          'api_calls_per_month' => 1000,
        ],
        'sort_order' => 2,
      ],
      [
        'name' => 'pro',
        'display_name' => 'Pro Plan',
        'description' => 'Advanced features for growing businesses and marketing agencies',
        'price' => 79.00,
        'annual_price' => 790.00, // 2 months free
        'trial_days' => 14,
        'is_popular' => true,
        'is_active' => true,
        'features' => [
          'Everything in Basic',
          'Unlimited campaigns',
          'Advanced reporting & insights',
          'Team management (up to 10 members)',
          'Priority phone & chat support',
          'Advanced automation',
          'A/B testing',
          'Custom integrations',
          'API access',
          'White-label reporting'
        ],
        'limits' => [
          'max_campaigns' => -1, // Unlimited
          'max_team_members' => 10,
          'max_monthly_impressions' => 1000000,
          'storage_gb' => 100,
          'api_calls_per_month' => 10000,
          'automation_rules' => 50,
        ],
        'sort_order' => 3,
      ],
      [
        'name' => 'enterprise',
        'display_name' => 'Enterprise Plan',
        'description' => 'Military-grade solution for large organizations with premium support',
        'price' => 199.00,
        'annual_price' => 1990.00, // 2 months free
        'trial_days' => 30,
        'is_popular' => false,
        'is_active' => true,
        'features' => [
          'Everything in Pro',
          'Unlimited everything',
          'Custom branding',
          'Dedicated account manager',
          '24/7 priority support',
          'Custom integrations',
          'Advanced security features',
          'SLA guarantee',
          'Custom training',
          'On-premise deployment option',
          'Advanced user permissions',
          'Compliance reporting'
        ],
        'limits' => [
          'max_campaigns' => -1, // Unlimited
          'max_team_members' => -1, // Unlimited
          'max_monthly_impressions' => -1, // Unlimited
          'storage_gb' => -1, // Unlimited
          'api_calls_per_month' => -1, // Unlimited
          'automation_rules' => -1, // Unlimited
        ],
        'sort_order' => 4,
      ],
    ];

    foreach ($plans as $planData) {
      \App\Models\BillingPlan::updateOrCreate(
        ['name' => $planData['name']],
        $planData
      );
    }
  }
}
