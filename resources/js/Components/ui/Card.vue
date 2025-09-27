<template>
  <div :class="cardClasses">
    <div v-if="$slots.header" :class="headerClasses">
      <slot name="header" />
    </div>

    <div :class="contentClasses">
      <slot />
    </div>

    <div v-if="$slots.footer" :class="footerClasses">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  variant?: 'default' | 'outlined' | 'elevated' | 'filled'
  padding?: 'none' | 'sm' | 'md' | 'lg'
  rounded?: 'sm' | 'md' | 'lg' | 'xl'
  shadow?: 'none' | 'sm' | 'md' | 'lg' | 'xl'
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
  padding: 'md',
  rounded: 'lg',
  shadow: 'md'
})

const baseClasses = 'bg-white overflow-hidden'

const variantClasses = {
  default: 'border border-gray-200',
  outlined: 'border-2 border-gray-200',
  elevated: 'border-0',
  filled: 'bg-gray-50 border border-gray-200'
}

const roundedClasses = {
  sm: 'rounded-sm',
  md: 'rounded-md',
  lg: 'rounded-lg',
  xl: 'rounded-xl'
}

const shadowClasses = {
  none: '',
  sm: 'shadow-sm',
  md: 'shadow',
  lg: 'shadow-lg',
  xl: 'shadow-xl'
}

const paddingClasses = {
  none: '',
  sm: 'p-3',
  md: 'p-4',
  lg: 'p-6'
}

const cardClasses = computed(() => [
  baseClasses,
  variantClasses[props.variant],
  roundedClasses[props.rounded],
  shadowClasses[props.shadow],
  props.class
].filter(Boolean).join(' '))

const headerClasses = computed(() => [
  'border-b border-gray-200 bg-gray-50',
  paddingClasses[props.padding]
].filter(Boolean).join(' '))

const contentClasses = computed(() => [
  paddingClasses[props.padding]
].filter(Boolean).join(' '))

const footerClasses = computed(() => [
  'border-t border-gray-200 bg-gray-50',
  paddingClasses[props.padding]
].filter(Boolean).join(' '))
</script>