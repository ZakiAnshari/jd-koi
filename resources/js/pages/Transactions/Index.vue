<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref, watch, onUnmounted } from 'vue'
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
import { Badge } from '@/components/ui/badge'

// ─── Types ──────────────────────────────────────────────
type Koi = {
    id: number
    code: string
    name: string
}

type Customer = {
    id: number
    name: string
}

type Transaction = {
    id: number
    koi_id: number
    customer_id: number
    date: string
    status: string
    qty: number
    sell_jpy: number
    sell_idr: number
    margin: number
    fee: number
    profit: number
    created_at: string
    koi?: Koi
    customer?: Customer
}

// ─── Page Props ─────────────────────────────────────────
const page = usePage()
const transactions = computed(() => (page.props.transactions as { data: Transaction[] } | null)?.data ?? [])
const kois = computed(() => (page.props.kois as Koi[] | null) ?? [])
const customers = computed(() => (page.props.customers as Customer[] | null) ?? [])
const koiSummaries = computed(() =>
    (page.props.koiSummaries as Record<number, { modal_jpy: number; modal_idr: number; available_qty: number }> | null) ?? {},
)

const exchangeRate = ref<number>(0)
const exchangeRateLoading = ref(false)
const exchangeRateError = ref(false)
const exchangeRateManual = ref(false)

async function fetchExchangeRate() {
    exchangeRateLoading.value = true
    exchangeRateError.value = false

    try {
        const response = await fetch('https://api.exchangerate-api.com/v4/latest/JPY')
        if (!response.ok) throw new Error(`HTTP ${response.status}`)

        const data = await response.json()
        if (data?.rates?.IDR) {
            exchangeRate.value = Math.round(Number(data.rates.IDR))
        } else {
            throw new Error('Rate IDR tidak ditemukan')
        }
    } catch (error) {
        console.error('Gagal fetch kurs JPY→IDR:', error)
        exchangeRateError.value = true
        const fallback = page.props.exchange_rate as number | undefined
        exchangeRate.value = fallback ?? 110
    } finally {
        exchangeRateLoading.value = false
    }
}

fetchExchangeRate()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Transaksi Penjualan', href: '/penjualan' },
]

// ─── Status Config ──────────────────────────────────────
const statusOptions = [
    { value: 'azukari', label: 'Azukari' },
    { value: 'cancel', label: 'Cancel' },
    { value: 'cancel_dead', label: 'Cancel Dead' },
    { value: 'mudpond_hiroshima', label: 'Mudpond Hiroshima' },
    { value: 'na', label: 'Na' },
    { value: 'oyagoi', label: 'Oyagoi' },
    { value: 'pending', label: 'Pending' },
    { value: 'shipped', label: 'Shipped' },
    { value: 'sold', label: 'Sold' },
]

// Status yang BUTUH isi Sell JPY, Sell IDR, Margin
const statusNeedsSellFields = ['azukari', 'sold', 'shipped'] as const

function getStatusVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status?.toLowerCase()) {
        case 'sold': return 'default'
        case 'shipped': return 'default'
        case 'azukari': return 'secondary'
        case 'pending': return 'outline'
        case 'mudpond_hiroshima': return 'secondary'
        case 'oyagoi': return 'secondary'
        case 'na': return 'outline'
        case 'cancel': return 'destructive'
        case 'cancel_dead': return 'destructive'
        default: return 'outline'
    }
}

function getStatusLabel(status: string): string {
    return statusOptions.find((s) => s.value === status?.toLowerCase())?.label ?? status ?? '-'
}

// ─── Computed: apakah status saat ini butuh field harga ─
const needsSellFields = computed(() =>
    statusNeedsSellFields.includes(form.status?.toLowerCase() as typeof statusNeedsSellFields[number]),
)

// ─── DataTable State ────────────────────────────────────
const search = ref('')
const sortKey = ref<keyof Transaction>('date')
const sortOrder = ref<'asc' | 'desc'>('desc')
const currentPage = ref(1)
const perPage = ref(10)

