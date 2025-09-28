<template>
  <div>
    <apexchart
      :options="chartOptions"
      :series="series"
      type="donut"
      :height="height"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'

interface Props {
  series: number[]
  labels: string[]
  title?: string
  height?: number
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  height: 350
})

const chartOptions = computed(() => ({
  chart: {
    type: 'donut',
    background: 'transparent'
  },
  labels: props.labels,
  colors: ['#8B5CF6', '#3B82F6', '#10B981', '#F59E0B', '#EF4444'],
  dataLabels: {
    enabled: true,
    style: {
      colors: ['#F9FAFB']
    }
  },
  plotOptions: {
    pie: {
      donut: {
        size: '65%',
        labels: {
          show: true,
          total: {
            show: true,
            color: '#F9FAFB'
          },
          value: {
            color: '#F9FAFB'
          },
          name: {
            color: '#9CA3AF'
          }
        }
      }
    }
  },
  legend: {
    position: 'bottom',
    labels: {
      colors: '#9CA3AF'
    }
  },
  title: {
    text: props.title,
    align: 'center',
    style: {
      color: '#F9FAFB'
    }
  },
  theme: {
    mode: 'dark'
  }
}))
</script>