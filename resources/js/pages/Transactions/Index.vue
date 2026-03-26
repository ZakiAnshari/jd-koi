<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import Swal, { type SweetAlertOptions } from 'sweetalert2'
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

type Koi = {
    id: number
    code: string
    name: string
}

type Transaction = {
    id: number
    koi_id: number
    date: string
    status: string
    qty: number
    sell_jpy: number
    sell_idr: number
    margin: number
    fee: number
    stock_jpy: number
    stock_idr: number
    sell_to: string
    profit: number
    created_at: string
    koi?: Koi
}

const page = usePage()
const transactions = computed(() => (page.props.transactions as { data: Transaction[] } | null)?.data ?? [])
const kois = computed(() => (page.props.kois as Koi[] | null) ?? [])
const stocks = computed(() => (page.props.stocks as Record<string, { price_jpy: string; price_idr: string }>) || {})

// Exchange rate from props or default (¥1 = Rp 110)
const exchangeRate = computed(() => {
    const rate = page.props.exchange_rate as number | undefined
    return rate ?? 110
})

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Transaksi Penjualan',
        href: '/transactions',
    },
]

function fireSwal(options: SweetAlertOptions) {
    return Swal.fire({
        ...options,
        scrollbarPadding: false,
        heightAuto: false,
    })
}

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
        result = result.filter(
            (t) =>
                (t.koi?.code?.toLowerCase().includes(q) ?? false) ||
                (t.koi?.name?.toLowerCase().includes(q) ?? false) ||
                t.sell_to.toLowerCase().includes(q),
        )
    }

    result = [...result].sort((a, b) => {
        const aVal = a[sortKey.value]
        const bVal = b[sortKey.value]
        if (typeof aVal === 'string' && typeof bVal === 'string') {
            return sortOrder.value === 'asc'
                ? aVal.localeCompare(bVal)
                : bVal.localeCompare(aVal)
        }
        if (typeof aVal === 'number' && typeof bVal === 'number') {
            return sortOrder.value === 'asc' ? aVal - bVal : bVal - aVal
        }
        return 0
    })

    return result
})

const totalPages = computed(() => Math.ceil(filteredTransactions.value.length / perPage.value) || 1)

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
const isSellIdrEdited = ref(false)
const isStockJpyEdited = ref(false)
const isStockIdrEdited = ref(false)
const isFeeEdited = ref(false)
const isProfitEdited = ref(false)

const filteredKois = computed(() => {
    if (!koiSearch.value) return kois.value
    const q = koiSearch.value.toLowerCase()
    return kois.value.filter(k => k.code.toLowerCase().includes(q) || k.name.toLowerCase().includes(q))
})

function formatCurrencyDisplay(value: string | number): string {
    if (!value && value !== 0) return ''
    const num = String(value).replace(/[^\d-]/g, '')
    // Handle negative numbers
    const isNegative = num.startsWith('-')
    const absNum = isNegative ? num.slice(1) : num
    const formatted = absNum.replace(/\B(?=(\d{3})+(?!\d))/g, '.')
    return isNegative ? '-' + formatted : formatted
}

function parseCurrencyInput(value: string): string {
    return value.replace(/\./g, '')
}

const form = useForm({
    date: new Date().toISOString().split('T')[0],
    status: 'completed',
    koi_id: '',
    qty: '',
    sell_jpy: '',
    sell_idr: '',
    margin: '',   // Now a PERCENTAGE (e.g., "5" means 5%)
    fee: '',       // Auto-calculated: Sell IDR × (Margin / 100)
    stock_jpy: '',
    stock_idr: '',
    sell_to: '',
    profit: '',    // Auto-calculated: = Fee
})

// ─── Auto-fill Stock JPY & Stock IDR when Koi is selected ───
watch(() => form.koi_id, (newKoiId) => {
    if (newKoiId && stocks.value[newKoiId]) {
        if (!isStockJpyEdited.value) {
            form.stock_jpy = stocks.value[newKoiId].price_jpy
        }
        if (!isStockIdrEdited.value) {
            form.stock_idr = stocks.value[newKoiId].price_idr
        }
    }
})

// ─── Helper: Recalculate Fee and Profit ───
function recalcFeeAndProfit() {
    const sellIdrNum = parseFloat(form.sell_idr)
    const marginPct = parseFloat(form.margin)

    // Fee = Sell IDR × (Margin% / 100)
    if (!isFeeEdited.value && !isNaN(sellIdrNum) && !isNaN(marginPct)) {
        form.fee = Math.round(sellIdrNum * (marginPct / 100)).toString()
    }

    // Profit = Fee
    if (!isProfitEdited.value) {
        const feeNum = parseFloat(form.fee)
        if (!isNaN(feeNum)) {
            form.profit = feeNum.toString()
        }
    }
}

