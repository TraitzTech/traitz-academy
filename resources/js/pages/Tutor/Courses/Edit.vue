<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
  ArrowLeft, BookOpen, ChevronDown, ChevronUp, Edit2, FileText,
  GripVertical, ImageIcon, Layers, PlusCircle, Send, Trash2, Video,
} from 'lucide-vue-next';
import { ref } from 'vue';

import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';

// ─── Types ────────────────────────────────────────────────────────────────────

interface Category { id: number; name: string; slug: string }

interface Lesson {
  id: number;
  title: string;
  type: 'video' | 'text' | 'quiz';
  description: string | null;
  content: string | null;
  duration: string | null;
  is_free: boolean;
  sort_order: number;
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
  duration: string | null;
  category: Category | null;
  sections: Section[];
}

const props = defineProps<{
  course: Course;
  categories: Category[];
}>();

// ─── Tab ──────────────────────────────────────────────────────────────────────

const activeTab = ref<'details' | 'curriculum' | 'publish'>('details');

// ─── Details form ─────────────────────────────────────────────────────────────

const detailsForm = useForm({
  title:             props.course.title,
  category_id:       props.course.category?.id ?? '',
  level:             props.course.level,
  short_description: props.course.short_description,
  description:       props.course.description ?? '',
  price:             props.course.price,
  sale_price:        props.course.sale_price ?? '',
  duration:          props.course.duration ?? '',
});

function saveDetails() {
  detailsForm.put(`/tutor/courses/${props.course.id}`, { preserveScroll: true });
}

// ─── Cover image ──────────────────────────────────────────────────────────────

const coverForm    = useForm({ cover_image: null as File | null });
const coverPreview = ref<string | null>(
  props.course.cover_image
    ? (props.course.cover_image.startsWith('http') ? props.course.cover_image : `/storage/${props.course.cover_image}`)
    : null,
);

function onCoverSelected(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0];
  if (!file) return;
  coverForm.cover_image = file;
  coverPreview.value = URL.createObjectURL(file);
}

