<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
  ArrowLeft, BookOpen, ChevronDown, Edit2, FileText,
  GripVertical, ImageIcon, Layers, Paperclip, PlusCircle, Send, Trash2, Upload, UserPlus, Video,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, shallowRef, watch } from 'vue';
import draggable from 'vuedraggable';

import ConfirmationModal from '@/components/ConfirmationModal.vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { STREAMING_IFRAME_ALLOW, streamingEmbedSrc } from '@/utils/videoEmbed';

// ─── Types ────────────────────────────────────────────────────────────────────

interface Category { id: number; name: string; slug: string }

interface LessonAttachment {
  id: number;
  name: string;
  file_url: string;
  file_type: string | null;
  file_size: number | null;
  formatted_file_size: string;
}

interface Lesson {
  id: number;
  title: string;
  type: 'video' | 'text' | 'quiz';
  description: string | null;
  content: string | null;
  video_url?: string | null;
  youtube_video_id?: string | null;
  youtube_status?: 'pending' | 'processing' | 'ready' | 'failed' | null;
  youtube_error?: string | null;
  duration: string | null;
  is_free: boolean;
  sort_order: number;
  attachments?: LessonAttachment[];
}

interface Section {
  id: number;
  title: string;
  description: string | null;
  sort_order: number;
  lessons: Lesson[];
}

interface Course {
  id: number;
  title: string;
  slug: string;
  short_description: string;
  description: string | null;
  cover_image: string | null;
  level: 'beginner' | 'intermediate' | 'advanced';
  status: 'draft' | 'pending_review' | 'published' | 'archived';
  price: string;
  sale_price: string | null;
  max_installments?: number;
  duration: string | null;
  category: Category | null;
  sections: Section[];
}

const props = defineProps<{
  course: Course;
  categories: Category[];
  can_manually_enroll: boolean;
}>();

// ─── Tab ──────────────────────────────────────────────────────────────────────

const activeTab = ref<'details' | 'curriculum' | 'publish'>('details');
const highlightedLessonId = ref<number | null>(null);

// ─── Details form ─────────────────────────────────────────────────────────────

const detailsForm = useForm({
  title:             props.course.title,
  category_id:       props.course.category?.id ?? '',
  level:             props.course.level,
  short_description: props.course.short_description,
  description:       props.course.description ?? '',
  price:             props.course.price ?? '',
  sale_price:        props.course.sale_price ?? '',
  max_installments:  Math.max(1, props.course.max_installments ?? 1),
  duration:          props.course.duration ?? '',
});

function saveDetails() {
  detailsForm.put(`/tutor/courses/${props.course.id}`, { preserveScroll: true });
}

// ─── Cover image ──────────────────────────────────────────────────────────────

const coverForm    = useForm({ cover_image: null as File | null });
const localPreview = ref<string | null>(null);

// Always reflects the latest prop value; localPreview takes precedence while a
// file is selected but not yet uploaded.
const coverPreview = computed(() => {
  if (localPreview.value) return localPreview.value;
  if (!props.course.cover_image) return null;
  return props.course.cover_image.startsWith('http')
    ? props.course.cover_image
    : `/storage/${props.course.cover_image}`;
});

function onCoverSelected(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0];
  if (!file) return;
  coverForm.cover_image = file;
  localPreview.value = URL.createObjectURL(file);
}

function uploadCover() {
  coverForm.post(`/tutor/courses/${props.course.id}/cover`, {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      // Clear local blob URL — computed will now show the saved image from props
      localPreview.value = null;
      coverForm.cover_image = null;
    },
  });
}

// ─── Submit for review ────────────────────────────────────────────────────────

const showSubmitModal = ref(false);

function submitForReview() {
  router.post(`/tutor/courses/${props.course.id}/submit`, {}, {
    preserveScroll: true,
    onFinish: () => { showSubmitModal.value = false; },
  });
}

// ─── Section management ───────────────────────────────────────────────────────

const expandedSections = ref<Set<number>>(new Set(props.course.sections.map((s) => s.id)));
const addSectionOpen   = ref(false);
const editingSectionId = ref<number | null>(null);

/** Local copy for drag-and-drop; kept in sync with `course.sections` from the server. */
function cloneSections(sections: Section[]): Section[] {
  return sections.map((s) => ({
    ...s,
    lessons: s.lessons.map((l) => ({
      ...l,
      attachments: [...(l.attachments ?? [])],
    })),
  }));
}

const sectionList = shallowRef<Section[]>(cloneSections(props.course.sections));

watch(
  () => props.course.sections,
  (sections) => {
    sectionList.value = cloneSections(sections);
  },
  { deep: true },
);

const sectionForm = useForm({ title: '', description: '' });

function addSection() {
  sectionForm.post(`/tutor/courses/${props.course.id}/sections`, {
    preserveScroll: true,
    onSuccess: () => { sectionForm.reset(); addSectionOpen.value = false; },
  });
}

function openEditSection(section: Section) {
  editingSectionId.value  = section.id;
  sectionForm.title       = section.title;
  sectionForm.description = section.description ?? '';
  addSectionOpen.value    = false;
}

function saveSection(sectionId: number) {
  sectionForm.put(`/tutor/courses/${props.course.id}/sections/${sectionId}`, {
    preserveScroll: true,
    onSuccess: () => { editingSectionId.value = null; sectionForm.reset(); },
  });
}

function deleteSection(sectionId: number) {
  if (!confirm('Delete this section and all its lessons?')) return;
  router.delete(`/tutor/courses/${props.course.id}/sections/${sectionId}`, { preserveScroll: true });
}

function toggleSection(id: number) {
  if (expandedSections.value.has(id)) {
    expandedSections.value.delete(id)
  } else {
    expandedSections.value.add(id)
  }
}

function onSectionsDragEnd() {
  const order = sectionList.value.map((s) => s.id);
  const prev = props.course.sections.map((s) => s.id);
  if (order.length === prev.length && order.every((id, i) => id === prev[i])) return;
  router.post(`/tutor/courses/${props.course.id}/sections/reorder`, { order }, { preserveScroll: true });
}

