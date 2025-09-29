<template>
  <TenantLayout title="Settings">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <h1 class="text-2xl font-bold mb-6">Organization Settings</h1>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- General Settings -->
              <div class="bg-gray-50 p-6 rounded-lg">
                <h3 class="text-lg font-semibold mb-2">General</h3>
                <p class="text-gray-600 mb-4">Basic organization information and preferences</p>
                <Link :href="route('tenant.manage.settings.general')" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                  Manage
                </Link>
              </div>

              <!-- Branding -->
              <div class="bg-gray-50 p-6 rounded-lg">
                <h3 class="text-lg font-semibold mb-2">Branding</h3>
                <p class="text-gray-600 mb-4">Customize your organization's appearance</p>
                <Link :href="route('tenant.manage.settings.branding')" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                  Manage
                </Link>
              </div>

              <!-- Billing -->
              <div class="bg-gray-50 p-6 rounded-lg">
                <h3 class="text-lg font-semibold mb-2">Billing</h3>
                <p class="text-gray-600 mb-4">Subscription and payment information</p>
                <Link :href="route('tenant.manage.settings.billing')" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                  Manage
                </Link>
              </div>

              <!-- Integrations -->
              <div class="bg-gray-50 p-6 rounded-lg">
                <h3 class="text-lg font-semibold mb-2">Integrations</h3>
                <p class="text-gray-600 mb-4">Connect with third-party services</p>
                <Link :href="route('tenant.manage.settings.integrations')" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                  Manage
                </Link>
              </div>

              <!-- Notifications -->
              <div class="bg-gray-50 p-6 rounded-lg">
                <h3 class="text-lg font-semibold mb-2">Notifications</h3>
                <p class="text-gray-600 mb-4">Configure notification preferences</p>
                <Link :href="route('tenant.manage.settings.notifications')" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                  Manage
                </Link>
              </div>

              <!-- Security -->
              <div class="bg-gray-50 p-6 rounded-lg">
                <h3 class="text-lg font-semibold mb-2">Security</h3>
                <p class="text-gray-600 mb-4">Security and access control settings</p>
                <Link :href="route('tenant.manage.settings.security')" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                  Manage
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </TenantLayout>
</template>

<script setup>
import TenantLayout from '@/Layouts/TenantLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  tenant: Object,
  settings: Object,
  integrations: Object,
  notifications: Object
})
</script>