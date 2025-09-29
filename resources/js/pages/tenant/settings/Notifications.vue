<template>
  <TenantLayout title="Notification Settings">
    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div class="flex justify-between items-center mb-6">
              <h1 class="text-2xl font-bold">Notification Settings</h1>
              <Link :href="route('tenant.manage.settings.index')" class="text-blue-600 hover:text-blue-800">
                ← Back to Settings
              </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
              <!-- Notification Channels -->
              <div>
                <h2 class="text-lg font-semibold mb-4">Notification Channels</h2>
                <div class="space-y-4">
                  <div v-for="(channel, key) in channels" :key="key" class="flex items-center justify-between p-4 border rounded-lg">
                    <div class="flex items-center space-x-3">
                      <div class="text-xl">{{ channel.icon }}</div>
                      <div>
                        <div class="font-medium">{{ channel.name }}</div>
                        <div class="text-sm text-gray-600">{{ channel.description }}</div>
                      </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                      <input
                        type="checkbox"
                        v-model="form[`${key}_enabled`]"
                        class="sr-only peer"
                      >
                      <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                  </div>
                </div>
              </div>

              <!-- Notification Types -->
              <div>
                <h2 class="text-lg font-semibold mb-4">Notification Types</h2>
                <div class="space-y-3">
                  <div class="flex items-center justify-between">
                    <div>
                      <div class="font-medium">Booking Notifications</div>
                      <div class="text-sm text-gray-600">New bookings, confirmations, and cancellations</div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                      <input type="checkbox" v-model="form.booking_notifications" class="sr-only peer">
                      <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                  </div>

                  <div class="flex items-center justify-between">
                    <div>
                      <div class="font-medium">Payment Notifications</div>
                      <div class="text-sm text-gray-600">Payment confirmations and failed payments</div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                      <input type="checkbox" v-model="form.payment_notifications" class="sr-only peer">
                      <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                  </div>

                  <div class="flex items-center justify-between">
                    <div>
                      <div class="font-medium">Team Notifications</div>
                      <div class="text-sm text-gray-600">Team member invitations and role changes</div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                      <input type="checkbox" v-model="form.team_notifications" class="sr-only peer">
                      <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                  </div>

                  <div class="flex items-center justify-between">
                    <div>
                      <div class="font-medium">Marketing Notifications</div>
                      <div class="text-sm text-gray-600">Product updates and promotional content</div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                      <input type="checkbox" v-model="form.marketing_notifications" class="sr-only peer">
                      <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
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
  channels: Object
})

const form = useForm({
  email_enabled: props.settings.email_enabled || false,
  sms_enabled: props.settings.sms_enabled || false,
  slack_enabled: props.settings.slack_enabled || false,
  booking_notifications: props.settings.booking_notifications || false,
  payment_notifications: props.settings.payment_notifications || false,
  team_notifications: props.settings.team_notifications || false,
  maintenance_notifications: props.settings.maintenance_notifications || false,
  marketing_notifications: props.settings.marketing_notifications || false
})

const submit = () => {
  form.post(route('tenant.manage.settings.notifications'))
}
</script>