function onLessonsDragEnd(section: Section) {
  const order = section.lessons.map((l) => l.id);
  const prev = props.course.sections.find((s) => s.id === section.id)?.lessons.map((l) => l.id) ?? [];
  if (order.length === prev.length && order.every((id, i) => id === prev[i])) return;
  router.post(
    `/tutor/courses/${props.course.id}/sections/${section.id}/lessons/reorder`,
    { order },
    { preserveScroll: true },
  );
}

// ─── Lesson management ────────────────────────────────────────────────────────

const addLessonSectionId   = ref<number | null>(null);
const editingLessonId      = ref<number | null>(null);
const editingLessonSection = ref<number | null>(null);

/** Optional video file chosen before “Add lesson” — uploaded automatically once the lesson exists. */
const pendingNewLessonVideoFile = ref<File | null>(null);
const newLessonVideoInputRef    = ref<HTMLInputElement | null>(null);

const lessonForm = useForm({
  title: '', type: 'video' as 'video' | 'text' | 'quiz',
  description: '', content: '', duration: '', is_free: false,
});

function clearPendingNewLessonVideo() {
  pendingNewLessonVideoFile.value = null;
  if (newLessonVideoInputRef.value) {
    newLessonVideoInputRef.value.value = '';
  }
}

function onPendingNewLessonVideoSelected(event: Event) {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0] ?? null;
  pendingNewLessonVideoFile.value = file;
}

watch(
  () => lessonForm.type,
  (type) => {
    if (type !== 'video') {
      lessonForm.duration = '';
      clearPendingNewLessonVideo();
    }
  },
);

const lessonTypeIcons:  Record<string, any>    = { video: Video, text: FileText, quiz: BookOpen };
const lessonTypeLabels: Record<string, string> = { video: 'Video', text: 'Text', quiz: 'Quiz' };

function openAddLesson(sectionId: number) {
  addLessonSectionId.value   = sectionId;
  editingLessonId.value      = null;
  editingLessonSection.value = null;
  clearPendingNewLessonVideo();
  lessonForm.reset();
  lessonForm.content = '';
  lessonForm.clearErrors();
}

function openEditLesson(section: Section, lesson: Lesson) {
  editingLessonId.value      = lesson.id;
  editingLessonSection.value = section.id;
  addLessonSectionId.value   = null;
  lessonForm.title       = lesson.title;
  lessonForm.type        = lesson.type;
  lessonForm.description = lesson.description ?? '';
  lessonForm.content     = lesson.content ?? '';
  lessonForm.duration    = lesson.duration ?? '';
  lessonForm.is_free     = lesson.is_free;
}

function addLesson(sectionId: number) {
  const wasVideo = lessonForm.type === 'video';
  const fileToUpload = pendingNewLessonVideoFile.value;

  lessonForm.post(`/tutor/courses/${props.course.id}/sections/${sectionId}/lessons`, {
    preserveScroll: true,
    onSuccess: (page) => {
      clearPendingNewLessonVideo();
      lessonForm.reset();

      if (!wasVideo || !fileToUpload) {
        addLessonSectionId.value = null;
        return;
      }

      const course = page.props.course as Course;
      const section = course.sections.find((s) => s.id === sectionId);
      if (!section?.lessons.length) {
        addLessonSectionId.value = null;
        return;
      }

      const newLesson = section.lessons.reduce((best, l) => (l.id > best.id ? l : best));

      videoUploadForm.clearErrors();
      videoUploadForm.video_file = fileToUpload;
      videoUploadForm.post(
        `/tutor/courses/${props.course.id}/sections/${sectionId}/lessons/${newLesson.id}/video`,
        {
          forceFormData: true,
          preserveScroll: true,
          onSuccess: () => {
            videoUploadForm.reset();
          },
          onFinish: () => {
            addLessonSectionId.value = null;
          },
        },
      );
    },
  });
}

function saveLesson(sectionId: number, lessonId: number) {
  lessonForm.put(`/tutor/courses/${props.course.id}/sections/${sectionId}/lessons/${lessonId}`, {
    preserveScroll: true,
    onSuccess: () => {
      editingLessonId.value      = null;
      editingLessonSection.value = null;
      lessonForm.reset();
    },
  });
}

function deleteLesson(sectionId: number, lessonId: number) {
  if (!confirm('Delete this lesson?')) return;
  router.delete(`/tutor/courses/${props.course.id}/sections/${sectionId}/lessons/${lessonId}`, { preserveScroll: true });
}

function resourcePublicUrl(path: string): string {
  if (path.startsWith('http://') || path.startsWith('https://')) return path;
  return `/storage/${path}`;
}

function uploadLessonResource(section: Section, lesson: Lesson, event: Event) {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];
  if (!file) return;
  router.post(
    `/tutor/courses/${props.course.id}/sections/${section.id}/lessons/${lesson.id}/attachments`,
    { file, name: file.name },
    {
      forceFormData: true,
      preserveScroll: true,
      onFinish: () => {
        input.value = '';
      },
    },
  );
}

function deleteLessonAttachment(section: Section, lesson: Lesson, attachmentId: number) {
  if (!confirm('Remove this file?')) return;
  router.delete(
    `/tutor/courses/${props.course.id}/sections/${section.id}/lessons/${lesson.id}/attachments/${attachmentId}`,
    { preserveScroll: true },
  );
}

// ─── Status config ────────────────────────────────────────────────────────────

const statusConfig: Record<string, { label: string; class: string }> = {
  draft:          { label: 'Draft',          class: 'bg-gray-100 text-gray-600' },
  pending_review: { label: 'Pending Review', class: 'bg-yellow-100 text-yellow-700' },
  published:      { label: 'Published',      class: 'bg-green-100 text-green-700' },
  archived:       { label: 'Archived',       class: 'bg-orange-100 text-orange-700' },
};

const canSubmit = ['draft', 'archived'].includes(props.course.status);

const enrollForm = useForm({ email: '' });

function submitEnrollStudent() {
  enrollForm.post(`/tutor/courses/${props.course.id}/enroll-student`, { preserveScroll: true });
}

const videoUploadForm = useForm({
  video_file: null as File | null,
});

