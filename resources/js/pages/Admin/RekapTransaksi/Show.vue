<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { ChevronLeft, ChevronRight, ImageOff } from 'lucide-vue-next'
import { computed, ref } from 'vue'

type Breeder  = { id: number; name_breeder: string }
type JenisKoi = { id: number; nama_jenis: string }
type KoiImage = { id: number; url: string }

type Koi = {
    id: number
    code: string
    name: string
    age: number | null
    size: number | null
    box_qty: number | null
    weight: number | null
    total_weight: number | null
    breeder?: Breeder
    jenis_koi?: JenisKoi
    images?: KoiImage[]
}

type KatalogKoi = {
    id: number
    sell_jpy: number
    kurs: number
    sell_idr: number
    stock_jpy: number
    stock_idr: number
    fee: number
    profit: number
    koi?: Koi
}

type Customer = { id: number; name: string; email: string }

type Pesanan = {
    id: number
    kode_pesanan: string
    qty: number
    harga_satuan: number
    total_harga: number
    status: string
    catatan?: string
    created_at: string
    customer?: Customer
    katalog_koi?: KatalogKoi
}

const props = defineProps<{ pesanan: Pesanan }>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Rekap Transaksi', href: '/admin/rekap-transaksi' },
    { title: props.pesanan.kode_pesanan, href: `/admin/rekap-transaksi/${props.pesanan.id}` },
]

