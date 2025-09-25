<template>
    <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-cyan-50">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0">
                            <img class="h-8 w-8" src="/images/logo.svg" alt="AdPro" />
                        </div>
                        <div>
                            <h1 class="text-xl font-semibold text-gray-900">Welcome to AdPro</h1>
                            <p class="text-sm text-gray-500">{{ tenant.name }}</p>
                        </div>
                    </div>
                    <div class="text-sm text-gray-500">
                        Step {{ currentStepIndex + 1 }} of {{ totalSteps }}
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Progress Bar -->
            <div class="mb-8">
                <div class="relative">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-indigo-600 uppercase tracking-wide">
                            Setup Progress
                        </span>
                        <span class="text-sm font-medium text-gray-900">
                            {{ Math.round(tenant.progress) }}% Complete
                        </span>
                    </div>
                    <div class="overflow-hidden h-2 text-xs flex rounded-full bg-gray-200">
                        <div
                            :style="{ width: tenant.progress + '%' }"
                            class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-gradient-to-r from-indigo-500 to-purple-600 transition-all duration-500 ease-out"
                        ></div>
                    </div>
                </div>

                <!-- Step Indicators -->
                <div class="mt-6 flex items-center justify-between">
                    <div
                        v-for="(step, index) in stepList"
                        :key="step.key"
                        class="flex items-center"
                        :class="{ 'flex-1': index < stepList.length - 1 }"
                    >
                        <div class="relative flex items-center">
                            <div
                                class="flex items-center justify-center w-8 h-8 rounded-full border-2 transition-all duration-200"
                                :class="getStepIndicatorClass(step, index)"
                            >
                                <span v-if="isStepCompleted(step.key)" class="text-white">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                <span v-else-if="isCurrentStep(step.key)" class="text-white font-semibold">
                                    {{ index + 1 }}
                                </span>
                                <span v-else class="text-gray-400">{{ index + 1 }}</span>
                            </div>
                            <div v-if="index < stepList.length - 1" class="flex-1 h-0.5 bg-gray-200 ml-2">
                                <div
                                    class="h-full bg-gradient-to-r from-indigo-500 to-purple-600 transition-all duration-500"
                                    :style="{ width: getConnectorWidth(index) }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Step Content -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <!-- Welcome Step -->
                <WelcomeStep
                    v-if="tenant.current_step === 'welcome_completed'"
                    :tenant="tenant"
                    @complete="completeStep"
                />

                <!-- Profile Setup Step -->
                <ProfileSetupStep
                    v-else-if="tenant.current_step === 'profile_setup'"
                    :tenant="tenant"
                    @complete="completeStep"
                />

                <!-- Branding Step -->
                <BrandingStep
                    v-else-if="tenant.current_step === 'branding_configured'"
                    :tenant="tenant"
                    @complete="completeStep"
                    @skip="skipStep"
                />

                <!-- First Billboard Step -->
                <FirstBillboardStep
                    v-else-if="tenant.current_step === 'first_billboard_added'"
                    :tenant="tenant"
                    @complete="completeStep"
                />

                <!-- First Client Step -->
                <FirstClientStep
                    v-else-if="tenant.current_step === 'first_client_added'"
                    :tenant="tenant"
                    @complete="completeStep"
                />

                <!-- Team Invitation Step -->
                <TeamInviteStep
                    v-else-if="tenant.current_step === 'team_invited'"
                    :tenant="tenant"
                    @complete="completeStep"
                    @skip="skipStep"
                />

                <!-- Payment Setup Step -->
                <PaymentSetupStep
                    v-else-if="tenant.current_step === 'payment_configured'"
                    :tenant="tenant"
                    @complete="completeStep"
                    @skip="skipStep"
                />

                <!-- Completion Step -->
                <CompletionStep
                    v-else
                    :tenant="tenant"
                />
            </div>

            <!-- Help Section -->
            <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Need help?</h3>
                        <p class="mt-1 text-sm text-blue-700">
                            Our setup wizard will guide you through each step. You can always come back to complete any step later.
                        </p>
                        <div class="mt-3">
                            <button class="text-sm font-medium text-blue-800 hover:text-blue-600">
                                Contact Support →
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import WelcomeStep from './Steps/WelcomeStep.vue'
import ProfileSetupStep from './Steps/ProfileSetupStep.vue'
import BrandingStep from './Steps/BrandingStep.vue'
import FirstBillboardStep from './Steps/FirstBillboardStep.vue'
import FirstClientStep from './Steps/FirstClientStep.vue'
import TeamInviteStep from './Steps/TeamInviteStep.vue'
import PaymentSetupStep from './Steps/PaymentSetupStep.vue'
import CompletionStep from './Steps/CompletionStep.vue'

const props = defineProps({
    tenant: Object,
    steps: Object,
})

const stepList = computed(() => {
    return Object.entries(props.steps).map(([key, step]) => ({
        key,
        ...step
    }))
})

const totalSteps = computed(() => stepList.value.length)

const currentStepIndex = computed(() => {
    return stepList.value.findIndex(step => step.key === props.tenant.current_step)
})

const isStepCompleted = (stepKey) => {
    return props.tenant.onboarding_progress?.[stepKey] === true
}

const isCurrentStep = (stepKey) => {
    return props.tenant.current_step === stepKey
}

const getStepIndicatorClass = (step, index) => {
    if (isStepCompleted(step.key)) {
        return 'bg-gradient-to-r from-indigo-500 to-purple-600 border-transparent'
    } else if (isCurrentStep(step.key)) {
        return 'bg-gradient-to-r from-indigo-500 to-purple-600 border-transparent'
    } else {
        return 'bg-white border-gray-300'
    }
}

const getConnectorWidth = (index) => {
    const nextStep = stepList.value[index + 1]
    if (nextStep && isStepCompleted(nextStep.key)) {
        return '100%'
    } else if (nextStep && isCurrentStep(nextStep.key)) {
        return '50%'
    } else {
        return '0%'
    }
}

const completeStep = (stepData = {}) => {
    // Refresh the page data to get updated progress
    router.reload({ only: ['tenant'] })
}

const skipStep = (stepKey) => {
    router.post(route('tenant.onboarding.skip', {
        tenant: props.tenant.uuid,
        step: stepKey
    }), {}, {
        onSuccess: () => {
            router.reload({ only: ['tenant'] })
        }
    })
}
</script>

<style scoped>
/* Custom animations for the progress indicators */
.step-indicator {
    transition: all 0.3s ease;
}

.step-indicator.completed {
    animation: pulse 0.5s ease-in-out;
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
    }
}
</style>