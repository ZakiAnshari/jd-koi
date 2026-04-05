<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
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

type Purchase = {
    id: number
    koi_id: number
    date: string
    qty: number
    price_jpy: number
    price_idr: number
    status: string
}

const page = usePage()
const purchase = page.props.purchase as Purchase
const kois = (page.props.kois as Koi[]) || []

// ─── Exchange Rate ─────────────────────────────────────
const exchangeRate = ref<number>(0)
const exchangeRateLoading = ref(false)
const exchangeRateError = ref(false)
const exchangeRateManual = ref(false)
const isInitialLoad = ref(true)
const isPriceIdrEdited = ref(false)

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
    {
        title: 'Pembelian',
        href: '/pembelian',
    },
    {
        title: 'Edit',
        href: `/pembelian/${purchase.id}/edit`,
    },
]

const koiSearch = ref('')

const filteredKois = computed(() => {
    if (!koiSearch.value) return kois
    const q = koiSearch.value.toLowerCase()
    return kois.filter(k => k.code.toLowerCase().includes(q) || k.name.toLowerCase().includes(q))
})

// ─── Currency Helpers ─────────────────────────────────
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

const form = useForm({
    date: purchase.date,
    koi_id: String(purchase.koi_id),
    qty: purchase.qty as string | number,
    price_jpy: purchase.price_jpy as string | number,
    price_idr: purchase.price_idr as string | number,
})

// ─── Auto-calc Price IDR = Price JPY × Kurs ──────────
watch(
    [() => form.price_jpy, exchangeRate],
    ([priceJpyRaw]) => {
        if (isInitialLoad.value) {
            isInitialLoad.value = false
            return
        }
        if (isPriceIdrEdited.value) return
        const priceJpy = Number(parseCurrencyInput(String(priceJpyRaw ?? '')))
        if (!isNaN(priceJpy) && priceJpy > 0) {
            form.price_idr = String(Math.round(priceJpy * exchangeRate.value))
        } else {
            form.price_idr = ''
        }
    },
)

function handleRefreshKurs() {
    exchangeRateManual.value = true
    isPriceIdrEdited.value = false
    fetchExchangeRate()
}

