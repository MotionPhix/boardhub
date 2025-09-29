<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
    <!-- Sticky Navigation Header -->
    <nav class="sticky top-0 z-50 bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <!-- Logo and Brand -->
          <div class="flex items-center">
            <Link :href="route('home')" class="flex-shrink-0 flex items-center">
              <div class="h-8 w-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
                <span class="text-white font-bold text-sm">AP</span>
              </div>
              <span class="ml-3 text-xl font-bold text-gray-900 dark:text-white">AdPro</span>
            </Link>

            <!-- Primary Navigation (Desktop) -->
            <div class="hidden md:ml-10 md:flex md:space-x-8">
              <template v-if="$page.props.auth.user">
                <Link
                  :href="route('tenants.select')"
                  class="text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 px-3 py-2 text-sm font-medium transition-colors"
                  :class="{ 'text-indigo-600 dark:text-indigo-400': $page.url.startsWith('/tenants') }"
                >
                  Organizations
                </Link>
                <Link
                  :href="route('system.dashboard')"
                  v-if="$page.props.auth.user.is_super_admin"
                  class="text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 px-3 py-2 text-sm font-medium transition-colors"
                  :class="{ 'text-indigo-600 dark:text-indigo-400': $page.url.startsWith('/system') }"
                >
                  System Admin
                </Link>
              </template>
              <template v-else>
                <a href="#features" class="text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 px-3 py-2 text-sm font-medium transition-colors">
                  Features
                </a>
                <a href="#pricing" class="text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 px-3 py-2 text-sm font-medium transition-colors">
                  Pricing
                </a>
              </template>
            </div>
          </div>

          <!-- Right side actions -->
          <div class="flex items-center space-x-4">
            <!-- Theme Toggle -->
            <DarkModeToggle />

            <!-- User Menu -->
            <template v-if="$page.props.auth.user">
              <!-- User Avatar and Dropdown -->
              <div class="relative">
                <button
                  @click="showUserMenu = !showUserMenu"
                  class="flex items-center space-x-3 text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400"
                >
                  <div class="h-8 w-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center">
                    <span class="text-white font-medium text-sm">
                      {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                  <span class="hidden md:block text-gray-700 dark:text-gray-300">
                    {{ $page.props.auth.user.name }}
                  </span>
                  <ChevronDown class="h-4 w-4 text-gray-400" />
                </button>

                <!-- Dropdown Menu -->
                <div
                  v-show="showUserMenu"
                  @click.away="showUserMenu = false"
                  class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5 dark:ring-gray-700"
                >
                  <div class="px-4 py-2 text-xs text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-gray-700">
                    {{ $page.props.auth.user.email }}
                  </div>
                  <Link
                    :href="route('security.index')"
                    class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                  >
                    Security Settings
                  </Link>
                  <Link
                    :href="route('logout')"
                    method="post"
                    class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                  >
                    Sign Out
                  </Link>
                </div>
              </div>
            </template>

            <!-- Guest Actions -->
            <template v-else>
              <Link
                :href="route('login')"
                class="text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 px-3 py-2 text-sm font-medium transition-colors"
              >
                Sign In
              </Link>
              <Link
                :href="route('register')"
                class="bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm"
              >
                Get Started
              </Link>
            </template>

            <!-- Mobile Menu Button -->
            <button
              @click="showMobileMenu = !showMobileMenu"
              class="md:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
              <Menu v-if="!showMobileMenu" class="h-6 w-6" />
              <X v-else class="h-6 w-6" />
            </button>
          </div>
        </div>

        <!-- Mobile Menu -->
        <div v-show="showMobileMenu" class="md:hidden">
          <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 border-t border-gray-200 dark:border-gray-700">
            <template v-if="$page.props.auth.user">
              <Link
                :href="route('tenants.select')"
                class="block px-3 py-2 text-base font-medium text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md"
              >
                Organizations
              </Link>
              <Link
                :href="route('system.dashboard')"
                v-if="$page.props.auth.user.is_super_admin"
                class="block px-3 py-2 text-base font-medium text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md"
              >
                System Admin
              </Link>
            </template>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1">
      <slot />
    </main>

    <!-- Clean Footer -->
    <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-auto">
      <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row justify-between items-center">
          <div class="flex items-center space-x-2">
            <div class="h-6 w-6 bg-gradient-to-br from-indigo-500 to-purple-600 rounded flex items-center justify-center">
              <span class="text-white font-bold text-xs">AP</span>
            </div>
            <span class="text-sm text-gray-600 dark:text-gray-400">
              © {{ new Date().getFullYear() }} AdPro. Multi-tenant SaaS platform.
            </span>
          </div>

          <div class="flex items-center space-x-6 mt-4 md:mt-0">
            <a href="#" class="text-sm text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
              Privacy Policy
            </a>
            <a href="#" class="text-sm text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
              Terms of Service
            </a>
            <a href="#" class="text-sm text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
              Support
            </a>
          </div>
        </div>
      </div>
    </footer>

    <!-- Toast Notifications -->
    <Toaster
      position="bottom-right"
      :theme="isDark ? 'dark' : 'light'"
      richColors
      closeButton
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Toaster } from 'vue-sonner'
import { ChevronDown, Menu, X } from 'lucide-vue-next'
import DarkModeToggle from '@/components/DarkModeToggle.vue'
import { useDarkMode } from '../composables/useDarkMode'

// Dark mode
const { isDark } = useDarkMode()

// Menu state
const showUserMenu = ref(false)
const showMobileMenu = ref(false)
</script>
