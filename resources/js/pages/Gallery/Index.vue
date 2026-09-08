<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import SeoHead from '@/components/SeoHead.vue';
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

interface PaginatedItems {
    data: GalleryItem[];
}

interface Props {
    items: PaginatedItems;
}

const props = defineProps<Props>();
const toast = useToast();

const activeTag = ref('all');
const lightboxImage = ref<GalleryItem | null>(null);

const mediaUrl = (path: string | null) => {
    if (!path) {
        return null;
    }

    return path.startsWith('http') ? path : `/storage/${path}`;
};

const galleryVideoEmbedSrc = (url: string | null) => {
    if (!url) {
        return null;
    }
    return streamingEmbedSrc(url) ?? url;
};

const tags = computed(() => {
    const allTags = new Set<string>();

    props.items.data.forEach((item) => {
        (item.tags || []).forEach((tag) => allTags.add(tag));
    });

    return ['all', ...Array.from(allTags)];
});

const filteredItems = computed(() => {
    if (activeTag.value === 'all') {
        return props.items.data;
    }

    return props.items.data.filter((item) =>
        (item.tags || []).includes(activeTag.value),
    );
});

const copyShareLink = async (path: string) => {
    try {
        const absoluteUrl = `${window.location.origin}${path}`;
        await navigator.clipboard.writeText(absoluteUrl);
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
        <SeoHead
            title="Gallery"
            description="Photos and videos from Traitz Academy classes, workshops and community activities — a look inside our programs at ENS Street, Bambili."
        />

        <section class="bg-white py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-12 text-center">
                    <h1
                        class="mb-4 text-4xl font-bold text-[#000928] md:text-5xl"
                    >
                        Gallery
                    </h1>
                    <p class="mx-auto max-w-3xl text-lg text-gray-600">
                        Explore our moments through images and videos from
                        trainings, workshops, and events.
                    </p>
                </div>

                <div
                    v-if="tags.length > 1"
                    class="mb-8 flex flex-wrap justify-center gap-2"
                >
                    <button
                        v-for="tag in tags"
                        :key="tag"
                        @click="activeTag = tag"
                        class="rounded-full border px-3 py-1.5 text-sm transition-colors"
                        :class="
                            activeTag === tag
                                ? 'border-[#42b6c5] bg-[#42b6c5] text-white'
                                : 'border-gray-300 bg-white text-gray-700 hover:border-[#42b6c5] hover:text-[#42b6c5]'
                        "
                    >
                        {{ tag === 'all' ? 'All' : `#${tag}` }}
                    </button>
                </div>

                <div
                    class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <article
                        v-for="item in filteredItems"
                        :key="item.id"
                        class="group overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition-all duration-300 hover:shadow-xl"
                    >
                        <div
                            class="relative aspect-[4/3] overflow-hidden bg-gray-100"
                        >
                            <img
                                v-if="item.type === 'image'"
                                :src="mediaUrl(item.image_path) || ''"
                                :alt="item.title"
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                            />
                            <iframe
                                v-else
                                :src="
                                    galleryVideoEmbedSrc(item.youtube_url) || ''
                                "
                                class="h-full w-full"
                                referrerpolicy="strict-origin-when-cross-origin"
                                :allow="STREAMING_IFRAME_ALLOW"
                                allowfullscreen
                            />
                            <button
                                v-if="item.type === 'image'"
                                @click="lightboxImage = item"
                                class="absolute inset-0 bg-black/0 transition-colors group-hover:bg-black/20"
                                aria-label="View full image"
                            />
                        </div>
                        <div class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <h3
                                    class="line-clamp-2 font-bold text-[#000928]"
                                >
                                    {{ item.title }}
                                </h3>
                                <span
                                    class="rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-700"
                                    >{{
                                        item.type === 'image'
                                            ? 'Image'
                                            : 'Video'
                                    }}</span
                                >
                            </div>
                            <p
                                v-if="item.description"
                                class="mt-2 line-clamp-2 text-sm text-gray-600"
                            >
                                {{ item.description }}
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span
                                    v-for="tag in item.tags || []"
                                    :key="tag"
                                    class="rounded-full bg-[#42b6c5]/10 px-2 py-1 text-xs text-[#42b6c5]"
                                    >#{{ tag }}</span
                                >
                            </div>
                            <div class="mt-4 flex items-center justify-between">
                                <Link
                                    :href="`/gallery/${item.slug}`"
                                    class="text-sm font-semibold text-[#381998] hover:text-[#42b6c5]"
                                    >View details</Link
                                >
                                <button
                                    @click="
                                        copyShareLink(`/gallery/${item.slug}`)
                                    "
                                    class="text-xs text-gray-600 hover:text-[#42b6c5]"
                                >
                                    Share
                                </button>
                            </div>
                        </div>
                    </article>
                </div>

                <p
                    v-if="filteredItems.length === 0"
                    class="mt-8 text-center text-gray-500"
                >
                    No gallery items found for this tag yet.
                </p>
            </div>
        </section>

        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="lightboxImage"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
                @click="lightboxImage = null"
            >
                <div class="relative w-full max-w-6xl" @click.stop>
                    <button
                        class="absolute -top-12 right-0 text-sm text-white hover:text-[#42b6c5]"
                        @click="lightboxImage = null"
                    >
                        Close ✕
                    </button>
                    <img
                        :src="mediaUrl(lightboxImage.image_path) || ''"
                        :alt="lightboxImage.title"
                        class="max-h-[85vh] w-full rounded-xl object-contain shadow-2xl"
                    />
                    <div class="mt-3 text-white">
                        <h3 class="text-lg font-semibold">
                            {{ lightboxImage.title }}
                        </h3>
                        <p
                            v-if="lightboxImage.description"
                            class="text-sm text-gray-300"
                        >
                            {{ lightboxImage.description }}
                        </p>
                    </div>
                </div>
            </div>
        </transition>
    </PublicLayout>
</template>
