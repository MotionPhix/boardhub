<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import MemberLayout from '../../layouts/MemberLayout.vue'

interface Props {
  tenant: {
    id: number
    name: string
    slug: string
    uuid: string
  }
  stats: {
    my_bookings: number
    active_bookings: number
    total_spent: number
    available_billboards: number
  }
  my_bookings: Array<{
    id: number
    start_date: string
    end_date: string
    status: string
    amount: number
    billboard: {
      id: number
      title: string
      location: string
      image_url?: string
    }
  }>
  available_billboards: Array<{
    id: number
    title: string
    location: string
    price_per_day: number
    status: string
    image_url?: string
  }>
}

const props = defineProps<Props>()

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount / 100) // Assuming amount is in cents
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString()
}
</script>

<template>
  <MemberLayout :tenant="tenant">
    <Head :title="`${tenant.name} - My Dashboard`" />

    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between">
          <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:text-3xl sm:truncate">
              My Dashboard
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Your bookings and available billboards at {{ tenant.name }}
            </p>
          </div>
          <div class="mt-4 flex space-x-3 md:mt-0 md:ml-4">
            <Link
              :href="route('member.billboards.index', { tenant: tenant.uuid })"
              class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Browse Billboards
            </Link>
          </div>
        </div>

        <!-- Stats Grid -->
        <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                      My Bookings
                    </dt>
                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                      {{ stats.my_bookings }}
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

          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" />
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                      Total Spent
                    </dt>
                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                      {{ formatCurrency(stats.total_spent) }}
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
                      <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4a1 1 0 011-1h12a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1V8z" clip-rule="evenodd" />
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                      Available Billboards
                    </dt>
                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                      {{ stats.available_billboards }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Content Grid -->
        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- My Recent Bookings -->
          <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
              <div class="flex items-center justify-between">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                  My Recent Bookings
                </h3>
                <Link
                  :href="route('member.bookings.index', { tenant: tenant.uuid })"
                  class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500"
                >
                  View all
                </Link>
              </div>
              <div class="mt-5">
                <div class="flow-root">
                  <ul class="-my-4 divide-y divide-gray-200 dark:divide-gray-700">
                    <li v-for="booking in my_bookings" :key="booking.id" class="py-4">
                      <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0">
                          <img
                            v-if="booking.billboard.image_url"
                            :src="booking.billboard.image_url"
                            :alt="booking.billboard.title"
                            class="h-10 w-10 rounded-lg object-cover"
                          >
                          <div
                            v-else
                            class="h-10 w-10 rounded-lg bg-gray-300 dark:bg-gray-600 flex items-center justify-center"
                          >
                            <svg class="h-6 w-6 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                              <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4a1 1 0 011-1h12a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1V8z" clip-rule="evenodd" />
                            </svg>
                          </div>
                        </div>
                        <div class="flex-1 min-w-0">
                          <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ booking.billboard.title }}
                          </p>
                          <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                            {{ booking.billboard.location }}
                          </p>
                        </div>
                        <div class="flex-shrink-0 text-right">
                          <p class="text-sm text-gray-900 dark:text-white">
                            {{ formatCurrency(booking.amount) }}
                          </p>
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
                <div v-if="my_bookings.length === 0" class="text-center py-8">
                  <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    You haven't made any bookings yet
                  </p>
                  <Link
                    :href="route('member.billboards.index', { tenant: tenant.uuid })"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-700 dark:text-indigo-300 bg-indigo-100 dark:bg-indigo-900 hover:bg-indigo-200 dark:hover:bg-indigo-800"
                  >
                    Browse Available Billboards
                  </Link>
                </div>
              </div>
            </div>
          </div>

          <!-- Available Billboards -->
          <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
              <div class="flex items-center justify-between">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                  Available Billboards
                </h3>
                <Link
                  :href="route('member.billboards.index', { tenant: tenant.uuid })"
                  class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500"
                >
                  View all
                </Link>
              </div>
              <div class="mt-5">
                <div class="grid grid-cols-1 gap-4">
                  <div
                    v-for="billboard in available_billboards"
                    :key="billboard.id"
                    class="flex items-center space-x-4 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:shadow-md transition-shadow"
                  >
                    <div class="flex-shrink-0">
                      <img
                        v-if="billboard.image_url"
                        :src="billboard.image_url"
                        :alt="billboard.title"
                        class="h-12 w-12 rounded-lg object-cover"
                      >
                      <div
                        v-else
                        class="h-12 w-12 rounded-lg bg-gray-300 dark:bg-gray-600 flex items-center justify-center"
                      >
                        <svg class="h-6 w-6 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4a1 1 0 011-1h12a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1V8z" clip-rule="evenodd" />
                        </svg>
                      </div>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                        {{ billboard.title }}
                      </p>
                      <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                        {{ billboard.location }}
                      </p>
                    </div>
                    <div class="flex-shrink-0 text-right">
                      <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ formatCurrency(billboard.price_per_day) }}/day
                      </p>
                      <Link
                        :href="route('member.billboards.show', { tenant: tenant.uuid, billboard: billboard.id })"
                        class="inline-flex items-center text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-500"
                      >
                        View Details
                      </Link>
                    </div>
                  </div>
                </div>
                <div v-if="available_billboards.length === 0" class="text-center py-8">
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    No billboards available at the moment
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MemberLayout>
</template>