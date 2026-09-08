<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import { useToast } from '@/composables/useToast';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { STREAMING_IFRAME_ALLOW, streamingEmbedSrc } from '@/utils/videoEmbed';

interface GalleryItem {
    id: number;
    title: string;
    slug: string;
    type: 'image' | 'youtube_video';
    image_path: string | null;
    youtube_url: string | null;
    description: string | null;
    tags: string[];
}

interface Props {
    item: GalleryItem;
    relatedItems: GalleryItem[];
}

const props = defineProps<Props>();
const toast = useToast();

const mediaUrl = (path: string | null) => {
    if (!path) return null;
    return path.startsWith('http') ? path : `/storage/${path}`;
};

const copyLink = async () => {
    try {
        await navigator.clipboard.writeText(window.location.href);
        toast.success('Share link copied successfully!');
    } catch {
        toast.error(
            'Unable to copy link. Please copy it manually from the address bar.',
        );
    }
};
</script>

<template>
    <PublicLayout>
        <Head :title="`${item.title} - Gallery`" />

        <section class="bg-white py-16">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <Link
                    href="/gallery"
                    class="text-[#42b6c5] hover:text-[#35919e]"
                    >← Back to Gallery</Link
                >
                <div class="mt-4 flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-4xl font-bold text-[#000928]">
                            {{ item.title }}
                        </h1>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span
                                v-for="tag in item.tags || []"
                                :key="tag"
                                class="rounded-full bg-[#42b6c5]/10 px-2 py-1 text-xs text-[#42b6c5]"
                                >#{{ tag }}</span
                            >
                        </div>
                    </div>
                    <button
                        @click="copyLink"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        Share
                    </button>
                </div>

                <div class="mt-8 overflow-hidden rounded-2xl shadow-xl">
                    <img
                        v-if="item.type === 'image'"
                        :src="mediaUrl(item.image_path) || ''"
                        :alt="item.title"
                        class="max-h-[520px] w-full object-cover"
                    />
                    <div v-else class="aspect-video bg-black">
                        <iframe
                            :src="
                                streamingEmbedSrc(item.youtube_url) ||
                                item.youtube_url ||
                                ''
                            "
                            class="h-full w-full"
                            referrerpolicy="strict-origin-when-cross-origin"
                            :allow="STREAMING_IFRAME_ALLOW"
                            allowfullscreen
                        />
                    </div>
                </div>

                <p
                    v-if="item.description"
                    class="mt-8 leading-relaxed text-gray-700"
                >
                    {{ item.description }}
                </p>

                <div v-if="relatedItems.length > 0" class="mt-14">
                    <h2 class="mb-4 text-2xl font-bold text-[#000928]">
                        Related Gallery Items
                    </h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <Link
                            v-for="related in relatedItems"
                            :key="related.id"
                            :href="`/gallery/${related.slug}`"
                            class="rounded-xl border border-gray-200 p-4 transition-shadow hover:shadow-lg"
                        >
                            <p
                                class="line-clamp-2 font-semibold text-[#000928]"
                            >
                                {{ related.title }}
                            </p>
                            <p class="mt-1 text-sm text-gray-500">
                                {{
                                    related.type === 'image' ? 'Image' : 'Video'
                                }}
                            </p>
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
