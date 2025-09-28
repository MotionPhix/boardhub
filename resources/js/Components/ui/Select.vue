<template>
  <div class="relative">
    <div v-if="label" class="mb-2">
      <label
        :for="id"
        class="block text-sm font-medium text-gray-700 dark:text-gray-300"
      >
        {{ label }}
        <span v-if="required" class="text-red-500 ml-1">*</span>
      </label>
      <p v-if="description" class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ description }}</p>
    </div>

    <Listbox
      :model-value="modelValue"
      @update:model-value="handleChange"
      :multiple="multiple"
      :disabled="disabled"
    >
      <div class="relative">
        <ListboxButton
          :class="buttonClasses"
          :disabled="disabled"
        >
          <span class="flex items-center min-h-[20px]">
            <div v-if="selectedOption?.avatar" class="flex-shrink-0 mr-3">
              <img :src="selectedOption.avatar" :alt="selectedOption.label" class="h-6 w-6 rounded-full" />
            </div>
            <component
              v-else-if="selectedOption?.icon"
              :is="selectedOption.icon"
              class="h-5 w-5 text-gray-400 dark:text-gray-500 mr-3 flex-shrink-0"
            />
            <div class="flex-1 text-left">
              <span v-if="selectedOption" class="block truncate">
                {{ selectedOption.label }}
              </span>
              <span v-else class="block truncate text-gray-500 dark:text-gray-400">
                {{ placeholder }}
              </span>
              <span v-if="selectedOption?.description" class="block text-sm text-gray-500 dark:text-gray-400 truncate">
                {{ selectedOption.description }}
              </span>
            </div>
          </span>
          <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
            <ChevronDown class="h-5 w-5 text-gray-400 dark:text-gray-500" />
          </span>
        </ListboxButton>

        <transition
          leave-active-class="transition ease-in duration-100"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <ListboxOptions
            class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white dark:bg-gray-800 py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 dark:ring-white dark:ring-opacity-10 focus:outline-none sm:text-sm border border-gray-200 dark:border-gray-700"
          >
            <ListboxOption
              v-for="option in options"
              :key="optionKey ? option[optionKey] : option.value"
              :value="option.value"
              v-slot="{ active, selected }"
            >
              <li
                :class="[
                  active ? 'bg-indigo-600 text-white' : 'text-gray-900 dark:text-gray-100',
                  'relative cursor-default select-none py-2 pl-3 pr-9'
                ]"
              >
                <div class="flex items-center">
                  <div v-if="option.avatar" class="flex-shrink-0 mr-3">
                    <img :src="option.avatar" :alt="option.label" class="h-6 w-6 rounded-full" />
                  </div>
                  <component
                    v-else-if="option.icon"
                    :is="option.icon"
                    :class="[
                      active ? 'text-white' : 'text-gray-400 dark:text-gray-500',
                      'h-5 w-5 mr-3 flex-shrink-0'
                    ]"
                  />
                  <div class="flex-1">
                    <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">
                      {{ option.label }}
                    </span>
                    <span v-if="option.description" :class="[active ? 'text-indigo-200' : 'text-gray-500 dark:text-gray-400', 'block text-sm truncate']">
                      {{ option.description }}
                    </span>
                  </div>
                </div>

                <span
                  v-if="selected"
                  :class="[
                    active ? 'text-white' : 'text-indigo-600 dark:text-indigo-400',
                    'absolute inset-y-0 right-0 flex items-center pr-4'
                  ]"
                >
                  <Check class="h-5 w-5" />
                </span>
              </li>
            </ListboxOption>
          </ListboxOptions>
        </transition>
      </div>
    </Listbox>

    <InputError v-if="error" :message="error" class="mt-2" />
    <p v-else-if="hint" class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ hint }}</p>
  </div>
</template>

<script setup lang="ts">
import { computed, useId } from 'vue'
import { Listbox, ListboxButton, ListboxOption, ListboxOptions } from '@headlessui/vue'
import { ChevronDown, Check } from 'lucide-vue-next'
import InputError from './InputError.vue'

interface Option {
  value: any
  label: string
  description?: string
  avatar?: string
  icon?: any
  disabled?: boolean
}

interface Props {
  modelValue?: any
  options: Option[]
  label?: string
  description?: string
  placeholder?: string
  error?: string
  hint?: string
  disabled?: boolean
  required?: boolean
  multiple?: boolean
  size?: 'sm' | 'md' | 'lg'
  optionKey?: string
  id?: string
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  disabled: false,
  required: false,
  multiple: false,
  size: 'md',
  placeholder: 'Select an option...'
})

const emit = defineEmits<{
  'update:modelValue': [value: any]
  'change': [value: any]
}>()

const id = props.id || useId()

const selectedOption = computed(() => {
  if (props.multiple) {
    return null // Handle multiple selection display differently if needed
  }
  return props.options.find(option => option.value === props.modelValue)
})

const baseClasses = 'relative w-full cursor-default rounded-md bg-white dark:bg-gray-800 py-1.5 pl-3 pr-10 text-left text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-600 dark:focus:ring-indigo-400 transition-colors'

const sizeClasses = {
  sm: 'py-1 text-sm',
  md: 'py-1.5 text-sm',
  lg: 'py-2 text-base'
}

const stateClasses = computed(() => {
  if (props.error) {
    return 'ring-red-300 dark:ring-red-500 focus:ring-red-600 dark:focus:ring-red-400'
  }
  if (props.disabled) {
    return 'bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 ring-gray-200 dark:ring-gray-600 cursor-not-allowed'
  }
  return ''
})

const buttonClasses = computed(() => [
  baseClasses,
  sizeClasses[props.size],
  stateClasses.value,
  props.class
].filter(Boolean).join(' '))

const handleChange = (value: any) => {
  emit('update:modelValue', value)
  emit('change', value)
}
</script>