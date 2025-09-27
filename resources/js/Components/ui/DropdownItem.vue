<template>
  <MenuItem v-slot="{ active, close }">
    <component
      :is="as"
      :href="href"
      :class="[
        active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
        'group flex items-center px-4 py-2 text-sm transition-colors',
        disabled && 'opacity-50 cursor-not-allowed',
        destructive && !disabled && 'text-red-700',
        destructive && active && !disabled && 'bg-red-50 text-red-900'
      ]"
      :disabled="disabled"
      @click="handleClick(close, $event)"
    >
      <component
        v-if="icon"
        :is="icon"
        :class="[
          'mr-3 h-4 w-4 flex-shrink-0',
          active && !destructive ? 'text-gray-500' : 'text-gray-400',
          destructive && !disabled && 'text-red-500',
          destructive && active && !disabled && 'text-red-600'
        ]"
      />

      <div class="flex-1">
        <div class="flex items-center justify-between">
          <span>{{ label }}</span>
          <span v-if="shortcut" class="ml-2 text-xs text-gray-400">{{ shortcut }}</span>
        </div>
        <p v-if="description" class="text-xs text-gray-500 mt-0.5">{{ description }}</p>
      </div>
    </component>
  </MenuItem>
</template>

<script setup lang="ts">
import { MenuItem } from '@headlessui/vue'

interface Props {
  label: string
  description?: string
  icon?: any
  shortcut?: string
  href?: string
  as?: string
  disabled?: boolean
  destructive?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  as: 'button',
  disabled: false,
  destructive: false
})

const emit = defineEmits<{
  click: [event: Event]
}>()

const handleClick = (close: () => void, event: Event) => {
  if (props.disabled) {
    event.preventDefault()
    return
  }

  emit('click', event)

  if (props.as === 'button') {
    close()
  }
}
</script>