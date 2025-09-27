<template>
  <div class="relative">
    <div v-if="label" class="mb-2">
      <label
        :for="id"
        class="block text-sm font-medium text-gray-700"
      >
        {{ label }}
        <span v-if="required" class="text-red-500 ml-1">*</span>
      </label>
      <p v-if="description" class="text-sm text-gray-500 mt-1">{{ description }}</p>
    </div>

    <div class="relative">
      <div
        v-if="$slots.prefix || prefixIcon"
        class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none"
      >
        <slot name="prefix">
          <component v-if="prefixIcon" :is="prefixIcon" class="h-5 w-5 text-gray-400" />
        </slot>
      </div>

      <input
        :id="id"
        :type="type"
        :placeholder="placeholder"
        :value="modelValue"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        :class="inputClasses"
        v-bind="$attrs"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
      />

      <div
        v-if="$slots.suffix || suffixIcon || clearable"
        class="absolute inset-y-0 right-0 flex items-center pr-3"
      >
        <slot name="suffix">
          <button
            v-if="clearable && modelValue && !disabled"
            type="button"
            @click="clearInput"
            class="text-gray-400 hover:text-gray-600"
          >
            <X class="h-4 w-4" />
          </button>
          <component v-else-if="suffixIcon" :is="suffixIcon" class="h-5 w-5 text-gray-400" />
        </slot>
      </div>
    </div>

    <InputError v-if="error" :message="error" class="mt-2" />
    <p v-else-if="hint" class="mt-2 text-sm text-gray-500">{{ hint }}</p>
  </div>
</template>

<script setup lang="ts">
import { computed, useId } from 'vue'
import { X } from 'lucide-vue-next'
import InputError from './InputError.vue'

interface Props {
  modelValue?: string | number
  type?: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'search'
  label?: string
  description?: string
  placeholder?: string
  error?: string
  hint?: string
  disabled?: boolean
  readonly?: boolean
  required?: boolean
  clearable?: boolean
  size?: 'sm' | 'md' | 'lg'
  variant?: 'default' | 'ghost'
  prefixIcon?: any
  suffixIcon?: any
  id?: string
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  disabled: false,
  readonly: false,
  required: false,
  clearable: false,
  size: 'md',
  variant: 'default'
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number]
  'focus': [event: FocusEvent]
  'blur': [event: FocusEvent]
}>()

const id = props.id || useId()

const baseClasses = 'block w-full rounded-md border-0 shadow-sm ring-1 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 transition-colors'

const variantClasses = {
  default: 'bg-white ring-gray-300',
  ghost: 'bg-transparent ring-transparent border-b border-gray-300 rounded-none focus:border-indigo-600'
}

const sizeClasses = {
  sm: 'px-2.5 py-1.5 text-sm',
  md: 'px-3 py-2 text-sm',
  lg: 'px-4 py-3 text-base'
}

const stateClasses = computed(() => {
  if (props.error) {
    return 'ring-red-300 focus:ring-red-600'
  }
  if (props.disabled) {
    return 'bg-gray-50 text-gray-500 ring-gray-200'
  }
  return variantClasses[props.variant]
})

const paddingClasses = computed(() => {
  const base = sizeClasses[props.size]
  const hasPrefix = props.prefixIcon || props.$slots?.prefix
  const hasSuffix = props.suffixIcon || props.$slots?.suffix || props.clearable

  if (hasPrefix && hasSuffix) {
    return base.replace('px-3', 'pl-10 pr-10').replace('px-2.5', 'pl-9 pr-9').replace('px-4', 'pl-11 pr-11')
  } else if (hasPrefix) {
    return base.replace('px-3', 'pl-10').replace('px-2.5', 'pl-9').replace('px-4', 'pl-11')
  } else if (hasSuffix) {
    return base.replace('px-3', 'pr-10').replace('px-2.5', 'pr-9').replace('px-4', 'pr-11')
  }
  return base
})

const inputClasses = computed(() => [
  baseClasses,
  stateClasses.value,
  paddingClasses.value,
  props.class
].filter(Boolean).join(' '))

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.value)
}

const handleFocus = (event: FocusEvent) => {
  emit('focus', event)
}

const handleBlur = (event: FocusEvent) => {
  emit('blur', event)
}

const clearInput = () => {
  emit('update:modelValue', '')
}
</script>