<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import TenantLayout from '@/layouts/TenantLayout.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'
import InputError from '@/components/ui/InputError.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { Users, ArrowRight, ArrowLeft, Plus, Mail, UserCheck, Clock } from 'lucide-vue-next'

interface Tenant {
  id: number
  name: string
}

interface Member {
  id: number
  name: string
  email: string
  role: string
  status: string
  joined_at: string
}

interface PendingInvitation {
  id: number
  email: string
  role: string
  invited_at: string
}

interface Props {
  tenant: Tenant
  members: Member[]
  pending_invitations: PendingInvitation[]
}

const props = defineProps<Props>()

const showInviteForm = ref(false)

const inviteForm = useForm({
  email: '',
  role: 'member'
})

const skipForm = useForm({
  step: 'team_setup'
})

const sendInvite = () => {
  inviteForm.post(route('tenant.members.invite'), {
    onSuccess: () => {
      inviteForm.reset()
      showInviteForm.value = false
    }
  })
}

const skip = () => {
  skipForm.post(route('tenant.onboarding.skip'))
}

const continue_ = () => {
  window.location.href = route('tenant.onboarding.branding')
}

const getRoleBadgeColor = (role: string) => {
  const colors = {
    owner: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
    admin: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    member: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
  }
  return colors[role] || colors.member
}

const formatRole = (role: string) => {
  return role.charAt(0).toUpperCase() + role.slice(1)
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString()
}
</script>

<template>
  <TenantLayout>
    <Head :title="`Team Setup - ${tenant.name}`" />

    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-8">
        <div class="flex justify-center mb-4">
          <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center">
            <Users class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
          </div>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
          Set up your team
        </h1>
        <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">
          Invite team members to collaborate on billboard campaigns. You can skip this step and add members later.
        </p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Current Members -->
        <Card class="p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
              Team Members
            </h2>
            <span class="text-sm text-gray-500 dark:text-gray-400">
              {{ members.length }} member{{ members.length !== 1 ? 's' : '' }}
            </span>
          </div>

          <div v-if="members.length > 0" class="space-y-4">
            <div
              v-for="member in members"
              :key="member.id"
              class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg"
            >
              <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center">
                  <UserCheck class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                </div>
                <div>
                  <div class="font-medium text-gray-900 dark:text-white">
                    {{ member.name }}
                  </div>
                  <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ member.email }}
                  </div>
                </div>
              </div>
              <div class="text-right">
                <span
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="getRoleBadgeColor(member.role)"
                >
                  {{ formatRole(member.role) }}
                </span>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  Joined {{ formatDate(member.joined_at) }}
                </div>
              </div>
            </div>
          </div>

          <EmptyState
            v-else
            icon="users"
            title="No team members yet"
            description="You're the only member of this organization."
          />
        </Card>

        <!-- Invite Section -->
        <Card class="p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
              Invite Members
            </h2>
            <Button
              v-if="!showInviteForm"
              @click="showInviteForm = true"
              size="sm"
            >
              <Plus class="w-4 h-4 mr-2" />
              Invite
            </Button>
          </div>

          <!-- Invite Form -->
          <div v-if="showInviteForm" class="mb-6">
            <form @submit.prevent="sendInvite" class="space-y-4">
              <div>
                <Label for="email" required>Email Address</Label>
                <Input
                  id="email"
                  v-model="inviteForm.email"
                  type="email"
                  placeholder="colleague@example.com"
                  required
                />
                <InputError :message="inviteForm.errors.email" />
              </div>

              <div>
                <Label for="role">Role</Label>
                <select
                  id="role"
                  v-model="inviteForm.role"
                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white"
                >
                  <option value="member">Member</option>
                  <option value="admin">Admin</option>
                </select>
                <InputError :message="inviteForm.errors.role" />
              </div>

              <div class="flex items-center space-x-3">
                <Button
                  type="submit"
                  :disabled="inviteForm.processing"
                  :loading="inviteForm.processing"
                >
                  <Mail class="w-4 h-4 mr-2" />
                  Send Invite
                </Button>
                <Button
                  type="button"
                  variant="outline"
                  @click="showInviteForm = false"
                >
                  Cancel
                </Button>
              </div>
            </form>
          </div>

          <!-- Pending Invitations -->
          <div v-if="pending_invitations.length > 0">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
              Pending Invitations
            </h3>
            <div class="space-y-3">
              <div
                v-for="invitation in pending_invitations"
                :key="invitation.id"
                class="flex items-center justify-between p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg"
              >
                <div class="flex items-center space-x-3">
                  <Clock class="h-4 w-4 text-yellow-600 dark:text-yellow-400" />
                  <div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ invitation.email }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                      Invited {{ formatDate(invitation.invited_at) }}
                    </div>
                  </div>
                </div>
                <span
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                  :class="getRoleBadgeColor(invitation.role)"
                >
                  {{ formatRole(invitation.role) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Help Text -->
          <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <div class="flex">
              <div class="flex-shrink-0">
                <Mail class="h-5 w-5 text-blue-400" />
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                  Team Collaboration
                </h3>
                <div class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                  <p>
                    Invite team members to help manage campaigns, view analytics, and collaborate on billboard strategies.
                    You can always add more members later from your settings.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </Card>
      </div>

      <!-- Form Actions -->
      <div class="flex items-center justify-between mt-8">
        <Button
          :href="route('tenant.onboarding.business-info')"
          variant="outline"
        >
          <ArrowLeft class="w-4 h-4 mr-2" />
          Back
        </Button>

        <div class="flex items-center space-x-3">
          <Button
            type="button"
            variant="ghost"
            @click="skip"
            :disabled="skipForm.processing"
          >
            Skip for now
          </Button>

          <Button @click="continue_">
            Continue
            <ArrowRight class="w-4 h-4 ml-2" />
          </Button>
        </div>
      </div>
    </div>
  </TenantLayout>
</template>