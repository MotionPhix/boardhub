<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { Toaster } from 'vue-sonner'
import DarkModeToggle from '@/components/DarkModeToggle.vue'

interface Props {
  title?: string
}

const props = withDefaults(defineProps<Props>(), {
  title: 'System Admin'
})

const page = usePage()
const user = computed(() => page.props.auth?.user)

const navigation = [
  { name: 'Dashboard', href: 'system.dashboard', icon: 'home' },
  { name: 'Tenants', href: 'system.tenants.index', icon: 'building-office' },
  { name: 'Users', href: 'system.users.index', icon: 'users' },
  { name: 'Analytics', href: 'system.analytics.index', icon: 'chart-bar' },
  { name: 'Settings', href: 'system.settings.index', icon: 'cog' },
]

const logout = () => {
  router.post('/logout')
}
</script>

<template>
  <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
    <Head :title="props.title" />

    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <!-- Logo -->
            <div class="flex-shrink-0 flex items-center">
              <Link href="/system" class="text-xl font-bold text-gray-900 dark:text-white">
                AdPro System
              </Link>
            </div>

            <!-- Navigation Links -->
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <Link
                v-for="item in navigation"
                :key="item.name"
                :href="route(item.href)"
                :class="{
                  'border-indigo-500 text-gray-900 dark:text-white': $page.component.startsWith(item.href.replace('.', '/')),
                  'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300': !$page.component.startsWith(item.href.replace('.', '/'))
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
                  class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
                >
                  Logout
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main>
      <slot />
    </main>

    <!-- Toast Notifications -->
    <Toaster position="top-right" :theme="'system'" richColors />
  </div>
</template>