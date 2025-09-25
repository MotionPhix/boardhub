<template>
  <div class="paychangu-checkout-container">
    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center p-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <span class="ml-2 text-gray-600">Preparing payment...</span>
    </div>

    <!-- Payment Form -->
    <div v-else class="space-y-6">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment Details</h3>

        <div class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <span class="font-medium text-gray-700">Amount:</span>
            <span class="text-gray-900 ml-2">{{ formattedAmount }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Currency:</span>
            <span class="text-gray-900 ml-2">{{ paymentData.currency }}</span>
          </div>
          <div class="col-span-2">
            <span class="font-medium text-gray-700">Description:</span>
            <span class="text-gray-900 ml-2">{{ paymentData.description }}</span>
          </div>
        </div>
      </div>

      <!-- Payment Provider Selection -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Choose Payment Method</h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <button
            v-for="provider in availableProviders"
            :key="provider.id"
            @click="selectProvider(provider.id)"
            :class="[
              'p-4 border-2 rounded-lg transition-all duration-200 hover:shadow-md',
              selectedProvider === provider.id
                ? 'border-blue-500 bg-blue-50'
                : 'border-gray-200 hover:border-gray-300'
            ]"
            :disabled="!provider.enabled"
          >
            <img
              :src="provider.logo"
              :alt="provider.name"
              class="h-8 w-8 mx-auto mb-2"
            />
            <div class="text-sm font-medium text-gray-900">{{ provider.name }}</div>
            <div class="text-xs text-gray-500 mt-1">{{ provider.description }}</div>
          </button>
        </div>
      </div>

      <!-- Mobile Money Form -->
      <div v-if="selectedProvider && isMobileMoneyProvider(selectedProvider)" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Mobile Money Details</h3>

        <div class="space-y-4">
          <div>
            <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1">
              Phone Number
            </label>
            <input
              id="phone_number"
              v-model="mobileMoneyData.phone_number"
              type="tel"
              placeholder="e.g., 0991234567"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :class="{ 'border-red-300': errors.phone_number }"
            />
            <p v-if="errors.phone_number" class="text-red-600 text-xs mt-1">{{ errors.phone_number }}</p>
          </div>
        </div>
      </div>

      <!-- Card/Bank Transfer Form -->
      <div v-else-if="selectedProvider && (selectedProvider === 'card' || selectedProvider === 'bank_transfer')" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
          {{ selectedProvider === 'card' ? 'Billing' : 'Account' }} Information
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">
              First Name
            </label>
            <input
              id="first_name"
              v-model="customerData.first_name"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :class="{ 'border-red-300': errors.first_name }"
            />
            <p v-if="errors.first_name" class="text-red-600 text-xs mt-1">{{ errors.first_name }}</p>
          </div>

          <div>
            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">
              Last Name
            </label>
            <input
              id="last_name"
              v-model="customerData.last_name"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :class="{ 'border-red-300': errors.last_name }"
            />
            <p v-if="errors.last_name" class="text-red-600 text-xs mt-1">{{ errors.last_name }}</p>
          </div>

          <div class="md:col-span-2">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
              Email Address
            </label>
            <input
              id="email"
              v-model="customerData.email"
              type="email"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :class="{ 'border-red-300': errors.email }"
            />
            <p v-if="errors.email" class="text-red-600 text-xs mt-1">{{ errors.email }}</p>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-col sm:flex-row gap-4">
        <button
          @click="processPayment"
          :disabled="!canProcessPayment || processing"
          class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-md font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
        >
          <div v-if="processing" class="flex items-center justify-center">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
            Processing...
          </div>
          <span v-else>
            {{ selectedProvider === 'card' || selectedProvider === 'bank_transfer' ? 'Continue to Payment' : 'Pay Now' }}
          </span>
        </button>

        <button
          @click="$emit('cancel')"
          class="px-6 py-3 border border-gray-300 text-gray-700 rounded-md font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200"
        >
          Cancel
        </button>
      </div>

      <!-- Error Display -->
      <div v-if="paymentError" class="bg-red-50 border border-red-200 rounded-md p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Payment Error</h3>
            <div class="mt-2 text-sm text-red-700">{{ paymentError }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- PayChangu Inline Script Container -->
    <div id="paychangu-inline-container"></div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  paymentData: {
    type: Object,
    required: true
  },
  tenantUuid: {
    type: String,
    required: true
  },
  publicKey: {
    type: String,
    required: true
  }
})

