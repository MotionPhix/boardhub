<script setup lang="ts">
interface Props {
  for?: string
  text?: string
  required?: boolean
  disabled?: boolean
  size?: 'sm' | 'md' | 'lg'
  customClass?: string
}

withDefaults(defineProps<Props>(), {
  size: 'md',
  customClass: ''
})
</script>

<template>
  <label
    :for="for"
    class="block text-sm font-medium text-gray-900 dark:text-gray-300"
    :class="[
      required ? 'after:content-[\'*\'] after:ml-0.5 after:text-red-500' : '',
      disabled ? 'text-gray-500 dark:text-gray-500 cursor-not-allowed' : 'cursor-pointer',
      size === 'sm' ? 'text-xs' : '',
      size === 'lg' ? 'text-base' : '',
      customClass
    ]"
  >
    <slot>{{ text }}</slot>
  </label>
</template>