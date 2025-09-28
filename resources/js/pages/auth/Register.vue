<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <!-- Dark Mode Toggle -->
    <div class="absolute top-4 right-4">
      <DarkModeToggle />
    </div>

    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
          Create your account
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
          Join our military-grade platform
        </p>
      </div>
      <form class="mt-8 space-y-6" @submit.prevent="submit">
        <!-- Social Login -->
        <div class="space-y-3">
          <SocialLoginButton provider="google" custom-text="Sign up with Google" />

          <div class="relative">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-gray-300 dark:border-gray-600" />
            </div>
            <div class="relative flex justify-center text-sm">
              <span class="px-2 bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400">
                Or create account with email
              </span>
            </div>
          </div>
        </div>

        <div class="space-y-4">
          <div>
            <Label for="name" text="Full name" required />
            <Input
              id="name"
              v-model="form.name"
              name="name"
              type="text"
              autocomplete="name"
              placeholder="Enter your full name"
              required
              :error="form.errors.name"
            />
          </div>

          <div>
            <Label for="email" text="Email address" required />
            <Input
              id="email"
              v-model="form.email"
              name="email"
              type="email"
              autocomplete="email"
              placeholder="Enter your email"
              required
              :error="form.errors.email"
            />
          </div>

          <div>
            <Label for="password" text="Password" required />
            <Input
              id="password"
              v-model="form.password"
              name="password"
              type="password"
              autocomplete="new-password"
              placeholder="Create a password"
              required
              :error="form.errors.password"
            />
          </div>

          <div>
            <Label for="password_confirmation" text="Confirm password" required />
            <Input
              id="password_confirmation"
              v-model="form.password_confirmation"
              name="password_confirmation"
              type="password"
              autocomplete="new-password"
              placeholder="Confirm your password"
              required
              :error="form.errors.password_confirmation"
            />
          </div>
        </div>

        <div class="flex items-start">
          <Checkbox
            id="terms"
            v-model="form.terms"
            name="terms"
            required
          >
            <span class="text-sm text-gray-900 dark:text-gray-300">
              I agree to the
              <a href="#" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300">Terms of Service</a>
              and
              <a href="#" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300">Privacy Policy</a>
            </span>
          </Checkbox>
        </div>

        <div>
          <button
            type="submit"
            :disabled="form.processing"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
          >
            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
              <UserPlusIcon class="h-5 w-5 text-indigo-500 group-hover:text-indigo-400" aria-hidden="true" />
            </span>
            {{ form.processing ? 'Creating account...' : 'Create account' }}
          </button>
        </div>

        <div class="text-center">
          <Link :href="route('login')" class="font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300">
            Already have an account? Sign in
          </Link>
        </div>

        <!-- Display validation errors -->
        <div v-if="Object.keys(form.errors).length > 0" class="rounded-md bg-red-50 p-4">
          <div class="text-sm text-red-700">
            <ul class="list-disc pl-5 space-y-1">
              <li v-for="(error, field) in form.errors" :key="field">
                {{ error }}
              </li>
            </ul>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import { UserPlusIcon } from 'lucide-vue-next'
import DarkModeToggle from '../../components/DarkModeToggle.vue'
import Input from '../../components/ui/Input.vue'
import Label from '../../components/ui/Label.vue'
import Checkbox from '../../components/ui/Checkbox.vue'
import SocialLoginButton from '../../components/ui/SocialLoginButton.vue'

// Define props if needed
interface Props {
  status?: string
}

const props = withDefaults(defineProps<Props>(), {
  status: ''
})

// Create form
const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  terms: false,
})

// Submit function
const submit = () => {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>