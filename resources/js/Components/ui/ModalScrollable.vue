<template>
  <div
    class="flex-1 overflow-y-auto px-6 py-4 custom-scrollbar"
    :class="class"
  >
    <slot />
  </div>
</template>

<script setup lang="ts">
interface Props {
  class?: string
}

defineProps<Props>()
</script>

<style scoped>
.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: rgb(156 163 175) transparent;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: rgb(156 163 175);
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: rgb(107 114 128);
}
</style>