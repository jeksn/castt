<template>
    <div class="space-y-4">
        <div v-for="podcast in podcasts" :key="podcast.id">
            <div @click="$emit('select-podcast', podcast.id)"
                class="border rounded-lg p-4 flex items-center gap-4 cursor-pointer"
                :class="{'border-blue-500 shadow-md': podcast.id === selectedPodcastId}"
            >
                <img :src="podcast.image_url" alt="Podcast image" class="w-16 h-16 rounded-full object-cover" v-if="podcast.image_url" />
                <div class="flex-1">
                    <h3 class="font-semibold text-lg">{{ podcast.title }}</h3>
                    <p class="text-sm text-slate-500">{{ podcast.author }}</p>
                </div>
                <button @click.stop="refreshFeed(podcast)" class="px-2 py-1 text-blue-500 border border-blue-500 rounded-lg text-sm">Refresh</button>
                <button @click.stop="deletePodcast(podcast)" class="px-2 py-1 text-red-500 border border-red-500 rounded-lg text-sm">Delete</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios';

const props = defineProps({
    podcasts: Array,
    selectedPodcastId: Number,
});

const emit = defineEmits(['select-podcast', 'podcast-refreshed']);

const refreshFeed = async (podcast) => {
    try {
        await axios.post(`/podcasts/${podcast.id}/refresh`);
        emit('podcast-refreshed');
    } catch (error) {
        console.error('Failed to refresh podcast:', error);
    }
};

const deletePodcast = async (podcast) => {
    if (confirm(`Are you sure you want to delete the podcast "${podcast.title}"?`)) {
        try {
            await axios.delete(`/podcasts/${podcast.id}`);
            window.location.reload();
        } catch (error) {
            console.error('Failed to delete podcast:', error);
        }
    }
};
</script>

<style scoped>
.cursor-pointer:hover {
    background-color: #f7fafc;
}
</style>