function shouldConfirmVideoReplacement(sectionId: number, lessonId: number): boolean {
  const section = props.course.sections.find((s) => s.id === sectionId);
  if (!section) return false;

  const lesson = section.lessons.find((l) => l.id === lessonId);
  if (!lesson) return false;

  return Boolean(lesson.video_url || lesson.youtube_video_id);
}

function onLessonVideoSelected(event: Event, sectionId: number, lessonId: number) {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0] ?? null;
  if (!file) return;

  if (shouldConfirmVideoReplacement(sectionId, lessonId)) {
    const shouldReplace = window.confirm(
      'Replace this video? The current YouTube video will be deleted and replaced with the new upload.'
    );
    if (!shouldReplace) {
      input.value = '';
      return;
    }
  }

  videoUploadForm.video_file = file;
  uploadLessonVideo(sectionId, lessonId);
}

function uploadLessonVideo(sectionId: number, lessonId: number) {
  if (!videoUploadForm.video_file) return;

  videoUploadForm.post(
    `/tutor/courses/${props.course.id}/sections/${sectionId}/lessons/${lessonId}/video`,
    {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => {
        videoUploadForm.reset();
      },
    }
  );
}

function lessonEmbedUrl(url: string | null | undefined): string | null {
  return streamingEmbedSrc(url ?? null);
}

