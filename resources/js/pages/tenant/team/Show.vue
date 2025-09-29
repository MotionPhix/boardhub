<template>
  <TenantLayout title="Team Member Details">
    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div class="flex justify-between items-center mb-6">
              <h1 class="text-2xl font-bold">Team Member Details</h1>
              <Link :href="route('tenant.manage.team.index')" class="text-blue-600 hover:text-blue-800">
                ← Back to Team
              </Link>
            </div>

            <!-- Member Profile -->
            <div class="bg-gray-50 p-6 rounded-lg mb-6">
              <div class="flex items-center space-x-6">
                <div class="h-20 w-20 rounded-full bg-gray-300 flex items-center justify-center">
                  <span class="text-2xl font-bold text-gray-700">
                    {{ member.user.name.charAt(0).toUpperCase() }}
                  </span>
                </div>
                <div class="flex-1">
                  <h2 class="text-xl font-bold">{{ member.user.name }}</h2>
                  <p class="text-gray-600">{{ member.user.email }}</p>
                  <div class="flex items-center space-x-4 mt-2">
                    <span :class="[
                      'px-3 py-1 text-sm font-semibold rounded-full',
                      member.role === 'owner' ? 'bg-purple-100 text-purple-800' :
                      member.role === 'admin' ? 'bg-blue-100 text-blue-800' :
                      member.role === 'manager' ? 'bg-green-100 text-green-800' :
                      'bg-gray-100 text-gray-800'
                    ]">
                      {{ member.role }}
                    </span>
                    <span :class="[
                      'px-3 py-1 text-sm font-semibold rounded-full',
                      member.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                    ]">
                      {{ member.status }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Member Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
              <div class="bg-blue-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-blue-600">{{ member.joined_at }}</div>
                <div class="text-sm text-gray-600">Joined</div>
              </div>
              <div class="bg-green-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-green-600">{{ member.last_accessed_at || 'Never' }}</div>
                <div class="text-sm text-gray-600">Last Access</div>
              </div>
              <div class="bg-purple-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-purple-600">{{ recentActivity.length }}</div>
                <div class="text-sm text-gray-600">Recent Actions</div>
              </div>
            </div>

            <!-- Role Management -->
            <div class="mb-6">
              <h3 class="text-lg font-semibold mb-4">Role Management</h3>
              <div class="bg-gray-50 p-4 rounded-lg">
                <form @submit.prevent="updateRole" class="flex items-center space-x-4">
                  <select v-model="roleForm.role" class="rounded-md border-gray-300">
                    <option value="owner">Owner</option>
                    <option value="admin">Admin</option>
                    <option value="manager">Manager</option>
                    <option value="member">Member</option>
                    <option value="viewer">Viewer</option>
                  </select>
                  <button
                    type="submit"
                    :disabled="roleForm.processing || roleForm.role === member.role"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                  >
                    Update Role
                  </button>
                </form>
              </div>
            </div>

            <!-- Recent Activity -->
            <div class="mb-6">
              <h3 class="text-lg font-semibold mb-4">Recent Activity</h3>
              <div class="space-y-3">
                <div v-for="activity in recentActivity" :key="activity.timestamp"
                     class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                  <div>
                    <div class="font-medium">{{ activity.action }}</div>
                    <div v-if="activity.target" class="text-sm text-gray-600">{{ activity.target }}</div>
                  </div>
                  <div class="text-sm text-gray-500">{{ activity.timestamp }}</div>
                </div>
              </div>
              <div v-if="recentActivity.length === 0" class="text-gray-600 text-center py-8">
                No recent activity
              </div>
            </div>

            <!-- Actions -->
            <div class="flex space-x-3">
              <form @submit.prevent="updateStatus" class="inline">
                <button
                  v-if="member.status === 'active'"
                  type="submit"
                  class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700"
                  @click="statusForm.status = 'suspended'"
                >
                  Suspend Member
                </button>
                <button
                  v-else
                  type="submit"
                  class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
                  @click="statusForm.status = 'active'"
                >
                  Activate Member
                </button>
              </form>

              <button
                v-if="member.role !== 'owner'"
                @click="confirmRemoval"
                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
              >
                Remove Member
              </button>
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
  member: Object,
  recentActivity: Array
})

const roleForm = useForm({
  role: props.member.role
})

const statusForm = useForm({
  status: props.member.status,
  reason: ''
})

const updateRole = () => {
  roleForm.put(route('tenant.manage.team.update-role', props.member.user.id))
}

const updateStatus = () => {
  statusForm.put(route('tenant.manage.team.update-status', props.member.user.id))
}

const confirmRemoval = () => {
  if (confirm('Are you sure you want to remove this member from the organization?')) {
    useForm({}).delete(route('tenant.manage.team.remove', props.member.user.id))
  }
}
</script>