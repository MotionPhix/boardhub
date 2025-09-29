<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import TenantLayout from '@/layouts/TenantLayout.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
import { Search, MapPin, DollarSign, Calendar, Filter } from 'lucide-vue-next'

interface Billboard {
  id: number
  name: string
  location: string
  size: string
  price: number
  status: string
  description: string
  image_url?: string
  availability_status: string
}

interface Props {
  tenant: {
    id: number
    name: string
    uuid: string
    slug: string
  }
  billboards: {
    data: Billboard[]
    links: any[]
    meta: any
  }
  filters: {
    search?: string
    location?: string
    status?: string
    min_price?: number
    max_price?: number
  }
  locations: string[]
  priceRange: {
    min: number
    max: number
  }
}

const props = defineProps<Props>()

const search = ref(props.filters.search || '')
const location = ref(props.filters.location || '')
const status = ref(props.filters.status || '')
const minPrice = ref(props.filters.min_price || props.priceRange.min)
const maxPrice = ref(props.filters.max_price || props.priceRange.max)
const showFilters = ref(false)

const applyFilters = () => {
  router.get(route('tenant.billboards.index', { tenant: props.tenant.uuid }), {
    search: search.value,
    location: location.value,
    status: status.value,
    min_price: minPrice.value !== props.priceRange.min ? minPrice.value : undefined,
    max_price: maxPrice.value !== props.priceRange.max ? maxPrice.value : undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

const clearFilters = () => {
  search.value = ''
  location.value = ''
  status.value = ''
  minPrice.value = props.priceRange.min
  maxPrice.value = props.priceRange.max
  applyFilters()
}

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}

const getStatusBadgeColor = (status: string) => {
  const colors = {
    available: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    booked: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    maintenance: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'
  }
  return colors[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
}

const hasActiveFilters = computed(() => {
  return search.value || location.value || status.value ||
         minPrice.value !== props.priceRange.min ||
         maxPrice.value !== props.priceRange.max
})
</script>

<template>
  <TenantLayout :tenant="tenant">
    <Head :title="`Browse Billboards - ${tenant.name}`" />

    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between mb-8">
          <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:text-3xl sm:truncate">
              Browse Billboards
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Discover premium billboard locations for your advertising campaigns
            </p>
          </div>
        </div>

        <!-- Search and Filters -->
        <Card class="mb-6 p-6">
          <div class="space-y-4">
            <!-- Main Search -->
            <div class="flex flex-col sm:flex-row gap-4">
              <div class="flex-1">
                <div class="relative">
                  <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                  <Input
                    v-model="search"
                    type="text"
                    placeholder="Search billboards by name, location, or description..."
                    class="pl-10"
                    @keyup.enter="applyFilters"
                  />
                </div>
              </div>
              <div class="flex gap-2">
                <Button @click="applyFilters">
                  Search
                </Button>
                <Button
                  variant="outline"
                  @click="showFilters = !showFilters"
                  :class="hasActiveFilters ? 'border-indigo-500 text-indigo-600' : ''"
                >
                  <Filter class="w-4 h-4 mr-2" />
                  Filters
                  <span v-if="hasActiveFilters" class="ml-2 bg-indigo-500 text-white rounded-full w-2 h-2"></span>
                </Button>
              </div>
            </div>

            <!-- Advanced Filters -->
            <div v-show="showFilters" class="border-t border-gray-200 dark:border-gray-700 pt-4">
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Location Filter -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Location
                  </label>
                  <select
                    v-model="location"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white"
                  >
                    <option value="">All Locations</option>
                    <option v-for="loc in locations" :key="loc" :value="loc">
                      {{ loc }}
                    </option>
                  </select>
                </div>

                <!-- Status Filter -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Availability
                  </label>
                  <select
                    v-model="status"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white"
                  >
                    <option value="">All Status</option>
                    <option value="available">Available</option>
                    <option value="booked">Booked</option>
                    <option value="maintenance">Maintenance</option>
                  </select>
                </div>

                <!-- Price Range -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Min Price
                  </label>
                  <Input
                    v-model.number="minPrice"
                    type="number"
                    :min="priceRange.min"
                    :max="priceRange.max"
                    :placeholder="`$${priceRange.min}`"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Max Price
                  </label>
                  <Input
                    v-model.number="maxPrice"
                    type="number"
                    :min="priceRange.min"
                    :max="priceRange.max"
                    :placeholder="`$${priceRange.max}`"
                  />
                </div>
              </div>

              <!-- Filter Actions -->
              <div class="flex justify-between items-center mt-4">
                <Button
                  variant="ghost"
                  @click="clearFilters"
                  v-show="hasActiveFilters"
                >
                  Clear Filters
                </Button>
                <div class="flex gap-2">
                  <Button variant="outline" @click="showFilters = false">
                    Cancel
                  </Button>
                  <Button @click="applyFilters">
                    Apply Filters
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </Card>

        <!-- Results Summary -->
        <div class="flex justify-between items-center mb-6">
          <p class="text-sm text-gray-600 dark:text-gray-400">
            Showing {{ billboards.data.length }} of {{ billboards.meta.total }} billboards
          </p>
          <div class="text-sm text-gray-500 dark:text-gray-400">
            Page {{ billboards.meta.current_page }} of {{ billboards.meta.last_page }}
          </div>
        </div>

        <!-- Billboard Grid -->
        <div v-if="billboards.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
          <Card
            v-for="billboard in billboards.data"
            :key="billboard.id"
            class="overflow-hidden hover:shadow-lg transition-shadow cursor-pointer"
            @click="router.visit(route('tenant.billboards.show', { tenant: tenant.uuid, billboard: billboard.id }))"
          >
            <!-- Billboard Image -->
            <div class="aspect-video bg-gray-200 dark:bg-gray-700 relative">
              <img
                v-if="billboard.image_url"
                :src="billboard.image_url"
                :alt="billboard.name"
                class="w-full h-full object-cover"
              />
              <div v-else class="flex items-center justify-center h-full">
                <MapPin class="h-12 w-12 text-gray-400" />
              </div>

              <!-- Status Badge -->
              <div class="absolute top-3 right-3">
                <span
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="getStatusBadgeColor(billboard.status)"
                >
                  {{ billboard.status }}
                </span>
              </div>
            </div>

            <!-- Billboard Info -->
            <div class="p-6">
              <div class="flex justify-between items-start mb-2">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                  {{ billboard.name }}
                </h3>
                <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">
                  {{ formatCurrency(billboard.price) }}
                </span>
              </div>

              <div class="flex items-center text-gray-600 dark:text-gray-400 mb-2">
                <MapPin class="h-4 w-4 mr-1" />
                <span class="text-sm">{{ billboard.location }}</span>
              </div>

              <div class="flex items-center text-gray-600 dark:text-gray-400 mb-3">
                <span class="text-sm bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-full">
                  {{ billboard.size }}
                </span>
              </div>

              <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                {{ billboard.description }}
              </p>

              <div class="flex justify-between items-center">
                <Button variant="outline" size="sm">
                  <Calendar class="w-4 h-4 mr-2" />
                  Check Availability
                </Button>
                <Button size="sm">
                  View Details
                </Button>
              </div>
            </div>
          </Card>
        </div>

        <!-- Empty State -->
        <Card v-else class="p-12 text-center">
          <MapPin class="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
            No billboards found
          </h3>
          <p class="text-gray-600 dark:text-gray-400 mb-4">
            Try adjusting your search criteria or filters to find billboards.
          </p>
          <Button variant="outline" @click="clearFilters">
            Clear Filters
          </Button>
        </Card>

        <!-- Pagination -->
        <div v-if="billboards.meta.last_page > 1" class="flex justify-center">
          <nav class="flex items-center space-x-2">
            <Button
              v-for="link in billboards.links"
              :key="link.label"
              :variant="link.active ? 'default' : 'outline'"
              :disabled="!link.url"
              @click="link.url && router.visit(link.url)"
              v-html="link.label"
              size="sm"
            />
          </nav>
        </div>
      </div>
    </div>
  </TenantLayout>
</template>