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
    shipping_cost: string | null
    shipping_qty: string | null
    shipping_batch: string | null
    shipping_note: string | null
    shipping_date: string | null
    azukari_period: string | null
}

const page = usePage()
const koi            = page.props.koi as Koi
const breeders       = (page.props.breeders       as Breeder[])  || []
const jenisKois      = (page.props.jenisKois      as JenisKoi[]) || []
const customers      = (page.props.customers      as Customer[]) || []
const shippingBatches      = (page.props.shippingBatches as string[]) || []
const shippingBatchDetails = (page.props.shippingBatchDetails as Record<string, { shipping_cost: number | null; shipping_qty: number | null; koi_count: number; members: { code: string; customer: string | null; sale_qty: number | null }[] }>) || {}
const extraBatches         = ref<string[]>([])
const allBatchOptions      = computed(() => [...shippingBatches, ...extraBatches.value.filter(b => !shippingBatches.includes(b))])

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
    sale_invoice:      koi.sale_invoice ?? '',
    sale_shipped_date: koi.sale_shipped_date ?? '',
    shipping_cost:     koi.shipping_cost != null ? String(Number(koi.shipping_cost)) : '',
    shipping_qty:      koi.shipping_qty != null ? String(Number(koi.shipping_qty)) : '',
    shipping_batch:    koi.shipping_batch ?? '',
    shipping_note:     koi.shipping_note ?? '',
    shipping_date:     koi.shipping_date ?? '',
    azukari_period:    koi.azukari_period ?? '',
})

// ─── Exchange rate ────────────────────────────────────────
const exchangeRate        = ref(0)
const exchangeRateLoading = ref(false)
const exchangeRateError   = ref(false)
const kursIsManual        = ref(!!form.kurs)

const kursInput = ref(form.kurs || '')
watch(kursInput, v => { form.kurs = v })

const modalQty = ref(koi.sale_qty != null ? String(koi.sale_qty) : '')

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
watch(exchangeRate, rate => {
    if (rate > 0 && !kursIsManual.value) {
        kursInput.value = String(rate)
    }
})
function resetKurs() {
    kursIsManual.value = false
    kursInput.value    = String(exchangeRate.value)
}
function onKursInput(e: Event) { kursIsManual.value = true; kursInput.value = parseInput(e) }

// unit_price × qty × kurs → sell_idr
watch([() => form.unit_price, () => form.sale_qty, kursInput], ([price, qty, kurs]) => {
    const p = Number(price) || 0
    const q = Number(qty)   || 1
    const k = Number(kurs)  || 0
    form.sell_idr = p > 0 && k > 0 ? String(Math.round(p * q * k)) : ''
})

