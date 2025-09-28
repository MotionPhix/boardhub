<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  id?: string
  name?: string
  label?: string
  description?: string
  value?: string | number | boolean
  modelValue?: boolean | string[] | number[]
  required?: boolean
  disabled?: boolean
  error?: string
  customClass?: string
}

interface Emits {
  (e: 'update:modelValue', value: boolean | string[] | number[]): void
}

const props = withDefaults(defineProps<Props>(), {
  id: () => `checkbox-${Math.random().toString(36).substr(2, 9)}`,
  value: true,
  modelValue: false,
  customClass: ''
})

const emit = defineEmits<Emits>()

const model = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})
</script>

<template>
  <div class="relative flex items-center">
    <input
      :id="id"
      v-model="model"
      :name="name"
      :value="value"
      :required="required"
      :disabled="disabled"
      type="checkbox"
      class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:ring-2 disabled:opacity-50 disabled:cursor-not-allowed"
      :class="[
        error ? 'border-red-500 dark:border-red-400' : '',
        customClass
      ]"
    />
    <div v-if="$slots.default || label" class="ml-2 flex-1">
      <label
        v-if="label"
        :for="id"
        class="text-sm font-medium text-gray-900 dark:text-gray-300"
        :class="{ 'text-gray-500 dark:text-gray-500': disabled }"
      >
        {{ label }}
      </label>
      <slot />
      <p v-if="description" class="text-xs text-gray-500 dark:text-gray-400">
        {{ description }}
      </p>
      <p v-if="error" class="mt-1 text-xs text-red-600 dark:text-red-400">
        {{ error }}
      </p>
    </div>
  </div>
</template>