<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, ref, onMounted } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { useSwal } from '@/composables/useSwal'
import { Search, RotateCcw, Eye, X, Download, ImagePlus, Columns3 } from 'lucide-vue-next'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'

type Breeder  = { id: number; name_breeder: string }
type Customer = { id: number; name: string }
type KoiRow = {
    id: number
    code: string
    sale_invoice: string | null
    sale_tanggal: string | null
    sale_shipped_date: string | null
    sale_status: string | null
    shipping_date: string | null
    shipping_qty: number | null
    shipping_batch: string | null
    shipping_note: string | null
    sale_qty: number | null
    unit_price: string | null
    sell_idr: string | null
    box_qty: number | null
    weight: string | null
    total_weight: string | null
    age: number | null
    size: string | null
    breeder: Breeder | null
    jenis_koi: { id: number; nama_jenis: string } | null
    customer: Customer | null
    description: string | null
    images: { id: number; url: string }[]
}

type Paginator = {
    data: KoiRow[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
    links: { url: string | null; label: string; active: boolean }[]
}

const { fire } = useSwal()
const page      = usePage()
const kois      = computed(() => page.props.kois as Paginator)
const breeders  = computed(() => (page.props.breeders  as Breeder[])  || [])
const customers = computed(() => (page.props.customers as Customer[]) || [])
const isAdmin   = computed(() => page.props.isAdmin as boolean)
const filters   = page.props.filters as Record<string, string>

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Rekap Transaksi', href: '/rekap-transaksi' },
]

// ─── Filter state ─────────────────────────────────────────
const fStatus   = ref<string|null>(filters.status      || null)
const fCode     = ref(filters.code        ?? '')
const fInvoice  = ref(filters.invoice     ?? '')
const fDateFrom = ref(filters.date_from   ?? '')
const fDateTo   = ref(filters.date_to     ?? '')
const fBreeder  = ref<string|null>(filters.breeder_id  ? filters.breeder_id  : null)
const fCustomer = ref<string|null>(filters.customer_id ? filters.customer_id : null)
const fPerPage  = ref(filters.per_page    ?? '50')

// ─── Search filters ───────────────────────────────────────
const statusSearch   = ref('')
const breederSearch  = ref('')
const customerSearch = ref('')

// ─── Selection state ──────────────────────────────────────
const selectedIds = ref<Set<number>>(new Set())

// ─── Photo modal ──────────────────────────────────────────
const showPhotoModal  = ref(false)
const photoModalImages = ref<{ id: number; url: string }[]>([])
const photoModalCode   = ref('')

function openPhotoModal(koi: KoiRow) {
    photoModalImages.value = koi.images ?? []
    photoModalCode.value   = koi.code
    showPhotoModal.value   = true
}

// ─── Select options ───────────────────────────────────────
const statusOptions = [
    { value: 'pending',       label: 'Pending' },
    { value: 'shipped',       label: 'Shipped' },
    { value: 'azukari',       label: 'Azukari' },
    { value: 'dead',          label: 'Dead' },
    { value: 'cancel',        label: 'Cancel' },
    { value: 'refund',        label: 'Refund' },
    { value: 'dead_refund',   label: 'Dead Refund' },
    { value: 'cancel_refund', label: 'Cancel Refund' },
]

const filteredStatusOptions = computed(() =>
    !statusSearch.value ? statusOptions : statusOptions.filter(s => s.label.toLowerCase().includes(statusSearch.value.toLowerCase()))
)

const breederOptions = computed(() =>
    breeders.value.map(b => ({ value: String(b.id), label: b.name_breeder }))
)

const filteredBreederOptions = computed(() =>
    !breederSearch.value ? breederOptions.value : breederOptions.value.filter(b => b.label.toLowerCase().includes(breederSearch.value.toLowerCase()))
)

const customerOptions = computed(() =>
    customers.value.map(c => ({ value: String(c.id), label: c.name }))
)

const filteredCustomerOptions = computed(() =>
    !customerSearch.value ? customerOptions.value : customerOptions.value.filter(c => c.label.toLowerCase().includes(customerSearch.value.toLowerCase()))
)

const perPageOptions = [
    { value: '25',  label: '25' },
    { value: '50',  label: '50' },
    { value: '100', label: '100' },
]

