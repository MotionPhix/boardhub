import { ref, watch, onMounted } from 'vue'

const isDark = ref(false)

export function useDarkMode() {
  const toggleDarkMode = () => {
    isDark.value = !isDark.value
    updateDarkMode()
  }

  const setDarkMode = (value: boolean) => {
    isDark.value = value
    updateDarkMode()
  }

  const updateDarkMode = () => {
    if (isDark.value) {
      document.documentElement.classList.add('dark')
      localStorage.setItem('darkMode', 'true')
    } else {
      document.documentElement.classList.remove('dark')
      localStorage.setItem('darkMode', 'false')
    }
  }

  const initializeDarkMode = () => {
    // Check localStorage first
    const savedPreference = localStorage.getItem('darkMode')

    if (savedPreference !== null) {
      isDark.value = savedPreference === 'true'
    } else {
      // Fall back to system preference
      isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches
    }

    updateDarkMode()

    // Listen for system theme changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
      if (localStorage.getItem('darkMode') === null) {
        isDark.value = e.matches
        updateDarkMode()
      }
    })
  }

  onMounted(() => {
    initializeDarkMode()
  })

  // Watch for changes and persist them
  watch(isDark, updateDarkMode)

  return {
    isDark,
    toggleDarkMode,
    setDarkMode,
    initializeDarkMode
  }
}