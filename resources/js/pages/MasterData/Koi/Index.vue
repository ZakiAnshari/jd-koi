<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { X, ImagePlus, Eye, Pencil, Trash2 } from 'lucide-vue-next'
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
type Koi = {
    id: number; breeder_id: number; jenis_koi_id: number | null
    code: string; name: string; age: number | null; size: number | null
    box_qty: number | null; weight: number | null; total_weight: number | null
    breeder?: Breeder; jenis_koi?: JenisKoi; customer?: Customer | null
    customer_id: number | null; sale_status: string | null; created_at: string
}

// ─── Page props ───────────────────────────────────────────
const page      = usePage()
const kois      = computed(() => (page.props.kois      as Koi[]      | null) ?? [])
const breeders  = computed(() => (page.props.breeders  as Breeder[]  | null) ?? [])
const jenisKois = computed(() => (page.props.jenisKois as JenisKoi[] | null) ?? [])
const customers = computed(() => (page.props.customers as Customer[] | null) ?? [])

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

// ─── Modal state ──────────────────────────────────────────
const showModal = ref(false)

const breederSearch  = ref('')
const jenisSearch    = ref('')
const customerSearch = ref('')
const fotoPreviews   = ref<{ file: File; url: string }[]>([])
const exchangeRate        = ref(0)
const exchangeRateLoading = ref(false)
const exchangeRateError   = ref(false)
const isSaleIdrManual     = ref(false)
const isSaleKursManual    = ref(false)

async function fetchExchangeRate() {
    if (exchangeRate.value > 0) return
    exchangeRateLoading.value = true; exchangeRateError.value = false
    try {
        const res = await fetch('https://api.exchangerate-api.com/v4/latest/JPY')
        if (!res.ok) throw new Error()
        const data = await res.json()
        exchangeRate.value = Math.round(Number(data?.rates?.IDR ?? 0))
        if (!exchangeRate.value) throw new Error()
    } catch { exchangeRateError.value = true; exchangeRate.value = 110 }
    finally  { exchangeRateLoading.value = false }
}

// ─── Form ─────────────────────────────────────────────────
const form = useForm({
    // Data Koi
    breeder_id: '', jenis_koi_id: '', code: '', name: '',
    age: '', size: '', box_qty: '', weight: '', total_weight: '',
    images: [] as File[],
    // Penjualan
    customer_id: '', sale_tanggal: '', sale_qty: '1',
    sell_jpy: '', kurs: '', sell_idr: '', fee: '0',
    sale_status: 'shipped',
})

watch(showModal, (open) => {
    if (open) { fetchExchangeRate() }
    else {
        form.reset(); form.clearErrors()
        breederSearch.value = ''; jenisSearch.value = ''; customerSearch.value = ''
        fotoPreviews.value = []; isSaleIdrManual.value = false; isSaleKursManual.value = false
    }
})
watch(exchangeRate, (rate) => {
    if (rate > 0 && !isSaleKursManual.value) form.kurs = String(rate)
})
watch(() => form.sell_jpy, (jpy) => {
    if (!isSaleIdrManual.value && form.kurs && jpy)
        form.sell_idr = String(Math.round(Number(jpy) * Number(form.kurs)))
})
watch(() => form.kurs, (kurs) => {
    if (!isSaleIdrManual.value && kurs && form.sell_jpy)
        form.sell_idr = String(Math.round(Number(form.sell_jpy) * Number(kurs)))
})

const filteredBreeders  = computed(() => !breederSearch.value  ? breeders.value  : breeders.value.filter(b => b.name_breeder.toLowerCase().includes(breederSearch.value.toLowerCase())))
const filteredJenisKois = computed(() => !jenisSearch.value   ? jenisKois.value  : jenisKois.value.filter(j => j.nama_jenis.toLowerCase().includes(jenisSearch.value.toLowerCase())))
const filteredCustomers = computed(() => !customerSearch.value ? customers.value : customers.value.filter(c => c.name.toLowerCase().includes(customerSearch.value.toLowerCase())))