const filteredTransactions = computed(() => {
    let result = transactions.value

    if (search.value) {
        const q = search.value.toLowerCase()
        result = result.filter((t) => {
            const koiCode = t.koi?.code?.toLowerCase() ?? ''
            const koiName = t.koi?.name?.toLowerCase() ?? ''
            const customerName = t.customer?.name?.toLowerCase() ?? ''
            const status = t.status?.toLowerCase() ?? ''
            return koiCode.includes(q) || koiName.includes(q) || customerName.includes(q) || status.includes(q)
        })
    }

    return [...result].sort((a, b) => {
        const aVal = a[sortKey.value]
        const bVal = b[sortKey.value]
        if (aVal == null || bVal == null) return 0
        if (typeof aVal === 'string' && typeof bVal === 'string') {
            return sortOrder.value === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal)
        }
        if (typeof aVal === 'number' && typeof bVal === 'number') {
            return sortOrder.value === 'asc' ? aVal - bVal : bVal - aVal
        }
        return 0
    })
})

const totalPages = computed(() => Math.ceil(filteredTransactions.value.length / perPage.value) || 1)

watch(totalPages, (newTotal) => {
    if (currentPage.value > newTotal) currentPage.value = 1
})

const paginatedTransactions = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return filteredTransactions.value.slice(start, start + perPage.value)
})

const showingFrom = computed(() =>
    filteredTransactions.value.length === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1,
)
const showingTo = computed(() =>
    Math.min(currentPage.value * perPage.value, filteredTransactions.value.length),
)

function onSearch(value: string | number) {
    search.value = String(value)
    currentPage.value = 1
}

function toggleSort(key: keyof Transaction) {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortKey.value = key
        sortOrder.value = 'asc'
    }
}

function sortIcon(key: keyof Transaction) {
    if (sortKey.value !== key) return '↕'
    return sortOrder.value === 'asc' ? '↑' : '↓'
}

// ─── Modal + Form ───────────────────────────────────────
const showModal = ref(false)
const koiSearch = ref('')
const customerSearch = ref('')
const isSellIdrEdited = ref(false)

watch(showModal, (isOpen) => {
    if (!isOpen) {
        koiSearch.value = ''
        customerSearch.value = ''
        isSellIdrEdited.value = false
    }
})

const filteredKois = computed(() => {
    if (!koiSearch.value) return kois.value
    const q = koiSearch.value.toLowerCase()
    return kois.value.filter((k) => k.code.toLowerCase().includes(q) || k.name.toLowerCase().includes(q))
})

const filteredCustomers = computed(() => {
    if (!customerSearch.value) return customers.value
    const q = customerSearch.value.toLowerCase()
    return customers.value.filter((c) => c.name.toLowerCase().includes(q))
})

// ─── Currency Helpers ───────────────────────────────────
function formatCurrencyDisplay(value: string | number | undefined | null): string {
    if (value === undefined || value === null || value === '') return ''
    const str = String(value).replace(/[^\d-]/g, '')
    if (!str || str === '-') return str
    const isNegative = str.startsWith('-')
    const absNum = isNegative ? str.slice(1) : str
    if (!absNum) return ''
    const formatted = absNum.replace(/\B(?=(\d{3})+(?!\d))/g, '.')
    return isNegative ? '-' + formatted : formatted
}

function parseCurrencyInput(value: string): string {
    if (!value) return ''
    return value.replace(/\./g, '')
}

function formatJPY(value: number | string | undefined | null): string {
    if (value === undefined || value === null || value === '') return '¥ 0'
    const num = Number(value)
    if (isNaN(num)) return '¥ 0'
    return '¥ ' + num.toLocaleString('en-US')
}

function formatIDR(value: number | string | undefined | null): string {
    if (value === undefined || value === null || value === '') return 'Rp 0'
    const num = Number(value)
    if (isNaN(num)) return 'Rp 0'
    return 'Rp ' + num.toLocaleString('id-ID')
}

function formatPercent(value: number | string | undefined | null): string {
    if (value === undefined || value === null || value === '') return '0%'
    const num = Number(value)
    if (isNaN(num)) return '0%'
    return num + '%'
}

function formatDate(dateStr: string): string {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    })
}

