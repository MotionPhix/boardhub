<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { ref, computed, onMounted } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import TenantLayout from '../../layouts/TenantLayout.vue'
import { Building2, TrendingUp, Users, Calendar, AlertTriangle, DollarSign, BarChart3, Activity } from 'lucide-vue-next'

interface Props {
  tenant: {
    id: number
    name: string
    slug: string
    uuid: string
  }
  stats: {
    total_billboards: number
    available_billboards: number
    active_bookings: number
    total_revenue: number
    monthly_revenue: number
    utilization_rate: number
    team_members: number
  }
  billboardMetrics: {
    by_location: Array<{ location: string; count: number }>
    by_size: Array<{ size: string; count: number }>
    utilization_trend: Array<{ date: string; bookings: number }>
  }
  recentActivity: {
    recent_bookings: Array<{
      id: number
      billboard_name: string
      billboard_location: string
      client_name: string
      amount: number
      status: string
      start_date: string
      end_date: string
      created_at: string
    }>
    recent_inquiries: Array<{
      id: number
      billboard_name: string
      client_name: string
      amount: number
      created_at: string
    }>
  }
  revenueAnalytics: {
    monthly_revenue: Array<{ period: string; revenue: number }>
    revenue_by_size: Array<{ size: string; revenue: number }>
  }
  expiringContracts: {
    expiring_7_days: Array<{
      id: number
      billboard_name: string
      billboard_location: string
      client_name: string
      amount: number
      end_date: string
      days_remaining: number
    }>
    expiring_30_days_count: number
  }
  topPerformingBillboards: Array<{
    id: number
    name: string
    location: string
    size: string
    total_revenue: number
    total_bookings: number
    avg_booking_value: number
  }>
}

const props = defineProps<Props>()

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString()
}

const formatShortDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

// Chart configurations
const revenueChartOptions = computed(() => ({
  chart: {
    type: 'area',
    height: 350,
    toolbar: { show: false },
    background: 'transparent'
  },
  theme: {
    mode: 'light'
  },
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 2 },
  xaxis: {
    categories: props.revenueAnalytics.monthly_revenue.map(item => item.period),
    labels: {
      style: { colors: '#6B7280' }
    }
  },
  yaxis: {
    labels: {
      style: { colors: '#6B7280' },
      formatter: (value: number) => formatCurrency(value)
    }
  },
  colors: ['#3B82F6'],
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.3,
      opacityTo: 0.1
    }
  },
  grid: {
    borderColor: '#E5E7EB'
  },
  tooltip: {
    y: {
      formatter: (value: number) => formatCurrency(value)
    }
  }
}))

const revenueChartSeries = computed(() => [{
  name: 'Revenue',
  data: props.revenueAnalytics.monthly_revenue.map(item => item.revenue)
}])

const utilizationChartOptions = computed(() => ({
  chart: {
    type: 'line',
    height: 300,
    toolbar: { show: false },
    background: 'transparent'
  },
  theme: {
    mode: 'light'
  },
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 3 },
  xaxis: {
    categories: props.billboardMetrics.utilization_trend.map(item => formatShortDate(item.date)),
    labels: {
      style: { colors: '#6B7280' }
    }
  },
  yaxis: {
    labels: {
      style: { colors: '#6B7280' }
    }
  },
  colors: ['#10B981'],
  grid: {
    borderColor: '#E5E7EB'
  }
}))

const utilizationChartSeries = computed(() => [{
  name: 'Daily Bookings',
  data: props.billboardMetrics.utilization_trend.map(item => item.bookings)
}])

const locationDonutOptions = computed(() => ({
  chart: {
    type: 'donut',
    height: 300,
    background: 'transparent'
  },
  theme: {
    mode: 'light'
  },
  labels: props.billboardMetrics.by_location.map(item => item.location),
  colors: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'],
  legend: {
    position: 'bottom',
    labels: {
      colors: '#6B7280'
    }
  },
  dataLabels: {
    enabled: true
  },
  plotOptions: {
    pie: {
      donut: {
        size: '60%'
      }
    }
  }
}))

const locationDonutSeries = computed(() => props.billboardMetrics.by_location.map(item => item.count))
</script>

