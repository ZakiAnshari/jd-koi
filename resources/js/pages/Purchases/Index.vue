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
import { Pencil, Trash2 } from 'lucide-vue-next'
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

type Koi = {
    id: number
    code: string
    name: string
    photo?: string
}

type Purchase = {
    id: number
    koi_id: number
    date: string
    qty: number
    price_jpy: number
    price_idr: number
    status: string
    created_at: string
    koi?: Koi
}

const page = usePage()
const purchases = computed(() => (page.props.purchases as { data: Purchase[] } | null)?.data ?? [])
const kois = computed(() => (page.props.kois as Koi[] | null) ?? [])

// ─── Exchange Rate (sama seperti Penjualan) ─────────────
const exchangeRate = ref<number>(0)
const exchangeRateLoading = ref(false)
const exchangeRateError = ref(false)
const exchangeRateManual = ref(false)
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
]

// ─── DataTable State ────────────────────────────────────
const search = ref('')
const sortKey = ref<keyof Purchase>('date')
const sortOrder = ref<'asc' | 'desc'>('desc')
const currentPage = ref(1)
const perPage = ref(10)
const previewOpen = ref(false)
const previewImage = ref<string | null>(null)
const previewKoiName = ref('')

const filteredPurchases = computed(() => {
    let result = purchases.value

    if (search.value) {
        const q = search.value.toLowerCase()
        result = result.filter((p) => {
            const koiCode = p.koi?.code?.toLowerCase() ?? ''
            const koiName = p.koi?.name?.toLowerCase() ?? ''
            const status = p.status?.toLowerCase() ?? ''
            return koiCode.includes(q) || koiName.includes(q) || status.includes(q)
        })
    }

    result = [...result].sort((a, b) => {
        const aVal = a[sortKey.value]
        const bVal = b[sortKey.value]
        if (aVal == null || bVal == null) return 0
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

const totalPages = computed(() => Math.ceil(filteredPurchases.value.length / perPage.value) || 1)

watch(totalPages, (newTotal) => {
    if (currentPage.value > newTotal) {
        currentPage.value = 1
    }
})

const paginatedPurchases = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return filteredPurchases.value.slice(start, start + perPage.value)
})

const showingFrom = computed(() =>
    filteredPurchases.value.length === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1,
)
const showingTo = computed(() =>
    Math.min(currentPage.value * perPage.value, filteredPurchases.value.length),
)

function onSearch(value: string | number) {
    search.value = String(value)
    currentPage.value = 1
}

function toggleSort(key: keyof Purchase) {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortKey.value = key
        sortOrder.value = 'asc'
    }
}

function sortIcon(key: keyof Purchase) {
    if (sortKey.value !== key) return '↕'
    return sortOrder.value === 'asc' ? '↑' : '↓'
}

// ─── Modal + Form ───────────────────────────────────────
const showModal = ref(false)
const koiSearch = ref('')

watch(showModal, (isOpen) => {
    if (!isOpen) {
        koiSearch.value = ''
        isPriceIdrEdited.value = false
        form.clearErrors()
    }
})

const filteredKois = computed(() => {
    if (!koiSearch.value) return kois.value
    const q = koiSearch.value.toLowerCase()
    return kois.value.filter(
        (k) => k.code.toLowerCase().includes(q) || k.name.toLowerCase().includes(q),
    )
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

function storagePath(photo: string | undefined | null) {
    if (!photo) return ''
    return `/storage/${photo}`
}

function openPreview(purchase: Purchase) {
    const koi = getKoiInfo(purchase)
    if (!purchase.koi?.photo) return
    previewImage.value = storagePath(purchase.koi.photo)
    previewKoiName.value = koi.name ?? 'Koi'
    previewOpen.value = true
}

function closePreview() {
    previewOpen.value = false
    previewImage.value = null
    previewKoiName.value = ''
}

// ─── Form ───────────────────────────────────────────────
const form = useForm({
    koi_id: '' as string,
    date: new Date().toISOString().split('T')[0],
    qty: '' as string | number,
    price_jpy: '' as string | number,
    price_idr: '' as string | number,
})

// ─── Auto-calc Price IDR = Price JPY × Kurs ─────────────
watch(
    [() => form.price_jpy, exchangeRate],
    ([priceJpyRaw]) => {
        if (isPriceIdrEdited.value) return
        const priceJpy = Number(parseCurrencyInput(String(priceJpyRaw ?? '')))
        if (!isNaN(priceJpy) && priceJpy > 0) {
            form.price_idr = String(Math.round(priceJpy * exchangeRate.value))
        } else {
            form.price_idr = ''
        }
    },
)

// ─── Submit ─────────────────────────────────────────────
const submit = () => {
    form.transform((data) => ({
        ...data,
        price_jpy: Number(parseCurrencyInput(String(data.price_jpy ?? ''))),
        price_idr: Number(parseCurrencyInput(String(data.price_idr ?? ''))),
        qty: Number(data.qty),
    })).post('/pembelian', {
        preserveScroll: true,
        onSuccess: () => {
            useSwal().fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data pembelian berhasil ditambahkan.',
                timer: 2500,
                showConfirmButton: false,
            })
            form.reset()
            form.date = new Date().toISOString().split('T')[0]
            isPriceIdrEdited.value = false
            exchangeRateManual.value = false
            showModal.value = false
        },
        onError: () => {
            // Modal tetap terbuka — error tampil langsung di field via InputError
        },
    })
}

// ─── Delete ─────────────────────────────────────────────
function confirmDelete(purchaseId: number) {
    useSwal().fire({
        title: 'Hapus pembelian?',
        text: 'Data pembelian akan dihapus permanen.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/pembelian/${purchaseId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    useSwal().fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Data pembelian berhasil dihapus.',
                        timer: 2500,
                        showConfirmButton: false,
                    })
                },
                onError: () => {
                    useSwal().fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Tidak dapat menghapus data.',
                        timer: 2500,
                        showConfirmButton: false,
                    })
                },
            })
        }
    })
}

