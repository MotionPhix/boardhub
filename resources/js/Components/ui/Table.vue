<template>
  <div class="overflow-hidden bg-white shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
    <!-- Table Header -->
    <div v-if="title || description || $slots.header" class="px-4 py-5 sm:px-6 border-b border-gray-200">
      <slot name="header">
        <div class="flex items-center justify-between">
          <div>
            <h3 v-if="title" class="text-lg font-medium leading-6 text-gray-900">
              {{ title }}
            </h3>
            <p v-if="description" class="mt-1 text-sm text-gray-500">
              {{ description }}
            </p>
          </div>
          <div v-if="$slots.actions">
            <slot name="actions" />
          </div>
        </div>
      </slot>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="p-8 text-center">
      <Loader2 class="h-8 w-8 animate-spin text-gray-400 mx-auto" />
      <p class="mt-2 text-sm text-gray-500">Loading...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="!data || data.length === 0" class="p-8">
      <slot name="empty">
        <EmptyState
          title="No data available"
          description="There are no items to display."
          :icon="Database"
        />
      </slot>
    </div>

    <!-- Table -->
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
          <tr>
            <th
              v-for="column in columns"
              :key="column.key"
              :class="getHeaderClasses(column)"
              scope="col"
            >
              <div class="flex items-center space-x-1">
                <span>{{ column.label }}</span>
                <button
                  v-if="column.sortable"
                  @click="handleSort(column.key)"
                  class="text-gray-400 hover:text-gray-600"
                >
                  <ArrowUpDown class="h-4 w-4" />
                </button>
              </div>
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr
            v-for="(row, rowIndex) in data"
            :key="getRowKey(row, rowIndex)"
            :class="getRowClasses(row, rowIndex)"
          >
            <td
              v-for="column in columns"
              :key="column.key"
              :class="getCellClasses(column)"
            >
              <slot
                :name="`cell.${column.key}`"
                :row="row"
                :value="getNestedValue(row, column.key)"
                :index="rowIndex"
              >
                {{ formatCellValue(getNestedValue(row, column.key), column) }}
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Table Footer -->
    <div v-if="$slots.footer" class="px-4 py-3 bg-gray-50 border-t border-gray-200">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { Loader2, ArrowUpDown, Database } from 'lucide-vue-next'
import EmptyState from './EmptyState.vue'

interface Column {
  key: string
  label: string
  sortable?: boolean
  align?: 'left' | 'center' | 'right'
  width?: string
  format?: 'text' | 'number' | 'currency' | 'date' | 'datetime' | 'boolean'
  class?: string
}

interface Props {
  columns: Column[]
  data?: any[]
  title?: string
  description?: string
  loading?: boolean
  sortable?: boolean
  striped?: boolean
  hoverable?: boolean
  dense?: boolean
  rowKey?: string
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  sortable: false,
  striped: false,
  hoverable: true,
  dense: false,
  rowKey: 'id'
})

const emit = defineEmits<{
  sort: [column: string, direction: 'asc' | 'desc']
}>()

const getHeaderClasses = (column: Column) => {
  const base = 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'
  const align = {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right'
  }
  return [base, align[column.align || 'left'], column.class].filter(Boolean).join(' ')
}

const getCellClasses = (column: Column) => {
  const base = props.dense ? 'px-6 py-2 whitespace-nowrap' : 'px-6 py-4 whitespace-nowrap'
  const align = {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right'
  }
  return [base, align[column.align || 'left']].filter(Boolean).join(' ')
}

const getRowClasses = (row: any, index: number) => {
  const classes = []

  if (props.hoverable) {
    classes.push('hover:bg-gray-50')
  }

  if (props.striped && index % 2 === 1) {
    classes.push('bg-gray-50')
  }

  return classes.join(' ')
}

const getRowKey = (row: any, index: number) => {
  return row[props.rowKey] || index
}

const getNestedValue = (obj: any, path: string) => {
  return path.split('.').reduce((o, p) => o?.[p], obj)
}

const formatCellValue = (value: any, column: Column) => {
  if (value == null) return '-'

  switch (column.format) {
    case 'number':
      return typeof value === 'number' ? value.toLocaleString() : value
    case 'currency':
      return typeof value === 'number' ? `$${value.toLocaleString()}` : value
    case 'date':
      return value instanceof Date ? value.toLocaleDateString() : new Date(value).toLocaleDateString()
    case 'datetime':
      return value instanceof Date ? value.toLocaleString() : new Date(value).toLocaleString()
    case 'boolean':
      return value ? 'Yes' : 'No'
    default:
      return value
  }
}

const handleSort = (column: string) => {
  // This is a basic implementation. You might want to track sort direction
  emit('sort', column, 'asc')
}
</script>