<template>
  <TenantLayout :tenant="tenant">
    <Head :title="`${tenant.name} - OOH Dashboard`" />

    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between">
          <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:text-3xl sm:truncate">
              {{ tenant.name }} Dashboard
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Out-of-Home advertising management & analytics
            </p>
          </div>
          <div class="mt-4 flex space-x-3 md:mt-0 md:ml-4">
            <Link
              href="#"
              class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <Building2 class="w-4 h-4 mr-2" />
              Add Billboard
            </Link>
            <Link
              href="#"
              class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <Users class="w-4 h-4 mr-2" />
              Manage Team
            </Link>
          </div>
        </div>

        <!-- Enhanced Stats Grid -->
        <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
          <!-- Total Billboards -->
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                    <Building2 class="w-6 h-6 text-white" />
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                      Total Billboards
                    </dt>
                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                      {{ stats.total_billboards }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <!-- Available Billboards -->
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                    <Activity class="w-6 h-6 text-white" />
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                      Available
                    </dt>
                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                      {{ stats.available_billboards }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <!-- Active Bookings -->
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-10 h-10 bg-yellow-500 rounded-lg flex items-center justify-center">
                    <Calendar class="w-6 h-6 text-white" />
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                      Active Bookings
                    </dt>
                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                      {{ stats.active_bookings }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <!-- Utilization Rate -->
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                    <BarChart3 class="w-6 h-6 text-white" />
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                      Utilization
                    </dt>
                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                      {{ stats.utilization_rate }}%
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <!-- Total Revenue -->
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-10 h-10 bg-indigo-500 rounded-lg flex items-center justify-center">
                    <DollarSign class="w-6 h-6 text-white" />
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                      Total Revenue
                    </dt>
                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                      {{ formatCurrency(stats.total_revenue) }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <!-- Monthly Revenue -->
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-10 h-10 bg-emerald-500 rounded-lg flex items-center justify-center">
                    <TrendingUp class="w-6 h-6 text-white" />
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                      This Month
                    </dt>
                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                      {{ formatCurrency(stats.monthly_revenue) }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts Row -->
        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Revenue Trend Chart -->
          <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Revenue Trend (12 Months)
              </h3>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Monthly revenue performance
              </p>
            </div>
            <div class="p-6">
              <VueApexCharts
                type="area"
                height="350"
                :options="revenueChartOptions"
                :series="revenueChartSeries"
              />
            </div>
          </div>

          <!-- Billboard Utilization Chart -->
          <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Daily Booking Activity (30 Days)
              </h3>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Billboard utilization trends
              </p>
            </div>
            <div class="p-6">
              <VueApexCharts
                type="line"
                height="300"
                :options="utilizationChartOptions"
                :series="utilizationChartSeries"
              />
            </div>
          </div>
        </div>

        <!-- Analytics and Activity Row -->
        <div class="mt-8 grid grid-cols-1 xl:grid-cols-3 gap-6">
          <!-- Billboard Distribution -->
          <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Billboards by Location
              </h3>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Geographic distribution
              </p>
            </div>
            <div class="p-6">
              <VueApexCharts
                type="donut"
                height="300"
                :options="locationDonutOptions"
                :series="locationDonutSeries"
              />
            </div>
          </div>

          <!-- Recent Activity -->
          <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Recent Bookings
              </h3>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Latest customer activity
              </p>
            </div>
            <div class="px-6 py-4">
              <div class="flow-root">
                <ul class="-my-4 divide-y divide-gray-200 dark:divide-gray-700">
                  <li v-for="booking in recentActivity.recent_bookings.slice(0, 5)" :key="booking.id" class="py-4">
                    <div class="flex items-center space-x-4">
                      <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                          {{ booking.billboard_name }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                          {{ booking.client_name }} • {{ formatCurrency(booking.amount) }}
                        </p>
                      </div>
                      <div class="flex-shrink-0">
                        <span :class="{
                          'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium': true,
                          'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100': booking.status === 'active',
                          'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100': booking.status === 'pending',
                          'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100': booking.status === 'completed'
                        }">
                          {{ booking.status }}
                        </span>
                      </div>
                    </div>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Expiring Contracts Alert -->
          <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
              <div class="flex items-center">
                <AlertTriangle class="w-5 h-5 text-red-500 mr-2" />
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                  Expiring Soon
                </h3>
              </div>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Contracts requiring attention
              </p>
            </div>
            <div class="px-6 py-4">
              <div class="mb-4">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-500 dark:text-gray-400">Next 7 days</span>
                  <span class="text-lg font-semibold text-red-600 dark:text-red-400">
                    {{ expiringContracts.expiring_7_days.length }}
                  </span>
                </div>
                <div class="mt-1 text-xs text-gray-400">
                  Next 30 days: {{ expiringContracts.expiring_30_days_count }}
                </div>
              </div>
              <div class="flow-root">
                <ul class="-my-3 divide-y divide-gray-200 dark:divide-gray-700">
                  <li v-for="contract in expiringContracts.expiring_7_days.slice(0, 4)" :key="contract.id" class="py-3">
                    <div class="flex items-center space-x-4">
                      <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                          {{ contract.billboard_name }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                          {{ contract.client_name }}
                        </p>
                      </div>
                      <div class="flex-shrink-0 text-right">
                        <p class="text-xs font-medium text-red-600 dark:text-red-400">
                          {{ contract.days_remaining }} days
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                          {{ formatCurrency(contract.amount) }}
                        </p>
                      </div>
                    </div>
                  </li>
                </ul>
              </div>
              <div v-if="expiringContracts.expiring_7_days.length === 0" class="text-center py-6">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  No contracts expiring soon
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Top Performing Billboards -->
        <div class="mt-8">
          <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Top Performing Billboards (6 Months)
              </h3>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Highest revenue generating billboards
              </p>
            </div>
            <div class="overflow-hidden">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Billboard
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Location
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Size
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Revenue
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Bookings
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Avg. Value
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                  <tr v-for="billboard in topPerformingBillboards.slice(0, 8)" :key="billboard.id">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-gray-900 dark:text-white">{{ billboard.name }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-500 dark:text-gray-400">{{ billboard.location }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                        {{ billboard.size }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-gray-900 dark:text-white">{{ formatCurrency(billboard.total_revenue) }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-500 dark:text-gray-400">{{ billboard.total_bookings }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-500 dark:text-gray-400">{{ formatCurrency(billboard.avg_booking_value) }}</div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </TenantLayout>
</template>