// Modal Price IDR per ekor = stock_price (JPY) × kurs
const modalPriceIdr = computed(() => {
    const m = Number(form.stock_price) || 0
    const k = Number(kursInput.value)  || 0
    return m > 0 && k > 0 ? Math.round(m * k) : null
})
// Total Modal IDR = modal price IDR × modalQty (stok disiapkan)
const totalModalIdr = computed(() => {
    const m = modalPriceIdr.value    || 0
    const q = Number(modalQty.value) || 0
    return m > 0 && q > 0 ? Math.round(m * q) : null
})
// Modal IDR untuk qty yang terjual (dipakai di profit)
const modalTerjualIdr = computed(() => {
    const m = modalPriceIdr.value   || 0
    const q = Number(form.sale_qty) || 1
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
const shippingCostIdr = computed(() => {
    const c = Number(form.shipping_cost) || 0
    return c > 0 ? c : null
})
const shippingPerEkor = computed(() => {
    const costIdr = shippingCostIdr.value     || 0
    const qty     = Number(form.shipping_qty) || 0
    return costIdr > 0 && qty > 0 ? Math.round(costIdr / qty) : null
})
const shippingKena = computed(() => {
    const perEkor = shippingPerEkor.value  || 0
    const saleQty = Number(form.sale_qty) || 1
    return perEkor > 0 ? Math.round(perEkor * saleQty) : null
})
const profit         = computed(() => (salesPriceIdr.value ?? 0) - (modalTerjualIdr.value ?? 0) + Number(form.fee || 0))
const profitPositive = computed(() => profit.value >= 0)

// Shipping batch
function generateBatchCode() {
    const d = new Date()
    const ymd = d.getFullYear().toString()
        + String(d.getMonth() + 1).padStart(2, '0')
        + String(d.getDate()).padStart(2, '0')
    const allBatches = [...shippingBatches, ...extraBatches.value]
    const existing = allBatches.filter(b => b.startsWith(`SHP-${ymd}-`))
    const nextNum = existing.length + 1
    const newCode = `SHP-${ymd}-${String(nextNum).padStart(2, '0')}`
    if (!allBatches.includes(newCode)) extraBatches.value.push(newCode)
    form.shipping_batch = newCode
}

// Auto-fill shipping_cost & shipping_qty saat pilih batch yang sudah ada
watch(() => form.shipping_batch, (batch) => {
    if (!batch) return
    const detail = shippingBatchDetails[batch]
    if (!detail) return
    const cost = detail.shipping_cost != null ? String(detail.shipping_cost) : ''
    const qty  = detail.shipping_qty  != null ? String(detail.shipping_qty)  : ''
    form.shipping_cost = cost
    form.shipping_qty  = qty
    shippingCostDisplay.value = addDots(cost)
})

// Fee select
const feePct = ref<string | null>(
    form.fee && form.sell_idr && Number(form.sell_idr) > 0
        ? String(Math.round(Number(form.fee) / Number(form.sell_idr) * 100))
        : null
)
function onFeeSelect(val: unknown) {
    feePct.value = val !== null ? String(val) : null
    form.fee = String(Math.round((salesPriceIdr.value ?? 0) * (Number(val) / 100)))
}

function fmtInput(val: string | number): string {
    const n = String(val).replace(/\D/g, '')
    return n ? Number(n).toLocaleString('id-ID') : ''
}
function parseInput(e: Event): string {
    return (e.target as HTMLInputElement).value.replace(/\D/g, '')
}
function addDots(val: string | number | null) {
    const n = Math.round(Number(val))
    return n > 0 ? n.toLocaleString('id-ID') : ''
}

// Display refs — format hanya saat blur
const stockPriceDisplay   = ref(addDots(koi.stock_price))
const shippingCostDisplay = ref(addDots(koi.shipping_cost))
const unitPriceDisplay    = ref(addDots(koi.unit_price))

// Input: hanya update display (tidak trigger computed)
// Blur: baru update form → computed hanya recompute sekali saat pindah field
function onStockPriceChange(val: string | number) { const r = String(val).replace(/\D/g, ''); form.stock_price = r; stockPriceDisplay.value = addDots(r) }
function onStockPriceBlur()            { stockPriceDisplay.value = addDots(form.stock_price) }
function onShippingCostChange(val: string | number) { const r = String(val).replace(/\D/g, ''); form.shipping_cost = r; shippingCostDisplay.value = addDots(r) }
function onShippingCostBlur()          { shippingCostDisplay.value = addDots(form.shipping_cost) }
function onUnitPriceChange(val: string | number)   { const r = String(val).replace(/\D/g, ''); form.unit_price = r; unitPriceDisplay.value = addDots(r) }
function onUnitPriceBlur()             { unitPriceDisplay.value = addDots(form.unit_price) }
const kursDisplay = ref(addDots(kursInput.value))
watch(kursInput, v => { kursDisplay.value = addDots(v) })

function onKursInputHandler(e: Event) {
    kursIsManual.value = true
    kursDisplay.value  = parseInput(e)   // hanya display, kursInput belum berubah
}
function onKursBlur() {
    kursInput.value   = kursDisplay.value  // baru update kursInput → computed recompute
    kursDisplay.value = addDots(kursDisplay.value)
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

        <div class="space-y-6 p-6 mx-auto max-w-6xl">
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

            <form @submit.prevent="submit" class="space-y-6">

                <!-- ═══════════════════════════════════════════
                     SECTION 1: DATA KOI
                ═══════════════════════════════════════════════ -->
                <div class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                    <h2 class="mb-5 text-base font-semibold">Data Koi</h2>

                    <div class="space-y-4">
                        <!-- Breeder & Jenis Koi -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <!-- Breeder -->
                            <div class="grid gap-2">
                                <Label>Breeder <span class="text-red-500">*</span></Label>
                                <Select v-model="form.breeder_id">
                                    <SelectTrigger class="w-full">
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
                                <Label>Jenis Koi <span class="text-xs font-normal text-muted-foreground">(opsional)</span></Label>
                                <Select v-model="form.jenis_koi_id">
                                    <SelectTrigger class="w-full">
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
                        </div>

                        <!-- Code & Name -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="code">Kode Koi <span class="text-red-500">*</span></Label>
                                <Input id="code" v-model="form.code" placeholder="Contoh: KOI-001" />
                                <InputError :message="form.errors.code" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="name">Nama Koi <span class="text-red-500">*</span></Label>
                                <Input id="name" v-model="form.name" placeholder="Nama koi" />
                                <InputError :message="form.errors.name" />
                            </div>
                        </div>

                        <!-- Data Fisik -->
                        <div class="rounded-lg border border-border bg-muted/30 p-4 space-y-3">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Data Fisik</h3>
                            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                                <div class="grid gap-2">
                                    <Label for="age" class="text-xs">Age (bulan)</Label>
                                    <Input id="age" v-model="form.age" type="number" min="0" placeholder="0" />
                                    <InputError :message="form.errors.age" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="size" class="text-xs">Size (cm)</Label>
                                    <Input id="size" v-model="form.size" type="number" min="0" step="0.01" placeholder="0.00" />
                                    <InputError :message="form.errors.size" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="box_qty" class="text-xs">Box QTY</Label>
                                    <Input id="box_qty" v-model="form.box_qty" type="number" min="0" placeholder="0" />
                                    <InputError :message="form.errors.box_qty" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="weight" class="text-xs">Weight (kg)</Label>
                                    <Input id="weight" v-model="form.weight" type="number" min="0" step="0.001" placeholder="0.000" />
                                    <InputError :message="form.errors.weight" />
                                </div>
                            </div>
                        </div>

                        <!-- Foto Koi -->
                        <div class="rounded-lg border border-sidebar-border/70 bg-muted/30 p-4">
                            <h3 class="mb-3 font-semibold">Foto Koi</h3>

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

                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border-2 border-dashed border-border px-4 py-3 text-sm text-muted-foreground hover:border-primary/50 hover:text-foreground transition-colors">
                                <ImagePlus class="h-5 w-5 flex-shrink-0" />
                                <span>Pilih foto (jpg, jpeg, png, webp · maks. 2 MB per file · bisa pilih beberapa)</span>
                                <input type="file" accept="image/jpg,image/jpeg,image/png,image/webp"
                                    multiple class="sr-only" @change="handleImagesChange" />
                            </label>
                            <InputError :message="(form.errors as any)['images.*']" class="mt-1" />
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════
                     SECTION 2: PENJUALAN
                ═══════════════════════════════════════════════ -->
                <div class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                    <h2 class="mb-5 text-base font-semibold">Penjualan</h2>

                    <div class="space-y-5">

                        <!-- ══ Kurs JPY → IDR ══ -->
                        <div class="rounded-lg border-2 border-red-400 dark:border-red-600 bg-red-50 dark:bg-red-950/30 p-3 space-y-1.5">
                            <div class="flex items-center justify-between">
                                <Label class="text-xs font-semibold text-red-700 dark:text-red-400">Kurs JPY → IDR</Label>
                                <div class="flex items-center gap-1.5">
                                    <span v-if="exchangeRateLoading" class="text-[10px] text-red-400 animate-pulse">Memuat kurs...</span>
                                    <template v-else>
                                        <span v-if="kursIsManual" class="inline-flex items-center gap-1 rounded-full bg-orange-100 dark:bg-orange-900/50 px-2 py-0.5 text-[10px] font-medium text-orange-600 dark:text-orange-400">✎ Manual</span>
                                        <span v-else-if="exchangeRateError" class="inline-flex items-center gap-1 rounded-full bg-yellow-100 dark:bg-yellow-900/50 px-2 py-0.5 text-[10px] font-medium text-yellow-700 dark:text-yellow-400">⚠ Fallback</span>
                                        <span v-else class="inline-flex items-center gap-1 rounded-full bg-green-100 dark:bg-green-900/50 px-2 py-0.5 text-[10px] font-medium text-green-700 dark:text-green-400">✓ Live API</span>
                                        <button v-if="kursIsManual" type="button" @click="resetKurs" class="text-[10px] underline text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition-colors">Gunakan API</button>
                                    </template>
                                </div>
                            </div>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-red-600 dark:text-red-400">Rp</span>
                                <input type="text" inputmode="numeric"
                                    v-model="kursDisplay"
                                    @input="onKursInputHandler"
                                    @blur="onKursBlur"
                                    placeholder="0"
                                    class="flex h-9 w-full rounded-md border-2 border-red-400 dark:border-red-600 bg-white dark:bg-red-900/20 pl-9 pr-14 text-sm font-semibold text-red-800 dark:text-red-100 placeholder:text-red-300 dark:placeholder:text-red-700 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-200 dark:focus:ring-red-800/50 transition-colors" />
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-red-500 dark:text-red-400">/ ¥1</span>
                            </div>
                            <p class="text-[11px] text-red-600/80 dark:text-red-400/80">Otomatis dari API · langsung ketik untuk ubah manual</p>
                        </div>

                        <div class="border-t border-border"></div>

                        <!-- ══ SECTION: Modal ══ -->
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="h-4 w-1 rounded-full bg-blue-500"></div>
                                <p class="text-sm font-semibold">Modal</p>
                            </div>
                            <div class="pl-3">
                                <div class="rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50/50 dark:bg-blue-950/20 p-3 space-y-3">
                                    <div class="grid grid-cols-2 gap-3">
                                        <!-- Modal Price JPY/ekor -->
                                        <div class="space-y-1.5">
                                            <Label class="text-xs font-semibold text-blue-700 dark:text-blue-400">Modal Price (JPY/ekor)</Label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-blue-500">¥</span>
                                                <Input type="text" inputmode="numeric" :modelValue="stockPriceDisplay" @update:modelValue="onStockPriceChange" @blur="onStockPriceBlur" placeholder="0" class="h-9 text-sm pl-7 border-blue-300 dark:border-blue-700 focus:border-blue-500" />
                                            </div>
                                            <div v-if="modalPriceIdr" class="rounded border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/30 px-2.5 py-1 text-[11px] flex justify-between">
                                                <span class="text-muted-foreground">≈ IDR/ekor</span>
                                                <span class="font-semibold text-blue-700 dark:text-blue-400">{{ fmtIDR(modalPriceIdr) }}</span>
                                            </div>
                                            <InputError :message="(form.errors as any).stock_price" />
                                        </div>
                                        <!-- Qty Modal -->
                                        <div class="space-y-1.5">
                                            <Label class="text-xs font-semibold text-blue-700 dark:text-blue-400">Qty Modal (ekor)</Label>
                                            <Input type="number" min="1" v-model="modalQty" placeholder="0" class="h-9 text-sm border-blue-300 dark:border-blue-700" />
                                            <p class="text-[10px] text-muted-foreground">Stok yang disiapkan</p>
                                        </div>
                                    </div>
                                    <div v-if="totalModalIdr" class="rounded border border-blue-300 dark:border-blue-700 bg-blue-100 dark:bg-blue-900/40 px-3 py-2 text-[11px] flex justify-between items-center">
                                        <span class="text-blue-700 dark:text-blue-300 font-medium">Total Modal ({{ modalQty || 1 }} ekor)</span>
                                        <span class="font-bold text-blue-800 dark:text-blue-200">{{ fmtIDR(totalModalIdr) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-border"></div>

                        <!-- ══ SECTION: Shipping ══ -->
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="h-4 w-1 rounded-full bg-orange-500"></div>
                                <p class="text-sm font-semibold">Shipping</p>
                                <span class="text-xs text-muted-foreground">(opsional)</span>
                            </div>
                            <div class="space-y-3 pl-3">
                                <div class="rounded-lg border border-orange-200 dark:border-orange-800 bg-orange-50/50 dark:bg-orange-950/20 p-3 space-y-3">
                                    <!-- Batch selector — selalu di atas -->
                                    <div class="space-y-1.5">
                                        <Label class="text-xs font-medium text-orange-700 dark:text-orange-400">Shipping Batch <span class="font-normal opacity-60">(untuk ikan patungan 1 box)</span></Label>
                                        <div class="flex gap-2">
                                            <select v-model="form.shipping_batch" class="flex-1 h-9 rounded-md border border-orange-300 dark:border-orange-700 bg-background px-3 text-sm">
                                                <option value="">— Tanpa batch / isi manual —</option>
                                                <option v-for="b in allBatchOptions" :key="b" :value="b"
                                                    :disabled="!!shippingBatchDetails[b] && shippingBatchDetails[b].shipping_qty != null && shippingBatchDetails[b].koi_count >= shippingBatchDetails[b].shipping_qty!">
                                                    {{ b }}{{ shippingBatchDetails[b]?.shipping_qty != null ? ` (${shippingBatchDetails[b].koi_count}/${shippingBatchDetails[b].shipping_qty}${shippingBatchDetails[b].koi_count >= shippingBatchDetails[b].shipping_qty! ? ' Full' : ''})` : '' }}
                                                </option>
                                            </select>
                                            <button type="button" @click="generateBatchCode" :disabled="!!form.shipping_batch"
                                                class="h-9 px-3 rounded-md border border-orange-300 dark:border-orange-700 bg-orange-50 dark:bg-orange-950 text-xs text-orange-700 dark:text-orange-300 hover:bg-orange-100 whitespace-nowrap disabled:opacity-40 disabled:cursor-not-allowed">
                                                + Buat Baru
                                            </button>
                                        </div>
                                        <InputError :message="(form.errors as any).shipping_batch" />
                                    </div>

                                    <!-- Jika batch lama dipilih: tampilkan info, sembunyikan input -->
                                    <template v-if="form.shipping_batch && shippingBatchDetails[form.shipping_batch]">
                                        <div class="rounded border border-orange-300 dark:border-orange-700 bg-white dark:bg-orange-950/40 p-2.5 space-y-1.5">
                                            <p class="text-[10px] font-semibold uppercase tracking-wide text-orange-600 dark:text-orange-400">Info Patungan Shipping</p>
                                            <div class="flex justify-between text-[11px]">
                                                <span class="text-muted-foreground">Total biaya box</span>
                                                <span class="font-semibold">{{ shippingBatchDetails[form.shipping_batch].shipping_cost != null ? 'Rp ' + shippingBatchDetails[form.shipping_batch].shipping_cost!.toLocaleString('id-ID') : '—' }}</span>
                                            </div>
                                            <div class="flex justify-between text-[11px]">
                                                <span class="text-muted-foreground">Total ikan sepaket</span>
                                                <span class="font-semibold">{{ shippingBatchDetails[form.shipping_batch].shipping_qty ?? '—' }} ekor</span>
                                            </div>
                                            <div class="flex justify-between text-[11px]">
                                                <span class="text-muted-foreground">Sudah ada</span>
                                                <span class="font-semibold">{{ shippingBatchDetails[form.shipping_batch].koi_count }} ekor dari customer lain</span>
                                            </div>
                                            <div v-if="shippingBatchDetails[form.shipping_batch].members.length" class="border-t border-orange-200 dark:border-orange-800 pt-1.5 space-y-1">
                                                <div v-for="m in shippingBatchDetails[form.shipping_batch].members" :key="m.code" class="flex justify-between text-[11px]">
                                                    <span class="text-muted-foreground">{{ m.customer ?? '—' }} <span class="font-mono">{{ m.code }}</span></span>
                                                    <span>{{ m.sale_qty ?? 1 }} ekor</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-if="shippingPerEkor" class="rounded border border-orange-300 dark:border-orange-700 bg-orange-100 dark:bg-orange-900/40 px-3 py-2 text-[11px] space-y-1">
                                            <div class="flex justify-between items-center">
                                                <span class="text-orange-700 dark:text-orange-300 font-medium">Shipping/ekor</span>
                                                <span class="font-semibold text-orange-800 dark:text-orange-200">{{ fmtIDR(shippingPerEkor) }}</span>
                                            </div>
                                            <div v-if="shippingKena" class="flex justify-between items-center border-t border-orange-200 dark:border-orange-800 pt-1">
                                                <span class="text-orange-700 dark:text-orange-300 font-medium">Total Shipping ({{ form.sale_qty || 1 }} ekor)</span>
                                                <span class="font-bold text-orange-800 dark:text-orange-200">{{ fmtIDR(shippingKena) }}</span>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Jika batch baru / tanpa batch: tampilkan input manual -->
                                    <template v-else>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="space-y-1.5">
                                                <Label class="text-xs font-semibold text-orange-700 dark:text-orange-400">Total Biaya Shipping (IDR)</Label>
                                                <div class="relative">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-orange-500">Rp</span>
                                                    <Input type="text" inputmode="numeric" :modelValue="shippingCostDisplay" @update:modelValue="onShippingCostChange" @blur="onShippingCostBlur" placeholder="0" class="h-9 text-sm pl-9 border-orange-300 dark:border-orange-700" />
                                                </div>
                                                <InputError :message="(form.errors as any).shipping_cost" />
                                            </div>
                                            <div class="space-y-1.5">
                                                <Label class="text-xs font-semibold text-orange-700 dark:text-orange-400">Jumlah Ikan Sepaket</Label>
                                                <Input v-model="form.shipping_qty" type="number" min="1" placeholder="0" class="h-9 text-sm border-orange-300 dark:border-orange-700" />
                                                <p class="text-[10px] text-muted-foreground">Total ikan dalam 1 pengiriman</p>
                                                <InputError :message="(form.errors as any).shipping_qty" />
                                            </div>
                                        </div>
                                        <div v-if="shippingPerEkor" class="rounded border border-orange-300 dark:border-orange-700 bg-orange-100 dark:bg-orange-900/40 px-3 py-2 text-[11px] space-y-1">
                                            <div class="flex justify-between items-center">
                                                <span class="text-orange-700 dark:text-orange-300 font-medium">Shipping/ekor</span>
                                                <span class="font-semibold text-orange-800 dark:text-orange-200">{{ fmtIDR(shippingPerEkor) }}</span>
                                            </div>
                                            <div v-if="shippingKena" class="flex justify-between items-center border-t border-orange-200 dark:border-orange-800 pt-1">
                                                <span class="text-orange-700 dark:text-orange-300 font-medium">Total Shipping ({{ form.sale_qty || 1 }} ekor)</span>
                                                <span class="font-bold text-orange-800 dark:text-orange-200">{{ fmtIDR(shippingKena) }}</span>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Keterangan & Tanggal Shipping -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="space-y-1.5">
                                            <Label class="text-xs font-medium text-orange-700 dark:text-orange-400">Keterangan <span class="font-normal opacity-60">(opsional)</span></Label>
                                            <Input v-model="form.shipping_note" type="text" placeholder="Contoh: Sea freight Nov 2025, JNE Express..." class="h-9 text-sm border-orange-300 dark:border-orange-700" />
                                            <InputError :message="(form.errors as any).shipping_note" />
                                        </div>
                                        <div class="space-y-1.5">
                                            <Label class="text-xs font-medium text-orange-700 dark:text-orange-400">Shipping Date <span class="font-normal opacity-60">(opsional)</span></Label>
                                            <Input v-model="form.shipping_date" type="date" class="h-9 text-sm border-orange-300 dark:border-orange-700" />
                                            <InputError :message="(form.errors as any).shipping_date" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-border"></div>

                        <!-- ══ SECTION: Transaksi ══ -->
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="h-4 w-1 rounded-full bg-green-500"></div>
                                <p class="text-sm font-semibold">Transaksi</p>
                            </div>

                            <div class="space-y-3 pl-3">

                                <!-- Customer & Status -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="grid gap-1.5">
                                        <Label class="text-xs font-medium text-muted-foreground">Customer</Label>
                                        <Select v-model="form.customer_id">
                                            <SelectTrigger class="w-full"><SelectValue placeholder="Pilih customer (opsional)" /></SelectTrigger>
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
                                    <div class="grid gap-1.5">
                                        <Label class="text-xs font-medium text-muted-foreground">Status</Label>
                                        <Select v-model="form.sale_status">
                                            <SelectTrigger class="w-full"><SelectValue placeholder="Pilih status" /></SelectTrigger>
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
                                </div>

                                <!-- Invoice -->
                                <div class="grid gap-1.5">
                                    <Label class="text-xs font-medium text-muted-foreground">No. Invoice</Label>
                                    <Input v-model="form.sale_invoice" type="text" placeholder="Contoh: INV-2025-001" class="h-9 text-sm font-mono" />
                                    <InputError :message="(form.errors as any).sale_invoice" />
                                </div>

                                <!-- Azukari period -->
                                <div v-if="form.sale_status === 'azukari'" class="grid gap-1.5">
                                    <Label class="text-xs font-medium text-muted-foreground">Periode Azukari <span class="font-normal opacity-60">(opsional)</span></Label>
                                    <Input v-model="form.azukari_period" type="text" placeholder="Contoh: Nov 2024 – Nov 2025" class="h-9 text-sm" />
                                    <p class="text-[10px] text-muted-foreground">Teks bebas · akan tampil di invoice PDF</p>
                                    <InputError :message="(form.errors as any).azukari_period" />
                                </div>

                                <!-- Tanggal & Qty Jual -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="grid gap-1.5">
                                        <Label class="text-xs font-medium text-muted-foreground">Tanggal</Label>
                                        <Input v-model="form.sale_tanggal" type="date" class="h-9 text-sm" />
                                        <InputError :message="form.errors.sale_tanggal" />
                                    </div>
                                    <div class="grid gap-1.5">
                                        <Label class="text-xs font-medium text-muted-foreground">
                                            Qty Jual (ekor)
                                            <span v-if="modalQty" class="font-normal text-muted-foreground">maks. {{ modalQty }}</span>
                                        </Label>
                                        <Input v-model="form.sale_qty" type="number" min="1" :max="modalQty || undefined" placeholder="0" class="h-9 text-sm" />
                                        <p v-if="modalQty && Number(form.sale_qty) > Number(modalQty)" class="text-[10px] text-red-500">Melebihi qty modal ({{ modalQty }})</p>
                                        <p v-else class="text-[10px] text-muted-foreground">Bisa 1–{{ modalQty || '?' }} ekor</p>
                                        <InputError :message="form.errors.sale_qty" />
                                    </div>
                                </div>

                                <!-- Harga -->
                                <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-950/20 p-3 space-y-3">
                                    <p class="text-[11px] font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wider">Harga</p>

                                    <!-- Sales Price JPY & Total Sales IDR -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="space-y-1.5">
                                            <Label class="text-xs font-semibold text-amber-700 dark:text-amber-400">Sales Price (JPY)</Label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-amber-500">¥</span>
                                                <Input type="text" inputmode="numeric" :modelValue="unitPriceDisplay" @update:modelValue="onUnitPriceChange" @blur="onUnitPriceBlur" placeholder="0" class="h-9 text-sm pl-7" />
                                            </div>
                                            <div v-if="unitPriceIdr" class="rounded border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/30 px-2.5 py-1 text-[11px] flex justify-between">
                                                <span class="text-muted-foreground">≈ IDR/unit</span>
                                                <span class="font-semibold text-amber-700 dark:text-amber-400">{{ fmtIDR(unitPriceIdr) }}</span>
                                            </div>
                                            <InputError :message="form.errors.sell_idr" />
                                        </div>
                                        <div class="space-y-1.5">
                                            <Label class="text-xs font-semibold text-amber-700 dark:text-amber-400">Total Sales (IDR) <span class="font-normal opacity-60">auto</span></Label>
                                            <div class="flex h-9 items-center rounded-md border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-950/30 px-3 text-sm font-semibold text-amber-800 dark:text-amber-200">
                                                {{ salesPriceIdr ? fmtIDR(salesPriceIdr) : '-' }}
                                            </div>
                                            <p class="text-[10px] text-muted-foreground">Sales JPY × {{ form.sale_qty || 1 }} ekor × kurs</p>
                                        </div>
                                    </div>

                                    <!-- Fee -->
                                    <div class="grid gap-1.5">
                                        <Label class="text-xs font-medium text-amber-700 dark:text-amber-400">Fee</Label>
                                        <Select v-model="feePct" @update:model-value="onFeeSelect">
                                            <SelectTrigger class="h-9 text-sm"><SelectValue placeholder="Pilih fee %" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="0">No Fee</SelectItem>
                                                <SelectItem value="5">5%</SelectItem>
                                                <SelectItem value="7.5">7.5%</SelectItem>
                                                <SelectItem value="10">10%</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError :message="form.errors.fee" />
                                    </div>
                                </div>

                                <!-- Ringkasan -->
                                <div v-if="salesPriceIdr || modalTerjualIdr" class="rounded-md border border-border overflow-hidden text-xs">
                                    <div class="bg-muted/60 px-4 py-2 border-b border-border flex items-center justify-between">
                                        <p class="text-[11px] font-semibold text-foreground uppercase tracking-wider">Ringkasan</p>
                                        <span class="text-[10px] text-muted-foreground">Kurs ¥1 = Rp {{ Number(kursInput || 0).toLocaleString('id-ID') }}</span>
                                    </div>
                                    <div class="divide-y divide-border/60">
                                        <div class="px-4 py-2.5 flex justify-between items-start">
                                            <div>
                                                <p class="font-medium text-foreground">Modal Terjual</p>
                                                <p class="text-[10px] text-muted-foreground mt-0.5">¥{{ Number(form.stock_price||0).toLocaleString('ja-JP') }} × kurs × {{ form.sale_qty || 1 }} ekor terjual</p>
                                            </div>
                                            <span class="font-semibold text-blue-600 dark:text-blue-400">{{ modalTerjualIdr ? fmtIDR(modalTerjualIdr) : '-' }}</span>
                                        </div>
                                        <div class="px-4 py-2.5 flex justify-between items-start">
                                            <div>
                                                <p class="font-medium text-foreground">Total Sales</p>
                                                <p class="text-[10px] text-muted-foreground mt-0.5">¥{{ Number(form.unit_price||0).toLocaleString('ja-JP') }} × {{ form.sale_qty||1 }} ekor × kurs</p>
                                            </div>
                                            <span class="font-semibold text-green-600 dark:text-green-400">{{ salesPriceIdr ? fmtIDR(salesPriceIdr) : '-' }}</span>
                                        </div>
                                        <div v-if="shippingKena" class="px-4 py-2.5 flex justify-between items-start">
                                            <div>
                                                <p class="font-medium text-foreground">Shipping</p>
                                                <p class="text-[10px] text-muted-foreground mt-0.5">
                                                    {{ fmtIDR(shippingPerEkor) }}/ekor × {{ form.sale_qty || 1 }} ekor
                                                    <span v-if="form.shipping_note" class="ml-1 italic">· {{ form.shipping_note }}</span>
                                                </p>
                                            </div>
                                            <span class="font-semibold text-orange-600 dark:text-orange-400">− {{ fmtIDR(shippingKena) }}</span>
                                        </div>
                                        <div v-if="Number(form.fee) > 0" class="px-4 py-2.5 flex justify-between items-center">
                                            <p class="font-medium text-foreground">Fee <span v-if="feePct && Number(feePct) > 0" class="font-normal text-muted-foreground">({{ feePct }}%)</span></p>
                                            <span class="font-semibold text-green-600 dark:text-green-400">+ {{ fmtIDR(Number(form.fee)) }}</span>
                                        </div>
                                        <div class="px-4 py-3 flex justify-between items-start bg-muted/30">
                                            <div>
                                                <p class="font-bold text-sm text-foreground">Profit</p>
                                                <p class="text-[10px] text-muted-foreground mt-0.5">Sales − Modal + Fee</p>
                                            </div>
                                            <span class="text-base font-bold" :class="profitPositive ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400'">
                                                {{ fmtIDR(profit) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>

                <!-- Submit -->
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
    </AppLayout>
</template>
