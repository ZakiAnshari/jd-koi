<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { X, ImagePlus } from 'lucide-vue-next'
import AppLayout from '@/layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import { useSwal } from '@/composables/useSwal'
import type { BreadcrumbItem } from '@/types'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'

type Breeder = { id: number; name_breeder: string }
type JenisKoi = { id: number; nama_jenis: string }
type Customer = { id: number; name: string }
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
    images: KoiImage[]
    customer_id: number | null
    sale_tanggal: string | null
    sale_qty: number | null
    buy_jpy: string | null
    kurs: string | null
    sell_idr: string | null
    unit_price: string | null
    stock_price: string | null
    fee: string | null
    sale_status: string | null
    sale_invoice: string | null
    sale_shipped_date: string | null
}

const page = usePage()
const koi       = page.props.koi as Koi
const breeders  = (page.props.breeders  as Breeder[])  || []
const jenisKois = (page.props.jenisKois as JenisKoi[]) || []
const customers = (page.props.customers as Customer[]) || []

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data', href: '#' },
    { title: 'Data Koi', href: '/master-data/koi?tab=koi' },
    { title: 'Edit', href: `/master-data/koi/${koi.id}/edit` },
]

// ─── Breeder search ───────────────────────────────────────
const breederSearch = ref('')
const filteredBreeders = computed(() => {
    if (!breederSearch.value) return breeders
    const q = breederSearch.value.toLowerCase()
    return breeders.filter(b => b.name_breeder.toLowerCase().includes(q))
})

// ─── Customer search ──────────────────────────────────────
const customerSearch = ref('')
const filteredCustomers = computed(() => {
    if (!customerSearch.value) return customers
    const q = customerSearch.value.toLowerCase()
    return customers.filter(c => c.name.toLowerCase().includes(q))
})

// ─── Jenis Koi search ─────────────────────────────────────
const jenisSearch = ref('')
const filteredJenisKois = computed(() => {
    if (!jenisSearch.value) return jenisKois
    const q = jenisSearch.value.toLowerCase()
    return jenisKois.filter(j => j.nama_jenis.toLowerCase().includes(q))
})

// ─── Images state ─────────────────────────────────────────
const existingImages = ref<KoiImage[]>(koi.images ? [...koi.images] : [])
const fotoPreviews   = ref<{ file: File; url: string }[]>([])

// ─── Form ─────────────────────────────────────────────────
const form = useForm({
    breeder_id:   String(koi.breeder_id),
    jenis_koi_id: koi.jenis_koi_id ? String(koi.jenis_koi_id) : '',
    code:         koi.code,
    name:         koi.name,
    age:          koi.age != null ? String(koi.age) : '',
    size:         koi.size != null ? String(koi.size) : '',
    box_qty:      koi.box_qty != null ? String(koi.box_qty) : '',
    weight:       koi.weight != null ? String(koi.weight) : '',
    total_weight: koi.total_weight != null ? String(koi.total_weight) : '',
    images:        [] as File[],
    delete_images: [] as number[],
    customer_id:       koi.customer_id ? String(koi.customer_id) : '',
    sale_tanggal:      koi.sale_tanggal ?? '',
    sale_qty:          koi.sale_qty != null ? String(koi.sale_qty) : '1',
    unit_price:        koi.unit_price != null ? String(Number(koi.unit_price)) : '',
    buy_jpy:           koi.buy_jpy != null ? String(Number(koi.buy_jpy)) : '',
    kurs:              koi.kurs != null ? String(Number(koi.kurs)) : '',
    sell_idr:          koi.sell_idr != null ? String(Number(koi.sell_idr)) : '',
    stock_price:       koi.stock_price != null ? String(Number(koi.stock_price)) : '',
    fee:               koi.fee != null ? String(Number(koi.fee)) : '0',
    sale_status:       koi.sale_status ?? '',
    sale_shipped_date: koi.sale_shipped_date ?? '',
})

// ─── Exchange rate ────────────────────────────────────────
const exchangeRate        = ref(0)
const exchangeRateLoading = ref(false)
const exchangeRateError   = ref(false)
const kursInput           = ref(form.kurs || '')
const kursIsManual        = ref(!!form.kurs)

