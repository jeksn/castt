<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PodcastList from '@/components/PodcastList.vue';
import EpisodeList from '@/components/EpisodeList.vue';
import AddPodcastForm from '@/components/AddPodcastForm.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';

interface Podcast {
    id: number;
    title: string;
    description?: string;
    image_url?: string;
    author?: string;
    last_refreshed_at?: string;
}

interface Episode {
    id: number;
    title: string;
    description?: string;
    audio_url: string;
    thumbnail_url?: string;
    duration_formatted?: string;
    published_at: string;
    is_completed: boolean;
    podcast: Podcast;
}

interface PaginationData {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: any[];
}

const props = defineProps<{
    podcasts: Podcast[];
}>();

console.log('Dashboard props:', props);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const selectedPodcastId = ref<number | null>(null);
const episodes = ref<Episode[]>([]);
const episodesLoading = ref(false);
const searchQuery = ref('');
const hideCompleted = ref(false);
const sortOrder = ref('newest');
const showAddPodcastForm = ref(false);
const currentPage = ref(1);
const paginationData = ref<PaginationData | null>(null);

const loadEpisodes = async (page: number = 1) => {
    episodesLoading.value = true;
    try {
        const params = new URLSearchParams();
        
        params.append('page', page.toString());
        
        if (selectedPodcastId.value) {
            params.append('podcast_id', selectedPodcastId.value.toString());
        }
        if (searchQuery.value) {
            params.append('search', searchQuery.value);
        }
        if (hideCompleted.value) {
            params.append('hide_completed', '1');
        }
        if (sortOrder.value) {
            params.append('sort_order', sortOrder.value);
        }
        
        const response = await axios.get(`/episodes?${params.toString()}`);
        episodes.value = response.data.data;
        paginationData.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            per_page: response.data.per_page,
            total: response.data.total,
            from: response.data.from,
            to: response.data.to,
            links: response.data.links
        };
        currentPage.value = response.data.current_page;
    } catch (error) {
        console.error('Failed to load episodes:', error);
    } finally {
        episodesLoading.value = false;
    }
};

const selectPodcast = (podcastId: number | null) => {
    selectedPodcastId.value = podcastId;
    currentPage.value = 1;
    loadEpisodes(1);
};

const handleSearch = (query: string) => {
    searchQuery.value = query;
    currentPage.value = 1;
    loadEpisodes(1);
};

const handleFilterChange = (filters: { hideCompleted: boolean; sortOrder: string }) => {
    hideCompleted.value = filters.hideCompleted;
    sortOrder.value = filters.sortOrder;
    currentPage.value = 1;
    loadEpisodes(1);
};

const handlePageChange = (page: number) => {
    currentPage.value = page;
    loadEpisodes(page);
};

const handlePodcastAdded = () => {
    showAddPodcastForm.value = false;
    // Refresh the page to show new podcast
    window.location.reload();
};

const handlePodcastRefreshed = () => {
    loadEpisodes(currentPage.value);
};

onMounted(() => {
    console.log('Dashboard mounted, podcasts:', props.podcasts);
    loadEpisodes();
});
</script>

<template>
    <Head title="Podcast Manager" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 gap-4 p-4">
            <!-- Left Sidebar - Podcast List -->
            <div class="w-80 flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Podcasts</h2>
                    <button 
                        @click="showAddPodcastForm = true"
                        class="px-3 py-2 cursor-pointer bg-neutral-200 text-neutral-800 rounded-md hover:bg-neutral-300 text-sm"
                    >
                        Add Podcast
                    </button>
                </div>
                
                <PodcastList 
                    :podcasts="podcasts"
                    :selected-podcast-id="selectedPodcastId"
                    @select-podcast="selectPodcast"
                    @podcast-refreshed="handlePodcastRefreshed"
                />
            </div>
            
            <!-- Right Main Content - Episode List -->
            <div class="flex-1 flex flex-col gap-4">
                <EpisodeList 
                    :episodes="episodes"
                    :loading="episodesLoading"
                    :pagination="paginationData"
                    :selected-podcast-id="selectedPodcastId"
                    :selected-podcast-title="selectedPodcastId ? podcasts.find(p => p.id === selectedPodcastId)?.title : null"
                    @search="handleSearch"
                    @filter-change="handleFilterChange"
                    @page-change="handlePageChange"
                />
            </div>
        </div>
        
        <!-- Add Podcast Modal -->
        <AddPodcastForm 
            v-if="showAddPodcastForm"
            @close="showAddPodcastForm = false"
            @podcast-added="handlePodcastAdded"
        />
    </AppLayout>
</template>
