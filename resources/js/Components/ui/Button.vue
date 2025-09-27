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
  primary: 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500',
  secondary: 'bg-gray-100 text-gray-900 hover:bg-gray-200 focus:ring-gray-500',
  outline: 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:ring-indigo-500',
  ghost: 'text-gray-700 hover:bg-gray-100 focus:ring-gray-500',
  destructive: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
  link: 'text-indigo-600 underline-offset-4 hover:underline focus:ring-indigo-500'
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