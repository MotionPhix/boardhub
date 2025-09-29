<template>
  <TenantLayout title="Security Settings">
    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div class="flex justify-between items-center mb-6">
              <h1 class="text-2xl font-bold">Security Settings</h1>
              <Link :href="route('tenant.manage.settings.index')" class="text-blue-600 hover:text-blue-800">
                ← Back to Settings
              </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
              <!-- Two-Factor Authentication -->
              <div>
                <h2 class="text-lg font-semibold mb-4">Two-Factor Authentication</h2>
                <div class="bg-gray-50 p-4 rounded-lg">
                  <div class="flex items-center justify-between">
                    <div>
                      <div class="font-medium">Require 2FA for all team members</div>
                      <div class="text-sm text-gray-600">Enhance security by requiring two-factor authentication</div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                      <input type="checkbox" v-model="form.two_factor_required" class="sr-only peer">
                      <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                  </div>
                </div>
              </div>

              <!-- Session Management -->
              <div>
                <h2 class="text-lg font-semibold mb-4">Session Management</h2>
                <div class="space-y-4">
                  <div>
                    <label for="session_timeout" class="block text-sm font-medium text-gray-700">Session Timeout (minutes)</label>
                    <select
                      id="session_timeout"
                      v-model="form.session_timeout"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                      <option value="15">15 minutes</option>
                      <option value="30">30 minutes</option>
                      <option value="60">1 hour</option>
                      <option value="120">2 hours</option>
                      <option value="480">8 hours</option>
                      <option value="1440">24 hours</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Password Policy -->
              <div>
                <h2 class="text-lg font-semibold mb-4">Password Policy</h2>
                <div class="space-y-4">
                  <div>
                    <label for="min_length" class="block text-sm font-medium text-gray-700">Minimum Password Length</label>
                    <input
                      id="min_length"
                      v-model="form.password_policy.min_length"
                      type="number"
                      min="6"
                      max="50"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                  </div>

                  <div class="space-y-3">
                    <div class="flex items-center">
                      <input
                        id="require_uppercase"
                        v-model="form.password_policy.require_uppercase"
                        type="checkbox"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                      >
                      <label for="require_uppercase" class="ml-2 block text-sm text-gray-900">
                        Require uppercase letters
                      </label>
                    </div>

                    <div class="flex items-center">
                      <input
                        id="require_lowercase"
                        v-model="form.password_policy.require_lowercase"
                        type="checkbox"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                      >
                      <label for="require_lowercase" class="ml-2 block text-sm text-gray-900">
                        Require lowercase letters
                      </label>
                    </div>

                    <div class="flex items-center">
                      <input
                        id="require_numbers"
                        v-model="form.password_policy.require_numbers"
                        type="checkbox"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                      >
                      <label for="require_numbers" class="ml-2 block text-sm text-gray-900">
                        Require numbers
                      </label>
                    </div>

                    <div class="flex items-center">
                      <input
                        id="require_symbols"
                        v-model="form.password_policy.require_symbols"
                        type="checkbox"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                      >
                      <label for="require_symbols" class="ml-2 block text-sm text-gray-900">
                        Require special characters
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <!-- IP Restrictions -->
              <div>
                <h2 class="text-lg font-semibold mb-4">IP Restrictions</h2>
                <div>
                  <label for="ip_whitelist" class="block text-sm font-medium text-gray-700">Allowed IP Addresses</label>
                  <textarea
                    id="ip_whitelist"
                    v-model="ipWhitelistText"
                    rows="4"
                    placeholder="Enter IP addresses, one per line (e.g., 192.168.1.1 or 192.168.1.0/24)"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  ></textarea>
                  <p class="mt-1 text-sm text-gray-600">
                    Leave empty to allow access from any IP address. Be careful not to lock yourself out!
                  </p>
                </div>
              </div>

              <!-- Active Sessions -->
              <div v-if="sessions.length > 0">
                <h2 class="text-lg font-semibold mb-4">Active Sessions</h2>
                <div class="space-y-3">
                  <div v-for="session in sessions" :key="session.id"
                       class="flex items-center justify-between p-4 border rounded-lg">
                    <div>
                      <div class="font-medium">{{ session.device }}</div>
                      <div class="text-sm text-gray-600">{{ session.ip_address }} • Last active {{ session.last_activity }}</div>
                    </div>
                    <button
                      type="button"
                      class="text-red-600 hover:text-red-800 text-sm"
                      @click="revokeSession(session.id)"
                    >
                      Revoke
                    </button>
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
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  tenant: Object,
  settings: Object,
  sessions: Array,
  auditLogs: Array
})

const form = useForm({
  two_factor_required: props.settings.two_factor_required || false,
  session_timeout: props.settings.session_timeout || 60,
  password_policy: {
    min_length: props.settings.password_policy?.min_length || 8,
    require_uppercase: props.settings.password_policy?.require_uppercase || false,
    require_lowercase: props.settings.password_policy?.require_lowercase || false,
    require_numbers: props.settings.password_policy?.require_numbers || false,
    require_symbols: props.settings.password_policy?.require_symbols || false
  },
  ip_whitelist: props.settings.ip_whitelist || []
})

const ipWhitelistText = ref(
  (props.settings.ip_whitelist || []).join('\n')
)

const submit = () => {
  // Parse IP whitelist from textarea
  form.ip_whitelist = ipWhitelistText.value
    .split('\n')
    .map(ip => ip.trim())
    .filter(ip => ip.length > 0)

  form.post(route('tenant.manage.settings.security'))
}

const revokeSession = (sessionId) => {
  if (confirm('Are you sure you want to revoke this session?')) {
    // Implementation would go here
    console.log('Revoking session:', sessionId)
  }
}
</script>