const statusMap: Record<string, { label: string; cls: string }> = {
    pending:   { label: 'On Hold',       cls: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' },
    shipped: { label: 'Shipped', cls: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' },
    azukari: { label: 'Azukari', cls: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' },
    dead:    { label: 'Dead',    cls: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' },
}
function statusLabel(s: string) { return statusMap[s]?.label ?? s }
function statusCls(s: string)   { return statusMap[s]?.cls ?? '' }

function fmtDate(d: string) {
    return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
}
function fmtCurrency(n: number | null | undefined, prefix = '¥') {
    if (n == null) return '-'
    return prefix + Number(n).toLocaleString('id-ID')
}

// ─── Carousel ─────────────────────────────────────────────
const images  = computed<KoiImage[]>(() => props.pesanan.katalog_koi?.koi?.images ?? [])
const current = ref(0)
function prev() { current.value = (current.value - 1 + images.value.length) % images.value.length }
function next() { current.value = (current.value + 1) % images.value.length }

const lightboxOpen  = ref(false)
const lightboxIndex = ref(0)
function openLightbox(i: number) { lightboxIndex.value = i; lightboxOpen.value = true }
function lbPrev() { lightboxIndex.value = (lightboxIndex.value - 1 + images.value.length) % images.value.length }
function lbNext() { lightboxIndex.value = (lightboxIndex.value + 1) % images.value.length }

const koi      = computed(() => props.pesanan.katalog_koi?.koi)
const katalog  = computed(() => props.pesanan.katalog_koi)
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Rekap: ${pesanan.kode_pesanan}`" />

        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Detail Transaksi</h1>
                    <p class="text-sm text-muted-foreground">Invoice: {{ pesanan.kode_pesanan }}</p>
                </div>
                <Link
                    href="/admin/rekap-transaksi"
                    class="inline-flex items-center gap-1 self-start rounded-md border border-border bg-background px-3 py-2 text-sm font-medium hover:bg-muted"
                >
                    <ChevronLeft class="h-4 w-4" /> Kembali
                </Link>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Foto Koi -->
                <div class="rounded-lg border border-sidebar-border/70 bg-background p-4 lg:col-span-1">
                    <h2 class="mb-3 text-base font-semibold">Foto Koi</h2>

                    <div v-if="images.length === 0"
                        class="flex flex-col items-center justify-center gap-2 rounded-lg bg-muted/40 py-16 text-muted-foreground">
                        <ImageOff class="h-10 w-10 opacity-40" />
                        <p class="text-sm">Belum ada foto.</p>
                    </div>

                    <div v-else>
                        <div class="relative overflow-hidden rounded-lg bg-muted">
                            <div class="h-32 w-64">
                                <button type="button" @click="openLightbox(current)" class="block h-full w-full">
                                    <img :src="images[current].url" :alt="`Foto ${current + 1}`"
                                        class="h-full w-full object-cover" />
                                </button>
                            </div>
                            <template v-if="images.length > 1">
                                <button @click="prev"
                                    class="absolute left-2 top-1/2 -translate-y-1/2 flex h-8 w-8 items-center justify-center rounded-full bg-black/50 text-white hover:bg-black/70">
                                    <ChevronLeft class="h-4 w-4" />
                                </button>
                                <button @click="next"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 flex h-8 w-8 items-center justify-center rounded-full bg-black/50 text-white hover:bg-black/70">
                                    <ChevronRight class="h-4 w-4" />
                                </button>
                                <div class="absolute bottom-2 right-2 rounded-full bg-black/60 px-2 py-0.5 text-xs text-white">
                                    {{ current + 1 }} / {{ images.length }}
                                </div>
                            </template>
                        </div>
                        <div v-if="images.length > 1" class="mt-3 flex gap-2 overflow-x-auto pb-1">
                            <button v-for="(img, i) in images" :key="img.id" @click="current = i"
                                :class="['flex-shrink-0 h-10 w-10 overflow-hidden rounded-md border-2 transition-all',
                                    i === current ? 'border-primary opacity-100' : 'border-transparent opacity-60 hover:opacity-90']">
                                <img :src="img.url" class="h-full w-full object-cover" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Detail -->
                <div class="space-y-4 lg:col-span-2">
                    <!-- Info Pesanan -->
                    <div class="rounded-lg border border-sidebar-border/70 bg-background p-4">
                        <h2 class="mb-3 text-base font-semibold">Informasi Pesanan</h2>
                        <dl class="grid grid-cols-2 gap-3 text-sm">
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Invoice</dt>
                                <dd class="mt-1 font-mono font-medium">{{ pesanan.kode_pesanan }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Status</dt>
                                <dd class="mt-1">
                                    <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', statusCls(pesanan.status)]">
                                        {{ statusLabel(pesanan.status) }}
                                    </span>
                                </dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Sales Date</dt>
                                <dd class="mt-1 font-medium">{{ fmtDate(pesanan.created_at) }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Customer</dt>
                                <dd class="mt-1 font-medium">{{ pesanan.customer?.name ?? '-' }}</dd>
                            </div>
                            <div class="col-span-2 rounded-lg border border-border p-3" v-if="pesanan.catatan">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Catatan</dt>
                                <dd class="mt-1 font-medium">{{ pesanan.catatan }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Info Koi -->
                    <div class="rounded-lg border border-sidebar-border/70 bg-background p-4">
                        <h2 class="mb-3 text-base font-semibold">Informasi Koi</h2>
                        <dl class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">KOI Code</dt>
                                <dd class="mt-1 font-medium">{{ koi?.code ?? '-' }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Nama Koi</dt>
                                <dd class="mt-1 font-medium">{{ koi?.name ?? '-' }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Breeder</dt>
                                <dd class="mt-1 font-medium">{{ koi?.breeder?.name_breeder ?? '-' }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Jenis Koi</dt>
                                <dd class="mt-1 font-medium">{{ koi?.jenis_koi?.nama_jenis ?? '-' }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Age</dt>
                                <dd class="mt-1 font-medium">{{ koi?.age != null ? koi.age + ' bulan' : '-' }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Size</dt>
                                <dd class="mt-1 font-medium">{{ koi?.size != null ? koi.size + ' cm' : '-' }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Box QTY</dt>
                                <dd class="mt-1 font-medium">{{ koi?.box_qty ?? '-' }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Weight</dt>
                                <dd class="mt-1 font-medium">{{ koi?.weight != null ? koi.weight + ' kg' : '-' }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Total Weight</dt>
                                <dd class="mt-1 font-medium">{{ koi?.total_weight != null ? koi.total_weight + ' kg' : '-' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Harga -->
                    <div class="rounded-lg border border-sidebar-border/70 bg-background p-4">
                        <h2 class="mb-3 text-base font-semibold">Harga Transaksi</h2>
                        <dl class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Koi QTY</dt>
                                <dd class="mt-1 font-medium">{{ pesanan.qty }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Unit Price</dt>
                                <dd class="mt-1 font-medium">{{ fmtCurrency(pesanan.harga_satuan) }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Sales Price</dt>
                                <dd class="mt-1 font-semibold text-primary">{{ fmtCurrency(pesanan.total_harga) }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Stock JPY</dt>
                                <dd class="mt-1 font-medium">{{ fmtCurrency(katalog?.stock_jpy) }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Stock IDR</dt>
                                <dd class="mt-1 font-medium">{{ fmtCurrency(katalog?.stock_idr, 'Rp') }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Profit</dt>
                                <dd class="mt-1 font-semibold text-green-600 dark:text-green-400">
                                    {{ fmtCurrency(katalog?.profit, 'Rp') }}
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
                <img :src="images[lightboxIndex].url" alt="Foto koi"
                    class="max-h-[90vh] max-w-[90vw] rounded-xl object-contain shadow-2xl" @click.stop />
                <template v-if="images.length > 1">
                    <button @click.stop="lbPrev"
                        class="absolute left-4 top-1/2 -translate-y-1/2 flex h-10 w-10 items-center justify-center rounded-full bg-white/20 text-white hover:bg-white/30">
                        <ChevronLeft class="h-6 w-6" />
                    </button>
                    <button @click.stop="lbNext"
                        class="absolute right-4 top-1/2 -translate-y-1/2 flex h-10 w-10 items-center justify-center rounded-full bg-white/20 text-white hover:bg-white/30">
                        <ChevronRight class="h-6 w-6" />
                    </button>
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
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
