<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    title: string;
    description: string;
    image?: string;
    type?: string;
}

const props = withDefaults(defineProps<Props>(), {
    image: '/images/academy-community/classroom/cohort-classroom-wide.jpg',
    type: 'website',
});

// The global Inertia titleCallback (see resources/js/app.ts) already appends
// " - Traitz Academy" to whatever `<Head title>` receives, so pass the raw
// title there. Meta tags aren't touched by that callback, so they need the
// fully-qualified title built here instead.
const metaTitle = computed(() =>
    props.title.includes('Traitz Academy') ? props.title : `${props.title} - Traitz Academy`,
);

const absoluteImage = computed(() =>
    props.image.startsWith('http') ? props.image : `${window.location.origin}${props.image}`,
);
</script>

<template>
    <Head :title="title">
        <meta name="description" :content="description" />
        <meta property="og:type" :content="type" />
        <meta property="og:title" :content="metaTitle" />
        <meta property="og:description" :content="description" />
        <meta property="og:image" :content="absoluteImage" />
        <meta property="og:site_name" content="Traitz Academy" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="metaTitle" />
        <meta name="twitter:description" :content="description" />
        <meta name="twitter:image" :content="absoluteImage" />
    </Head>
</template>
