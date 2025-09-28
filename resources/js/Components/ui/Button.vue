<template>
  <component
    :is="as"
    :type="as === 'button' ? type : undefined"
    :href="as === 'a' ? href : undefined"
    :class="buttonClasses"
    :disabled="disabled || loading"
    v-bind="$attrs"
    @click="handleClick"
  >
    <component
      v-if="loading"
      :is="Loader2"
      class="animate-spin mr-2 h-4 w-4"
    />
    <component
      v-else-if="icon && iconPosition === 'left'"
      :is="icon"
      class="mr-2 h-4 w-4"
    />

    <slot />

    <component
      v-if="!loading && icon && iconPosition === 'right'"
      :is="icon"
      class="ml-2 h-4 w-4"
    />
  </component>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Loader2 } from 'lucide-vue-next'

interface Props {
  variant?: 'primary' | 'secondary' | 'outline' | 'ghost' | 'destructive' | 'link'
  size?: 'sm' | 'md' | 'lg'
  as?: 'button' | 'a'
  type?: 'button' | 'submit' | 'reset'
  href?: string
  disabled?: boolean
  loading?: boolean
  icon?: any
  iconPosition?: 'left' | 'right'
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  as: 'button',
  type: 'button',
  disabled: false,
  loading: false,
  iconPosition: 'left'
})

const emit = defineEmits<{
  click: [event: Event]
}>()

const baseClasses = 'inline-flex items-center justify-center rounded-md font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50'

const variantClasses = {
  primary: 'bg-indigo-600 dark:bg-indigo-500 text-white hover:bg-indigo-700 dark:hover:bg-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-400',
  secondary: 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-600 focus:ring-gray-500 dark:focus:ring-gray-400',
  outline: 'border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:ring-indigo-500 dark:focus:ring-indigo-400',
  ghost: 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:ring-gray-500 dark:focus:ring-gray-400',
  destructive: 'bg-red-600 dark:bg-red-500 text-white hover:bg-red-700 dark:hover:bg-red-600 focus:ring-red-500 dark:focus:ring-red-400',
  link: 'text-indigo-600 dark:text-indigo-400 underline-offset-4 hover:underline focus:ring-indigo-500 dark:focus:ring-indigo-400'
}

const sizeClasses = {
  sm: 'h-8 px-3 text-sm',
  md: 'h-10 px-4 py-2',
  lg: 'h-12 px-8 text-lg'
}

const buttonClasses = computed(() => [
  baseClasses,
  variantClasses[props.variant],
  sizeClasses[props.size],
  props.loading && 'cursor-not-allowed',
  props.class
].filter(Boolean).join(' '))

const handleClick = (event: Event) => {
  if (!props.disabled && !props.loading) {
    emit('click', event)
  }
}
</script>