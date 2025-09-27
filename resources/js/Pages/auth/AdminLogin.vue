<template>
  <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div class="flex justify-center">
        <div class="flex items-center">
          <div class="h-10 w-10 bg-indigo-600 rounded-lg flex items-center justify-center">
            <span class="text-white font-bold text-lg">A</span>
          </div>
          <span class="ml-2 text-2xl font-bold text-gray-900">AdPro Admin</span>
        </div>
      </div>
      <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
        Administrative Access
      </h2>
      <p class="mt-2 text-center text-sm text-gray-600">
        Secure login for authorized personnel only
      </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow-2xl sm:rounded-lg sm:px-10 border">
        <!-- Security Notice -->
        <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
          <div class="flex">
            <ShieldAlert class="h-5 w-5 text-yellow-400" />
            <div class="ml-3">
              <h3 class="text-sm font-medium text-yellow-800">
                Security Notice
              </h3>
              <div class="mt-2 text-sm text-yellow-700">
                <p>All administrative access is monitored and logged. Unauthorized access attempts will be reported.</p>
              </div>
            </div>
          </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700">
              Email address
            </label>
            <div class="mt-1 relative">
              <input
                id="email"
                v-model="form.email"
                name="email"
                type="email"
                autocomplete="email"
                required
                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                :class="{ 'border-red-300': form.errors.email }"
              />
              <User class="absolute right-3 top-2 h-5 w-5 text-gray-400" />
            </div>
            <p v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</p>
          </div>

          <div>
            <label for="password" class="block text-sm font-medium text-gray-700">
              Password
            </label>
            <div class="mt-1 relative">
              <input
                id="password"
                v-model="form.password"
                name="password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="current-password"
                required
                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm pr-10"
                :class="{ 'border-red-300': form.errors.password }"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-3 flex items-center"
              >
                <Eye v-if="!showPassword" class="h-5 w-5 text-gray-400" />
                <EyeOff v-else class="h-5 w-5 text-gray-400" />
              </button>
            </div>
            <p v-if="form.errors.password" class="mt-2 text-sm text-red-600">{{ form.errors.password }}</p>
          </div>

          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <input
                id="remember-me"
                v-model="form.remember"
                name="remember-me"
                type="checkbox"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
              <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                Remember me
              </label>
            </div>

            <div class="text-sm">
              <Link href="/admin/forgot-password" class="font-medium text-indigo-600 hover:text-indigo-500">
                Forgot your password?
              </Link>
            </div>
          </div>

          <div>
            <button
              type="submit"
              :disabled="form.processing"
              class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                <Lock class="h-5 w-5 text-indigo-500 group-hover:text-indigo-400" />
              </span>
              <span v-if="form.processing">Authenticating...</span>
              <span v-else>Sign in to Admin Panel</span>
            </button>
          </div>
        </form>

        <!-- 2FA Section -->
        <div v-if="show2FA" class="mt-6 border-t border-gray-200 pt-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Two-Factor Authentication</h3>
          <div class="space-y-4">
            <div>
              <label for="two_factor_code" class="block text-sm font-medium text-gray-700">
                Authentication Code
              </label>
              <input
                id="two_factor_code"
                v-model="twoFactorForm.code"
                type="text"
                maxlength="6"
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm text-center text-lg tracking-wider"
                placeholder="000000"
                @input="validateTwoFactorCode"
              />
              <p class="mt-2 text-sm text-gray-500">
                Enter the 6-digit code from your authenticator app
              </p>
            </div>
            <button
              @click="submitTwoFactor"
              :disabled="twoFactorForm.processing || !isValidCode"
              class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
            >
              Verify & Continue
            </button>
          </div>
        </div>

        <!-- Security Features -->
        <div class="mt-6 border-t border-gray-200 pt-6">
          <div class="text-center">
            <div class="flex items-center justify-center space-x-2 text-sm text-gray-500">
              <Shield class="h-4 w-4 text-green-500" />
              <span>256-bit SSL encryption</span>
            </div>
            <div class="flex items-center justify-center space-x-2 text-sm text-gray-500 mt-1">
              <Clock class="h-4 w-4 text-blue-500" />
              <span>Session timeout: 8 hours</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import {
  Lock,
  User,
  Eye,
  EyeOff,
  ShieldAlert,
  Shield,
  Clock
} from 'lucide-vue-next'

const showPassword = ref(false)
const show2FA = ref(false)

const form = useForm({
  email: '',
  password: '',
  remember: false
})

const twoFactorForm = useForm({
  code: ''
})

const isValidCode = computed(() => {
  return twoFactorForm.code.length === 6 && /^\d{6}$/.test(twoFactorForm.code)
})

const submit = () => {
  form.post('/admin/login', {
    onSuccess: (page) => {
      if (page.props.requires_2fa) {
        show2FA.value = true
      }
    }
  })
}

const validateTwoFactorCode = () => {
  // Remove non-numeric characters
  twoFactorForm.code = twoFactorForm.code.replace(/\D/g, '')
}

const submitTwoFactor = () => {
  twoFactorForm.post('/admin/two-factor-challenge', {
    onSuccess: () => {
      // Redirect will be handled by the backend
    }
  })
}
</script>