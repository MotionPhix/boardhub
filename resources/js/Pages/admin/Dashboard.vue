<template>
  <AdminLayout>
    <!-- Dashboard Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="min-w-0 flex-1">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
          Administrative Dashboard
        </h2>
        <div class="mt-1 flex flex-col sm:mt-0 sm:flex-row sm:flex-wrap sm:space-x-6">
          <div class="mt-2 flex items-center text-sm text-gray-500">
            <ClockIcon class="mr-1.5 h-5 w-5 flex-shrink-0 text-gray-400" />
            Last updated {{ lastUpdated }}
          </div>
          <div class="mt-2 flex items-center text-sm text-gray-500">
            <ShieldCheckIcon class="mr-1.5 h-5 w-5 flex-shrink-0 text-green-400" />
            System Secure
          </div>
        </div>
      </div>
      <div class="mt-4 flex md:ml-4 md:mt-0">
        <button
          type="button"
          class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
          @click="refreshData"
        >
          <RotateCcw class="mr-2 h-4 w-4" />
          Refresh
        </button>
      </div>
    </div>

    <!-- Key Metrics -->
    <div class="mt-8">
      <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div v-for="stat in stats" :key="stat.name" class="relative overflow-hidden rounded-lg bg-white px-4 pb-12 pt-5 shadow sm:px-6 sm:pt-6">
          <dt>
            <div class="absolute rounded-md p-3" :class="stat.iconBackground">
              <component :is="stat.icon" class="h-6 w-6 text-white" />
            </div>
            <p class="ml-16 truncate text-sm font-medium text-gray-500">{{ stat.name }}</p>
          </dt>
          <dd class="ml-16 flex items-baseline pb-6 sm:pb-7">
            <p class="text-2xl font-semibold text-gray-900">{{ stat.value }}</p>
            <p :class="[
              stat.changeType === 'increase' ? 'text-green-600' : 'text-red-600',
              'ml-2 flex items-baseline text-sm font-semibold'
            ]">
              <component :is="stat.changeType === 'increase' ? ArrowUp : ArrowDown" class="h-5 w-5 flex-shrink-0 self-center" />
              <span class="sr-only">{{ stat.changeType === 'increase' ? 'Increased' : 'Decreased' }} by</span>
              {{ stat.change }}
            </p>
          </dd>
          <div class="absolute inset-x-0 bottom-0 bg-gray-50 px-4 py-4 sm:px-6">
            <div class="text-sm">
              <a :href="stat.href" class="font-medium text-indigo-600 hover:text-indigo-500">
                View all<span class="sr-only"> {{ stat.name }} stats</span>
              </a>
            </div>
          </div>
        </div>
      </dl>
    </div>

    <!-- Recent Activity & Charts -->
    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
      <!-- Recent Activity -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg font-medium leading-6 text-gray-900">Recent Activity</h3>
          <div class="mt-5">
            <div class="flow-root">
              <ul class="-mb-8">
                <li v-for="(activity, activityIdx) in recentActivity" :key="activity.id">
                  <div class="relative pb-8">
                    <span v-if="activityIdx !== recentActivity.length - 1" class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200"></span>
                    <div class="relative flex space-x-3">
                      <div>
                        <span :class="[activity.iconBackground, 'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white']">
                          <component :is="activity.icon" class="h-5 w-5 text-white" />
                        </span>
                      </div>
                      <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                        <div>
                          <p class="text-sm text-gray-500">{{ activity.content }}</p>
                        </div>
                        <div class="whitespace-nowrap text-right text-sm text-gray-500">
                          <time :datetime="activity.datetime">{{ activity.date }}</time>
                        </div>
                      </div>
                    </div>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- System Health -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg font-medium leading-6 text-gray-900">System Health</h3>
          <div class="mt-5">
            <div class="space-y-4">
              <div v-for="metric in systemMetrics" :key="metric.name" class="flex items-center justify-between">
                <div class="flex items-center">
                  <div :class="[metric.status === 'healthy' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800', 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium']">
                    {{ metric.status }}
                  </div>
                  <span class="ml-3 text-sm font-medium text-gray-900">{{ metric.name }}</span>
                </div>
                <span class="text-sm text-gray-500">{{ metric.value }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-8">
      <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg font-medium leading-6 text-gray-900">Quick Actions</h3>
          <div class="mt-5">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
              <Link
                v-for="action in quickActions"
                :key="action.name"
                :href="action.href"
                class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                <div class="flex-shrink-0">
                  <component :is="action.icon" class="h-6 w-6 text-gray-400" />
                </div>
                <div class="flex-1 min-w-0">
                  <span class="absolute inset-0" aria-hidden="true" />
                  <p class="text-sm font-medium text-gray-900">{{ action.name }}</p>
                  <p class="text-sm text-gray-500 truncate">{{ action.description }}</p>
                </div>
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import {
  ArrowDown,
  ArrowUp,
  RotateCcw,
  Clock,
  Shield,
  Users,
  Building,
  ClipboardList,
  DollarSign,
  Plus,
  Settings,
  FileText,
  UserPlus
} from 'lucide-vue-next'

const props = defineProps({
  stats: Object,
  recentActivity: Array,
  systemMetrics: Array
})

const lastUpdated = ref(new Date().toLocaleString())

const stats = computed(() => [
  {
    name: 'Total Users',
    value: props.stats?.total_users || '0',
    change: '+4.75%',
    changeType: 'increase',
    href: '/admin/users',
    icon: Users,
    iconBackground: 'bg-indigo-500'
  },
  {
    name: 'Active Tenants',
    value: props.stats?.active_tenants || '0',
    change: '+2.02%',
    changeType: 'increase',
    href: '/admin/tenants',
    icon: Building,
    iconBackground: 'bg-green-500'
  },
  {
    name: 'Total Billboards',
    value: props.stats?.total_billboards || '0',
    change: '+1.39%',
    changeType: 'increase',
    href: '/admin/billboards',
    icon: ClipboardList,
    iconBackground: 'bg-yellow-500'
  },
  {
    name: 'Monthly Revenue',
    value: props.stats?.monthly_revenue || 'MWK 0',
    change: '+8.1%',
    changeType: 'increase',
    href: '/admin/payments',
    icon: DollarSign,
    iconBackground: 'bg-purple-500'
  }
])

const recentActivity = computed(() => props.recentActivity || [
  {
    id: 1,
    content: 'New user registration',
    datetime: '2024-01-15T14:30:00',
    date: '30m ago',
    icon: UserPlus,
    iconBackground: 'bg-green-500'
  },
  {
    id: 2,
    content: 'Billboard listing approved',
    datetime: '2024-01-15T13:45:00',
    date: '1h ago',
    icon: ClipboardList,
    iconBackground: 'bg-blue-500'
  },
  {
    id: 3,
    content: 'Payment processed',
    datetime: '2024-01-15T12:30:00',
    date: '2h ago',
    icon: DollarSign,
    iconBackground: 'bg-purple-500'
  }
])

const systemMetrics = computed(() => props.systemMetrics || [
  { name: 'Database', status: 'healthy', value: '99.9% uptime' },
  { name: 'API Response', status: 'healthy', value: '<200ms avg' },
  { name: 'Cache Hit Rate', status: 'healthy', value: '94.2%' },
  { name: 'Storage Usage', status: 'healthy', value: '67% used' }
])

const quickActions = [
  {
    name: 'Add User',
    description: 'Create new user account',
    href: '/admin/users/create',
    icon: UserPlus
  },
  {
    name: 'Create Tenant',
    description: 'Setup new tenant',
    href: '/admin/tenants/create',
    icon: Building
  },
  {
    name: 'System Settings',
    description: 'Configure platform',
    href: '/admin/settings',
    icon: Settings
  },
  {
    name: 'Security Report',
    description: 'View security logs',
    href: '/admin/security/reports',
    icon: FileText
  }
]

const refreshData = () => {
  // Trigger data refresh
  window.location.reload()
}

onMounted(() => {
  // Update timestamp every minute
  setInterval(() => {
    lastUpdated.value = new Date().toLocaleString()
  }, 60000)
})
</script>