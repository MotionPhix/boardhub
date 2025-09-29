<template>
  <TenantLayout title="General Settings">
    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div class="flex justify-between items-center mb-6">
              <h1 class="text-2xl font-bold">General Settings</h1>
              <Link :href="route('tenant.manage.settings.index')" class="text-blue-600 hover:text-blue-800">
                ← Back to Settings
              </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
              <!-- Organization Name -->
              <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Organization Name</label>
                <input
                  id="name"
                  v-model="form.name"
                  type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  required
                >
                <div v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</div>
              </div>

              <!-- Description -->
              <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea
                  id="description"
                  v-model="form.description"
                  rows="3"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                ></textarea>
                <div v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description }}</div>
              </div>

              <!-- Website -->
              <div>
                <label for="website" class="block text-sm font-medium text-gray-700">Website</label>
                <input
                  id="website"
                  v-model="form.website"
                  type="url"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                <div v-if="errors.website" class="mt-1 text-sm text-red-600">{{ errors.website }}</div>
              </div>

              <!-- Phone -->
              <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                <input
                  id="phone"
                  v-model="form.phone"
                  type="tel"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                <div v-if="errors.phone" class="mt-1 text-sm text-red-600">{{ errors.phone }}</div>
              </div>

              <!-- Contact Information -->
              <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Contact Information</h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <div>
                    <label for="contact_info.address" class="block text-sm font-medium text-gray-700">Address</label>
                    <input
                      id="contact_info.address"
                      v-model="form.contact_info.address"
                      type="text"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                  </div>

                  <div>
                    <label for="contact_info.city" class="block text-sm font-medium text-gray-700">City</label>
                    <input
                      id="contact_info.city"
                      v-model="form.contact_info.city"
                      type="text"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                  </div>

                  <div>
                    <label for="contact_info.country" class="block text-sm font-medium text-gray-700">Country</label>
                    <select
                      id="contact_info.country"
                      v-model="form.contact_info.country"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                      <option value="">Select Country</option>
                      <option v-for="(country, code) in countries" :key="code" :value="code">{{ country }}</option>
                    </select>
                  </div>

                  <div>
                    <label for="contact_info.postal_code" class="block text-sm font-medium text-gray-700">Postal Code</label>
                    <input
                      id="contact_info.postal_code"
                      v-model="form.contact_info.postal_code"
                      type="text"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                  </div>
                </div>
              </div>

              <!-- Submit Button -->
              <div class="flex justify-end">
                <button
                  type="submit"
                  :disabled="form.processing"
                  class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                >
                  <span v-if="form.processing">Saving...</span>
                  <span v-else>Save Changes</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </TenantLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  tenant: Object,
  settings: Object,
  timezones: Object,
  currencies: Object,
  countries: Object,
  errors: Object
})

const form = useForm({
  name: props.settings.name || '',
  description: props.settings.description || '',
  website: props.settings.website || '',
  phone: props.settings.phone || '',
  timezone: props.settings.timezone || 'UTC',
  currency: props.settings.currency || 'USD',
  language: props.settings.language || 'en',
  contact_info: {
    address: props.settings.contact_info?.address || '',
    city: props.settings.contact_info?.city || '',
    state: props.settings.contact_info?.state || '',
    country: props.settings.contact_info?.country || '',
    postal_code: props.settings.contact_info?.postal_code || ''
  }
})

const submit = () => {
  form.post(route('tenant.manage.settings.general'))
}
</script>