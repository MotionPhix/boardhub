<template>
  <Menu as="div" class="relative inline-block text-left">
    <div>
      <MenuButton
        :class="buttonClasses"
        v-bind="$attrs"
      >
        <slot name="trigger">
          {{ triggerText }}
          <ChevronDown class="ml-2 h-4 w-4" />
        </slot>
      </MenuButton>
    </div>

    <transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <MenuItems
        :class="[
          'absolute z-10 mt-2 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none',
          positionClasses,
          widthClasses
        ]"
      >
        <div class="py-1">
          <slot />
        </div>
      </MenuItems>
    </transition>
  </Menu>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Menu, MenuButton, MenuItems } from '@headlessui/vue'
import { ChevronDown } from 'lucide-vue-next'

interface Props {
  triggerText?: string
  variant?: 'default' | 'outline' | 'ghost'
  size?: 'sm' | 'md' | 'lg'
  position?: 'left' | 'right'
  width?: 'auto' | 'sm' | 'md' | 'lg' | 'full'
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  triggerText: 'Options',
  variant: 'default',
  size: 'md',
  position: 'right',
  width: 'auto'
})

const baseButtonClasses = 'inline-flex items-center justify-center rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'

const variantClasses = {
  default: 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50',
  outline: 'bg-transparent text-gray-700 border border-gray-300 hover:bg-gray-50',
  ghost: 'bg-transparent text-gray-700 hover:bg-gray-100'
}

const sizeClasses = {
  sm: 'px-2.5 py-1.5 text-sm',
  md: 'px-3 py-2 text-sm',
  lg: 'px-4 py-2 text-base'
}

const buttonClasses = computed(() => [
  baseButtonClasses,
  variantClasses[props.variant],
  sizeClasses[props.size],
  props.class
].filter(Boolean).join(' '))

const positionClasses = computed(() => {
  return props.position === 'left' ? 'left-0' : 'right-0'
})

const widthClasses = computed(() => {
  const widths = {
    auto: 'w-auto min-w-[8rem]',
    sm: 'w-48',
    md: 'w-56',
    lg: 'w-64',
    full: 'w-full'
  }
  return widths[props.width]
})
</script>