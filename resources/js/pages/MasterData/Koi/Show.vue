<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { ChevronLeft, ChevronRight, ImageOff } from 'lucide-vue-next'
import { computed, ref } from 'vue'

type Breeder  = { id: number; name_breeder: string }
type JenisKoi = { id: number; nama_jenis: string }
type KoiImage = { id: number; image_path: string; url: string; sort_order: number }

type Koi = {
    id: number
    breeder_id: number
    jenis_koi_id: number | null
    code: string
    name: string
    age: number | null
    size: number | null
    box_qty: number | null
    weight: number | null
    total_weight: number | null
    photo?: string
    images?: KoiImage[]
    breeder?: Breeder
    jenis_koi?: JenisKoi
    customer?: { id: number; name: string } | null
    customer_id: number | null
    sale_tanggal: string | null
    sale_qty: number | null
    unit_price: string | null
    buy_jpy: string | null
    kurs: string | null
    sell_idr: string | null
    stock_price: string | null
    fee: string | null
    sale_status: string | null
    created_at: string
}

const props = defineProps<{ koi: Koi }>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data', href: '#' },
    { title: 'Data Koi', href: '/master-data/koi?tab=koi' },
    { title: props.koi.name, href: `/master-data/koi/${props.koi.id}` },
]

// ─── Carousel ─────────────────────────────────────────────
const images  = computed<KoiImage[]>(() => props.koi.images ?? [])
const current = ref(0)

function prev() { current.value = (current.value - 1 + images.value.length) % images.value.length }
function next() { current.value = (current.value + 1) % images.value.length }

// ─── Lightbox ─────────────────────────────────────────────
const lightboxOpen  = ref(false)
const lightboxIndex = ref(0)

function openLightbox(index: number) { lightboxIndex.value = index; lightboxOpen.value = true }
function lightboxPrev() { lightboxIndex.value = (lightboxIndex.value - 1 + images.value.length) % images.value.length }
function lightboxNext() { lightboxIndex.value = (lightboxIndex.value + 1) % images.value.length }

function fmtDate(d: string) {
    return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
}

const fmtIDR = (v: string | number | null) =>
    v != null ? new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(v)) : '-'

const salesPriceJpy = computed(() => {
    const p = Number(props.koi.unit_price) || 0
    const q = Number(props.koi.sale_qty)   || 1
    return p > 0 ? p * q : null
})
const salesPriceIdr = computed(() => Number(props.koi.sell_idr) || 0)
const unitPriceIdr  = computed(() => {
    const p = Number(props.koi.unit_price) || 0
    const k = Number(props.koi.kurs)       || 0
    return p > 0 && k > 0 ? Math.round(p * k) : null
})
const stockPriceIdr = computed(() => {
    const b = Number(props.koi.buy_jpy) || 0
    const k = Number(props.koi.kurs)   || 0
    return b > 0 && k > 0 ? Math.round(b * k) : null
})
const profit = computed(() => salesPriceIdr.value - (stockPriceIdr.value ?? 0) - Number(props.koi.fee ?? 0))