onMounted(async () => {
  const params = new URLSearchParams(window.location.search);
  const lesson = Number(params.get('lesson'));
  const section = Number(params.get('section'));

  if (Number.isFinite(lesson) && lesson > 0) {
    highlightedLessonId.value = lesson;
    activeTab.value = 'curriculum';
  }

  if (Number.isFinite(section) && section > 0) {
    expandedSections.value.add(section);
  }

  if (highlightedLessonId.value !== null) {
    await nextTick();
    const el = document.getElementById(`lesson-row-${highlightedLessonId.value}`);
    el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
});
</script>

<template>
  <AppLayout :breadcrumbs="[
    { title: 'My Courses', href: '/tutor/courses' },
    { title: course.title, href: `/tutor/courses/${course.id}/edit` },
  ]">
    <Head :title="`Edit: ${course.title}`" />

    <!-- Page header -->
    <div class="mb-6 flex items-center gap-4">
      <Link
        href="/tutor/courses"
        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-gray-200 text-gray-500 transition-colors hover:border-[#381998] hover:text-[#381998] dark:border-gray-700"
      >
        <ArrowLeft class="h-4 w-4" />
      </Link>
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-3">
          <h1 class="truncate text-xl font-bold text-[#000928] dark:text-white">{{ course.title }}</h1>
          <span :class="['shrink-0 rounded-full px-2.5 py-0.5 text-xs font-bold', statusConfig[course.status]?.class]">
            {{ statusConfig[course.status]?.label }}
          </span>
        </div>
        <p class="mt-0.5 text-sm text-gray-500">Course editor</p>
      </div>
    </div>

    <!-- Tab bar -->
    <div class="mb-6 flex w-fit gap-1 rounded-xl border border-gray-200 bg-white p-1 shadow-sm dark:bg-gray-800 dark:border-gray-700">
      <button
        v-for="tab in [
          { id: 'details',    label: 'Course Details', icon: FileText },
          { id: 'curriculum', label: 'Curriculum',     icon: Layers },
          { id: 'publish',    label: 'Publish',        icon: Send },
        ]"
        :key="tab.id"
        @click="activeTab = (tab.id as any)"
        :class="[
          'flex items-center gap-2 rounded-lg px-5 py-2 text-sm font-medium transition-colors',
          activeTab === tab.id
            ? 'bg-[#000928] text-white shadow-sm'
            : 'text-gray-600 hover:text-[#000928] dark:text-gray-300 dark:hover:text-white',
        ]"
      >
        <component :is="tab.icon" class="h-4 w-4" />
        {{ tab.label }}
      </button>
    </div>

    <!-- ═══════════════════ TAB 1: DETAILS ═══════════════════ -->
    <div v-show="activeTab === 'details'" class="mx-auto max-w-2xl">
      <form @submit.prevent="saveDetails" class="space-y-5 rounded-2xl border border-gray-100 bg-white p-8 shadow-sm dark:bg-gray-800 dark:border-gray-700">

        <div>
          <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Course Title <span class="text-red-500">*</span></label>
          <input v-model="detailsForm.title" type="text"
            class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/20 dark:bg-gray-900 dark:border-gray-600 dark:text-white"
            :class="{ 'border-red-400': detailsForm.errors.title }" />
          <p v-if="detailsForm.errors.title" class="mt-1 text-xs text-red-500">{{ detailsForm.errors.title }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Category</label>
            <select v-model="detailsForm.category_id"
              class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-[#42b6c5] focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-white">
              <option value="">No category</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Level <span class="text-red-500">*</span></label>
            <select v-model="detailsForm.level"
              class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-[#42b6c5] focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-white">
              <option value="beginner">Beginner</option>
              <option value="intermediate">Intermediate</option>
              <option value="advanced">Advanced</option>
            </select>
          </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-600 dark:bg-gray-900/40">
          <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Pricing &amp; installments</p>
          <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Set your public course price, optional sale price, and the maximum installment count.
          </p>
          <div class="mt-3 grid gap-4 sm:grid-cols-3">
            <div>
              <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Price (XAF)</label>
              <input
                v-model="detailsForm.price"
                type="number"
                min="0"
                step="0.01"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
              />
              <p v-if="detailsForm.errors.price" class="mt-1 text-xs text-red-500">{{ detailsForm.errors.price }}</p>
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Sale price (XAF)</label>
              <input
                v-model="detailsForm.sale_price"
                type="number"
                min="0"
                step="0.01"
                placeholder="Optional"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
              />
              <p v-if="detailsForm.errors.sale_price" class="mt-1 text-xs text-red-500">{{ detailsForm.errors.sale_price }}</p>
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Max installments</label>
              <input
                v-model.number="detailsForm.max_installments"
                type="number"
                min="1"
                max="12"
                :disabled="Number(detailsForm.price || 0) <= 0"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm disabled:opacity-60 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
              />
              <p v-if="detailsForm.errors.max_installments" class="mt-1 text-xs text-red-500">{{ detailsForm.errors.max_installments }}</p>
            </div>
          </div>
          <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
            Free courses automatically use 1 installment.
          </p>
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Duration</label>
          <input v-model="detailsForm.duration" type="text" placeholder="e.g. 8 weeks"
            class="w-full max-w-md rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-[#42b6c5] focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-white" />
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Short Description <span class="text-red-500">*</span></label>
          <textarea v-model="detailsForm.short_description" rows="3" maxlength="500"
            class="w-full resize-none rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/20 dark:bg-gray-900 dark:border-gray-600 dark:text-white"
            :class="{ 'border-red-400': detailsForm.errors.short_description }" />
          <div class="mt-1 flex justify-between">
            <p v-if="detailsForm.errors.short_description" class="text-xs text-red-500">{{ detailsForm.errors.short_description }}</p>
            <p class="ml-auto text-xs text-gray-400">{{ detailsForm.short_description.length }}/500</p>
          </div>
        </div>

        <div class="max-w-4xl">
          <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Full description</label>
          <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">
            Rich text: headings, lists, images, and links — shown on the public course page.
          </p>
          <RichTextEditor
            v-model="detailsForm.description"
            placeholder="Detailed course overview, prerequisites, what students will achieve…"
            upload-url="/lesson-content/media"
            body-class="min-h-[220px] max-h-[min(60vh,560px)]"
          />
        </div>

        <div class="flex justify-end pt-2">
          <button type="submit" :disabled="detailsForm.processing"
            class="inline-flex items-center gap-2 rounded-xl bg-[#381998] px-6 py-2.5 text-sm font-semibold text-white hover:bg-[#000928] disabled:opacity-60 transition-colors">
            {{ detailsForm.processing ? 'Saving…' : 'Save Details' }}
          </button>
        </div>
      </form>
    </div>

    <!-- ═══════════════════ TAB 2: CURRICULUM ═══════════════════ -->
    <div v-show="activeTab === 'curriculum'" class="mx-auto w-full max-w-5xl space-y-6">
      <draggable
        v-model="sectionList"
        item-key="id"
        handle=".section-drag-handle"
        :animation="200"
        class="space-y-5"
        @end="onSectionsDragEnd"
      >
        <template #item="{ element: section }">
        <div
          class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:bg-gray-800 dark:border-gray-700">

          <!-- Section header row -->
          <div class="flex items-center gap-3 px-5 py-3.5 sm:px-6">
            <button
              type="button"
              class="section-drag-handle shrink-0 cursor-grab touch-none rounded p-1 text-gray-400 hover:text-[#381998] active:cursor-grabbing"
              aria-label="Drag to reorder section"
            >
              <GripVertical class="h-4 w-4" />
            </button>

            <!-- Inline edit mode -->
            <template v-if="editingSectionId === section.id">
              <div class="flex min-w-0 flex-1 flex-col gap-3 sm:flex-row sm:items-center sm:gap-3">
                <input v-model="sectionForm.title" type="text" placeholder="Section title"
                  class="w-full min-w-0 rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/15 dark:bg-gray-900 dark:border-gray-600 dark:text-white" />
                <div class="flex shrink-0 justify-end gap-2 sm:justify-start">
                  <button @click="saveSection(section.id)" :disabled="sectionForm.processing"
                    class="rounded-xl bg-[#381998] px-4 py-2 text-sm font-semibold text-white hover:bg-[#000928] disabled:opacity-50">
                    Save
                  </button>
                  <button @click="editingSectionId = null; sectionForm.reset()"
                    class="rounded-xl border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:border-gray-400 dark:border-gray-600 dark:text-gray-300">
                    Cancel
                  </button>
                </div>
              </div>
            </template>

            <!-- Display mode -->
            <template v-else>
              <button @click="toggleSection(section.id)" class="flex flex-1 items-center justify-between text-left min-w-0">
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold text-[#000928] dark:text-white">{{ section.title }}</p>
                  <p class="text-xs text-gray-400">{{ section.lessons.length }} lesson{{ section.lessons.length !== 1 ? 's' : '' }}</p>
                </div>
                <ChevronDown :class="['ml-2 h-4 w-4 shrink-0 text-gray-400 transition-transform', expandedSections.has(section.id) ? 'rotate-180' : '']" />
              </button>
              <div class="flex items-center gap-1 shrink-0 ml-2">
                <button @click="openEditSection(section)"
                  class="rounded-lg p-1.5 text-gray-400 hover:text-[#381998] transition-colors">
                  <Edit2 class="h-4 w-4" />
                </button>
                <button @click="deleteSection(section.id)"
                  class="rounded-lg p-1.5 text-gray-400 hover:text-red-500 transition-colors">
                  <Trash2 class="h-4 w-4" />
                </button>
              </div>
            </template>
          </div>

          <!-- Lessons list (collapsible) -->
          <div v-if="expandedSections.has(section.id)" class="border-t border-gray-100 dark:border-gray-700">

            <draggable
              v-model="section.lessons"
              item-key="id"
              handle=".lesson-drag-handle"
              :animation="200"
              @end="onLessonsDragEnd(section)"
            >
            <template #item="{ element: lesson }">
            <div
              class="border-b border-gray-50 dark:border-gray-700/50 last:border-b-0">

              <!-- Edit form for this lesson (inline) -->
              <div v-if="editingLessonId === lesson.id && editingLessonSection === section.id"
                class="border-t border-gray-100 bg-gray-50 px-5 py-5 sm:px-6 dark:border-gray-700 dark:bg-gray-900/40">
                <p class="mb-4 text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Edit lesson</p>
                <div class="mx-auto w-full max-w-4xl space-y-5">
                  <div class="grid grid-cols-1 gap-4 sm:grid-cols-12 sm:gap-x-5">
                    <div class="sm:col-span-8">
                      <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Title <span class="text-red-500">*</span></label>
                      <input v-model="lessonForm.title" type="text" placeholder="Lesson title"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/15 dark:bg-gray-900 dark:border-gray-600 dark:text-white" />
                      <p v-if="lessonForm.errors.title" class="mt-0.5 text-xs text-red-500">{{ lessonForm.errors.title }}</p>
                    </div>
                    <div class="sm:col-span-4">
                      <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Type <span class="text-red-500">*</span></label>
                      <select v-model="lessonForm.type"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/15 dark:bg-gray-900 dark:border-gray-600 dark:text-white">
                        <option value="video">Video</option>
                        <option value="text">Text / Article</option>
                        <option value="quiz">Quiz</option>
                      </select>
                    </div>
                  </div>
                  <div v-if="lessonForm.type === 'video'" class="grid grid-cols-1 gap-4 sm:grid-cols-12 sm:gap-x-5">
                    <div class="sm:col-span-4">
                      <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Duration</label>
                      <input v-model="lessonForm.duration" type="text" placeholder="mm:ss"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/15 dark:bg-gray-900 dark:border-gray-600 dark:text-white" />
                      <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Save this lesson, then upload the video file from the lesson row.</p>
                    </div>
                  </div>
                  <div v-if="lessonForm.type === 'text'" class="w-full rounded-xl border border-[#42b6c5]/25 bg-[#42b6c5]/[0.04] p-4 sm:p-5 dark:border-[#42b6c5]/35 dark:bg-[#42b6c5]/10">
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800 dark:text-gray-100">Lesson content</label>
                    <p class="mb-3 text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                      Headings, lists, code blocks, images (upload or URL), and links — same formatting learners see in the player.
                    </p>
                    <RichTextEditor
                      :key="`lesson-body-edit-${lesson.id}-${lessonForm.type}`"
                      v-model="lessonForm.content"
                      placeholder="Write the lesson content…"
                      upload-url="/lesson-content/media"
                      body-class="min-h-[280px] max-h-[min(70vh,720px)]"
                    />
                  </div>
                  <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Short description <span class="font-normal text-gray-400">(optional)</span></label>
                    <input v-model="lessonForm.description" type="text" placeholder="Short description (optional)"
                      class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/15 dark:bg-gray-900 dark:border-gray-600 dark:text-white" />
                  </div>
                  <label class="flex cursor-pointer items-center gap-2.5 text-sm text-gray-700 dark:text-gray-200">
                    <input type="checkbox" v-model="lessonForm.is_free" class="rounded border-gray-300 text-[#381998] focus:ring-[#381998]" />
                    Free preview (accessible without enrolment)
                  </label>
                  <div class="flex flex-wrap items-center gap-3 border-t border-gray-200/80 pt-4 dark:border-gray-600/60">
                    <button type="button" @click="saveLesson(section.id, lesson.id)" :disabled="lessonForm.processing"
                      class="rounded-xl bg-[#381998] px-5 py-2 text-sm font-semibold text-white hover:bg-[#000928] disabled:opacity-50">
                      {{ lessonForm.processing ? 'Saving…' : 'Save Lesson' }}
                    </button>
                    <button type="button" @click="editingLessonId = null; editingLessonSection = null; lessonForm.reset()"
                      class="rounded-xl border border-gray-200 px-5 py-2 text-sm text-gray-600 hover:border-gray-400 dark:border-gray-600">
                      Cancel
                    </button>
                  </div>
                </div>
              </div>

              <!-- Lesson display row -->
              <div
                v-else
                :id="`lesson-row-${lesson.id}`"
                :class="[
                  'flex items-center gap-3 px-5 py-2.5 sm:px-6 transition-colors',
                  highlightedLessonId === lesson.id ? 'bg-[#381998]/8 ring-1 ring-[#381998]/25' : '',
                ]"
              >
                <button
                  type="button"
                  class="lesson-drag-handle shrink-0 cursor-grab touch-none rounded p-1 text-gray-400 hover:text-[#381998] active:cursor-grabbing"
                  aria-label="Drag to reorder lesson"
                >
                  <GripVertical class="h-4 w-4" />
                </button>
                <component :is="lessonTypeIcons[lesson.type]" class="h-4 w-4 shrink-0 text-gray-400" />
                <div class="flex-1 min-w-0">
                  <p class="truncate text-sm text-gray-800 dark:text-gray-200">{{ lesson.title }}</p>
                  <div class="flex items-center gap-2 text-xs text-gray-400">
                    <span>{{ lessonTypeLabels[lesson.type] }}</span>
                    <span v-if="lesson.type === 'video' && lesson.duration">· {{ lesson.duration }}</span>
                    <span v-if="lesson.is_free" class="rounded-full bg-green-100 px-1.5 py-0.5 text-green-600 font-medium">Free preview</span>
                  </div>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                  <label
                    v-if="lesson.type === 'video'"
                    class="cursor-pointer rounded-lg px-2 py-1 text-[11px] font-semibold text-[#42b6c5] hover:bg-[#42b6c5]/10"
                  >
                    {{ lesson.video_url ? 'Replace Video' : 'Upload Video' }}
                    <input
                      type="file"
                      class="hidden"
                      accept="video/mp4,video/mov,video/avi,video/mkv,video/webm"
                      @change="onLessonVideoSelected($event, section.id, lesson.id)"
                    />
                  </label>
                  <Link
                    v-if="lesson.type === 'quiz'"
                    :href="`/tutor/courses/${course.id}/lessons/${lesson.id}/quiz`"
                    class="rounded-lg px-2 py-1 text-[11px] font-semibold text-[#381998] hover:bg-[#381998]/10"
                  >
                    Build Quiz
                  </Link>
                  <button @click="openEditLesson(section, lesson)"
                    class="rounded-lg p-1.5 text-gray-400 hover:text-[#381998] transition-colors">
                    <Edit2 class="h-3.5 w-3.5" />
                  </button>
                  <button @click="deleteLesson(section.id, lesson.id)"
                    class="rounded-lg p-1.5 text-gray-400 hover:text-red-500 transition-colors">
                    <Trash2 class="h-3.5 w-3.5" />
                  </button>
                </div>
              </div>
              <div
                v-if="lesson.type === 'video'"
                class="flex items-center justify-between gap-3 border-t border-gray-50 px-5 py-2 text-xs sm:px-6 dark:border-gray-700/50"
              >
                <div class="flex items-center gap-2">
                  <span class="text-gray-400">Video:</span>
                  <span
                    :class="[
                      'rounded-full px-2 py-0.5 font-medium',
                      lesson.youtube_status === 'ready'
                        ? 'bg-green-100 text-green-700'
                        : lesson.youtube_status === 'failed'
                          ? 'bg-red-100 text-red-700'
                          : lesson.youtube_status === 'processing'
                            ? 'bg-amber-100 text-amber-700'
                            : 'bg-gray-100 text-gray-600',
                    ]"
                  >
                    {{ lesson.youtube_status || 'not uploaded' }}
                  </span>
                </div>
                <span v-if="lesson.youtube_error" class="max-w-[45ch] truncate text-red-500" :title="lesson.youtube_error">
                  {{ lesson.youtube_error }}
                </span>
              </div>
              <div
                v-if="lesson.type === 'video' && lessonEmbedUrl(lesson.video_url)"
                class="border-t border-gray-50 px-5 pb-3 pt-2 sm:px-6 dark:border-gray-700/50"
              >
                <div class="aspect-video overflow-hidden rounded-lg bg-black">
                  <iframe
                    :src="lessonEmbedUrl(lesson.video_url) ?? undefined"
                    class="h-full w-full"
                    referrerpolicy="strict-origin-when-cross-origin"
                    :allow="STREAMING_IFRAME_ALLOW"
                    allowfullscreen
                  />
                </div>
              </div>
              <div
                class="border-t border-gray-50 px-5 py-3 sm:px-6 dark:border-gray-700/50"
              >
                <p class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                  <Paperclip class="h-3.5 w-3.5 shrink-0" />
                  Lesson resources
                </p>
                <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
                  Supplementary downloads (PDFs, slides, worksheets) — shown to learners in the Resources panel.
                </p>
                <ul v-if="(lesson.attachments ?? []).length" class="mb-3 space-y-2">
                  <li
                    v-for="att in lesson.attachments"
                    :key="att.id"
                    class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-gray-100 bg-gray-50/80 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900/40"
                  >
                    <a
                      :href="resourcePublicUrl(att.file_url)"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="min-w-0 flex-1 truncate font-medium text-[#381998] hover:underline"
                    >
                      {{ att.name }}
                    </a>
                    <span class="shrink-0 text-xs text-gray-400">{{ att.formatted_file_size }}</span>
                    <button
                      type="button"
                      class="shrink-0 rounded p-1 text-gray-400 hover:text-red-500"
                      aria-label="Remove file"
                      @click="deleteLessonAttachment(section, lesson, att.id)"
                    >
                      <Trash2 class="h-3.5 w-3.5" />
                    </button>
                  </li>
                </ul>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                  <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-dashed border-gray-200 px-3 py-2 text-xs font-medium text-gray-600 hover:border-[#42b6c5] dark:border-gray-600 dark:text-gray-300">
                    <Upload class="h-3.5 w-3.5 shrink-0" />
                    <span>Add file</span>
                    <input
                      type="file"
                      class="hidden"
                      accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.txt,image/*"
                      @change="uploadLessonResource(section, lesson, $event)"
                    />
                  </label>
                  <span class="text-xs text-gray-400">Max 20&nbsp;MB per file.</span>
                </div>
              </div>
            </div>
            </template>
            </draggable>

            <!-- Add lesson inline form -->
            <div v-if="addLessonSectionId === section.id"
              class="border-t border-gray-100 bg-gray-50 px-5 py-5 sm:px-6 dark:border-gray-700 dark:bg-gray-900/40">
              <p class="mb-4 text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">New lesson</p>
              <p
                v-if="lessonForm.type === 'text'"
                class="mb-5 rounded-xl border border-dashed border-[#42b6c5]/35 bg-[#42b6c5]/[0.06] px-4 py-3 text-xs leading-relaxed text-gray-600 dark:border-[#42b6c5]/30 dark:bg-[#42b6c5]/10 dark:text-gray-300"
              >
                For formatted articles: set <strong class="font-semibold">Type</strong> to <strong class="font-semibold">Text / Article</strong>
                — the rich editor appears below.
              </p>
              <div class="mx-auto w-full max-w-4xl space-y-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-12 sm:gap-x-5">
                  <div class="sm:col-span-8">
                    <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Title <span class="text-red-500">*</span></label>
                    <input v-model="lessonForm.title" type="text" placeholder="Lesson title"
                      class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/15 dark:bg-gray-900 dark:border-gray-600 dark:text-white" />
                    <p v-if="lessonForm.errors.title" class="mt-0.5 text-xs text-red-500">{{ lessonForm.errors.title }}</p>
                  </div>
                  <div class="sm:col-span-4">
                    <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Type <span class="text-red-500">*</span></label>
                    <select v-model="lessonForm.type"
                      class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/15 dark:bg-gray-900 dark:border-gray-600 dark:text-white">
                      <option value="video">Video</option>
                      <option value="text">Text / Article</option>
                      <option value="quiz">Quiz</option>
                    </select>
                  </div>
                </div>
                <div v-if="lessonForm.type === 'video'" class="space-y-4">
                  <div class="grid grid-cols-1 gap-4 sm:grid-cols-12 sm:gap-x-5">
                    <div class="sm:col-span-4">
                      <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Duration</label>
                      <input v-model="lessonForm.duration" type="text" placeholder="mm:ss"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/15 dark:bg-gray-900 dark:border-gray-600 dark:text-white" />
                      <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Shown next to the lesson title (optional).</p>
                    </div>
                  </div>
                  <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-600 dark:bg-gray-900/50">
                    <div class="mb-2 flex items-center gap-2">
                      <Upload class="h-4 w-4 shrink-0 text-[#42b6c5]" />
                      <label class="text-sm font-semibold text-gray-800 dark:text-gray-100">Video file</label>
                    </div>
                    <p class="mb-3 text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                      Choose a file now and it will upload to YouTube right after you click <strong class="font-medium text-gray-700 dark:text-gray-300">Add lesson</strong>.
                      You can also skip this and upload later from the lesson row.
                    </p>
                    <input
                      ref="newLessonVideoInputRef"
                      type="file"
                      accept="video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm"
                      class="block w-full cursor-pointer text-sm text-gray-600 file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-[#42b6c5]/15 file:px-3 file:py-2 file:text-sm file:font-medium file:text-[#381998] hover:file:bg-[#42b6c5]/25 dark:text-gray-300 dark:file:bg-[#42b6c5]/20"
                      @change="onPendingNewLessonVideoSelected"
                    />
                    <p v-if="pendingNewLessonVideoFile" class="mt-2 text-xs font-medium text-gray-700 dark:text-gray-200">
                      Selected: {{ pendingNewLessonVideoFile.name }}
                    </p>
                    <p v-if="videoUploadForm.errors.video_file" class="mt-2 text-xs text-red-500">{{ videoUploadForm.errors.video_file }}</p>
                  </div>
                </div>
                <div v-if="lessonForm.type === 'text'" class="w-full rounded-xl border border-[#42b6c5]/25 bg-[#42b6c5]/[0.04] p-4 sm:p-5 dark:border-[#42b6c5]/35 dark:bg-[#42b6c5]/10">
                  <label class="mb-1.5 block text-sm font-semibold text-gray-800 dark:text-gray-100">Lesson content</label>
                  <p class="mb-3 text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                    Headings, lists, code blocks, images, and links — same as the edit form above.
                  </p>
                  <RichTextEditor
                    :key="`lesson-body-add-${section.id}-${lessonForm.type}`"
                    v-model="lessonForm.content"
                    placeholder="Write the lesson content…"
                    upload-url="/lesson-content/media"
                    body-class="min-h-[280px] max-h-[min(70vh,720px)]"
                  />
                </div>
                <div>
                  <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Short description <span class="font-normal text-gray-400">(optional)</span></label>
                  <input v-model="lessonForm.description" type="text" placeholder="Short description (optional)"
                    class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/15 dark:bg-gray-900 dark:border-gray-600 dark:text-white" />
                </div>
                <label class="flex cursor-pointer items-center gap-2.5 text-sm text-gray-700 dark:text-gray-200">
                  <input type="checkbox" v-model="lessonForm.is_free" class="rounded border-gray-300 text-[#381998] focus:ring-[#381998]" />
                  Free preview (accessible without enrolment)
                </label>
                <div class="flex flex-wrap items-center gap-3 border-t border-gray-200/80 pt-4 dark:border-gray-600/60">
                  <button
                    type="button"
                    @click="addLesson(section.id)"
                    :disabled="lessonForm.processing || videoUploadForm.processing"
                    class="rounded-xl bg-[#381998] px-5 py-2 text-sm font-semibold text-white hover:bg-[#000928] disabled:opacity-50"
                  >
                    {{
                      lessonForm.processing
                        ? 'Adding…'
                        : videoUploadForm.processing
                          ? 'Uploading video…'
                          : 'Add lesson'
                    }}
                  </button>
                  <button
                    type="button"
                    :disabled="lessonForm.processing || videoUploadForm.processing"
                    @click="addLessonSectionId = null; lessonForm.reset(); clearPendingNewLessonVideo()"
                    class="rounded-xl border border-gray-200 px-5 py-2 text-sm text-gray-600 hover:border-gray-400 disabled:opacity-50 dark:border-gray-600 dark:text-gray-300"
                  >
                    Cancel
                  </button>
                </div>
              </div>
            </div>

            <!-- + Add Lesson trigger -->
            <div v-else class="border-t border-gray-50 px-5 py-3.5 sm:px-6 dark:border-gray-700/50">
              <button @click="openAddLesson(section.id)"
                class="flex items-center gap-2 text-sm font-medium text-[#381998] hover:text-[#000928] transition-colors">
                <PlusCircle class="h-4 w-4 shrink-0" /> Add lesson
              </button>
            </div>
          </div>
        </div>
        </template>
      </draggable>

      <!-- Add section inline form -->
      <div v-if="addSectionOpen"
        class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-7">
        <p class="mb-1 text-base font-bold text-[#000928] dark:text-white">New section</p>
        <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">Sections group your lessons. You can reorder them anytime.</p>
        <form @submit.prevent="addSection" class="mx-auto max-w-4xl space-y-5">
          <div>
            <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Title <span class="text-red-500">*</span></label>
            <input v-model="sectionForm.title" type="text" placeholder="e.g. Introduction to the course"
              class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/15 dark:bg-gray-900 dark:border-gray-600 dark:text-white"
              :class="{ 'border-red-400': sectionForm.errors.title }" />
            <p v-if="sectionForm.errors.title" class="mt-1 text-xs text-red-500">{{ sectionForm.errors.title }}</p>
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Description <span class="font-normal text-gray-400">(optional)</span></label>
            <input v-model="sectionForm.description" type="text" placeholder="Optional — shown to you when organizing"
              class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/15 dark:bg-gray-900 dark:border-gray-600 dark:text-white" />
          </div>
          <div class="flex flex-wrap items-center gap-3 border-t border-gray-100 pt-5 dark:border-gray-700">
            <button type="submit" :disabled="sectionForm.processing"
              class="rounded-xl bg-[#381998] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#000928] disabled:opacity-50">
              {{ sectionForm.processing ? 'Adding…' : 'Add section' }}
            </button>
            <button type="button" @click="addSectionOpen = false; sectionForm.reset()"
              class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm text-gray-600 hover:border-gray-400 dark:border-gray-600 dark:text-gray-300">
              Cancel
            </button>
          </div>
        </form>
      </div>

      <!-- + Add Section trigger -->
      <button v-else @click="addSectionOpen = true"
        class="flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-gray-200 py-4 text-sm font-medium text-gray-500 transition-colors hover:border-[#381998] hover:text-[#381998] dark:border-gray-700 dark:hover:border-[#381998]">
        <PlusCircle class="h-4 w-4 shrink-0" /> Add section
      </button>
    </div>

    <!-- ═══════════════════ TAB 3: PUBLISH ═══════════════════ -->
    <div v-show="activeTab === 'publish'" class="mx-auto max-w-2xl space-y-6">

      <!-- Cover image -->
      <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <div class="mb-4 flex items-center gap-3">
          <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#42b6c5]/10">
            <ImageIcon class="h-5 w-5 text-[#42b6c5]" />
          </div>
          <h2 class="font-bold text-[#000928] dark:text-white">Cover Image</h2>
        </div>

        <div class="mb-4 h-48 overflow-hidden rounded-xl border border-gray-100 bg-gradient-to-br from-[#381998] to-[#42b6c5]">
          <img v-if="coverPreview" :src="coverPreview" alt="Cover" class="h-full w-full object-cover" />
          <div v-else class="flex h-full items-center justify-center">
            <BookOpen class="h-14 w-14 text-white/30" />
          </div>
        </div>

        <form @submit.prevent="uploadCover" class="flex items-center gap-3">
          <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-600 transition-colors hover:border-[#42b6c5] hover:text-[#42b6c5] dark:border-gray-600 dark:text-gray-300">
            <ImageIcon class="h-4 w-4 shrink-0" />
            <span class="truncate">{{ coverForm.cover_image ? coverForm.cover_image.name : 'Choose image (JPG, PNG, WebP — max 2 MB)' }}</span>
            <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="onCoverSelected" />
          </label>
          <button v-if="coverForm.cover_image" type="submit" :disabled="coverForm.processing"
            class="shrink-0 rounded-xl bg-[#42b6c5] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#35919e] disabled:opacity-50">
            {{ coverForm.processing ? 'Uploading…' : 'Upload' }}
          </button>
        </form>
        <p v-if="coverForm.errors.cover_image" class="mt-2 text-xs text-red-500">{{ coverForm.errors.cover_image }}</p>
      </div>

      <!-- Submit for review -->
      <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <div class="mb-4 flex items-center gap-3">
          <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#381998]/10">
            <Send class="h-5 w-5 text-[#381998]" />
          </div>
          <h2 class="font-bold text-[#000928] dark:text-white">Submit for Review</h2>
        </div>

        <!-- Status description -->
        <div class="mb-5 flex items-center gap-3 rounded-xl bg-gray-50 p-4 dark:bg-gray-900/40">
          <div class="flex-1">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Current Status</p>
            <p class="mt-0.5 text-xs text-gray-400">
              <template v-if="course.status === 'draft'">Your course is a draft. Complete the checklist below and submit for review.</template>
              <template v-else-if="course.status === 'pending_review'">Awaiting admin review. You'll be notified once it's approved.</template>
              <template v-else-if="course.status === 'published'">Your course is live and visible to students.</template>
              <template v-else-if="course.status === 'archived'">Archived. You can re-submit for review.</template>
            </p>
          </div>
          <span :class="['shrink-0 rounded-full px-3 py-1 text-xs font-bold', statusConfig[course.status]?.class]">
            {{ statusConfig[course.status]?.label }}
          </span>
        </div>

        <!-- Readiness checklist -->
        <ul class="mb-6 space-y-2">
          <li v-for="item in [
            { label: 'Title and short description filled', done: !!course.title && !!course.short_description },
            { label: 'Cover image uploaded',               done: !!course.cover_image },
            { label: 'At least one section added',         done: course.sections.length > 0 },
            { label: 'At least one lesson added',          done: course.sections.some(s => s.lessons.length > 0) },
          ]" :key="item.label" class="flex items-center gap-2 text-sm">
            <div :class="['flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-xs font-bold', item.done ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400']">
              {{ item.done ? '✓' : '○' }}
            </div>
            <span :class="item.done ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400'">{{ item.label }}</span>
          </li>
        </ul>

        <button v-if="canSubmit" @click="showSubmitModal = true"
          class="inline-flex items-center gap-2 rounded-xl bg-[#381998] px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#000928]">
          <Send class="h-4 w-4" /> Submit for Review
        </button>
        <p v-else-if="course.status === 'pending_review'" class="text-sm font-medium text-yellow-600">
          ⏳ Awaiting review — no action needed.
        </p>
        <p v-else-if="course.status === 'published'" class="text-sm font-medium text-green-600">
          ✓ Your course is live.
        </p>
      </div>

      <!-- Enroll a learner (published courses only; backend enforces ownership) -->
      <div
        v-if="can_manually_enroll"
        class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:bg-gray-800 dark:border-gray-700"
      >
        <div class="mb-4 flex items-center gap-3">
          <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#42b6c5]/10">
            <UserPlus class="h-5 w-5 text-[#42b6c5]" />
          </div>
          <h2 class="font-bold text-[#000928] dark:text-white">Enroll a learner</h2>
        </div>
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
          Grant access to your published course by email. The learner must already have a registered account with that email.
        </p>
        <form class="space-y-3" @submit.prevent="submitEnrollStudent">
          <div>
            <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Learner email</label>
            <input
              v-model="enrollForm.email"
              type="email"
              autocomplete="email"
              placeholder="student@example.com"
              class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/20 dark:bg-gray-900 dark:border-gray-600 dark:text-white"
            />
            <p v-if="enrollForm.errors.email" class="mt-1 text-xs text-red-500">{{ enrollForm.errors.email }}</p>
          </div>
          <button
            type="submit"
            :disabled="enrollForm.processing"
            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#42b6c5] px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#35919e] disabled:opacity-50 sm:w-auto"
          >
            {{ enrollForm.processing ? 'Enrolling…' : 'Grant access' }}
          </button>
        </form>
      </div>
    </div>

  </AppLayout>

  <!-- Submit for review confirmation -->
  <ConfirmationModal
    :open="showSubmitModal"
    title="Submit for Review"
    description="Once submitted, an admin will review your course before it goes live. You won't be able to edit it until the review is complete. Are you sure you're ready?"
    confirm-text="Yes, Submit"
    cancel-text="Not Yet"
    variant="default"
    @update:open="showSubmitModal = $event"
    @confirm="submitForReview"
  />
</template>
