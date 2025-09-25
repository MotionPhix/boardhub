<template>
  <AppLayout>
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <h1 class="text-2xl font-bold text-gray-900">My Bookings</h1>
        <p class="mt-1 text-gray-600">Track and manage your billboard booking requests</p>
      </div>
    </div>

    <!-- Bookings List -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div v-if="bookings.data.length > 0" class="space-y-6">
        <div
          v-for="booking in bookings.data"
          :key="booking.id"
          class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow"
        >
          <div class="p-6">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center space-x-3 mb-2">
                  <h3 class="text-lg font-semibold text-gray-900">
                    {{ booking.billboard.name }}
                  </h3>
                  <span
                    :class="getStatusClasses(booking.status)"
                    class="px-2 py-1 text-xs font-medium rounded-full"
                  >
                    {{ formatStatus(booking.status) }}
                  </span>
                </div>

                <p class="text-gray-600 mb-2 flex items-center">
                  <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  </svg>
                  {{ booking.billboard.location }}
                </p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                  <div>
                    <span class="text-gray-500">Campaign Period:</span>
                    <div class="font-medium">
                      {{ formatDate(booking.start_date) }} - {{ formatDate(booking.end_date) }}
                    </div>
                  </div>

                  <div>
                    <span class="text-gray-500">Duration:</span>
                    <div class="font-medium">{{ getDuration(booking) }} days</div>
                  </div>

                  <div>
                    <span class="text-gray-500">Requested Price:</span>
                    <div class="font-medium text-indigo-600">
                      MWK {{ formatPrice(booking.requested_price) }}
                    </div>
                  </div>
                </div>

                <div v-if="booking.campaign_details" class="mt-3">
                  <span class="text-gray-500 text-sm">Campaign Details:</span>
                  <p class="text-gray-700 text-sm mt-1">{{ booking.campaign_details }}</p>
                </div>
              </div>

              <div class="ml-6 flex flex-col space-y-2">
                <Link
                  :href="`/bookings/${booking.id}`"
                  class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700 text-center"
                >
                  View Details
                </Link>

                <button
                  v-if="booking.status === 'requested'"
                  @click="cancelBooking(booking.id)"
                  class="px-4 py-2 border border-red-300 text-red-600 text-sm rounded-md hover:bg-red-50 text-center"
                >
                  Cancel
                </button>
              </div>
            </div>

            <!-- Status Timeline -->
            <div v-if="booking.status_history && booking.status_history.length > 0" class="mt-4 pt-4 border-t border-gray-200">
              <h4 class="text-sm font-medium text-gray-900 mb-2">Status History</h4>
              <div class="space-y-1">
                <div
                  v-for="(history, index) in booking.status_history.slice(-3)"
                  :key="index"
                  class="text-xs text-gray-600"
                >
                  {{ formatStatus(history.to_status) }} - {{ formatDateTime(history.changed_at) }}
                  <span v-if="history.reason" class="text-gray-500">({{ history.reason }})</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No bookings yet</h3>
        <p class="text-gray-500 mb-6">Start browsing billboards to make your first booking request.</p>
        <Link
          href="/browse"
          class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
        >
          Browse Billboards
        </Link>
      </div>

      <!-- Pagination -->
      <div v-if="bookings.data.length > 0 && bookings.last_page > 1" class="mt-8 flex justify-center">
        <nav class="flex items-center space-x-2">
          <Link
            v-if="bookings.prev_page_url"
            :href="bookings.prev_page_url"
            class="px-3 py-2 text-gray-500 hover:text-gray-700 border border-gray-300 rounded-md"
          >
            Previous
          </Link>

          <span class="px-3 py-2 text-gray-700">
            Page {{ bookings.current_page }} of {{ bookings.last_page }}
          </span>

          <Link
            v-if="bookings.next_page_url"
            :href="bookings.next_page_url"
            class="px-3 py-2 text-gray-500 hover:text-gray-700 border border-gray-300 rounded-md"
          >
            Next
          </Link>
        </nav>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  bookings: Object,
})

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-GB', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDateTime = (datetime) => {
  return new Date(datetime).toLocaleString('en-GB', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('en-MW').format(price)
}

const formatStatus = (status) => {
  const statusLabels = {
    'requested': 'Pending Review',
    'confirmed': 'Confirmed',
    'rejected': 'Rejected',
    'cancelled': 'Cancelled',
    'completed': 'Completed'
  }
  return statusLabels[status] || status
}

const getStatusClasses = (status) => {
  const classes = {
    'requested': 'bg-yellow-100 text-yellow-800',
    'confirmed': 'bg-green-100 text-green-800',
    'rejected': 'bg-red-100 text-red-800',
    'cancelled': 'bg-gray-100 text-gray-800',
    'completed': 'bg-blue-100 text-blue-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getDuration = (booking) => {
  const start = new Date(booking.start_date)
  const end = new Date(booking.end_date)
  const diffTime = Math.abs(end - start)
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1
}

const cancelBooking = (bookingId) => {
  if (confirm('Are you sure you want to cancel this booking request?')) {
    router.delete(`/bookings/${bookingId}`, {
      onSuccess: () => {
        // Booking cancelled successfully
      }
    })
  }
}
</script>
