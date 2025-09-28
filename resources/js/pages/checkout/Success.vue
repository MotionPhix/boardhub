<template>
  <AppLayout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center py-12">
      <div class="max-w-md w-full mx-auto px-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 text-center">
          <!-- Success Icon -->
          <div class="mx-auto w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-6">
            <CheckCircle class="h-8 w-8 text-green-600 dark:text-green-400" />
          </div>

          <!-- Success Message -->
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
            Payment Successful!
          </h1>

          <p class="text-gray-600 dark:text-gray-400 mb-8">
            Your organization has been created successfully. You can now start using your subscription.
          </p>

          <!-- Action Buttons -->
          <div class="space-y-3">
            <Link
              :href="route('tenants.select')"
              class="w-full inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 transition-colors"
            >
              Access Your Organization
            </Link>

            <Link
              :href="route('home')"
              class="w-full inline-flex items-center justify-center px-6 py-3 border border-gray-300 dark:border-gray-600 text-base font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
            >
              Go to Dashboard
            </Link>
          </div>

          <!-- Additional Info -->
          <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">
              A confirmation email has been sent to your email address with your receipt and organization details.
            </p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AppLayout from '../../layouts/AppLayout.vue'
import { CheckCircle } from 'lucide-vue-next'
</script>