function uploadCover() {
  coverForm.post(`/tutor/courses/${props.course.id}/cover`, {
    preserveScroll: true,
    forceFormData: true,
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
  expandedSections.value.has(id) ? expandedSections.value.delete(id) : expandedSections.value.add(id);
}

function moveSectionUp(index: number) {
  if (index === 0) return;
  const order = props.course.sections.map((s) => s.id);
  [order[index - 1], order[index]] = [order[index], order[index - 1]];
  router.post(`/tutor/courses/${props.course.id}/sections/reorder`, { order }, { preserveScroll: true });
}

function moveSectionDown(index: number) {
  if (index === props.course.sections.length - 1) return;
  const order = props.course.sections.map((s) => s.id);
  [order[index], order[index + 1]] = [order[index + 1], order[index]];
  router.post(`/tutor/courses/${props.course.id}/sections/reorder`, { order }, { preserveScroll: true });
}

// ─── Lesson management ────────────────────────────────────────────────────────

const addLessonSectionId   = ref<number | null>(null);
const editingLessonId      = ref<number | null>(null);
const editingLessonSection = ref<number | null>(null);

const lessonForm = useForm({
  title: '', type: 'video' as 'video' | 'text' | 'quiz',
  description: '', content: '', duration: '', is_free: false,
});

const lessonTypeIcons:  Record<string, any>    = { video: Video, text: FileText, quiz: BookOpen };
const lessonTypeLabels: Record<string, string> = { video: 'Video', text: 'Text', quiz: 'Quiz' };

function openAddLesson(sectionId: number) {
  addLessonSectionId.value   = sectionId;
  editingLessonId.value      = null;
  editingLessonSection.value = null;
  lessonForm.reset();
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
  lessonForm.post(`/tutor/courses/${props.course.id}/sections/${sectionId}/lessons`, {
    preserveScroll: true,
    onSuccess: () => { lessonForm.reset(); addLessonSectionId.value = null; },
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

function moveLessonUp(section: Section, index: number) {
  if (index === 0) return;
  const order = section.lessons.map((l) => l.id);
  [order[index - 1], order[index]] = [order[index], order[index - 1]];
  router.post(`/tutor/courses/${props.course.id}/sections/${section.id}/lessons/reorder`, { order }, { preserveScroll: true });
}

function moveLessonDown(section: Section, index: number) {
  if (index === section.lessons.length - 1) return;
  const order = section.lessons.map((l) => l.id);
  [order[index], order[index + 1]] = [order[index + 1], order[index]];
  router.post(`/tutor/courses/${props.course.id}/sections/${section.id}/lessons/reorder`, { order }, { preserveScroll: true });
}

// ─── Status config ────────────────────────────────────────────────────────────

const statusConfig: Record<string, { label: string; class: string }> = {
  draft:          { label: 'Draft',          class: 'bg-gray-100 text-gray-600' },
  pending_review: { label: 'Pending Review', class: 'bg-yellow-100 text-yellow-700' },
  published:      { label: 'Published',      class: 'bg-green-100 text-green-700' },
  archived:       { label: 'Archived',       class: 'bg-orange-100 text-orange-700' },
};

const canSubmit = ['draft', 'archived'].includes(props.course.status);
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

        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Price (XAF) <span class="text-red-500">*</span></label>
            <input v-model="detailsForm.price" type="number" min="0" step="100"
              class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-[#42b6c5] focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-white"
              :class="{ 'border-red-400': detailsForm.errors.price }" />
            <p v-if="detailsForm.errors.price" class="mt-1 text-xs text-red-500">{{ detailsForm.errors.price }}</p>
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Sale Price (XAF)</label>
            <input v-model="detailsForm.sale_price" type="number" min="0" step="100" placeholder="Optional"
              class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-[#42b6c5] focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-white"
              :class="{ 'border-red-400': detailsForm.errors.sale_price }" />
            <p v-if="detailsForm.errors.sale_price" class="mt-1 text-xs text-red-500">{{ detailsForm.errors.sale_price }}</p>
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Duration</label>
            <input v-model="detailsForm.duration" type="text" placeholder="e.g. 8 weeks"
              class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-[#42b6c5] focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-white" />
          </div>
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

        <div>
          <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Full Description</label>
          <textarea v-model="detailsForm.description" rows="8"
            placeholder="Detailed course overview, prerequisites, what students will achieve…"
            class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/20 dark:bg-gray-900 dark:border-gray-600 dark:text-white" />
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
    <div v-show="activeTab === 'curriculum'" class="max-w-3xl">
      <div class="space-y-3">
        <div v-for="(section, sIndex) in course.sections" :key="section.id"
          class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:bg-gray-800 dark:border-gray-700">

          <!-- Section header row -->
          <div class="flex items-center gap-3 px-4 py-3">
            <!-- Up/down arrows -->
            <div class="flex flex-col gap-0.5 shrink-0">
              <button @click="moveSectionUp(sIndex)" :disabled="sIndex === 0"
                class="rounded p-0.5 text-gray-400 hover:text-[#381998] disabled:opacity-20 transition-colors">
                <ChevronUp class="h-4 w-4" />
              </button>
              <button @click="moveSectionDown(sIndex)" :disabled="sIndex === course.sections.length - 1"
                class="rounded p-0.5 text-gray-400 hover:text-[#381998] disabled:opacity-20 transition-colors">
                <ChevronDown class="h-4 w-4" />
              </button>
            </div>
            <GripVertical class="h-4 w-4 shrink-0 text-gray-300" />

            <!-- Inline edit mode -->
            <template v-if="editingSectionId === section.id">
              <div class="flex flex-1 items-center gap-2">
                <input v-model="sectionForm.title" type="text" placeholder="Section title"
                  class="flex-1 rounded-lg border border-gray-200 px-3 py-1.5 text-sm focus:border-[#42b6c5] focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-white" />
                <button @click="saveSection(section.id)" :disabled="sectionForm.processing"
                  class="rounded-lg bg-[#381998] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#000928] disabled:opacity-50">
                  Save
                </button>
                <button @click="editingSectionId = null; sectionForm.reset()"
                  class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-500 hover:border-gray-400">
                  Cancel
                </button>
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

            <!-- Each lesson row -->
            <div v-for="(lesson, lIndex) in section.lessons" :key="lesson.id"
              class="border-b border-gray-50 dark:border-gray-700/50 last:border-b-0">

              <!-- Edit form for this lesson (inline) -->
              <div v-if="editingLessonId === lesson.id && editingLessonSection === section.id"
                class="p-4 bg-gray-50 dark:bg-gray-900/40">
                <p class="mb-3 text-xs font-bold uppercase tracking-wide text-gray-500">Edit Lesson</p>
                <div class="space-y-3">
                  <div class="grid grid-cols-2 gap-3">
                    <div>
                      <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Title *</label>
                      <input v-model="lessonForm.title" type="text" placeholder="Lesson title"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-[#42b6c5] focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-white" />
                      <p v-if="lessonForm.errors.title" class="mt-0.5 text-xs text-red-500">{{ lessonForm.errors.title }}</p>
                    </div>
                    <div>
                      <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Type *</label>
                      <select v-model="lessonForm.type"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-[#42b6c5] focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-white">
                        <option value="video">Video</option>
                        <option value="text">Text / Article</option>
                        <option value="quiz">Quiz</option>
                      </select>
                    </div>
                  </div>
                  <div v-if="lessonForm.type === 'video'">
                    <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Duration (e.g. 5:30)</label>
                    <input v-model="lessonForm.duration" type="text" placeholder="mm:ss"
                      class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-[#42b6c5] focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-white" />
                    <p class="mt-0.5 text-xs text-gray-400">Video upload will be available soon.</p>
                  </div>
                  <div v-if="lessonForm.type === 'text'">
                    <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Content</label>
                    <textarea v-model="lessonForm.content" rows="5" placeholder="Lesson content…"
                      class="w-full resize-y rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-[#42b6c5] focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-white" />
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Description</label>
                    <input v-model="lessonForm.description" type="text" placeholder="Short description (optional)"
                      class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-[#42b6c5] focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-white" />
                  </div>
                  <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                    <input type="checkbox" v-model="lessonForm.is_free" class="rounded border-gray-300" />
                    Free preview (accessible without enrolment)
                  </label>
                  <div class="flex items-center gap-2 pt-1">
                    <button type="button" @click="saveLesson(section.id, lesson.id)" :disabled="lessonForm.processing"
                      class="rounded-xl bg-[#381998] px-4 py-2 text-xs font-semibold text-white hover:bg-[#000928] disabled:opacity-50">
                      {{ lessonForm.processing ? 'Saving…' : 'Save Lesson' }}
                    </button>
                    <button type="button" @click="editingLessonId = null; editingLessonSection = null; lessonForm.reset()"
                      class="rounded-xl border border-gray-200 px-4 py-2 text-xs text-gray-600 hover:border-gray-400">
                      Cancel
                    </button>
                  </div>
                </div>
              </div>

              <!-- Lesson display row -->
              <div v-else class="flex items-center gap-3 px-6 py-2.5">
                <div class="flex flex-col gap-0.5 shrink-0">
                  <button @click="moveLessonUp(section, lIndex)" :disabled="lIndex === 0"
                    class="rounded p-0.5 text-gray-300 hover:text-[#381998] disabled:opacity-20 transition-colors">
                    <ChevronUp class="h-3.5 w-3.5" />
                  </button>
                  <button @click="moveLessonDown(section, lIndex)" :disabled="lIndex === section.lessons.length - 1"
                    class="rounded p-0.5 text-gray-300 hover:text-[#381998] disabled:opacity-20 transition-colors">
                    <ChevronDown class="h-3.5 w-3.5" />
                  </button>
                </div>
                <component :is="lessonTypeIcons[lesson.type]" class="h-4 w-4 shrink-0 text-gray-400" />
                <div class="flex-1 min-w-0">
                  <p class="truncate text-sm text-gray-800 dark:text-gray-200">{{ lesson.title }}</p>
                  <div class="flex items-center gap-2 text-xs text-gray-400">
                    <span>{{ lessonTypeLabels[lesson.type] }}</span>
                    <span v-if="lesson.duration">· {{ lesson.duration }}</span>
                    <span v-if="lesson.is_free" class="rounded-full bg-green-100 px-1.5 py-0.5 text-green-600 font-medium">Free preview</span>
                  </div>
                </div>
                <div class="flex items-center gap-1 shrink-0">
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
            </div>

            <!-- Add lesson inline form -->
            <div v-if="addLessonSectionId === section.id" class="p-4 bg-gray-50 dark:bg-gray-900/40">
              <p class="mb-3 text-xs font-bold uppercase tracking-wide text-gray-500">New Lesson</p>
              <div class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Title *</label>
                    <input v-model="lessonForm.title" type="text" placeholder="Lesson title"
                      class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-[#42b6c5] focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-white" />
                    <p v-if="lessonForm.errors.title" class="mt-0.5 text-xs text-red-500">{{ lessonForm.errors.title }}</p>
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Type *</label>
                    <select v-model="lessonForm.type"
                      class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-[#42b6c5] focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-white">
                      <option value="video">Video</option>
                      <option value="text">Text / Article</option>
                      <option value="quiz">Quiz</option>
                    </select>
                  </div>
                </div>
                <div v-if="lessonForm.type === 'video'">
                  <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Duration (e.g. 5:30)</label>
                  <input v-model="lessonForm.duration" type="text" placeholder="mm:ss"
                    class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-[#42b6c5] focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-white" />
                  <p class="mt-0.5 text-xs text-gray-400">Video upload will be available soon.</p>
                </div>
                <div v-if="lessonForm.type === 'text'">
                  <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Content</label>
                  <textarea v-model="lessonForm.content" rows="5" placeholder="Lesson content…"
                    class="w-full resize-y rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-[#42b6c5] focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-white" />
                </div>
                <div>
                  <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Description</label>
                  <input v-model="lessonForm.description" type="text" placeholder="Short description (optional)"
                    class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-[#42b6c5] focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-white" />
                </div>
                <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                  <input type="checkbox" v-model="lessonForm.is_free" class="rounded border-gray-300" />
                  Free preview (accessible without enrolment)
                </label>
                <div class="flex items-center gap-2 pt-1">
                  <button type="button" @click="addLesson(section.id)" :disabled="lessonForm.processing"
                    class="rounded-xl bg-[#381998] px-4 py-2 text-xs font-semibold text-white hover:bg-[#000928] disabled:opacity-50">
                    {{ lessonForm.processing ? 'Adding…' : 'Add Lesson' }}
                  </button>
                  <button type="button" @click="addLessonSectionId = null; lessonForm.reset()"
                    class="rounded-xl border border-gray-200 px-4 py-2 text-xs text-gray-600 hover:border-gray-400">
                    Cancel
                  </button>
                </div>
              </div>
            </div>

            <!-- + Add Lesson trigger -->
            <div v-else class="px-6 py-3">
              <button @click="openAddLesson(section.id)"
                class="flex items-center gap-1.5 text-xs font-medium text-[#381998] hover:text-[#000928] transition-colors">
                <PlusCircle class="h-3.5 w-3.5" /> Add Lesson
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Add section inline form -->
      <div v-if="addSectionOpen"
        class="mt-4 rounded-2xl border border-[#381998]/30 bg-white p-5 shadow-sm dark:bg-gray-800">
        <p class="mb-4 text-sm font-bold text-[#000928] dark:text-white">New Section</p>
        <form @submit.prevent="addSection" class="space-y-3">
          <div>
            <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Title *</label>
            <input v-model="sectionForm.title" type="text" placeholder="e.g. Introduction to the Course"
              class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-[#42b6c5] focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-white"
              :class="{ 'border-red-400': sectionForm.errors.title }" />
            <p v-if="sectionForm.errors.title" class="mt-1 text-xs text-red-500">{{ sectionForm.errors.title }}</p>
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Description</label>
            <input v-model="sectionForm.description" type="text" placeholder="Optional section description"
              class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-[#42b6c5] focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-white" />
          </div>
          <div class="flex items-center gap-2 pt-1">
            <button type="submit" :disabled="sectionForm.processing"
              class="rounded-xl bg-[#381998] px-4 py-2 text-sm font-semibold text-white hover:bg-[#000928] disabled:opacity-50">
              {{ sectionForm.processing ? 'Adding…' : 'Add Section' }}
            </button>
            <button type="button" @click="addSectionOpen = false; sectionForm.reset()"
              class="rounded-xl border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:border-gray-400">
              Cancel
            </button>
          </div>
        </form>
      </div>

      <!-- + Add Section trigger -->
      <button v-else @click="addSectionOpen = true"
        class="mt-4 flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-gray-200 py-4 text-sm font-medium text-gray-500 transition-colors hover:border-[#381998] hover:text-[#381998] dark:border-gray-700 dark:hover:border-[#381998]">
        <PlusCircle class="h-4 w-4" /> Add Section
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