// ─── Sell JPY changed → auto-calc Sell IDR, then Fee & Profit ───
watch(() => form.sell_jpy, (newSellJpy) => {
    const sellJpyNum = parseFloat(newSellJpy)

    // Sell IDR = Sell JPY × exchange rate
    if (!isSellIdrEdited.value && !isNaN(sellJpyNum)) {
        form.sell_idr = Math.round(sellJpyNum * exchangeRate.value).toString()
    }

    recalcFeeAndProfit()
})

// ─── Sell IDR changed → recalc Fee & Profit ───
watch(() => form.sell_idr, () => {
    recalcFeeAndProfit()
})

// ─── Margin% changed → recalc Fee & Profit ───
watch(() => form.margin, () => {
    recalcFeeAndProfit()
})

// ─── Fee changed → recalc Profit ───
watch(() => form.fee, () => {
    if (!isProfitEdited.value) {
        const feeNum = parseFloat(form.fee)
        if (!isNaN(feeNum)) {
            form.profit = feeNum.toString()
        }
    }
})

// ─── Stock JPY changed → auto-calc Stock IDR ───
watch(() => form.stock_jpy, (newStockJpy) => {
    if (!isStockIdrEdited.value) {
        const stockJpyNum = parseFloat(newStockJpy)
        if (!isNaN(stockJpyNum)) {
            form.stock_idr = Math.round(stockJpyNum * exchangeRate.value).toString()
        }
    }
})

const submit = () => {
    form.post('/transactions', {
        preserveScroll: true,
        onSuccess: () => {
            fireSwal({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Transaksi penjualan berhasil ditambahkan.',
                timer: 3000,
                showConfirmButton: false,
            })
            form.reset()
            isSellIdrEdited.value = false
            isStockJpyEdited.value = false
            isStockIdrEdited.value = false
            isFeeEdited.value = false
            isProfitEdited.value = false
            showModal.value = false
            koiSearch.value = ''
        },
        onError: () => {
            fireSwal({
                icon: 'error',
                title: 'Gagal!',
                text: 'Terjadi kesalahan saat menyimpan data.',
                timer: 3000,
                showConfirmButton: false,
            })
        },
    })
}

