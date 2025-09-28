<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import Button from '../../../components/ui/Button.vue'
import Card from '../../../components/ui/Card.vue'
import EmptyState from '../../../components/ui/EmptyState.vue'
import { Building2, Plus, LogOut, CheckCircle, Link as LinkIcon, Users, Check } from 'lucide-vue-next'

interface Tenant {
  id: number
  name: string
  slug: string
  subdomain?: string
  settings?: Record<string, any>
}

interface Props {
  tenants?: Tenant[]
  currentTenant?: Tenant
}

const props = withDefaults(defineProps<Props>(), {
  tenants: () => [],
  currentTenant: undefined
})

const form = useForm({
  tenant_id: null as number | null
})

const switchTenant = (tenantId: number) => {
  form.tenant_id = tenantId
  form.post(route('tenants.switch'))
}
</script>

<template>
  <Head title="Select Organization" />

  <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
      <!-- Header Section -->
      <div class="text-center mb-12">
        <div class="mx-auto w-16 h-16 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center mb-6">
          <Building2 class="w-8 h-8 text-white" />
        </div>
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
          Welcome to AdPro
        </h1>
        <p class="text-xl text-gray-600 dark:text-gray-300 max-w-2xl mx-auto mb-8">
          Choose the organization you want to work with, or create a new one to get started with your advertising campaigns.
        </p>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
          <Link
            :href="route('organizations.create')"
            :as="Button"
            variant="primary"
            size="lg">
            <Plus class="w-5 h-5 mr-2" />
            New Organization
          </Link>

          <Link
            :href="route('logout')"
            method="post"
            :as="Button"
            variant="outline"
            size="lg">
            <LogOut class="w-4 h-4 mr-2" />
            Logout
          </Link>
        </div>
      </div>

      <!-- Empty State with Better Design -->
      <div v-if="tenants.length === 0" class="text-center py-16">
        <Card class="max-w-2xl mx-auto p-12 border-2 border-dashed border-indigo-200 dark:border-indigo-800 bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20">
          <div class="mx-auto w-20 h-20 bg-gradient-to-r from-indigo-100 to-purple-100 dark:from-indigo-800 dark:to-purple-800 rounded-full flex items-center justify-center mb-6">
            <Building2 class="w-10 h-10 text-indigo-600 dark:text-indigo-400" />
          </div>

          <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
            Ready to Get Started?
          </h3>
          <p class="text-lg text-gray-600 dark:text-gray-300 mb-8 leading-relaxed">
            You don't have access to any organizations yet. Create your first organization to start managing your advertising campaigns and unlock powerful features.
          </p>

          <div class="space-y-4">
            <Link
              :href="route('organizations.create')"
              :as="Button"
              variant="primary"
              size="lg">
              <Plus class="w-5 h-5 mr-2" />
              Create Your First Organization
            </Link>

            <div class="flex items-center justify-center space-x-6 mt-8 text-sm text-gray-500 dark:text-gray-400">
              <div class="flex items-center">
                <Check class="w-4 h-4 mr-1 text-green-500" />
                14-day free trial
              </div>
              <div class="flex items-center">
                <Check class="w-4 h-4 mr-1 text-green-500" />
                No credit card required
              </div>
              <div class="flex items-center">
                <Check class="w-4 h-4 mr-1 text-green-500" />
                Cancel anytime
              </div>
            </div>
          </div>
        </Card>
      </div>

      <!-- Organizations Grid -->
      <div v-else>
        <div class="flex items-center justify-between mb-8">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
            Your Organizations
          </h2>
          <span class="text-sm text-gray-500 dark:text-gray-400">
            {{ tenants.length }} organization{{ tenants.length === 1 ? '' : 's' }}
          </span>
        </div>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          <Card
            v-for="tenant in tenants"
            :key="tenant.id"
            class="group hover:shadow-xl hover:-translate-y-1 transition-all duration-300 cursor-pointer border-2"
            :class="{
              'border-indigo-500 dark:border-indigo-400 bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20': currentTenant?.id === tenant.id,
              'border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-600': currentTenant?.id !== tenant.id
            }"
          >
            <div class="relative">
              <!-- Organization Icon/Avatar -->
              <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3">
                  <div class="w-12 h-12 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 flex items-center justify-center">
                    <span class="text-lg font-bold text-white">
                      {{ tenant.name.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                  <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                      {{ tenant.name }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                      {{ tenant.slug }}
                    </p>
                  </div>
                </div>

                <span
                  v-if="currentTenant?.id === tenant.id"
                  class="px-3 py-1 text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full flex items-center"
                >
                  <div class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></div>
                  Active
                </span>
              </div>

              <!-- Organization Details -->
              <div class="space-y-2 mb-6">
                <div v-if="tenant.subdomain" class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                  <LinkIcon class="w-4 h-4 mr-2" />
                  {{ tenant.subdomain }}.adpro.test
                </div>

                <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                  <Users class="w-4 h-4 mr-2" />
                  Team members
                </div>
              </div>

              <!-- Action Button -->
              <Button
                v-if="currentTenant?.id !== tenant.id"
                @click="switchTenant(tenant.id)"
                :disabled="form.processing"
                variant="primary"
                class="w-full group-hover:shadow-lg transition-shadow duration-200"
              >
                <span v-if="form.processing && form.tenant_id === tenant.id" class="flex items-center justify-center">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Switching...
                </span>
                <span v-else class="flex items-center justify-center">
                  <LogOut class="w-4 h-4 mr-2" />
                  Switch to Organization
                </span>
              </Button>

              <div
                v-else
                class="w-full px-4 py-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-center"
              >
                <span class="text-sm font-medium text-green-700 dark:text-green-300 flex items-center justify-center">
                  <CheckCircle class="w-4 h-4 mr-2" />
                  Currently Active
                </span>
              </div>
            </div>
          </Card>
        </div>
      </div>

      <!-- Footer -->
      <div class="mt-16 pt-8 border-t border-gray-200 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row items-center justify-between">
          <p class="text-gray-500 dark:text-gray-400 text-sm mb-4 sm:mb-0">
            Need help? Contact our support team for assistance with your organizations.
          </p>
          <div class="flex items-center space-x-6 text-sm">
            <a href="#" class="text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 transition-colors">
              Documentation
            </a>
            <a href="#" class="text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 transition-colors">
              Support
            </a>
            <a href="#" class="text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 transition-colors">
              Status
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
