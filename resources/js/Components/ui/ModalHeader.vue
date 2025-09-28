<template>
  <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
    <div class="flex items-center space-x-3">
      <div
        v-if="icon"
        :class="[
          'flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-lg',
          iconBackgroundClass
        ]"
      >
        <component :is="icon" :class="iconClass" />
      </div>
      <div>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
          {{ title }}
        </h2>
        <p v-if="description" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          {{ description }}
        </p>
      </div>
    </div>

    <button
      v-if="showCloseButton"
      type="button"
      @click="onClose"
      class="rounded-md bg-white dark:bg-gray-800 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:ring-offset-2"
    >
      <span class="sr-only">Close</span>
      <X class="h-6 w-6" />
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { X } from 'lucide-vue-next'

interface Props {
  title: string
  description?: string
  icon?: any
  iconVariant?: 'primary' | 'success' | 'warning' | 'danger' | 'info'
  showCloseButton?: boolean
  onClose?: () => void
}

const props = withDefaults(defineProps<Props>(), {
  iconVariant: 'primary',
  showCloseButton: true
})

const iconBackgroundClass = computed(() => {
  const variants = {
    primary: 'bg-indigo-100 dark:bg-indigo-900',
    success: 'bg-green-100 dark:bg-green-900',
    warning: 'bg-yellow-100 dark:bg-yellow-900',
    danger: 'bg-red-100 dark:bg-red-900',
    info: 'bg-blue-100 dark:bg-blue-900'
  }
  return variants[props.iconVariant]
})

const iconClass = computed(() => {
  const variants = {
    primary: 'h-6 w-6 text-indigo-600 dark:text-indigo-400',
    success: 'h-6 w-6 text-green-600 dark:text-green-400',
    warning: 'h-6 w-6 text-yellow-600 dark:text-yellow-400',
    danger: 'h-6 w-6 text-red-600 dark:text-red-400',
    info: 'h-6 w-6 text-blue-600 dark:text-blue-400'
  }
  return variants[props.iconVariant]
})
</script>