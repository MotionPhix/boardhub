<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import TenantLayout from '@/layouts/TenantLayout.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import { CheckCircle, Sparkles, ArrowRight, Building2, Users, Palette, Target } from 'lucide-vue-next'

interface Tenant {
  id: number
  name: string
  setup_completed: boolean
}

interface Progress {
  business_info: boolean
  team_setup: boolean
  branding: boolean
  setup_completed: boolean
}

interface Props {
  tenant: Tenant
  progress: Progress
  completion_percentage: number
}

const props = defineProps<Props>()

const completedSteps = [
  {
    icon: Building2,
    title: 'Business Information',
    description: 'Your business details have been saved',
    completed: props.progress.business_info
  },
  {
    icon: Users,
    title: 'Team Setup',
    description: 'Team collaboration is ready',
    completed: props.progress.team_setup
  },
  {
    icon: Palette,
    title: 'Branding',
    description: 'Your brand identity is configured',
    completed: props.progress.branding
  }
]

const nextSteps = [
  {
    title: 'Create Your First Campaign',
    description: 'Start by creating a billboard advertising campaign',
    icon: Target,
    action: 'Create Campaign',
    route: 'tenant.campaigns.create'
  },
  {
    title: 'Explore Billboards',
    description: 'Browse available billboard locations',
    icon: Building2,
    action: 'Browse Billboards',
    route: 'tenant.billboards.index'
  },
  {
    title: 'View Analytics',
    description: 'Check your dashboard and analytics',
    icon: Sparkles,
    action: 'View Dashboard',
    route: 'tenant.dashboard'
  }
]
</script>

<template>
  <TenantLayout>
    <Head :title="`Setup Complete - ${tenant.name}`" />

    <div class="max-w-4xl mx-auto">
      <!-- Congratulations Header -->
      <div class="text-center mb-12">
        <div class="flex justify-center mb-6">
          <div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
            <Sparkles class="h-10 w-10 text-green-600 dark:text-green-400" />
          </div>
        </div>
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
          🎉 Congratulations!
        </h1>
        <p class="text-xl text-gray-600 dark:text-gray-400 mb-2">
          {{ tenant.name }} is ready for billboard advertising
        </p>
        <div class="text-lg font-medium text-green-600 dark:text-green-400">
          {{ completion_percentage }}% Setup Complete
        </div>
      </div>

      <!-- Setup Summary -->
      <Card class="mb-8 p-8">
        <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-6 text-center">
          Setup Summary
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div
            v-for="step in completedSteps"
            :key="step.title"
            class="text-center"
          >
            <div class="flex justify-center mb-4">
              <div
                class="w-16 h-16 rounded-full flex items-center justify-center"
                :class="step.completed
                  ? 'bg-green-100 dark:bg-green-900/30'
                  : 'bg-gray-100 dark:bg-gray-800'"
              >
                <CheckCircle
                  v-if="step.completed"
                  class="h-8 w-8 text-green-600 dark:text-green-400"
                />
                <component
                  v-else
                  :is="step.icon"
                  class="h-8 w-8 text-gray-400"
                />
              </div>
            </div>
            <h3 class="font-medium text-gray-900 dark:text-white mb-2">
              {{ step.title }}
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              {{ step.description }}
            </p>
            <div
              v-if="step.completed"
              class="mt-2 text-xs font-medium text-green-600 dark:text-green-400"
            >
              ✓ Completed
            </div>
            <div
              v-else
              class="mt-2 text-xs font-medium text-gray-400"
            >
              Skipped
            </div>
          </div>
        </div>

        <!-- Setup Complete Badge -->
        <div
          v-if="tenant.setup_completed"
          class="mt-8 text-center"
        >
          <div class="inline-flex items-center px-6 py-3 bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-full">
            <CheckCircle class="h-5 w-5 text-green-600 dark:text-green-400 mr-2" />
            <span class="text-green-800 dark:text-green-200 font-medium">
              Organization Setup Complete
            </span>
          </div>
        </div>
      </Card>

      <!-- What's Next -->
      <Card class="mb-8 p-8">
        <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-6 text-center">
          What's Next?
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div
            v-for="step in nextSteps"
            :key="step.title"
            class="text-center"
          >
            <div class="flex justify-center mb-4">
              <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center">
                <component
                  :is="step.icon"
                  class="h-6 w-6 text-indigo-600 dark:text-indigo-400"
                />
              </div>
            </div>
            <h3 class="font-medium text-gray-900 dark:text-white mb-2">
              {{ step.title }}
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
              {{ step.description }}
            </p>
            <Button
              :href="route(step.route)"
              size="sm"
              variant="outline"
            >
              {{ step.action }}
            </Button>
          </div>
        </div>
      </Card>

      <!-- Getting Started Tips -->
      <Card class="mb-8 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          💡 Getting Started Tips
        </h3>
        <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
          <div class="flex items-start space-x-3">
            <div class="w-2 h-2 bg-indigo-500 rounded-full mt-2 flex-shrink-0"></div>
            <p>Start with browsing available billboards in your target locations</p>
          </div>
          <div class="flex items-start space-x-3">
            <div class="w-2 h-2 bg-indigo-500 rounded-full mt-2 flex-shrink-0"></div>
            <p>Create your first campaign with clear objectives and target audience</p>
          </div>
          <div class="flex items-start space-x-3">
            <div class="w-2 h-2 bg-indigo-500 rounded-full mt-2 flex-shrink-0"></div>
            <p>Use analytics to track campaign performance and optimize results</p>
          </div>
          <div class="flex items-start space-x-3">
            <div class="w-2 h-2 bg-indigo-500 rounded-full mt-2 flex-shrink-0"></div>
            <p>Invite team members to collaborate on campaigns and strategies</p>
          </div>
        </div>
      </Card>

      <!-- Call to Action -->
      <div class="text-center">
        <Button
          :href="route('tenant.dashboard')"
          size="lg"
          class="px-8 py-4"
        >
          Go to Dashboard
          <ArrowRight class="ml-2 h-5 w-5" />
        </Button>
        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
          You can always access these setup steps later from your organization settings.
        </p>
      </div>
    </div>
  </TenantLayout>
</template>