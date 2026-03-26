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

type Koi = {
    id: number
    code: string
    name: string
}

type Stock = {
    id: number
    koi_id: number
    date: string
    qty: number
    price_jpy: number
    price_idr: number
    total_jpy: number
    total_idr: number
    created_at: string
    koi?: Koi
}

const page = usePage()
const stocks = computed(() => (page.props.stocks as { data: Stock[] } | null)?.data ?? [])
const kois = computed(() => (page.props.kois as Koi[] | null) ?? [])

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Manajemen Stok',
        href: '/inventory',
    },
]

function fireSwal(options: SweetAlertOptions) {
    return Swal.fire({
        ...options,
        scrollbarPadding: false,
        heightAuto: false,
    })
}

const search = ref('')
const sortKey = ref<keyof Stock>('date')
const sortOrder = ref<'asc' | 'desc'>('desc')
const currentPage = ref(1)
const perPage = ref(10)

const filteredStocks = computed(() => {
    let result = stocks.value

    if (search.value) {
        const q = search.value.toLowerCase()
        result = result.filter(
            (s) =>
                s.koi?.code.toLowerCase().includes(q) ||
                s.koi?.name.toLowerCase().includes(q),
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

const totalPages = computed(() => Math.ceil(filteredStocks.value.length / perPage.value) || 1)

const paginatedStocks = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return filteredStocks.value.slice(start, start + perPage.value)
})

const showingFrom = computed(() =>
    filteredStocks.value.length === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1,
)
const showingTo = computed(() =>
    Math.min(currentPage.value * perPage.value, filteredStocks.value.length),
)

function onSearch(value: string | number) {
    search.value = String(value)
    currentPage.value = 1
}

function toggleSort(key: keyof Stock) {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortKey.value = key
        sortOrder.value = 'asc'
    }
}

function sortIcon(key: keyof Stock) {
    if (sortKey.value !== key) return '↕'
    return sortOrder.value === 'asc' ? '↑' : '↓'
}

const showModal = ref(false)
const koiSearch = ref('')

const filteredKois = computed(() => {
    if (!koiSearch.value) return kois.value
    const q = koiSearch.value.toLowerCase()
    return kois.value.filter(k => k.code.toLowerCase().includes(q) || k.name.toLowerCase().includes(q))
})

const form = useForm({
    koi_id: '',
    date: new Date().toISOString().split('T')[0],
    qty: '',
    price_jpy: '',
    price_idr: '',
    total_jpy: '',
    total_idr: '',
})

// Auto-convert Price JPY to Price IDR (1 JPY = 110 IDR)
watch(() => form.price_jpy, (newValue) => {
    if (newValue) {
        const priceJpy = parseInt(parseCurrency(String(newValue))) || 0
        form.price_idr = String(priceJpy * 110)
    }
})

// Auto-calculate totals when price or qty changes
watch([() => form.price_jpy, () => form.price_idr, () => form.qty], () => {
    const qty = parseInt(String(form.qty)) || 0
    const priceJpy = parseInt(parseCurrency(String(form.price_jpy))) || 0
    const priceIdr = parseInt(parseCurrency(String(form.price_idr))) || 0
    
    form.total_jpy = String(qty * priceJpy)
    form.total_idr = String(qty * priceIdr)
})

const submit = () => {
    form.post('/inventory', {
        preserveScroll: true,
        onSuccess: () => {
            fireSwal({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data stok berhasil ditambahkan.',
                timer: 3000,
                showConfirmButton: false,
            })
            form.reset()
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

function confirmDelete(stockId: number) {
    fireSwal({
        title: 'Hapus stok?',
        text: 'Data stok akan dihapus permanen.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/inventory/${stockId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    fireSwal({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Stok berhasil dihapus.',
                        timer: 3000,
                        showConfirmButton: false,
                    })
                },
                onError: () => {
                    fireSwal({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Terjadi kesalahan saat menghapus stok.',
                        timer: 3000,
                        showConfirmButton: false,
                    })
                },
            })
        }
    })
}

function getKoiInfo(koiId: number): { code: string; name: string } {
    const koi = kois.value.find(k => k.id === koiId)
    return { code: koi?.code ?? '-', name: koi?.name ?? '-' }
}

function formatCurrency(value: string | number): string {
    if (!value) return ''
    const num = String(value).replace(/\D/g, '')
    return num.replace(/\B(?=(\d{3})+(?!\d))/g, '.')
}

function parseCurrency(value: string): string {
    return value.replace(/\./g, '')
}

function formatJPY(value: number | string | undefined): string {
    if (!value) return '¥ 0'
    const num = Number(value)
    return '¥ ' + num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
}

