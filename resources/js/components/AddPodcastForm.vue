<template>
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-96">
            <h2 class="text-xl font-semibold mb-4">Add New Podcast</h2>
            
            <form @submit.prevent="submitForm">
                <div class="mb-4">
                    <label for="rss_url" class="block text-sm font-medium text-gray-700 mb-2">
                        RSS Feed URL
                    </label>
                    <input
                        id="rss_url"
                        v-model="form.rss_url"
                        type="url"
                        required
                        placeholder="https://example.com/feed.xml"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    <div v-if="errors.rss_url" class="text-red-500 text-sm mt-1">
                        {{ errors.rss_url }}
                    </div>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="loading"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{ loading ? 'Adding...' : 'Add Podcast' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const emit = defineEmits(['close', 'podcast-added']);

const form = ref({
    rss_url: '',
});

const loading = ref(false);
const errors = ref({});

const submitForm = () => {
    loading.value = true;
    errors.value = {};
    
    router.post('/podcasts', form.value, {
        onSuccess: () => {
            emit('podcast-added');
        },
        onError: (formErrors) => {
            errors.value = formErrors;
            loading.value = false;
        },
        onFinish: () => {
            loading.value = false;
        },
    });
};
</script>
