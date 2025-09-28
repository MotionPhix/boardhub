<template>
  <div>
    <apexchart
      :options="chartOptions"
      :series="series"
      type="line"
      :height="height"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'

interface Props {
  series: Array<{
    name: string
    data: number[]
  }>
  categories: string[]
  title?: string
  height?: number
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  height: 350
})

const chartOptions = computed(() => ({
  chart: {
    type: 'line',
    toolbar: {
      show: false
    },
    background: 'transparent'
  },
  dataLabels: {
    enabled: false
  },
  stroke: {
    curve: 'smooth',
    width: 3
  },
  xaxis: {
    categories: props.categories,
    labels: {
      style: {
        colors: '#9CA3AF'
      }
    }
  },
  yaxis: {
    labels: {
      style: {
        colors: '#9CA3AF'
      }
    }
  },
  grid: {
    borderColor: '#374151',
    strokeDashArray: 3
  },
  colors: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444'],
  title: {
    text: props.title,
    style: {
      color: '#F9FAFB'
    }
  },
  legend: {
    labels: {
      colors: '#9CA3AF'
    }
  },
  theme: {
    mode: 'dark'
  }
}))
</script>