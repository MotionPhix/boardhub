<template>
  <AppLayout>
    <!-- Search and Filter Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
          <div class="flex-1 max-w-lg">
            <div class="relative">
              <input
                v-model="searchQuery"
                @keyup.enter="search"
                type="text"
                placeholder="Search billboards by location, name, or features..."
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
              />
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
            </div>
          </div>

          <div class="flex items-center space-x-4">
            <select
              v-model="filters.location"
              @change="search"
              class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500"
            >
              <option value="">All Locations</option>
              <option v-for="location in locations" :key="location" :value="location">
                {{ location }}
              </option>
            </select>

            <button
              @click="showFilters = !showFilters"
              class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500"
            >
              More Filters
            </button>
          </div>
        </div>

        <!-- Advanced Filters -->
        <div v-if="showFilters" class="mt-4 p-4 bg-gray-50 rounded-lg">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Min Price (MWK)</label>
              <input
                v-model="filters.min_price"
                type="number"
                class="w-full border border-gray-300 rounded-lg px-3 py-2"
                :placeholder="priceRange.min"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Max Price (MWK)</label>
              <input
                v-model="filters.max_price"
                type="number"
                class="w-full border border-gray-300 rounded-lg px-3 py-2"
                :placeholder="priceRange.max"
              />
            </div>
            <div class="flex items-end">
              <button
                @click="search"
                class="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700"
              >
                Apply Filters
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Results Header -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">
          Available Billboards
          <span class="text-lg font-normal text-gray-500">
            ({{ billboards.total }} results)
          </span>
        </h1>

        <div class="flex items-center space-x-2">
          <span class="text-sm text-gray-500">Sort by:</span>
          <select
            v-model="sortBy"
            @change="search"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm"
          >
            <option value="created_at">Newest First</option>
            <option value="price_asc">Price: Low to High</option>
            <option value="price_desc">Price: High to Low</option>
            <option value="name">Name A-Z</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Billboard Grid -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <div
          v-for="billboard in billboards.data"
          :key="billboard.id"
          class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer group"
          @click="viewBillboard(billboard.id)"
        >
          <div class="aspect-w-16 aspect-h-9 bg-gray-200">
            <img
              :src="billboard.image || '/images/billboard-placeholder.jpg'"
              :alt="billboard.name"
              class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300"
            />
            <div class="absolute top-2 right-2">
              <span
                :class="statusClasses(billboard.status)"
                class="px-2 py-1 text-xs font-medium rounded-full"
              >
                {{ billboard.status }}
              </span>
            </div>
          </div>

          <div class="p-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ billboard.name }}</h3>
            <p class="text-gray-600 mb-2 flex items-center">
              <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              {{ billboard.location }}
            </p>

            <div class="flex items-center justify-between mb-4">
              <div class="text-sm text-gray-500">{{ billboard.size }}</div>
              <div class="text-lg font-bold text-indigo-600">
                MWK {{ formatPrice(billboard.price) }}/month
              </div>
            </div>

            <button
              @click.stop="quickBook(billboard)"
              class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors"
            >
              Book Now
            </button>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="billboards.data.length > 0" class="mt-8 flex justify-center">
        <nav class="flex items-center space-x-2">
          <Link
            v-if="billboards.prev_page_url"
            :href="billboards.prev_page_url"
            class="px-3 py-2 text-gray-500 hover:text-gray-700 border border-gray-300 rounded-md"
          >
            Previous
          </Link>

          <span class="px-3 py-2 text-gray-700">
            Page {{ billboards.current_page }} of {{ billboards.last_page }}
          </span>

          <Link
            v-if="billboards.next_page_url"
            :href="billboards.next_page_url"
            class="px-3 py-2 text-gray-500 hover:text-gray-700 border border-gray-300 rounded-md"
          >
            Next
          </Link>
        </nav>
      </div>

      <!-- Empty State -->
      <div v-if="billboards.data.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.291-1.1-5.573-2.726" />
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No billboards found</h3>
        <p class="text-gray-500">Try adjusting your search criteria or browse all available billboards.</p>
        <Link href="/" class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
          Browse All Billboards
        </Link>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  billboards: Object,
  filters: Object,
  locations: Array,
  priceRange: Object,
})

const searchQuery = ref(props.filters.search || '')
const showFilters = ref(false)
const sortBy = ref('created_at')

const filters = ref({
  location: props.filters.location || '',
  min_price: props.filters.min_price || '',
  max_price: props.filters.max_price || '',
})

const search = () => {
  const params = {
    search: searchQuery.value,
    sort: sortBy.value,
    ...filters.value,
  }

  // Remove empty values
  Object.keys(params).forEach(key => {
    if (!params[key]) delete params[key]
  })

  router.get('/browse', params, {
    preserveState: true,
    preserveScroll: true,
  })
}

const viewBillboard = (id) => {
  router.get(`/billboards/${id}`)
}

const quickBook = (billboard) => {
  if (!$page.props.auth.user) {
    router.get('/login')
    return
  }

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

