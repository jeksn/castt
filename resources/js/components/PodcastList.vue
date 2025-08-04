<template>
    <div class="space-y-4">
        <div v-for="podcast in podcasts" :key="podcast.id" >
            <div @click="$emit('select-podcast', podcast.id)"
                class="border rounded-lg p-4 flex flex-col items-start gap-4 cursor-pointer hover:border-neutral-200 dark:bg-black/10 bg-neutral-100"
                :class="{'border-neutral-200 shadow-md': podcast.id === selectedPodcastId}"
            >
				<div class="flex items-center gap-2">
                <img :src="podcast.image_url" alt="Podcast image" class="w-16 h-16 rounded-full object-cover" v-if="podcast.image_url" />
                <div class="flex-1 flex flex-col gap-1">
                    <h3 class="font-semibold text-lg">{{ podcast.title }}</h3>
                    <p class="text-sm text-slate-500">{{ podcast.author }}</p>
                </div>
				</div>
				<div class="flex gap-2 justify-end w-full">
                	<button @click.stop="refreshFeed(podcast)" class="px-2 py-1 text-blue-500 border border-blue-500 rounded-lg text-sm">
						<RefreshCcw class="size-4" />
					</button>
                	<button @click.stop="deletePodcast(podcast)" class="px-2 py-1 text-red-500 border border-red-500 rounded-lg text-sm">
						<Trash2 class="size-4" />
					</button>
				</div>
            </div>
        </div>
    </div>
</template>
		
<script setup lang="ts">
import axios from 'axios';
import { RefreshCcw, Trash2 } from 'lucide-vue-next';
import { type Podcast } from '@/types';

defineProps<{
    podcasts: Podcast[];
    selectedPodcastId: number | null;
}>();

const emit = defineEmits(['select-podcast', 'podcast-refreshed']);

const refreshFeed = async (podcast: Podcast) => {
    try {
        await axios.post(`/podcasts/${podcast.id}/refresh`);
        emit('podcast-refreshed');
    } catch (error) {
        console.error('Failed to refresh podcast:', error);
    }
};

const deletePodcast = async (podcast: Podcast) => {
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
