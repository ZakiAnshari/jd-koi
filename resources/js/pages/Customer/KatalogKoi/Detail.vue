<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { ChevronLeft, ChevronRight, ImageOff } from 'lucide-vue-next'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

type KoiImage = {
    id: number
    url: string
}

type Breeder = {
    name_breeder: string
}

type Koi = {
    id: number
    nama: string
    kode: string
    images: KoiImage[]
    deskripsi?: string
    breeder: Breeder
}

type Katalog = {
    id: number
    tanggal: string
    qty: number
    sell_idr: number
    koi: Koi
}

const props = defineProps<{ katalog: Katalog }>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Katalog Koi', href: '/customer/katalog' },
    { title: props.katalog.koi.nama, href: '#' },
]

// ─── Carousel ─────────────────────────────────────────────
const images = computed<KoiImage[]>(() => props.katalog.koi?.images ?? [])
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

// ─── Formatting ───────────────────────────────────────────
function formatCurrency(value: number) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value)
}
function formatDate(date: string) {
    return new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`${katalog.koi.nama} — Katalog Koi`" />

        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">{{ katalog.koi.nama }}</h1>
                    <p class="text-sm text-muted-foreground">Koi Code: {{ katalog.koi.kode }}</p>
                </div>
                <Link
                    href="/customer/katalog"
                    class="inline-flex items-center gap-1 self-start rounded-md border border-border bg-background px-3 py-2 text-sm font-medium hover:bg-muted"
                >
                    <ChevronLeft class="h-4 w-4" />
                    Kembali
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
                    </div>

                    <!-- Carousel -->
                    <div v-else>
                        <!-- Main image -->
                        <div class="relative overflow-hidden rounded-lg bg-muted">
                            <div class="h-64 w-full">
                                <button type="button" @click="openLightbox(current)" class="block h-full w-full">
                                    <img
                                        :src="images[current].url"
                                        :alt="`Foto koi ${katalog.koi.nama} ${current + 1}`"
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
                                <img :src="img.url" :alt="`Thumbnail ${i + 1}`" class="h-full w-full object-cover" />
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
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Kode</dt>
                            <dd class="mt-1 font-medium text-foreground">{{ katalog.koi.kode }}</dd>
                        </div>
                        <div class="rounded-lg border border-border p-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Nama</dt>
                            <dd class="mt-1 font-medium text-foreground">{{ katalog.koi.nama }}</dd>
                        </div>
                        <div class="rounded-lg border border-border p-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Breeder</dt>
                            <dd class="mt-1 font-medium text-foreground">{{ katalog.koi.breeder.name_breeder }}</dd>
                        </div>
                        <div class="rounded-lg border border-border p-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Tanggal Masuk</dt>
                            <dd class="mt-1 font-medium text-foreground">{{ formatDate(katalog.tanggal) }}</dd>
                        </div>
                        <div class="rounded-lg border border-border p-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Harga Jual</dt>
                            <dd class="mt-1 font-medium text-foreground">{{ formatCurrency(katalog.sell_idr) }}</dd>
                        </div>
                        <div class="rounded-lg border border-border p-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Stok</dt>
                            <dd class="mt-1">
                                <span
                                    v-if="katalog.qty > 0"
                                    class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400"
                                >
                                    {{ katalog.qty }} ekor tersedia
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-400"
                                >
                                    Stok Habis
                                </span>
                            </dd>
                        </div>
                    </dl>

                    <!-- Deskripsi -->
                    <div v-if="katalog.koi.deskripsi" class="mt-4 border-t border-border pt-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-2">Deskripsi</p>
                        <p class="text-sm text-foreground leading-relaxed">{{ katalog.koi.deskripsi }}</p>
                    </div>

                    <!-- CTA -->
                    <div class="mt-4 border-t border-border pt-4">
                        <Link
                            v-if="katalog.qty > 0"
                            :href="`/customer/checkout/${katalog.id}`"
                            class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
                        >
                            Pesan Sekarang
                        </Link>
                        <button
                            v-else
                            disabled
                            class="inline-flex items-center justify-center rounded-md bg-muted px-4 py-2 text-sm font-medium text-muted-foreground cursor-not-allowed"
                        >
                            Stok Habis
                        </button>
                    </div>
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
                    :src="images[lightboxIndex].url"
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
