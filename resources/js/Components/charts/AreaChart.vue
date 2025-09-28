<template>
  <div>
    <apexchart
      :options="chartOptions"
      :series="series"
      type="area"
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
    type: 'area',
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
    width: 2
  },
  fill: {
    type: 'gradient',
    gradient: {
      opacityFrom: 0.6,
      opacityTo: 0.1
    }
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