<template>
  <TenantLayout title="Billing Settings">
    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div class="flex justify-between items-center mb-6">
              <h1 class="text-2xl font-bold">Billing & Subscription</h1>
              <Link :href="route('tenant.manage.settings.index')" class="text-blue-600 hover:text-blue-800">
                ← Back to Settings
              </Link>
            </div>

            <!-- Current Subscription -->
            <div class="bg-gray-50 p-6 rounded-lg mb-6">
              <h2 class="text-lg font-semibold mb-4">Current Subscription</h2>
              <div v-if="subscription" class="flex justify-between items-center">
                <div>
                  <div class="text-xl font-bold capitalize">{{ tenant.subscription_tier }}</div>
                  <div class="text-gray-600">{{ subscription.status }}</div>
                </div>
                <div class="text-right">
                  <div v-if="subscription.amount" class="text-2xl font-bold">${{ subscription.amount }}/month</div>
                  <div class="text-gray-600">Next billing: {{ subscription.next_billing_date }}</div>
                </div>
              </div>
              <div v-else>
                <div class="text-xl font-bold capitalize">{{ tenant.subscription_tier }}</div>
                <div class="text-gray-600">Free trial</div>
              </div>
            </div>

            <!-- Available Plans -->
            <div class="mb-6">
              <h2 class="text-lg font-semibold mb-4">Available Plans</h2>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div v-for="(plan, key) in plans" :key="key"
                     :class="[
                       'border rounded-lg p-4',
                       tenant.subscription_tier === key ? 'border-blue-500 bg-blue-50' : 'border-gray-200'
                     ]">
                  <h3 class="text-lg font-semibold">{{ plan.name }}</h3>
                  <div class="text-2xl font-bold">${{ plan.price }}<span class="text-sm font-normal">/{{ plan.interval }}</span></div>
                  <ul class="mt-4 space-y-2 text-sm">
                    <li v-for="feature in plan.features" :key="feature" class="flex items-center">
                      <svg class="h-4 w-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                      </svg>
                      {{ feature }}
                    </li>
                  </ul>
                  <button v-if="tenant.subscription_tier !== key"
                          class="w-full mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Upgrade to {{ plan.name }}
                  </button>
                  <div v-else class="w-full mt-4 px-4 py-2 bg-gray-200 text-gray-600 rounded-md text-center">
                    Current Plan
                  </div>
                </div>
              </div>
            </div>

            <!-- Payment Methods -->
            <div class="mb-6">
              <h2 class="text-lg font-semibold mb-4">Payment Methods</h2>
              <div v-if="paymentMethods.length > 0" class="space-y-3">
                <div v-for="method in paymentMethods" :key="method.id"
                     class="flex items-center justify-between p-4 border rounded-lg">
                  <div class="flex items-center">
                    <div class="h-8 w-8 bg-gray-200 rounded mr-3"></div>
                    <div>
                      <div class="font-medium">**** **** **** {{ method.last4 }}</div>
                      <div class="text-sm text-gray-600">{{ method.brand }} • Expires {{ method.exp_month }}/{{ method.exp_year }}</div>
                    </div>
                  </div>
                  <button class="text-red-600 hover:text-red-800">Remove</button>
                </div>
              </div>
              <div v-else class="text-gray-600 text-center py-8">
                No payment methods on file
              </div>
              <button class="mt-4 px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                Add Payment Method
              </button>
            </div>

            <!-- Billing History -->
            <div>
              <h2 class="text-lg font-semibold mb-4">Billing History</h2>
              <div v-if="invoices.length > 0" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200">
                    <tr v-for="invoice in invoices" :key="invoice.id">
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ invoice.date }}</td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${{ invoice.amount }}</td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span :class="[
                          invoice.status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800',
                          'px-2 inline-flex text-xs leading-5 font-semibold rounded-full'
                        ]">
                          {{ invoice.status }}
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="#" class="text-indigo-600 hover:text-indigo-900">Download</a>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div v-else class="text-gray-600 text-center py-8">
                No billing history available
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </TenantLayout>
</template>

<script setup>
import TenantLayout from '@/Layouts/TenantLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  tenant: Object,
  subscription: Object,
  paymentMethods: Array,
  invoices: Array,
  plans: Object
})
</script>