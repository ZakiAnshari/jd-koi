<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { ChevronLeft, ChevronRight, ImageOff } from 'lucide-vue-next'
import { computed, ref } from 'vue'

interface KoiImage {
    id: number
    url: string
}

interface Katalog {
    id: number
    tanggal: string
    qty: number
    sell_jpy: number
    kurs: number
    sell_idr: number
    margin: number
    stock_jpy: number
    stock_idr: number
    fee: number
    profit: number
    koi?: {
        code: string
        name: string
        breeder?: string
        images: KoiImage[]
    }
    pembelian?: {
        date: string
        price_jpy: number
        price_idr: number
    }
}

const props = defineProps<{ katalog: Katalog }>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '#' },
    { title: 'Katalog Koi', href: '/admin/katalog-koi' },
    { title: props.katalog.koi?.name ?? 'Detail', href: '#' },
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
function fmtIDR(v: number) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v)
}
function fmtJPY(v: number) {
    return new Intl.NumberFormat('ja-JP', { style: 'currency', currency: 'JPY', maximumFractionDigits: 0 }).format(v)
}
function fmtDate(d: string) {
    return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
}
function profitClass(v: number) {
    return v >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Detail Katalog - ${katalog.koi?.name ?? ''}`" />

        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold">Detail Katalog Koi</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ katalog.koi?.code }} · {{ katalog.koi?.name }}
                        <span v-if="katalog.koi?.breeder"> · {{ katalog.koi.breeder }}</span>
                    </p>
                </div>
                <Link
                    href="/master-data/koi?tab=katalog"
                    class="inline-flex items-center gap-1 rounded-md border border-border bg-background px-3 py-2 text-sm font-medium hover:bg-muted"
                >
                    <ChevronLeft class="h-4 w-4" />
                    Kembali
                </Link>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Left: Info cards -->
                <div class="space-y-4">
                    <!-- Koi Info -->
                    <div class="rounded-lg border border-sidebar-border/70 bg-background p-4">
                        <h2 class="mb-3 text-base font-semibold">Informasi Koi</h2>
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Kode</dt>
                                <dd class="mt-0.5 font-medium text-foreground">{{ katalog.koi?.code ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Nama</dt>
                                <dd class="mt-0.5 font-medium text-foreground">{{ katalog.koi?.name ?? '-' }}</dd>
                            </div>
                            <div class="col-span-2">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Breeder</dt>
                                <dd class="mt-0.5 font-medium text-foreground">{{ katalog.koi?.breeder ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Tanggal</dt>
                                <dd class="mt-0.5 font-medium text-foreground">{{ fmtDate(katalog.tanggal) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Qty</dt>
                                <dd class="mt-0.5 font-medium text-foreground">{{ katalog.qty }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Harga & Kalkulasi -->
                    <div class="rounded-lg border border-sidebar-border/70 bg-background p-4">
                        <h2 class="mb-3 text-base font-semibold">Harga & Kalkulasi</h2>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-muted-foreground">Harga Beli (JPY)</dt>
                                <dd class="font-medium text-foreground">{{ fmtJPY(Number(katalog.stock_jpy)) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-muted-foreground">Harga Beli (IDR)</dt>
                                <dd class="font-medium text-foreground">{{ fmtIDR(Number(katalog.stock_idr)) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-muted-foreground">Kurs (JPY → IDR)</dt>
                                <dd class="font-medium text-foreground">{{ Number(katalog.kurs).toLocaleString('id-ID') }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-muted-foreground">Harga Jual (JPY)</dt>
                                <dd class="font-medium text-foreground">{{ fmtJPY(Number(katalog.sell_jpy)) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-muted-foreground">Harga Jual (IDR)</dt>
                                <dd class="font-medium text-foreground">{{ fmtIDR(Number(katalog.sell_idr)) }}</dd>
                            </div>
                            <div class="my-1 border-t border-border" />
                            <div class="flex justify-between">
                                <dt class="text-muted-foreground">Margin</dt>
                                <dd class="font-semibold" :class="profitClass(Number(katalog.margin))">
                                    {{ fmtIDR(Number(katalog.margin)) }}
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-muted-foreground">Fee</dt>
                                <dd class="font-medium text-foreground">{{ fmtIDR(Number(katalog.fee)) }}</dd>
                            </div>
                            <div class="my-1 border-t border-border" />
                            <div class="flex justify-between text-base">
                                <dt class="font-semibold">Profit</dt>
                                <dd class="font-bold" :class="profitClass(Number(katalog.profit))">
                                    {{ fmtIDR(Number(katalog.profit)) }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Data Pembelian -->
                    <div v-if="katalog.pembelian" class="rounded-lg border border-sidebar-border/70 bg-background p-4">
                        <h2 class="mb-3 text-base font-semibold">Data Pembelian</h2>
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                            <div class="col-span-2">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Tanggal Beli</dt>
                                <dd class="mt-0.5 font-medium text-foreground">{{ fmtDate(katalog.pembelian.date) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Harga (JPY)</dt>
                                <dd class="mt-0.5 font-medium text-foreground">{{ fmtJPY(Number(katalog.pembelian.price_jpy)) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Harga (IDR)</dt>
                                <dd class="mt-0.5 font-medium text-foreground">{{ fmtIDR(Number(katalog.pembelian.price_idr)) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Right: Foto Carousel -->
                <div class="rounded-lg border border-sidebar-border/70 bg-background p-4">
                    <h2 class="mb-3 text-base font-semibold">Foto Koi</h2>

                    <!-- Empty state -->
                    <div v-if="images.length === 0"
                        class="flex flex-col items-center justify-center gap-2 rounded-lg bg-muted/40 py-16 text-muted-foreground">
                        <ImageOff class="h-10 w-10 opacity-40" />
                        <p class="text-sm">Belum ada foto untuk koi ini.</p>
                        <p class="text-xs opacity-70">Upload foto melalui menu Master Data → Data Koi</p>
                    </div>

                    <!-- Carousel -->
                    <div v-else>
                        <!-- Main image -->
                        <div class="relative overflow-hidden rounded-lg bg-muted">
                            <div class="aspect-square w-full">
                                <button type="button" @click="openLightbox(current)" class="block h-full w-full">
                                    <img
                                        :src="images[current].url"
                                        :alt="`Foto koi ${katalog.koi?.name} ${current + 1}`"
                                        class="h-full w-full object-cover transition-opacity duration-200"
                                    />
                                </button>
                            </div>

                            <!-- Arrows — hanya muncul jika lebih dari 1 foto -->
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
                                    i === current ? 'w-5 bg-primary' : 'w-2 bg-muted-foreground/30 hover:bg-muted-foreground/60'
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
                                    'flex-shrink-0 h-14 w-14 overflow-hidden rounded-md border-2 transition-all',
                                    i === current ? 'border-primary opacity-100' : 'border-transparent opacity-60 hover:opacity-90'
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

                <!-- Lightbox arrows -->
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
