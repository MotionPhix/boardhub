<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import {Head, Link, useForm} from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Button from '@/components/ui/Button.vue'
import Card from '@/components/ui/Card.vue'
import Input from '@/components/ui/Input.vue'
import InputError from '@/components/ui/InputError.vue'
import Label from '@/components/ui/Label.vue'
import Textarea from '@/components/ui/Textarea.vue'
import ModalRoot from '@/components/ui/ModalRoot.vue'
import ModalHeader from '@/components/ui/ModalHeader.vue'
import ModalScrollable from '@/components/ui/ModalScrollable.vue'
import ModalFooter from '@/components/ui/ModalFooter.vue'
import { Check, Crown, Star, Zap, X } from 'lucide-vue-next'
import { Modal, ModalLink } from '@inertiaui/modal-vue'
import {toast} from "vue-sonner";

interface BillingPlan {
  id: number
  name: string
  display_name: string
  description: string
  price: number
  annual_price: number
  trial_days: number
  is_popular: boolean
  is_active: boolean
  features: string[]
  limits: Record<string, any>
  sort_order: number
}

interface Props {
  billingPlans: BillingPlan[]
}

const props = defineProps<Props>()

const form = useForm({
  name: '',
  description: '',
  slug: '',
  subdomain: '',
  plan: 'trial',
  settings: {
    primary_color: '#6366f1',
    secondary_color: '#8b5cf6',
    theme: 'default',
    features: {}
  }
})

const selectedBillingCycle = ref<'monthly' | 'annually'>('monthly')
const expandedPlans = ref<Set<string>>(new Set())
const generating = ref(false)

// Get currently selected plan
const selectedPlan = computed(() => {
  return props.billingPlans.find(plan => plan.name === form.plan)
})

// Watch for plan changes and update form features
watch(() => form.plan, (newPlan) => {
  const plan = props.billingPlans.find(p => p.name === newPlan)
  if (plan) {
    // Reset features based on plan selection
    form.settings.features = {}

    // Auto-enable basic features for all plans
    if (plan.features.length > 0) {
      plan.features.slice(0, 3).forEach((feature, index) => {
        form.settings.features[`feature_${index}`] = true
      })
    }

    // Clear subdomain for trial plans (premium feature only)
    if (newPlan === 'trial') {
      form.subdomain = ''
    }
  }
})

const togglePlanExpansion = (planName: string) => {
  if (expandedPlans.value.has(planName)) {
    expandedPlans.value.delete(planName)
  } else {
    expandedPlans.value.add(planName)
  }
}

const generateSlug = () => {
  if (form.name) {
    form.slug = form.name
      .toLowerCase()
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
      .trim()
  }
}

// Watch for changes in organization name and auto-generate slug
watch(() => form.name, (newName) => {
  if (newName) {
    generateSlug()
    // Also auto-generate subdomain if it's empty or matches the previous slug
    if (!form.subdomain || form.subdomain === form.slug) {
      generateSubdomain()
    }
  }
}, { immediate: false })

const generateSubdomain = () => {
  if (form.slug) {
    form.subdomain = form.slug
  }
}

const submit = () => {
  form.post(route('organizations.store'), {
    onSuccess: () => {
      toast.success('Organization store successfully.')
    },
    onError: () => {
      toast.error('Oops!', {
        description: 'An error occurred while creating the organization.',
      })
    }
  })
}

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 0,
  }).format(price)
}

const getAnnualSavings = (plan: BillingPlan) => {
  if (!plan.annual_price || plan.price === 0) return 0
  const monthlyCost = plan.price * 12
  const savings = monthlyCost - plan.annual_price
  return Math.round((savings / monthlyCost) * 100)
}

const getPlanIcon = (planName: string) => {
  switch (planName) {
    case 'trial': return Check
    case 'basic': return Zap
    case 'pro': return Star
    case 'enterprise': return Crown
    default: return Check
  }
}
</script>