const STATUS_LABEL: Record<string, string> = {
    cancel: 'Cancel', dead_refund: 'Dead Refund', refund: 'Refund',
    cancel_refund: 'Cancel Refund', pending: 'Pending', shipped: 'Shipped',
    azukari: 'Azukari', dead: 'Dead',
}
const STATUS_CLASS: Record<string, string> = {
    shipped:      'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
    pending:      'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
    azukari:      'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
    refund:       'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300',
    cancel:       'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
    cancel_refund:'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
    dead_refund:  'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
    dead:         'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
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

                    <div v-if="images.length === 0"
                        class="flex flex-col items-center justify-center gap-2 rounded-lg bg-muted/40 py-16 text-muted-foreground">
                        <ImageOff class="h-10 w-10 opacity-40" />
                        <p class="text-sm">Belum ada foto untuk koi ini.</p>
                        <p class="text-xs opacity-70">Upload foto melalui menu Edit Data Koi</p>
                    </div>

                    <div v-else>
                        <div class="relative overflow-hidden rounded-lg bg-muted">
                            <div class="h-64 w-full">
                                <button type="button" @click="openLightbox(current)" class="block h-full w-full">
                                    <img
                                        :src="images[current].url"
                                        :alt="`Foto koi ${koi.name} ${current + 1}`"
                                        class="h-full w-full object-cover transition-opacity duration-200"
                                    />
                                </button>
                            </div>
                            <template v-if="images.length > 1">
                                <button @click="prev"
                                    class="absolute left-2 top-1/2 -translate-y-1/2 flex h-9 w-9 items-center justify-center rounded-full bg-black/50 text-white shadow hover:bg-black/70 transition-colors"
                                    aria-label="Foto sebelumnya">
                                    <ChevronLeft class="h-5 w-5" />
                                </button>
                                <button @click="next"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 flex h-9 w-9 items-center justify-center rounded-full bg-black/50 text-white shadow hover:bg-black/70 transition-colors"
                                    aria-label="Foto berikutnya">
                                    <ChevronRight class="h-5 w-5" />
                                </button>
                                <div class="absolute bottom-2 right-2 rounded-full bg-black/60 px-2 py-0.5 text-xs font-medium text-white">
                                    {{ current + 1 }} / {{ images.length }}
                                </div>
                            </template>
                        </div>

                        <div v-if="images.length > 1" class="mt-3 flex items-center justify-center gap-1.5">
                            <button v-for="(_, i) in images" :key="i" @click="current = i"
                                :class="['h-2 rounded-full transition-all duration-200', i === current ? 'w-5 bg-primary' : 'w-2 bg-muted-foreground/30 hover:bg-muted-foreground/60']"
                                :aria-label="`Foto ${i + 1}`" />
                        </div>

                        <div v-if="images.length > 1" class="mt-3 flex gap-2 overflow-x-auto pb-1">
                            <button v-for="(img, i) in images" :key="img.id" @click="current = i"
                                :class="['flex-shrink-0 h-10 w-10 overflow-hidden rounded-md border-2 transition-all', i === current ? 'border-primary opacity-100' : 'border-transparent opacity-60 hover:opacity-90']">
                                <img :src="img.url" :alt="`Thumbnail ${i + 1}`" class="h-full w-full object-cover" />
                            </button>
                        </div>

                        <p class="mt-2 text-xs text-muted-foreground">
                            {{ images.length }} foto tersedia · klik foto untuk perbesar
                        </p>
                    </div>
                </div>

                <!-- Right: Details -->
                <div class="space-y-4 lg:col-span-2">
                    <!-- Identitas -->
                    <div class="rounded-lg border border-sidebar-border/70 bg-background p-4">
                        <h2 class="mb-3 text-base font-semibold">Informasi Identitas</h2>
                        <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Code</dt>
                                <dd class="mt-1 font-medium text-foreground">{{ koi.code }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Nama</dt>
                                <dd class="mt-1 font-medium text-foreground">{{ koi.name }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Breeder</dt>
                                <dd class="mt-1 font-medium text-foreground">{{ koi.breeder?.name_breeder ?? '-' }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Jenis Koi</dt>
                                <dd class="mt-1 font-medium text-foreground">{{ koi.jenis_koi?.nama_jenis ?? '-' }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3 sm:col-span-2">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Tanggal Ditambahkan</dt>
                                <dd class="mt-1 font-medium text-foreground">{{ fmtDate(koi.created_at) }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Data Fisik -->
                    <div class="rounded-lg border border-sidebar-border/70 bg-background p-4">
                        <h2 class="mb-3 text-base font-semibold">Data Fisik</h2>
                        <dl class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Age</dt>
                                <dd class="mt-1 font-medium text-foreground">
                                    {{ koi.age != null ? koi.age + ' bulan' : '-' }}
                                </dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Size</dt>
                                <dd class="mt-1 font-medium text-foreground">
                                    {{ koi.size != null ? koi.size + ' cm' : '-' }}
                                </dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Box QTY</dt>
                                <dd class="mt-1 font-medium text-foreground">{{ koi.box_qty ?? '-' }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Weight</dt>
                                <dd class="mt-1 font-medium text-foreground">
                                    {{ koi.weight != null ? koi.weight + ' kg' : '-' }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Penjualan -->
                    <div class="rounded-lg border border-sidebar-border/70 bg-background p-4">
                        <h2 class="mb-3 text-base font-semibold">Penjualan</h2>
                        <dl class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Customer</dt>
                                <dd class="mt-1 font-medium text-foreground">{{ koi.customer?.name ?? '-' }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Status</dt>
                                <dd class="mt-1">
                                    <template v-if="koi.sale_status">
                                        <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium', STATUS_CLASS[koi.sale_status] ?? '']">
                                            {{ STATUS_LABEL[koi.sale_status] ?? koi.sale_status }}
                                        </span>
                                    </template>
                                    <span v-else class="font-medium text-foreground">-</span>
                                </dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Qty</dt>
                                <dd class="mt-1 font-medium text-foreground">{{ koi.sale_qty ?? '-' }}</dd>
                            </div>
                            <!-- Stock JPY -->
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Stock JPY (modal)</dt>
                                <dd class="mt-1 font-medium text-foreground">
                                    {{ koi.buy_jpy != null ? '¥' + Number(koi.buy_jpy).toLocaleString('ja-JP') : '-' }}
                                </dd>
                                <dd v-if="stockPriceIdr" class="mt-0.5 text-xs text-blue-600 dark:text-blue-400">≈ {{ fmtIDR(stockPriceIdr) }}</dd>
                            </div>
                            <!-- Unit Price JPY -->
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Unit Price (JPY)</dt>
                                <dd class="mt-1 font-medium text-foreground">
                                    {{ koi.unit_price != null ? '¥' + Number(koi.unit_price).toLocaleString('ja-JP') : '-' }}
                                </dd>
                                <dd v-if="unitPriceIdr" class="mt-0.5 text-xs text-amber-600 dark:text-amber-400">≈ {{ fmtIDR(unitPriceIdr) }}</dd>
                            </div>
                            <!-- Sales Price JPY -->
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Sales Price (JPY)</dt>
                                <dd class="mt-1 font-medium text-foreground">
                                    {{ salesPriceJpy != null ? '¥' + salesPriceJpy.toLocaleString('ja-JP') : '-' }}
                                </dd>
                                <dd v-if="salesPriceIdr" class="mt-0.5 text-xs text-green-600 dark:text-green-400">≈ {{ fmtIDR(salesPriceIdr) }}</dd>
                            </div>
                            <!-- Kurs -->
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Kurs (JPY→IDR)</dt>
                                <dd class="mt-1 font-medium text-foreground">
                                    {{ koi.kurs != null ? 'Rp ' + Number(koi.kurs).toLocaleString('id-ID') + ' / ¥1' : '-' }}
                                </dd>
                            </div>
                            <!-- Fee -->
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Fee</dt>
                                <dd class="mt-1 font-medium text-foreground">{{ fmtIDR(koi.fee) }}</dd>
                            </div>
                            <!-- Total Diterima -->
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Total Diterima</dt>
                                <dd class="mt-1 font-semibold text-foreground">{{ fmtIDR(salesPriceIdr - Number(koi.fee ?? 0)) }}</dd>
                            </div>
                            <!-- Profit -->
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Profit</dt>
                                <dd class="mt-1 font-semibold" :class="profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400'">
                                    {{ fmtIDR(profit) }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lightbox -->
        <Transition name="fade">
            <div v-if="lightboxOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4"
                @click="lightboxOpen = false">
                <img
                    :src="images[lightboxIndex].url"
                    alt="Foto koi"
                    class="max-h-[90vh] max-w-[90vw] rounded-xl object-contain shadow-2xl"
                    @click.stop
                />
                <template v-if="images.length > 1">
                    <button @click.stop="lightboxPrev"
                        class="absolute left-4 top-1/2 -translate-y-1/2 flex h-10 w-10 items-center justify-center rounded-full bg-white/20 text-white hover:bg-white/30">
                        <ChevronLeft class="h-6 w-6" />
                    </button>
                    <button @click.stop="lightboxNext"
                        class="absolute right-4 top-1/2 -translate-y-1/2 flex h-10 w-10 items-center justify-center rounded-full bg-white/20 text-white hover:bg-white/30">
                        <ChevronRight class="h-6 w-6" />
                    </button>
                    <div class="absolute bottom-6 left-1/2 -translate-x-1/2 rounded-full bg-black/60 px-3 py-1 text-sm font-medium text-white">
                        {{ lightboxIndex + 1 }} / {{ images.length }}
                    </div>
                </template>
                <button @click="lightboxOpen = false"
                    class="absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-full bg-white/20 text-white hover:bg-white/30">
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
