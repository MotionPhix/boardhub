<template>
  <AdminLayout>
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold leading-6 text-gray-900">Users</h1>
        <p class="mt-2 text-sm text-gray-700">
          Manage platform users, permissions, and security settings.
        </p>
      </div>
      <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
        <Link
          href="/admin/users/create"
          class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
        >
          Add User
        </Link>
      </div>
    </div>

    <!-- Filters -->
    <div class="mt-6 bg-white shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
          <div>
            <label for="search" class="block text-sm font-medium leading-6 text-gray-900">Search</label>
            <input
              v-model="filters.search"
              type="text"
              name="search"
              id="search"
              placeholder="Name or email..."
              class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
              @input="search"
            />
          </div>
          <div>
            <label for="role" class="block text-sm font-medium leading-6 text-gray-900">Role</label>
            <select
              v-model="filters.role"
              name="role"
              id="role"
              class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
              @change="search"
            >
              <option value="">All Roles</option>
              <option value="super-admin">Super Admin</option>
              <option value="admin">Admin</option>
              <option value="manager">Manager</option>
              <option value="agent">Agent</option>
              <option value="client">Client</option>
            </select>
          </div>
          <div>
            <label for="status" class="block text-sm font-medium leading-6 text-gray-900">Status</label>
            <select
              v-model="filters.status"
              name="status"
              id="status"
              class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
              @change="search"
            >
              <option value="">All Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="suspended">Suspended</option>
            </select>
          </div>
          <div class="flex items-end">
            <button
              type="button"
              @click="clearFilters"
              class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
            >
              Clear Filters
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Users Table -->
    <div class="mt-6 flow-root">
      <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
          <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                    User
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                    Role
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                    Status
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                    Last Active
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                    2FA
                  </th>
                  <th scope="col" class="relative px-6 py-3">
                    <span class="sr-only">Actions</span>
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 bg-white">
                <tr v-for="user in users.data" :key="user.id">
                  <td class="whitespace-nowrap px-6 py-4">
                    <div class="flex items-center">
                      <div class="h-10 w-10 flex-shrink-0">
                        <div class="h-10 w-10 rounded-full bg-indigo-600 flex items-center justify-center">
                          <span class="text-sm font-medium text-white">{{ getUserInitials(user.name) }}</span>
                        </div>
                      </div>
                      <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                        <div class="text-sm text-gray-500">{{ user.email }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="whitespace-nowrap px-6 py-4">
                    <span :class="getRoleBadgeClass(user.roles?.[0]?.name)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                      {{ user.roles?.[0]?.name || 'No Role' }}
                    </span>
                  </td>
                  <td class="whitespace-nowrap px-6 py-4">
                    <span :class="getStatusBadgeClass(user.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                      {{ user.status }}
                    </span>
                  </td>
                  <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                    {{ formatDate(user.last_activity_at) }}
                  </td>
                  <td class="whitespace-nowrap px-6 py-4">
                    <span v-if="user.two_factor_enabled" class="text-green-600">
                      <CheckCircle class="h-5 w-5" />
                    </span>
                    <span v-else class="text-gray-400">
                      <XCircle class="h-5 w-5" />
                    </span>
                  </td>
                  <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                    <div class="flex items-center justify-end space-x-2">
                      <Link
                        :href="`/admin/users/${user.id}`"
                        class="text-indigo-600 hover:text-indigo-900"
                      >
                        View
                      </Link>
                      <Link
                        :href="`/admin/users/${user.id}/edit`"
                        class="text-indigo-600 hover:text-indigo-900"
                      >
                        Edit
                      </Link>
                      <button
                        @click="confirmDelete(user)"
                        class="text-red-600 hover:text-red-900"
                      >
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="users.last_page > 1" class="mt-6">
      <Pagination :links="users.links" />
    </div>

    <!-- Delete Confirmation Modal -->
    <ConfirmDialog
      :show="showDeleteModal"
      title="Delete User"
      :message="`Are you sure you want to delete ${userToDelete?.name}? This action cannot be undone.`"
      @confirm="deleteUser"
      @cancel="showDeleteModal = false"
    />
  </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import Pagination from '@/components/Pagination.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { CheckCircle, XCircle } from 'lucide-vue-next'

const props = defineProps({
  users: Object,
  filters: Object
})

const filters = reactive({
  search: props.filters?.search || '',
  role: props.filters?.role || '',
  status: props.filters?.status || ''
})

const showDeleteModal = ref(false)
const userToDelete = ref(null)

const search = () => {
  router.get('/admin/users', filters, {
    preserveState: true,
    replace: true
  })
}

const clearFilters = () => {
  filters.search = ''
  filters.role = ''
  filters.status = ''
  search()
}

const getUserInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const getRoleBadgeClass = (role) => {
  const classes = {
    'super-admin': 'bg-purple-100 text-purple-800',
    'admin': 'bg-red-100 text-red-800',
    'manager': 'bg-blue-100 text-blue-800',
    'agent': 'bg-green-100 text-green-800',
    'client': 'bg-gray-100 text-gray-800'
  }
  return classes[role] || 'bg-gray-100 text-gray-800'
}

const getStatusBadgeClass = (status) => {
  const classes = {
    'active': 'bg-green-100 text-green-800',
    'inactive': 'bg-gray-100 text-gray-800',
    'suspended': 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date) => {
  return date ? new Date(date).toLocaleDateString() : 'Never'
}

const confirmDelete = (user) => {
  userToDelete.value = user
  showDeleteModal.value = true
}

const deleteUser = () => {
  router.delete(`/admin/users/${userToDelete.value.id}`, {
    onSuccess: () => {
      showDeleteModal.value = false
      userToDelete.value = null
    }
  })
}
</script>