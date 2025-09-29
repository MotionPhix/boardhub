<template>
  <TenantLayout title="Integrations">
    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div class="flex justify-between items-center mb-6">
              <h1 class="text-2xl font-bold">Integrations</h1>
              <Link :href="route('tenant.manage.settings.index')" class="text-blue-600 hover:text-blue-800">
                ← Back to Settings
              </Link>
            </div>

            <div class="space-y-4">
              <div v-for="(integration, key) in availableIntegrations" :key="key"
                   class="border rounded-lg p-6 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                  <div class="h-12 w-12 bg-gray-200 rounded-lg flex items-center justify-center">
                    <span class="text-gray-600 font-semibold">{{ integration.icon || '🔧' }}</span>
                  </div>
                  <div>
                    <h3 class="text-lg font-semibold">{{ integration.name }}</h3>
                    <p class="text-gray-600 text-sm">{{ integration.description }}</p>
                    <span class="inline-block mt-1 px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded">
                      {{ integration.category }}
                    </span>
                  </div>
                </div>
                <div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input
                      type="checkbox"
                      :checked="integrations[key]?.enabled || false"
                      @change="toggleIntegration(key)"
                      class="sr-only peer"
                    >
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                  </label>
                </div>
              </div>
            </div>
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
  integrations: Object,
  availableIntegrations: Object
})

const form = useForm({
  integrations: props.integrations
})

const toggleIntegration = (key) => {
  form.integrations = {
    ...form.integrations,
    [key]: {
      ...form.integrations[key],
      enabled: !form.integrations[key]?.enabled
    }
  }

  form.post(route('tenant.manage.settings.integrations'))
}
</script>