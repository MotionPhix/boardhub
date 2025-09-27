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
      <textarea
        :id="id"
        ref="textarea"
        :placeholder="placeholder"
        :value="modelValue"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        :rows="rows"
        :class="textareaClasses"
        v-bind="$attrs"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
      />

      <div
        v-if="showCharCount && maxLength"
        class="absolute bottom-2 right-2 text-xs text-gray-500 bg-white px-1 rounded"
      >
        {{ currentLength }}/{{ maxLength }}
      </div>
    </div>

    <InputError v-if="error" :message="error" class="mt-2" />
    <p v-else-if="hint" class="mt-2 text-sm text-gray-500">{{ hint }}</p>
  </div>
</template>

<script setup lang="ts">
import { computed, useId, ref, nextTick, watch } from 'vue'
import InputError from './InputError.vue'

interface Props {
  modelValue?: string
  label?: string
  description?: string
  placeholder?: string
  error?: string
  hint?: string
  disabled?: boolean
  readonly?: boolean
  required?: boolean
  rows?: number
  autoResize?: boolean
  minRows?: number
  maxRows?: number
  maxLength?: number
  showCharCount?: boolean
  size?: 'sm' | 'md' | 'lg'
  id?: string
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  disabled: false,
  readonly: false,
  required: false,
  rows: 4,
  autoResize: false,
  minRows: 2,
  maxRows: 10,
  showCharCount: false,
  size: 'md'
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  'focus': [event: FocusEvent]
  'blur': [event: FocusEvent]
}>()

const textarea = ref<HTMLTextAreaElement>()
const id = props.id || useId()

const currentLength = computed(() => props.modelValue?.length || 0)

const baseClasses = 'block w-full rounded-md border-0 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 transition-colors resize-none'

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
  return 'bg-white'
})

const textareaClasses = computed(() => [
  baseClasses,
  stateClasses.value,
  sizeClasses[props.size],
  props.class
].filter(Boolean).join(' '))

const handleInput = (event: Event) => {
  const target = event.target as HTMLTextAreaElement
  let value = target.value

  // Enforce max length
  if (props.maxLength && value.length > props.maxLength) {
    value = value.slice(0, props.maxLength)
    target.value = value
  }

  emit('update:modelValue', value)

  // Auto resize
  if (props.autoResize) {
    nextTick(() => {
      autoResize()
    })
  }
}

const handleFocus = (event: FocusEvent) => {
  emit('focus', event)
}

const handleBlur = (event: FocusEvent) => {
  emit('blur', event)
}

const autoResize = () => {
  const element = textarea.value
  if (!element) return

  // Reset height to calculate new height
  element.style.height = 'auto'

  // Calculate the new height
  const scrollHeight = element.scrollHeight
  const lineHeight = parseInt(window.getComputedStyle(element).lineHeight)
  const minHeight = lineHeight * props.minRows
  const maxHeight = lineHeight * props.maxRows

  const newHeight = Math.min(Math.max(scrollHeight, minHeight), maxHeight)
  element.style.height = newHeight + 'px'

  // Add scrollbar if content exceeds max height
  element.style.overflowY = scrollHeight > maxHeight ? 'auto' : 'hidden'
}

// Auto resize on mount and when value changes
watch(() => props.modelValue, () => {
  if (props.autoResize) {
    nextTick(() => {
      autoResize()
    })
  }
}, { immediate: true })
</script>