const submit = () => {
    form.transform((data) => ({
        ...data,
        price_jpy: Number(parseCurrencyInput(String(data.price_jpy ?? ''))),
        price_idr: Number(parseCurrencyInput(String(data.price_idr ?? ''))),
        qty: Number(data.qty),
    })).post(`/pembelian/${purchase.id}?_method=PUT`, {
        preserveScroll: true,
        onSuccess: () => {
            useSwal().fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data pembelian berhasil diperbarui.',
                timer: 2500,
                showConfirmButton: false,
            })
        },
        onError: () => {
            useSwal().fire({
                icon: 'error',
                title: 'Gagal!',
                text: 'Terjadi kesalahan saat memperbarui data.',
                timer: 2500,
                showConfirmButton: false,
            })
        },
    })
}

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Edit Pembelian" />

        <div class="space-y-6 p-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Edit Pembelian</h1>
                    <p class="text-sm text-muted-foreground">Perbarui data pembelian koi.</p>
                </div>
                <Link
                    href="/pembelian"
                    class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted"
                >
                    Kembali
                </Link>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Section 1: Informasi Dasar -->
                    <fieldset class="space-y-4 pb-6 border-b border-border">
                        <legend class="text-sm font-semibold text-foreground">Informasi Dasar</legend>

                        <div class="grid gap-2">
                            <Label for="koi_id">Koi *</Label>
                            <Select v-model="form.koi_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih koi" />
                                </SelectTrigger>
                                <SelectContent class="p-0 w-[--radix-select-trigger-width] min-w-[20rem]">
                                    <div class="sticky top-0 bg-background p-2 border-b">
                                        <Input v-model="koiSearch" placeholder="Cari koi..." class="h-8" />
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

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="date">Tanggal *</Label>
                                <Input id="date" v-model="form.date" type="date" />
                                <InputError :message="form.errors.date" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="qty">Qty *</Label>
                                <Input id="qty" v-model="form.qty" type="number" placeholder="0" />
                                <InputError :message="form.errors.qty" />
                            </div>
                        </div>


                    </fieldset>

                    <!-- Section 2: Harga & Kurs -->
                    <fieldset class="space-y-4">
                        <div class="flex items-center justify-between">
                            <legend class="text-sm font-semibold text-foreground">Harga & Kurs</legend>
                            <button
                                type="button"
                                @click="handleRefreshKurs"
                                :disabled="exchangeRateLoading"
                                class="inline-flex items-center gap-2 text-xs font-medium text-primary hover:text-primary/80 disabled:opacity-50"
                            >
                                <span v-if="exchangeRateLoading" class="text-xs">Memuat...</span>
                                <span v-else>🔄 Refresh Kurs</span>
                            </button>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <div class="flex items-center gap-1">
                                    <Label for="price_jpy">Harga JPY *</Label>
                                </div>
                                <Input
                                    id="price_jpy"
                                    :model-value="formatCurrencyDisplay(form.price_jpy)"
                                    @update:model-value="(val) => { form.price_jpy = parseCurrencyInput(String(val)); isPriceIdrEdited = false }"
                                    type="text"
                                    inputmode="numeric"
                                    placeholder="0"
                                />
                                <InputError :message="form.errors.price_jpy" />
                            </div>

                            <div class="grid gap-2">
                                <div class="flex items-center gap-1">
                                    <Label for="exchange_rate">Kurs (¥1 = Rp) <span class="text-xs text-muted-foreground">(auto)</span></Label>
                                </div>
                                <div class="relative">
                                    <Input
                                        id="exchange_rate"
                                        :model-value="formatCurrencyDisplay(String(exchangeRate))"
                                        @update:model-value="(val) => { exchangeRate = Number(parseCurrencyInput(String(val))) || 0; exchangeRateManual = true; isPriceIdrEdited = false }"
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
                                        @click="exchangeRateManual = false; isPriceIdrEdited = false; fetchExchangeRate()"
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

                        <div class="grid gap-2">
                            <div class="flex items-center gap-1">
                                <Label for="price_idr">Harga IDR <span class="text-xs text-muted-foreground">(auto dari JPY × Kurs)</span></Label>
                            </div>
                            <Input
                                id="price_idr"
                                :model-value="formatCurrencyDisplay(form.price_idr)"
                                @update:model-value="(value) => {
                                    form.price_idr = parseCurrencyInput(String(value));
                                    isPriceIdrEdited = true;
                                }"
                                type="text"
                                inputmode="numeric"
                                placeholder="0"
                                @focus="isPriceIdrEdited = true"
                            />
                            <InputError :message="form.errors.price_idr" />
                        </div>

                        <!-- Info Box -->
                        <div class="rounded-md bg-blue-50 p-3 text-xs text-blue-900 dark:bg-blue-950 dark:text-blue-100">
                            <p class="font-semibold">ℹ️ Cara Kerja Kurs</p>
                            <ul class="mt-2 space-y-1 list-disc list-inside">
                                <li>Kurs diambil otomatis dari API terbaru jika ada koneksi internet.</li>
                                <li>Harga IDR = Harga JPY × Kurs (otomatis dihitung).</li>
                                <li>Klik tombol "Refresh Kurs" untuk update kurs terbaru dan aktifkan auto-calc.</li>
                                <li>Jika Anda ubah Harga IDR manual, auto-calc akan berhenti sampai kurs di-refresh.</li>
                            </ul>
                        </div>
                    </fieldset>

                    <div class="flex items-center gap-4 pt-6 border-t border-border">
                        <Button type="submit" :disabled="form.processing">Simpan</Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
