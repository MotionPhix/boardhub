<template>
    <div class="p-8">
        <div class="max-w-2xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-3xl mb-4">
                    🏢
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Tell us about your business</h2>
                <p class="text-gray-600">Help us customize AdPro to fit your specific needs</p>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitProfile" class="space-y-6">
                <!-- Business Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        What type of business are you?
                    </label>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div
                            v-for="option in businessTypeOptions"
                            :key="option.value"
                            @click="form.business_type = option.value"
                            class="relative border-2 rounded-lg p-4 cursor-pointer hover:border-indigo-300 transition-colors"
                            :class="form.business_type === option.value ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'"
                        >
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-lg"
                                         :class="form.business_type === option.value ? 'bg-indigo-500 text-white' : 'bg-gray-100 text-gray-600'">
                                        {{ option.icon }}
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-gray-900">{{ option.label }}</h3>
                                    <p class="text-xs text-gray-500 mt-1">{{ option.description }}</p>
                                </div>
                            </div>
                            <div v-if="form.business_type === option.value"
                                 class="absolute top-2 right-2 w-5 h-5 bg-indigo-500 rounded-full flex items-center justify-center">
                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div v-if="errors.business_type" class="mt-2 text-sm text-red-600">
                        {{ errors.business_type }}
                    </div>
                </div>

                <!-- Industry -->
                <div>
                    <label for="industry" class="block text-sm font-medium text-gray-700">
                        Industry
                    </label>
                    <select
                        id="industry"
                        v-model="form.industry"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="">Select your industry</option>
                        <option v-for="industry in industries" :key="industry" :value="industry">
                            {{ industry }}
                        </option>
                    </select>
                    <div v-if="errors.industry" class="mt-2 text-sm text-red-600">
                        {{ errors.industry }}
                    </div>
                </div>

                <!-- Company Size -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Company size
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <div
                            v-for="size in companySizes"
                            :key="size.value"
                            @click="form.company_size = size.value"
                            class="relative border-2 rounded-lg p-3 cursor-pointer hover:border-indigo-300 transition-colors"
                            :class="form.company_size === size.value ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'"
                        >
                            <div class="text-center">
                                <div class="text-sm font-medium text-gray-900">{{ size.label }}</div>
                                <div class="text-xs text-gray-500 mt-1">{{ size.description }}</div>
                            </div>
                        </div>
                    </div>
                    <div v-if="errors.company_size" class="mt-2 text-sm text-red-600">
                        {{ errors.company_size }}
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">
                            Phone Number
                        </label>
                        <input
                            id="phone"
                            v-model="form.contact_info.phone"
                            type="tel"
                            placeholder="+265 XXX XXX XXX"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                        />
                        <div v-if="errors['contact_info.phone']" class="mt-2 text-sm text-red-600">
                            {{ errors['contact_info.phone'] }}
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            Business Email
                        </label>
                        <input
                            id="email"
                            v-model="form.contact_info.email"
                            type="email"
                            placeholder="hello@yourcompany.com"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                        />
                        <div v-if="errors['contact_info.email']" class="mt-2 text-sm text-red-600">
                            {{ errors['contact_info.email'] }}
                        </div>
                    </div>
                </div>

                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700">
                        Business Address (Optional)
                    </label>
                    <textarea
                        id="address"
                        v-model="form.contact_info.address"
                        rows="2"
                        placeholder="Street address, city, region"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    ></textarea>
                </div>

                <div>
                    <label for="website" class="block text-sm font-medium text-gray-700">
                        Website (Optional)
                    </label>
                    <input
                        id="website"
                        v-model="form.contact_info.website"
                        type="url"
                        placeholder="https://www.yourcompany.com"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>

                <!-- Actions -->
                <div class="flex justify-between pt-6 border-t">
                    <button
                        type="button"
                        class="px-4 py-2 text-gray-600 font-medium rounded-lg hover:bg-gray-100 transition-colors"
                    >
                        ← Back
                    </button>

                    <button
                        type="submit"
                        :disabled="!canSubmit || loading"
                        class="px-8 py-2 bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold rounded-lg hover:from-indigo-600 hover:to-purple-700 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="!loading">Continue →</span>
                        <span v-else class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Saving...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    tenant: Object,
})

const loading = ref(false)
const errors = ref({})

const form = ref({
    business_type: '',
    industry: '',
    company_size: '',
    contact_info: {
        phone: '',
        email: '',
        address: '',
        website: ''
    }
})

const businessTypeOptions = [
    {
        value: 'advertising_agency',
        label: 'Advertising Agency',
        description: 'I help clients find billboard space',
        icon: '🎯'
    },
    {
        value: 'billboard_owner',
        label: 'Billboard Owner',
        description: 'I own billboards to rent out',
        icon: '📊'
    },
    {
        value: 'hybrid',
        label: 'Both',
        description: 'I do both advertising and own billboards',
        icon: '🚀'
    }
]

const industries = [
    'Advertising & Marketing',
    'Media & Entertainment',
    'Outdoor Advertising',
    'Real Estate',
    'Retail & Commerce',
    'Technology',
    'Transportation',
    'Other'
]

const companySizes = [
    {
        value: '1-10',
        label: '1-10 employees',
        description: 'Small business'
    },
    {
        value: '11-50',
        label: '11-50 employees',
        description: 'Growing company'
    },
    {
        value: '51-200',
        label: '51-200 employees',
        description: 'Medium company'
    },
    {
        value: '200+',
        label: '200+ employees',
        description: 'Large enterprise'
    }
]

const canSubmit = computed(() => {
    return form.value.business_type &&
           form.value.industry &&
           form.value.company_size &&
           form.value.contact_info.phone &&
           form.value.contact_info.email
})

const submitProfile = async () => {
    if (!canSubmit.value) return

    loading.value = true
    errors.value = {}

    try {
        await router.post(route('tenant.onboarding.profile', {
            tenant: props.tenant.uuid
        }), form.value, {
            onSuccess: () => {
                emit('complete')
            },
            onError: (responseErrors) => {
                errors.value = responseErrors
            },
            onFinish: () => {
                loading.value = false
            }
        })
    } catch (error) {
        console.error('Error completing profile setup:', error)
        loading.value = false
    }
}

const emit = defineEmits(['complete'])
</script>