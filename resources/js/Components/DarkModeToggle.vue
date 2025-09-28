<template>
  <button
    @click="toggleDarkMode"
    class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400"
    :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
    aria-label="Toggle dark mode"
  >
    <Sun
      v-if="isDark"
      class="h-5 w-5 text-yellow-500"
    />
    <Moon
      v-else
      class="h-5 w-5 text-gray-700 dark:text-gray-300"
    />
  </button>
</template>

<script setup lang="ts">
import { Sun, Moon } from 'lucide-vue-next'
import { useDarkMode } from '../composables/useDarkMode'

const { isDark, toggleDarkMode } = useDarkMode()
</script>