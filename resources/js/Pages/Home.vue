<template>
  <AppLayout>
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div class="text-center">
          <h1 class="text-4xl font-bold tracking-tight text-white sm:text-6xl">
            Find Perfect Billboard Space in Malawi
          </h1>
          <p class="mt-6 text-xl leading-8 text-indigo-100 max-w-2xl mx-auto">
            Connect with billboard owners across Malawi. Get instant pricing, check real-time availability, and book premium advertising locations.
          </p>

          <!-- Search Bar -->
          <div class="mt-10 max-w-xl mx-auto">
            <div class="flex rounded-md shadow-sm">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search by location, size, or features..."
                class="flex-1 min-w-0 px-4 py-3 text-gray-900 placeholder-gray-500 border-0 rounded-l-md focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-600"
              />
              <button
                @click="searchBillboards"
                class="px-6 py-3 bg-white text-indigo-600 font-semibold rounded-r-md hover:bg-gray-50 focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-600"
              >
                Search
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="bg-white py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
          <div class="text-center">
            <div class="text-3xl font-bold text-indigo-600">{{ stats.totalBillboards }}+</div>
            <div class="mt-2 text-gray-600">Premium Billboards</div>
          </div>
          <div class="text-center">
            <div class="text-3xl font-bold text-indigo-600">{{ stats.cities }}+</div>
            <div class="mt-2 text-gray-600">Cities Covered</div>
          </div>
          <div class="text-center">
            <div class="text-3xl font-bold text-indigo-600">{{ stats.activeAgencies }}+</div>
            <div class="mt-2 text-gray-600">Active Ad Agencies</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Featured Billboards -->
    <div class="bg-gray-50 py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-bold text-gray-900">Featured Billboard Locations</h2>
          <p class="mt-4 text-lg text-gray-600">Premium advertising spaces available now</p>
        </div>

        <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="billboard in featuredBillboards"
            :key="billboard.id"
            class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer"
            @click="viewBillboard(billboard.id)"
          >
            <div class="aspect-w-16 aspect-h-9 bg-gray-200">
              <img
                :src="billboard.image || '/images/billboard-placeholder.jpg'"
                :alt="billboard.name"
                class="w-full h-48 object-cover"
              />
            </div>

            <div class="p-6">
              <div class="flex items-center justify-between mb-2">
                <h3 class="text-lg font-semibold text-gray-900">{{ billboard.name }}</h3>
                <span
                  :class="statusClasses(billboard.status)"
                  class="px-2 py-1 text-xs font-medium rounded-full"
                >
                  {{ billboard.status }}
                </span>
              </div>

              <p class="text-gray-600 mb-4">{{ billboard.location }}</p>

              <div class="flex items-center justify-between">
                <div class="text-sm text-gray-500">{{ billboard.size }}</div>
                <div class="text-lg font-bold text-indigo-600">
                  MWK {{ formatPrice(billboard.price) }}/month
                </div>
              </div>

              <button
                @click.stop="quickBook(billboard)"
                class="mt-4 w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
              >
                Quick Book
              </button>
            </div>
          </div>
        </div>

        <div class="text-center mt-12">
          <Link
            href="/browse"
            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-indigo-600 bg-white hover:bg-indigo-50 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
          >
            View All Billboards
            <svg class="ml-2 -mr-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
          </Link>
        </div>
      </div>
    </div>

    <!-- How It Works -->
    <div class="bg-white py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-bold text-gray-900">How AdPro Works</h2>
          <p class="mt-4 text-lg text-gray-600">Simple, transparent, and efficient billboard booking</p>
        </div>

        <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
          <div class="text-center">
            <div class="mx-auto h-16 w-16 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
              <svg class="h-8 w-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">1. Search & Discover</h3>
            <p class="text-gray-600">Browse available billboard spaces across Malawi with real-time availability and transparent pricing.</p>
          </div>

          <div class="text-center">
            <div class="mx-auto h-16 w-16 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
              <svg class="h-8 w-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">2. Book Instantly</h3>
            <p class="text-gray-600">Select your preferred dates, get instant pricing, and book your billboard space with just a few clicks.</p>
          </div>

          <div class="text-center">
            <div class="mx-auto h-16 w-16 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
              <svg class="h-8 w-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">3. Launch Campaign</h3>
            <p class="text-gray-600">Coordinate with billboard owners, upload your creative, and launch your advertising campaign seamlessly.</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  featuredBillboards: Array,
  stats: Object,
})

const searchQuery = ref('')

const searchBillboards = () => {
  if (searchQuery.value.trim()) {
    router.get('/browse', { search: searchQuery.value })
  } else {
    router.get('/browse')
  }
}

const viewBillboard = (id) => {
  router.get(`/billboards/${id}`)
}

const quickBook = (billboard) => {
  router.post('/bookings/quick', { billboard_id: billboard.id })
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
