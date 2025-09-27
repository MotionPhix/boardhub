<template>
  <div v-if="message" class="flex items-center space-x-2">
    <AlertCircle class="h-4 w-4 text-red-500 flex-shrink-0" />
    <p class="text-sm text-red-600">{{ message }}</p>
  </div>
</template>

<script setup lang="ts">
import { AlertCircle } from 'lucide-vue-next'

interface Props {
  message?: string
}

defineProps<Props>()
</script>