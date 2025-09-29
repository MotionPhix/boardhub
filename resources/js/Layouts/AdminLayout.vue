<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-900" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" id="sidebar">
      <div class="flex items-center justify-center h-16 px-4 bg-gray-800">
        <span class="text-xl font-bold text-white">AdPro Admin</span>
      </div>

      <!-- Navigation -->
      <nav class="mt-5 px-2 space-y-1">
        <Link
          v-for="item in navigation"
          :key="item.name"
          :href="item.href"
          :class="[
            item.current
              ? 'bg-gray-800 text-white'
              : 'text-gray-300 hover:bg-gray-700 hover:text-white',
            'group flex items-center px-2 py-2 text-sm font-medium rounded-md'
          ]"
        >
          <component :is="item.icon" class="mr-3 h-5 w-5 flex-shrink-0" />
          {{ item.name }}
        </Link>
      </nav>

      <!-- User Section -->
      <div class="absolute bottom-0 w-full p-4 bg-gray-800">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center">
              <span class="text-sm font-medium text-white">{{ userInitials }}</span>
            </div>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-white">{{ $page.props.auth.user.name }}</p>
            <p class="text-xs text-gray-300">{{ $page.props.auth.user.email }}</p>
          </div>
        </div>
        <div class="mt-3 flex space-x-2">
          <Link href="/admin/profile" class="text-xs text-gray-300 hover:text-white">Profile</Link>
          <Link href="/logout" method="post" class="text-xs text-gray-300 hover:text-white">Logout</Link>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="lg:pl-64">
      <!-- Top Navigation -->
      <div class="sticky top-0 z-40 flex h-16 flex-shrink-0 bg-white shadow">
        <button
          type="button"
          class="border-r border-gray-200 px-4 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 lg:hidden"
          @click="sidebarOpen = !sidebarOpen"
        >
          <span class="sr-only">Open sidebar</span>
          <Menu class="h-6 w-6" />
        </button>

        <div class="flex flex-1 justify-between px-4">
          <div class="flex flex-1">
            <!-- Breadcrumbs -->
            <nav class="flex items-center" aria-label="Breadcrumb">
              <ol class="flex items-center space-x-4">
                <li>
                  <div class="flex items-center">
                    <Link href="/admin" class="text-gray-400 hover:text-gray-500">
                      <Home class="h-5 w-5 flex-shrink-0" />
                      <span class="sr-only">Home</span>
                    </Link>
                  </div>
                </li>
                <li v-for="page in breadcrumbs" :key="page.name">
                  <div class="flex items-center">
                    <ChevronRight class="h-5 w-5 flex-shrink-0 text-gray-400" />
                    <Link
                      :href="page.href"
                      class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700"
                    >
                      {{ page.name }}
                    </Link>
                  </div>
                </li>
              </ol>
            </nav>
          </div>

          <div class="ml-4 flex items-center space-x-4">
            <!-- Security Status -->
            <div class="flex items-center text-sm">
              <div class="h-2 w-2 bg-green-400 rounded-full mr-2"></div>
              <span class="text-gray-600">Secure Session</span>
            </div>

            <!-- Notifications -->
            <button
              type="button"
              class="relative rounded-full bg-white p-1 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            >
              <span class="sr-only">View notifications</span>
              <Bell class="h-6 w-6" />
              <span v-if="notificationCount > 0" class="absolute -top-1 -right-1 h-4 w-4 rounded-full bg-red-500 text-xs text-white flex items-center justify-center">
                {{ notificationCount }}
              </span>
            </button>
          </div>
        </div>
      </div>

      <!-- Page Content -->
      <main class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <!-- Flash Messages -->
          <div v-if="$page.props.flash.success" class="mb-4 rounded-md bg-green-50 p-4">
            <div class="flex">
              <CheckCircle class="h-5 w-5 text-green-400" />
              <div class="ml-3">
                <p class="text-sm font-med.error" class="mb-4 rounded-md bg-red-50 p-4">
                  <div class="flex">
                    <XCircle class="h-5 w-ium text-green-800">{{ $page.props.flash.success }}</p>
              </div>
            </div>
          </div>

          <div v-if="$page.props.flash5 text-red-400" />
              <div class="ml-3">
                <p class="text-sm font-medium text-red-800">{{ $page.props.flash.error }}</p>
              </div>
            </div>
          </div>

          <slot />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
  Menu,
  Bell,
  ChevronRight,
  Home,
  CheckCircle,
  XCircle,
  BarChart,
  Users,
  Building,
  ClipboardList,
  Settings,
  Shield,
  DollarSign,
  FileText
} from 'lucide-vue-next'

const page = usePage()
const sidebarOpen = ref(false)

const userInitials = computed(() => {
  const name = page.props.auth?.user?.name || ''
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
})

const notificationCount = computed(() => page.props.notifications?.unread_count || 0)

const navigation = [
  { name: 'Dashboard', href: '/admin', icon: BarChart, current: page.url === '/admin' },
  { name: 'Users', href: '/admin/users', icon: Users, current: page.url.startsWith('/admin/users') },
  { name: 'Tenants', href: '/admin/tenants', icon: Building, current: page.url.startsWith('/admin/tenants') },
  { name: 'Billboards', href: '/admin/billboards', icon: ClipboardList, current: page.url.startsWith('/admin/billboards') },
  { name: 'Bookings', href: '/admin/bookings', icon: FileText, current: page.url.startsWith('/admin/bookings') },
  { name: 'Payments', href: '/admin/payments', icon: DollarSign, current: page.url.startsWith('/admin/payments') },
  { name: 'Security', href: '/admin/security', icon: Shield, current: page.url.startsWith('/admin/security') },
  { name: 'Settings', href: '/admin/settings', icon: Settings, current: page.url.startsWith('/admin/settings') },
]

const breadcrumbs = computed(() => {
  const segments = page.url.split('/').filter(Boolean)
  return segments.slice(1).map((segment, index) => ({
    name: segment.charAt(0).toUpperCase() + segment.slice(1),
    href: '/' + segments.slice(0, index + 2).join('/')
  }))
})
</script>