async function fetchExchangeRate() {
    if (kursIsManual.value) return
    exchangeRateLoading.value = true; exchangeRateError.value = false
    try {
        const res  = await fetch('https://api.exchangerate-api.com/v4/latest/JPY')
        if (!res.ok) throw new Error()
        const data = await res.json()
        exchangeRate.value = Math.round(Number(data?.rates?.IDR ?? 0))
        if (!exchangeRate.value) throw new Error()
    } catch { exchangeRateError.value = true; exchangeRate.value = 110 }
    finally  { exchangeRateLoading.value = false }
}
fetchExchangeRate()
watch(exchangeRate, rate => { if (rate > 0 && !kursIsManual.value) kursInput.value = String(rate) })
function resetKurs() { kursIsManual.value = false; kursInput.value = String(exchangeRate.value) }

// Sync kursInput → form.kurs
watch(kursInput, v => { form.kurs = v })

// unit_price × qty × kurs → sell_idr
watch([() => form.unit_price, () => form.sale_qty, kursInput], ([price, qty, kurs]) => {
    const p = Number(price) || 0
    const q = Number(qty)   || 1
    const k = Number(kurs)  || 0
    form.sell_idr = p > 0 && k > 0 ? String(Math.round(p * q * k)) : form.sell_idr
})

// Total Modal = stock_price (IDR/unit) × qty
const totalModalIdr = computed(() => {
    const m = Number(form.stock_price) || 0
    const q = Number(form.sale_qty)    || 1
    return m > 0 ? Math.round(m * q) : null
})
const unitPriceIdr = computed(() => {
    const p = Number(form.unit_price) || 0
    const k = Number(kursInput.value) || 0
    return p > 0 && k > 0 ? Math.round(p * k) : null
})
const salesPriceJpy = computed(() => {
    const p = Number(form.unit_price) || 0
    const q = Number(form.sale_qty)   || 1
    return p > 0 ? p * q : null
})
const salesPriceIdr = computed(() => {
    const jpy = salesPriceJpy.value
    const k   = Number(kursInput.value) || 0
    return jpy && k > 0 ? Math.round(jpy * k) : null
})
const profit         = computed(() => (salesPriceIdr.value ?? 0) - (totalModalIdr.value ?? 0) - Number(form.fee || 0))
const profitPositive = computed(() => profit.value >= 0)

function fmtInput(val: string | number): string {
    const n = String(val).replace(/\D/g, '')
    return n ? Number(n).toLocaleString('id-ID') : ''
}
function parseInput(e: Event): string {
    return (e.target as HTMLInputElement).value.replace(/\D/g, '')
}

const fmtIDR = (v: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v)

function handleImagesChange(e: Event) {
    const input = e.target as HTMLInputElement
    if (!input.files?.length) return
    const newFiles = Array.from(input.files)
    form.images = [...form.images, ...newFiles]
    newFiles.forEach(file => {
        const reader = new FileReader()
        reader.onload = ev => {
            fotoPreviews.value.push({ file, url: ev.target?.result as string })
        }
        reader.readAsDataURL(file)
    })
    input.value = ''
}

function removeNewImage(index: number) {
    fotoPreviews.value.splice(index, 1)
    form.images = form.images.filter((_, i) => i !== index)
}

function markDeleteExisting(id: number) {
    form.delete_images = [...form.delete_images, id]
    existingImages.value = existingImages.value.filter(img => img.id !== id)
}

