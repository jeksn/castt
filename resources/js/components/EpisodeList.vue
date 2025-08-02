<template>
    <div class="flex flex-col gap-3">
        <!-- Podcast Title and Description -->
        <div v-if="selectedPodcast" class="mb-4">
            <div class="flex gap-4 items-start">
                <img 
                    v-if="selectedPodcast.image_url" 
                    :src="selectedPodcast.image_url" 
                    :alt="selectedPodcast.title"
                    class="w-20 h-20 object-cover rounded-lg flex-shrink-0" 
                />
                <div class="flex-1">
                    <h2 class="text-2xl font-semibold">{{ selectedPodcast.title }}</h2>
                    <p v-if="selectedPodcast.description" class="dark:text-gray-400 text-gray-700 mt-2">{{ stripHtml(selectedPodcast.description) }}</p>
                </div>
            </div>
        </div>
        <!-- Episode Completion Progress -->
        <div v-if="completionStats.total > 0" class="mb-4">
            <div class="dark:bg-gray-800 bg-gray-100 p-2 rounded text-sm font-medium text-gray-700 dark:text-gray-400">
                {{ completionStats.completed }} of {{ completionStats.total }} episodes completed
            </div>
        </div>
		

        <!-- Search and Filter Controls - Always visible and stable -->
        <div class="flex gap-4 mb-4 items-center flex-wrap">
            <input
                ref="searchInputRef"
                v-model="search"
                type="text"
                placeholder="Search episodes..."
                class="border border-gray-300 p-2 rounded flex-1 min-w-64"
            />

            <label class="flex items-center gap-2">
                <input type="checkbox" v-model="hideCompleted" @change="onFilterChange" />
                Hide Completed
            </label>
            
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700">Sort by:</label>
                <select
                    v-model="sortOrder"
                    @change="onFilterChange"
                    class="border border-gray-300 p-2 rounded text-sm"
                >
                    <option value="newest">Newest First</option>
                    <option value="oldest">Oldest First</option>
                </select>
            </div>
            
            <button
                @click="toggleAllCompleted"
                :disabled="episodes.length === 0 || markingAllCompleted"
                :class="[
                    'px-4 py-2 text-white rounded-md disabled:bg-gray-400 disabled:cursor-not-allowed text-sm font-medium',
                    allEpisodesCompleted ? 'bg-orange-600 hover:bg-orange-700' : 'bg-green-600 hover:bg-green-700'
                ]"
            >
                {{ getButtonText() }}
            </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center text-slate-500">Loading...</div>

        <!-- No Episodes Found -->
        <div v-else-if="episodes.length === 0" class="text-center text-slate-500">
            No episodes found.
        </div>

        <!-- Episodes List - Only this section updates during search -->
        <div v-else class="space-y-4">
            <div v-for="episode in episodes" :key="episode.id" class="dark:bg-neutral-800 bg-neutral-100 p-4 rounded-lg">
                <div class="flex gap-4 items-start">
                    <img 
                        v-if="episode.thumbnail_url" 
                        :src="episode.thumbnail_url" 
                        :alt="episode.title"
                        class="w-32 h-32 object-cover rounded-lg flex-shrink-0" 
                    />
                    <div 
                        v-else
                        class="w-32 h-32 bg-gray-200 rounded-lg flex-shrink-0 flex items-center justify-center"
                    >
                        <span class="text-gray-500 text-xs">No Image</span>
                    </div>

                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100 mb-1">{{ episode.title }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                            {{ formatDate(episode.published_at) }}
                            <span v-if="episode.duration_formatted"> · {{ episode.duration_formatted }}</span>
                        </p>

                        <div v-if="episode.description" class="text-sm text-gray-700 dark:text-gray-400 mb-3">
                            <p class="prose prose-p:text-gray-700 dark:prose-p:text-gray-400">
                                {{ expandedEpisodes[episode.id] ? stripHtml(episode.description) : getShortDescription(episode.description) }}
                                <button
                                    v-if="episode.description.length > 100"
                                    @click="toggleDescription(episode.id)"
                                    class="underline cursor-pointer ml-1"
                                >
                                    {{ expandedEpisodes[episode.id] ? 'Show Less' : 'Show More' }}
                                </button>
                            </p>
                        </div>

                        <a 
                            :href="episode.audio_url" 
                            target="_blank" 
                            class="inline-block text-sm font-medium"
                        >
                            🎧 Listen Now
                        </a>
                    </div>

                    <button
                        @click="toggleCompleted(episode)"
                        class="px-3 py-1 text-sm border rounded-lg transition-colors flex-shrink-0"
                        :class="episode.is_completed 
                            ? 'bg-green-600 text-white border-green-600 hover:bg-green-700' 
                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                        "
                    >
                        {{ episode.is_completed ? '✓ Completed' : 'Mark Complete' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Pagination Controls -->
        <div v-if="pagination && pagination.last_page > 1" class="flex justify-center items-center gap-4 mt-6">
            <div class="text-sm text-gray-700 dark:text-gray-400">
                Showing {{ pagination.from }}-{{ pagination.to }} of {{ pagination.total }} episodes
            </div>
            
            <div class="flex gap-2">
                <!-- Previous Page Button -->
                <button
                    @click="emit('page-change', pagination.current_page - 1)"
                    :disabled="pagination.current_page <= 1"
                    class="px-3 py-1 text-sm border rounded-md transition-colors"
                    :class="pagination.current_page <= 1 
                        ? 'bg-gray-100 text-gray-400 border-gray-200 dark:bg-gray-800 dark:text-gray-600 dark:border-gray-700 cursor-not-allowed' 
                        : 'bg-white text-gray-700 border-gray-300 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600 hover:bg-gray-600'
                    "
                >
                    Previous
                </button>
                
                <!-- Page Numbers -->
                <template v-for="page in getVisiblePages()" :key="page">
                    <button
                        v-if="page !== '...'"
                        @click="emit('page-change', page)"
                        class="px-3 py-1 text-sm border rounded-md transition-colors"
                        :class="page === pagination.current_page 
                            ? 'bg-blue-600 text-white border-blue-600' 
                            : 'bg-white text-gray-700 border-gray-300 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600 hover:bg-gray-600 dark:hover:bg-gray-600 dark:hover:text-gray-200 dark:hover:border-gray-600'
                        "
                    >
                        {{ page }}
                    </button>
                    <span v-else class="px-3 py-1 text-sm text-gray-500 dark:text-gray-400">...</span>
                </template>
                
                <!-- Next Page Button -->
                <button
                    @click="emit('page-change', pagination.current_page + 1)"
                    :disabled="pagination.current_page >= pagination.last_page"
                    class="px-3 py-1 text-sm border rounded-md transition-colors"
                    :class="pagination.current_page >= pagination.last_page 
                        ? 'bg-gray-100 text-gray-400 border-gray-200 dark:bg-gray-800 dark:text-gray-600 dark:border-gray-700 cursor-not-allowed' 
                        : 'bg-white text-gray-700 border-gray-300 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600 hover:bg-gray-600'
                    "
                >
                    Next
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, computed, nextTick } from 'vue';
import axios from 'axios';

const props = defineProps({
    episodes: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    pagination: {
        type: [Object, null],
        default: () => null,
    },
    selectedPodcastId: {
        type: [Number, null],
        default: () => null,
    },
    selectedPodcastTitle: {
        type: [String, null],
        default: () => null,
    },
    podcasts: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['search', 'filter-change', 'page-change']);

const search = ref('');
const hideCompleted = ref(false);
const sortOrder = ref('newest');
const expandedEpisodes = ref({});
const markingAllCompleted = ref(false);
const searchInputRef = ref(null);
const completionStats = ref({ completed: 0, total: 0 });

// Function to strip HTML tags from text
const stripHtml = (html) => {
    if (!html) return '';
    // Create a temporary div element to parse HTML
    const temp = document.createElement('div');
    temp.innerHTML = html;
    return temp.textContent || temp.innerText || '';
};

// Computed property to get the selected podcast
const selectedPodcast = computed(() => {
    if (!props.selectedPodcastId || !props.podcasts) return null;
    const found = props.podcasts.find(podcast => podcast.id === props.selectedPodcastId);
    return found;
});

// Computed property to check if all episodes are completed
const allEpisodesCompleted = computed(() => {
    if (props.episodes.length === 0) return false;
    return props.episodes.every(episode => episode.is_completed);
});

// Function to fetch completion statistics for the selected podcast
const fetchCompletionStats = async () => {
    if (!props.selectedPodcastId) return;
    
    try {
        const response = await axios.get(`/podcasts/${props.selectedPodcastId}/completion-stats`);
        completionStats.value = response.data;
    } catch (error) {
        console.error('Error fetching completion stats:', error);
        // Fallback to pagination data if available
        if (props.pagination) {
            completionStats.value.total = props.pagination.total;
            // For completed count, we can only estimate based on current page
            completionStats.value.completed = props.episodes.filter(episode => episode.is_completed).length;
        }
    }
};

const formatDate = (dateStr) => new Date(dateStr).toLocaleDateString();

const getShortDescription = (description) => {
    if (!description) return '';
    return description.length > 100 ? description.substring(0, 100) + '...' : description;
};

const toggleDescription = (episodeId) => {
    expandedEpisodes.value[episodeId] = !expandedEpisodes.value[episodeId];
};

const toggleCompleted = async (episode) => {
    try {
        const response = await axios.post(`/episodes/${episode.id}/toggle-completed`);
        episode.is_completed = response.data.is_completed;
    } catch (error) {
        console.error('Failed to toggle episode completion:', error);
    }
};
const markAllCompleted = async () => {
    const params = new URLSearchParams();
    
    // Include the selected podcast ID if one is selected
    if (props.selectedPodcastId) {
        params.append('podcast_id', props.selectedPodcastId.toString());
    }
    
    if (search.value) {
        params.append('search', search.value);
    }
    
    const action = allEpisodesCompleted.value ? 'mark-all-incomplete' : 'mark-all-completed';
    const url = `/episodes/${action}?${params.toString()}`;

    try {
        const response = await axios.post(url);
        const newState = !allEpisodesCompleted.value;

        props.episodes.forEach(episode => {
            episode.is_completed = newState;
        });

        console.log(response.data.message);
    } catch (error) {
        console.error(`Failed to ${action.replace('-', ' ')}:`, error);
    } finally {
        markingAllCompleted.value = false;
    }
};

const toggleAllCompleted = () => {
    if (markingAllCompleted.value) return;
    markingAllCompleted.value = true;
    markAllCompleted();
};

const getButtonText = () => {
    if (markingAllCompleted.value) {
        return 'Processing...';
    }
    
    const scope = props.selectedPodcastId ? 'in Podcast' : 'Episodes';
    
    return allEpisodesCompleted.value
        ? `Mark All as Incomplete ${scope}`
        : `Mark All as Completed ${scope}`;
};

const onFilterChange = () => {
    emit('filter-change', {
        hideCompleted: hideCompleted.value,
        sortOrder: sortOrder.value,
    });
};

const getVisiblePages = () => {
    if (!props.pagination) return [];
    
    const current = props.pagination.current_page;
    const last = props.pagination.last_page;
    const delta = 2; // Number of pages to show on each side of current page
    const pages = [];
    
    // Always show first page
    if (last > 1) {
        pages.push(1);
    }
    
    // Add ellipsis after first page if needed
    if (current - delta > 2) {
        pages.push('...');
    }
    
    // Add pages around current page
    for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
        pages.push(i);
    }
    
    // Add ellipsis before last page if needed
    if (current + delta < last - 1) {
        pages.push('...');
    }
    
    // Always show last page (if different from first)
    if (last > 1) {
        pages.push(last);
    }
    
    // Remove duplicates while preserving order
    return pages.filter((page, index, arr) => arr.indexOf(page) === index);
};

// Track if user is actively searching
let isUserSearching = false;
let searchTimeout;

// Watch for changes in selected podcast to fetch completion stats
watch(() => props.selectedPodcastId, (newPodcastId) => {
    if (newPodcastId) {
        fetchCompletionStats();
    }
}, { immediate: true });

// Also fetch stats when episodes change (after completion toggles)
watch(() => props.episodes, () => {
    if (props.selectedPodcastId) {
        fetchCompletionStats();
    }
}, { deep: true });

// Use a debounced approach for search to avoid losing focus
watch(search, (newValue) => {
    clearTimeout(searchTimeout);
    isUserSearching = true;
    
    searchTimeout = setTimeout(() => {
        emit('search', newValue);
    }, 300); // 300ms debounce
});

// Restore focus when episodes update after a search
watch(() => props.episodes, async () => {
    if (isUserSearching && searchInputRef.value) {
        await nextTick();
        searchInputRef.value.focus();
        isUserSearching = false;
    }
}, { flush: 'post' });
</script>

<style scoped>
li {
    transition: background-color .3s ease;
}

li:hover {
    background-color: #f7fafc;
}
</style>
