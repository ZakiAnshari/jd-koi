<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed, ref, watch, onMounted } from 'vue'
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
    customer_id: number | null
    date: string
    status: string
    qty: number
    sell_jpy: number
    sell_idr: number
    margin: number
    fee: number
    profit: number
}

const page = usePage()
const transaction = page.props.transaction as Transaction
const kois = (page.props.kois as Koi[]) || []
const customers = (page.props.customers as Customer[]) || []
const koiSummaries = computed(() =>
    (page.props.koiSummaries as Record<number, { modal_jpy: number; modal_idr: number; available_qty: number }> | null) ?? {},
)

const exchangeRate = ref<number>(0)
const exchangeRateLoading = ref(false)
const exchangeRateError = ref(false)
const exchangeRateManual = ref(false)
const isSellIdrEdited = ref(false)
const isInitialLoad = ref(true)

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

onMounted(() => {
    fetchExchangeRate()
})

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Transaksi Penjualan',
        href: '/penjualan',
    },
    {
        title: 'Edit',
        href: `/penjualan/${transaction.id}/edit`,
    },
]

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

const statusNeedsSellFields = ['azukari', 'sold', 'shipped'] as const

const koiSearch = ref('')
const customerSearch = ref('')

const filteredKois = computed(() => {
    if (!koiSearch.value) return kois
    const q = koiSearch.value.toLowerCase()
    return kois.filter((k) => k.code.toLowerCase().includes(q) || k.name.toLowerCase().includes(q))
})

const filteredCustomers = computed(() => {
    if (!customerSearch.value) return customers
    const q = customerSearch.value.toLowerCase()
    return customers.filter((c) => c.name.toLowerCase().includes(q))
})

function formatCurrencyDisplay(value: string | number | undefined | null): string {
    if (value === undefined || value === null || value === "") return ""
    const str = String(value).replace(/[^\d-]/g, '')
    if (!str || str === "-") return str
    const isNegative = str.startsWith("-")
    const absNum = isNegative ? str.slice(1) : str
    if (!absNum) return ""
    const formatted = absNum.replace(/\B(?=(\d{3})+(?!\d))/g, '.')
    return isNegative ? "-" + formatted : formatted
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

const form = useForm({
    date: transaction.date,
    status: transaction.status,
    koi_id: String(transaction.koi_id),
    customer_id: String(transaction.customer_id ?? ''),
    qty: transaction.qty as string | number,
    sell_jpy: transaction.sell_jpy as string | number,
    sell_idr: transaction.sell_idr as string | number,
    margin: transaction.margin as string | number,
    fee: transaction.fee as string | number,
    profit: transaction.profit as string | number,
})

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

const selectedKoiSummary = computed(() => {
    const koiId = Number(form.koi_id)
    if (!koiId) return { modal_jpy: 0, modal_idr: 0, available_qty: 0 }
    return koiSummaries.value[koiId] ?? { modal_jpy: 0, modal_idr: 0, available_qty: 0 }
})

const stockJpy = computed(() => selectedKoiSummary.value.modal_jpy)
const stockIdr = computed(() => selectedKoiSummary.value.modal_idr)
const availableStock = computed(() => selectedKoiSummary.value.available_qty)

const needsSellFields = computed(() =>
    statusNeedsSellFields.includes(form.status?.toLowerCase() as typeof statusNeedsSellFields[number]),
)

const computedFee = computed(() => {
    if (!needsSellFields.value) return 0
    const sellIdr = Number(parseCurrencyInput(String(form.sell_idr ?? '')))
    const margin = Number(form.margin)
    if (isNaN(sellIdr) || isNaN(margin)) return 0
    return Math.round(sellIdr * (margin / 100))
})

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

watch([computedFee, computedProfit], ([feeVal, profitVal]) => {
    form.fee = String(feeVal)
    form.profit = String(profitVal)
})

watch(
    [() => form.sell_jpy, exchangeRate],
    ([sellJpyRaw]) => {
        if (isInitialLoad.value) {
            isInitialLoad.value = false
            return
        }
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

const submit = () => {
    form.transform((data) => ({
        ...data,
        qty: Number(data.qty),
        sell_jpy: needsSellFields.value ? Number(parseCurrencyInput(String(data.sell_jpy ?? ''))) : 0,
        sell_idr: needsSellFields.value ? Number(parseCurrencyInput(String(data.sell_idr ?? ''))) : 0,
        margin: needsSellFields.value ? Number(data.margin) : 0,
        fee: needsSellFields.value ? Number(parseCurrencyInput(String(data.fee ?? ''))) : 0,
        profit: needsSellFields.value ? Number(parseCurrencyInput(String(data.profit ?? ''))) : 0,
    })).post(`/penjualan/${transaction.id}?_method=PUT`, {
        preserveScroll: true,
        onSuccess: () => {
            useSwal().fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Transaksi penjualan berhasil diperbarui.',
                timer: 3000,
                showConfirmButton: false,
            })
        },
        onError: () => {
            useSwal().fire({
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
        <Head title="Edit Transaksi Penjualan" />

        <div class="space-y-6 p-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Edit Transaksi Penjualan</h1>
                    <p class="text-sm text-muted-foreground">Perbarui data transaksi penjualan.</p>
                </div>
                <Link
                    href="/penjualan"
                    class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted"
                >
                    Kembali
                </Link>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                <form @submit.prevent="submit" class="space-y-6">
                    <fieldset class="space-y-4">
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

                    <fieldset v-if="needsSellFields" class="space-y-4 rounded-lg border border-border bg-muted/20 p-4">
                        <legend class="mb-1 rounded bg-primary/10 px-2 py-0.5 text-xs font-semibold uppercase tracking-widest text-primary">
                            Harga &amp; Perhitungan
                        </legend>

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
                                    Kurs (¥1 = Rp)
                                    <span class="text-xs text-muted-foreground">(auto)</span>
                                </Label>
                                <div class="relative">
                                    <Input
                                        id="exchange_rate"
                                        :model-value="formatCurrencyDisplay(String(exchangeRate))"
                                        @update:model-value="(val) => { exchangeRate = Number(parseCurrencyInput(String(val))) || 0; exchangeRateManual = true; isSellIdrEdited.value = false }"
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

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-1.5">
                                <Label for="sell_idr">
                                    Sell IDR <span class="text-destructive">*</span>
                                    <span class="text-xs text-muted-foreground">(auto)</span>
                                </Label>
                                <Input
                                    id="sell_idr"
                                    :model-value="formatCurrencyDisplay(form.sell_idr)"
                                    @update:model-value="(val) => { form.sell_idr = parseCurrencyInput(val); isSellIdrEdited.value = true }"
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

                    <div class="rounded-md border border-dashed border-muted-foreground/25 bg-muted/20 px-4 py-2.5 text-xs leading-relaxed text-muted-foreground">
                        <template v-if="needsSellFields">
                            Kurs: ¥1 = Rp {{ exchangeRate.toLocaleString('id-ID') }}
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
                            Status <span class="font-medium">{{ form.status }}</span> tidak memerlukan data harga.
                            Field harga akan otomatis di-set ke 0.
                        </template>
                    </div>

                    <div class="flex items-center gap-4 pt-2">
                        <Button type="submit" :disabled="form.processing">
                            <span v-if="form.processing">Menyimpan...</span>
                            <span v-else>Simpan</span>
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>