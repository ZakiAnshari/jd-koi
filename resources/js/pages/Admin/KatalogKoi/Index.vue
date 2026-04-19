<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { computed, ref, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import InputError from '@/components/InputError.vue'
import { useSwal } from '@/composables/useSwal'
import type { BreadcrumbItem } from '@/types'
import { X, Pencil, Trash2, Eye, ImagePlus } from 'lucide-vue-next'

const katalogStore = { url: () => '/admin/katalog-koi' }
const katalogUpdate = { url: (id: number) => `/admin/katalog-koi/${id}` }
const katalogDestroy = { url: (id: number) => `/admin/katalog-koi/${id}` }

interface Breeder {
    id: number
    name_breeder: string
}

interface KoiImage {
    id: number
    url: string
}

interface Koi {
    id: number
    code: string
    name: string
    breeder?: Breeder
    images?: KoiImage[]
}

interface Pembelian {
    id: number
    date: string
    qty: number
    price_jpy: number
    price_idr: number
    koi?: Koi
}

interface KatalogKoi {
    id: number
    tanggal: string
    qty: number
    sell_jpy: number
    kurs: number
    sell_idr: number
    margin: number
    stock_jpy: number
    stock_idr: number
    fee: number
    profit: number
    koi?: Koi
    pembelian?: Pembelian
}

const props = defineProps<{
    katalogs: KatalogKoi[]
    pembelians: Pembelian[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '#' },
    { title: 'Katalog Koi', href: '/admin/katalog-koi' },
]

// ─── Modal state ──────────────────────────────────────────
const showModal = ref(false)
const editingId = ref<number | null>(null)
const tableSearch = ref('')
const tablePerPage = ref(10)
const tableCurrentPage = ref(1)
const sortKey = ref<keyof KatalogKoi>('tanggal')
const sortOrder = ref<'asc' | 'desc'>('desc')

const isEditing = computed(() => editingId.value !== null)

const form = useForm({
    tanggal: '',
    koi_id: 0,
    pembelian_id: 0,
    qty: 1,
    sell_jpy: 0,
    kurs: 0,
    sell_idr: 0,
    stock_jpy: 0,
    stock_idr: 0,
    fee: 0,
})

// ─── Pembelian select ─────────────────────────────────────
const selectedPembelianId = ref<string>('')
const pembelianSearchQuery = ref<string>('')

const selectedPembelian = computed<Pembelian | null>(() => {
    if (!selectedPembelianId.value) return null
    return props.pembelians.find(p => p.id === parseInt(selectedPembelianId.value)) ?? null
})

const filteredPembelians = computed<Pembelian[]>(() => {
    if (!pembelianSearchQuery.value.trim()) return props.pembelians
    const query = pembelianSearchQuery.value.toLowerCase()
    return props.pembelians.filter(p => {
        const kode = p.koi?.code?.toLowerCase() ?? ''
        const nama = p.koi?.name?.toLowerCase() ?? ''
        const breeder = p.koi?.breeder?.name_breeder?.toLowerCase() ?? ''
        const tgl = new Date(p.date).toLocaleDateString('id-ID')
        return kode.includes(query) || nama.includes(query) || breeder.includes(query) || tgl.includes(query)
    })
})

function onPembelianChange(val: string | null) {
    if (!val) return
    selectedPembelianId.value = val
    const p = selectedPembelian.value
    if (!p) return
    form.pembelian_id = p.id
    form.koi_id = p.koi?.id ?? 0
    form.qty = p.qty
    form.stock_jpy = p.price_jpy
    form.stock_idr = p.price_idr
    form.kurs = p.price_jpy > 0 ? Math.round(p.price_idr / p.price_jpy) : 0
    form.sell_idr = Math.round(form.sell_jpy * form.kurs)
}

watch(() => [form.sell_jpy, form.kurs] as const, ([jpy, kurs]) => {
    form.sell_idr = Math.round(jpy * kurs)
})

const margin = computed(() => form.sell_idr - form.stock_idr)
const profit = computed(() => margin.value - form.fee)
const profitPct = computed(() => {
    if (form.stock_idr === 0) return 0
    return (profit.value / form.stock_idr) * 100
})

// ─── Table filtering + sorting ────────────────────────────
const filteredKatalogs = computed(() => {
    let result = props.katalogs
    if (tableSearch.value.trim()) {
        const query = tableSearch.value.toLowerCase()
        result = result.filter(k => {
            const kode = k.koi?.code?.toLowerCase() ?? ''
            const nama = k.koi?.name?.toLowerCase() ?? ''
            const breeder = k.koi?.breeder?.name_breeder?.toLowerCase() ?? ''
            return kode.includes(query) || nama.includes(query) || breeder.includes(query)
        })
    }
    result = [...result].sort((a, b) => {
        const aVal = (a as any)[sortKey.value]
        const bVal = (b as any)[sortKey.value]
        if (aVal == null || bVal == null) return 0
        if (typeof aVal === 'string' && typeof bVal === 'string')
            return sortOrder.value === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal)
        if (typeof aVal === 'number' && typeof bVal === 'number')
            return sortOrder.value === 'asc' ? aVal - bVal : bVal - aVal
        return 0
    })
    return result
})

