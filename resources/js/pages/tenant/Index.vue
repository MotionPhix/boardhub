<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import Button from '@/components/ui/Button.vue'
import Card from '@/components/ui/Card.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import DarkModeToggle from '@/components/DarkModeToggle.vue'
import VueApexCharts from 'vue3-apexcharts'
import { Building2, Plus, LogOut, CheckCircle, Link as LinkIcon, Users, Check, TrendingUp, Calendar, Activity } from 'lucide-vue-next'

interface Tenant {
  id: number
  name: string
  slug: string
  subdomain?: string
  settings?: Record<string, any>
}

interface RecentActivity {
  type: string
  description: string
  organization: string
  date: string
}

interface OrganizationAnalytics {
  total_organizations: number
  current_organization_id: number | null
  current_organization_name: string | null
  membership_roles: Record<string, number>
  subscription_statuses: Record<string, number>
  recent_activities: RecentActivity[]
  organizations_by_status: Record<string, number>
  trial_organizations: number
  active_organizations: number
}

interface Props {
  tenants?: Tenant[]
  currentTenant?: Tenant
  analytics?: OrganizationAnalytics
  breadcrumbs?: Array<{ name: string; href?: string }>
}

const props = withDefaults(defineProps<Props>(), {
  tenants: () => [],
  currentTenant: undefined,
  analytics: undefined,
  breadcrumbs: () => []
})

const form = useForm({
  tenant_id: null as number | null
})

const switchTenant = (tenantId: number) => {
  form.tenant_id = tenantId
  form.post(route('organizations.switch'))
}

// Chart configurations
const membershipRoleChart = computed(() => {
  if (!props.analytics?.membership_roles) return null

  const data = Object.entries(props.analytics.membership_roles)
  return {
    series: data.map(([, count]) => count),
    options: {
      chart: {
        type: 'donut',
        background: 'transparent'
      },
      labels: data.map(([role]) => role.charAt(0).toUpperCase() + role.slice(1)),
      colors: ['#6366f1', '#8b5cf6', '#06b6d4', '#10b981'],
      legend: {
        position: 'bottom'
      },
      responsive: [{
        breakpoint: 480,
        options: {
          chart: {
            width: 200
          },
          legend: {
            position: 'bottom'
          }
        }
      }]
    }
  }
})

const organizationStatusChart = computed(() => {
  if (!props.analytics?.organizations_by_status) return null

  const data = Object.entries(props.analytics.organizations_by_status)
  return {
    series: [{
      name: 'Organizations',
      data: data.map(([, count]) => count)
    }],
    options: {
      chart: {
        type: 'bar',
        background: 'transparent',
        toolbar: {
          show: false
        }
      },
      xaxis: {
        categories: data.map(([status]) => {
          // Capitalize and format status names
          return status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ')
        }),
        labels: {
          style: {
            fontSize: '12px',
            fontWeight: 500
          }
        }
      },
      yaxis: {
        title: {
          text: 'Number of Organizations',
          style: {
            fontSize: '12px',
            fontWeight: 500
          }
        },
        labels: {
          style: {
            fontSize: '11px'
          }
        }
      },
      colors: ['#6366f1'],
      plotOptions: {
        bar: {
          horizontal: false,
          columnWidth: '55%',
          borderRadius: 4
        },
      },
      dataLabels: {
        enabled: true,
        style: {
          fontSize: '12px',
          fontWeight: 'bold',
          colors: ['#fff']
        }
      },
      tooltip: {
        theme: 'dark',
        y: {
          formatter: function (val, { seriesIndex, dataPointIndex, w }) {
            const categories = w.globals.categoryLabels
            const statusName = categories[dataPointIndex]
            return `${val} on ${statusName}`
          }
        }
      },
      grid: {
        show: true,
        borderColor: '#374151',
        strokeDashArray: 3,
        xaxis: {
          lines: {
            show: false
          }
        },
        yaxis: {
          lines: {
            show: true
          }
        }
      }
    }
  }
})

