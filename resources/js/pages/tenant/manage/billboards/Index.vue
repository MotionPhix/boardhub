<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import TenantLayout from '@/layouts/TenantLayout.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
import Select from '@/components/ui/Select.vue'
import {
  Plus,
  Search,
  Filter,
  MoreHorizontal,
  Eye,
  Edit,
  Trash2,
  MapPin,
  DollarSign,
  Calendar,
  BarChart3,
  Building2
} from 'lucide-vue-next'

interface Tenant {
  id: number
  uuid: string
  name: string
}

interface Billboard {
  id: number
  title: string
  description: string
  location: string
  price_per_day: number
  size: string
  availability_status: 'available' | 'booked' | 'unavailable'
  visibility_rating: number
  traffic_rating: number
  image_url?: string
  bookings_count: number
  created_at: string
}

interface PaginatedBillboards {
  data: Billboard[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  links: Array<{
    url: string | null
    label: string
    active: boolean
  }>
}

interface Stats {
  total: number
  available: number
  booked: number
  maintenance: number
}

interface Filters {
  sizes: string[]
  statuses: Record<string, string>
}

interface Props {
  tenant: Tenant
  billboards: PaginatedBillboards
  stats: Stats
  filters: Filters
  queryParams: Record<string, any>
}

const props = defineProps<Props>()

const searchQuery = ref(props.queryParams.search || '')
const selectedStatus = ref(props.queryParams.status || '')
const selectedSize = ref(props.queryParams.size || '')
const showFilters = ref(false)

const search = () => {
  router.get(route('tenant.manage.billboards.index', { tenant: props.tenant.uuid }), {
    search: searchQuery.value,
    status: selectedStatus.value,
    size: selectedSize.value
  }, {
    preserveState: true,
    replace: true
  })
}

const clearFilters = () => {
  searchQuery.value = ''
  selectedStatus.value = ''
  selectedSize.value = ''
  search()
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'available':
      return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
    case 'booked':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'
    case 'unavailable':
      return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
    default:
      return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
  }
}

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('en-ZM', {
    style: 'currency',
    currency: 'ZMW'
  }).format(price)
}

const renderStars = (rating: number) => {
  return Array.from({ length: 5 }, (_, i) => i < rating)
}

const deleteBillboard = (billboard: Billboard) => {
  if (confirm(`Are you sure you want to delete "${billboard.title}"?`)) {
    router.delete(route('tenant.manage.billboards.destroy', {
      tenant: props.tenant.uuid,
      billboard: billboard.id
    }))
  }
}
</script>

