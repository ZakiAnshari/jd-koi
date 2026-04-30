<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { X, ImagePlus, Eye, Pencil, Trash2, ExternalLink } from 'lucide-vue-next'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { useSwal } from '@/composables/useSwal'
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'

// ─── Types ────────────────────────────────────────────────
type Breeder  = { id: number; name_breeder: string }
type JenisKoi = { id: number; nama_jenis: string }
type Customer = { id: number; name: string }
type Invoice  = { sale_invoice: string; koi_count: number }
type KoiImage = { id: number; koi_id: number; image_path: string; sort_order: number; url: string }
type Koi = {
    id: number; breeder_id: number; jenis_koi_id: number | null
    code: string; name: string; age: number | null; size: number | null
    box_qty: number | null; weight: number | null; total_weight: number | null
    breeder?: Breeder; jenis_koi?: JenisKoi; customer?: Customer | null
    customer_id: number | null; sale_status: string | null; created_at: string
    images?: KoiImage[]
}

// ─── Page props ───────────────────────────────────────────
const page      = usePage()
const kois      = computed(() => (page.props.kois      as Koi[]      | null) ?? [])
const breeders  = computed(() => (page.props.breeders  as Breeder[]  | null) ?? [])
const jenisKois = computed(() => (page.props.jenisKois as JenisKoi[] | null) ?? [])
const customers = computed(() => (page.props.customers as Customer[] | null) ?? [])
const invoices  = computed(() => (page.props.invoices  as Invoice[]  | null) ?? [])

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data', href: '#' },
    { title: 'Data Koi', href: '/master-data/koi' },
]

// ─── DataTable ────────────────────────────────────────────
const kSearch    = ref('')
const kSortKey   = ref<keyof Koi>('created_at')
const kSortOrder = ref<'asc' | 'desc'>('desc')
const kPage      = ref(1)
const kPerPage   = ref(10)

const filteredKois = computed(() => {
    let r = kois.value
    if (kSearch.value) {
        const q = kSearch.value.toLowerCase()
        r = r.filter(k =>
            k.code.toLowerCase().includes(q) ||
            k.name.toLowerCase().includes(q) ||
            (k.breeder?.name_breeder ?? '').toLowerCase().includes(q),
        )
    }
    return [...r].sort((a, b) => {
        const av = a[kSortKey.value] ?? ''; const bv = b[kSortKey.value] ?? ''
        if (typeof av === 'string' && typeof bv === 'string')
            return kSortOrder.value === 'asc' ? av.localeCompare(bv) : bv.localeCompare(av)
        if (typeof av === 'number' && typeof bv === 'number')
            return kSortOrder.value === 'asc' ? av - bv : bv - av
        return 0
    })
})
const kTotalPages   = computed(() => Math.ceil(filteredKois.value.length / kPerPage.value) || 1)
const paginatedKois = computed(() => filteredKois.value.slice((kPage.value - 1) * kPerPage.value, kPage.value * kPerPage.value))
const kFrom = computed(() => filteredKois.value.length === 0 ? 0 : (kPage.value - 1) * kPerPage.value + 1)
const kTo   = computed(() => Math.min(kPage.value * kPerPage.value, filteredKois.value.length))

function kToggleSort(key: keyof Koi) {
    if (kSortKey.value === key) kSortOrder.value = kSortOrder.value === 'asc' ? 'desc' : 'asc'
    else { kSortKey.value = key; kSortOrder.value = 'asc' }
}
function kSortIcon(key: keyof Koi) { return kSortKey.value !== key ? '↕' : kSortOrder.value === 'asc' ? '↑' : '↓' }

function getBreederName(koi: Koi) {
    return koi.breeder?.name_breeder ?? breeders.value.find(b => b.id === koi.breeder_id)?.name_breeder ?? '-'
}
function getJenisName(koi: Koi) {
    if (koi.jenis_koi?.nama_jenis) return koi.jenis_koi.nama_jenis
    if (!koi.jenis_koi_id) return '-'
    return jenisKois.value.find(j => j.id === koi.jenis_koi_id)?.nama_jenis ?? '-'
}
function getFirstImage(koi: Koi): string | null {
    if (koi.images && koi.images.length > 0) {
        return koi.images[0].url
    }
    return null
}
function openImageModal(koi: Koi) {
    selectedKoiImages.value = koi.images || []
    showImageModal.value = true
}
function quickUpdateStatus(id: number, status: string) {
    router.put(`/master-data/koi/${id}/status`, { sale_status: status || null }, {
        preserveScroll: true,
        onSuccess: () => {
            useSwal().fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Status berhasil diperbarui.',
                timer: 2000,
                showConfirmButton: false,
            })
        },
    })
}

// ─── Modal state ──────────────────────────────────────────
const showModal      = ref(false)
const showImageModal = ref(false)
const selectedKoiImages = ref<KoiImage[]>([])

const breederSearch  = ref('')
const jenisSearch    = ref('')
const customerSearch = ref('')
const fotoPreviews   = ref<{ file: File; url: string }[]>([])
const feePct              = ref<string | null>(null)
const exchangeRate        = ref(0)
const exchangeRateLoading = ref(false)
const exchangeRateError   = ref(false)
const kursInput           = ref('')
const kursIsManual        = ref(false)

