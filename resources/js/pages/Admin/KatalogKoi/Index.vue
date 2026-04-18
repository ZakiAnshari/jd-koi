<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import InputError from '@/components/InputError.vue'
import { useSwal } from '@/composables/useSwal'
import type { BreadcrumbItem } from '@/types'
import { X, Pencil, Trash2 } from 'lucide-vue-next'
import { store as katalogStore, update as katalogUpdate, destroy as katalogDestroy } from '@/routes/admin/katalog-koi'

interface Breeder {
    id: number
    name_breeder: string
}

interface Koi {
    id: number
    code: string
    name: string
    breeder?: Breeder
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

interface Paginated {
    data: KatalogKoi[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
    path: string
}

const props = defineProps<{
    katalogs: Paginated
    pembelians: Pembelian[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '#' },
    { title: 'Katalog Koi', href: '/admin/katalog-koi' },
]

// Modal state
const showModal = ref(false)
const editingId = ref<number | null>(null)
const tableSearch = ref('')
const tablePerPage = ref(10)
const tableCurrentPage = ref(1)

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

const sellJpyDisplay = ref('')
const sellIdrDisplay = ref('')

function onSellJpyInput(e: Event) {
    const input = e.target as HTMLInputElement
    // Remove all non-digit characters and dots
    const raw = input.value.replace(/\./g, '').replace(/[^0-9]/g, '')
    const num = parseInt(raw) || 0
    form.sell_jpy = num
    // Format display dengan pemisah ribuan (titik untuk locale id-ID)
    sellJpyDisplay.value = num > 0 ? num.toLocaleString('id-ID') : ''
    // Update input display juga
    input.value = sellJpyDisplay.value
}

function onSellIdrInput(e: Event) {
    const input = e.target as HTMLInputElement
    // Remove all non-digit characters and dots
    const raw = input.value.replace(/\./g, '').replace(/[^0-9]/g, '')
    const num = parseInt(raw) || 0
    form.sell_idr = num
    // Format display dengan pemisah ribuan (titik untuk locale id-ID)
    sellIdrDisplay.value = num > 0 ? num.toLocaleString('id-ID') : ''
    // Update input display juga
    input.value = sellIdrDisplay.value
}

// Computed: selected pembelian for prefill
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
    form.sell_idr = form.sell_jpy * form.kurs
}

// Watch sell_jpy + kurs → auto update sell_idr
watch(() => [form.sell_jpy, form.kurs] as const, ([jpy, kurs]) => {
    form.sell_idr = Math.round(jpy * kurs)
    // Update display ketika auto-calculated
    sellIdrDisplay.value = form.sell_idr > 0 ? form.sell_idr.toLocaleString('id-ID') : ''
})

// Realtime calculations
const margin = computed(() => form.sell_idr - form.stock_idr)
const profit = computed(() => margin.value - form.fee)
const profitPct = computed(() => {
    if (form.stock_idr === 0) return 0
    return (profit.value / form.stock_idr) * 100
})

// Table filtering
const filteredKatalogs = computed(() => {
    if (!tableSearch.value.trim()) return props.katalogs.data
    
    const query = tableSearch.value.toLowerCase()
    return props.katalogs.data.filter(k => {
        const kode = k.koi?.code?.toLowerCase() ?? ''
        const nama = k.koi?.name?.toLowerCase() ?? ''
        const breeder = k.koi?.breeder?.name_breeder?.toLowerCase() ?? ''
        return kode.includes(query) || nama.includes(query) || breeder.includes(query)
    })
})

const totalPages = computed(() => Math.ceil(filteredKatalogs.value.length / tablePerPage.value) || 1)

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

// Formatting
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

// Open create modal
function openCreate() {
    editingId.value = null
    selectedPembelianId.value = ''
    pembelianSearchQuery.value = ''
    sellJpyDisplay.value = ''
    sellIdrDisplay.value = ''
    form.reset()
    showModal.value = true
}

// Open edit modal
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
    // Set display values
    sellJpyDisplay.value = form.sell_jpy > 0 ? form.sell_jpy.toLocaleString('id-ID') : ''
    sellIdrDisplay.value = form.sell_idr > 0 ? form.sell_idr.toLocaleString('id-ID') : ''
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
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Koi</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Breeder</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Harga Jual</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Profit</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-background">
                            <tr v-if="paginatedKatalogs.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                    Belum ada katalog koi.
                                    <button @click="openCreate" class="text-blue-600 hover:underline">Buat yang pertama</button>.
                                </td>
                            </tr>
                            <tr
                                v-for="katalog in paginatedKatalogs"
                                :key="katalog.id"
                                class="hover:bg-muted/50"
                            >
                                <td class="whitespace-nowrap px-4 py-3 text-sm">{{ fmtDate(katalog.tanggal) }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-sm">{{ katalog.koi?.name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ katalog.koi?.code }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm">{{ katalog.koi?.breeder?.name_breeder ?? '-' }}</td>
                                <td class="px-4 py-3 text-right text-sm font-medium">{{ fmtIDR(Number(katalog.sell_idr)) }}</td>
                                <td class="px-4 py-3 text-right text-sm" :class="profitClass(Number(katalog.profit))">
                                    {{ fmtIDR(Number(katalog.profit)) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
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
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="flex items-center justify-between rounded-lg border border-sidebar-border/70 bg-background px-4 py-3 dark:border-sidebar-border">
                <p class="text-sm text-muted-foreground">
                    Menampilkan {{ showingFrom }} hingga {{ showingTo }} dari {{ filteredKatalogs.length }} data
                </p>
                <div class="flex items-center gap-2">
                    <button
                        v-if="tableCurrentPage > 1"
                        @click="tableCurrentPage--"
                        class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background px-3 py-1 text-sm hover:bg-muted"
                    >
                        ← Sebelumnya
                    </button>
                    <div class="flex items-center gap-1">
                        <button
                            v-for="page in totalPages"
                            :key="page"
                            @click="tableCurrentPage = page"
                            :class="[
                                'inline-flex h-8 w-8 items-center justify-center rounded-md text-sm font-medium',
                                tableCurrentPage === page
                                    ? 'bg-blue-600 text-white'
                                    : 'border border-neutral-200 bg-background hover:bg-muted'
                            ]"
                        >
                            {{ page }}
                        </button>
                    </div>
                    <button
                        v-if="tableCurrentPage < totalPages"
                        @click="tableCurrentPage++"
                        class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background px-3 py-1 text-sm hover:bg-muted"
                    >
                        Berikutnya →
                    </button>
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
                                <!-- Select with Search -->
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

                                <!-- Info box pembelian terpilih -->
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
                                <!-- Tanggal -->
                                <div class="grid gap-2">
                                    <Label>Tanggal <span class="text-red-500">*</span></Label>
                                    <Input v-model="form.tanggal" type="date" />
                                    <InputError :message="form.errors.tanggal" />
                                </div>

                                <!-- Qty -->
                                <div class="grid gap-2">
                                    <Label>Qty <span class="text-red-500">*</span></Label>
                                    <Input v-model.number="form.qty" type="number" min="1" />
                                    <InputError :message="form.errors.qty" />
                                </div>

                                <!-- Sell JPY -->
                                <div class="grid gap-2">
                                    <Label>Sell JPY <span class="text-red-500">*</span></Label>
                                    <Input
                                        type="text"
                                        :value="sellJpyDisplay"
                                        placeholder="0"
                                        @input="onSellJpyInput"
                                        inputmode="numeric"
                                    />
                                    <InputError :message="form.errors.sell_jpy" />
                                </div>

                                <!-- Kurs -->
                                <div class="grid gap-2">
                                    <Label>Kurs (JPY → IDR) <span class="text-red-500">*</span></Label>
                                    <Input v-model.number="form.kurs" type="number" min="0" placeholder="0" />
                                    <InputError :message="form.errors.kurs" />
                                </div>

                                <!-- Sell IDR -->
                                <div class="grid gap-2">
                                    <Label>Sell IDR <span class="text-red-500">*</span></Label>
                                    <Input
                                        type="text"
                                        :value="sellIdrDisplay"
                                        placeholder="0"
                                        @input="onSellIdrInput"
                                        inputmode="numeric"
                                    />
                                    <InputError :message="form.errors.sell_idr" />
                                </div>

                                <!-- Fee -->
                                <div class="grid gap-2">
                                    <Label>Fee IDR</Label>
                                    <Input v-model.number="form.fee" type="number" min="0" placeholder="0" />
                                    <InputError :message="form.errors.fee" />
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 3: Kalkulasi Otomatis -->
                        <div class="rounded-lg bg-gray-100 p-4 text-sm dark:bg-gray-900">
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
                                <div class="my-1 border-t border-gray-300 dark:border-gray-700" />
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Margin (Sell IDR − Stock IDR):</span>
                                    <span class="font-semibold" :class="profitClass(margin)">{{ fmtIDR(margin) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Fee:</span>
                                    <span class="font-medium">{{ fmtIDR(form.fee) }}</span>
                                </div>
                                <div class="my-1 border-t border-gray-300 dark:border-gray-700" />
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
