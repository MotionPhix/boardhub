<template>
  <div class="flex items-center justify-between px-6 py-4 bg-gray-50 border-t border-gray-200 gap-4">
    <div class="flex-1">
      <slot name="left" />
    </div>

    <div class="flex items-center space-x-3">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
// Fixed footer with consistent button placement and styling
// Left slot for additional content, default slot for action buttons
</script>