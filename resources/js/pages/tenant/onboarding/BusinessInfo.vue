<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import TenantLayout from '@/layouts/TenantLayout.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
import Select from '@/components/ui/Select.vue'
import Textarea from '@/components/ui/Textarea.vue'
import Label from '@/components/ui/Label.vue'
import InputError from '@/components/ui/InputError.vue'
import { Building2, ArrowRight, ArrowLeft } from 'lucide-vue-next'

interface Tenant {
  id: number
  name: string
  business_type?: string
  industry?: string
  company_size?: string
  contact_info: {
    phone?: string
    address?: string
    city?: string
    country?: string
    website?: string
  }
}

interface Props {
  tenant: Tenant
  business_types: Record<string, string>
  industries: Record<string, string>
  company_sizes: Record<string, string>
}

const props = defineProps<Props>()

const form = useForm({
  business_type: props.tenant.business_type || '',
  industry: props.tenant.industry || '',
  company_size: props.tenant.company_size || '',
  contact_info: {
    phone: props.tenant.contact_info?.phone || '',
    address: props.tenant.contact_info?.address || '',
    city: props.tenant.contact_info?.city || '',
    country: props.tenant.contact_info?.country || '',
    website: props.tenant.contact_info?.website || '',
  }
})

const isSubmitting = ref(false)

const submit = () => {
  if (isSubmitting.value) return

  isSubmitting.value = true
  form.post(route('tenant.onboarding.business-info.update'), {
    onFinish: () => {
      isSubmitting.value = false
    }
  })
}

const skip = () => {
  form.post(route('tenant.onboarding.skip'), {
    data: { step: 'business_info' }
  })
}
</script>

<template>
  <TenantLayout>
    <Head :title="`Business Information - ${tenant.name}`" />

    <div class="max-w-2xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-8">
        <div class="flex justify-center mb-4">
          <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center">
            <Building2 class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
          </div>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
          Tell us about your business
        </h1>
        <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">
          This helps us customize your experience and provide relevant billboard recommendations.
        </p>
      </div>

      <!-- Form -->
      <Card class="p-6">
        <form @submit.prevent="submit" class="space-y-6">
          <!-- Business Type -->
          <div>
            <Label for="business_type" required>Business Type</Label>
            <Select
              id="business_type"
              v-model="form.business_type"
              :options="business_types"
              placeholder="Select your business type"
              required
            />
            <InputError :message="form.errors.business_type" />
          </div>

          <!-- Industry -->
          <div>
            <Label for="industry" required>Industry</Label>
            <Select
              id="industry"
              v-model="form.industry"
              :options="industries"
              placeholder="Select your industry"
              required
            />
            <InputError :message="form.errors.industry" />
          </div>

          <!-- Company Size -->
          <div>
            <Label for="company_size" required>Company Size</Label>
            <Select
              id="company_size"
              v-model="form.company_size"
              :options="company_sizes"
              placeholder="Select your company size"
              required
            />
            <InputError :message="form.errors.company_size" />
          </div>

          <!-- Contact Information -->
          <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
              Contact Information
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Phone -->
              <div>
                <Label for="phone">Phone Number</Label>
                <Input
                  id="phone"
                  v-model="form.contact_info.phone"
                  type="tel"
                  placeholder="+260 XXX XXX XXX"
                />
                <InputError :message="form.errors['contact_info.phone']" />
              </div>

              <!-- Website -->
              <div>
                <Label for="website">Website</Label>
                <Input
                  id="website"
                  v-model="form.contact_info.website"
                  type="url"
                  placeholder="https://example.com"
                />
                <InputError :message="form.errors['contact_info.website']" />
              </div>
            </div>

            <!-- Address -->
            <div class="mt-4">
              <Label for="address">Business Address</Label>
              <Textarea
                id="address"
                v-model="form.contact_info.address"
                rows="3"
                placeholder="Enter your business address"
              />
              <InputError :message="form.errors['contact_info.address']" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
              <!-- City -->
              <div>
                <Label for="city">City</Label>
                <Input
                  id="city"
                  v-model="form.contact_info.city"
                  placeholder="Lusaka"
                />
                <InputError :message="form.errors['contact_info.city']" />
              </div>

              <!-- Country -->
              <div>
                <Label for="country">Country</Label>
                <Input
                  id="country"
                  v-model="form.contact_info.country"
                  placeholder="Zambia"
                />
                <InputError :message="form.errors['contact_info.country']" />
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
            <Button
              :href="route('tenant.onboarding.index')"
              variant="outline"
            >
              <ArrowLeft class="w-4 h-4 mr-2" />
              Back
            </Button>

            <div class="flex items-center space-x-3">
              <Button
                type="button"
                variant="ghost"
                @click="skip"
                :disabled="form.processing"
              >
                Skip for now
              </Button>

              <Button
                type="submit"
                :disabled="form.processing || isSubmitting"
                :loading="form.processing || isSubmitting"
              >
                Continue
                <ArrowRight class="w-4 h-4 ml-2" />
              </Button>
            </div>
          </div>
        </form>
      </Card>

      <!-- Help Text -->
      <div class="mt-6 text-center">
        <p class="text-sm text-gray-500 dark:text-gray-400">
          This information helps us provide better billboard recommendations and customize your dashboard.
          You can update these details anytime from your organization settings.
        </p>
      </div>
    </div>
  </TenantLayout>
</template>