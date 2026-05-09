<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { ChevronLeft, ChevronRight, ImageOff, Pencil, X } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import { useSwal } from '@/composables/useSwal'

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
    sale_invoice: string | null
    sale_tanggal: string | null
    sale_shipped_date: string | null
    shipping_date: string | null
    sale_qty: number | null
    unit_price: string | null
    buy_jpy: string | null
    kurs: string | null
    sell_idr: string | null
    stock_price: string | null
    fee: string | null
    sale_status: string | null
    shipping_cost: string | null
    shipping_qty: number | null
    shipping_batch: string | null
    shipping_note: string | null
    created_at: string
}

type BatchMate = {
    id: number
    code: string
    sale_qty: number | null
    customer: { id: number; name: string } | null
}

const props = defineProps<{ koi: Koi; batchMates: BatchMate[] }>()

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
    const m = Number(props.koi.stock_price) || 0
    const k = Number(props.koi.kurs)        || 0
    return m > 0 && k > 0 ? Math.round(m * k) : null
})
const totalModalIdr = computed(() => {
    const m = Number(props.koi.stock_price) || 0
    const k = Number(props.koi.kurs)        || 0
    const q = Number(props.koi.sale_qty)    || 1
    return m > 0 && k > 0 ? Math.round(m * k * q) : null
})
const profit = computed(() => salesPriceIdr.value - (totalModalIdr.value ?? 0) + Number(props.koi.fee ?? 0))
const shippingCostIdr = computed(() => { const c = Number(props.koi.shipping_cost) || 0; return c > 0 ? c : null })
const shippingPerEkor = computed(() => {
    const c = shippingCostIdr.value || 0
    const q = Number(props.koi.shipping_qty) || 0
    return c > 0 && q > 0 ? Math.round(c / q) : null
})
const shippingKena = computed(() => {
    const p = shippingPerEkor.value || 0
    const q = Number(props.koi.sale_qty) || 1
    return p > 0 ? Math.round(p * q) : null
})

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

// ─── Edit Modal ───────────────────────────────────────────
const showEditModal = ref(false)

const editForm = useForm({
    breeder_id: String(props.koi.breeder_id) || '',
    jenis_koi_id: props.koi.jenis_koi_id ? String(props.koi.jenis_koi_id) : '',
    code: props.koi.code || '',
    name: props.koi.name || '',
    age: props.koi.age != null ? String(props.koi.age) : '',
    size: props.koi.size != null ? String(props.koi.size) : '',
    box_qty: props.koi.box_qty != null ? String(props.koi.box_qty) : '',
    weight: props.koi.weight != null ? String(props.koi.weight) : '',
    total_weight: props.koi.total_weight != null ? String(props.koi.total_weight) : '',
    unit_price: props.koi.unit_price != null ? String(Math.round(Number(props.koi.unit_price))) : '',
    stock_price: props.koi.stock_price != null ? String(Math.round(Number(props.koi.stock_price))) : '',
    kurs: props.koi.kurs != null ? String(Math.round(Number(props.koi.kurs))) : '',
    fee: props.koi.fee != null ? String(Math.round(Number(props.koi.fee))) : '0',
})