// Analytics cards data
const analyticsCards = computed(() => {
  if (!props.analytics) return []

  return [
    {
      title: 'Total Organizations',
      value: props.analytics.total_organizations,
      icon: Building2,
      color: 'text-blue-600 dark:text-blue-400',
      bgColor: 'bg-blue-100 dark:bg-blue-900/20'
    },
    {
      title: 'Current Organization',
      value: props.analytics.current_organization_name || 'None Selected',
      icon: CheckCircle,
      color: 'text-green-600 dark:text-green-400',
      bgColor: 'bg-green-100 dark:bg-green-900/20'
    },
    {
      title: 'Trial Organizations',
      value: props.analytics.trial_organizations,
      icon: Calendar,
      color: 'text-purple-600 dark:text-purple-400',
      bgColor: 'bg-purple-100 dark:bg-purple-900/20'
    },
    {
      title: 'Recent Activities',
      value: props.analytics.recent_activities?.length || 0,
      icon: Activity,
      color: 'text-orange-600 dark:text-orange-400',
      bgColor: 'bg-orange-100 dark:bg-orange-900/20'
    }
  ]
})
</script>

<template>
  <Head title="Select Organization" />

  <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <!-- Theme Toggle -->
    <div class="absolute top-4 right-4">
      <DarkModeToggle />
    </div>

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

      <!-- Current Organization Highlight -->
      <div v-if="currentTenant && tenants.length > 0" class="mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-indigo-200 dark:border-indigo-800 p-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div class="h-12 w-12 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
                <Building2 class="h-6 w-6 text-white" />
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                  {{ currentTenant.name }}
                </h3>
                <p class="text-sm text-indigo-600 dark:text-indigo-400">Currently Active Organization</p>
              </div>
            </div>
            <div class="flex items-center text-green-600 dark:text-green-400">
              <CheckCircle class="h-5 w-5 mr-2" />
              <span class="text-sm font-medium">Active</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Organizations Grid -->
      <div v-if="tenants.length > 0">
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

      <!-- Analytics Dashboard -->
      <div v-if="analytics" class="mt-12">
        <div class="flex items-center justify-between mb-8">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
            Organization Analytics
          </h2>
          <div class="text-sm text-gray-500 dark:text-gray-400">
            Real-time data
          </div>
        </div>

        <!-- Analytics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          <div
            v-for="card in analyticsCards"
            :key="card.title"
            class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-all duration-200 hover:-translate-y-1"
          >
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">
                  {{ card.title }}
                </p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                  {{ card.value }}
                </p>
              </div>
              <div :class="[card.bgColor, 'p-3 rounded-lg']">
                <component :is="card.icon" :class="[card.color, 'w-6 h-6']" />
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Activities Section -->
        <div v-if="analytics.recent_activities && analytics.recent_activities.length > 0" class="mb-8">
          <Card class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
              <Activity class="w-5 h-5 mr-2 text-orange-600 dark:text-orange-400" />
              Recent Activities
            </h3>
            <div class="space-y-4">
              <div
                v-for="(activity, index) in analytics.recent_activities"
                :key="index"
                class="flex items-start space-x-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
              >
                <div class="flex-shrink-0 mt-1">
                  <div v-if="activity.type === 'organization_created'" class="w-2 h-2 bg-green-500 rounded-full"></div>
                  <div v-else-if="activity.type === 'membership_updated'" class="w-2 h-2 bg-blue-500 rounded-full"></div>
                  <div v-else class="w-2 h-2 bg-gray-500 rounded-full"></div>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ activity.description }}
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ activity.date }}
                  </p>
                </div>
                <div class="flex-shrink-0">
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200">
                    {{ activity.organization }}
                  </span>
                </div>
              </div>
            </div>
          </Card>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- Membership Roles Chart -->
          <Card v-if="membershipRoleChart" class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
              Your Role Distribution
            </h3>
            <div class="h-64">
              <VueApexCharts
                type="donut"
                height="100%"
                :options="membershipRoleChart.options"
                :series="membershipRoleChart.series"
              />
            </div>
          </Card>

          <!-- Organization Status Chart -->
          <Card v-if="organizationStatusChart" class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
              Organizations by Status
            </h3>
            <div class="h-64">
              <VueApexCharts
                type="bar"
                height="100%"
                :options="organizationStatusChart.options"
                :series="organizationStatusChart.series"
              />
            </div>
          </Card>
        </div>

        <!-- Subscription Status Breakdown -->
        <div v-if="analytics.subscription_statuses && Object.keys(analytics.subscription_statuses).length > 0" class="mt-8">
          <Card class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
              Subscription Overview
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div
                v-for="(count, status) in analytics.subscription_statuses"
                :key="status"
                class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center"
              >
                <div class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                  {{ count }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400 capitalize">
                  {{ status.replace('_', ' ') }}
                </div>
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
