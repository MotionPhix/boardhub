<template>
  <Modal v-slot="{ close }">
    <ModalRoot>
      <ModalHeader
        title="Create New User"
        description="Add a new user to the system with appropriate permissions."
        :icon="UserPlus"
        :on-close="close"
      />

      <ModalScrollable>
        <form @submit.prevent="submit" class="space-y-6">
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <Input
              v-model="form.name"
              label="Full Name"
              placeholder="Enter full name"
              required
              :error="form.errors.name"
            />

            <Input
              v-model="form.email"
              type="email"
              label="Email Address"
              placeholder="user@example.com"
              required
              :error="form.errors.email"
            />
          </div>

          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <Input
              v-model="form.password"
              type="password"
              label="Password"
              placeholder="Enter secure password"
              required
              :error="form.errors.password"
            />

            <Input
              v-model="form.password_confirmation"
              type="password"
              label="Confirm Password"
              placeholder="Confirm password"
              required
              :error="form.errors.password_confirmation"
            />
          </div>

          <Select
            v-model="form.role"
            label="User Role"
            description="Select the appropriate role for this user"
            placeholder="Choose a role..."
            :options="roleOptions"
            required
            :error="form.errors.role"
          />

          <Select
            v-model="form.status"
            label="Account Status"
            :options="statusOptions"
            required
            :error="form.errors.status"
          />

          <div class="flex items-center space-x-3">
            <input
              id="send_welcome_email"
              v-model="form.send_welcome_email"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="send_welcome_email" class="text-sm text-gray-700">
              Send welcome email to the user
            </label>
          </div>
        </form>
      </ModalScrollable>

      <ModalFooter>
        <Button
          type="button"
          variant="outline"
          @click="close"
          :disabled="form.processing"
        >
          Cancel
        </Button>
        <Button
          type="submit"
          :loading="form.processing"
          @click="submit"
        >
          Create User
        </Button>
      </ModalFooter>
    </ModalRoot>
  </Modal>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { UserPlus } from 'lucide-vue-next'
import {
  Modal,
  ModalRoot,
  ModalHeader,
  ModalScrollable,
  ModalFooter,
  Button,
  Input,
  Select
} from '@/components/ui'

interface Props {
  roles: Array<{ id: number; name: string }>
}

const props = defineProps<Props>()

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: '',
  status: 'active',
  send_welcome_email: true
})

const roleOptions = props.roles.map(role => ({
  value: role.name,
  label: role.name.charAt(0).toUpperCase() + role.name.slice(1),
  description: `Grant ${role.name} level access`
}))

const statusOptions = [
  { value: 'active', label: 'Active', description: 'User can login and access the system' },
  { value: 'inactive', label: 'Inactive', description: 'User account is disabled' },
  { value: 'suspended', label: 'Suspended', description: 'User account is temporarily suspended' }
]

const submit = () => {
  form.post('/admin/users', {
    onSuccess: () => {
      // Modal will be closed automatically by InertiaUI
    }
  })
}
</script>