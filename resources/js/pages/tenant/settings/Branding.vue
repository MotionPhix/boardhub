<template>
  <TenantLayout title="Branding Settings">
    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div class="flex justify-between items-center mb-6">
              <h1 class="text-2xl font-bold">Branding Settings</h1>
              <Link :href="route('tenant.manage.settings.index')" class="text-blue-600 hover:text-blue-800">
                ← Back to Settings
              </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
              <!-- Logo Upload -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Organization Logo</label>
                <div class="flex items-center space-x-4">
                  <div class="h-16 w-16 rounded-lg bg-gray-200 flex items-center justify-center">
                    <img v-if="settings.logo_url" :src="settings.logo_url" class="h-16 w-16 rounded-lg object-cover">
                    <span v-else class="text-gray-400 text-xs">No Logo</span>
                  </div>
                  <div>
                    <input type="file" @change="handleLogoUpload" accept="image/*" class="hidden" ref="logoInput">
                    <button type="button" @click="$refs.logoInput.click()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                      Upload Logo
                    </button>
                  </div>
                </div>
              </div>

              <!-- Colors -->
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label for="primary_color" class="block text-sm font-medium text-gray-700">Primary Color</label>
                  <div class="flex items-center space-x-2 mt-1">
                    <input
                      id="primary_color"
                      v-model="form.primary_color"
                      type="color"
                      class="h-10 w-20 rounded border border-gray-300"
                    >
                    <input
                      v-model="form.primary_color"
                      type="text"
                      class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                  </div>
                </div>

                <div>
                  <label for="secondary_color" class="block text-sm font-medium text-gray-700">Secondary Color</label>
                  <div class="flex items-center space-x-2 mt-1">
                    <input
                      id="secondary_color"
                      v-model="form.secondary_color"
                      type="color"
                      class="h-10 w-20 rounded border border-gray-300"
                    >
                    <input
                      v-model="form.secondary_color"
                      type="text"
                      class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                  </div>
                </div>
              </div>

              <!-- Preview -->
              <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Preview</h3>
                <div class="p-4 border rounded-lg" :style="`background-color: ${form.primary_color}20`">
                  <div class="flex items-center space-x-3">
                    <div class="h-8 w-8 rounded" :style="`background-color: ${form.primary_color}`"></div>
                    <div>
                      <div class="text-lg font-semibold" :style="`color: ${form.primary_color}`">{{ tenant.name }}</div>
                      <div class="text-sm" :style="`color: ${form.secondary_color}`">Your organization branding</div>
                    </div>
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
  colorPresets: Object
})

const form = useForm({
  logo_url: props.settings.logo_url || '',
  primary_color: props.settings.primary_color || '#6366f1',
  secondary_color: props.settings.secondary_color || '#8b5cf6',
  accent_color: props.settings.accent_color || '#06b6d4',
  font_family: props.settings.font_family || 'Inter',
  branding_settings: props.settings.branding_settings || {}
})

const handleLogoUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    form.logo = file
  }
}

const submit = () => {
  form.post(route('tenant.manage.settings.branding'))
}
</script>