<template>
  <Head title="Create Organization" />

  <AppLayout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
      <div class="max-w-4xl mx-auto">
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            Create New Organization
          </h1>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            Set up your organization and start managing your advertising campaigns. You'll be charged per organization based on your selected plan.
          </p>
        </div>

        <form @submit.prevent="submit" class="space-y-8">
          <!-- Basic Information -->
          <Card>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
              Basic Information
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label for="name" required>Organization Name</Label>
                <Input
                  id="name"
                  v-model="form.name"
                  type="text"
                  size="lg"
                  placeholder="Enter organization name"
                  required
                />
                <InputError :message="form.errors.name" />
              </div>

              <div>
                <Label for="slug" required>Slug</Label>
                <div>
                  <Input
                    id="slug"
                    v-model="form.slug"
                    type="text"
                    size="lg"
                    placeholder="organization-slug"
                    required
                  />
                </div>
                <InputError :message="form.errors.slug" />
                <p class="text-sm text-gray-500 mt-1">
                  Used for URLs and identification
                </p>
              </div>

              <div class="md:col-span-2">
                <Label for="description">Description</Label>
                <Textarea
                  id="description"
                  v-model="form.description"
                  placeholder="Brief description of the organization"
                  :rows="3"
                />
                <InputError :message="form.errors.description" />
              </div>
            </div>
          </Card>

          <!-- Domain Configuration -->
          <Card>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
              Access Configuration
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
              Configure how your organization will be accessed. Each organization gets a secure, unique access URL.
            </p>

            <!-- Current Access Method (Always Available) -->
            <div class="mb-6">
              <Label>Organization Access URL</Label>
              <div class="mt-2 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                  <svg class="h-5 w-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                  </svg>
                  <span class="font-mono text-sm text-gray-900 dark:text-gray-100">
                    https://adpro.test/t/{{ form.slug || 'your-organization-id' }}
                  </span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">✅ Available on all plans - Secure UUID-based access</p>
              </div>
            </div>

            <!-- Premium Subdomain Feature -->
            <div
              :class="[
                'relative overflow-hidden rounded-lg border-2 transition-all duration-200',
                form.plan === 'trial'
                  ? 'border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50'
                  : 'border-indigo-200 dark:border-indigo-700 bg-indigo-50 dark:bg-indigo-900/20'
              ]"
            >
              <!-- Premium Badge -->
              <div v-if="form.plan === 'trial'" class="absolute top-3 right-3">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-200">
                  <Crown class="w-3 h-3 mr-1" />
                  Premium
                </span>
              </div>

              <div class="p-4">
                <Label for="subdomain" :class="form.plan === 'trial' ? 'text-gray-400 dark:text-gray-500' : ''">
                  Custom Subdomain (Premium Feature)
                </Label>
                <div class="flex mt-2">
                  <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-sm">
                    https://
                  </span>
                  <Input
                    id="subdomain"
                    v-model="form.subdomain"
                    type="text"
                    placeholder="my-organization"
                    :disabled="form.plan === 'trial'"
                    :class="[
                      'flex-1 rounded-none',
                      form.plan === 'trial' ? 'bg-gray-100 dark:bg-gray-700 cursor-not-allowed' : ''
                    ]"
                  />
                  <span class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-sm">
                    .adpro.test
                  </span>
                </div>
                <InputError :message="form.errors.subdomain" />

                <div v-if="form.plan === 'trial'" class="mt-3 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                  <div class="flex">
                    <Crown class="h-5 w-5 text-amber-500 mt-0.5 mr-2 flex-shrink-0" />
                    <div>
                      <h4 class="text-sm font-medium text-amber-800 dark:text-amber-200">
                        Upgrade to unlock custom subdomains
                      </h4>
                      <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                        Get a branded subdomain like <strong>mycompany.adpro.test</strong> with professional plans.
                      </p>
                    </div>
                  </div>
                </div>

                <div v-else class="mt-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                  <div class="flex">
                    <Check class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
                    <div>
                      <h4 class="text-sm font-medium text-green-800 dark:text-green-200">
                        Custom subdomain enabled
                      </h4>
                      <p class="text-sm text-green-700 dark:text-green-300 mt-1">
                        <span v-if="form.subdomain">
                          Your organization will be available at: <strong>https://{{ form.subdomain }}.adpro.test</strong>
                        </span>
                        <span v-else>
                          Enter a subdomain to get your custom URL
                        </span>
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Information Box -->
            <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                    How organization access works:
                  </h3>
                  <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                    <ul class="list-disc list-inside space-y-1">
                      <li><strong>Secure UUID Access</strong> - All organizations get a secure, unique URL (always available)</li>
                      <li><strong>Data Isolation</strong> - Your billboards, bookings, and team data are completely isolated</li>
                      <li><strong>Custom Subdomains</strong> - Professional plans get branded subdomain access</li>
                      <li><strong>Future: Custom Domains</strong> - Enterprise plans will support custom domains</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </Card>

          <!-- Premium Pricing Plans -->
          <Card>
            <div class="text-center mb-8">
              <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                Choose Your Plan
              </h2>
              <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                Select the perfect plan for your organization. Start with a free trial and upgrade anytime.
              </p>

              <!-- Billing Cycle Toggle -->
              <div class="flex items-center justify-center mt-6">
                <span class="text-sm text-gray-500 mr-3">Monthly</span>
                <button
                  type="button"
                  @click="selectedBillingCycle = selectedBillingCycle === 'monthly' ? 'annually' : 'monthly'"
                  :class="[
                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2',
                    selectedBillingCycle === 'annually' ? 'bg-indigo-600' : 'bg-gray-200'
                  ]"
                >
                  <span
                    :class="[
                      'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                      selectedBillingCycle === 'annually' ? 'translate-x-5' : 'translate-x-0'
                    ]"
                  />
                </button>
                <span class="text-sm text-gray-500 ml-3">
                  Annually
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 ml-1">
                    Save up to 17%
                  </span>
                </span>
              </div>
            </div>

            <!-- Plans Grid -->
            <div class="max-w-7xl mx-auto">
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8">
                <div
                  v-for="plan in billingPlans"
                  :key="plan.id"
                  :class="[
                    'min-h-[320px] flex flex-col relative cursor-pointer transition-all duration-200',
                    plan.is_popular
                      ? 'bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 rounded-xl shadow-xl ring-1 ring-gray-900/10 dark:ring-gray-100/10 transform scale-105'
                      : form.plan === plan.name
                        ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-xl shadow-xl ring-2 ring-blue-600 dark:ring-blue-400'
                        : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-xl shadow-lg ring-1 ring-gray-900/10 dark:ring-gray-700/50 hover:shadow-xl hover:ring-gray-900/20 dark:hover:ring-gray-700'
                  ]"
                  @click="form.plan = plan.name">
                  <!-- Popular Badge -->
                  <div v-if="plan.is_popular" class="absolute -top-3 left-1 transform translate-x-1/2">
                    <span class="bg-blue-600 text-white px-3 py-1 text-sm font-semibold rounded-full">
                      Most popular
                    </span>
                  </div>

                  <!-- Card Content -->
                  <div class="p-6 h-full flex flex-col justify-between">
                    <!-- Top Content -->
                    <div class="flex-1">
                      <!-- Plan Name -->
                      <div class="flex items-center">
                        <h3 :class="[
                          'text-lg font-semibold',
                          plan.is_popular ? 'text-white dark:text-gray-900' : 'text-gray-900 dark:text-gray-100'
                        ]">
                          {{ plan.display_name }}
                        </h3>
                        <div v-if="form.plan === plan.name" class="ml-auto">
                          <Check class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        </div>
                      </div>

                      <!-- Pricing -->
                      <div class="mt-4 flex items-baseline gap-x-1">
                        <span :class="[
                          'text-3xl font-bold tracking-tight',
                          plan.is_popular ? 'text-white dark:text-gray-900' : 'text-gray-900 dark:text-gray-100'
                        ]">
                          {{ selectedBillingCycle === 'annually' && plan.annual_price
                             ? formatPrice(plan.annual_price / 12)
                             : formatPrice(plan.price) }}
                        </span>
                        <span :class="[
                          'text-sm font-semibold',
                          plan.is_popular ? 'text-gray-300 dark:text-gray-500' : 'text-gray-600 dark:text-gray-400'
                        ]">
                          {{ plan.price === 0 ? '' : '/month' }}
                        </span>
                      </div>

                      <!-- Annual Savings -->
                      <div v-if="selectedBillingCycle === 'annually' && plan.annual_price && plan.price > 0" class="mt-1">
                        <span :class="[
                          'text-xs line-through',
                          plan.is_popular ? 'text-gray-400 dark:text-gray-500' : 'text-gray-500 dark:text-gray-400'
                        ]">
                          {{ formatPrice(plan.price) }}/month
                        </span>
                        <span class="ml-2 text-xs text-green-600 dark:text-green-400 font-medium">
                          Save {{ getAnnualSavings(plan) }}%
                        </span>
                      </div>

                      <!-- Plan Description -->
                      <p :class="[
                        'mt-4 text-sm leading-relaxed',
                        plan.is_popular ? 'text-gray-300 dark:text-gray-500' : 'text-gray-600 dark:text-gray-400'
                      ]">
                        {{ plan.description }}
                      </p>

                      <!-- Trial -->
                      <div v-if="plan.trial_days > 0" class="mt-4">
                        <span :class="[
                          'inline-block px-2.5 py-1 text-xs font-medium rounded-full',
                          plan.is_popular ? 'bg-gray-800 dark:bg-gray-200 text-gray-300 dark:text-gray-700' : 'bg-blue-50 dark:bg-blue-900 text-blue-700 dark:text-blue-300'
                        ]">
                          {{ plan.trial_days }}-day free trial
                        </span>
                      </div>
                    </div>

                    <!-- Bottom Content -->
                    <div class="pt-4 space-y-3">
                      <!-- Features Button -->
                      <div class="flex">
                        <ModalLink
                          :href="`#plan-features-${plan.name}`"
                          @click.stop
                          :class="[
                            'text-sm font-medium hover:underline transition-colors',
                            plan.is_popular ? 'text-gray-300 dark:text-gray-500 hover:text-white dark:hover:text-gray-700' : 'text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300'
                          ]">
                          Features
                        </ModalLink>
                      </div>

                      <!-- Bottom Faint Description -->
                      <div v-if="plan.limits" :class="[
                        'pt-3 border-t text-xs space-y-1.5 leading-relaxed',
                        plan.is_popular ? 'text-gray-400 dark:text-gray-500 border-gray-700 dark:border-gray-600' : 'text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-700'
                      ]">
                        <div v-if="plan.limits.max_campaigns && plan.limits.max_campaigns !== -1">
                          Up to {{ plan.limits.max_campaigns }} campaigns
                        </div>
                        <div v-if="plan.limits.max_team_members && plan.limits.max_team_members !== -1">
                          {{ plan.limits.max_team_members }} team members
                        </div>
                        <div v-if="plan.limits.storage_gb && plan.limits.storage_gb !== -1">
                          {{ plan.limits.storage_gb }}GB storage
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Feature Modals -->
            <div v-for="plan in billingPlans" :key="`modal-${plan.id}`">
              <Modal :name="`plan-features-${plan.name}`" max-width="2xl" :close-button="false" v-slot="{ close }">
                <ModalRoot>
                  <ModalHeader
                    :title="`${plan.display_name} Features`"
                    description="Complete feature list and usage limits"
                    :icon="getPlanIcon(plan.name)"
                    :on-close="close"
                  />

                  <ModalScrollable>
                    <!-- Plan Info -->
                    <div class="mb-6">
                      <div class="flex items-baseline gap-x-2 mb-2">
                        <span class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                          {{ selectedBillingCycle === 'annually' && plan.annual_price
                             ? formatPrice(plan.annual_price / 12)
                             : formatPrice(plan.price) }}
                        </span>
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                          {{ plan.price === 0 ? '' : '/month' }}
                        </span>
                      </div>
                      <p class="text-sm text-gray-600 dark:text-gray-400">{{ plan.description }}</p>
                      <div v-if="plan.trial_days > 0" class="mt-2">
                        <span class="inline-block px-2 py-1 text-xs font-medium rounded bg-blue-50 dark:bg-blue-900 text-blue-700 dark:text-blue-300">
                          {{ plan.trial_days }}-day free trial
                        </span>
                      </div>
                    </div>

                    <!-- All Features -->
                    <div class="mb-6">
                      <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">All Features Included:</h4>
                      <ul class="space-y-3">
                        <li
                          v-for="(feature, index) in plan.features"
                          :key="index"
                          class="flex gap-x-3 text-sm"
                        >
                          <Check class="h-4 w-4 flex-none mt-0.5 text-green-600 dark:text-green-400" />
                          <span class="text-gray-700 dark:text-gray-300">{{ feature }}</span>
                        </li>
                      </ul>
                    </div>

                    <!-- Usage Limits -->
                    <div v-if="plan.limits" class="border-t border-gray-200 dark:border-gray-700 pt-6">
                      <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">Usage Limits:</h4>
                      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-600 dark:text-gray-400">
                        <div v-if="plan.limits.max_campaigns && plan.limits.max_campaigns !== -1" class="flex items-center">
                          <div class="w-2 h-2 bg-blue-600 dark:bg-blue-400 rounded-full mr-3"></div>
                          <span>Up to {{ plan.limits.max_campaigns }} campaigns</span>
                        </div>
                        <div v-if="plan.limits.max_team_members && plan.limits.max_team_members !== -1" class="flex items-center">
                          <div class="w-2 h-2 bg-blue-600 dark:bg-blue-400 rounded-full mr-3"></div>
                          <span>{{ plan.limits.max_team_members }} team members</span>
                        </div>
                        <div v-if="plan.limits.storage_gb && plan.limits.storage_gb !== -1" class="flex items-center">
                          <div class="w-2 h-2 bg-blue-600 dark:bg-blue-400 rounded-full mr-3"></div>
                          <span>{{ plan.limits.storage_gb }}GB storage</span>
                        </div>
                        <div v-if="plan.limits.max_api_calls && plan.limits.max_api_calls !== -1" class="flex items-center">
                          <div class="w-2 h-2 bg-blue-600 dark:bg-blue-400 rounded-full mr-3"></div>
                          <span>{{ plan.limits.max_api_calls.toLocaleString() }} API calls/month</span>
                        </div>
                      </div>
                    </div>
                  </ModalScrollable>

                  <ModalFooter>
                    <Button variant="outline" @click="close">
                      Close
                    </Button>
                  </ModalFooter>
                </ModalRoot>
              </Modal>
            </div>

            <InputError :message="form.errors.plan" class="mt-4" />

            <!-- Custom Branding for Enterprise -->
            <div v-if="form.plan === 'enterprise'" class="mt-8 p-6 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl border border-indigo-200 dark:border-indigo-800">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <Crown class="w-5 h-5 mr-2 text-indigo-600" />
                Enterprise Customization
              </h3>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <Label for="primary_color">Primary Brand Color</Label>
                  <div class="flex">
                    <Input
                      id="primary_color"
                      v-model="form.settings.primary_color"
                      type="text"
                      placeholder="#6366f1"
                      class="flex-1"
                    />
                    <input
                      v-model="form.settings.primary_color"
                      type="color"
                      class="ml-2 w-12 h-10 rounded border border-gray-300"
                    />
                  </div>
                </div>

                <div>
                  <Label for="secondary_color">Secondary Brand Color</Label>
                  <div class="flex">
                    <Input
                      id="secondary_color"
                      v-model="form.settings.secondary_color"
                      type="text"
                      placeholder="#8b5cf6"
                      class="flex-1"
                    />
                    <input
                      v-model="form.settings.secondary_color"
                      type="color"
                      class="ml-2 w-12 h-10 rounded border border-gray-300"
                    />
                  </div>
                </div>
              </div>
            </div>
          </Card>

          <!-- Form Actions -->
          <div class="flex justify-end space-x-4">
            <Link
              :href="route('tenants.select')"
              :as="Button"
              variant="outline">
              Cancel
            </Link>

            <Button
              type="submit"
              variant="primary"
              :disabled="form.processing">
              {{ form.processing ? 'Creating...' : 'Create Organization' }}
            </Button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
