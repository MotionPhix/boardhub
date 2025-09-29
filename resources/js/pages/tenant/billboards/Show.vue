<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import TenantLayout from '@/layouts/TenantLayout.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import {
  MapPin,
  Eye,
  Calendar,
  DollarSign,
  Users,
  Clock,
  ArrowLeft,
  Star,
  Building2,
  Ruler,
  Info
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
  latitude: number
  longitude: number
  price_per_day: number
  size: string
  visibility_rating: number
  traffic_rating: number
  available_from: string
  available_to: string
  image_url?: string
  images: string[]
  features: string[]
  owner: {
    name: string
    contact_email: string
    phone?: string
  }
  availability_status: 'available' | 'booked' | 'unavailable'
  impressions_per_day?: number
}

interface Props {
  tenant: Tenant
  billboard: Billboard
}

const props = defineProps<Props>()

const formattedPrice = computed(() => {
  return new Intl.NumberFormat('en-ZM', {
    style: 'currency',
    currency: 'ZMW'
  }).format(props.billboard.price_per_day)
})

const availabilityColor = computed(() => {
  switch (props.billboard.availability_status) {
    case 'available':
      return 'text-green-600 dark:text-green-400'
    case 'booked':
      return 'text-red-600 dark:text-red-400'
    case 'unavailable':
      return 'text-gray-600 dark:text-gray-400'
    default:
      return 'text-gray-600 dark:text-gray-400'
  }
})

const availabilityText = computed(() => {
  switch (props.billboard.availability_status) {
    case 'available':
      return 'Available'
    case 'booked':
      return 'Currently Booked'
    case 'unavailable':
      return 'Unavailable'
    default:
      return 'Unknown'
  }
})

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const renderStars = (rating: number) => {
  return Array.from({ length: 5 }, (_, i) => i < rating)
}
</script>

