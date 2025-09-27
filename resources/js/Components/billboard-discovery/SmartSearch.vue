<template>
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <!-- Search Header -->
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                        🔍
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white">AI-Powered Billboard Discovery</h2>
                        <p class="text-indigo-100 text-sm">Find the perfect billboard for your campaign</p>
                    </div>
                </div>
                <div class="text-white">
                    <button
                        @click="showAdvancedFilters = !showAdvancedFilters"
                        class="px-4 py-2 bg-white bg-opacity-20 rounded-lg hover:bg-opacity-30 transition-all"
                    >
                        Advanced Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Quick Search Bar -->
        <div class="p-6 border-b border-gray-200">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input
                    v-model="searchQuery"
                    @input="handleSearchInput"
                    @keydown.enter="performSearch"
                    type="text"
                    placeholder="Search by location, area type, or billboard features..."
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-lg"
                />
                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <button
                        v-if="searchQuery"
                        @click="clearSearch"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Search Suggestions -->
            <div v-if="suggestions.length > 0" class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg">
                <div
                    v-for="suggestion in suggestions"
                    :key="suggestion.value"
                    @click="selectSuggestion(suggestion)"
                    class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0"
                >
                    <div class="flex items-center space-x-3">
                        <span class="text-lg">{{ suggestion.icon }}</span>
                        <div>
                            <div class="font-medium text-gray-900">{{ suggestion.label }}</div>
                            <div class="text-sm text-gray-500 capitalize">{{ suggestion.type }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Filters -->
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <div class="flex flex-wrap gap-3">
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700">Quick Filters:</label>
                </div>

                <!-- Location Filter -->
                <select
                    v-model="filters.location"
                    @change="performSearch"
                    class="px-3 py-1 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-indigo-500"
                >
                    <option value="">Any Location</option>
                    <option v-for="location in popularLocations" :key="location" :value="location">
                        {{ location }}
                    </option>
                </select>

                <!-- Size Filter -->
                <select
                    v-model="filters.size"
                    @change="performSearch"
                    class="px-3 py-1 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-indigo-500"
                >
                    <option value="">Any Size</option>
                    <option value="small">Small</option>
                    <option value="medium">Medium</option>
                    <option value="large">Large</option>
                    <option value="extra_large">Extra Large</option>
                </select>

                <!-- Price Range -->
                <select
                    v-model="filters.priceRange"
                    @change="applyPriceRange"
                    class="px-3 py-1 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-indigo-500"
                >
                    <option value="">Any Price</option>
                    <option value="0-1000">Under $1,000</option>
                    <option value="1000-5000">$1,000 - $5,000</option>
                    <option value="5000-10000">$5,000 - $10,000</option>
                    <option value="10000+">$10,000+</option>
                </select>

                <!-- AI Toggle -->
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input
                        v-model="filters.useAI"
                        @change="performSearch"
                        type="checkbox"
                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <span class="text-sm text-gray-700 flex items-center">
                        🤖 AI Recommendations
                    </span>
                </label>
            </div>
        </div>

        <!-- Advanced Filters Panel -->
        <div v-if="showAdvancedFilters" class="px-6 py-4 bg-blue-50 border-b border-blue-200">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Campaign Budget -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Campaign Budget</label>
                    <div class="flex space-x-2">
                        <input
                            v-model.number="filters.budget"
                            type="number"
                            placeholder="Budget"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-indigo-500"
                        />
                        <select
                            v-model="filters.duration"
                            class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="7">7 days</option>
                            <option value="14">2 weeks</option>
                            <option value="30">1 month</option>
                            <option value="90">3 months</option>
                        </select>
                    </div>
                </div>

                <!-- Geographic Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Location Radius</label>
                    <div class="flex space-x-2">
                        <button
                            @click="getCurrentLocation"
                            class="px-3 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700"
                        >
                            📍 Use My Location
                        </button>
                        <select
                            v-model="filters.radius"
                            class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="5">5 km</option>
                            <option value="10">10 km</option>
                            <option value="25">25 km</option>
                            <option value="50">50 km</option>
                        </select>
                    </div>
                </div>

                <!-- Area Types -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Area Types</label>
                    <div class="space-y-1">
                        <label v-for="areaType in areaTypes" :key="areaType.value" class="flex items-center space-x-2">
                            <input
                                v-model="filters.areaTypes"
                                :value="areaType.value"
                                type="checkbox"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-sm text-gray-700">{{ areaType.icon }} {{ areaType.label }}</span>
                        </label>
                    </div>
                </div>

                <!-- Performance Filters -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Performance</label>
                    <div class="space-y-2">
                        <div>
                            <label class="text-xs text-gray-600">Min. Occupancy Rate</label>
                            <input
                                v-model.number="filters.minOccupancyRate"
                                type="range"
                                min="0"
                                max="100"
                                class="w-full"
                            />
                            <div class="text-xs text-gray-500">{{ filters.minOccupancyRate }}%</div>
                        </div>
                        <label class="flex items-center space-x-2">
                            <input
                                v-model="filters.excludeLowPerformers"
                                type="checkbox"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-xs text-gray-700">Exclude low performers</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex justify-between">
                <button
                    @click="resetFilters"
                    class="px-4 py-2 text-gray-600 hover:text-gray-800 font-medium"
                >
                    Reset Filters
                </button>
                <button
                    @click="performSearch"
                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium"
                >
                    Apply Filters & Search
                </button>
            </div>
        </div>

        <!-- Search Status -->
        <div v-if="searchStatus.searching" class="px-6 py-4 bg-yellow-50 border-b border-yellow-200">
            <div class="flex items-center space-x-3">
                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-indigo-600"></div>
                <span class="text-sm text-yellow-800">{{ searchStatus.message }}</span>
            </div>
        </div>

        <!-- Search Results Summary -->
        <div v-if="searchResults.billboards.length > 0" class="px-6 py-4 bg-green-50 border-b border-green-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-green-800">
                    Found {{ searchResults.billboards.length }} billboards
                    <span v-if="filters.useAI" class="font-medium">with AI recommendations</span>
                </div>
                <div class="flex items-center space-x-4 text-xs text-green-700">
                    <span v-if="searchResults.insights">💡 {{ Object.keys(searchResults.insights).length }} insights available</span>
                    <span v-if="searchResults.alternatives">🔄 {{ searchResults.alternatives.length }} alternatives suggested</span>
                </div>
            </div>
        </div>

        <!-- No Results -->
        <div v-if="searchCompleted && searchResults.billboards.length === 0" class="px-6 py-8 text-center">
            <div class="text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 20a7.962 7.962 0 01-5.291-2.709M15 3H9v3.4a6.002 6.002 0 016 0V3z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No billboards found</h3>
                <p class="text-gray-500 mb-4">Try adjusting your search criteria or explore our suggestions</p>
                <div class="flex justify-center space-x-3">
                    <button
                        @click="resetFilters"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
                    >
                        Clear Filters
                    </button>
                    <button
                        @click="showTrendingBillboards"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                    >
                        Show Trending
                    </button>
                </div>
            </div>
        </div>

        <!-- AI Insights Panel -->
        <div v-if="searchResults.insights" class="px-6 py-4 bg-blue-50 border-b border-blue-200">
            <h3 class="font-medium text-blue-900 mb-3 flex items-center">
                <span class="mr-2">🤖</span>
                AI Insights & Recommendations
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                    v-for="(insight, key) in searchResults.insights"
                    :key="key"
                    class="bg-white rounded-lg p-3 border border-blue-200"
                >
                    <div class="text-sm font-medium text-blue-900 capitalize">{{ key.replace('_', ' ') }}</div>
                    <div class="text-sm text-blue-700 mt-1">{{ insight.message || insight }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { debounce } from 'lodash-es'
import axios from 'axios'

const props = defineProps({
    tenant: Object,
})

// Reactive data
const searchQuery = ref('')
const suggestions = ref([])
const showAdvancedFilters = ref(false)
const searchCompleted = ref(false)

const filters = reactive({
    location: '',
    size: '',
    priceRange: '',
    useAI: true,
    budget: null,
    duration: 30,
    radius: 10,
    coordinates: null,
    areaTypes: [],
    minOccupancyRate: 0,
    excludeLowPerformers: false,
})

const searchStatus = reactive({
    searching: false,
    message: '',
})

const searchResults = reactive({
    billboards: [],
    insights: null,
    alternatives: [],
    market_trends: null,
})

// Static data
const popularLocations = [
    'Lilongwe Central',
    'Blantyre CBD',
    'Mzuzu City',
    'Zomba Town',
    'Kasungu',
]

const areaTypes = [
    { value: 'commercial', label: 'Commercial', icon: '🏢' },
    { value: 'highway', label: 'Highway', icon: '🛣️' },
    { value: 'residential', label: 'Residential', icon: '🏠' },
    { value: 'industrial', label: 'Industrial', icon: '🏭' },
    { value: 'mixed', label: 'Mixed Use', icon: '🌆' },
]

// Computed properties
const searchCriteria = computed(() => ({
    query: searchQuery.value,
    locations: filters.location ? [filters.location] : [],
    sizes: filters.size ? [filters.size] : [],
    price_min: filters.priceRange ? parseInt(filters.priceRange.split('-')[0]) : null,
    price_max: filters.priceRange && filters.priceRange !== '10000+' ? parseInt(filters.priceRange.split('-')[1]) : null,
    use_ai_recommendations: filters.useAI,
    budget: filters.budget,
    duration: filters.duration,
    coordinates: filters.coordinates,
    radius: filters.radius,
    area_types: filters.areaTypes,
    min_occupancy_rate: filters.minOccupancyRate,
    exclude_low_performers: filters.excludeLowPerformers,
}))

// Methods
const handleSearchInput = debounce(async () => {
    if (searchQuery.value.length > 2) {
        await fetchSuggestions()
    } else {
        suggestions.value = []
    }
}, 300)

const fetchSuggestions = async () => {
    try {
        const response = await axios.get(`/api/t/${props.tenant.uuid}/billboards/suggestions`, {
            params: {
                query: searchQuery.value,
                limit: 8,
            }
        })

        suggestions.value = response.data.data
    } catch (error) {
        console.error('Error fetching suggestions:', error)
        suggestions.value = []
    }
}

const selectSuggestion = (suggestion) => {
    if (suggestion.type === 'location') {
        filters.location = suggestion.value
    } else if (suggestion.type === 'area_type') {
        if (!filters.areaTypes.includes(suggestion.value)) {
            filters.areaTypes.push(suggestion.value)
        }
    }

    searchQuery.value = suggestion.label
    suggestions.value = []
    performSearch()
}

const performSearch = async () => {
    if (!searchQuery.value && !hasActiveFilters()) {
        return
    }

    searchStatus.searching = true
    searchStatus.message = filters.useAI ? 'AI is analyzing billboards...' : 'Searching billboards...'

    try {
        const response = await axios.post(`/api/t/${props.tenant.uuid}/billboards/discover`, searchCriteria.value)

        searchResults.billboards = response.data.data.billboards
        searchResults.insights = response.data.data.insights
        searchResults.alternatives = response.data.data.alternatives
        searchResults.market_trends = response.data.data.market_trends

        searchCompleted.value = true

        // Emit search results to parent
        emit('searchResults', response.data.data)

    } catch (error) {
        console.error('Search error:', error)
        // Handle error appropriately
    } finally {
        searchStatus.searching = false
        searchStatus.message = ''
    }
}

const clearSearch = () => {
    searchQuery.value = ''
    suggestions.value = []
    searchResults.billboards = []
    searchResults.insights = null
    searchResults.alternatives = []
    searchCompleted.value = false
}

const resetFilters = () => {
    Object.assign(filters, {
        location: '',
        size: '',
        priceRange: '',
        useAI: true,
        budget: null,
        duration: 30,
        radius: 10,
        coordinates: null,
        areaTypes: [],
        minOccupancyRate: 0,
        excludeLowPerformers: false,
    })

    clearSearch()
}

const applyPriceRange = () => {
    performSearch()
}

const getCurrentLocation = () => {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                filters.coordinates = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                }
                performSearch()
            },
            (error) => {
                console.error('Error getting location:', error)
                // Handle geolocation error
            }
        )
    }
}

const hasActiveFilters = () => {
    return filters.location || filters.size || filters.priceRange ||
           filters.areaTypes.length > 0 || filters.budget ||
           filters.coordinates || filters.minOccupancyRate > 0 ||
           filters.excludeLowPerformers
}

const showTrendingBillboards = async () => {
    try {
        const response = await axios.get(`/api/t/${props.tenant.uuid}/billboards/trending`)
        searchResults.billboards = response.data.data.popular_billboards
        searchCompleted.value = true
        emit('searchResults', response.data.data)
    } catch (error) {
        console.error('Error fetching trending:', error)
    }
}

// Lifecycle
onMounted(() => {
    // Load initial trending data or perform default search
    showTrendingBillboards()
})

// Watch for real-time updates
onMounted(() => {
    if (window.Echo && props.tenant) {
        window.Echo.private(`tenant.${props.tenant.id}.billboards`)
            .listen('.billboard.availability.changed', (data) => {
                // Update search results in real-time if billboard availability changes
                const index = searchResults.billboards.findIndex(b => b.id === data.billboard_id)
                if (index !== -1) {
                    searchResults.billboards[index].status = data.new_status
                }
            })
    }
})

const emit = defineEmits(['searchResults'])
</script>