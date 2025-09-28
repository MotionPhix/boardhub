<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import SystemLayout from '../../../layouts/SystemLayout.vue'
import Dropdown from '../../../components/ui/Dropdown.vue'
import DropdownItem from '../../../components/ui/DropdownItem.vue'
import {
  MoreHorizontal,
  Eye,
  Edit,
  Users,
  Pause,
  Play,
  BarChart,
  Trash2
} from 'lucide-vue-next'

interface Props {
  tenants: {
    data: Array<{
      uuid: string
      name: string
      slug: string
      is_active: boolean
      created_at: string
      users_count: number
    }>
    links: any
    meta: any
  }
  filters: {
    search?: string
    status?: string
  }
}

const props = defineProps<Props>()

// Action handlers
const viewTenant = (uuid: string) => {
  console.log('Viewing tenant details:', uuid)
  // TODO: Navigate to tenant details page
  // router.visit(`/system/tenants/${uuid}`)
}

const editTenant = (uuid: string) => {
  console.log('Editing tenant:', uuid)
  // TODO: Open edit modal or navigate to edit page
}

const manageUsers = (uuid: string) => {
  console.log('Managing users for tenant:', uuid)
  // TODO: Navigate to user management for this tenant
}

const suspendTenant = (uuid: string) => {
  console.log('Suspending tenant:', uuid)
  // TODO: Show confirmation dialog and make API call
  if (confirm('Are you sure you want to suspend this tenant?')) {
    // Make API call to suspend tenant
  }
}

const activateTenant = (uuid: string) => {
  console.log('Activating tenant:', uuid)
  // TODO: Show confirmation dialog and make API call
  if (confirm('Are you sure you want to activate this tenant?')) {
    // Make API call to activate tenant
  }
}

const viewAnalytics = (uuid: string) => {
  console.log('Viewing analytics for tenant:', uuid)
  // TODO: Navigate to analytics page for this tenant
}

const deleteTenant = (uuid: string) => {
  console.log('Deleting tenant:', uuid)
  // TODO: Show confirmation dialog and make API call
  if (confirm('Are you sure you want to delete this tenant? This action cannot be undone.')) {
    // Make API call to delete tenant
  }
}
</script>

<template>
  <SystemLayout>
    <Head title="Tenant Management" />

    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between">
          <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:text-3xl sm:truncate">
              Tenant Management
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Manage system tenants and organizations
            </p>
          </div>
          <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
            <button
              class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Export Data
            </button>
            <button
              class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Add Tenant
            </button>
          </div>
        </div>

        <!-- Tenants List -->
        <div class="mt-8">
          <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
              <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                All Tenants
              </h3>
              <div class="mt-5">
                <div v-if="tenants.data.length === 0" class="text-center py-12">
                  <p class="text-gray-500 dark:text-gray-400">No tenants found</p>
                </div>
                <div v-else class="flow-root">
                  <ul class="-my-4 divide-y divide-gray-200 dark:divide-gray-700">
                    <li v-for="tenant in tenants.data" :key="tenant.uuid" class="py-4">
                      <div class="flex items-center space-x-4">
                        <div class="flex-1 min-w-0">
                          <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ tenant.name }}
                          </p>
                          <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                            {{ tenant.slug }} • {{ tenant.users_count }} users • Created {{ tenant.created_at }}
                          </p>
                        </div>
                        <div class="flex items-center space-x-3">
                          <span :class="{
                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium': true,
                            'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100': tenant.is_active,
                            'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100': !tenant.is_active
                          }">
                            {{ tenant.is_active ? 'Active' : 'Inactive' }}
                          </span>

                          <!-- Actions Dropdown -->
                          <Dropdown variant="ghost" position="right" width="sm">
                            <template #trigger>
                              <MoreHorizontal class="w-5 h-5" />
                            </template>

                            <DropdownItem
                              label="View Details"
                              :icon="Eye"
                              @click="() => viewTenant(tenant.uuid)"
                            />

                            <DropdownItem
                              label="Edit Settings"
                              :icon="Edit"
                              @click="() => editTenant(tenant.uuid)"
                            />

                            <DropdownItem
                              label="Manage Users"
                              :icon="Users"
                              @click="() => manageUsers(tenant.uuid)"
                            />

                            <div class="border-t border-gray-100 dark:border-gray-600 my-1"></div>

                            <DropdownItem
                              v-if="tenant.is_active"
                              label="Suspend Tenant"
                              :icon="Pause"
                              @click="() => suspendTenant(tenant.uuid)"
                            />

                            <DropdownItem
                              v-else
                              label="Activate Tenant"
                              :icon="Play"
                              @click="() => activateTenant(tenant.uuid)"
                            />

                            <DropdownItem
                              label="View Analytics"
                              :icon="BarChart"
                              @click="() => viewAnalytics(tenant.uuid)"
                            />

                            <div class="border-t border-gray-100 dark:border-gray-600 my-1"></div>

                            <DropdownItem
                              label="Delete Tenant"
                              :icon="Trash2"
                              destructive
                              @click="() => deleteTenant(tenant.uuid)"
                            />
                          </Dropdown>
                        </div>
                      </div>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </SystemLayout>
</template>