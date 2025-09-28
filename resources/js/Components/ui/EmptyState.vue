<template>
  <div class="text-center py-12">
    <div v-if="icon" class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4">
      <component :is="icon" class="h-full w-full" />
    </div>

    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ title }}</h3>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ description }}</p>

    <div v-if="$slots.action" class="mt-6">
      <slot name="action" />
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  title: string
  description: string
  icon?: any
}

defineProps<Props>()
</script>