function submitEdit() {
    editForm.put(`/master-data/koi/${props.koi.id}?_method=PUT`, {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false
            setTimeout(() => useSwal().fire({ icon: 'success', title: 'Berhasil!', text: 'Data koi berhasil diperbarui.', timer: 2000, showConfirmButton: false }), 300)
        },
        onError: () => useSwal().fire({ icon: 'error', title: 'Gagal!', text: 'Terjadi kesalahan saat memperbarui data.', timer: 2000, showConfirmButton: false }),
    })
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
                <div class="flex items-center gap-2 self-start">
                    <Link
                        href="/master-data/koi"
                        class="inline-flex items-center gap-1 rounded-md border border-border bg-background px-3 py-2 text-sm font-medium hover:bg-muted"
                    >
                        <ChevronLeft class="h-4 w-4" />
                        Back
                    </Link>
                    <Link
                        :href="`/master-data/koi/${koi.id}/edit`"
                        class="inline-flex items-center gap-1 rounded-md border border-border bg-background px-3 py-2 text-sm font-medium hover:bg-muted"
                    >
                        <Pencil class="h-4 w-4" />
                        Edit
                    </Link>
                </div>
            </div>

            <!-- Content -->
            <div class="grid gap-6 lg:grid-cols-3 auto-rows-max items-start">
                <!-- Kolom Kiri: Foto + Biaya Shipping + Patungan -->
                <div class="flex flex-col gap-6 lg:col-span-1">

                <!-- Foto Koi -->
                <div class="rounded-xl border border-sidebar-border/70 bg-background p-6">
                    <h2 class="mb-5 text-base font-semibold">Foto Koi</h2>

                    <div v-if="images.length === 0"
                        class="flex flex-col items-center justify-center gap-2 rounded-lg bg-muted/40 py-16 text-muted-foreground">
                        <ImageOff class="h-10 w-10 opacity-40" />
                        <p class="text-sm">Belum ada foto untuk koi ini.</p>
                        <p class="text-xs opacity-70">Upload foto melalui menu Edit Data Koi</p>
                    </div>

                    <div v-else>
                        <div class="relative overflow-hidden rounded-lg bg-muted">
                            <div class="h-78 w-full">
                                <button type="button" @click="openLightbox(current)" class="block h-full w-full">
                                    <img :src="images[current].url" :alt="`Foto koi ${koi.name} ${current + 1}`"
                                        class="h-full w-full object-cover transition-opacity duration-200" />
                                </button>
                            </div>
                            <template v-if="images.length > 1">
                                <button @click="prev"
                                    class="absolute left-2 top-1/2 -translate-y-1/2 flex h-9 w-9 items-center justify-center rounded-full bg-black/50 text-white shadow hover:bg-black/70 transition-colors">
                                    <ChevronLeft class="h-5 w-5" />
                                </button>
                                <button @click="next"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 flex h-9 w-9 items-center justify-center rounded-full bg-black/50 text-white shadow hover:bg-black/70 transition-colors">
                                    <ChevronRight class="h-5 w-5" />
                                </button>
                                <div class="absolute bottom-2 right-2 rounded-full bg-black/60 px-2 py-0.5 text-xs font-medium text-white">
                                    {{ current + 1 }} / {{ images.length }}
                                </div>
                            </template>
                        </div>

                        <div v-if="images.length > 1" class="mt-3 flex items-center justify-center gap-1.5">
                            <button v-for="(_, i) in images" :key="i" @click="current = i"
                                :class="['h-2 rounded-full transition-all duration-200', i === current ? 'w-5 bg-primary' : 'w-2 bg-muted-foreground/30 hover:bg-muted-foreground/60']" />
                        </div>

                        <div v-if="images.length > 1" class="mt-3 flex gap-2 overflow-x-auto pb-1">
                            <button v-for="(img, i) in images" :key="img.id" @click="current = i"
                                :class="['flex-shrink-0 h-10 w-10 overflow-hidden rounded-md border-2 transition-all', i === current ? 'border-primary opacity-100' : 'border-transparent opacity-60 hover:opacity-90']">
                                <img :src="img.url" class="h-full w-full object-cover" />
                            </button>
                        </div>

                        <p class="mt-2 text-xs text-muted-foreground">
                            {{ images.length }} foto tersedia · klik foto untuk perbesar
                        </p>
                    </div>
                </div>

                <!-- Patungan Shipping -->
                <div v-if="koi.shipping_batch" class="rounded-xl border border-orange-300 dark:border-orange-700 bg-orange-50/60 dark:bg-orange-950/30 p-6 space-y-3">
                    <h2 class="text-base font-semibold text-orange-700 dark:text-orange-400">
                        Patungan Shipping · <span class="font-mono">{{ koi.shipping_batch }}</span>
                    </h2>
                    <p class="text-[11px] text-orange-800 dark:text-orange-300">Ikan ini satu box bersama:</p>
                    <div v-if="batchMates.length > 0" class="space-y-1.5">
                        <div v-for="mate in batchMates" :key="mate.id"
                            class="flex items-center justify-between rounded border border-orange-200 dark:border-orange-800 bg-white dark:bg-orange-950/40 px-2.5 py-2">
                            <div class="text-[11px]">
                                <span class="font-medium text-foreground">{{ mate.customer?.name ?? '—' }}</span>
                                <span class="ml-1.5 text-muted-foreground font-mono">{{ mate.code }}</span>
                            </div>
                            <span class="text-[11px] text-orange-700 dark:text-orange-300 font-medium">{{ mate.sale_qty ?? 1 }} ekor</span>
                        </div>
                    </div>
                    <p v-else class="text-[11px] text-muted-foreground italic">Belum ada ikan lain dalam batch ini.</p>
                </div>

                </div><!-- end kolom kiri -->

                <!-- Kanan: Informasi + Harga -->
                <div class="space-y-4 lg:col-span-2">
                    <!-- Informasi Transaksi -->
                    <div class="rounded-xl border border-sidebar-border/70 bg-background p-6">
                        <h2 class="mb-5 text-base font-semibold">Informasi Transaksi</h2>
                        <dl class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Invoice</dt>
                                <dd class="mt-1 font-mono font-medium text-foreground">{{ koi.sale_invoice ?? '-' }}</dd>
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
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Customer</dt>
                                <dd class="mt-1 font-medium text-foreground">{{ koi.customer?.name ?? '-' }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Sales Date</dt>
                                <dd class="mt-1 font-medium text-foreground">{{ koi.sale_tanggal ? fmtDate(koi.sale_tanggal) : '-' }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Shipping Date</dt>
                                <dd class="mt-1 font-medium text-foreground">{{ koi.shipping_date ? fmtDate(koi.shipping_date) : '-' }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Koi QTY</dt>
                                <dd class="mt-1 font-medium text-foreground">{{ koi.sale_qty ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Harga Transaksi -->
                    <div class="rounded-xl border border-sidebar-border/70 bg-background p-6">
                        <h2 class="mb-5 text-base font-semibold">Harga Transaksi</h2>
                        <dl class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                            <div class="rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50/50 dark:bg-blue-950/20 p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-400">Modal Price</dt>
                                <dd class="mt-1 font-medium text-foreground">
                                    {{ koi.stock_price != null ? '¥' + Number(koi.stock_price).toLocaleString('ja-JP') : '-' }}
                                </dd>
                                <dd v-if="stockPriceIdr" class="mt-0.5 text-xs text-blue-600 dark:text-blue-400">
                                    ≈ {{ fmtIDR(stockPriceIdr) }}
                                </dd>
                                <dd v-if="totalModalIdr" class="mt-0.5 text-xs text-blue-600 dark:text-blue-400">Total × {{ koi.sale_qty || 1 }} = {{ fmtIDR(totalModalIdr) }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Unit Price (JPY)</dt>
                                <dd class="mt-1 font-medium text-foreground">
                                    {{ koi.unit_price != null ? '¥' + Number(koi.unit_price).toLocaleString('ja-JP') : '-' }}
                                </dd>
                                <dd v-if="unitPriceIdr" class="mt-0.5 text-xs text-amber-600 dark:text-amber-400">≈ {{ fmtIDR(unitPriceIdr) }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Sales Price (JPY)</dt>
                                <dd class="mt-1 font-medium text-foreground">
                                    {{ salesPriceJpy != null ? '¥' + salesPriceJpy.toLocaleString('ja-JP') : '-' }}
                                </dd>
                                <dd v-if="salesPriceIdr" class="mt-0.5 text-xs text-green-600 dark:text-green-400">≈ {{ fmtIDR(salesPriceIdr) }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Kurs (JPY→IDR)</dt>
                                <dd class="mt-1 font-medium text-foreground">
                                    {{ koi.kurs != null ? 'Rp ' + Number(koi.kurs).toLocaleString('id-ID') + ' / ¥1' : '-' }}
                                </dd>
                            </div>
                            <!-- Biaya Shipping -->
                            <div v-if="shippingKena" class="rounded-lg border border-orange-200 dark:border-orange-800 bg-orange-50/50 dark:bg-orange-950/20 p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-orange-700 dark:text-orange-400">Biaya Shipping</dt>
                                <dd class="mt-1 font-semibold text-orange-700 dark:text-orange-400">+ {{ fmtIDR(shippingKena) }}</dd>
                                <dd class="mt-0.5 text-[11px] text-muted-foreground">
                                    {{ fmtIDR(shippingPerEkor) }}/ekor × {{ koi.sale_qty || 1 }} ekor
                                    <span v-if="koi.shipping_note"> · {{ koi.shipping_note }}</span>
                                </dd>
                            </div>

                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Fee</dt>
                                <dd class="mt-1 font-medium text-foreground">{{ fmtIDR(koi.fee) }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Total Diterima</dt>
                                <dd class="mt-1 font-semibold text-foreground">{{ fmtIDR(salesPriceIdr - Number(koi.fee ?? 0)) }}</dd>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Profit</dt>
                                <dd class="mt-1 font-semibold" :class="profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400'">
                                    {{ fmtIDR(profit) }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Identitas Koi — full width -->
                <div class="rounded-xl border border-sidebar-border/70 bg-background p-6 lg:col-span-3">
                    <h2 class="mb-5 text-base font-semibold">Identitas Koi</h2>
                    <dl class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                        <div class="rounded-lg border border-border p-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">KOI Code</dt>
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
                        <div class="rounded-lg border border-border p-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Year</dt>
                            <dd class="mt-1 font-medium text-foreground">{{ koi.age != null ? koi.age + ' tahun' : '-' }}</dd>
                        </div>
                        <div class="rounded-lg border border-border p-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Size</dt>
                            <dd class="mt-1 font-medium text-foreground">{{ koi.size != null ? koi.size + ' cm' : '-' }}</dd>
                        </div>
                        <div class="rounded-lg border border-border p-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Box QTY</dt>
                            <dd class="mt-1 font-medium text-foreground">{{ koi.box_qty ?? '-' }}</dd>
                        </div>
                        <div class="rounded-lg border border-border p-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Weight</dt>
                            <dd class="mt-1 font-medium text-foreground">{{ koi.weight != null ? parseFloat(Number(koi.weight).toFixed(3)) + ' kg' : '-' }}</dd>
                        </div>
                        <div class="rounded-lg border border-border p-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Total Weight</dt>
                            <dd class="mt-1 font-medium text-foreground">
                                {{
                                    koi.total_weight != null
                                        ? parseFloat(Number(koi.total_weight).toFixed(3)) + ' kg'
                                        : (koi.weight != null && koi.box_qty != null)
                                            ? parseFloat((Number(koi.weight) * Number(koi.box_qty)).toFixed(3)) + ' kg'
                                            : '-'
                                }}
                            </dd>
                        </div>
                    </dl>
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

        <!-- ══ Edit Modal ══ -->
        <Dialog v-model:open="showEditModal">
            <DialogContent class="sm:max-w-[500px] max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Edit Koi: {{ koi.name }}</DialogTitle>
                    <DialogDescription class="text-xs">Perbarui informasi koi.</DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitEdit" class="space-y-4">
                    <!-- Code -->
                    <div class="grid gap-1.5">
                        <Label class="text-xs font-medium">Code</Label>
                        <Input v-model="editForm.code" type="text" class="h-9 text-sm" />
                        <InputError :message="editForm.errors.code" />
                    </div>

                    <!-- Name -->
                    <div class="grid gap-1.5">
                        <Label class="text-xs font-medium">Name</Label>
                        <Input v-model="editForm.name" type="text" class="h-9 text-sm" />
                        <InputError :message="editForm.errors.name" />
                    </div>

                    <!-- Age & Size -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-1.5">
                            <Label class="text-xs font-medium">Year (Tahun)</Label>
                            <Input v-model="editForm.age" type="number" class="h-9 text-sm" />
                            <InputError :message="editForm.errors.age" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label class="text-xs font-medium">Size (cm)</Label>
                            <Input v-model="editForm.size" type="number" class="h-9 text-sm" />
                            <InputError :message="editForm.errors.size" />
                        </div>
                    </div>

                    <!-- Weight & Box Qty -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-1.5">
                            <Label class="text-xs font-medium">Weight (kg)</Label>
                            <Input v-model="editForm.weight" type="number" step="0.001" class="h-9 text-sm" />
                            <InputError :message="editForm.errors.weight" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label class="text-xs font-medium">Box Qty</Label>
                            <Input v-model="editForm.box_qty" type="number" class="h-9 text-sm" />
                            <InputError :message="editForm.errors.box_qty" />
                        </div>
                    </div>

                    <!-- Pricing -->
                    <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-950/20 p-3 space-y-3">
                        <p class="text-[11px] font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wider">Pricing</p>
                        
                        <div class="grid gap-1.5">
                            <Label class="text-xs font-medium">Stock Price (JPY)</Label>
                            <Input v-model="editForm.stock_price" type="number" class="h-9 text-sm" />
                            <InputError :message="editForm.errors.stock_price" />
                        </div>

                        <div class="grid gap-1.5">
                            <Label class="text-xs font-medium">Unit Price (JPY)</Label>
                            <Input v-model="editForm.unit_price" type="number" class="h-9 text-sm" />
                            <InputError :message="editForm.errors.unit_price" />
                        </div>

                        <div class="grid gap-1.5">
                            <Label class="text-xs font-medium">Kurs (JPY→IDR)</Label>
                            <Input v-model="editForm.kurs" type="number" class="h-9 text-sm" />
                            <InputError :message="editForm.errors.kurs" />
                        </div>

                        <div class="grid gap-1.5">
                            <Label class="text-xs font-medium">Fee (IDR)</Label>
                            <Input v-model="editForm.fee" type="number" class="h-9 text-sm" />
                            <InputError :message="editForm.errors.fee" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-border">
                        <Button type="button" variant="outline" @click="showEditModal = false">Batal</Button>
                        <Button type="submit" :disabled="editForm.processing">{{ editForm.processing ? 'Menyimpan...' : 'Simpan' }}</Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
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