// ─── Form ───────────────────────────────────────────────
const form = useForm({
    date: new Date().toISOString().split('T')[0],
    status: 'sold' as string,
    koi_id: '' as string,
    customer_id: '' as string,
    qty: '' as string | number,
    sell_jpy: '' as string | number,
    sell_idr: '' as string | number,
    margin: '' as string | number,
    fee: '' as string | number,
    profit: '' as string | number,
})

// Reset field harga saat status berubah ke yang tidak butuh
watch(() => form.status, (newStatus) => {
    const needs = statusNeedsSellFields.includes(newStatus?.toLowerCase() as typeof statusNeedsSellFields[number])
    if (!needs) {
        form.sell_jpy = ''
        form.sell_idr = ''
        form.margin = ''
        form.fee = '0'
        form.profit = '0'
        isSellIdrEdited.value = false
    }
})

// ─── Koi Summary ────────────────────────────────────────
const selectedKoiSummary = computed(() => {
    const koiId = Number(form.koi_id)
    if (!koiId) return { modal_jpy: 0, modal_idr: 0, available_qty: 0 }
    return koiSummaries.value[koiId] ?? { modal_jpy: 0, modal_idr: 0, available_qty: 0 }
})

const stockJpy = computed(() => selectedKoiSummary.value.modal_jpy)
const stockIdr = computed(() => selectedKoiSummary.value.modal_idr)
const availableStock = computed(() => selectedKoiSummary.value.available_qty)

// ─── Auto-compute Fee & Profit ──────────────────────────
const computedFee = computed(() => {
    if (!needsSellFields.value) return 0
    const sellIdr = Number(parseCurrencyInput(String(form.sell_idr ?? '')))
    const margin = Number(form.margin)
    if (isNaN(sellIdr) || isNaN(margin)) return 0
    return Math.round(sellIdr * (margin / 100))
})

const statusWithProfit = ['azukari', 'sold', 'shipped'] as const

const computedProfit = computed(() => {
    if (!needsSellFields.value) return 0
    const status = form.status?.toLowerCase() ?? ''
    const sellIdr = Number(parseCurrencyInput(String(form.sell_idr ?? '')))
    const modalIdr = stockIdr.value
    const fee = computedFee.value

    switch (status) {
        case 'azukari':
            return fee
        case 'sold':
            return Math.round(modalIdr)
        case 'shipped':
            return Math.round((sellIdr - modalIdr) + fee)
        default:
            return 0
    }
})

const isStatusWithProfit = computed(() =>
    statusWithProfit.includes(form.status?.toLowerCase() as typeof statusWithProfit[number]),
)

watch([computedFee, computedProfit], ([feeVal, profitVal]) => {
    form.fee = String(feeVal)
    form.profit = String(profitVal)
})

// ─── Auto-calc Sell IDR = Sell JPY × Kurs ───────────────
watch(
    [() => form.sell_jpy, exchangeRate],
    ([sellJpyRaw]) => {
        if (isSellIdrEdited.value) return
        if (!needsSellFields.value) return
        const sellJpy = Number(parseCurrencyInput(String(sellJpyRaw ?? '')))
        if (!isNaN(sellJpy) && sellJpy > 0) {
            form.sell_idr = String(Math.round(sellJpy * exchangeRate.value))
        } else {
            form.sell_idr = ''
        }
    },
)

// ─── Submit ─────────────────────────────────────────────
const submit = () => {
    form.transform((data) => ({
        ...data,
        qty: Number(data.qty),
        sell_jpy: needsSellFields.value ? Number(parseCurrencyInput(String(data.sell_jpy ?? ''))) : 0,
        sell_idr: needsSellFields.value ? Number(parseCurrencyInput(String(data.sell_idr ?? ''))) : 0,
        margin: needsSellFields.value ? Number(data.margin) : 0,
        fee: needsSellFields.value ? Number(parseCurrencyInput(String(data.fee ?? ''))) : 0,
        profit: needsSellFields.value ? Number(parseCurrencyInput(String(data.profit ?? ''))) : 0,
    })).post('/penjualan', {
        preserveScroll: true,
        onSuccess: () => {
            useSwal().fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Transaksi penjualan berhasil ditambahkan.',
                timer: 2500,
                showConfirmButton: false,
            })
            form.reset()
            form.date = new Date().toISOString().split('T')[0]
            form.status = 'sold'
            isSellIdrEdited.value = false
            exchangeRateManual.value = false
            showModal.value = false
        },
        onError: () => {
            useSwal().fire({
                icon: 'error',
                title: 'Gagal!',
                text: 'Terjadi kesalahan saat menyimpan data.',
                timer: 2500,
                showConfirmButton: false,
            })
        },
    })
}

