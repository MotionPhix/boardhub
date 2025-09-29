<template>
  <AppLayout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center py-12">
      <div class="max-w-md w-full mx-auto px-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 text-center">
          <!-- Error Icon -->
          <div class="mx-auto w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mb-6">
            <XCircle class="h-8 w-8 text-red-600 dark:text-red-400" />
          </div>

          <!-- Error Message -->
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
            Payment Failed
          </h1>

          <p class="text-gray-600 dark:text-gray-400 mb-8">
            {{ $page.props.flash?.error || 'There was an issue processing your payment. Please try again or contact support if the problem persists.' }}
          </p>

          <!-- Action Buttons -->
          <div class="space-y-3">
            <Link
              :href="route('organizations.create')"
              class="w-full inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 transition-colors"
            >
              <ArrowLeft class="h-4 w-4 mr-2" />
              Try Again
            </Link>

            <Link
              :href="route('home')"
              class="w-full inline-flex items-center justify-center px-6 py-3 border border-gray-300 dark:border-gray-600 text-base font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
            >
              Go to Dashboard
            </Link>
          </div>

          <!-- Help Section -->
          <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
              Need help? Contact our support team:
            </p>

            <div class="space-y-2">
              <div class="flex items-center justify-center text-sm text-gray-600 dark:text-gray-400">
                <Mail class="h-4 w-4 mr-2" />
                <a href="mailto:support@adpro.com" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                  support@adpro.com
                </a>
              </div>

              <div class="flex items-center justify-center text-sm text-gray-600 dark:text-gray-400">
                <Phone class="h-4 w-4 mr-2" />
                <a href="tel:+260000000000" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                  +260 000 000 000
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { XCircle, ArrowLeft, Mail, Phone } from 'lucide-vue-next'
</script>