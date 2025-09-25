<template>
  <AppLayout>
    <!-- Billboard Header -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Billboard Image -->
        <div class="space-y-4">
          <div class="aspect-w-16 aspect-h-10 bg-gray-200 rounded-lg overflow-hidden">
            <img
              :src="billboard.image || '/images/billboard-placeholder.jpg'"
              :alt="billboard.name"
              class="w-full h-full object-cover"
            />
          </div>

          <!-- Additional images gallery would go here -->
          <div class="grid grid-cols-4 gap-2">
            <div v-for="i in 4" :key="i" class="aspect-w-16 aspect-h-10 bg-gray-100 rounded-lg">
              <div class="flex items-center justify-center text-gray-400">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
            </div>
          </div>
        </div>

        <!-- Billboard Details -->
        <div class="space-y-6">
          <div>
            <div class="flex items-center justify-between mb-2">
              <h1 class="text-3xl font-bold text-gray-900">{{ billboard.name }}</h1>
              <span
                :class="statusClasses(billboard.status)"
                class="px-3 py-1 text-sm font-medium rounded-full"
              >
                {{ billboard.status }}
              </span>
            </div>

            <p class="text-lg text-gray-600 flex items-center">
              <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              {{ billboard.location }}
            </p>
          </div>

          <!-- Key Details -->
          <div class="bg-gray-50 p-6 rounded-lg">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Billboard Details</h3>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <span class="block text-sm text-gray-500">Size</span>
                <span class="text-lg font-medium">{{ billboard.size }}</span>
              </div>
              <div>
                <span class="block text-sm text-gray-500">Monthly Rate</span>
                <span class="text-2xl font-bold text-indigo-600">MWK {{ formatPrice(billboard.price) }}</span>
              </div>
            </div>
          </div>

          <!-- Description -->
          <div v-if="billboard.description">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Description</h3>
            <p class="text-gray-600">{{ billboard.description }}</p>
          </div>

          <!-- Booking Form -->
          <div v-if="billboard.status === 'available'" class="bg-white border border-gray-200 p-6 rounded-lg">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Book This Billboard</h3>

            <form @submit.prevent="submitBooking" class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                  <input
                    v-model="bookingForm.start_date"
                    type="date"
                    required
                    :min="minDate"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                  <input
                    v-model="bookingForm.end_date"
                    type="date"
                    required
                    :min="bookingForm.start_date || minDate"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500"
                  />
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Campaign Details (Optional)</label>
                <textarea
                  v-model="bookingForm.campaign_details"
                  rows="3"
                  placeholder="Tell us about your campaign..."
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500"
                ></textarea>
              </div>

              <!-- Price Calculation -->
              <div v-if="calculatedPrice" class="bg-indigo-50 p-4 rounded-lg">
                <div class="flex justify-between items-center mb-2">
                  <span class="text-sm text-gray-600">Duration</span>
                  <span class="font-medium">{{ durationDays }} days</span>
                </div>
                <div class="flex justify-between items-center mb-2">
                  <span class="text-sm text-gray-600">Monthly Rate</span>
                  <span class="font-medium">MWK {{ formatPrice(billboard.price) }}</span>
                </div>
                <hr class="my-2">
                <div class="flex justify-between items-center">
                  <span class="text-lg font-semibold">Total Estimate</span>
                  <span class="text-xl font-bold text-indigo-600">MWK {{ formatPrice(calculatedPrice) }}</span>
                </div>
              </div>

              <button
                type="submit"
                :disabled="!canSubmit || processing"
                class="w-full bg-indigo-600 text-white py-3 px-6 rounded-lg hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed font-medium"
              >
                {{ processing ? 'Submitting...' : 'Request Booking' }}
              </button>
            </form>
          </div>

          <!-- Unavailable Notice -->
          <div v-else class="bg-red-50 border border-red-200 p-6 rounded-lg">
            <h3 class="text-lg font-semibold text-red-800 mb-2">Currently Unavailable</h3>
            <p class="text-red-600">This billboard is not available for booking at this time.</p>
            <Link
              href="/browse"
              class="mt-4 inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
            >
              Browse Other Billboards
            </Link>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  billboard: Object,
  suggestedDates: Object,
  availabilityCalendar: Object,
})

const bookingForm = useForm({
  start_date: props.suggestedDates.start_date,
  end_date: props.suggestedDates.end_date,
  campaign_details: '',
})

const processing = ref(false)

const minDate = computed(() => {
  return new Date().toISOString().split('T')[0]
})

const durationDays = computed(() => {
  if (!bookingForm.start_date || !bookingForm.end_date) return 0

  const start = new Date(bookingForm.start_date)
  const end = new Date(bookingForm.end_date)
  const diffTime = Math.abs(end - start)
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1
})

const calculatedPrice = computed(() => {
  if (durationDays.value <= 0) return 0

  // Calculate prorated monthly price
  const monthlyRate = parseFloat(props.billboard.price)
  const dailyRate = monthlyRate / 30
  return Math.round(dailyRate * durationDays.value)
})

const canSubmit = computed(() => {
  return bookingForm.start_date &&
         bookingForm.end_date &&
         new Date(bookingForm.end_date) > new Date(bookingForm.start_date) &&
         !processing.value
})

const submitBooking = () => {
  if (!$page.props.auth.user) {
    router.get('/login')
    return
  }

  processing.value = true

  bookingForm.post('/bookings', {
    onSuccess: () => {
      processing.value = false
    },
    onError: () => {
      processing.value = false
    }
  })
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('en-MW').format(price)
}

const statusClasses = (status) => {
  const classes = {
    'available': 'bg-green-100 text-green-800',
    'occupied': 'bg-red-100 text-red-800',
    'maintenance': 'bg-yellow-100 text-yellow-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}
</script>