const saleProfit         = computed(() => (Number(form.sell_idr) || 0) - (Number(form.fee) || 0))
const saleProfitPositive = computed(() => saleProfit.value >= 0)

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
        onSuccess: () => { useSwal().fire({ icon: 'success', title: 'Berhasil!', text: 'Data berhasil disimpan.', timer: 3000, showConfirmButton: false }); showModal.value = false },
        onError:   () => { useSwal().fire({ icon: 'error',   title: 'Gagal!',    text: 'Terjadi kesalahan. Periksa kembali isian.', timer: 3000, showConfirmButton: false }) },
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
                    <DialogContent class="sm:max-w-[640px] max-h-[92vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>Tambah Data Koi</DialogTitle>
                            <DialogDescription>Isi data koi dan informasi penjualan (opsional).</DialogDescription>
                        </DialogHeader>

                        <form @submit.prevent="submit" class="space-y-4">

                            <!-- ── Data Koi ─────────────────────── -->
                            <div class="rounded-lg border border-border p-4 space-y-3">
                                <p class="text-sm font-semibold">Data Koi</p>

                                <!-- Breeder -->
                                <div class="grid gap-1.5">
                                    <Label class="text-xs text-muted-foreground">Breeder <span class="text-red-500">*</span></Label>
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

                                <!-- Jenis Koi -->
                                <div class="grid gap-1.5">
                                    <Label class="text-xs text-muted-foreground">Jenis Koi</Label>
                                    <Select v-model="form.jenis_koi_id">
                                        <SelectTrigger><SelectValue placeholder="Pilih jenis koi (opsional)" /></SelectTrigger>
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

                                <!-- Kode & Nama -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="grid gap-1.5">
                                        <Label class="text-xs text-muted-foreground">Kode Koi <span class="text-red-500">*</span></Label>
                                        <Input v-model="form.code" placeholder="KOI-001" />
                                        <InputError :message="form.errors.code" />
                                    </div>
                                    <div class="grid gap-1.5">
                                        <Label class="text-xs text-muted-foreground">Nama Koi <span class="text-red-500">*</span></Label>
                                        <Input v-model="form.name" placeholder="Nama koi" />
                                        <InputError :message="form.errors.name" />
                                    </div>
                                </div>

                                <!-- Data Fisik -->
                                <div class="rounded-md border border-border bg-muted/30 p-3 space-y-2">
                                    <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Data Fisik</p>
                                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                        <div class="grid gap-1">
                                            <Label class="text-xs text-muted-foreground">Age (bln)</Label>
                                            <Input v-model="form.age" type="number" min="0" placeholder="0" class="h-8 text-sm" />
                                            <InputError :message="form.errors.age" />
                                        </div>
                                        <div class="grid gap-1">
                                            <Label class="text-xs text-muted-foreground">Size (cm)</Label>
                                            <Input v-model="form.size" type="number" min="0" step="0.01" placeholder="0" class="h-8 text-sm" />
                                            <InputError :message="form.errors.size" />
                                        </div>
                                        <div class="grid gap-1">
                                            <Label class="text-xs text-muted-foreground">Box QTY</Label>
                                            <Input v-model="form.box_qty" type="number" min="0" placeholder="0" class="h-8 text-sm" />
                                            <InputError :message="form.errors.box_qty" />
                                        </div>
                                        <div class="grid gap-1">
                                            <Label class="text-xs text-muted-foreground">Weight (kg)</Label>
                                            <Input v-model="form.weight" type="number" min="0" step="0.001" placeholder="0" class="h-8 text-sm" />
                                            <InputError :message="form.errors.weight" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Foto Koi -->
                                <div class="rounded-md border border-border bg-muted/30 p-3">
                                    <p class="mb-2 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Foto Koi</p>
                                    <div v-if="fotoPreviews.length > 0" class="mb-2 flex flex-wrap gap-2">
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
                                    <InputError :message="(form.errors as any)['images.*']" class="mt-1" />
                                </div>
                            </div>

                            <!-- ── Penjualan ────────────────────── -->
                            <div class="rounded-lg border border-border p-4 space-y-3">
                                <p class="text-sm font-semibold">Penjualan</p>

                                <div class="space-y-3">
                                    <!-- Customer -->
                                    <div class="grid gap-1.5">
                                        <Label class="text-xs text-muted-foreground">Customer</Label>
                                        <Select v-model="form.customer_id">
                                            <SelectTrigger><SelectValue placeholder="Pilih customer" /></SelectTrigger>
                                            <SelectContent class="p-0 w-[--radix-select-trigger-width] min-w-[20rem]">
                                                <div class="sticky top-0 bg-background p-2 border-b">
                                                    <Input v-model="customerSearch" placeholder="Cari customer..." class="h-8" @keydown.stop />
                                                </div>
                                                <div class="max-h-44 overflow-y-auto">
                                                    <div v-if="filteredCustomers.length === 0" class="px-3 py-2 text-sm text-muted-foreground">Tidak ada customer</div>
                                                    <SelectItem v-for="c in filteredCustomers" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                                                </div>
                                            </SelectContent>
                                        </Select>
                                        <InputError :message="form.errors.customer_id" />
                                    </div>

                                    <!-- Status -->
                                    <div class="grid gap-1.5">
                                        <Label class="text-xs text-muted-foreground">Status</Label>
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

                                    <!-- Info kurs -->
                                    <div class="rounded-md bg-muted/50 px-3 py-1.5 text-xs text-muted-foreground">
                                        <span v-if="exchangeRateLoading">Memuat kurs JPY→IDR...</span>
                                        <span v-else-if="exchangeRateError" class="text-orange-500">Kurs fallback: {{ exchangeRate }}</span>
                                        <span v-else>Kurs JPY→IDR: <strong>Rp {{ exchangeRate.toLocaleString('id-ID') }}</strong></span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="grid gap-1.5">
                                            <Label class="text-xs text-muted-foreground">Tanggal</Label>
                                            <Input v-model="form.sale_tanggal" type="date" class="h-9 text-sm" />
                                            <InputError :message="form.errors.sale_tanggal" />
                                        </div>
                                        <div class="grid gap-1.5">
                                            <Label class="text-xs text-muted-foreground">Qty</Label>
                                            <Input v-model="form.sale_qty" type="number" min="1" placeholder="1" class="h-9 text-sm" />
                                            <InputError :message="form.errors.sale_qty" />
                                        </div>
                                        <div class="grid gap-1.5">
                                            <Label class="text-xs text-muted-foreground">Harga Jual JPY</Label>
                                            <Input type="text" inputmode="numeric" :value="fmtInput(form.sell_jpy)" @input="e => { form.sell_jpy = parseInput(e); isSaleIdrManual = false }" placeholder="0" class="h-9 text-sm" />
                                            <InputError :message="form.errors.sell_jpy" />
                                        </div>
                                        <div class="grid gap-1.5">
                                            <Label class="text-xs text-muted-foreground">
                                                Kurs (IDR/JPY)
                                                <span v-if="!isSaleKursManual && exchangeRate > 0" class="ml-1 font-normal text-muted-foreground/60">(auto)</span>
                                                <span v-else-if="isSaleKursManual" class="ml-1 font-normal text-muted-foreground/60">(manual)</span>
                                            </Label>
                                            <Input type="text" inputmode="numeric" :value="fmtInput(form.kurs)" @input="e => { isSaleKursManual = true; form.kurs = parseInput(e) }" placeholder="0" class="h-9 text-sm" />
                                            <InputError :message="form.errors.kurs" />
                                        </div>
                                        <div class="grid gap-1.5">
                                            <Label class="text-xs text-muted-foreground">Harga Jual IDR <span class="font-normal">(auto)</span></Label>
                                            <Input type="text" inputmode="numeric" :value="fmtInput(form.sell_idr)" @input="e => { isSaleIdrManual = true; form.sell_idr = parseInput(e) }" placeholder="0" class="h-9 text-sm" />
                                            <InputError :message="form.errors.sell_idr" />
                                        </div>
                                        <div class="grid gap-1.5">
                                            <Label class="text-xs text-muted-foreground">Fee IDR</Label>
                                            <Input type="text" inputmode="numeric" :value="fmtInput(form.fee)" @input="e => { form.fee = parseInput(e) }" placeholder="0" class="h-9 text-sm" />
                                            <InputError :message="form.errors.fee" />
                                        </div>
                                    </div>

                                    <!-- Preview kalkulasi -->
                                    <div v-if="form.sell_idr" class="rounded-md bg-muted/50 p-3 text-xs space-y-1.5">
                                        <p class="font-semibold text-muted-foreground uppercase tracking-wide mb-1">Perhitungan</p>
                                        <div class="flex justify-between"><span class="text-muted-foreground">Harga Jual IDR:</span><span>{{ fmtIDR(Number(form.sell_idr) || 0) }}</span></div>
                                        <div class="flex justify-between"><span class="text-muted-foreground">Fee:</span><span>{{ fmtIDR(Number(form.fee) || 0) }}</span></div>
                                        <div class="flex justify-between border-t border-border pt-1.5 font-semibold" :class="saleProfitPositive ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                            <span>Estimasi Profit:</span><span>{{ fmtIDR(saleProfit) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <DialogFooter>
                                <Button type="button" variant="outline" @click="showModal = false">Batal</Button>
                                <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Menyimpan...' : 'Simpan' }}</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- DataTable -->
            <div class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                <div class="flex flex-col gap-2 border-b border-border p-4 sm:flex-row sm:items-center sm:justify-between">
                    <Input :model-value="kSearch" @update:model-value="v => { kSearch = String(v); kPage = 1 }" placeholder="Cari code, nama, atau breeder..." class="max-w-sm" />
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted-foreground">Show</span>
                        <select :value="kPerPage" @change="(e) => { kPerPage = Number((e.target as HTMLSelectElement).value); kPage = 1 }" class="rounded-md border border-input bg-background px-3 py-1 text-sm">
                            <option>5</option><option>10</option><option>25</option><option>50</option>
                        </select>
                        <span class="text-sm text-muted-foreground">entries</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
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
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-muted-foreground">{{ kSearch ? 'Tidak ada hasil.' : 'Belum ada data koi.' }}</td>
                            </tr>
                            <tr v-for="koi in paginatedKois" :key="koi.id" class="transition-colors hover:bg-muted/50">
                                <td class="px-4 py-3 text-sm font-medium">{{ koi.code }}</td>
                                <td class="px-4 py-3 text-sm">{{ koi.name }}</td>
                                <td class="px-4 py-3 text-sm">{{ getBreederName(koi) }}</td>
                                <td class="px-4 py-3 text-sm">{{ fmtDate(koi.created_at) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span v-if="koi.sale_status" :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium', statusClass(koi.sale_status)]">
                                        {{ statusLabel(koi.sale_status) }}
                                    </span>
                                    <span v-else class="text-xs text-muted-foreground">-</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
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
    </AppLayout>
</template>