const emit = defineEmits(['success', 'error', 'cancel'])

// State
const loading = ref(true)
const processing = ref(false)
const selectedProvider = ref(null)
const availableProviders = ref([])
const paymentError = ref(null)

// Form data
const mobileMoneyData = ref({
  phone_number: ''
})

const customerData = ref({
  first_name: '',
  last_name: '',
  email: ''
})

const errors = ref({})

// Computed
const formattedAmount = computed(() => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: props.paymentData.currency || 'MWK'
  }).format(props.paymentData.amount)
})

const canProcessPayment = computed(() => {
  if (!selectedProvider.value) return false

  if (isMobileMoneyProvider(selectedProvider.value)) {
    return mobileMoneyData.value.phone_number
  }

  if (selectedProvider.value === 'card' || selectedProvider.value === 'bank_transfer') {
    return customerData.value.first_name && customerData.value.last_name && customerData.value.email
  }

  return true
})

// Methods
const loadAvailableProviders = async () => {
  try {
    const response = await fetch(`/api/t/${props.tenantUuid}/payments/providers`)
    const result = await response.json()

    if (result.success) {
      availableProviders.value = result.data.providers
    }
  } catch (error) {
    console.error('Failed to load payment providers:', error)
    paymentError.value = 'Failed to load payment methods'
  } finally {
    loading.value = false
  }
}

const selectProvider = (providerId) => {
  selectedProvider.value = providerId
  paymentError.value = null
  errors.value = {}
}

const isMobileMoneyProvider = (provider) => {
  return ['airtel_money', 'tnm_mpamba'].includes(provider)
}

const processPayment = async () => {
  if (!canProcessPayment.value) return

  processing.value = true
  paymentError.value = null
  errors.value = {}

  try {
    const paymentPayload = {
      ...props.paymentData,
      provider: selectedProvider.value,
      ...(isMobileMoneyProvider(selectedProvider.value) && mobileMoneyData.value),
      ...(selectedProvider.value === 'card' || selectedProvider.value === 'bank_transfer' && customerData.value)
    }

    const response = await fetch(`/api/t/${props.tenantUuid}/payments/process`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(paymentPayload)
    })

    const result = await response.json()

    if (result.success) {
      if (selectedProvider.value === 'card' || selectedProvider.value === 'bank_transfer') {
        // Redirect to PayChangu checkout page
        if (result.data.checkout_url) {
          window.location.href = result.data.checkout_url
        } else {
          emit('success', result.data)
        }
      } else if (isMobileMoneyProvider(selectedProvider.value)) {
        // For mobile money, show success and poll for status
        emit('success', result.data)
        startStatusPolling(result.data.tx_ref)
      } else {
        // Handle other payment methods
        emit('success', result.data)
      }
    } else {
      if (result.errors) {
        errors.value = result.errors
      }
      paymentError.value = result.message || 'Payment processing failed'
    }
  } catch (error) {
    console.error('Payment processing error:', error)
    paymentError.value = 'An unexpected error occurred. Please try again.'
  } finally {
    processing.value = false
  }
}

const startStatusPolling = (txRef) => {
  const pollInterval = setInterval(async () => {
    try {
      const response = await fetch(`/api/t/${props.tenantUuid}/payments/${txRef}/status`)
      const result = await response.json()

      if (result.success && result.data.status !== 'pending') {
        clearInterval(pollInterval)

        if (result.data.status === 'completed') {
          emit('success', result.data)
        } else {
          emit('error', result.data)
        }
      }
    } catch (error) {
      console.error('Status polling error:', error)
    }
  }, 3000) // Poll every 3 seconds

  // Stop polling after 5 minutes
  setTimeout(() => {
    clearInterval(pollInterval)
  }, 300000)
}

const loadPayChanguScript = () => {
  return new Promise((resolve) => {
    if (window.PaychanguCheckout) {
      resolve()
      return
    }

    const script = document.createElement('script')
    script.src = 'https://in.paychangu.com/js/popup.js'
    script.onload = resolve
    script.onerror = resolve
    document.head.appendChild(script)
  })
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    loadAvailableProviders(),
    loadPayChanguScript()
  ])
})
</script>

<style scoped>
.paychangu-checkout-container {
  max-width: 600px;
  margin: 0 auto;
}

/* Animation for loading states */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>