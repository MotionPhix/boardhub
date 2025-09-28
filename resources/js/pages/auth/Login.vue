<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <!-- Dark Mode Toggle -->
    <div class="absolute top-4 right-4">
      <DarkModeToggle />
    </div>

    <div class="max-w-md w-full space-y-8">
      <div>
        <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900">
          <Building2 class="h-8 w-8 text-indigo-600 dark:text-indigo-400" />
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
          Sign in to your account
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
          Welcome back to AdPro
        </p>
      </div>

      <form class="mt-8 space-y-6" @submit.prevent="submit">
        <!-- Social Login -->
        <div class="space-y-3">
          <SocialLoginButton provider="google" />

          <div class="relative">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-gray-300 dark:border-gray-600" />
            </div>
            <div class="relative flex justify-center text-sm">
              <span class="px-2 bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400">
                Or continue with email
              </span>
            </div>
          </div>
        </div>

        <div class="space-y-4">
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
              autocomplete="current-password"
              placeholder="Enter your password"
              required
              :error="form.errors.password"
            />
          </div>
        </div>

        <div class="flex items-center justify-between">
          <Checkbox
            id="remember-me"
            v-model="form.remember"
            name="remember"
            label="Remember me"
          />

          <div class="text-sm">
            <a href="#" class="font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300">
              Forgot your password?
            </a>
          </div>
        </div>

        <div v-if="form.errors.general" class="rounded-md bg-red-50 dark:bg-red-900/20 p-4">
          <div class="text-sm text-red-700 dark:text-red-400">
            {{ form.errors.general }}
          </div>
        </div>

        <div>
          <button
            type="submit"
            :disabled="form.processing"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
              <LockKeyhole class="h-5 w-5 text-indigo-500 group-hover:text-indigo-400" />
            </span>
            <span v-if="form.processing" class="flex items-center">
              <Loader2 class="h-4 w-4 mr-2 animate-spin" />
              Signing in...
            </span>
            <span v-else>Sign in</span>
          </button>
        </div>

        <div class="text-center">
          <p class="text-sm text-gray-600 dark:text-gray-400">
            Don't have an account?
            <Link href="/register" class="font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300">
              Sign up here
            </Link>
          </p>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import { Building2, LockKeyhole, Loader2 } from 'lucide-vue-next'
import DarkModeToggle from '../../components/DarkModeToggle.vue'
import Input from '../../components/ui/Input.vue'
import Label from '../../components/ui/Label.vue'
import Checkbox from '../../components/ui/Checkbox.vue'
import SocialLoginButton from '../../components/ui/SocialLoginButton.vue'

const form = useForm({
  email: '',
  password: '',
  remember: false
})

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
    onError: (errors) => {
      if (errors.email || errors.password) {
        form.errors.general = 'Please check your credentials and try again.'
      }
    }
  })
}
</script>