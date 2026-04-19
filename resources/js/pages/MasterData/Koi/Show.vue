<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { ChevronLeft, ChevronRight, ImageOff } from 'lucide-vue-next'
import { computed, ref } from 'vue'

type Breeder = {
    id: number
    name_breeder: string
}

type KoiImage = {
    id: number
    image_path: string
    sort_order: number
}

type Koi = {
    id: number
    breeder_id: number
    code: string
    name: string
    photo?: string
    images?: KoiImage[]
    breeder?: Breeder
    created_at: string
}

const props = defineProps<{ koi: Koi }>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data', href: '#' },
    { title: 'Data Koi', href: '/master-data/koi' },
    { title: props.koi.name, href: `/master-data/koi/${props.koi.id}` },
]

// ─── Carousel ─────────────────────────────────────────────
const images = computed<KoiImage[]>(() => props.koi.images ?? [])
const current = ref(0)

function prev() {
    current.value = (current.value - 1 + images.value.length) % images.value.length
}
function next() {
    current.value = (current.value + 1) % images.value.length
}

// ─── Lightbox ─────────────────────────────────────────────
const lightboxOpen = ref(false)
const lightboxIndex = ref(0)

function openLightbox(index: number) {
    lightboxIndex.value = index
    lightboxOpen.value = true
}
function lightboxPrev() {
    lightboxIndex.value = (lightboxIndex.value - 1 + images.value.length) % images.value.length
}
function lightboxNext() {
    lightboxIndex.value = (lightboxIndex.value + 1) % images.value.length
}

function imgSrc(img: KoiImage) {
    return `/storage/${img.image_path}`
}