// ─── Delete ─────────────────────────────────────────────
function confirmDelete(transactionId: number) {
    useSwal().fire({
        title: 'Hapus transaksi?',
        text: 'Data transaksi akan dihapus permanen.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/penjualan/${transactionId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    useSwal().fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Transaksi berhasil dihapus.',
                        timer: 2500,
                        showConfirmButton: false,
                    })
                },
                onError: () => {
                    useSwal().fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Terjadi kesalahan saat menghapus transaksi.',
                        timer: 2500,
                        showConfirmButton: false,
                    })
                },
            })
        }
    })
}

// ─── Helpers ────────────────────────────────────────────
function getKoiInfo(transaction: Transaction): { code: string; name: string } {
    if (transaction.koi) return { code: transaction.koi.code, name: transaction.koi.name }
    const koi = kois.value.find((k) => k.id === transaction.koi_id)
    return { code: koi?.code ?? '-', name: koi?.name ?? '-' }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Transaksi Penjualan" />

        <div class="flex flex-col gap-4 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Transaksi Penjualan</h1>
                    <p class="text-sm text-muted-foreground">Kelola transaksi penjualan koi.</p>
                </div>

                <Dialog v-model:open="showModal">
                    <DialogTrigger as-child>
                        <Button>Tambah Transaksi</Button>
                    </DialogTrigger>
                    <DialogContent class="flex max-h-[90vh] flex-col p-0 sm:max-w-[600px]">
                        <DialogHeader class="flex-shrink-0 border-b px-6 py-4">
                            <DialogTitle>Tambah Transaksi Penjualan</DialogTitle>
                            <DialogDescription>Isi data transaksi. Field harga otomatis muncul sesuai status.</DialogDescription>
                        </DialogHeader>

                        <form @submit.prevent="submit" class="flex flex-1 flex-col overflow-hidden">
                            <div class="flex-1 space-y-5 overflow-y-auto px-6 py-5">

                                <!-- ══════ Section 1: Informasi Dasar ══════ -->
                                <fieldset class="space-y-4">
                                    <!-- <legend class="mb-1 text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                                        Informasi Dasar
                                    </legend> -->

                                    <!-- Tanggal + Status -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="grid gap-1.5">
                                            <Label for="date">Tanggal <span class="text-destructive">*</span></Label>
                                            <Input id="date" v-model="form.date" type="date" />
                                            <InputError :message="form.errors.date" />
                                        </div>
                                        <div class="grid gap-1.5">
                                            <Label for="status">Status <span class="text-destructive">*</span></Label>
                                            <Select v-model="form.status">
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Pilih status" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem
                                                        v-for="opt in statusOptions"
                                                        :key="opt.value"
                                                        :value="opt.value"
                                                    >
                                                        {{ opt.label }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError :message="form.errors.status" />
                                        </div>
                                    </div>

                                    <!-- Koi -->
                                    <div class="grid gap-4">
                                        <div class="grid gap-1.5">
                                            <Label for="koi_id">Koi <span class="text-destructive">*</span></Label>
                                            <Select v-model="form.koi_id">
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Pilih koi" />
                                                </SelectTrigger>
                                                <SelectContent class="w-full max-w-[320px] p-0" side="bottom" align="start">
                                                    <div class="sticky top-0 border-b bg-background p-2">
                                                        <Input v-model="koiSearch" placeholder="Cari koi..." class="h-8" @keydown.stop />
                                                    </div>
                                                    <div class="max-h-48 overflow-y-auto">
                                                        <div v-if="filteredKois.length === 0" class="px-2 py-2 text-sm text-muted-foreground">
                                                            Koi tidak ditemukan
                                                        </div>
                                                        <SelectItem v-for="koi in filteredKois" :key="koi.id" :value="String(koi.id)">
                                                            {{ koi.code }} - {{ koi.name }}
                                                        </SelectItem>
                                                    </div>
                                                </SelectContent>
                                            </Select>
                                            <InputError :message="form.errors.koi_id" />
                                        </div>
                                    </div>

                                    <!-- Qty + Stok Tersedia -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="grid gap-1.5">
                                            <Label for="qty">Qty <span class="text-destructive">*</span></Label>
                                            <Input id="qty" v-model="form.qty" type="number" min="1" placeholder="0" />
                                            <InputError :message="form.errors.qty" />
                                        </div>
                                        <div class="grid gap-1.5">
                                            <Label class="text-xs text-muted-foreground">Stok Tersedia</Label>
                                            <Input :value="availableStock" disabled class="bg-muted" />
                                        </div>
                                    </div>

                                    <!-- Sell To -->
                                    <div class="grid gap-4">
                                        <div class="grid gap-1.5">
                                            <Label for="customer_id">Sell To <span class="text-destructive">*</span></Label>
                                            <Select v-model="form.customer_id">
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Pilih customer" />
                                                </SelectTrigger>
                                                <SelectContent class="w-full max-w-[280px] p-0" side="bottom" align="start">
                                                    <div class="sticky top-0 border-b bg-background p-2">
                                                        <Input v-model="customerSearch" placeholder="Cari customer..." class="h-8" @keydown.stop />
                                                    </div>
                                                    <div class="max-h-48 overflow-y-auto">
                                                        <div v-if="filteredCustomers.length === 0" class="px-2 py-2 text-sm text-muted-foreground">
                                                            Customer tidak ditemukan
                                                        </div>
                                                        <SelectItem v-for="customer in filteredCustomers" :key="customer.id" :value="String(customer.id)">
                                                            {{ customer.name }}
                                                        </SelectItem>
                                                    </div>
                                                </SelectContent>
                                            </Select>
                                            <InputError :message="form.errors.customer_id" />
                                        </div>
                                    </div>
                                </fieldset>

                                <!-- ══════ Section 2: Harga & Perhitungan (conditional) ══════ -->
                                <fieldset v-if="needsSellFields" class="space-y-4 rounded-lg border border-border bg-muted/20 p-4">
                                    <legend class="mb-1 rounded bg-primary/10 px-2 py-0.5 text-xs font-semibold uppercase tracking-widest text-primary">
                                        Harga &amp; Perhitungan
                                    </legend>

                                    <!-- Sell JPY + Kurs -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="grid gap-1.5">
                                            <Label for="sell_jpy">Sell JPY <span class="text-destructive">*</span></Label>
                                            <Input
                                                id="sell_jpy"
                                                :model-value="formatCurrencyDisplay(form.sell_jpy)"
                                                @update:model-value="form.sell_jpy = parseCurrencyInput($event)"
                                                type="text"
                                                inputmode="numeric"
                                                placeholder="0"
                                            />
                                            <InputError :message="form.errors.sell_jpy" />
                                        </div>
                                        <div class="grid gap-1.5">
                                            <Label for="exchange_rate">
                                                Kurs (&#165;1 = Rp)
                                                <span class="text-xs text-muted-foreground">(auto)</span>
                                            </Label>
                                            <div class="relative">
                                                <Input
                                                    id="exchange_rate"
                                                    :model-value="formatCurrencyDisplay(String(exchangeRate))"
                                                    @update:model-value="(val) => { exchangeRate = Number(parseCurrencyInput(val)) || 0; exchangeRateManual = true; isSellIdrEdited = false }"
                                                    type="text"
                                                    inputmode="numeric"
                                                    placeholder="110"
                                                    :disabled="exchangeRateLoading"
                                                    class="pr-9"
                                                />
                                                <button
                                                    type="button"
                                                    class="absolute inset-y-0 right-0 flex items-center px-2 text-muted-foreground hover:text-foreground"
                                                    :class="{ 'animate-spin': exchangeRateLoading }"
                                                    :disabled="exchangeRateLoading"
                                                    @click="exchangeRateManual = false; fetchExchangeRate()"
                                                    title="Refresh kurs dari API"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                                                        <path d="M3 3v5h5" />
                                                        <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16" />
                                                        <path d="M16 16h5v5" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <p v-if="exchangeRateError && !exchangeRateManual" class="text-xs text-amber-600">
                                                Gagal fetch, gunakan fallback
                                            </p>
                                            <p v-if="exchangeRateManual" class="text-xs text-amber-600">
                                                Kurs manual
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Sell IDR + Margin -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="grid gap-1.5">
                                            <Label for="sell_idr">
                                                Sell IDR <span class="text-destructive">*</span>
                                                <span class="text-xs text-muted-foreground">(auto)</span>
                                            </Label>
                                            <Input
                                                id="sell_idr"
                                                :model-value="formatCurrencyDisplay(form.sell_idr)"
                                                @update:model-value="(val) => { form.sell_idr = parseCurrencyInput(val); isSellIdrEdited = true }"
                                                type="text"
                                                inputmode="numeric"
                                                placeholder="0"
                                            />
                                            <InputError :message="form.errors.sell_idr" />
                                        </div>
                                        <div class="grid gap-1.5">
                                            <Label for="margin">Margin % <span class="text-destructive">*</span></Label>
                                            <Input id="margin" v-model="form.margin" type="number" step="0.1" min="0" placeholder="0" />
                                            <InputError :message="form.errors.margin" />
                                        </div>
                                    </div>

                                    <!-- Ringkasan: Stock + Fee + Profit (read-only) -->
                                    <div class="grid grid-cols-2 gap-x-4 gap-y-3 sm:grid-cols-4">
                                        <div class="grid gap-1">
                                            <Label class="text-[11px] text-muted-foreground">Stock JPY</Label>
                                            <div class="rounded-md border bg-muted px-3 py-1.5 text-sm tabular-nums">
                                                {{ formatJPY(stockJpy) }}
                                            </div>
                                        </div>
                                        <div class="grid gap-1">
                                            <Label class="text-[11px] text-muted-foreground">Stock IDR</Label>
                                            <div class="rounded-md border bg-muted px-3 py-1.5 text-sm tabular-nums">
                                                {{ formatIDR(stockIdr) }}
                                            </div>
                                        </div>
                                        <div class="grid gap-1">
                                            <Label class="text-[11px] text-muted-foreground">Fee</Label>
                                            <div class="rounded-md border bg-muted px-3 py-1.5 text-sm tabular-nums">
                                                {{ formatIDR(computedFee) }}
                                            </div>
                                        </div>
                                        <div class="grid gap-1">
                                            <Label class="text-[11px] text-muted-foreground">Profit</Label>
                                            <div
                                                class="rounded-md border px-3 py-1.5 text-sm font-semibold tabular-nums"
                                                :class="computedProfit >= 0 ? 'border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-950 dark:text-green-400' : 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-400'"
                                            >
                                                {{ formatIDR(computedProfit) }}
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>

                                <!-- Info box (selalu tampil) -->
                                <div class="rounded-md border border-dashed border-muted-foreground/25 bg-muted/20 px-4 py-2.5 text-xs leading-relaxed text-muted-foreground">
                                    <template v-if="needsSellFields">
                                        Kurs: &#165;1 = Rp {{ exchangeRate.toLocaleString('id-ID') }}
                                        <span v-if="exchangeRateManual" class="text-amber-600">(manual)</span>
                                        <span v-else-if="exchangeRateError" class="text-amber-600">(fallback)</span>
                                        <span v-else class="text-green-600">(live)</span>
                                        · Harga per ekor
                                        · Profit:
                                        <span v-if="form.status === 'azukari'" class="font-medium">Fee (komisi titipan)</span>
                                        <span v-else-if="form.status === 'sold'" class="font-medium">Stock IDR (jual langsung)</span>
                                        <span v-else-if="form.status === 'shipped'" class="font-medium">(Sell IDR − Stock IDR) + Fee</span>
                                    </template>
                                    <template v-else>
                                        Status <span class="font-medium">{{ getStatusLabel(form.status) }}</span> tidak memerlukan data harga.
                                        Field harga akan otomatis di-set ke 0.
                                    </template>
                                </div>
                            </div>

                            <DialogFooter class="flex-shrink-0 border-t bg-background px-6 py-4">
                                <Button type="button" variant="outline" @click="showModal = false">Batal</Button>
                                <Button type="submit" :disabled="form.processing">
                                    <span v-if="form.processing">Menyimpan...</span>
                                    <span v-else>Simpan</span>
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- DataTable -->
            <div class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                <!-- Search + Per Page -->
                <div class="flex flex-col gap-2 border-b border-border p-4 sm:flex-row sm:items-center sm:justify-between">
                    <Input
                        :model-value="search"
                        @update:model-value="onSearch"
                        placeholder="Cari koi, customer, status..."
                        class="max-w-sm"
                    />
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted-foreground">Show</span>
                        <select
                            :value="perPage"
                            @change="(e) => { perPage = Number((e.target as HTMLSelectElement).value); currentPage = 1 }"
                            class="rounded-md border border-input bg-background px-3 py-1 text-sm"
                        >
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        <span class="text-sm text-muted-foreground">entries</span>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="toggleSort('date')">
                                    Tanggal <span class="ml-1">{{ sortIcon('date') }}</span>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Status
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Koi
                                </th>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="toggleSort('qty')">
                                    Qty <span class="ml-1">{{ sortIcon('qty') }}</span>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Sell To
                                </th>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="toggleSort('profit')">
                                    Profit <span class="ml-1">{{ sortIcon('profit') }}</span>
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-background">
                            <tr v-if="paginatedTransactions.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                    {{ search ? 'Tidak ada hasil untuk pencarian tersebut.' : 'Belum ada transaksi penjualan.' }}
                                </td>
                            </tr>
                            <tr
                                v-for="transaction in paginatedTransactions"
                                :key="transaction.id"
                                class="transition-colors hover:bg-muted/50"
                            >
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-foreground">
                                    {{ formatDate(transaction.date) }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <Badge :variant="getStatusVariant(transaction.status)">
                                        {{ getStatusLabel(transaction.status) }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 text-sm text-foreground">
                                    <div class="font-medium">{{ getKoiInfo(transaction).code }}</div>
                                    <div class="max-w-[200px] truncate text-xs text-muted-foreground">
                                        {{ getKoiInfo(transaction).name }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-foreground">
                                    {{ transaction.qty?.toLocaleString('id-ID') ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-foreground">
                                    {{ transaction.customer?.name ?? '-' }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-4 py-3 text-sm font-medium"
                                    :class="Number(transaction.profit) >= 0 ? 'text-green-600' : 'text-red-600'"
                                >
                                    {{ formatIDR(transaction.profit) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                    <Link
                                        :href="`/penjualan/${transaction.id}`"
                                        class="mr-2 rounded-md border border-border px-3 py-1 text-xs font-medium text-foreground hover:bg-muted"
                                    >
                                        Detail
                                    </Link>
                                    <Link
                                        :href="`/penjualan/${transaction.id}/edit`"
                                        class="mr-2 rounded-md border border-border px-3 py-1 text-xs font-medium text-foreground hover:bg-muted"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        type="button"
                                        class="rounded-md border border-red-500 bg-red-500/10 px-3 py-1 text-xs font-medium text-red-700 hover:bg-red-500/20"
                                        @click="confirmDelete(transaction.id)"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex flex-col items-center justify-between gap-2 border-t border-border px-4 py-3 sm:flex-row">
                    <p class="text-sm text-muted-foreground">
                        Showing {{ showingFrom }} to {{ showingTo }} of {{ filteredTransactions.length }} entries
                        <span v-if="search">(filtered from {{ transactions.length }} total)</span>
                    </p>
                    <div class="flex items-center gap-1">
                        <Button variant="outline" size="sm" :disabled="currentPage <= 1" @click="currentPage--">
                            Previous
                        </Button>
                        <template v-for="p in totalPages" :key="p">
                            <Button size="sm" :variant="p === currentPage ? 'default' : 'outline'" @click="currentPage = p">
                                {{ p }}
                            </Button>
                        </template>
                        <Button variant="outline" size="sm" :disabled="currentPage >= totalPages" @click="currentPage++">
                            Next
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>