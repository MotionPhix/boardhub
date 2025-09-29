<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import TenantLayout from '@/layouts/TenantLayout.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'
import InputError from '@/components/ui/InputError.vue'
import { Palette, ArrowRight, ArrowLeft, Upload, Eye } from 'lucide-vue-next'

interface Tenant {
  id: number
  name: string
  logo_url?: string
  primary_color: string
  secondary_color: string
  branding_settings: Record<string, any>
}

interface Props {
  tenant: Tenant
}

const props = defineProps<Props>()

const form = useForm({
  logo_url: props.tenant.logo_url || '',
  primary_color: props.tenant.primary_color || '#6366f1',
  secondary_color: props.tenant.secondary_color || '#8b5cf6',
  branding_settings: props.tenant.branding_settings || {}
})

const isSubmitting = ref(false)

const previewStyle = computed(() => ({
  '--primary-color': form.primary_color,
  '--secondary-color': form.secondary_color
}))

const submit = () => {
  if (isSubmitting.value) return

  isSubmitting.value = true
  form.post(route('tenant.onboarding.branding.update'), {
    onFinish: () => {
      isSubmitting.value = false
    }
  })
}

const skip = () => {
  form.post(route('tenant.onboarding.skip'), {
    data: { step: 'branding' }
  })
}

const predefinedColors = [
  { name: 'Indigo', primary: '#6366f1', secondary: '#8b5cf6' },
  { name: 'Blue', primary: '#3b82f6', secondary: '#06b6d4' },
  { name: 'Green', primary: '#10b981', secondary: '#059669' },
  { name: 'Purple', primary: '#8b5cf6', secondary: '#a855f7' },
  { name: 'Pink', primary: '#ec4899', secondary: '#f43f5e' },
  { name: 'Orange', primary: '#f59e0b', secondary: '#ea580c' },
  { name: 'Red', primary: '#ef4444', secondary: '#dc2626' },
  { name: 'Teal', primary: '#14b8a6', secondary: '#0d9488' }
]

const applyColorScheme = (scheme: any) => {
  form.primary_color = scheme.primary
  form.secondary_color = scheme.secondary
}
</script>

<template>
  <TenantLayout>
    <Head :title="`Branding - ${tenant.name}`" />

    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-8">
        <div class="flex justify-center mb-4">
          <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center">
            <Palette class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
          </div>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
          Customize your branding
        </h1>
        <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">
          Make your organization stand out with custom colors and logo. This step is optional.
        </p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Branding Form -->
        <div class="space-y-6">
          <Card class="p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
              Brand Settings
            </h2>

            <form @submit.prevent="submit" class="space-y-6">
              <!-- Logo URL -->
              <div>
                <Label for="logo_url">Logo URL</Label>
                <div class="mt-1 flex rounded-md shadow-sm">
                  <Input
                    id="logo_url"
                    v-model="form.logo_url"
                    type="url"
                    placeholder="https://example.com/logo.png"
                    class="flex-1"
                  />
                  <Button
                    type="button"
                    variant="outline"
                    class="ml-2"
                    disabled
                  >
                    <Upload class="w-4 h-4" />
                  </Button>
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                  Enter a URL to your logo image, or upload one later from settings.
                </p>
                <InputError :message="form.errors.logo_url" />
              </div>

              <!-- Color Scheme -->
              <div>
                <Label>Brand Colors</Label>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                  Choose colors that represent your brand
                </p>

                <!-- Predefined Color Schemes -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-4">
                  <button
                    v-for="scheme in predefinedColors"
                    :key="scheme.name"
                    type="button"
                    @click="applyColorScheme(scheme)"
                    class="flex items-center space-x-2 p-2 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                  >
                    <div class="flex space-x-1">
                      <div
                        class="w-4 h-4 rounded-full"
                        :style="{ backgroundColor: scheme.primary }"
                      ></div>
                      <div
                        class="w-4 h-4 rounded-full"
                        :style="{ backgroundColor: scheme.secondary }"
                      ></div>
                    </div>
                    <span class="text-xs text-gray-700 dark:text-gray-300">{{ scheme.name }}</span>
                  </button>
                </div>

                <!-- Custom Colors -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <Label for="primary_color">Primary Color</Label>
                    <div class="mt-1 flex items-center space-x-2">
                      <Input
                        id="primary_color"
                        v-model="form.primary_color"
                        type="color"
                        class="w-12 h-10 p-1 border-none"
                      />
                      <Input
                        v-model="form.primary_color"
                        type="text"
                        placeholder="#6366f1"
                        class="flex-1"
                      />
                    </div>
                    <InputError :message="form.errors.primary_color" />
                  </div>

                  <div>
                    <Label for="secondary_color">Secondary Color</Label>
                    <div class="mt-1 flex items-center space-x-2">
                      <Input
                        id="secondary_color"
                        v-model="form.secondary_color"
                        type="color"
                        class="w-12 h-10 p-1 border-none"
                      />
                      <Input
                        v-model="form.secondary_color"
                        type="text"
                        placeholder="#8b5cf6"
                        class="flex-1"
                      />
                    </div>
                    <InputError :message="form.errors.secondary_color" />
                  </div>
                </div>
              </div>
            </form>
          </Card>
        </div>

        <!-- Preview -->
        <Card class="p-6">
          <div class="flex items-center mb-6">
            <Eye class="w-5 h-5 text-gray-400 mr-2" />
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
              Preview
            </h2>
          </div>

          <div class="space-y-4" :style="previewStyle">
            <!-- Logo Preview -->
            <div v-if="form.logo_url" class="text-center">
              <img
                :src="form.logo_url"
                :alt="`${tenant.name} logo`"
                class="h-16 mx-auto object-contain"
                @error="form.logo_url = ''"
              />
            </div>
            <div v-else class="text-center">
              <div class="w-16 h-16 mx-auto bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                <Palette class="w-8 h-8 text-gray-400" />
              </div>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">No logo</p>
            </div>

            <!-- Color Preview -->
            <div class="space-y-3">
              <div
                class="h-12 rounded-lg flex items-center justify-center text-white font-medium"
                :style="{ backgroundColor: form.primary_color }"
              >
                Primary Color
              </div>

              <div
                class="h-12 rounded-lg flex items-center justify-center text-white font-medium"
                :style="{ backgroundColor: form.secondary_color }"
              >
                Secondary Color
              </div>

              <!-- Sample UI Elements -->
              <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <h3 class="font-medium text-gray-900 dark:text-white mb-2">Sample Dashboard</h3>
                <div class="space-y-2">
                  <div
                    class="h-8 rounded text-white text-sm flex items-center justify-center"
                    :style="{ backgroundColor: form.primary_color }"
                  >
                    Primary Button
                  </div>
                  <div
                    class="h-6 rounded"
                    :style="{ backgroundColor: form.secondary_color, opacity: 0.7 }"
                  ></div>
                  <div
                    class="h-4 rounded"
                    :style="{ backgroundColor: form.primary_color, opacity: 0.3 }"
                  ></div>
                </div>
              </div>
            </div>
          </div>
        </Card>
      </div>

      <!-- Form Actions -->
      <div class="flex items-center justify-between mt-8">
        <Button
          :href="route('tenant.onboarding.team-setup')"
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
            @click="submit"
            :disabled="form.processing || isSubmitting"
            :loading="form.processing || isSubmitting"
          >
            Complete Setup
            <ArrowRight class="w-4 h-4 ml-2" />
          </Button>
        </div>
      </div>
    </div>
  </TenantLayout>
</template>