function confirmDelete(transactionId: number) {
    fireSwal({
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
            router.delete(`/transactions/${transactionId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    fireSwal({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Transaksi berhasil dihapus.',
                        timer: 3000,
                        showConfirmButton: false,
                    })
                },
                onError: () => {
                    fireSwal({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Terjadi kesalahan saat menghapus transaksi.',
                        timer: 3000,
                        showConfirmButton: false,
                    })
                },
            })
        }
    })
}

function getKoiInfo(transaction: Transaction): { code: string; name: string } {
    if (transaction.koi) return { code: transaction.koi.code, name: transaction.koi.name }
    const koi = kois.value.find(k => k.id === transaction.koi_id)
    return { code: koi?.code ?? '-', name: koi?.name ?? '-' }
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    })
}

function formatJPY(value: number | string | undefined): string {
    if (!value) return '¥ 0'
    return '¥ ' + Number(value).toLocaleString('en-US')
}

function formatIDR(value: number | string | undefined): string {
    if (!value) return 'Rp 0'
    return 'Rp ' + Number(value).toLocaleString('id-ID')
}

function formatPercent(value: number | string | undefined): string {
    if (!value) return '0%'
    return Number(value) + '%'
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Transaksi Penjualan" />

        <div class="flex flex-col gap-4 p-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Transaksi Penjualan</h1>
                    <p class="text-sm text-muted-foreground">Kelola transaksi penjualan koi.</p>
                </div>

                <Dialog v-model:open="showModal">
                    <DialogTrigger as-child>
                        <Button>Add Transaction</Button>
                    </DialogTrigger>
                    <DialogContent class="flex max-h-[90vh] flex-col p-0 sm:max-w-[700px]">
                        <DialogHeader class="flex-shrink-0 border-b px-6 py-4">
                            <DialogTitle>Add Transaction</DialogTitle>
                            <DialogDescription>Add new sales transaction.</DialogDescription>
                        </DialogHeader>
                        <form @submit.prevent="submit" class="flex flex-1 flex-col overflow-hidden">
                            <div class="flex-1 space-y-4 overflow-y-auto px-6 py-4">
                                <!-- Row 1: Date, Status, Qty -->
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div class="grid gap-2">
                                        <Label for="date">Date *</Label>
                                        <Input id="date" v-model="form.date" type="date" />
                                        <InputError :message="form.errors.date" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="status">Status *</Label>
                                        <Select v-model="form.status">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select status" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="completed">Completed</SelectItem>
                                                <SelectItem value="pending">Pending</SelectItem>
                                                <SelectItem value="cancelled">Cancelled</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError :message="form.errors.status" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="qty">Qty *</Label>
                                        <Input id="qty" v-model="form.qty" type="number" placeholder="0" />
                                        <InputError :message="form.errors.qty" />
                                    </div>
                                </div>

                                <!-- Row 2: Koi (Full Width) -->
                                <div class="grid grid-cols-1 gap-4">
                                    <div class="grid gap-2">
                                        <Label for="koi_id">Koi *</Label>
                                        <Select v-model="form.koi_id">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select koi" />
                                            </SelectTrigger>
                                            <SelectContent class="w-full max-w-[280px] p-0" side="bottom" align="start">
                                                <div class="sticky top-0 border-b bg-background p-2">
                                                    <Input v-model="koiSearch" placeholder="Search koi..." class="h-8" />
                                                </div>
                                                <div class="max-h-48 overflow-y-auto">
                                                    <div v-if="filteredKois.length === 0" class="px-2 py-2 text-sm text-muted-foreground">
                                                        No kois found
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

                                <!-- Row 3: Pembeli, Sell JPY, Sell IDR -->
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div class="grid gap-2">
                                        <Label for="sell_to">Pembeli *</Label>
                                        <Input id="sell_to" v-model="form.sell_to" type="text" placeholder="Customer name" />
                                        <InputError :message="form.errors.sell_to" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="sell_jpy">Sell JPY *</Label>
                                        <Input id="sell_jpy" :model-value="formatCurrencyDisplay(form.sell_jpy)" @update:model-value="form.sell_jpy = parseCurrencyInput($event)" type="text" placeholder="0" />
                                        <InputError :message="form.errors.sell_jpy" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="sell_idr">
                                            Sell IDR *
                                            <span class="text-xs text-muted-foreground">(auto: JPY × {{ exchangeRate }})</span>
                                        </Label>
                                        <Input id="sell_idr" :model-value="formatCurrencyDisplay(form.sell_idr)" @update:model-value="(val) => { form.sell_idr = parseCurrencyInput(val); isSellIdrEdited = true }" type="text" placeholder="0" />
                                        <InputError :message="form.errors.sell_idr" />
                                    </div>
                                </div>

                                <!-- Row 4: Stock JPY, Stock IDR -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="grid gap-2">
                                        <Label for="stock_jpy">Stock JPY *</Label>
                                        <Input id="stock_jpy" :model-value="formatCurrencyDisplay(form.stock_jpy)" @update:model-value="(val) => { form.stock_jpy = parseCurrencyInput(val); isStockJpyEdited = true }" type="text" placeholder="0" />
                                        <InputError :message="form.errors.stock_jpy" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="stock_idr">
                                            Stock IDR *
                                            <span class="text-xs text-muted-foreground">(auto: JPY × {{ exchangeRate }})</span>
                                        </Label>
                                        <Input id="stock_idr" :model-value="formatCurrencyDisplay(form.stock_idr)" @update:model-value="(val) => { form.stock_idr = parseCurrencyInput(val); isStockIdrEdited = true }" type="text" placeholder="0" />
                                        <InputError :message="form.errors.stock_idr" />
                                    </div>
                                </div>

                                <!-- Row 5: Margin (%), Fee (auto), Profit (auto) -->
                                <div class="grid grid-cols-3 gap-4">
                                    <div class="grid gap-2">
                                        <Label for="margin">Margin % *</Label>
                                        <div class="relative">
                                            <Input id="margin" v-model="form.margin" type="number" step="0.1" min="0" max="100" placeholder="0" class="pr-8" />
                                            <span class="absolute inset-y-0 right-3 flex items-center text-sm text-muted-foreground">%</span>
                                        </div>
                                        <InputError :message="form.errors.margin" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="fee">
                                            Fee IDR *
                                            <span class="text-xs text-muted-foreground">(auto: IDR × %)</span>
                                        </Label>
                                        <Input id="fee" :model-value="formatCurrencyDisplay(form.fee)" @update:model-value="(val) => { form.fee = parseCurrencyInput(val); isFeeEdited = true }" type="text" placeholder="0" />
                                        <InputError :message="form.errors.fee" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="profit">
                                            Profit IDR *
                                            <span class="text-xs text-muted-foreground">(auto: = Fee)</span>
                                        </Label>
                                        <Input id="profit" :model-value="formatCurrencyDisplay(form.profit)" @update:model-value="(val) => { form.profit = parseCurrencyInput(val); isProfitEdited = true }" type="text" placeholder="0" />
                                        <InputError :message="form.errors.profit" />
                                    </div>
                                </div>

                                <!-- Exchange Rate Info -->
                                <div class="rounded-md border border-dashed border-muted-foreground/30 bg-muted/30 px-4 py-2 text-xs text-muted-foreground">
                                    Kurs: ¥1 = Rp {{ exchangeRate.toLocaleString('id-ID') }}
                                    · Sell IDR = Sell JPY × Kurs
                                    · Stock IDR = Stock JPY × Kurs
                                    · Fee = Sell IDR × Margin%
                                    · Profit = Fee
                                </div>
                            </div>
                            <DialogFooter class="flex-shrink-0 border-t bg-background px-6 py-4">
                                <Button type="submit" :disabled="form.processing">Add</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- DataTable -->
            <div class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                <div class="flex flex-col gap-2 border-b border-border p-4 sm:flex-row sm:items-center sm:justify-between">
                    <Input :model-value="search" @update:model-value="onSearch" placeholder="Search koi or customer..." class="max-w-sm" />
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

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="toggleSort('date')">
                                    Date <span class="ml-1">{{ sortIcon('date') }}</span>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Koi</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Pembeli</th>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="toggleSort('qty')">
                                    Qty <span class="ml-1">{{ sortIcon('qty') }}</span>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Jual JPY</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Jual IDR</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Margin</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Fee</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Profit</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-background">
                            <tr v-if="paginatedTransactions.length === 0">
                                <td colspan="11" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                    {{ search ? 'Tidak ada hasil untuk pencarian tersebut.' : 'Belum ada transaksi penjualan.' }}
                                </td>
                            </tr>
                            <tr v-for="transaction in paginatedTransactions" :key="transaction.id" class="transition-colors hover:bg-muted/50">
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-foreground">{{ formatDate(transaction.date) }}</td>
                                <td class="px-4 py-3 text-sm text-foreground">
                                    <div>{{ getKoiInfo(transaction).code }}</div>
                                    <div class="text-xs text-muted-foreground">{{ getKoiInfo(transaction).name }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-foreground">{{ transaction.sell_to }}</td>
                                <td class="px-4 py-3 text-sm text-foreground">{{ transaction.qty }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-foreground">{{ formatJPY(transaction.sell_jpy) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-foreground">{{ formatIDR(transaction.sell_idr) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-foreground">{{ formatPercent(transaction.margin) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-foreground">{{ formatIDR(transaction.fee) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium" :class="Number(transaction.profit) >= 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ formatIDR(transaction.profit) }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <Badge :variant="transaction.status === 'completed' ? 'default' : transaction.status === 'pending' ? 'secondary' : 'destructive'">
                                        {{ transaction.status }}
                                    </Badge>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                    <Link :href="`/transactions/${transaction.id}`" class="mr-2 rounded-md border border-border px-3 py-1 text-xs font-medium text-foreground hover:bg-muted">
                                        Detail
                                    </Link>
                                    <Link :href="`/transactions/${transaction.id}/edit`" class="mr-2 rounded-md border border-border px-3 py-1 text-xs font-medium text-foreground hover:bg-muted">
                                        Edit
                                    </Link>
                                    <button type="button" class="rounded-md border border-red-500 bg-red-500/10 px-3 py-1 text-xs font-medium text-red-700 hover:bg-red-500/20" @click="confirmDelete(transaction.id)">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col items-center justify-between gap-2 border-t border-border px-4 py-3 sm:flex-row">
                    <p class="text-sm text-muted-foreground">
                        Showing {{ showingFrom }} to {{ showingTo }} of {{ filteredTransactions.length }} entries
                        <span v-if="search">(filtered from {{ transactions.length }} total)</span>
                    </p>
                    <div class="flex items-center gap-1">
                        <Button variant="outline" size="sm" :disabled="currentPage <= 1" @click="currentPage--">Previous</Button>
                        <template v-for="p in totalPages" :key="p">
                            <Button size="sm" :variant="p === currentPage ? 'default' : 'outline'" @click="currentPage = p">{{ p }}</Button>
                        </template>
                        <Button variant="outline" size="sm" :disabled="currentPage >= totalPages" @click="currentPage++">Next</Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>