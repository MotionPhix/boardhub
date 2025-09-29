<template>
  <TenantLayout title="Team Permissions">
    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div class="flex justify-between items-center mb-6">
              <h1 class="text-2xl font-bold">Team Permissions</h1>
              <Link :href="route('tenant.manage.team.index')" class="text-blue-600 hover:text-blue-800">
                ← Back to Team
              </Link>
            </div>

            <div class="space-y-6">
              <div v-for="(role, key) in roles" :key="key" class="border rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                  <h2 class="text-xl font-semibold capitalize">{{ key }}</h2>
                  <span :class="[
                    'px-3 py-1 text-sm font-semibold rounded-full',
                    key === 'owner' ? 'bg-purple-100 text-purple-800' :
                    key === 'admin' ? 'bg-blue-100 text-blue-800' :
                    key === 'manager' ? 'bg-green-100 text-green-800' :
                    'bg-gray-100 text-gray-800'
                  ]">
                    {{ key }}
                  </span>
                </div>

                <p class="text-gray-600 mb-4">{{ role.description }}</p>

                <div>
                  <h3 class="font-medium mb-2">Permissions:</h3>
                  <ul class="space-y-1">
                    <li v-for="permission in role.permissions" :key="permission" class="flex items-center text-sm">
                      <svg class="h-4 w-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                      </svg>
                      {{ permission }}
                    </li>
                  </ul>
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
import TenantLayout from '@/Layouts/TenantLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  tenant: Object,
  roles: Object
})
</script>