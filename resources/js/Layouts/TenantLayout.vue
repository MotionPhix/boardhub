<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { Toaster } from 'vue-sonner'
import DarkModeToggle from '@/components/DarkModeToggle.vue'

interface Tenant {
  id: number
  name: string
  uuid: string
  slug: string
}

interface Props {
  tenant: Tenant
  title?: string
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Tenant Dashboard'
})

const page = usePage()
const user = computed(() => page.props.auth?.user)

const navigation = [
  { name: 'Dashboard', href: 'tenant.dashboard', icon: 'home' },
  { name: 'Billboards', href: 'tenant.manage.billboards.index', icon: 'building-office' },
  { name: 'Bookings', href: 'tenant.manage.bookings.index', icon: 'calendar' },
  { name: 'Team', href: 'tenant.manage.team.index', icon: 'users' },
  { name: 'Analytics', href: 'tenant.manage.analytics.index', icon: 'chart-bar' },
  { name: 'Settings', href: 'tenant.manage.settings.index', icon: 'cog' },
]

const logout = () => {
  router.post('/logout')
}

const switchTenant = () => {
  router.get('/organizations')
}
</script>

<template>
  <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
    <Head :title="`${props.title} - ${tenant.name}`" />

    <!-- Navigation -->
    <nav class="fixed inset-x-0 z-40 bg-white dark:bg-gray-800 shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <!-- Logo / Tenant Name -->
            <div class="flex-shrink-0 flex items-center">
              <button
                @click="switchTenant"
                class="text-xl font-bold text-gray-900 dark:text-white">
                {{ tenant.name }}
              </button>
              <span class="ml-2 px-2 py-1 text-xs bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200 rounded-full">
                Admin
              </span>
            </div>

            <!-- Navigation Links -->
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <Link
                v-for="item in navigation"
                :key="item.name"
                :href="route(item.href, { tenant: tenant.uuid })"
                :class="{
                  'border-indigo-500 text-gray-900 dark:text-white': $page.component.startsWith('tenant/' + item.href.split('.')[1]),
                  'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300': !$page.component.startsWith('tenant/' + item.href.split('.')[1])
                }"
                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors"
              >
                {{ item.name }}
              </Link>
            </div>
          </div>

          <!-- Right side -->
          <div class="flex items-center space-x-4">
            <DarkModeToggle />

            <!-- User Menu -->
            <div class="relative">
              <div class="flex items-center space-x-3">
                <span class="text-sm text-gray-700 dark:text-gray-300">
                  {{ user?.name }}
                </span>

                <button
                  @click="logout"
                  class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                  Logout
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="fixed inset-0 overflow-y-auto top-16 scroll-smooth scrollbar-thin">
      <slot />
    </main>

    <!-- Toast Notifications -->
    <Toaster position="top-right" :theme="'system'" richColors />
  </div>
</template>
