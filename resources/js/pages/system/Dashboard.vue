<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import SystemLayout from '../../layouts/SystemLayout.vue'
import LineChart from '../../components/charts/LineChart.vue'
import DonutChart from '../../components/charts/DonutChart.vue'
import AreaChart from '../../components/charts/AreaChart.vue'

interface Props {
  stats: {
    total_tenants: number
    active_tenants: number
    total_users: number
    super_admins: number
  }
  recent_tenants: Array<{
    id: number
    name: string
    slug: string
    is_active: boolean
    created_at: string
  }>
  recent_users: Array<{
    id: number
    name: string
    email: string
    tenant_id: number | null
    created_at: string
  }>
  chartData: {
    tenant_growth: {
      categories: string[]
      series: Array<{
        name: string
        data: number[]
      }>
    }
    user_distribution: {
      series: number[]
      labels: string[]
    }
    activity_overview: {
      categories: string[]
      series: Array<{
        name: string
        data: number[]
      }>
    }
  }
}

const props = defineProps<Props>()
</script>

<template>
  <SystemLayout>
    <Head title="System Dashboard" />

    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between">
          <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:text-3xl sm:truncate">
              System Dashboard
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              System-wide overview and management
            </p>
          </div>
          <div class="mt-4 flex md:mt-0 md:ml-4">
            <Link
              :href="route('system.tenants.index')"
              class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Manage Tenants
            </Link>
          </div>
        </div>

        <!-- Stats Grid -->
        <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-indigo-500 rounded-md flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm2 6a2 2 0 114 0 2 2 0 01-4 0zm8-2a2 2 0 100 4 2 2 0 000-4z" clip-rule="evenodd" />
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                      Total Tenants
                    </dt>
                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                      {{ stats.total_tenants }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                      Active Tenants
                    </dt>
                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                      {{ stats.active_tenants }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                      Total Users
                    </dt>
                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                      {{ stats.total_users }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1 1H6v2H2v-4l4.257-4.257A6 6 0 1118 8zm-6-4a1 1 0 100 2 2 2 0 012 2 1 1 0 102 0 4 4 0 00-4-4z" clip-rule="evenodd" />
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                      Super Admins
                    </dt>
                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                      {{ stats.super_admins }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts Section -->
        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Tenant Growth Chart -->
          <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
              Tenant Growth (Last 7 Months)
            </h3>
            <LineChart
              :series="chartData.tenant_growth.series"
              :categories="chartData.tenant_growth.categories"
              :height="300"
            />
          </div>

          <!-- User Distribution Chart -->
          <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
              User Distribution
            </h3>
            <DonutChart
              :series="chartData.user_distribution.series"
              :labels="chartData.user_distribution.labels"
              :height="300"
            />
          </div>
        </div>

        <!-- Activity Overview Chart -->
        <div class="mt-6">
          <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
              Recent Activity (Last 7 Days)
            </h3>
            <AreaChart
              :series="chartData.activity_overview.series"
              :categories="chartData.activity_overview.categories"
              :height="350"
            />
          </div>
        </div>

        <!-- Recent Activity Grid -->
        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Recent Tenants -->
          <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
              <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Recent Tenants
              </h3>
              <div class="mt-5">
                <div class="flow-root">
                  <ul class="-my-4 divide-y divide-gray-200 dark:divide-gray-700">
                    <li v-for="tenant in recent_tenants" :key="tenant.id" class="py-4">
                      <div class="flex items-center space-x-4">
                        <div class="flex-1 min-w-0">
                          <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ tenant.name }}
                          </p>
                          <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                            {{ tenant.slug }}
                          </p>
                        </div>
                        <div class="flex-shrink-0">
                          <span :class="{
                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium': true,
                            'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100': tenant.is_active,
                            'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100': !tenant.is_active
                          }">
                            {{ tenant.is_active ? 'Active' : 'Inactive' }}
                          </span>
                        </div>
                      </div>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <!-- Recent Users -->
          <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
              <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Recent Users
              </h3>
              <div class="mt-5">
                <div class="flow-root">
                  <ul class="-my-4 divide-y divide-gray-200 dark:divide-gray-700">
                    <li v-for="user in recent_users" :key="user.id" class="py-4">
                      <div class="flex items-center space-x-4">
                        <div class="flex-1 min-w-0">
                          <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ user.name }}
                          </p>
                          <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                            {{ user.email }}
                          </p>
                        </div>
                        <div class="flex-shrink-0">
                          <span :class="{
                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium': true,
                            'bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100': !user.tenant_id,
                            'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100': user.tenant_id
                          }">
                            {{ user.tenant_id ? 'Tenant User' : 'System Admin' }}
                          </span>
                        </div>
                      </div>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </SystemLayout>
</template>