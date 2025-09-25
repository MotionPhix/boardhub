<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation Header -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <Link href="/" class="flex-shrink-0">
              <img class="h-8 w-auto" src="/images/logo.svg" alt="AdPro" />
              <span class="ml-2 text-xl font-bold text-indigo-600">AdPro</span>
            </Link>

            <div class="hidden md:ml-10 md:flex md:space-x-8">
              <Link href="/" class="text-gray-900 hover:text-indigo-600 px-3 py-2 text-sm font-medium">
                Browse Billboards
              </Link>
              <Link href="/locations" class="text-gray-500 hover:text-indigo-600 px-3 py-2 text-sm font-medium">
                Locations
              </Link>
              <Link href="/pricing" class="text-gray-500 hover:text-indigo-600 px-3 py-2 text-sm font-medium">
                Pricing
              </Link>
            </div>
          </div>

          <div class="flex items-center space-x-4">
            <template v-if="$page.props.auth.user">
              <Link href="/dashboard" class="text-gray-500 hover:text-indigo-600 px-3 py-2 text-sm font-medium">
                My Bookings
              </Link>
              <Link href="/logout" method="post" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                Logout
              </Link>
            </template>
            <template v-else>
              <Link href="/login" class="text-gray-500 hover:text-indigo-600 px-3 py-2 text-sm font-medium">
                Sign In
              </Link>
              <Link href="/register" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                Get Started
              </Link>
            </template>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main>
      <slot />
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200">
      <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
          <div class="col-span-1 md:col-span-2">
            <div class="flex items-center">
              <span class="text-xl font-bold text-indigo-600">AdPro</span>
            </div>
            <p class="mt-4 text-gray-600 max-w-md">
              Malawi's premier billboard advertising marketplace. Connect with billboard owners and find the perfect advertising space for your campaigns.
            </p>
          </div>

          <div>
            <h3 class="text-sm font-semibold text-gray-900 tracking-wider uppercase">Platform</h3>
            <ul class="mt-4 space-y-2">
              <li><a href="/browse" class="text-gray-600 hover:text-indigo-600">Browse Billboards</a></li>
              <li><a href="/pricing" class="text-gray-600 hover:text-indigo-600">Pricing</a></li>
              <li><a href="/locations" class="text-gray-600 hover:text-indigo-600">Locations</a></li>
            </ul>
          </div>

          <div>
            <h3 class="text-sm font-semibold text-gray-900 tracking-wider uppercase">Support</h3>
            <ul class="mt-4 space-y-2">
              <li><a href="/contact" class="text-gray-600 hover:text-indigo-600">Contact Us</a></li>
              <li><a href="/help" class="text-gray-600 hover:text-indigo-600">Help Center</a></li>
              <li><a href="/terms" class="text-gray-600 hover:text-indigo-600">Terms of Service</a></li>
            </ul>
          </div>
        </div>

        <div class="mt-8 border-t border-gray-200 pt-8">
          <p class="text-center text-gray-600">
            © {{ new Date().getFullYear() }} AdPro. Connecting Malawi's advertising ecosystem.
          </p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
</script>