<template>
  <TenantLayout>
    <Head :title="`Billboard Management - ${tenant.name}`" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Billboard Management
          </h1>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Manage your billboard inventory and performance
          </p>
        </div>
        <div class="mt-4 sm:mt-0">
          <Button :href="route('tenant.manage.billboards.create', { tenant: tenant.uuid })">
            <Plus class="w-4 h-4 mr-2" />
            Add Billboard
          </Button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <Card class="p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <Building2 class="h-8 w-8 text-gray-400" />
            </div>
            <div class="ml-4">
              <div class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ stats.total }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">
                Total Billboards
              </div>
            </div>
          </div>
        </Card>

        <Card class="p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
              </div>
            </div>
            <div class="ml-4">
              <div class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ stats.available }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">
                Available
              </div>
            </div>
          </div>
        </Card>

        <Card class="p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
              </div>
            </div>
            <div class="ml-4">
              <div class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ stats.booked }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">
                Booked
              </div>
            </div>
          </div>
        </Card>

        <Card class="p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                <div class="w-3 h-3 bg-red-500 rounded-full"></div>
              </div>
            </div>
            <div class="ml-4">
              <div class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ stats.maintenance }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">
                Maintenance
              </div>
            </div>
          </div>
        </Card>
      </div>

      <!-- Search and Filters -->
      <Card class="p-6">
        <div class="space-y-4">
          <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1 relative">
              <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
              <Input
                v-model="searchQuery"
                placeholder="Search billboards..."
                class="pl-10"
                @keyup.enter="search"
              />
            </div>
            <div class="flex gap-2">
              <Button
                variant="outline"
                @click="showFilters = !showFilters"
              >
                <Filter class="w-4 h-4 mr-2" />
                Filters
              </Button>
              <Button @click="search">
                Search
              </Button>
            </div>
          </div>

          <!-- Filter Options -->
          <div v-if="showFilters" class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Status
              </label>
              <select
                v-model="selectedStatus"
                class="w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white"
              >
                <option value="">All Statuses</option>
                <option
                  v-for="(label, value) in filters.statuses"
                  :key="value"
                  :value="value"
                >
                  {{ label }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Size
              </label>
              <select
                v-model="selectedSize"
                class="w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white"
              >
                <option value="">All Sizes</option>
                <option
                  v-for="size in filters.sizes"
                  :key="size"
                  :value="size"
                >
                  {{ size }}
                </option>
              </select>
            </div>

            <div class="flex items-end">
              <Button
                variant="outline"
                @click="clearFilters"
                class="w-full"
              >
                Clear Filters
              </Button>
            </div>
          </div>
        </div>
      </Card>

      <!-- Billboards Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <Card
          v-for="billboard in billboards.data"
          :key="billboard.id"
          class="overflow-hidden"
        >
          <!-- Billboard Image -->
          <div class="aspect-video bg-gray-100 dark:bg-gray-800">
            <img
              v-if="billboard.image_url"
              :src="billboard.image_url"
              :alt="billboard.title"
              class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full flex items-center justify-center">
              <Building2 class="w-12 h-12 text-gray-400" />
            </div>
          </div>

          <!-- Billboard Info -->
          <div class="p-6">
            <div class="flex items-start justify-between mb-3">
              <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
                  {{ billboard.title }}
                </h3>
                <div class="flex items-center text-sm text-gray-600 dark:text-gray-400 mt-1">
                  <MapPin class="w-4 h-4 mr-1" />
                  {{ billboard.location }}
                </div>
              </div>
              <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="getStatusColor(billboard.availability_status)"
              >
                {{ billboard.availability_status }}
              </span>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
              {{ billboard.description }}
            </p>

            <!-- Metrics -->
            <div class="space-y-2 mb-4">
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Price per day</span>
                <span class="font-medium text-gray-900 dark:text-white">
                  {{ formatPrice(billboard.price_per_day) }}
                </span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Size</span>
                <span class="font-medium text-gray-900 dark:text-white">
                  {{ billboard.size }}
                </span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Active bookings</span>
                <span class="font-medium text-gray-900 dark:text-white">
                  {{ billboard.bookings_count }}
                </span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Visibility</span>
                <div class="flex items-center">
                  <div
                    v-for="(filled, index) in renderStars(billboard.visibility_rating)"
                    :key="`vis-${index}`"
                    class="w-3 h-3 mr-0.5"
                    :class="filled ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600'"
                  >
                    ★
                  </div>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
              <div class="flex space-x-2">
                <Button
                  size="sm"
                  variant="outline"
                  :href="route('tenant.manage.billboards.show', { tenant: tenant.uuid, billboard: billboard.id })"
                >
                  <Eye class="w-4 h-4 mr-1" />
                  View
                </Button>
                <Button
                  size="sm"
                  variant="outline"
                  :href="route('tenant.manage.billboards.edit', { tenant: tenant.uuid, billboard: billboard.id })"
                >
                  <Edit class="w-4 h-4 mr-1" />
                  Edit
                </Button>
              </div>
              <button
                @click="deleteBillboard(billboard)"
                class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
          </div>
        </Card>
      </div>

      <!-- Pagination -->
      <div v-if="billboards.last_page > 1" class="flex items-center justify-between">
        <div class="text-sm text-gray-700 dark:text-gray-300">
          Showing {{ ((billboards.current_page - 1) * billboards.per_page) + 1 }} to
          {{ Math.min(billboards.current_page * billboards.per_page, billboards.total) }} of
          {{ billboards.total }} results
        </div>

        <div class="flex space-x-1">
          <Link
            v-for="link in billboards.links"
            :key="link.label"
            :href="link.url || '#'"
            class="relative inline-flex items-center px-4 py-2 text-sm font-medium border rounded-md"
            :class="[
              link.active
                ? 'z-10 bg-indigo-50 dark:bg-indigo-900/50 border-indigo-500 text-indigo-600 dark:text-indigo-400'
                : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700',
              !link.url && 'opacity-50 cursor-not-allowed'
            ]"
            :disabled="!link.url"
            v-html="link.label"
          />
        </div>
      </div>
    </div>
  </TenantLayout>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>