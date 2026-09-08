<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import SeoHead from '@/components/SeoHead.vue';
import { useToast } from '@/composables/useToast';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface LearningResource {
    id: number;
    title: string;
    slug: string;
    type: 'document' | 'youtube_video' | 'writing' | 'external_link';
    description: string | null;
    tags: string[];
}

interface PaginatedResources {
    data: LearningResource[];
}

interface Props {
    resources: PaginatedResources;
}

const props = defineProps<Props>();
const toast = useToast();

const activeTag = ref('all');

const tags = computed(() => {
    const allTags = new Set<string>();

    props.resources.data.forEach((resource) => {
        (resource.tags || []).forEach((tag) => allTags.add(tag));
    });

    return ['all', ...Array.from(allTags)];
});

const filteredResources = computed(() => {
    if (activeTag.value === 'all') {
        return props.resources.data;
    }

    return props.resources.data.filter((resource) =>
        (resource.tags || []).includes(activeTag.value),
    );
});

const typeLabel = (type: LearningResource['type']) => {
    switch (type) {
        case 'document':
            return 'Document';
        case 'youtube_video':
            return 'Video';
        case 'writing':
            return 'Writing';
        case 'external_link':
            return 'External Link';
        default:
            return 'Resource';
    }
};

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
            title="Learning Resources"
            description="Documents, videos, writings and curated external resources to support your learning at Traitz Academy."
        />

        <section class="bg-gray-50 py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-12 text-center">
                    <h1
                        class="mb-4 text-4xl font-bold text-[#000928] md:text-5xl"
                    >
                        Learning Resources
                    </h1>
                    <p class="mx-auto max-w-3xl text-lg text-gray-600">
                        Free documents, videos, writings, and useful links
                        curated for learners and professionals.
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
                                ? 'border-[#381998] bg-[#381998] text-white'
                                : 'border-gray-300 bg-white text-gray-700 hover:border-[#381998] hover:text-[#381998]'
                        "
                    >
                        {{ tag === 'all' ? 'All' : `#${tag}` }}
                    </button>
                </div>

                <div
                    class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
                >
                    <article
                        v-for="resource in filteredResources"
                        :key="resource.id"
                        class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
                    >
                        <div
                            class="mb-3 flex items-start justify-between gap-2"
                        >
                            <h3
                                class="line-clamp-2 text-xl font-bold text-[#000928]"
                            >
                                {{ resource.title }}
                            </h3>
                            <span
                                class="rounded-full bg-[#381998]/10 px-2 py-1 text-xs text-[#381998]"
                                >{{ typeLabel(resource.type) }}</span
                            >
                        </div>
                        <p
                            v-if="resource.description"
                            class="line-clamp-3 text-sm text-gray-600"
                        >
                            {{ resource.description }}
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span
                                v-for="tag in resource.tags || []"
                                :key="tag"
                                class="rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-700"
                                >#{{ tag }}</span
                            >
                        </div>
                        <div class="mt-5 flex items-center justify-between">
                            <Link
                                :href="`/resources/${resource.slug}`"
                                class="text-sm font-semibold text-[#381998] hover:text-[#42b6c5]"
                                >Open resource</Link
                            >
                            <button
                                @click="
                                    copyShareLink(`/resources/${resource.slug}`)
                                "
                                class="text-xs text-gray-600 hover:text-[#42b6c5]"
                            >
                                Share
                            </button>
                        </div>
                    </article>
                </div>

                <p
                    v-if="filteredResources.length === 0"
                    class="mt-8 text-center text-gray-500"
                >
                    No resources found for this tag yet.
                </p>
            </div>
        </section>
    </PublicLayout>
</template>
