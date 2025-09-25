<template>
    <div class="p-8 text-center">
        <!-- Hero Section -->
        <div class="max-w-2xl mx-auto">
            <div class="mb-6">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-3xl">
                    👋
                </div>
            </div>

            <h2 class="text-3xl font-bold text-gray-900 mb-4">
                Welcome to AdPro, {{ tenant.name }}!
            </h2>

            <p class="text-lg text-gray-600 mb-8">
                You're about to transform how billboard advertising works in your market.
                Let's get you set up with a few quick steps.
            </p>

            <!-- Feature Preview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-gradient-to-br from-blue-50 to-indigo-100 rounded-lg p-6 border border-blue-200">
                    <div class="w-12 h-12 mx-auto mb-4 bg-blue-500 rounded-lg flex items-center justify-center text-white text-xl">
                        📊
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">Smart Dashboard</h3>
                    <p class="text-sm text-gray-600">Real-time analytics and performance tracking for all your billboards</p>
                </div>

                <div class="bg-gradient-to-br from-green-50 to-emerald-100 rounded-lg p-6 border border-green-200">
                    <div class="w-12 h-12 mx-auto mb-4 bg-green-500 rounded-lg flex items-center justify-center text-white text-xl">
                        💳
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">Mobile Payments</h3>
                    <p class="text-sm text-gray-600">Airtel Money & TNM Mpamba integration for seamless transactions</p>
                </div>

                <div class="bg-gradient-to-br from-purple-50 to-violet-100 rounded-lg p-6 border border-purple-200">
                    <div class="w-12 h-12 mx-auto mb-4 bg-purple-500 rounded-lg flex items-center justify-center text-white text-xl">
                        🚀
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">AI Insights</h3>
                    <p class="text-sm text-gray-600">Predictive pricing and demand forecasting to maximize revenue</p>
                </div>
            </div>

            <!-- Getting Started Timeline -->
            <div class="bg-gray-50 rounded-lg p-6 mb-8">
                <h3 class="font-semibold text-gray-900 mb-4">What we'll set up together:</h3>
                <div class="space-y-3 text-left">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0 w-6 h-6 bg-indigo-500 rounded-full flex items-center justify-center text-white text-xs font-semibold">1</div>
                        <p class="text-sm text-gray-700">Your business profile and preferences</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0 w-6 h-6 bg-indigo-500 rounded-full flex items-center justify-center text-white text-xs font-semibold">2</div>
                        <p class="text-sm text-gray-700">Custom branding and platform appearance</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0 w-6 h-6 bg-indigo-500 rounded-full flex items-center justify-center text-white text-xs font-semibold">3</div>
                        <p class="text-sm text-gray-700">Your first billboard and client records</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0 w-6 h-6 bg-indigo-500 rounded-full flex items-center justify-center text-white text-xs font-semibold">4</div>
                        <p class="text-sm text-gray-700">Team access and payment methods</p>
                    </div>
                </div>
            </div>

            <!-- CTA -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button
                    @click="startOnboarding"
                    :disabled="loading"
                    class="px-8 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold rounded-lg hover:from-indigo-600 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="!loading" class="flex items-center">
                        Let's Get Started
                        <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </span>
                    <span v-else class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Setting up...
                    </span>
                </button>

                <button
                    @click="skipForNow"
                    class="px-8 py-3 text-gray-600 font-medium rounded-lg hover:bg-gray-100 transition-colors"
                >
                    I'll set this up later
                </button>
            </div>

            <!-- Trust Indicators -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <p class="text-xs text-gray-500 mb-3">Trusted by leading billboard companies across Africa</p>
                <div class="flex items-center justify-center space-x-6 opacity-60">
                    <div class="text-xs font-semibold text-gray-400">BILLBOARD CO.</div>
                    <div class="text-xs font-semibold text-gray-400">OUTDOOR MEDIA</div>
                    <div class="text-xs font-semibold text-gray-400">CITY SIGNS</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    tenant: Object,
})

const loading = ref(false)

const startOnboarding = async () => {
    loading.value = true

    try {
        await router.post(route('tenant.onboarding.welcome', {
            tenant: props.tenant.uuid
        }), {}, {
            onSuccess: () => {
                props.emit('complete')
            },
            onError: (errors) => {
                console.error('Error completing welcome step:', errors)
            },
            onFinish: () => {
                loading.value = false
            }
        })
    } catch (error) {
        console.error('Error starting onboarding:', error)
        loading.value = false
    }
}

const skipForNow = () => {
    // Redirect to dashboard with onboarding banner
    router.visit(route('tenant.dashboard', { tenant: props.tenant.uuid }))
}

const emit = defineEmits(['complete'])
</script>