// ─── Helpers ────────────────────────────────────────────
function getKoiInfo(purchase: Purchase): { code: string; name: string } {
    if (purchase.koi) {
        return { code: purchase.koi.code, name: purchase.koi.name }
    }
    const koi = kois.value.find((k) => k.id === purchase.koi_id)
    return { code: koi?.code ?? '-', name: koi?.name ?? '-' }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Pembelian" />

        <div class="flex flex-col gap-4 p-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Pembelian</h1>
                    <p class="text-sm text-muted-foreground">Kelola pembelian koi.</p>
                </div>

                <div class="flex gap-2">
                    <Link
                        href="/pembelian/print"
                        target="_blank"
                        class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        🖨️ Cetak
                    </Link>
                    <Dialog v-model:open="showModal">
                        <DialogTrigger as-child>
                            <Button>Tambah Pembelian</Button>
                        </DialogTrigger>
                    <DialogContent class="flex max-h-[90vh] flex-col p-0 sm:max-w-[600px]">
                        <DialogHeader class="flex-shrink-0 border-b px-6 py-4">
                            <DialogTitle>Tambah Pembelian</DialogTitle>
                            <DialogDescription>Tambah data pembelian koi baru.</DialogDescription>
                        </DialogHeader>

                        <form @submit.prevent="submit" class="flex flex-1 flex-col overflow-hidden">
                            <div class="flex-1 space-y-5 overflow-y-auto px-6 py-5">

                                <!-- ══════ Section 1: Informasi Dasar ══════ -->
                                <fieldset class="space-y-4">

                                    <!-- Koi -->
                                    <div class="grid gap-1.5">
                                        <Label for="koi_id">Koi <span class="text-destructive">*</span></Label>
                                        <Select v-model="form.koi_id">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Pilih koi" />
                                            </SelectTrigger>
                                            <SelectContent class="w-full max-w-[320px] p-0" side="bottom" align="start">
                                                <div class="sticky top-0 border-b bg-background p-2">
                                                    <Input
                                                        v-model="koiSearch"
                                                        placeholder="Cari koi..."
                                                        class="h-8"
                                                        @keydown.stop
                                                    />
                                                </div>
                                                <div class="max-h-48 overflow-y-auto">
                                                    <div
                                                        v-if="filteredKois.length === 0"
                                                        class="px-2 py-2 text-sm text-muted-foreground"
                                                    >
                                                        Koi tidak ditemukan
                                                    </div>
                                                    <SelectItem
                                                        v-for="koi in filteredKois"
                                                        :key="koi.id"
                                                        :value="String(koi.id)"
                                                    >
                                                        {{ koi.code }} - {{ koi.name }}
                                                    </SelectItem>
                                                </div>
                                            </SelectContent>
                                        </Select>
                                        <InputError :message="form.errors.koi_id" />
                                    </div>

                                    <!-- Tanggal + Qty -->
                                    <div class="grid grid-cols-2 items-start gap-4">
                                        <div class="grid gap-1.5">
                                            <Label for="date">Tanggal <span class="text-destructive">*</span></Label>
                                            <Input id="date" v-model="form.date" type="date" />
                                            <InputError :message="form.errors.date" />
                                        </div>
                                        <div class="grid gap-1.5">
                                            <Label for="qty">Qty <span class="text-destructive">*</span></Label>
                                            <Input
                                                id="qty"
                                                v-model="form.qty"
                                                type="number"
                                                min="1"
                                                placeholder="0"
                                            />
                                            <InputError :message="form.errors.qty" />
                                        </div>
                                    </div>
                                </fieldset>

                                <!-- ══════ Section 2: Harga & Kurs ══════ -->
                                <fieldset class="space-y-4 rounded-lg border border-border bg-muted/20 p-4">
                                    <legend class="mb-1 rounded bg-primary/10 px-2 py-0.5 text-xs font-semibold uppercase tracking-widest text-primary">
                                        Harga &amp; Kurs
                                    </legend>

                                    <!-- Harga JPY + Kurs -->
                                    <div class="grid grid-cols-2 items-start gap-4">
                                        <div class="grid gap-1.5">
                                            <Label for="price_jpy">Harga JPY (per ekor) <span class="text-destructive">*</span></Label>
                                            <Input
                                                id="price_jpy"
                                                :model-value="formatCurrencyDisplay(form.price_jpy)"
                                                @update:model-value="form.price_jpy = parseCurrencyInput($event)"
                                                type="text"
                                                inputmode="numeric"
                                                placeholder="0"
                                            />
                                            <InputError :message="form.errors.price_jpy" />
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
                                                    @update:model-value="(val) => { exchangeRate = Number(parseCurrencyInput(val)) || 0; exchangeRateManual = true; isPriceIdrEdited = false }"
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

                                    <!-- Harga IDR (auto / bisa override) -->
                                    <div class="grid gap-1.5">
                                        <Label for="price_idr">
                                            Harga IDR (per ekor)
                                            <span class="text-xs text-muted-foreground">(auto dari JPY × Kurs)</span>
                                        </Label>
                                        <Input
                                            id="price_idr"
                                            :model-value="formatCurrencyDisplay(form.price_idr)"
                                            @update:model-value="(val) => { form.price_idr = parseCurrencyInput(val); isPriceIdrEdited = true }"
                                            type="text"
                                            inputmode="numeric"
                                            placeholder="0"
                                        />
                                        <InputError :message="form.errors.price_idr" />
                                    </div>
                                </fieldset>

                                <!-- Info box -->
                                <div class="rounded-md border border-dashed border-muted-foreground/25 bg-muted/20 px-4 py-2.5 text-xs leading-relaxed text-muted-foreground">
                                    <div class="font-medium mb-1">Nilai Tukar Mata Uang:</div>
                                    <div class="mb-2">
                                        ¥1 = Rp {{ exchangeRate.toLocaleString('id-ID') }}
                                        <span v-if="exchangeRateManual" class="text-amber-600">(diubah manual)</span>
                                        <span v-else-if="exchangeRateError" class="text-amber-600">(menggunakan cadangan)</span>
                                        <span v-else class="text-green-600">(langsung dari internet)</span>
                                    </div>
                                    <div class="font-medium mb-1">Cara Menghitung Harga:</div>
                                    <div>• Harga per ekor koi</div>
                                    <div>• Rupiah = Yen × Nilai Tukar</div>
                                    <div>• Anda bisa mengubah harga Rupiah secara manual jika diperlukan</div>
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
            </div>

            <!-- DataTable -->
            <div class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                <div
                    class="flex flex-col gap-2 border-b border-border p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <Input
                        :model-value="search"
                        @update:model-value="onSearch"
                        placeholder="Cari koi atau status..."
                        class="max-w-sm"
                    />
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted-foreground">Show</span>
                        <select
                            :value="perPage"
                            @change="
                                (e) => {
                                    perPage = Number((e.target as HTMLSelectElement).value)
                                    currentPage = 1
                                }
                            "
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
                                <th
                                    class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('date')"
                                >
                                    Tanggal <span class="ml-1">{{ sortIcon('date') }}</span>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Koi
                                </th>
                              
                                <th
                                    class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('qty')"
                                >
                                    Qty <span class="ml-1">{{ sortIcon('qty') }}</span>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Harga JPY /ekor
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Harga IDR /ekor
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-background">
                            <tr v-if="paginatedPurchases.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                    {{
                                        search
                                            ? 'Tidak ada hasil untuk pencarian tersebut.'
                                            : 'Belum ada data pembelian.'
                                    }}
                                </td>
                            </tr>
                            <tr
                                v-for="purchase in paginatedPurchases"
                                :key="purchase.id"
                                class="transition-colors hover:bg-muted/50"
                            >
                                <td class="px-4 py-3 text-sm text-foreground">
                                    {{ new Date(purchase.date).toLocaleDateString('id-ID') }}
                                </td>
                                <td class="px-4 py-3 text-sm text-foreground">
                                    <div class="flex items-center gap-3">
                                        <button
                                            v-if="purchase.koi?.photo"
                                            type="button"
                                            class="h-10 w-10 overflow-hidden rounded-full border border-border bg-muted/50 transition hover:scale-105"
                                            @click="openPreview(purchase)"
                                        >
                                            <img
                                                :src="storagePath(purchase.koi.photo)"
                                                :alt="getKoiInfo(purchase).name ?? 'Koi'"
                                                class="h-full w-full object-cover"
                                            />
                                        </button>
                                        <div>
                                            <div class="font-medium">{{ getKoiInfo(purchase).code }}</div>
                                            <div class="max-w-[200px] truncate text-xs text-muted-foreground">
                                                {{ getKoiInfo(purchase).name }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                             
                                <td class="px-4 py-3 text-sm text-foreground">{{ purchase.qty }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-foreground">
                                    {{ formatJPY(purchase.price_jpy) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-foreground">
                                    {{ formatIDR(purchase.price_idr) }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link
                                            :href="`/pembelian/${purchase.id}`"
                                            class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background p-2 text-foreground shadow-sm hover:bg-muted"
                                            title="Detail"
                                        >
                                            <Pencil :size="16" />
                                        </Link>
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center rounded-md border border-red-300 bg-red-50 p-2 text-red-700 shadow-sm hover:bg-red-100 dark:border-red-700 dark:bg-red-900 dark:text-red-200 dark:hover:bg-red-800"
                                            title="Hapus"
                                            @click="confirmDelete(purchase.id)"
                                        >
                                            <Trash2 :size="16" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    class="flex flex-col items-center justify-between gap-2 border-t border-border px-4 py-3 sm:flex-row"
                >
                    <p class="text-sm text-muted-foreground">
                        Showing {{ showingFrom }} to {{ showingTo }} of
                        {{ filteredPurchases.length }} entries
                        <span v-if="search">(filtered from {{ purchases.length }} total)</span>
                    </p>

                    <div class="flex items-center gap-1">
                        <Button variant="outline" size="sm" :disabled="currentPage <= 1" @click="currentPage--">
                            Previous
                        </Button>

                        <template v-for="p in totalPages" :key="p">
                            <Button
                                size="sm"
                                :variant="p === currentPage ? 'default' : 'outline'"
                                @click="currentPage = p"
                            >
                                {{ p }}
                            </Button>
                        </template>

                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="currentPage >= totalPages"
                            @click="currentPage++"
                        >
                            Next
                        </Button>
                    </div>
                </div>
            </div>

            <Dialog v-model:open="previewOpen">
                <DialogContent class="sm:max-w-[650px]">
                    <DialogHeader>
                        <DialogTitle>{{ previewKoiName || 'Preview Gambar Koi' }}</DialogTitle>
                        <DialogDescription>Perbesar gambar koi dengan klik thumbnail.</DialogDescription>
                    </DialogHeader>
                    <div class="p-4">
                        <div v-if="previewImage" class="mx-auto max-h-[70vh] w-full overflow-hidden rounded-2xl border border-border bg-background p-4">
                            <img :src="previewImage" :alt="previewKoiName" class="mx-auto h-full w-full max-w-full object-contain" />
                        </div>
                        <div v-else class="flex h-48 items-center justify-center rounded-2xl border border-border bg-muted text-muted-foreground">
                            Gambar tidak tersedia.
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>

<style scoped>
@media print {
    body {
        margin: 0;
        padding: 0;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    th, td {
        border: 1px solid black;
        padding: 8px;
    }
    
    th {
        background-color: #f0f0f0;
        font-weight: bold;
    }
    
    tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    
    .no-print {
        display: none;
    }
    
    /* Prevent page breaks inside table rows */
    tr {
        page-break-inside: avoid;
    }
}
</style>