const submit = () => {
    const swal = useSwal()
    form.post(`/master-data/koi/${koi.id}?_method=PUT`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data koi berhasil diperbarui.',
                timer: 3000,
                showConfirmButton: false,
            })
            fotoPreviews.value = []
            form.images = []
            form.delete_images = []
        },
        onError: () => {
            swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: 'Terjadi kesalahan saat memperbarui data.',
                timer: 3000,
                showConfirmButton: false,
            })
        },
    })
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Edit Koi" />

        <div class="space-y-6 p-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Edit Koi</h1>
                    <p class="text-sm text-muted-foreground">Perbarui data dan foto koi.</p>
                </div>
                <Link
                    href="/master-data/koi"
                    class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted"
                >
                    Kembali
                </Link>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                <form @submit.prevent="submit" class="space-y-6">

                    <!-- Data Identitas -->
                    <div class="space-y-4">
                        <h3 class="font-semibold text-sm uppercase tracking-wide text-muted-foreground">Data Identitas</h3>

                        <!-- Breeder -->
                        <div class="grid gap-2">
                            <Label>Breeder *</Label>
                            <Select v-model="form.breeder_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih breeder" />
                                </SelectTrigger>
                                <SelectContent class="w-[--radix-select-trigger-width] min-w-[20rem] p-0">
                                    <div class="sticky top-0 border-b bg-background p-2">
                                        <Input v-model="breederSearch" placeholder="Cari breeder..." class="h-8" @keydown.stop />
                                    </div>
                                    <div class="max-h-48 overflow-y-auto">
                                        <div v-if="filteredBreeders.length === 0" class="px-2 py-2 text-sm text-muted-foreground">Breeder tidak ditemukan</div>
                                        <SelectItem v-for="b in filteredBreeders" :key="b.id" :value="String(b.id)">
                                            {{ b.name_breeder }}
                                        </SelectItem>
                                    </div>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.breeder_id" />
                        </div>

                        <!-- Jenis Koi -->
                        <div class="grid gap-2">
                            <Label>Jenis Koi</Label>
                            <Select v-model="form.jenis_koi_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih jenis koi (opsional)" />
                                </SelectTrigger>
                                <SelectContent class="w-[--radix-select-trigger-width] min-w-[20rem] p-0">
                                    <div class="sticky top-0 border-b bg-background p-2">
                                        <Input v-model="jenisSearch" placeholder="Cari jenis koi..." class="h-8" @keydown.stop />
                                    </div>
                                    <div class="max-h-48 overflow-y-auto">
                                        <div v-if="filteredJenisKois.length === 0" class="px-2 py-2 text-sm text-muted-foreground">Jenis koi tidak ditemukan</div>
                                        <SelectItem v-for="j in filteredJenisKois" :key="j.id" :value="String(j.id)">
                                            {{ j.nama_jenis }}
                                        </SelectItem>
                                    </div>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.jenis_koi_id" />
                        </div>

                        <!-- Code & Name -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="code">Kode Koi *</Label>
                                <Input id="code" v-model="form.code" placeholder="Contoh: KOI-001" />
                                <InputError :message="form.errors.code" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="name">Nama Koi *</Label>
                                <Input id="name" v-model="form.name" placeholder="Nama koi" />
                                <InputError :message="form.errors.name" />
                            </div>
                        </div>
                    </div>

                    <!-- Data Fisik -->
                    <div class="rounded-lg border border-border bg-muted/30 p-4 space-y-3">
                        <h3 class="font-semibold text-sm uppercase tracking-wide text-muted-foreground">Data Fisik</h3>
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div class="grid gap-2">
                                <Label for="age">Age (bulan)</Label>
                                <Input id="age" v-model="form.age" type="number" min="0" placeholder="0" />
                                <InputError :message="form.errors.age" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="size">Size (cm)</Label>
                                <Input id="size" v-model="form.size" type="number" min="0" step="0.01" placeholder="0.00" />
                                <InputError :message="form.errors.size" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="box_qty">Box QTY</Label>
                                <Input id="box_qty" v-model="form.box_qty" type="number" min="0" placeholder="0" />
                                <InputError :message="form.errors.box_qty" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="weight">Weight (kg)</Label>
                                <Input id="weight" v-model="form.weight" type="number" min="0" step="0.001" placeholder="0.000" />
                                <InputError :message="form.errors.weight" />
                            </div>
                        </div>
                    </div>

                    <!-- Foto Koi -->
                    <div class="rounded-lg border border-sidebar-border/70 bg-muted/30 p-4">
                        <h3 class="mb-4 font-semibold">Foto Koi</h3>

                        <div v-if="existingImages.length > 0" class="mb-4">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Foto Tersimpan</p>
                            <div class="flex flex-wrap gap-3">
                                <div v-for="img in existingImages" :key="img.id"
                                    class="relative h-24 w-24 overflow-hidden rounded-lg border border-border group">
                                    <img :src="img.url" alt="Foto koi" class="h-full w-full object-cover" />
                                    <button type="button" @click="markDeleteExisting(img.id)"
                                        class="absolute inset-0 flex items-center justify-center bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity"
                                        title="Hapus foto">
                                        <X class="h-5 w-5 text-white" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="existingImages.length === 0 && fotoPreviews.length === 0"
                            class="mb-3 text-xs italic text-muted-foreground">
                            Belum ada foto tersimpan.
                        </div>

                        <div v-if="fotoPreviews.length > 0" class="mb-4">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Foto Baru</p>
                            <div class="flex flex-wrap gap-3">
                                <div v-for="(preview, i) in fotoPreviews" :key="i"
                                    class="relative h-24 w-24 overflow-hidden rounded-lg border border-border group">
                                    <img :src="preview.url" :alt="`Preview ${i + 1}`" class="h-full w-full object-cover" />
                                    <button type="button" @click="removeNewImage(i)"
                                        class="absolute inset-0 flex items-center justify-center bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity"
                                        title="Batalkan">
                                        <X class="h-5 w-5 text-white" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border-2 border-dashed border-border px-4 py-3 text-sm text-muted-foreground transition-colors hover:border-primary/50 hover:text-foreground">
                            <ImagePlus class="h-5 w-5 flex-shrink-0" />
                            <span>Pilih foto (jpg, jpeg, png, webp · maks. 2 MB per file · bisa pilih beberapa)</span>
                            <input type="file" accept="image/jpg,image/jpeg,image/png,image/webp"
                                multiple class="sr-only" @change="handleImagesChange" />
                        </label>
                        <InputError :message="(form.errors as any)['images.*']" class="mt-1" />
                    </div>

                    <!-- Penjualan -->
                    <div class="rounded-lg border border-border p-4 space-y-4">
                        <h3 class="font-semibold text-sm uppercase tracking-wide text-muted-foreground">Penjualan</h3>

                        <!-- Invoice (read-only) -->
                        <div class="grid gap-2" v-if="koi.sale_invoice">
                            <Label>Invoice</Label>
                            <div class="flex items-center h-9 rounded-md border border-input bg-muted px-3 text-sm font-mono text-muted-foreground">
                                {{ koi.sale_invoice }}
                            </div>
                        </div>

                        <!-- Customer -->
                        <div class="grid gap-2">
                            <Label>Customer</Label>
                            <Select v-model="form.customer_id">
                                <SelectTrigger><SelectValue placeholder="Pilih customer (opsional)" /></SelectTrigger>
                                <SelectContent class="w-[--radix-select-trigger-width] min-w-[20rem] p-0">
                                    <div class="sticky top-0 border-b bg-background p-2">
                                        <Input v-model="customerSearch" placeholder="Cari customer..." class="h-8" @keydown.stop />
                                    </div>
                                    <div class="max-h-48 overflow-y-auto">
                                        <div v-if="filteredCustomers.length === 0" class="px-2 py-2 text-sm text-muted-foreground">Tidak ditemukan</div>
                                        <SelectItem v-for="c in filteredCustomers" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                                    </div>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.customer_id" />
                        </div>

                        <!-- Status -->
                        <div class="grid gap-2">
                            <Label>Status</Label>
                            <Select v-model="form.sale_status">
                                <SelectTrigger><SelectValue placeholder="Pilih status" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="cancel">Cancel</SelectItem>
                                    <SelectItem value="dead_refund">Dead Refund</SelectItem>
                                    <SelectItem value="refund">Refund</SelectItem>
                                    <SelectItem value="cancel_refund">Cancel Refund</SelectItem>
                                    <SelectItem value="pending">Pending</SelectItem>
                                    <SelectItem value="shipped">Shipped</SelectItem>
                                    <SelectItem value="azukari">Azukari</SelectItem>
                                    <SelectItem value="dead">Dead</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.sale_status" />
                        </div>

                        <!-- Tanggal & Qty -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-2">
                                <Label>Tanggal Penjualan</Label>
                                <Input v-model="form.sale_tanggal" type="date" />
                                <InputError :message="form.errors.sale_tanggal" />
                            </div>
                            <div class="grid gap-2">
                                <Label>Tanggal Shipped</Label>
                                <Input v-model="form.sale_shipped_date" type="date" />
                                <InputError :message="(form.errors as any).sale_shipped_date" />
                            </div>
                            <div class="grid gap-2">
                                <Label>Qty (ekor)</Label>
                                <Input v-model="form.sale_qty" type="number" min="1" placeholder="1" />
                                <InputError :message="form.errors.sale_qty" />
                            </div>
                        </div>

                        <!-- Kalkulasi harga -->
                        <div class="rounded-lg border border-border bg-muted/30 p-4 space-y-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Kalkulasi Harga</p>

                            <!-- Kurs -->
                            <div class="rounded-lg border-2 border-red-400 dark:border-red-600 bg-red-50 dark:bg-red-950/30 p-3 space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <Label class="text-xs font-semibold text-red-700 dark:text-red-400">Kurs JPY → IDR</Label>
                                    <div class="flex items-center gap-1.5">
                                        <span v-if="exchangeRateLoading" class="text-[10px] text-red-400 animate-pulse">Memuat kurs...</span>
                                        <template v-else>
                                            <span v-if="kursIsManual"
                                                class="inline-flex items-center gap-1 rounded-full bg-orange-100 dark:bg-orange-900/50 px-2 py-0.5 text-[10px] font-medium text-orange-600 dark:text-orange-400">
                                                ✎ Manual
                                            </span>
                                            <span v-else-if="exchangeRateError"
                                                class="inline-flex items-center gap-1 rounded-full bg-yellow-100 dark:bg-yellow-900/50 px-2 py-0.5 text-[10px] font-medium text-yellow-700 dark:text-yellow-400">
                                                ⚠ Fallback
                                            </span>
                                            <span v-else
                                                class="inline-flex items-center gap-1 rounded-full bg-green-100 dark:bg-green-900/50 px-2 py-0.5 text-[10px] font-medium text-green-700 dark:text-green-400">
                                                ✓ Live API
                                            </span>
                                            <button v-if="kursIsManual" type="button" @click="resetKurs"
                                                class="text-[10px] underline text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition-colors">
                                                Gunakan API
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-red-600 dark:text-red-400">Rp</span>
                                    <input
                                        type="text" inputmode="numeric"
                                        :value="fmtInput(kursInput.value)"
                                        @input="e => { kursIsManual = true; kursInput.value = parseInput(e) }"
                                        placeholder="0"
                                        class="flex h-9 w-full rounded-md border-2 border-red-400 dark:border-red-600 bg-white dark:bg-red-900/20 pl-9 pr-14 text-sm font-semibold text-red-800 dark:text-red-100 placeholder:text-red-300 dark:placeholder:text-red-700 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-200 dark:focus:ring-red-800/50 transition-colors"
                                    />
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-red-500 dark:text-red-400">/ ¥1</span>
                                </div>
                                <p class="text-[11px] text-red-600/80 dark:text-red-400/80">
                                    Otomatis dari API · langsung ketik untuk ubah manual
                                </p>
                            </div>

                            <!-- Row 1: Stock JPY & Sales Price JPY -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1.5">
                                    <Label class="text-xs font-medium text-muted-foreground">Stock JPY</Label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-500">¥</span>
                                        <Input
                                            type="text" inputmode="numeric"
                                            :value="fmtInput(form.buy_jpy)"
                                            @input="e => { form.buy_jpy = parseInput(e) }"
                                            placeholder="0"
                                            class="h-9 text-sm pl-7"
                                        />
                                    </div>
                                    <InputError :message="(form.errors as any).buy_jpy" />
                                </div>
                                <div class="space-y-1.5">
                                    <Label class="text-xs font-medium text-muted-foreground">Sales Price (JPY)</Label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-amber-500">¥</span>
                                        <Input
                                            type="text" inputmode="numeric"
                                            :value="fmtInput(form.unit_price)"
                                            @input="e => { form.unit_price = parseInput(e) }"
                                            placeholder="0"
                                            class="h-9 text-sm pl-7"
                                        />
                                    </div>
                                    <div v-if="unitPriceIdr" class="rounded border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/30 px-2.5 py-1 text-[11px] flex justify-between">
                                        <span class="text-muted-foreground">≈ IDR/unit</span>
                                        <span class="font-semibold text-amber-700 dark:text-amber-400">{{ fmtIDR(unitPriceIdr) }}</span>
                                    </div>
                                    <InputError :message="form.errors.sell_idr" />
                                </div>
                            </div>

                            <!-- Row 2: Modal Price IDR & Total Sales IDR -->
                            <div class="grid grid-cols-2 gap-3">
                                <!-- Modal Price IDR (input langsung) -->
                                <div class="space-y-1.5">
                                    <Label class="text-xs font-semibold text-blue-700 dark:text-blue-400">Modal Price (IDR/ekor)</Label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-blue-600 dark:text-blue-400">Rp</span>
                                        <Input
                                            type="text" inputmode="numeric"
                                            :value="fmtInput(form.stock_price)"
                                            @input="e => { form.stock_price = parseInput(e) }"
                                            placeholder="0"
                                            class="h-9 text-sm pl-9 border-blue-300 dark:border-blue-700 focus:border-blue-500"
                                        />
                                    </div>
                                    <div v-if="totalModalIdr" class="rounded border border-blue-300 dark:border-blue-700 bg-blue-100 dark:bg-blue-900/40 px-2.5 py-1.5 text-[11px]">
                                        <div class="flex justify-between items-center">
                                            <span class="text-blue-700 dark:text-blue-300 font-medium">Total Modal × {{ form.sale_qty || 1 }}</span>
                                            <span class="font-bold text-blue-800 dark:text-blue-200">{{ fmtIDR(totalModalIdr) }}</span>
                                        </div>
                                    </div>
                                    <InputError :message="(form.errors as any).stock_price" />
                                </div>
                                <!-- Total Sales IDR (auto) -->
                                <div class="space-y-1.5">
                                    <Label class="text-xs font-semibold text-green-700 dark:text-green-400">Total Sales (IDR) <span class="font-normal opacity-60">auto</span></Label>
                                    <div class="flex h-9 items-center rounded-md border border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-950/30 px-3 text-sm font-semibold text-green-800 dark:text-green-200">
                                        {{ salesPriceIdr ? fmtIDR(salesPriceIdr) : '-' }}
                                    </div>
                                    <p class="text-[10px] text-muted-foreground">Sales JPY × {{ form.sale_qty || 1 }} ekor × kurs</p>
                                </div>
                            </div>

                            <!-- Fee & Profit -->
                            <div class="grid grid-cols-2 gap-3 pt-1">
                                <div class="grid gap-1.5">
                                    <Label class="text-xs font-medium text-muted-foreground">Fee (IDR)</Label>
                                    <Input
                                        type="text" inputmode="numeric"
                                        :value="fmtInput(form.fee)"
                                        @input="e => { form.fee = parseInput(e) }"
                                        placeholder="0"
                                        class="h-9 text-sm"
                                    />
                                    <InputError :message="form.errors.fee" />
                                </div>
                                <div class="grid gap-1.5">
                                    <Label class="text-xs font-medium text-muted-foreground">Profit</Label>
                                    <div class="flex h-9 items-center rounded-md border px-3 text-sm font-semibold"
                                        :class="profitPositive ? 'border-green-300 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-950 dark:text-green-400' : 'border-red-300 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-400'">
                                        {{ fmtIDR(profit) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                        </Button>
                        <Link href="/master-data/koi" class="text-sm text-muted-foreground hover:text-foreground">
                            Batal
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