const totalPages = computed(() => Math.ceil(filteredKatalogs.value.length / tablePerPage.value) || 1)
watch(totalPages, n => { if (tableCurrentPage.value > n) tableCurrentPage.value = 1 })

const paginatedKatalogs = computed(() => {
    const start = (tableCurrentPage.value - 1) * tablePerPage.value
    return filteredKatalogs.value.slice(start, start + tablePerPage.value)
})

const showingFrom = computed(() =>
    filteredKatalogs.value.length === 0 ? 0 : (tableCurrentPage.value - 1) * tablePerPage.value + 1
)
const showingTo = computed(() =>
    Math.min(tableCurrentPage.value * tablePerPage.value, filteredKatalogs.value.length)
)

function toggleSort(key: keyof KatalogKoi) {
    if (sortKey.value === key) sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    else { sortKey.value = key; sortOrder.value = 'asc' }
}
function sortIcon(key: keyof KatalogKoi) {
    if (sortKey.value !== key) return '↕'
    return sortOrder.value === 'asc' ? '↑' : '↓'
}

// ─── Formatting ───────────────────────────────────────────
function fmtIDR(v: number) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v)
}
function fmtJPY(v: number) {
    return new Intl.NumberFormat('ja-JP', { style: 'currency', currency: 'JPY', maximumFractionDigits: 0 }).format(v)
}
function fmtDate(d: string) {
    return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}
function profitClass(v: number) {
    return v >= 0 ? 'text-green-600 dark:text-green-400 font-semibold' : 'text-red-600 dark:text-red-400 font-semibold'
}
function pembelianLabel(p: Pembelian) {
    const kode = p.koi?.code ?? '?'
    const nama = p.koi?.name ?? '?'
    const breeder = p.koi?.breeder?.name_breeder ?? '-'
    const tgl = new Date(p.date).toLocaleDateString('id-ID')
    return `${kode} - ${nama} (${breeder}) | ${tgl}`
}

// ─── Modal actions ────────────────────────────────────────
function openCreate() {
    editingId.value = null
    selectedPembelianId.value = ''
    pembelianSearchQuery.value = ''
    form.reset()
    showModal.value = true
}

function openEdit(katalog: KatalogKoi) {
    editingId.value = katalog.id
    selectedPembelianId.value = ''
    form.tanggal = katalog.tanggal
    form.koi_id = katalog.koi?.id ?? 0
    form.pembelian_id = katalog.pembelian?.id ?? 0
    form.qty = katalog.qty
    form.sell_jpy = Number(katalog.sell_jpy)
    form.kurs = Number(katalog.kurs)
    form.sell_idr = Number(katalog.sell_idr)
    form.stock_jpy = Number(katalog.stock_jpy)
    form.stock_idr = Number(katalog.stock_idr)
    form.fee = Number(katalog.fee)
    showModal.value = true
}

function closeModal() {
    showModal.value = false
    editingId.value = null
    selectedPembelianId.value = ''
    pembelianSearchQuery.value = ''
    form.reset()
    form.clearErrors()
}

function submit() {
    if (isEditing.value) {
        form.put(katalogUpdate.url(editingId.value!), {
            preserveScroll: true,
            onSuccess: closeModal,
        })
    } else {
        form.post(katalogStore.url(), {
            preserveScroll: true,
            onSuccess: closeModal,
        })
    }
}

