<template>
  <nav class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6" aria-label="Pagination">
    <div class="hidden sm:block">
      <p class="text-sm text-gray-700">
        Showing
        <span class="font-medium">{{ from }}</span>
        to
        <span class="font-medium">{{ to }}</span>
        of
        <span class="font-medium">{{ total }}</span>
        results
      </p>
    </div>
    <div class="flex flex-1 justify-between sm:justify-end">
      <Link
        v-if="prevPageUrl"
        :href="prevPageUrl"
        class="relative inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus-visible:outline-offset-0"
        preserve-scroll
      >
        Previous
      </Link>
      <span
        v-else
        class="relative inline-flex items-center rounded-md bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-400 ring-1 ring-inset ring-gray-300"
      >
        Previous
      </span>

      <Link
        v-if="nextPageUrl"
        :href="nextPageUrl"
        class="relative ml-3 inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus-visible:outline-offset-0"
        preserve-scroll
      >
        Next
      </Link>
      <span
        v-else
        class="relative ml-3 inline-flex items-center rounded-md bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-400 ring-1 ring-inset ring-gray-300"
      >
        Next
      </span>
    </div>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  links: Array
})

const prevPageUrl = computed(() => {
  const prevLink = props.links?.find(link => link.label === '&laquo; Previous')
  return prevLink?.url
})

const nextPageUrl = computed(() => {
  const nextLink = props.links?.find(link => link.label === 'Next &raquo;')
  return nextLink?.url
})

const from = computed(() => {
  const currentPage = props.links?.find(link => link.active)
  return currentPage?.from || 0
})

const to = computed(() => {
  const currentPage = props.links?.find(link => link.active)
  return currentPage?.to || 0
})

const total = computed(() => {
  const currentPage = props.links?.find(link => link.active)
  return currentPage?.total || 0
})
</script>