async function fetchExchangeRate() {
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

watch(showModal, open => {
    if (!open) return
    kursIsManual.value = false
    if (exchangeRate.value > 0) {
        kursInput.value = String(exchangeRate.value)
    } else {
        fetchExchangeRate()
    }
})
watch(exchangeRate, rate => { if (rate > 0 && !kursIsManual.value) kursInput.value = String(rate) })
watch(kursInput, v => { form.kurs = v })
function resetKurs() { kursIsManual.value = false; kursInput.value = String(exchangeRate.value) }

// ─── Form ─────────────────────────────────────────────────
const form = useForm({
    breeder_id: '', jenis_koi_id: '', code: '', name: '',
    age: '', size: '', box_qty: '', weight: '', total_weight: '',
    images: [] as File[],
    customer_id: '', sale_tanggal: '', sale_qty: '', sale_invoice: '', azukari_period: '',
    buy_jpy: '', unit_price: '', kurs: '', sell_idr: '', stock_price: '', fee: '0',
    sale_status: 'shipped',
    shipping_cost: '', shipping_qty: '', shipping_note: '',
})

const modalQty = ref('')

// unit_price × qty × kurs → sell_idr
watch([() => form.unit_price, () => form.sale_qty, kursInput], ([price, qty, kurs]) => {
    const p = Number(price) || 0
    const q = Number(qty)   || 1
    const k = Number(kurs)  || 0
    form.sell_idr = p > 0 && k > 0 ? String(Math.round(p * q * k)) : ''
})

// ─── Computed previews ────────────────────────────────────
// Modal Price IDR per ekor = stock_price (JPY) × kurs
const modalPriceIdr = computed(() => {
    const m = Number(form.stock_price) || 0
    const k = Number(kursInput.value)  || 0
    return m > 0 && k > 0 ? Math.round(m * k) : null
})
// Total Modal IDR = modal price IDR × modal_qty (stok disiapkan)
const totalModalIdr = computed(() => {
    const m = modalPriceIdr.value      || 0
    const q = Number(modalQty.value)   || 0
    return m > 0 && q > 0 ? Math.round(m * q) : null
})
// Modal IDR untuk qty yang terjual (dipakai di profit)
const modalTerjualIdr = computed(() => {
    const m = modalPriceIdr.value   || 0
    const q = Number(form.sale_qty) || 1
    return m > 0 ? Math.round(m * q) : null
})
// Unit price IDR preview
const unitPriceIdr = computed(() => {
    const p = Number(form.unit_price) || 0
    const k = Number(kursInput.value) || 0
    return p > 0 && k > 0 ? Math.round(p * k) : null
})
// Sales Price JPY (unit × qty)
const salesPriceJpy = computed(() => {
    const p = Number(form.unit_price) || 0
    const q = Number(form.sale_qty)   || 1
    return p > 0 ? p * q : null
})
// Sales Price IDR
const salesPriceIdr = computed(() => {
    const jpy = salesPriceJpy.value
    const k   = Number(kursInput.value) || 0
    return jpy && k > 0 ? Math.round(jpy * k) : null
})
// Shipping per ekor = total shipping ÷ jumlah ikan sepaket
const shippingPerEkor = computed(() => {
    const cost = Number(form.shipping_cost) || 0
    const qty  = Number(form.shipping_qty)  || 0
    return cost > 0 && qty > 0 ? Math.round(cost / qty) : null
})

// Profit = (Sell IDR − Modal IDR − Shipping/ekor) + Fee
const profit = computed(() =>
    (salesPriceIdr.value ?? 0) - (modalTerjualIdr.value ?? 0) - (shippingPerEkor.value ?? 0) + Number(form.fee || 0)
)

const filteredBreeders  = computed(() => !breederSearch.value  ? breeders.value  : breeders.value.filter(b => b.name_breeder.toLowerCase().includes(breederSearch.value.toLowerCase())))
const filteredJenisKois = computed(() => !jenisSearch.value   ? jenisKois.value  : jenisKois.value.filter(j => j.nama_jenis.toLowerCase().includes(jenisSearch.value.toLowerCase())))
const filteredCustomers = computed(() => !customerSearch.value ? customers.value : customers.value.filter(c => c.name.toLowerCase().includes(customerSearch.value.toLowerCase())))


// ─── Status badge ─────────────────────────────────────────
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
function statusLabel(s?: string | null) { return s ? (STATUS_LABEL[s] ?? s) : '-' }
function statusClass(s?: string | null) { return s ? (STATUS_CLASS[s] ?? '') : '' }

// ─── Formatters ───────────────────────────────────────────
const fmtIDR  = (v: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v)
const fmtDate = (d: string) => new Date(d).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' })

function fmtInput(val: string | number): string {
    const n = String(val).replace(/\D/g, '')
    return n ? Number(n).toLocaleString('id-ID') : ''
}
function parseInput(e: Event): string {
    return (e.target as HTMLInputElement).value.replace(/\D/g, '')
}
function onKursInput(e: Event) { kursIsManual.value = true; kursInput.value = parseInput(e) }
function onBuyJpyInput(e: Event) { form.buy_jpy = parseInput(e) }
function onUnitPriceInput(e: Event) { form.unit_price = parseInput(e) }
function onFeeSelect(val: unknown) {
    feePct.value = val !== null ? String(val) : null
    form.fee = String(Math.round(Number(form.sell_idr) * (Number(val) / 100)))
}

// ─── Image handlers ───────────────────────────────────────
function handleImagesChange(e: Event) {
    const input = e.target as HTMLInputElement
    if (!input.files?.length) return
    const files = Array.from(input.files)
    form.images = [...form.images, ...files]
    files.forEach(f => { const r = new FileReader(); r.onload = ev => fotoPreviews.value.push({ file: f, url: ev.target?.result as string }); r.readAsDataURL(f) })
    input.value = ''
}
function removeNewImage(i: number) { fotoPreviews.value.splice(i, 1); form.images = form.images.filter((_, idx) => idx !== i) }

// ─── Submit ───────────────────────────────────────────────
function submit() {
    form.post('/master-data/koi', {
        forceFormData: true, preserveScroll: true,
        onSuccess: () => {
            useSwal().fire({ icon: 'success', title: 'Berhasil!', text: 'Data berhasil disimpan.', timer: 3000, showConfirmButton: false })
            showModal.value = false
        },
        onError: () => {
            useSwal().fire({ icon: 'error', title: 'Gagal!', text: 'Terjadi kesalahan. Periksa kembali isian.', timer: 4000, showConfirmButton: false })
        },
    })
}

// ─── Delete ───────────────────────────────────────────────
function confirmDeleteKoi(id: number, name: string) {
    useSwal().fire({ title: 'Hapus koi?', text: `Koi "${name}" akan dihapus permanen.`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', cancelButtonColor: '#6b7280', confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal' })
        .then(r => { if (r.isConfirmed) router.delete(`/master-data/koi/${id}`, { preserveScroll: true, onSuccess: () => useSwal().fire({ icon: 'success', title: 'Berhasil!', text: 'Koi dihapus.', timer: 2000, showConfirmButton: false }) }) })
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Data Koi" />

        <div class="flex flex-col gap-4 p-6">

            <!-- Header -->
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Data Koi</h1>
                    <p class="text-sm text-muted-foreground">Kelola data koi.</p>
                </div>

                <!-- MODAL -->
                <Dialog v-model:open="showModal">
                    <DialogTrigger as-child>
                        <Button>+ New Koi</Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-[660px] max-h-[92vh] overflow-y-auto">
                        <DialogHeader class="pb-2 border-b border-border">
                            <DialogTitle class="text-base font-semibold">Tambah Data Koi</DialogTitle>
                            <DialogDescription class="text-xs">Isi data koi dan informasi penjualan (opsional).</DialogDescription>
                        </DialogHeader>

                        <form @submit.prevent="submit" class="py-4 space-y-5 px-1">

                            <!-- ══ SECTION: Data Koi ══ -->
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="h-4 w-1 rounded-full bg-primary"></div>
                                    <p class="text-sm font-semibold">Data Koi</p>
                                </div>

                                <div class="space-y-3 pl-3">
                                    <!-- Breeder & Jenis Koi -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="grid gap-1.5">
                                            <Label class="text-xs font-medium text-muted-foreground">Breeder <span class="text-red-500">*</span></Label>
                                            <Select v-model="form.breeder_id">
                                                <SelectTrigger><SelectValue placeholder="Pilih breeder" /></SelectTrigger>
                                                <SelectContent class="p-0 w-[--radix-select-trigger-width] min-w-[20rem]">
                                                    <div class="sticky top-0 bg-background p-2 border-b">
                                                        <Input v-model="breederSearch" placeholder="Cari breeder..." class="h-8" @keydown.stop />
                                                    </div>
                                                    <div class="max-h-44 overflow-y-auto">
                                                        <div v-if="filteredBreeders.length === 0" class="px-3 py-2 text-sm text-muted-foreground">Tidak ada breeder</div>
                                                        <SelectItem v-for="b in filteredBreeders" :key="b.id" :value="String(b.id)">{{ b.name_breeder }}</SelectItem>
                                                    </div>
                                                </SelectContent>
                                            </Select>
                                            <InputError :message="form.errors.breeder_id" />
                                        </div>
                                        <div class="grid gap-1.5">
                                            <Label class="text-xs font-medium text-muted-foreground">Jenis Koi</Label>
                                            <Select v-model="form.jenis_koi_id">
                                                <SelectTrigger><SelectValue placeholder="Pilih jenis koi" /></SelectTrigger>
                                                <SelectContent class="p-0 w-[--radix-select-trigger-width] min-w-[20rem]">
                                                    <div class="sticky top-0 bg-background p-2 border-b">
                                                        <Input v-model="jenisSearch" placeholder="Cari jenis koi..." class="h-8" @keydown.stop />
                                                    </div>
                                                    <div class="max-h-44 overflow-y-auto">
                                                        <div v-if="filteredJenisKois.length === 0" class="px-3 py-2 text-sm text-muted-foreground">Tidak ada</div>
                                                        <SelectItem v-for="j in filteredJenisKois" :key="j.id" :value="String(j.id)">{{ j.nama_jenis }}</SelectItem>
                                                    </div>
                                                </SelectContent>
                                            </Select>
                                            <InputError :message="form.errors.jenis_koi_id" />
                                        </div>
                                    </div>

                                    <!-- Kode & Nama -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="grid gap-1.5">
                                            <Label class="text-xs font-medium text-muted-foreground">Kode Koi <span class="text-red-500">*</span></Label>
                                            <Input v-model="form.code" placeholder="KOI-001" />
                                            <InputError :message="form.errors.code" />
                                        </div>
                                        <div class="grid gap-1.5">
                                            <Label class="text-xs font-medium text-muted-foreground">Nama Koi <span class="text-red-500">*</span></Label>
                                            <Input v-model="form.name" placeholder="Nama koi" />
                                            <InputError :message="form.errors.name" />
                                        </div>
                                    </div>

                                    <!-- Data Fisik -->
                                    <div class="rounded-md border border-border bg-muted/20 p-3 space-y-2.5">
                                        <p class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">Data Fisik</p>
                                        <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                                            <div class="grid gap-1">
                                                <Label class="text-[11px] text-muted-foreground">Age (bln)</Label>
                                                <Input v-model="form.age" type="number" min="0" placeholder="0" class="h-8 text-sm" />
                                                <InputError :message="form.errors.age" />
                                            </div>
                                            <div class="grid gap-1">
                                                <Label class="text-[11px] text-muted-foreground">Size (cm)</Label>
                                                <Input v-model="form.size" type="number" min="0" step="0.01" placeholder="0" class="h-8 text-sm" />
                                                <InputError :message="form.errors.size" />
                                            </div>
                                            <div class="grid gap-1">
                                                <Label class="text-[11px] text-muted-foreground">Box QTY</Label>
                                                <Input v-model="form.box_qty" type="number" min="0" placeholder="0" class="h-8 text-sm" />
                                                <InputError :message="form.errors.box_qty" />
                                            </div>
                                            <div class="grid gap-1">
                                                <Label class="text-[11px] text-muted-foreground">Weight (kg)</Label>
                                                <Input v-model="form.weight" type="number" min="0" step="0.001" placeholder="0" class="h-8 text-sm" />
                                                <InputError :message="form.errors.weight" />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Foto Koi -->
                                    <div class="rounded-md border border-border bg-muted/20 p-3 space-y-2">
                                        <p class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">Foto Koi</p>
                                        <div v-if="fotoPreviews.length > 0" class="flex flex-wrap gap-2">
                                            <div v-for="(preview, i) in fotoPreviews" :key="i" class="relative h-16 w-16 overflow-hidden rounded-lg border group">
                                                <img :src="preview.url" class="h-full w-full object-cover" />
                                                <button type="button" @click="removeNewImage(i)" class="absolute inset-0 flex items-center justify-center bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <X class="h-4 w-4 text-white" />
                                                </button>
                                            </div>
                                        </div>
                                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border-2 border-dashed border-border px-3 py-2 text-xs text-muted-foreground hover:border-primary/50 hover:text-foreground transition-colors">
                                            <ImagePlus class="h-4 w-4 flex-shrink-0" />
                                            <span>Pilih foto (jpg, png, webp · maks. 2 MB · bisa beberapa)</span>
                                            <input type="file" accept="image/jpg,image/jpeg,image/png,image/webp" multiple class="sr-only" @change="handleImagesChange" />
                                        </label>
                                        <InputError :message="(form.errors as any)['images.*']" />
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-border"></div>

                            <!-- ══ SECTION: Modal ══ -->
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="h-4 w-1 rounded-full bg-blue-500"></div>
                                    <p class="text-sm font-semibold">Modal</p>
                                </div>

                                <div class="space-y-3 pl-3">
                                    <div class="rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50/50 dark:bg-blue-950/20 p-3 space-y-3">
                                        <div class="grid grid-cols-2 gap-3">
                                            <!-- Modal Price JPY -->
                                            <div class="space-y-1.5">
                                                <Label class="text-xs font-semibold text-blue-700 dark:text-blue-400">Modal Price (JPY/ekor)</Label>
                                                <div class="relative">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-blue-500">¥</span>
                                                    <Input
                                                        type="text" inputmode="numeric"
                                                        :value="fmtInput(form.stock_price)"
                                                        @input="(e: Event) => { form.stock_price = parseInput(e) }"
                                                        placeholder="0"
                                                        class="h-9 text-sm pl-7 border-blue-300 dark:border-blue-700 focus:border-blue-500"
                                                    />
                                                </div>
                                                <div v-if="modalPriceIdr" class="rounded border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/30 px-2.5 py-1 text-[11px] flex justify-between">
                                                    <span class="text-muted-foreground">≈ IDR/ekor</span>
                                                    <span class="font-semibold text-blue-700 dark:text-blue-400">{{ fmtIDR(modalPriceIdr) }}</span>
                                                </div>
                                                <InputError :message="(form.errors as any).stock_price" />
                                            </div>
                                            <!-- Qty Modal (stok disiapkan) -->
                                            <div class="space-y-1.5">
                                                <Label class="text-xs font-semibold text-blue-700 dark:text-blue-400">Qty Modal (ekor)</Label>
                                                <Input
                                                    type="number" min="1" placeholder="1"
                                                    v-model="modalQty"
                                                    class="h-9 text-sm border-blue-300 dark:border-blue-700"
                                                />
                                                <p class="text-[10px] text-muted-foreground">Stok yang disiapkan</p>
                                            </div>
                                        </div>
                                        <!-- Total Modal -->
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
                                        <div class="grid grid-cols-2 gap-3">
                                            <!-- Total Biaya Shipping -->
                                            <div class="space-y-1.5">
                                                <Label class="text-xs font-semibold text-orange-700 dark:text-orange-400">Total Biaya Shipping (IDR)</Label>
                                                <div class="relative">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-orange-600 dark:text-orange-400">Rp</span>
                                                    <Input
                                                        type="text" inputmode="numeric"
                                                        :value="fmtInput(form.shipping_cost)"
                                                        @input="(e: Event) => { form.shipping_cost = parseInput(e) }"
                                                        placeholder="0"
                                                        class="h-9 text-sm pl-9 border-orange-300 dark:border-orange-700"
                                                    />
                                                </div>
                                                <InputError :message="(form.errors as any).shipping_cost" />
                                            </div>
                                            <!-- Jumlah Ikan Sepaket -->
                                            <div class="space-y-1.5">
                                                <Label class="text-xs font-semibold text-orange-700 dark:text-orange-400">Jumlah Ikan Sepaket</Label>
                                                <Input
                                                    v-model="form.shipping_qty"
                                                    type="number" min="1" placeholder="1"
                                                    class="h-9 text-sm border-orange-300 dark:border-orange-700"
                                                />
                                                <p class="text-[10px] text-muted-foreground">Total ikan dalam 1 pengiriman</p>
                                                <InputError :message="(form.errors as any).shipping_qty" />
                                            </div>
                                        </div>

                                        <!-- Shipping per ekor (auto) -->
                                        <div v-if="shippingPerEkor" class="rounded border border-orange-300 dark:border-orange-700 bg-orange-100 dark:bg-orange-900/40 px-3 py-2 text-[11px] flex justify-between items-center">
                                            <span class="text-orange-700 dark:text-orange-300 font-medium">Shipping/ekor</span>
                                            <span class="font-bold text-orange-800 dark:text-orange-200">{{ fmtIDR(shippingPerEkor) }}</span>
                                        </div>

                                        <!-- Keterangan Shipping -->
                                        <div class="space-y-1.5">
                                            <Label class="text-xs font-medium text-orange-700 dark:text-orange-400">Keterangan <span class="font-normal opacity-60">(opsional)</span></Label>
                                            <Input
                                                v-model="form.shipping_note"
                                                type="text"
                                                placeholder="Contoh: Sea freight Nov 2025, JNE Express..."
                                                class="h-9 text-sm border-orange-300 dark:border-orange-700"
                                            />
                                            <InputError :message="(form.errors as any).shipping_note" />
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
                                                <SelectTrigger><SelectValue placeholder="Pilih customer" /></SelectTrigger>
                                                <SelectContent class="p-0 w-[--radix-select-trigger-width] min-w-[20rem]">
                                                    <div class="sticky top-0 bg-background p-2 border-b flex items-center gap-1.5">
                                                        <Input v-model="customerSearch" placeholder="Cari customer..." class="h-8 flex-1" @keydown.stop />
                                                        <a href="/admin/hak-akses/customer" target="_blank" title="Buka halaman Customer"
                                                            class="flex-shrink-0 inline-flex items-center justify-center h-8 w-8 rounded-md border border-border bg-background hover:bg-muted text-muted-foreground hover:text-foreground transition-colors">
                                                            <ExternalLink class="w-3.5 h-3.5" />
                                                        </a>
                                                    </div>
                                                    <div class="max-h-44 overflow-y-auto">
                                                        <div v-if="filteredCustomers.length === 0" class="px-3 py-2 text-sm text-muted-foreground">Tidak ada customer</div>
                                                        <SelectItem v-for="c in filteredCustomers" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                                                    </div>
                                                </SelectContent>
                                            </Select>
                                            <InputError :message="form.errors.customer_id" />
                                        </div>
                                        <div class="grid gap-1.5">
                                            <Label class="text-xs font-medium text-muted-foreground">Status</Label>
                                            <Select v-model="form.sale_status">
                                                <SelectTrigger><SelectValue placeholder="Pilih status" /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="pending">Pending</SelectItem>
                                                    <SelectItem value="shipped">Shipped</SelectItem>
                                                    <SelectItem value="azukari">Azukari</SelectItem>
                                                    <SelectItem value="dead">Dead</SelectItem>
                                                    <SelectItem value="cancel">Cancel</SelectItem>
                                                    <SelectItem value="refund">Refund</SelectItem>
                                                    <SelectItem value="dead_refund">Dead Refund</SelectItem>
                                                    <SelectItem value="cancel_refund">Cancel Refund</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError :message="form.errors.sale_status" />
                                        </div>
                                    </div>

                                    <!-- Invoice -->
                                    <div class="grid gap-1.5">
                                        <Label class="text-xs font-medium text-muted-foreground">
                                            No. Invoice
                                            <span class="font-normal opacity-60">(opsional · auto jika kosong)</span>
                                        </Label>
                                        <div class="relative">
                                            <input
                                                v-model="form.sale_invoice"
                                                list="invoice-list"
                                                type="text"
                                                placeholder="Ketik atau pilih invoice existing..."
                                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm font-mono shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                            />
                                            <datalist id="invoice-list">
                                                <option v-for="inv in invoices" :key="inv.sale_invoice" :value="inv.sale_invoice">
                                                    {{ inv.sale_invoice }} ({{ inv.koi_count }} koi)
                                                </option>
                                            </datalist>
                                        </div>
                                        <!-- Preview: jika invoice dipilih dari existing -->
                                        <div v-if="form.sale_invoice && invoices.find(i => i.sale_invoice === form.sale_invoice)" class="rounded border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/30 px-2.5 py-1.5 text-[11px] flex items-center gap-1.5">
                                            <span class="text-amber-600 dark:text-amber-400 font-medium">Invoice sudah ada:</span>
                                            <span class="text-amber-700 dark:text-amber-300">
                                                {{ invoices.find(i => i.sale_invoice === form.sale_invoice)?.koi_count }} koi terdaftar
                                                → akan menjadi {{ (invoices.find(i => i.sale_invoice === form.sale_invoice)?.koi_count ?? 0) + 1 }} koi
                                            </span>
                                        </div>
                                        <InputError :message="(form.errors as any).sale_invoice" />
                                    </div>

                                    <!-- Periode Azukari (hanya tampil jika status = azukari) -->
                                    <div v-if="form.sale_status === 'azukari'" class="grid gap-1.5">
                                        <Label class="text-xs font-medium text-muted-foreground">Periode Azukari <span class="font-normal opacity-60">(opsional)</span></Label>
                                        <Input v-model="form.azukari_period" type="text" placeholder="Contoh: Nov 2024 – Nov 2025" class="h-9 text-sm" />
                                        <p class="text-[10px] text-muted-foreground">Teks bebas · akan tampil di invoice PDF</p>
                                        <InputError :message="(form.errors as any).azukari_period" />
                                    </div>

                                    <!-- Tanggal & Qty -->
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
                                            <Input
                                                v-model="form.sale_qty"
                                                type="number" min="1"
                                                :max="modalQty || undefined"
                                                placeholder="1"
                                                class="h-9 text-sm"
                                            />
                                            <p v-if="modalQty && Number(form.sale_qty) > Number(modalQty)" class="text-[10px] text-red-500">Melebihi qty modal ({{ modalQty }})</p>
                                            <p v-else class="text-[10px] text-muted-foreground">Bisa 1–{{ modalQty || '?' }} ekor</p>
                                            <InputError :message="form.errors.sale_qty" />
                                        </div>
                                    </div>

                                    <!-- Kurs JPY → IDR -->
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
                                                :value="fmtInput(kursInput)"
                                                @input="onKursInput"
                                                placeholder="0"
                                                class="flex h-9 w-full rounded-md border-2 border-red-400 dark:border-red-600 bg-white dark:bg-red-900/20 pl-9 pr-14 text-sm font-semibold text-red-800 dark:text-red-100 placeholder:text-red-300 dark:placeholder:text-red-700 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-200 dark:focus:ring-red-800/50 transition-colors"
                                            />
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-red-500 dark:text-red-400">/ ¥1</span>
                                        </div>
                                        <p class="text-[11px] text-red-600/80 dark:text-red-400/80">
                                            Otomatis dari API · langsung ketik untuk ubah manual
                                        </p>
                                    </div>

                                    <!-- Harga -->
                                    <div class="rounded-lg border border-border bg-muted/20 p-3 space-y-3">
                                        <p class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">Harga</p>

                                        <!-- Stock JPY (dinonaktifkan sementara) -->
                                        <!-- <div class="space-y-1.5">
                                            <Label class="text-xs font-medium text-muted-foreground">Stock JPY</Label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-500">¥</span>
                                                <Input
                                                    type="text" inputmode="numeric"
                                                    :value="fmtInput(form.buy_jpy)"
                                                    @input="onBuyJpyInput"
                                                    placeholder="0"
                                                    class="h-9 text-sm pl-7"
                                                />
                                            </div>
                                            <InputError :message="(form.errors as any).buy_jpy" />
                                        </div> -->

                                        <!-- Sales Price JPY & Total Sales IDR -->
                                        <div class="grid grid-cols-2 gap-3">
                                            <!-- Sales Price JPY -->
                                            <div class="space-y-1.5">
                                                <Label class="text-xs font-medium text-muted-foreground">Sales Price (JPY)</Label>
                                                <div class="relative">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-amber-500">¥</span>
                                                    <Input
                                                        type="text" inputmode="numeric"
                                                        :value="fmtInput(form.unit_price)"
                                                        @input="onUnitPriceInput"
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
                                            <!-- Total Sales IDR (auto dari unit_price × qty × kurs) -->
                                            <div class="space-y-1.5">
                                                <Label class="text-xs font-semibold text-green-700 dark:text-green-400">Total Sales (IDR) <span class="font-normal opacity-60">auto</span></Label>
                                                <div class="flex h-9 items-center rounded-md border border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-950/30 px-3 text-sm font-semibold text-green-800 dark:text-green-200">
                                                    {{ salesPriceIdr ? fmtIDR(salesPriceIdr) : '-' }}
                                                </div>
                                                <p class="text-[10px] text-muted-foreground">Sales JPY × {{ form.sale_qty || 1 }} ekor × kurs</p>
                                            </div>
                                        </div>

                                        <!-- Fee -->
                                        <div class="grid gap-1.5">
                                            <Label class="text-xs font-medium text-muted-foreground">Fee</Label>
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
                                    <div v-if="salesPriceIdr || totalModalIdr" class="rounded-md border border-border overflow-hidden text-xs">
                                        <div class="bg-muted/60 px-4 py-2 border-b border-border flex items-center justify-between">
                                            <p class="text-[11px] font-semibold text-foreground uppercase tracking-wider">Ringkasan</p>
                                            <span class="text-[10px] text-muted-foreground">Kurs ¥1 = Rp {{ Number(kursInput || 0).toLocaleString('id-ID') }}</span>
                                        </div>
                                        <div class="divide-y divide-border/60">
                                            <!-- Total Modal (per qty terjual) -->
                                            <div class="px-4 py-2.5 flex justify-between items-start">
                                                <div>
                                                    <p class="font-medium text-foreground">Modal Terjual</p>
                                                    <p class="text-[10px] text-muted-foreground mt-0.5">
                                                        ¥{{ Number(form.stock_price||0).toLocaleString('ja-JP') }} × kurs × {{ form.sale_qty || 1 }} ekor terjual
                                                    </p>
                                                </div>
                                                <span class="font-semibold text-blue-600 dark:text-blue-400">
                                                    {{ modalTerjualIdr ? fmtIDR(modalTerjualIdr) : '-' }}
                                                </span>
                                            </div>
                                            <!-- Total Sales -->
                                            <div class="px-4 py-2.5 flex justify-between items-start">
                                                <div>
                                                    <p class="font-medium text-foreground">Total Sales</p>
                                                    <p class="text-[10px] text-muted-foreground mt-0.5">
                                                        ¥{{ Number(form.unit_price||0).toLocaleString('ja-JP') }} × {{ form.sale_qty||1 }} ekor × kurs
                                                    </p>
                                                </div>
                                                <span class="font-semibold text-green-600 dark:text-green-400">
                                                    {{ salesPriceIdr ? fmtIDR(salesPriceIdr) : '-' }}
                                                </span>
                                            </div>
                                            <!-- Shipping -->
                                            <div v-if="shippingPerEkor" class="px-4 py-2.5 flex justify-between items-start">
                                                <div>
                                                    <p class="font-medium text-foreground">Shipping/ekor</p>
                                                    <p class="text-[10px] text-muted-foreground mt-0.5">
                                                        {{ fmtIDR(Number(form.shipping_cost)) }} ÷ {{ form.shipping_qty }} ikan
                                                        <span v-if="form.shipping_note" class="ml-1 italic">· {{ form.shipping_note }}</span>
                                                    </p>
                                                </div>
                                                <span class="font-semibold text-orange-600 dark:text-orange-400">− {{ fmtIDR(shippingPerEkor) }}</span>
                                            </div>
                                            <!-- Fee -->
                                            <div v-if="Number(form.fee) > 0" class="px-4 py-2.5 flex justify-between items-center">
                                                <p class="font-medium text-foreground">
                                                    Fee <span v-if="feePct && Number(feePct) > 0" class="font-normal text-muted-foreground">({{ feePct }}%)</span>
                                                </p>
                                                <span class="font-semibold text-green-600 dark:text-green-400">+ {{ fmtIDR(Number(form.fee)) }}</span>
                                            </div>
                                            <!-- Profit -->
                                            <div class="px-4 py-3 flex justify-between items-start bg-muted/30">
                                                <div>
                                                    <p class="font-bold text-sm text-foreground">Profit</p>
                                                    <p class="text-[10px] text-muted-foreground mt-0.5">(Sell IDR − Modal − Shipping) + Fee</p>
                                                </div>
                                                <span class="text-base font-bold"
                                                    :class="profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400'">
                                                    {{ fmtIDR(profit) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="border-t border-border pt-3 flex justify-end gap-2">
                                <Button type="button" variant="outline" @click="() => { form.reset(); form.clearErrors(); breederSearch = ''; jenisSearch = ''; customerSearch = ''; fotoPreviews = []; feePct = null; kursIsManual = false; modalQty = ''; showModal = false }"
>Batal</Button>
                                <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Menyimpan...' : 'Simpan' }}</Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- DataTable -->
            <div class="border border-sidebar-border/70 dark:border-sidebar-border rounded-lg overflow-visible">
                <div class="flex flex-col gap-2 border-b border-border p-4 rounded-t-lg sm:flex-row sm:items-center sm:justify-between">
                    <Input :model-value="kSearch" @update:model-value="v => { kSearch = String(v); kPage = 1 }" placeholder="Cari code, nama, atau breeder..." class="max-w-sm" />
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted-foreground">Show</span>
                        <select :value="kPerPage" @change="(e) => { kPerPage = Number((e.target as HTMLSelectElement).value); kPage = 1 }" class="rounded-md border border-input bg-background px-3 py-1 text-sm">
                            <option>5</option><option>10</option><option>25</option><option>50</option>
                        </select>
                        <span class="text-sm text-muted-foreground">entries</span>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-b-lg">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Foto</th>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="kToggleSort('code')">Code <span class="ml-1">{{ kSortIcon('code') }}</span></th>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="kToggleSort('name')">Nama <span class="ml-1">{{ kSortIcon('name') }}</span></th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Breeder</th>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="kToggleSort('created_at')">Created <span class="ml-1">{{ kSortIcon('created_at') }}</span></th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-background">
                            <tr v-if="paginatedKois.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-muted-foreground">{{ kSearch ? 'Tidak ada hasil.' : 'Belum ada data koi.' }}</td>
                            </tr>
                            <tr v-for="koi in paginatedKois" :key="koi.id" class="transition-colors hover:bg-muted/50">
                                <td class="px-4 py-3">
                                    <button type="button" @click="openImageModal(koi)" class="relative h-10 w-10 overflow-hidden rounded-full border border-border bg-muted">
                                        <img v-if="getFirstImage(koi)" :src="getFirstImage(koi)!" class="h-full w-full object-cover" />
                                        <div v-else class="flex h-full w-full items-center justify-center text-muted-foreground">
                                            <ImagePlus :size="16" />
                                        </div>
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium">{{ koi.code }}</td>
                                <td class="px-4 py-3 text-sm">{{ koi.name }}</td>
                                <td class="px-4 py-3 text-sm">{{ getBreederName(koi) }}</td>
                                <td class="px-4 py-3 text-sm">{{ fmtDate(koi.created_at) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span v-if="koi.sale_status" :class="statusClass(koi.sale_status)" class="inline-block rounded-full px-3 py-1 text-xs font-semibold">{{ statusLabel(koi.sale_status) }}</span>
                                    <span v-else class="text-sm text-muted-foreground">-</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Select :model-value="koi.sale_status || ''" @update:model-value="(val) => quickUpdateStatus(koi.id, String(val))">
                                            <SelectTrigger class="h-9 w-32 border border-input bg-background rounded-md px-3 py-1.5 text-sm hover:bg-muted/50">
                                                <SelectValue :placeholder="'Pilih Status'" />
                                            </SelectTrigger>
                                            <SelectContent side="top" class="z-50">
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
                                        <Link :href="`/master-data/koi/${koi.id}`" class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background p-2 text-foreground shadow-sm hover:bg-muted" title="Detail"><Eye :size="16" /></Link>
                                        <Link :href="`/master-data/koi/${koi.id}/edit`" class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background p-2 text-foreground shadow-sm hover:bg-muted" title="Edit"><Pencil :size="16" /></Link>
                                        <button type="button" class="inline-flex items-center justify-center rounded-md border border-red-300 bg-red-50 p-2 text-red-700 shadow-sm hover:bg-red-100 dark:border-red-700 dark:bg-red-900 dark:text-red-200 dark:hover:bg-red-800" title="Hapus" @click="confirmDeleteKoi(koi.id, koi.name)"><Trash2 :size="16" /></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col items-center justify-between gap-2 border-t border-border px-4 py-3 sm:flex-row">
                    <p class="text-sm text-muted-foreground">Showing {{ kFrom }} to {{ kTo }} of {{ filteredKois.length }} entries<span v-if="kSearch"> (filtered from {{ kois.length }} total)</span></p>
                    <div class="flex items-center gap-1">
                        <Button variant="outline" size="sm" :disabled="kPage <= 1" @click="kPage--">Previous</Button>
                        <template v-for="p in kTotalPages" :key="p">
                            <Button size="sm" :variant="p === kPage ? 'default' : 'outline'" @click="kPage = p">{{ p }}</Button>
                        </template>
                        <Button variant="outline" size="sm" :disabled="kPage >= kTotalPages" @click="kPage++">Next</Button>
                    </div>
                </div>
            </div>

        </div>

        <!-- Image Modal -->
        <Dialog v-model:open="showImageModal">
            <DialogContent class="sm:max-w-[600px] max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Foto Koi</DialogTitle>
                    <DialogDescription>Daftar foto untuk koi ini.</DialogDescription>
                </DialogHeader>
                <div v-if="selectedKoiImages.length > 0" class="grid grid-cols-2 gap-3">
                    <div v-for="img in selectedKoiImages" :key="img.id" class="relative aspect-square overflow-hidden rounded-lg border">
                        <img :src="img.url" class="h-full w-full object-cover" />
                    </div>
                </div>
                <div v-else class="py-8 text-center text-muted-foreground">
                    <ImagePlus class="mx-auto h-12 w-12 opacity-50" />
                    <p class="mt-2 text-sm">Tidak ada foto</p>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="showImageModal = false">Tutup</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
