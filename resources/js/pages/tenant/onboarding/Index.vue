<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import TenantLayout from '@/layouts/TenantLayout.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import { CheckCircle, Clock, ArrowRight, Building2, Users, Palette, Sparkles } from 'lucide-vue-next'

interface Tenant {
  id: number
  name: string
  setup_completed: boolean
  onboarding_progress: Record<string, boolean>
}

interface Progress {
  business_info: boolean
  team_setup: boolean
  branding: boolean
  setup_completed: boolean
}

interface NextStep {
  name: string
  description: string
  route: string
  icon: string
}

interface Props {
  tenant: Tenant
  progress: Progress
  current_step: string
  next_steps: NextStep[]
}

const props = defineProps<Props>()

const completionPercentage = computed(() => {
  const completed = Object.values(props.progress).filter(Boolean).length
  const total = Object.keys(props.progress).length
  return Math.round((completed / total) * 100)
})

const getStepIcon = (step: string) => {
  const icons = {
    'business_info': Building2,
    'team_setup': Users,
    'branding': Palette,
    'complete': Sparkles
  }
  return icons[step] || Clock
}

const getStepStatus = (stepKey: string) => {
  if (props.progress[stepKey]) {
    return 'completed'
  }
  if (stepKey === props.current_step) {
    return 'current'
  }
  return 'pending'
}

const steps = [
  {
    key: 'business_info',
    name: 'Business Information',
    description: 'Tell us about your business',
    route: 'tenant.onboarding.business-info'
  },
  {
    key: 'team_setup',
    name: 'Team Setup',
    description: 'Add team members (optional)',
    route: 'tenant.onboarding.team-setup'
  },
  {
    key: 'branding',
    name: 'Branding',
    description: 'Customize your organization',
    route: 'tenant.onboarding.branding'
  }
]
</script>

<template>
  <TenantLayout>
    <Head :title="`Setup ${tenant.name}`" />

    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
          Welcome to {{ tenant.name }}! 🎉
        </h1>
        <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">
          Let's get your organization set up and ready for billboard advertising.
        </p>
      </div>

      <!-- Progress Overview -->
      <Card class="mb-8 p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
            Setup Progress
          </h2>
          <div class="text-right">
            <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
              {{ completionPercentage }}%
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
              Complete
            </div>
          </div>
        </div>

        <!-- Progress Bar -->
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 mb-6">
          <div
            class="bg-gradient-to-r from-indigo-500 to-purple-600 h-3 rounded-full transition-all duration-500"
            :style="{ width: `${completionPercentage}%` }"
          ></div>
        </div>

        <!-- Setup Complete Message -->
        <div
          v-if="tenant.setup_completed"
          class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-6"
        >
          <div class="flex items-center">
            <CheckCircle class="h-5 w-5 text-green-600 dark:text-green-400 mr-3" />
            <div>
              <h3 class="text-sm font-medium text-green-800 dark:text-green-200">
                Organization Setup Complete!
              </h3>
              <p class="text-sm text-green-700 dark:text-green-300 mt-1">
                Your organization is ready for billboard advertising. You can now create campaigns and manage bookings.
              </p>
            </div>
          </div>
        </div>
      </Card>

      <!-- Setup Steps -->
      <div class="space-y-4 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
          Setup Steps
        </h2>

        <div
          v-for="step in steps"
          :key="step.key"
          class="relative"
        >
          <Card
            class="p-6 transition-all duration-200 hover:shadow-md"
            :class="{
              'ring-2 ring-indigo-500 dark:ring-indigo-400': getStepStatus(step.key) === 'current',
              'bg-green-50 dark:bg-green-900/10 border-green-200 dark:border-green-800': getStepStatus(step.key) === 'completed'
            }"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-4">
                <!-- Step Icon -->
                <div
                  class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center"
                  :class="{
                    'bg-green-100 dark:bg-green-900/30': getStepStatus(step.key) === 'completed',
                    'bg-indigo-100 dark:bg-indigo-900/30': getStepStatus(step.key) === 'current',
                    'bg-gray-100 dark:bg-gray-800': getStepStatus(step.key) === 'pending'
                  }"
                >
                  <CheckCircle
                    v-if="getStepStatus(step.key) === 'completed'"
                    class="h-6 w-6 text-green-600 dark:text-green-400"
                  />
                  <component
                    v-else
                    :is="getStepIcon(step.key)"
                    class="h-6 w-6"
                    :class="{
                      'text-indigo-600 dark:text-indigo-400': getStepStatus(step.key) === 'current',
                      'text-gray-400 dark:text-gray-500': getStepStatus(step.key) === 'pending'
                    }"
                  />
                </div>

                <!-- Step Info -->
                <div>
                  <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ step.name }}
                  </h3>
                  <p class="text-gray-600 dark:text-gray-400">
                    {{ step.description }}
                  </p>
                </div>
              </div>

              <!-- Action Button -->
              <div class="flex items-center space-x-3">
                <div
                  v-if="getStepStatus(step.key) === 'completed'"
                  class="text-green-600 dark:text-green-400 text-sm font-medium"
                >
                  ✓ Complete
                </div>

                <Link
                  v-else
                  :href="route(step.route)"
                  class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 transition-colors"
                >
                  {{ getStepStatus(step.key) === 'current' ? 'Continue' : 'Start' }}
                  <ArrowRight class="ml-2 h-4 w-4" />
                </Link>
              </div>
            </div>
          </Card>
        </div>
      </div>

      <!-- Quick Actions -->
      <Card class="p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
          Quick Actions
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Link
            :href="route('tenant.dashboard')"
            class="flex items-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
          >
            <div>
              <h3 class="font-medium text-gray-900 dark:text-white">
                Go to Dashboard
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Access your main dashboard
              </p>
            </div>
            <ArrowRight class="ml-auto h-5 w-5 text-gray-400" />
          </Link>

          <div
            v-if="next_steps.length > 0"
            class="flex items-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
          >
            <div>
              <h3 class="font-medium text-gray-900 dark:text-white">
                Next: {{ next_steps[0].name }}
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ next_steps[0].description }}
              </p>
            </div>
          </div>
        </div>
      </Card>
    </div>
  </TenantLayout>
</template>