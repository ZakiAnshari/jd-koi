<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { ChevronLeft, ChevronRight, ImageOff, ArrowLeft, Tag, Hash, CalendarDays, Fish } from 'lucide-vue-next'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { checkout } from '@/routes/customer/pesanan'

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

const props = defineProps<{
    katalog: Katalog
}>()

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
const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value)

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' })
</script>

<template>
    <Head :title="`${katalog.koi.nama} — Katalog Koi`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 sm:p-6 space-y-4">

            <!-- Back link -->
            <Link
                href="/customer/katalog"
                class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground transition-colors"
            >
                <ArrowLeft class="h-4 w-4" />
                Kembali ke Katalog
            </Link>

            <!-- Main card -->
            <div class="rounded-xl border border-border bg-background overflow-hidden">
                <div class="grid lg:grid-cols-[3fr_2fr]">

                    <!-- ── Left: Photo panel ─────────────────────── -->
                    <div class="bg-muted/40 border-b lg:border-b-0 lg:border-r border-border">

                        <!-- Empty state -->
                        <div
                            v-if="images.length === 0"
                            class="aspect-square flex flex-col items-center justify-center gap-3 text-muted-foreground"
                        >
                            <ImageOff class="h-12 w-12 opacity-30" />
                            <p class="text-sm">Belum ada foto tersedia</p>
                        </div>

                        <!-- Carousel -->
                        <div v-else>
                            <!-- Main image -->
                            <div class="relative aspect-square overflow-hidden">
                                <button
                                    type="button"
                                    @click="openLightbox(current)"
                                    class="block h-full w-full cursor-zoom-in"
                                    title="Klik untuk perbesar"
                                >
                                    <img
                                        :src="images[current].url"
                                        :alt="`${katalog.koi.nama} foto ${current + 1}`"
                                        class="h-full w-full object-cover transition-opacity duration-200"
                                    />
                                </button>

                                <!-- Arrow buttons -->
                                <template v-if="images.length > 1">
                                    <button
                                        @click="prev"
                                        class="absolute left-3 top-1/2 -translate-y-1/2 flex h-9 w-9 items-center justify-center rounded-full bg-black/50 text-white shadow hover:bg-black/70 transition-colors"
                                        aria-label="Foto sebelumnya"
                                    >
                                        <ChevronLeft class="h-5 w-5" />
                                    </button>
                                    <button
                                        @click="next"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 flex h-9 w-9 items-center justify-center rounded-full bg-black/50 text-white shadow hover:bg-black/70 transition-colors"
                                        aria-label="Foto berikutnya"
                                    >
                                        <ChevronRight class="h-5 w-5" />
                                    </button>

                                    <!-- Counter -->
                                    <div class="absolute bottom-3 right-3 rounded-full bg-black/60 px-2.5 py-0.5 text-xs font-medium text-white">
                                        {{ current + 1 }} / {{ images.length }}
                                    </div>
                                </template>
                            </div>

                            <!-- Thumbnail strip -->
                            <div v-if="images.length > 1" class="flex gap-2 overflow-x-auto p-3 bg-background border-t border-border">
                                <button
                                    v-for="(img, i) in images"
                                    :key="img.id"
                                    @click="current = i"
                                    :class="[
                                        'flex-shrink-0 h-16 w-16 overflow-hidden rounded-lg border-2 transition-all',
                                        i === current
                                            ? 'border-primary ring-1 ring-primary'
                                            : 'border-transparent opacity-50 hover:opacity-80 hover:border-border'
                                    ]"
                                    :aria-label="`Pilih foto ${i + 1}`"
                                >
                                    <img :src="img.url" :alt="`Thumbnail ${i + 1}`" class="h-full w-full object-cover" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ── Right: Info panel ──────────────────────── -->
                    <div class="flex flex-col p-5 sm:p-6 gap-5">

                        <!-- Name + breeder -->
                        <div>
                            <h1 class="text-xl font-semibold text-foreground leading-snug">
                                {{ katalog.koi.nama }}
                            </h1>
                            <p class="mt-1 text-sm text-muted-foreground flex items-center gap-1.5">
                                <Fish class="h-3.5 w-3.5 flex-shrink-0" />
                                {{ katalog.koi.breeder.name_breeder }}
                            </p>
                        </div>

                        <!-- Price block -->
                        <div class="rounded-lg border border-border bg-muted/40 px-4 py-3">
                            <p class="text-xs font-medium text-muted-foreground mb-0.5">Harga Jual</p>
                            <p class="text-2xl font-bold text-primary tracking-tight">
                                {{ formatCurrency(katalog.sell_idr) }}
                            </p>
                        </div>

                        <!-- Info rows -->
                        <dl class="space-y-3 text-sm">
                            <div class="flex items-center gap-3">
                                <Hash class="h-4 w-4 text-muted-foreground flex-shrink-0" />
                                <dt class="w-28 text-xs font-medium text-muted-foreground">Kode</dt>
                                <dd class="font-mono font-semibold text-foreground">{{ katalog.koi.kode }}</dd>
                            </div>

                            <div class="flex items-start gap-3">
                                <Tag class="h-4 w-4 text-muted-foreground flex-shrink-0 mt-0.5" />
                                <dt class="w-28 text-xs font-medium text-muted-foreground">Stok</dt>
                                <dd>
                                    <span
                                        v-if="katalog.qty > 0"
                                        class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400"
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

                            <div class="flex items-center gap-3">
                                <CalendarDays class="h-4 w-4 text-muted-foreground flex-shrink-0" />
                                <dt class="w-28 text-xs font-medium text-muted-foreground">Tanggal Masuk</dt>
                                <dd class="text-foreground">{{ formatDate(katalog.tanggal) }}</dd>
                            </div>
                        </dl>

                        <!-- Deskripsi -->
                        <div v-if="katalog.koi.deskripsi" class="border-t border-border pt-4">
                            <p class="text-xs font-medium text-muted-foreground mb-2">Deskripsi</p>
                            <p class="text-sm text-foreground leading-relaxed">{{ katalog.koi.deskripsi }}</p>
                        </div>

                        <!-- Spacer pushes CTA down -->
                        <div class="flex-1" />

                        <!-- CTA -->
                        <div class="pt-2 border-t border-border">
                            <Link
                                v-if="katalog.qty > 0"
                                :href="checkout.url(katalog.id)"
                                class="block w-full rounded-lg bg-primary hover:bg-primary/90 text-primary-foreground font-semibold text-sm py-2.5 text-center transition-colors shadow-sm"
                            >
                                Pesan Sekarang
                            </Link>
                            <button
                                v-else
                                disabled
                                class="w-full rounded-lg bg-muted text-muted-foreground font-semibold text-sm py-2.5 cursor-not-allowed"
                            >
                                Stok Habis
                            </button>
                        </div>
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
                        class="absolute left-4 top-1/2 -translate-y-1/2 flex h-10 w-10 items-center justify-center rounded-full bg-white/20 text-white hover:bg-white/30 transition-colors"
                        aria-label="Foto sebelumnya"
                    >
                        <ChevronLeft class="h-6 w-6" />
                    </button>
                    <button
                        @click.stop="lightboxNext"
                        class="absolute right-4 top-1/2 -translate-y-1/2 flex h-10 w-10 items-center justify-center rounded-full bg-white/20 text-white hover:bg-white/30 transition-colors"
                        aria-label="Foto berikutnya"
                    >
                        <ChevronRight class="h-6 w-6" />
                    </button>
                    <div class="absolute bottom-6 left-1/2 -translate-x-1/2 rounded-full bg-black/60 px-3 py-1 text-sm font-medium text-white">
                        {{ lightboxIndex + 1 }} / {{ images.length }}
                    </div>
                </template>

                <button
                    @click="lightboxOpen = false"
                    class="absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-full bg-white/20 text-white hover:bg-white/30 transition-colors"
                    aria-label="Tutup"
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
