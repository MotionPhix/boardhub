<template>
  <div>
    <div class="border-b border-gray-200 dark:border-gray-700">
      <nav class="-mb-px flex space-x-8" aria-label="Tabs">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          @click="setActiveTab(tab.key)"
          :class="getTabClasses(tab)"
          :aria-selected="tab.key === activeTab"
          type="button"
        >
          <component
            v-if="tab.icon"
            :is="tab.icon"
            :class="getTabIconClasses(tab)"
          />
          {{ tab.label }}
          <span
            v-if="tab.badge"
            :class="getTabBadgeClasses(tab)"
          >
            {{ tab.badge }}
          </span>
        </button>
      </nav>
    </div>

    <div class="mt-4">
      <slot :active-tab="activeTab" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'

interface Tab {
  key: string
  label: string
  icon?: any
  badge?: string | number
  disabled?: boolean
}

interface Props {
  tabs: Tab[]
  modelValue?: string
  variant?: 'default' | 'pills'
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default'
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  'change': [tab: Tab]
}>()

const activeTab = ref(props.modelValue || props.tabs[0]?.key)

const setActiveTab = (key: string) => {
  const tab = props.tabs.find(t => t.key === key)
  if (tab && !tab.disabled) {
    activeTab.value = key
    emit('update:modelValue', key)
    emit('change', tab)
  }
}

const getTabClasses = (tab: Tab) => {
  const base = 'group inline-flex items-center space-x-2 py-4 px-1 border-b-2 font-medium text-sm transition-colors'

  if (tab.disabled) {
    return `${base} border-transparent text-gray-400 dark:text-gray-500 cursor-not-allowed`
  }

  if (tab.key === activeTab.value) {
    return `${base} border-indigo-500 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400`
  }

  return `${base} border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600`
}

const getTabIconClasses = (tab: Tab) => {
  const base = 'h-5 w-5'

  if (tab.disabled) {
    return `${base} text-gray-400 dark:text-gray-500`
  }

  if (tab.key === activeTab.value) {
    return `${base} text-indigo-600 dark:text-indigo-400`
  }

  return `${base} text-gray-400 dark:text-gray-500 group-hover:text-gray-500 dark:group-hover:text-gray-400`
}

const getTabBadgeClasses = (tab: Tab) => {
  const base = 'ml-2 inline-block py-0.5 px-2 text-xs font-medium rounded-full'

  if (tab.disabled) {
    return `${base} bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500`
  }

  if (tab.key === activeTab.value) {
    return `${base} bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400`
  }

  return `${base} bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 group-hover:bg-gray-200 dark:group-hover:bg-gray-600`
}

// Watch for external changes to modelValue
watch(() => props.modelValue, (newValue) => {
  if (newValue && newValue !== activeTab.value) {
    activeTab.value = newValue
  }
})
</script>