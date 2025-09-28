<template>
  <AppLayout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-8">
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Complete Your Purchase</h1>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            Complete your payment to create your organization with the {{ billingPlan.display_name }} plan
          </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- Order Summary -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Order Summary</h2>

            <!-- Organization Details -->
            <div class="border-b border-gray-200 dark:border-gray-700 pb-4 mb-4">
              <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Organization</h3>
              <p class="text-lg font-medium text-gray-900 dark:text-white">{{ pendingOrganization.name }}</p>
              <p class="text-sm text-gray-600 dark:text-gray-400">{{ pendingOrganization.description }}</p>
            </div>

            <!-- Plan Details -->
            <div class="border-b border-gray-200 dark:border-gray-700 pb-4 mb-4">
              <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Subscription Plan</h3>
              <div class="flex justify-between items-center">
                <div>
                  <p class="text-lg font-medium text-gray-900 dark:text-white">{{ billingPlan.display_name }}</p>
                  <p class="text-sm text-gray-600 dark:text-gray-400">{{ billingPlan.description }}</p>
                </div>
                <div class="text-right">
                  <p class="text-lg font-bold text-gray-900 dark:text-white">
                    K{{ form.billing_cycle === 'annually' ? billingPlan.annual_price : billingPlan.price }}
                  </p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    per {{ form.billing_cycle === 'annually' ? 'year' : 'month' }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Features -->
            <div class="mb-6">
              <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Included Features</h3>
              <ul class="space-y-2">
                <li v-for="feature in billingPlan.features" :key="feature.id" class="flex items-center text-sm">
                  <Check class="h-4 w-4 text-green-500 mr-2 flex-shrink-0" />
                  <span class="text-gray-700 dark:text-gray-300">{{ feature.name }}</span>
                </li>
              </ul>
            </div>

            <!-- Billing Cycle Toggle -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
              <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Billing Cycle</h3>
              <div class="flex space-x-4">
                <label class="flex items-center">
                  <input
                    v-model="form.billing_cycle"
                    type="radio"
                    value="monthly"
                    class="text-indigo-600 focus:ring-indigo-500"
                  />
                  <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Monthly</span>
                </label>
                <label class="flex items-center">
                  <input
                    v-model="form.billing_cycle"
                    type="radio"
                    value="annually"
                    class="text-indigo-600 focus:ring-indigo-500"
                  />
                  <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                    Annually
                    <span class="text-green-600 dark:text-green-400 text-xs ml-1">
                      (Save {{ Math.round(((billingPlan.price * 12 - billingPlan.annual_price) / (billingPlan.price * 12)) * 100) }}%)
                    </span>
                  </span>
                </label>
              </div>
            </div>
          </div>

          <!-- Payment Form -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Payment Method</h2>

            <form @submit.prevent="processPayment" class="space-y-6">
              <!-- Payment Method Selection -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                  Choose Payment Method
                </label>
                <div class="space-y-3">
                  <label class="flex items-center p-4 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
                    <input
                      v-model="form.payment_method"
                      type="radio"
                      value="card"
                      class="text-indigo-600 focus:ring-indigo-500"
                    />
                    <div class="ml-3 flex items-center">
                      <CreditCard class="h-5 w-5 text-gray-400 mr-2" />
                      <span class="text-sm font-medium text-gray-900 dark:text-white">Credit/Debit Card</span>
                    </div>
                  </label>

                  <label class="flex items-center p-4 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
                    <input
                      v-model="form.payment_method"
                      type="radio"
                      value="mobile_money"
                      class="text-indigo-600 focus:ring-indigo-500"
                    />
                    <div class="ml-3 flex items-center">
                      <Smartphone class="h-5 w-5 text-gray-400 mr-2" />
                      <span class="text-sm font-medium text-gray-900 dark:text-white">Mobile Money</span>
                    </div>
                  </label>
                </div>
              </div>

              <!-- Error Display -->
              <div v-if="form.errors.general" class="bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-800 rounded-md p-4">
                <div class="flex">
                  <AlertCircle class="h-5 w-5 text-red-400 mr-2" />
                  <p class="text-sm text-red-700 dark:text-red-300">{{ form.errors.general }}</p>
                </div>
              </div>

              <!-- Total -->
              <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="flex justify-between items-center">
                  <span class="text-lg font-medium text-gray-900 dark:text-white">Total</span>
                  <span class="text-2xl font-bold text-gray-900 dark:text-white">
                    K{{ form.billing_cycle === 'annually' ? billingPlan.annual_price : billingPlan.price }}
                  </span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                  Billed {{ form.billing_cycle === 'annually' ? 'annually' : 'monthly' }}
                </p>
              </div>

              <!-- Submit Button -->
              <Button
                type="submit"
                :disabled="form.processing || !form.payment_method"
                class="w-full"
                size="lg"
              >
                <div v-if="form.processing" class="flex items-center">
                  <div class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full mr-2"></div>
                  Processing...
                </div>
                <div v-else class="flex items-center justify-center">
                  <Lock class="h-4 w-4 mr-2" />
                  Complete Payment
                </div>
              </Button>

              <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
                Your payment is secured by PayChangu. By completing this purchase, you agree to our terms of service.
              </p>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '../../layouts/AppLayout.vue'
import Button from '../../components/ui/Button.vue'
import { Check, CreditCard, Smartphone, AlertCircle, Lock } from 'lucide-vue-next'

interface BillingPlan {
  id: number
  name: string
  display_name: string
  description: string
  price: number
  annual_price: number
  features: Array<{
    id: number
    name: string
    description: string
  }>
}

interface PendingOrganization {
  name: string
  description: string
  slug: string
  subdomain: string
  plan: string
  settings: Record<string, any>
}

const props = defineProps<{
  billingPlan: BillingPlan
  pendingOrganization: PendingOrganization
  paychanguPublicKey: string
}>()

const form = useForm({
  plan: props.billingPlan.name,
  payment_method: 'card',
  billing_cycle: 'monthly',
})

const processPayment = async () => {
  form.clearErrors()

  try {
    // Submit payment request
    const response = await form.post(route('checkout.payment'), {
      preserveScroll: true,
      onSuccess: (data: any) => {
        // Redirect to PayChangu payment page
        if (data.props.flash?.payment_url) {
          window.location.href = data.props.flash.payment_url
        }
      },
      onError: (errors: any) => {
        if (errors.message) {
          form.setError('general', errors.message)
        }
      }
    })
  } catch (error) {
    form.setError('general', 'An unexpected error occurred. Please try again.')
  }
}
</script>