function handleDelete(katalog: KatalogKoi) {
    useSwal().fire({
        title: 'Hapus Katalog Koi?',
        text: `Hapus katalog koi ${katalog.koi?.code} - ${katalog.koi?.name}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Hapus',
        cancelButtonText: 'Batal',
    }).then(result => {
        if (result.isConfirmed) {
            router.delete(katalogDestroy.url(katalog.id), {
                preserveScroll: true,
                onSuccess: () => {
                    useSwal().fire({ icon: 'success', title: 'Terhapus!', timer: 1500, showConfirmButton: false })
                },
            })
        }
    })
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Katalog Koi" />

        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Katalog Koi</h1>
                    <p class="text-sm text-muted-foreground">Kelola katalog koi untuk ditawarkan ke customer.</p>
                </div>
                <Button @click="openCreate">+ Tambah Katalog</Button>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                <!-- Search & Settings -->
                <div class="flex flex-col gap-2 border-b border-border p-4 sm:flex-row sm:items-center sm:justify-between">
                    <Input
                        v-model="tableSearch"
                        placeholder="Cari kode, nama, atau breeder..."
                        class="max-w-sm"
                    />
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted-foreground">Show</span>
                        <select
                            :value="tablePerPage"
                            @change="(e) => { tablePerPage = Number((e.target as HTMLSelectElement).value); tableCurrentPage = 1 }"
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
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground w-16">
                                    Foto
                                </th>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('tanggal')">
                                    Tanggal <span class="ml-1">{{ sortIcon('tanggal') }}</span>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Koi</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Breeder</th>
                                <th class="cursor-pointer select-none px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('sell_idr')">
                                    Harga Jual <span class="ml-1">{{ sortIcon('sell_idr') }}</span>
                                </th>
                                <th class="cursor-pointer select-none px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('profit')">
                                    Profit <span class="ml-1">{{ sortIcon('profit') }}</span>
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-background">
                            <tr v-if="paginatedKatalogs.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                    {{ tableSearch ? 'Tidak ada hasil untuk pencarian tersebut.' : 'Belum ada katalog koi.' }}
                                </td>
                            </tr>
                            <tr v-for="katalog in paginatedKatalogs" :key="katalog.id"
                                class="transition-colors hover:bg-muted/50">
                                <!-- Thumbnail dari koi.images -->
                                <td class="px-4 py-2">
                                    <div class="w-12 h-12 rounded-lg overflow-hidden bg-muted flex items-center justify-center flex-shrink-0">
                                        <img v-if="katalog.koi?.images && katalog.koi.images.length > 0"
                                            :src="katalog.koi.images[0].url"
                                            :alt="katalog.koi?.name"
                                            class="w-full h-full object-cover"
                                        />
                                        <span v-else class="text-muted-foreground/40">
                                            <ImagePlus class="w-5 h-5" />
                                        </span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-foreground">{{ fmtDate(katalog.tanggal) }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-sm text-foreground">{{ katalog.koi?.name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ katalog.koi?.code }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-foreground">{{ katalog.koi?.breeder?.name_breeder ?? '-' }}</td>
                                <td class="px-4 py-3 text-right text-sm font-medium text-foreground">{{ fmtIDR(Number(katalog.sell_idr)) }}</td>
                                <td class="px-4 py-3 text-right text-sm" :class="profitClass(Number(katalog.profit))">
                                    {{ fmtIDR(Number(katalog.profit)) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link
                                            :href="`/admin/katalog-koi/${katalog.id}`"
                                            class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background p-2 text-foreground shadow-sm hover:bg-muted"
                                            title="Detail"
                                        >
                                            <Eye class="h-4 w-4" />
                                        </Link>
                                        <button
                                            @click="openEdit(katalog)"
                                            class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background p-2 text-foreground shadow-sm hover:bg-muted"
                                            title="Edit"
                                        >
                                            <Pencil class="h-4 w-4" />
                                        </button>
                                        <button
                                            @click="handleDelete(katalog)"
                                            class="inline-flex items-center justify-center rounded-md border border-red-300 bg-red-50 p-2 text-red-700 shadow-sm hover:bg-red-100 dark:border-red-700 dark:bg-red-900 dark:text-red-200 dark:hover:bg-red-800"
                                            title="Hapus"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer: info + pagination -->
                <div class="flex flex-col items-center justify-between gap-2 border-t border-border px-4 py-3 sm:flex-row">
                    <p class="text-sm text-muted-foreground">
                        Showing {{ showingFrom }} to {{ showingTo }} of {{ filteredKatalogs.length }} entries
                        <span v-if="tableSearch">(filtered from {{ katalogs.length }} total)</span>
                    </p>
                    <div class="flex items-center gap-1">
                        <Button variant="outline" size="sm" :disabled="tableCurrentPage <= 1" @click="tableCurrentPage--">Previous</Button>
                        <template v-for="p in totalPages" :key="p">
                            <Button size="sm" :variant="p === tableCurrentPage ? 'default' : 'outline'" @click="tableCurrentPage = p">{{ p }}</Button>
                        </template>
                        <Button variant="outline" size="sm" :disabled="tableCurrentPage >= totalPages" @click="tableCurrentPage++">Next</Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <Transition name="fade">
            <div
                v-if="showModal"
                class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 py-8"
                @click.self="closeModal"
            >
                <div class="w-full max-w-4xl rounded-xl bg-background p-6 shadow-xl dark:bg-background">
                    <!-- Modal Header -->
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-semibold">
                                {{ isEditing ? 'Edit Katalog Koi' : 'Tambah Katalog Koi' }}
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                {{ isEditing ? 'Perbarui data katalog koi.' : 'Pilih pembelian dan tentukan harga jual.' }}
                            </p>
                        </div>
                        <button @click="closeModal" class="text-muted-foreground hover:text-foreground">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">

                        <!-- SECTION 1: Pilih Pembelian (only on create) -->
                        <div v-if="!isEditing" class="rounded-lg border border-sidebar-border/70 bg-muted/30 p-4">
                            <h3 class="mb-4 font-semibold">Pilih Pembelian</h3>
                            <div class="space-y-4">
                                <div class="grid gap-2">
                                    <Label>Pembelian <span class="text-red-500">*</span></Label>
                                    <Select :model-value="selectedPembelianId" @update:model-value="onPembelianChange">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih atau ketik untuk cari..." />
                                        </SelectTrigger>
                                        <SelectContent class="p-0">
                                            <div class="sticky top-0 z-10 border-b bg-background p-2">
                                                <Input
                                                    v-model="pembelianSearchQuery"
                                                    type="text"
                                                    placeholder="Cari kode, nama, breeder..."
                                                    class="h-8"
                                                    @click.stop
                                                    @keydown.stop
                                                />
                                            </div>
                                            <div class="max-h-60 overflow-y-auto">
                                                <SelectItem v-for="p in filteredPembelians" :key="p.id" :value="String(p.id)">
                                                    {{ pembelianLabel(p) }}
                                                </SelectItem>
                                                <div v-if="filteredPembelians.length === 0" class="py-6 px-8 text-center text-sm text-muted-foreground">
                                                    Tidak ada hasil
                                                </div>
                                            </div>
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="form.errors.pembelian_id" />
                                </div>

                                <div v-if="selectedPembelian" class="rounded-lg bg-blue-50 p-4 text-sm dark:bg-blue-950">
                                    <p class="mb-2 font-semibold text-blue-800 dark:text-blue-200">Detail Pembelian</p>
                                    <div class="grid grid-cols-2 gap-3 text-blue-900 dark:text-blue-100 md:grid-cols-4">
                                        <div>
                                            <p class="text-xs opacity-70">Tanggal</p>
                                            <p class="font-medium">{{ fmtDate(selectedPembelian.date) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs opacity-70">Qty</p>
                                            <p class="font-medium">{{ selectedPembelian.qty }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs opacity-70">Harga Beli JPY</p>
                                            <p class="font-medium">{{ fmtJPY(selectedPembelian.price_jpy) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs opacity-70">Harga Beli IDR</p>
                                            <p class="font-medium">{{ fmtIDR(selectedPembelian.price_idr) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Info pembelian readonly saat edit -->
                        <div v-if="isEditing" class="rounded-lg border border-sidebar-border/70 bg-muted/30 p-4">
                            <h3 class="mb-2 font-semibold">Data Pembelian</h3>
                            <div class="grid grid-cols-2 gap-3 text-sm md:grid-cols-4">
                                <div>
                                    <p class="text-xs text-muted-foreground">Stock JPY</p>
                                    <p class="font-medium">{{ fmtJPY(form.stock_jpy) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Stock IDR</p>
                                    <p class="font-medium">{{ fmtIDR(form.stock_idr) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 2: Input Harga -->
                        <div class="rounded-lg border border-sidebar-border/70 bg-muted/30 p-4">
                            <h3 class="mb-4 font-semibold">Harga Jual & Detail</h3>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label>Tanggal <span class="text-red-500">*</span></Label>
                                    <Input v-model="form.tanggal" type="date" />
                                    <InputError :message="form.errors.tanggal" />
                                </div>
                                <div class="grid gap-2">
                                    <Label>Qty <span class="text-red-500">*</span></Label>
                                    <Input v-model.number="form.qty" type="number" min="1" />
                                    <InputError :message="form.errors.qty" />
                                </div>
                                <div class="grid gap-2">
                                    <Label>Sell JPY <span class="text-red-500">*</span></Label>
                                    <div class="flex items-center gap-2">
                                        <Input v-model.number="form.sell_jpy" type="number" placeholder="0" inputmode="numeric" class="flex-1" />
                                        <span class="whitespace-nowrap text-sm text-muted-foreground">{{ form.sell_jpy > 0 ? fmtJPY(form.sell_jpy) : '-' }}</span>
                                    </div>
                                    <InputError :message="form.errors.sell_jpy" />
                                </div>
                                <div class="grid gap-2">
                                    <Label>Kurs (JPY → IDR) <span class="text-red-500">*</span></Label>
                                    <Input v-model.number="form.kurs" type="number" min="0" placeholder="0" />
                                    <InputError :message="form.errors.kurs" />
                                </div>
                                <div class="grid gap-2">
                                    <Label>Sell IDR <span class="text-red-500">*</span></Label>
                                    <div class="flex items-center gap-2">
                                        <Input v-model.number="form.sell_idr" type="number" placeholder="0" inputmode="numeric" class="flex-1" />
                                        <span class="whitespace-nowrap text-sm text-muted-foreground">{{ form.sell_idr > 0 ? fmtIDR(form.sell_idr) : '-' }}</span>
                                    </div>
                                    <InputError :message="form.errors.sell_idr" />
                                </div>
                                <div class="grid gap-2">
                                    <Label>Fee IDR</Label>
                                    <Input v-model.number="form.fee" type="number" min="0" placeholder="0" />
                                    <InputError :message="form.errors.fee" />
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 3: Kalkulasi Otomatis -->
                        <div class="rounded-lg bg-muted/50 p-4 text-sm">
                            <h3 class="mb-3 font-semibold">Kalkulasi Otomatis</h3>
                            <div class="grid gap-2">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Stock JPY:</span>
                                    <span class="font-medium">{{ fmtJPY(form.stock_jpy) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Stock IDR:</span>
                                    <span class="font-medium">{{ fmtIDR(form.stock_idr) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Sell IDR:</span>
                                    <span class="font-medium">{{ fmtIDR(form.sell_idr) }}</span>
                                </div>
                                <div class="my-1 border-t border-border" />
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Margin (Sell IDR − Stock IDR):</span>
                                    <span class="font-semibold" :class="profitClass(margin)">{{ fmtIDR(margin) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Fee:</span>
                                    <span class="font-medium">{{ fmtIDR(form.fee) }}</span>
                                </div>
                                <div class="my-1 border-t border-border" />
                                <div class="flex justify-between text-base">
                                    <span class="font-semibold">Profit (Margin − Fee):</span>
                                    <span class="font-bold" :class="profitClass(profit)">
                                        {{ fmtIDR(profit) }}
                                        <span class="text-xs">({{ profitPct.toFixed(1) }}%)</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="flex items-center gap-3">
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                            </Button>
                            <Button type="button" variant="outline" @click="closeModal">Batal</Button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