function fmtDate(d: string) {
    return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Koi: ${koi.name}`" />

        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">{{ koi.name }}</h1>
                    <p class="text-sm text-muted-foreground">Koi Code: {{ koi.code }}</p>
                </div>
                <Link
                    href="/master-data/koi"
                    class="inline-flex items-center gap-1 self-start rounded-md border border-border bg-background px-3 py-2 text-sm font-medium hover:bg-muted"
                >
                    <ChevronLeft class="h-4 w-4" />
                    Back
                </Link>
            </div>

            <!-- Content -->
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Left: Carousel -->
                <div class="rounded-lg border border-sidebar-border/70 bg-background p-4 lg:col-span-1">
                    <h2 class="mb-3 text-base font-semibold">Foto Koi</h2>

                    <!-- Empty state -->
                    <div
                        v-if="images.length === 0"
                        class="flex flex-col items-center justify-center gap-2 rounded-lg bg-muted/40 py-16 text-muted-foreground"
                    >
                        <ImageOff class="h-10 w-10 opacity-40" />
                        <p class="text-sm">Belum ada foto untuk koi ini.</p>
                        <p class="text-xs opacity-70">Upload foto melalui menu Edit Data Koi</p>
                    </div>

                    <!-- Carousel -->
                    <div v-else>
                        <!-- Main image -->
                        <div class="relative overflow-hidden rounded-lg bg-muted">
                            <div class="h-64 w-full">
                                <button type="button" @click="openLightbox(current)" class="block h-full w-full">
                                    <img
                                        :src="imgSrc(images[current])"
                                        :alt="`Foto koi ${koi.name} ${current + 1}`"
                                        class="h-full w-full object-cover transition-opacity duration-200"
                                    />
                                </button>
                            </div>

                            <!-- Arrows -->
                            <template v-if="images.length > 1">
                                <button
                                    @click="prev"
                                    class="absolute left-2 top-1/2 -translate-y-1/2 flex h-9 w-9 items-center justify-center rounded-full bg-black/50 text-white shadow hover:bg-black/70 transition-colors"
                                    aria-label="Foto sebelumnya"
                                >
                                    <ChevronLeft class="h-5 w-5" />
                                </button>
                                <button
                                    @click="next"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 flex h-9 w-9 items-center justify-center rounded-full bg-black/50 text-white shadow hover:bg-black/70 transition-colors"
                                    aria-label="Foto berikutnya"
                                >
                                    <ChevronRight class="h-5 w-5" />
                                </button>

                                <!-- Counter badge -->
                                <div class="absolute bottom-2 right-2 rounded-full bg-black/60 px-2 py-0.5 text-xs font-medium text-white">
                                    {{ current + 1 }} / {{ images.length }}
                                </div>
                            </template>
                        </div>

                        <!-- Dot indicators -->
                        <div v-if="images.length > 1" class="mt-3 flex items-center justify-center gap-1.5">
                            <button
                                v-for="(_, i) in images"
                                :key="i"
                                @click="current = i"
                                :class="[
                                    'h-2 rounded-full transition-all duration-200',
                                    i === current ? 'w-5 bg-primary' : 'w-2 bg-muted-foreground/30 hover:bg-muted-foreground/60',
                                ]"
                                :aria-label="`Foto ${i + 1}`"
                            />
                        </div>

                        <!-- Thumbnail strip -->
                        <div v-if="images.length > 1" class="mt-3 flex gap-2 overflow-x-auto pb-1">
                            <button
                                v-for="(img, i) in images"
                                :key="img.id"
                                @click="current = i"
                                :class="[
                                    'flex-shrink-0 h-10 w-10 overflow-hidden rounded-md border-2 transition-all',
                                    i === current ? 'border-primary opacity-100' : 'border-transparent opacity-60 hover:opacity-90',
                                ]"
                            >
                                <img :src="imgSrc(img)" :alt="`Thumbnail ${i + 1}`" class="h-full w-full object-cover" />
                            </button>
                        </div>

                        <p class="mt-2 text-xs text-muted-foreground">
                            {{ images.length }} foto tersedia · klik foto untuk perbesar
                        </p>
                    </div>
                </div>

                <!-- Right: Details -->
                <div class="rounded-lg border border-sidebar-border/70 bg-background p-4 lg:col-span-2">
                    <h2 class="mb-3 text-base font-semibold">Informasi Koi</h2>
                    <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                        <div class="rounded-lg border border-border p-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Code</dt>
                            <dd class="mt-1 font-medium text-foreground">{{ koi.code }}</dd>
                        </div>
                        <div class="rounded-lg border border-border p-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Name</dt>
                            <dd class="mt-1 font-medium text-foreground">{{ koi.name }}</dd>
                        </div>
                        <div class="rounded-lg border border-border p-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Breeder</dt>
                            <dd class="mt-1 font-medium text-foreground">{{ koi.breeder?.name_breeder ?? '-' }}</dd>
                        </div>
                        <div class="rounded-lg border border-border p-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Created</dt>
                            <dd class="mt-1 font-medium text-foreground">{{ fmtDate(koi.created_at) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Lightbox -->
        <Transition name="fade">
            <div
                v-if="lightboxOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4"
                @click="lightboxOpen = false"
            >
                <img
                    :src="imgSrc(images[lightboxIndex])"
                    alt="Foto koi"
                    class="max-h-[90vh] max-w-[90vw] rounded-xl object-contain shadow-2xl"
                    @click.stop
                />

                <template v-if="images.length > 1">
                    <button
                        @click.stop="lightboxPrev"
                        class="absolute left-4 top-1/2 -translate-y-1/2 flex h-10 w-10 items-center justify-center rounded-full bg-white/20 text-white hover:bg-white/30"
                    >
                        <ChevronLeft class="h-6 w-6" />
                    </button>
                    <button
                        @click.stop="lightboxNext"
                        class="absolute right-4 top-1/2 -translate-y-1/2 flex h-10 w-10 items-center justify-center rounded-full bg-white/20 text-white hover:bg-white/30"
                    >
                        <ChevronRight class="h-6 w-6" />
                    </button>
                    <div class="absolute bottom-6 left-1/2 -translate-x-1/2 rounded-full bg-black/60 px-3 py-1 text-sm font-medium text-white">
                        {{ lightboxIndex + 1 }} / {{ images.length }}
                    </div>
                </template>

                <button
                    @click="lightboxOpen = false"
                    class="absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-full bg-white/20 text-white hover:bg-white/30"
                >
                    ✕
                </button>
            </div>
        </Transition>
    </AppLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
