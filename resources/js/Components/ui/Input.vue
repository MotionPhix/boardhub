<template>
  <div class="relative">
    <div v-if="label" class="mb-2">
      <label
        :for="id"
        class="block text-sm font-medium text-gray-700 dark:text-gray-300"
      >
        {{ label }}
        <span v-if="required" class="text-red-500 dark:text-red-400 ml-1">*</span>
      </label>
      <p v-if="description" class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ description }}</p>
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
        :type="computedType"
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
        v-if="$slots.suffix || suffixIcon || clearable || showPasswordToggleButton"
        class="absolute inset-y-0 right-0 flex items-center px-3"
      >
        <slot name="suffix">
          <button
            v-if="showPasswordToggleButton"
            type="button"
            @click="togglePasswordVisibility"
            :disabled="disabled"
            class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none"
            :title="passwordVisible ? 'Hide password' : 'Show password'"
          >
            <EyeOff v-if="passwordVisible" class="h-4 w-4" />
            <Eye v-else class="h-4 w-4" />
          </button>
          <button
            v-else-if="clearable && modelValue && !disabled"
            type="button"
            @click="clearInput"
            class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <X class="h-4 w-4" />
          </button>
          <component v-else-if="suffixIcon" :is="suffixIcon" class="h-5 w-5 text-gray-400 dark:text-gray-500" />
        </slot>
      </div>
    </div>

    <InputError v-if="error" :message="error" class="mt-2" />
    <p v-else-if="hint" class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ hint }}</p>
  </div>
</template>

<script setup lang="ts">
import { computed, useId, ref } from 'vue'
import { X, Eye, EyeOff } from 'lucide-vue-next'
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
  showPasswordToggle?: boolean
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
  showPasswordToggle: true,
  size: 'md',
  variant: 'default'
})

const passwordVisible = ref(false)

const emit = defineEmits<{
  'update:modelValue': [value: string | number]
  'focus': [event: FocusEvent]
  'blur': [event: FocusEvent]
}>()

const id = props.id || useId()

const baseClasses = 'px-3 block w-full rounded-md border-0 shadow-sm ring-1 ring-inset placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:focus:ring-indigo-400 transition-colors'

const variantClasses = {
  default: 'bg-white dark:bg-gray-800 ring-gray-300 dark:ring-gray-600 text-gray-900 dark:text-white',
  ghost: 'bg-transparent ring-transparent border-b border-gray-300 dark:border-gray-600 rounded-none focus:border-indigo-600 dark:focus:border-indigo-400 text-gray-900 dark:text-white'
}

const computedType = computed(() => {
  if (props.type === 'password' && passwordVisible.value) {
    return 'text'
  }
  return props.type
})

const showPasswordToggleButton = computed(() =>
  props.type === 'password' && props.showPasswordToggle
)

const togglePasswordVisibility = () => {
  passwordVisible.value = !passwordVisible.value
}

const sizeClasses = {
  sm: 'px-2.5 py-1.5 text-sm',
  md: 'px-3 py-2 text-sm',
  lg: 'px-4 py-3 text-base'
}

const stateClasses = computed(() => {
  if (props.error) {
    return 'ring-red-300 dark:ring-red-500 focus:ring-red-600 dark:focus:ring-red-400'
  }
  if (props.disabled) {
    return 'bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 ring-gray-200 dark:ring-gray-600'
  }
  return variantClasses[props.variant]
})

const paddingClasses = computed(() => {
  const base = sizeClasses[props.size]
  const hasPrefix = props.prefixIcon || props.$slots?.prefix
  const hasSuffix = props.suffixIcon || props.$slots?.suffix || props.clearable || showPasswordToggleButton.value

  if (hasPrefix && hasSuffix) {
    return base.replace('px-3', 'pl-10 pr-12').replace('px-2.5', 'pl-9 pr-11').replace('px-4', 'pl-11 pr-13')
  } else if (hasPrefix) {
    return base.replace('px-3', 'pl-10').replace('px-2.5', 'pl-9').replace('px-4', 'pl-11')
  } else if (hasSuffix) {
    return base.replace('px-3', 'pr-12').replace('px-2.5', 'pr-11').replace('px-4', 'pr-13')
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