function formatIDR(value: number | string | undefined): string {
    if (!value) return 'Rp 0'
    const num = Number(value)
    return 'Rp ' + num.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Manajemen Stok" />

        <div class="flex flex-col gap-4 p-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Manajemen Stok</h1>
                    <p class="text-sm text-muted-foreground">Manage koi stock inventory.</p>
                </div>

                <Dialog v-model:open="showModal">
                    <DialogTrigger as-child>
                        <Button>Add Stock</Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-[600px]">
                        <DialogHeader>
                            <DialogTitle>Add Stock</DialogTitle>
                            <DialogDescription>Add new stock entry.</DialogDescription>
                        </DialogHeader>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div class="space-y-4">
                                <div class="grid gap-2">
                                    <Label for="koi_id">Koi *</Label>
                                    <Select v-model="form.koi_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select koi" />
                                        </SelectTrigger>
                                        <SelectContent class="p-0 w-[--radix-select-trigger-width] min-w-[20rem]">
                                            <div class="sticky top-0 bg-background p-2 border-b">
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

                                <div class="grid gap-2">
                                    <Label for="date">Date *</Label>
                                    <Input id="date" v-model="form.date" type="date" />
                                    <InputError :message="form.errors.date" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="qty">Quantity *</Label>
                                    <Input id="qty" v-model="form.qty" type="number" placeholder="0" />
                                    <InputError :message="form.errors.qty" />
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="grid gap-2">
                                        <Label for="price_jpy">Price JPY *</Label>
                                        <Input id="price_jpy" :model-value="formatCurrency(form.price_jpy)" @update:model-value="form.price_jpy = parseCurrency($event)" type="text" placeholder="0" />
                                        <InputError :message="form.errors.price_jpy" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="price_idr">Price IDR * (Auto)</Label>
                                        <Input id="price_idr" :model-value="formatCurrency(form.price_idr)" type="text" placeholder="0" disabled class="bg-muted cursor-not-allowed" />
                                        <InputError :message="form.errors.price_idr" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="grid gap-2">
                                        <Label for="total_jpy">Total JPY * (Auto)</Label>
                                        <Input id="total_jpy" :model-value="formatCurrency(form.total_jpy)" type="text" placeholder="0" disabled class="bg-muted cursor-not-allowed" />
                                        <InputError :message="form.errors.total_jpy" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="total_idr">Total IDR * (Auto)</Label>
                                        <Input id="total_idr" :model-value="formatCurrency(form.total_idr)" type="text" placeholder="0" disabled class="bg-muted cursor-not-allowed" />
                                        <InputError :message="form.errors.total_idr" />
                                    </div>
                                </div>
                            </div>
                            <DialogFooter>
                                <Button type="submit" :disabled="form.processing">Add</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                <div class="flex flex-col gap-2 border-b border-border p-4 sm:flex-row sm:items-center sm:justify-between">
                    <Input :model-value="search" @update:model-value="onSearch" placeholder="Search koi..."
                        class="max-w-sm" />
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted-foreground">Show</span>
                        <select :value="perPage"
                            @change="(e) => { perPage = Number((e.target as HTMLSelectElement).value); currentPage = 1 }"
                            class="rounded-md border border-input bg-background px-3 py-1 text-sm">
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
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('date')">
                                    Date <span class="ml-1">{{ sortIcon('date') }}</span>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Koi
                                </th>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('qty')">
                                    Qty <span class="ml-1">{{ sortIcon('qty') }}</span>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Price JPY
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Price IDR
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-background">
                            <tr v-if="paginatedStocks.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                    {{ search ? 'Tidak ada hasil untuk pencarian tersebut.' : 'Belum ada data stok.' }}
                                </td>
                            </tr>
                            <tr v-for="stock in paginatedStocks" :key="stock.id" class="transition-colors hover:bg-muted/50">
                                <td class="px-4 py-3 text-sm text-foreground">{{ new Date(stock.date).toLocaleDateString() }}</td>
                                <td class="px-4 py-3 text-sm text-foreground">
                                    <div>{{ getKoiInfo(stock.koi_id).code }}</div>
                                    <div class="text-xs text-muted-foreground">{{ getKoiInfo(stock.koi_id).name }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-foreground">{{ stock.qty }}</td>
                                <td class="px-4 py-3 text-sm text-foreground">{{ formatJPY(stock.price_jpy) }}</td>
                                <td class="px-4 py-3 text-sm text-foreground">{{ formatIDR(stock.price_idr) }}</td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <Link :href="`/inventory/${stock.id}`"
                                        class="mr-2 rounded-md border border-border px-3 py-1 text-xs font-medium text-foreground hover:bg-muted">
                                        Detail
                                    </Link>
                                    <Link :href="`/inventory/${stock.id}/edit`"
                                        class="mr-2 rounded-md border border-border px-3 py-1 text-xs font-medium text-foreground hover:bg-muted">
                                        Edit
                                    </Link>
                                    <button type="button"
                                        class="rounded-md border border-red-500 bg-red-500/10 px-3 py-1 text-xs font-medium text-red-700 hover:bg-red-500/20"
                                        @click="confirmDelete(stock.id)">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col items-center justify-between gap-2 border-t border-border px-4 py-3 sm:flex-row">
                    <p class="text-sm text-muted-foreground">
                        Showing {{ showingFrom }} to {{ showingTo }} of {{ filteredStocks.length }} entries
                        <span v-if="search">(filtered from {{ stocks.length }} total)</span>
                    </p>

                    <div class="flex items-center gap-1">
                        <Button variant="outline" size="sm" :disabled="currentPage <= 1" @click="currentPage--">
                            Previous
                        </Button>

                        <template v-for="p in totalPages" :key="p">
                            <Button size="sm" :variant="p === currentPage ? 'default' : 'outline'"
                                @click="currentPage = p">
                                {{ p }}
                            </Button>
                        </template>

                        <Button variant="outline" size="sm" :disabled="currentPage >= totalPages"
                            @click="currentPage++">
                            Next
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