<template>
  <TenantLayout>
    <Head :title="`${billboard.title} - ${tenant.name}`" />

    <div class="max-w-6xl mx-auto">
      <!-- Back Navigation -->
      <div class="mb-6">
        <Link
          :href="route('tenant.billboards.index', { tenant: tenant.uuid })"
          class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
        >
          <ArrowLeft class="w-4 h-4 mr-2" />
          Back to Billboards
        </Link>
      </div>

      <!-- Billboard Header -->
      <div class="mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
          <div class="mb-4 lg:mb-0">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
              {{ billboard.title }}
            </h1>
            <div class="flex items-center mt-2 text-gray-600 dark:text-gray-400">
              <MapPin class="w-4 h-4 mr-1" />
              {{ billboard.location }}
            </div>
          </div>
          <div class="flex flex-col items-end">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ formattedPrice }}/day
            </div>
            <div class="flex items-center mt-1" :class="availabilityColor">
              <div class="w-2 h-2 rounded-full mr-2"
                   :class="{
                     'bg-green-500': billboard.availability_status === 'available',
                     'bg-red-500': billboard.availability_status === 'booked',
                     'bg-gray-500': billboard.availability_status === 'unavailable'
                   }">
              </div>
              {{ availabilityText }}
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Images -->
          <Card class="p-0 overflow-hidden">
            <div v-if="billboard.image_url || billboard.images.length > 0" class="aspect-video bg-gray-100 dark:bg-gray-800">
              <img
                :src="billboard.image_url || billboard.images[0]"
                :alt="billboard.title"
                class="w-full h-full object-cover"
              />
            </div>
            <div v-else class="aspect-video bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
              <div class="text-center text-gray-500 dark:text-gray-400">
                <Building2 class="w-12 h-12 mx-auto mb-2" />
                <p>No image available</p>
              </div>
            </div>

            <!-- Additional Images -->
            <div v-if="billboard.images.length > 1" class="p-4">
              <div class="grid grid-cols-4 gap-2">
                <img
                  v-for="(image, index) in billboard.images.slice(1, 5)"
                  :key="index"
                  :src="image"
                  :alt="`${billboard.title} view ${index + 2}`"
                  class="aspect-square object-cover rounded-lg cursor-pointer hover:opacity-80 transition-opacity"
                />
              </div>
            </div>
          </Card>

          <!-- Description -->
          <Card class="p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
              Description
            </h2>
            <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
              {{ billboard.description }}
            </p>
          </Card>

          <!-- Features -->
          <Card v-if="billboard.features.length > 0" class="p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
              Features
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div
                v-for="feature in billboard.features"
                :key="feature"
                class="flex items-center text-gray-600 dark:text-gray-400"
              >
                <div class="w-2 h-2 bg-indigo-500 rounded-full mr-3"></div>
                {{ feature }}
              </div>
            </div>
          </Card>

          <!-- Location & Map -->
          <Card class="p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
              Location
            </h2>
            <div class="flex items-center text-gray-600 dark:text-gray-400 mb-4">
              <MapPin class="w-5 h-5 mr-2" />
              {{ billboard.location }}
            </div>
            <div class="bg-gray-100 dark:bg-gray-800 rounded-lg h-64 flex items-center justify-center">
              <div class="text-center text-gray-500 dark:text-gray-400">
                <MapPin class="w-8 h-8 mx-auto mb-2" />
                <p>Map integration coming soon</p>
                <p class="text-sm">{{ billboard.latitude }}, {{ billboard.longitude }}</p>
              </div>
            </div>
          </Card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Quick Stats -->
          <Card class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
              Billboard Details
            </h3>
            <div class="space-y-4">
              <!-- Size -->
              <div class="flex items-center justify-between">
                <div class="flex items-center text-gray-600 dark:text-gray-400">
                  <Ruler class="w-4 h-4 mr-2" />
                  Size
                </div>
                <span class="font-medium text-gray-900 dark:text-white">
                  {{ billboard.size }}
                </span>
              </div>

              <!-- Visibility Rating -->
              <div class="flex items-center justify-between">
                <div class="flex items-center text-gray-600 dark:text-gray-400">
                  <Eye class="w-4 h-4 mr-2" />
                  Visibility
                </div>
                <div class="flex items-center">
                  <Star
                    v-for="(filled, index) in renderStars(billboard.visibility_rating)"
                    :key="`vis-${index}`"
                    class="w-4 h-4"
                    :class="filled ? 'text-yellow-400 fill-current' : 'text-gray-300 dark:text-gray-600'"
                  />
                </div>
              </div>

              <!-- Traffic Rating -->
              <div class="flex items-center justify-between">
                <div class="flex items-center text-gray-600 dark:text-gray-400">
                  <Users class="w-4 h-4 mr-2" />
                  Traffic
                </div>
                <div class="flex items-center">
                  <Star
                    v-for="(filled, index) in renderStars(billboard.traffic_rating)"
                    :key="`traffic-${index}`"
                    class="w-4 h-4"
                    :class="filled ? 'text-yellow-400 fill-current' : 'text-gray-300 dark:text-gray-600'"
                  />
                </div>
              </div>

              <!-- Daily Impressions -->
              <div v-if="billboard.impressions_per_day" class="flex items-center justify-between">
                <div class="flex items-center text-gray-600 dark:text-gray-400">
                  <Eye class="w-4 h-4 mr-2" />
                  Daily Impressions
                </div>
                <span class="font-medium text-gray-900 dark:text-white">
                  {{ billboard.impressions_per_day.toLocaleString() }}
                </span>
              </div>
            </div>
          </Card>

          <!-- Availability -->
          <Card class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
              Availability
            </h3>
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-gray-600 dark:text-gray-400">Available from</span>
                <span class="font-medium text-gray-900 dark:text-white">
                  {{ formatDate(billboard.available_from) }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-gray-600 dark:text-gray-400">Available to</span>
                <span class="font-medium text-gray-900 dark:text-white">
                  {{ formatDate(billboard.available_to) }}
                </span>
              </div>
              <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                  <span class="text-gray-600 dark:text-gray-400">Status</span>
                  <span class="font-medium" :class="availabilityColor">
                    {{ availabilityText }}
                  </span>
                </div>
              </div>
            </div>
          </Card>

          <!-- Owner Contact -->
          <Card class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
              Owner Contact
            </h3>
            <div class="space-y-3">
              <div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Name</div>
                <div class="font-medium text-gray-900 dark:text-white">
                  {{ billboard.owner.name }}
                </div>
              </div>
              <div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Email</div>
                <div class="font-medium text-gray-900 dark:text-white">
                  {{ billboard.owner.contact_email }}
                </div>
              </div>
              <div v-if="billboard.owner.phone">
                <div class="text-sm text-gray-600 dark:text-gray-400">Phone</div>
                <div class="font-medium text-gray-900 dark:text-white">
                  {{ billboard.owner.phone }}
                </div>
              </div>
            </div>
          </Card>

          <!-- Action Buttons -->
          <div class="space-y-3">
            <Button
              v-if="billboard.availability_status === 'available'"
              class="w-full"
              size="lg"
            >
              <Calendar class="w-4 h-4 mr-2" />
              Book This Billboard
            </Button>

            <Button
              v-else
              disabled
              variant="outline"
              class="w-full"
              size="lg"
            >
              <Clock class="w-4 h-4 mr-2" />
              {{ billboard.availability_status === 'booked' ? 'Currently Booked' : 'Not Available' }}
            </Button>

            <Button
              variant="outline"
              class="w-full"
            >
              <Info class="w-4 h-4 mr-2" />
              Contact Owner
            </Button>
          </div>
        </div>
      </div>
    </div>
  </TenantLayout>
</template>