// ─── Actions ─────────────────────────────────────────────
function applyFilter() {
    router.get('/rekap-transaksi', {
        status:      fStatus.value    || undefined,
        code:        fCode.value      || undefined,
        invoice:     fInvoice.value   || undefined,
        date_from:   fDateFrom.value  || undefined,
        date_to:     fDateTo.value    || undefined,
        breeder_id:  fBreeder.value   || undefined,
        customer_id: fCustomer.value  || undefined,
        per_page:    fPerPage.value   || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilter() {
    fStatus.value = null; fCode.value = ''; fInvoice.value = ''
    fDateFrom.value = ''; fDateTo.value = ''
    fBreeder.value = null; fCustomer.value = null
    fPerPage.value = '50'
    router.get('/rekap-transaksi', {}, { preserveState: false, replace: true })
}

function goTo(url: string | null) { if (url) router.visit(url) }

function changePerPage(val: string | null) {
    if (val) { fPerPage.value = val; applyFilter() }
}

// ─── Selection functions ──────────────────────────────────
const isAllSelected = computed(() => kois.value.data.length > 0 && kois.value.data.every(k => selectedIds.value.has(k.id)))
const isSomeSelected = computed(() => selectedIds.value.size > 0)
const selectedCount = computed(() => selectedIds.value.size)

function toggleSelect(id: number) {
    if (selectedIds.value.has(id)) {
        selectedIds.value.delete(id)
    } else {
        selectedIds.value.add(id)
    }
    selectedIds.value = new Set(selectedIds.value)
}

function toggleSelectAll() {
    if (isAllSelected.value) {
        kois.value.data.forEach(k => selectedIds.value.delete(k.id))
    } else {
        kois.value.data.forEach(k => selectedIds.value.add(k.id))
    }
    selectedIds.value = new Set(selectedIds.value)
}

function clearSelection() {
    selectedIds.value.clear()
    selectedIds.value = new Set(selectedIds.value)
}

function downloadPdf() {
    const params = new URLSearchParams()
    Array.from(selectedIds.value).forEach(id => params.append('ids[]', String(id)))
    window.open('/rekap-transaksi/export-pdf?' + params.toString(), '_blank')
}

function downloadInvoicePdf() {
    // Ambil invoice number dari koi yang dipilih (harus 1 invoice)
    const selectedKois = (kois.value.data ?? []).filter((k: any) => selectedIds.value.has(k.id))
    const invoiceNumbers = [...new Set(selectedKois.map((k: any) => k.sale_invoice).filter(Boolean))]
    if (invoiceNumbers.length === 0) {
        fire({ icon: 'warning', title: 'Tidak ada invoice', text: 'Koi yang dipilih tidak memiliki nomor invoice.' })
        return
    }
    if (invoiceNumbers.length > 1) {
        fire({ icon: 'warning', title: 'Invoice berbeda', text: 'Pilih koi dengan nomor invoice yang sama untuk download invoice PDF.' })
        return
    }
    window.open('/rekap-transaksi/invoice-pdf?invoice=' + encodeURIComponent(invoiceNumbers[0]!), '_blank')
}

function downloadInvoiceExcel() {
    const selectedKois = (kois.value.data ?? []).filter((k: any) => selectedIds.value.has(k.id))
    const invoiceNumbers = [...new Set(selectedKois.map((k: any) => k.sale_invoice).filter(Boolean))]
    if (invoiceNumbers.length === 0) {
        fire({ icon: 'warning', title: 'Tidak ada invoice', text: 'Koi yang dipilih tidak memiliki nomor invoice.' })
        return
    }
    if (invoiceNumbers.length > 1) {
        fire({ icon: 'warning', title: 'Invoice berbeda', text: 'Pilih koi dengan nomor invoice yang sama untuk export Excel.' })
        return
    }
    window.open('/rekap-transaksi/invoice-excel?invoice=' + encodeURIComponent(invoiceNumbers[0]!), '_blank')
}

// ─── Display helpers ──────────────────────────────────────
const STATUS_LABELS: Record<string, string> = {
    pending: 'Pending', shipped: 'Shipped', azukari: 'Azukari', dead: 'Dead',
    cancel: 'Cancel', refund: 'Refund', dead_refund: 'Dead Refund', cancel_refund: 'Cancel Refund',
}
const STATUS_COLORS: Record<string, string> = {
    pending:       'bg-yellow-100 text-yellow-800',
    shipped:       'bg-blue-100 text-blue-800',
    azukari:       'bg-green-100 text-green-800',
    dead:          'bg-red-100 text-red-800',
    cancel:        'bg-gray-100 text-gray-700',
    refund:        'bg-orange-100 text-orange-800',
    dead_refund:   'bg-red-200 text-red-900',
    cancel_refund: 'bg-purple-100 text-purple-800',
}

const fmtJpy = (v: string | number | null) =>
    v != null && v !== '' ? '¥' + Number(v).toLocaleString('ja-JP') : '-'
const fmtIDR = (v: string | number | null) =>
    v != null && v !== '' ? new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(v)) : '-'

const fmtDate = (d: string | null) =>
    d ? new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' }) : '-'

const pageLinks = computed(() => kois.value.links.slice(1, kois.value.links.length - 1))

// ─── Column visibility ────────────────────────────────────
const STORAGE_KEY = 'rekap-transaksi-cols'

type ColKey = 'foto' | 'invoice' | 'kuj_code' | 'sales_date' | 'status' | 'shipping_date' | 'shipping_batch' | 'shipping_note' | 'customer' | 'breeder' | 'description' | 'age' | 'size' | 'koi_qty' | 'unit_price' | 'sales_price' | 'box_qty' | 'weight' | 'total_weight'

const allColumns: { key: ColKey; label: string; adminOnly?: boolean }[] = [
    { key: 'foto',          label: 'Foto' },
    { key: 'invoice',       label: 'Invoice' },
    { key: 'kuj_code',      label: 'KUJ Code' },
    { key: 'sales_date',    label: 'Sales Date' },
    { key: 'status',        label: 'Status' },
    { key: 'shipping_date',  label: 'Shipping Date' },
    { key: 'shipping_batch', label: 'Shipping Batch', adminOnly: true },
    { key: 'shipping_note',  label: 'Shipping Note', adminOnly: true },
    { key: 'customer',      label: 'Customer', adminOnly: true },
    { key: 'breeder',       label: 'Breeder' },
    { key: 'description',   label: 'Description' },
    { key: 'age',           label: 'Age' },
    { key: 'size',          label: 'Size' },
    { key: 'koi_qty',       label: 'Koi QTY' },
    { key: 'unit_price',    label: 'Unit Price (JPY)' },
    { key: 'sales_price',   label: 'Sales Price (JPY)' },
    { key: 'box_qty',       label: 'Box QTY' },
    { key: 'weight',        label: 'Weight (kg)' },
    { key: 'total_weight',  label: 'Total Weight (kg)' },
]

const defaultVisible: ColKey[] = ['foto', 'invoice', 'kuj_code', 'sales_date', 'status', 'shipping_date', 'customer', 'breeder', 'description', 'age', 'size', 'koi_qty', 'unit_price', 'sales_price', 'box_qty', 'weight', 'total_weight']

const visibleCols = ref<Set<ColKey>>(new Set(defaultVisible))
const showColPicker = ref(false)

onMounted(() => {
    try {
        const saved = localStorage.getItem(STORAGE_KEY)
        if (saved) visibleCols.value = new Set(JSON.parse(saved) as ColKey[])
    } catch {}
})

function toggleCol(key: ColKey) {
    const s = new Set(visibleCols.value)
    s.has(key) ? s.delete(key) : s.add(key)
    visibleCols.value = s
    localStorage.setItem(STORAGE_KEY, JSON.stringify([...s]))
}

const col = (key: ColKey) => visibleCols.value.has(key)

const visibleColCount = computed(() => {
    let n = 2 // checkbox + aksi always shown
    allColumns.forEach(c => {
        if (c.adminOnly && !isAdmin.value) return
        if (col(c.key)) n++
    })
    return n
})

// Color-group rows that share the same shipping_batch (same box)
const SHIPPING_GROUP_COLORS = [
    'bg-blue-50 dark:bg-blue-950/30',
    'bg-amber-50 dark:bg-amber-950/30',
    'bg-green-50 dark:bg-green-950/30',
    'bg-purple-50 dark:bg-purple-950/30',
    'bg-rose-50 dark:bg-rose-950/30',
]

const shippingGroupColorMap = computed(() => {
    const map = new Map<string, string>()
    let colorIndex = 0
    for (const koi of kois.value.data) {
        if (!koi.shipping_batch) continue
        if (!map.has(koi.shipping_batch)) {
            map.set(koi.shipping_batch, SHIPPING_GROUP_COLORS[colorIndex % SHIPPING_GROUP_COLORS.length])
            colorIndex++
        }
    }
    return map
})

function shippingGroupColor(koi: KoiRow): string {
    if (!koi.shipping_batch) return ''
    return shippingGroupColorMap.value.get(koi.shipping_batch) ?? ''
}
</script>

<template>
    <Head title="Rekap Transaksi"/>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4">

            <!-- ── Filters ── -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-2">

                <!-- Status -->
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold text-muted-foreground">Status:</label>
                    <Select v-model="fStatus">
                        <SelectTrigger class="w-full"><SelectValue placeholder="Semua Status" /></SelectTrigger>
                        <SelectContent class="p-0 w-[--radix-select-trigger-width]">
                            <div class="sticky top-0 bg-background p-2 border-b">
                                <Input v-model="statusSearch" placeholder="Cari status..." class="h-8" @keydown.stop />
                            </div>
                            <div class="max-h-44 overflow-y-auto">
                                <div v-if="filteredStatusOptions.length === 0" class="px-3 py-2 text-sm text-muted-foreground">Tidak ada status</div>
                                <SelectItem v-for="s in filteredStatusOptions" :key="s.value" :value="s.value">{{ s.label }}</SelectItem>
                            </div>
                        </SelectContent>
                    </Select>
                </div>

                <!-- CODE -->
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold text-muted-foreground">CODE:</label>
                    <Input v-model="fCode" class="h-[38px] text-sm" @keyup.enter="applyFilter" />
                </div>

                <!-- Invoice -->
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold text-muted-foreground">Invoice:</label>
                    <Input v-model="fInvoice" class="h-[38px] text-sm" @keyup.enter="applyFilter" />
                </div>

                <!-- Sales Date -->
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold text-muted-foreground">Sales Date:</label>
                    <div class="flex items-center gap-1">
                        <Input v-model="fDateFrom" type="date" class="h-[38px] text-sm flex-1" />
                        <span class="text-muted-foreground text-xs">~</span>
                        <Input v-model="fDateTo" type="date" class="h-[38px] text-sm flex-1" />
                    </div>
                </div>

                <!-- Breeder -->
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold text-muted-foreground">Breeder:</label>
                    <Select v-model="fBreeder">
                        <SelectTrigger class="w-full"><SelectValue placeholder="Semua Breeder" /></SelectTrigger>
                        <SelectContent class="p-0 w-[--radix-select-trigger-width]">
                            <div class="sticky top-0 bg-background p-2 border-b">
                                <Input v-model="breederSearch" placeholder="Cari breeder..." class="h-8" @keydown.stop />
                            </div>
                            <div class="max-h-44 overflow-y-auto">
                                <div v-if="filteredBreederOptions.length === 0" class="px-3 py-2 text-sm text-muted-foreground">Tidak ada breeder</div>
                                <SelectItem v-for="b in filteredBreederOptions" :key="b.value" :value="b.value">{{ b.label }}</SelectItem>
                            </div>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Customer (admin only) -->
                <div v-if="isAdmin" class="flex flex-col gap-1">
                    <label class="text-xs font-semibold text-muted-foreground">Customer:</label>
                    <Select v-model="fCustomer">
                        <SelectTrigger class="w-full"><SelectValue placeholder="Semua Customer" /></SelectTrigger>
                        <SelectContent class="p-0 w-[--radix-select-trigger-width]">
                            <div class="sticky top-0 bg-background p-2 border-b">
                                <Input v-model="customerSearch" placeholder="Cari customer..." class="h-8" @keydown.stop />
                            </div>
                            <div class="max-h-44 overflow-y-auto">
                                <div v-if="filteredCustomerOptions.length === 0" class="px-3 py-2 text-sm text-muted-foreground">Tidak ada customer</div>
                                <SelectItem v-for="c in filteredCustomerOptions" :key="c.value" :value="c.value">{{ c.label }}</SelectItem>
                            </div>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Tombol Reset + Filter -->
                <div class="flex items-end gap-2 col-span-2 md:col-span-1">
                    <button @click="resetFilter"
                        class="inline-flex items-center gap-1.5 rounded-md border border-input bg-background px-3 py-1.5 text-sm font-medium hover:bg-muted transition h-[38px]">
                        <RotateCcw class="w-3.5 h-3.5" /> Reset
                    </button>
                    <button @click="applyFilter"
                        class="inline-flex items-center gap-1.5 rounded-md bg-black hover:bg-gray-900 text-white px-4 py-1.5 text-sm font-medium transition h-[38px]">
                        <Search class="w-3.5 h-3.5" /> Filter
                    </button>
                </div>
            </div>

            <!-- ── Results bar ── -->
            <div class="flex flex-wrap items-center gap-3">
                <p class="text-xs text-muted-foreground">
                    Results: <span class="font-semibold text-foreground">{{ kois.total }}</span>
                </p>
                <div class="flex items-center gap-1.5">
                    <span class="text-xs text-muted-foreground">Show</span>
                    <div class="w-20">
                        <Select :model-value="fPerPage" @update:model-value="changePerPage">
                            <SelectTrigger class="w-full"><SelectValue placeholder="50" /></SelectTrigger>
                            <SelectContent class="w-[--radix-select-trigger-width]">
                                <SelectItem v-for="p in perPageOptions" :key="p.value" :value="p.value">{{ p.label }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <span class="text-xs text-muted-foreground">entries</span>
                </div>

                <!-- Column picker -->
                <div class="relative ml-auto">
                    <button @click="showColPicker = !showColPicker"
                        class="inline-flex items-center gap-1.5 rounded-md border border-input bg-background px-3 py-1.5 text-xs font-medium hover:bg-muted transition h-[32px]">
                        <Columns3 class="w-3.5 h-3.5" /> Kolom
                    </button>
                    <div v-if="showColPicker"
                        class="absolute right-0 top-9 z-50 w-56 rounded-lg border border-border bg-background shadow-lg p-3 space-y-1.5">
                        <p class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider mb-2">Tampilkan Kolom</p>
                        <template v-for="c in allColumns" :key="c.key">
                            <label v-if="!c.adminOnly || isAdmin" class="flex items-center gap-2 cursor-pointer select-none rounded px-1 py-0.5 hover:bg-muted">
                                <input type="checkbox" :checked="col(c.key)" @change="toggleCol(c.key)"
                                    class="w-3.5 h-3.5 rounded border-border" />
                                <span class="text-xs">{{ c.label }}</span>
                            </label>
                        </template>
                    </div>
                </div>
            </div>

            <!-- backdrop to close col picker -->
            <div v-if="showColPicker" class="fixed inset-0 z-40" @click="showColPicker = false" />

            <!-- ── Selection action bar ── -->
            <div v-if="isSomeSelected" class="flex flex-wrap items-center gap-2 rounded-lg border border-border bg-muted/50 p-4">
                <span class="text-sm font-medium">{{ selectedCount }} item{{ selectedCount > 1 ? 's' : '' }} dipilih</span>
                <div class="flex gap-2">
                    <Button size="sm" variant="outline" @click="downloadPdf">
                        <Download class="w-4 h-4 mr-1" /> Rekap PDF
                    </Button>
                    <Button size="sm" variant="outline" @click="downloadInvoicePdf">
                        <Download class="w-4 h-4 mr-1" /> Invoice PDF
                    </Button>
                    <Button size="sm" variant="outline" @click="downloadInvoiceExcel">
                        <Download class="w-4 h-4 mr-1" /> Invoice Excel
                    </Button>
                    <Button size="sm" variant="outline" @click="clearSelection">
                        <X class="w-4 h-4 mr-1" /> Batal
                    </Button>
                </div>
            </div>

            <!-- ── Table ── -->
            <div class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="w-12 px-4 py-3 text-center">
                                    <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" class="w-4 h-4 rounded border-border" />
                                </th>
                                <th v-if="col('foto')"          class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground">Foto</th>
                                <th v-if="col('invoice')"       class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Invoice</th>
                                <th v-if="col('kuj_code')"      class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">KUJ CODE</th>
                                <th v-if="col('sales_date')"    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Sales Date</th>
                                <th v-if="col('status')"        class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground">Status</th>
                                <th v-if="col('shipping_date')"               class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Shipping Date</th>
                                <th v-if="isAdmin && col('shipping_batch')"   class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Shipping Batch</th>
                                <th v-if="isAdmin && col('shipping_note')"    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Shipping Note</th>
                                <th v-if="isAdmin && col('customer')"  class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Customer</th>
                                <th v-if="col('breeder')"       class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Breeder</th>
                                <th v-if="col('description')"   class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Description</th>
                                <th v-if="col('age')"           class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground">Age</th>
                                <th v-if="col('size')"          class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground">Size</th>
                                <th v-if="col('koi_qty')"       class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground">Koi QTY</th>
                                <th v-if="col('unit_price')"    class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Unit Price (JPY)</th>
                                <th v-if="col('sales_price')"   class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Sales Price (JPY)</th>
                                <th v-if="col('box_qty')"       class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground">Box QTY</th>
                                <th v-if="col('weight')"        class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Weight (kg)</th>
                                <th v-if="col('total_weight')"  class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Total Weight (kg)</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-background">
                            <tr v-if="kois.data.length === 0">
                                <td :colspan="visibleColCount" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                    Tidak ada data transaksi.
                                </td>
                            </tr>
                            <tr v-for="koi in kois.data" :key="koi.id" :class="['transition-colors hover:bg-muted/50', shippingGroupColor(koi)]">
                                <td class="w-12 px-4 py-3 text-center">
                                    <input type="checkbox" :checked="selectedIds.has(koi.id)" @change="toggleSelect(koi.id)" class="w-4 h-4 rounded border-border" />
                                </td>
                                <td v-if="col('foto')" class="px-4 py-3 text-center">
                                    <button type="button" @click="openPhotoModal(koi)"
                                        class="relative h-9 w-9 overflow-hidden rounded-lg border border-border bg-muted mx-auto flex items-center justify-center hover:ring-2 hover:ring-primary/40 transition-all">
                                        <img v-if="koi.images?.length" :src="koi.images[0].url" class="h-full w-full object-cover" />
                                        <ImagePlus v-else class="w-4 h-4 text-muted-foreground" />
                                    </button>
                                </td>
                                <td v-if="col('invoice')"       class="px-4 py-3 text-sm font-mono text-muted-foreground whitespace-nowrap">{{ koi.sale_invoice ?? '-' }}</td>
                                <td v-if="col('kuj_code')"      class="px-4 py-3 text-sm font-medium whitespace-nowrap">{{ koi.code }}</td>
                                <td v-if="col('sales_date')"    class="px-4 py-3 text-sm whitespace-nowrap">{{ fmtDate(koi.sale_tanggal) }}</td>
                                <td v-if="col('status')"        class="px-4 py-3 text-center">
                                    <span v-if="koi.sale_status"
                                        :class="`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap ${STATUS_COLORS[koi.sale_status] ?? 'bg-gray-100 text-gray-700'}`">
                                        {{ STATUS_LABELS[koi.sale_status] ?? koi.sale_status }}
                                    </span>
                                    <span v-else class="text-xs text-muted-foreground">-</span>
                                </td>
                                <td v-if="col('shipping_date')" class="px-4 py-3 text-sm whitespace-nowrap">{{ fmtDate(koi.shipping_date) }}</td>
                                <td v-if="isAdmin && col('shipping_batch')" class="px-4 py-3 text-sm whitespace-nowrap">
                                    <span v-if="koi.shipping_batch"
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-300">
                                        {{ koi.shipping_batch }}
                                    </span>
                                    <span v-else class="text-xs text-muted-foreground">-</span>
                                </td>
                                <td v-if="isAdmin && col('shipping_note')" class="px-4 py-3 text-sm max-w-[160px]">
                                    <span v-if="koi.shipping_note" class="line-clamp-2 text-xs text-muted-foreground">{{ koi.shipping_note }}</span>
                                    <span v-else class="text-xs text-muted-foreground">-</span>
                                </td>
                                <td v-if="isAdmin && col('customer')"  class="px-4 py-3 text-sm whitespace-nowrap">{{ koi.customer?.name ?? '-' }}</td>
                                <td v-if="col('breeder')"       class="px-4 py-3 text-sm whitespace-nowrap">{{ koi.breeder?.name_breeder ?? '-' }}</td>
                                <td v-if="col('description')"   class="px-4 py-3 text-sm max-w-[180px]">
                                    <span v-if="koi.description" class="line-clamp-2 text-xs text-muted-foreground">{{ koi.description }}</span>
                                    <span v-else class="text-xs text-muted-foreground">-</span>
                                </td>
                                <td v-if="col('age')"           class="px-4 py-3 text-center text-sm">{{ koi.age != null ? koi.age + ' th' : '-' }}</td>
                                <td v-if="col('size')"          class="px-4 py-3 text-center text-sm">{{ koi.size != null ? Number(koi.size).toFixed(0) + ' cm' : '-' }}</td>
                                <td v-if="col('koi_qty')"       class="px-4 py-3 text-center text-sm">{{ koi.sale_qty ?? '-' }}</td>
                                <td v-if="col('unit_price')"    class="px-4 py-3 text-right text-sm whitespace-nowrap">{{ fmtJpy(koi.unit_price) }}</td>
                                <td v-if="col('sales_price')"   class="px-4 py-3 text-right text-sm whitespace-nowrap">{{ fmtJpy(koi.unit_price != null && koi.sale_qty != null ? Number(koi.unit_price) * Number(koi.sale_qty) : null) }}</td>
                                <td v-if="col('box_qty')"       class="px-4 py-3 text-center text-sm">{{ koi.box_qty ?? '-' }}</td>
                                <td v-if="col('weight')"        class="px-4 py-3 text-right text-sm whitespace-nowrap">{{ koi.weight != null ? parseFloat(Number(koi.weight).toFixed(3)) + ' kg' : '-' }}</td>
                                <td v-if="col('total_weight')"  class="px-4 py-3 text-right text-sm whitespace-nowrap">
                                    {{
                                        koi.total_weight != null
                                            ? parseFloat(Number(koi.total_weight).toFixed(3)) + ' kg'
                                            : (koi.weight != null && koi.box_qty != null)
                                                ? parseFloat((Number(koi.weight) * Number(koi.box_qty)).toFixed(3)) + ' kg'
                                                : '-'
                                    }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a :href="`/rekap-transaksi/${koi.id}`"
                                        class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background p-2 text-foreground shadow-sm hover:bg-muted"
                                        title="Lihat Detail">
                                        <Eye :size="16" />
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ── Pagination bottom ── -->
                <div class="flex flex-col items-center justify-between gap-2 border-t border-border px-4 py-3 sm:flex-row">
                    <p class="text-sm text-muted-foreground">
                        Showing {{ kois.from ?? 0 }} to {{ kois.to ?? 0 }} of {{ kois.total }} entries
                    </p>
                    <div class="flex items-center gap-1">
                        <Button variant="outline" size="sm" :disabled="!kois.links[0]?.url" @click="goTo(kois.links[0]?.url ?? null)">Previous</Button>
                        <template v-for="link in pageLinks" :key="link.label">
                            <Button size="sm" :variant="link.active ? 'default' : 'outline'" :disabled="!link.url" @click="goTo(link.url)" v-html="link.label" />
                        </template>
                        <Button variant="outline" size="sm" :disabled="!kois.links[kois.links.length - 1]?.url" @click="goTo(kois.links[kois.links.length - 1]?.url ?? null)">Next</Button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Photo Modal -->
        <Dialog v-model:open="showPhotoModal">
            <DialogContent class="sm:max-w-[560px] max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Foto Koi — {{ photoModalCode }}</DialogTitle>
                </DialogHeader>
                <div v-if="photoModalImages.length > 0" class="grid grid-cols-2 gap-3 mt-2">
                    <div v-for="img in photoModalImages" :key="img.id"
                        class="aspect-square overflow-hidden rounded-lg border border-border">
                        <img :src="img.url" class="h-full w-full object-cover" />
                    </div>
                </div>
                <div v-else class="py-12 flex flex-col items-center gap-2 text-muted-foreground">
                    <ImagePlus class="w-10 h-10 opacity-40" />
                    <p class="text-sm">Tidak ada foto</p>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

