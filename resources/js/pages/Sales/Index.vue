<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
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

type SalesReport = {
    id: number
    day: number
    month: number
    year: number
    status: string
    breeder: string
    code: string
    photo: string | null
    name: string
    qty: number
    sell_jpy: number
    sell_idr: number
    margin: number
    fee: number
    stock_jpy: number
    stock_idr: number
    sell_to: string
    profit: number
    user_id: number
    created_at: string
    updated_at: string
}

const page = usePage()
const salesReports = computed(() => (page.props.salesReports as { data: SalesReport[] } | null)?.data ?? [])

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Sales Reports',
        href: '/sales-reports',
    },
]

// ─── SweetAlert Helper (prevent scrollbar hiding) ───────
function fireSwal(options: SweetAlertOptions) {
    return Swal.fire({
        ...options,
        scrollbarPadding: false,
        heightAuto: false,
    })
}

// ─── DataTable State ────────────────────────────────────
const search = ref('')
const sortKey = ref<keyof SalesReport>('name')
const sortOrder = ref<'asc' | 'desc'>('asc')
const currentPage = ref(1)
const perPage = ref(10)

// ─── Filtered + Sorted + Paginated ─────────────────────
const filteredReports = computed(() => {
    let result = salesReports.value

    if (search.value) {
        const q = search.value.toLowerCase()
        result = result.filter(
            (r) =>
                r.name.toLowerCase().includes(q) ||
                r.code.toLowerCase().includes(q) ||
                r.breeder.toLowerCase().includes(q),
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

const totalPages = computed(() => Math.ceil(filteredReports.value.length / perPage.value) || 1)

const paginatedReports = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return filteredReports.value.slice(start, start + perPage.value)
})

const showingFrom = computed(() =>
    filteredReports.value.length === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1,
)
const showingTo = computed(() =>
    Math.min(currentPage.value * perPage.value, filteredReports.value.length),
)

function onSearch(value: string | number) {
    search.value = String(value)
    currentPage.value = 1
}

function toggleSort(key: keyof SalesReport) {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortKey.value = key
        sortOrder.value = 'asc'
    }
}

function sortIcon(key: keyof SalesReport) {
    if (sortKey.value !== key) return '↕'
    return sortOrder.value === 'asc' ? '↑' : '↓'
}

// ─── Helper for display ────────────────────────────────
const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']

const displayDate = computed(() => {
    if (form.day && form.month && form.year) {
        const date = new Date(Number(form.year), Number(form.month) - 1, Number(form.day))
        const dayName = dayNames[date.getDay()]
        return `${dayName}, ${form.day} ${monthNames[Number(form.month) - 1]} ${form.year}`
    }
    return ''
})

// ─── Modal + Form ───────────────────────────────────────
const showModal = ref(false)

const form = useForm({
    day: '',
    month: '',
    year: '',
    status: '',
    breeder: '',
    code: '',
    photo: '',
    name: '',
    qty: '',
    sell_jpy: '',
    sell_idr: '',
    margin: '',
    fee: '',
    stock_jpy: '',
    stock_idr: '',
    sell_to: '',
    profit: '',
})

const submit = () => {
    form.post('/sales-reports', {
        preserveScroll: true,
        onSuccess: () => {
            fireSwal({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data laporan penjualan berhasil ditambahkan.',
                timer: 3000,
                showConfirmButton: false,
            })
            form.reset()
            showModal.value = false
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

function confirmDelete(reportId: number, reportName: string) {
    fireSwal({
        title: 'Hapus laporan penjualan?',
        text: `Laporan "${reportName}" akan dihapus permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/sales-reports/${reportId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    fireSwal({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Laporan penjualan berhasil dihapus.',
                        timer: 3000,
                        showConfirmButton: false,
                    })
                },
                onError: () => {
                    fireSwal({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Terjadi kesalahan saat menghapus laporan penjualan.',
                        timer: 3000,
                        showConfirmButton: false,
                    })
                },
            })
        }
    })
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Sales Reports" />

        <div class="flex flex-col gap-4 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Sales Reports</h1>
                    <p class="text-sm text-muted-foreground">Manage the sales reports in your system.</p>
                </div>

                <Dialog v-model:open="showModal">
                    <DialogTrigger as-child>
                        <Button>New report</Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-[425px] max-h-[80vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>Create sales report</DialogTitle>
                            <DialogDescription>Create a new sales report entry.</DialogDescription>
                        </DialogHeader>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div v-if="displayDate" class="rounded-md bg-muted p-3 text-sm font-medium text-foreground">
                                📅 {{ displayDate }}
                            </div>
                            <div class="grid gap-2">
                                <Label for="day">Day</Label>
                                <Input id="day" v-model="form.day" type="number" min="1" max="31" placeholder="Tanggal (1-31)" />
                                <InputError :message="form.errors.day" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="month">Month</Label>
                                <select v-model="form.month" class="rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="">Pilih Bulan</option>
                                    <option v-for="(month, index) in monthNames" :key="index" :value="index + 1">
                                        {{ month }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.month" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="year">Year</Label>
                                <Input id="year" v-model="form.year" type="number" placeholder="Tahun" />
                                <InputError :message="form.errors.year" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="status">Status</Label>
                                <Input id="status" v-model="form.status" placeholder="Status" />
                                <InputError :message="form.errors.status" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="breeder">Breeder</Label>
                                <Input id="breeder" v-model="form.breeder" placeholder="Breeder name" />
                                <InputError :message="form.errors.breeder" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="code">Code</Label>
                                <Input id="code" v-model="form.code" placeholder="Product code" />
                                <InputError :message="form.errors.code" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="photo">Photo</Label>
                                <Input id="photo" v-model="form.photo" type="file" accept="image/*" />
                                <InputError :message="form.errors.photo" />
                                <p class="text-xs text-muted-foreground">Format: JPG, PNG, GIF. Maksimal: 5MB</p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="name">Name</Label>
                                <Input id="name" v-model="form.name" placeholder="Product name" />
                                <InputError :message="form.errors.name" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="qty">Quantity</Label>
                                <Input id="qty" v-model="form.qty" type="number" placeholder="Quantity" />
                                <InputError :message="form.errors.qty" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="sell_jpy">Sell JPY</Label>
                                <Input id="sell_jpy" v-model="form.sell_jpy" type="number" step="0.01" placeholder="Sell price JPY" />
                                <InputError :message="form.errors.sell_jpy" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="sell_idr">Sell IDR</Label>
                                <Input id="sell_idr" v-model="form.sell_idr" type="number" step="0.01" placeholder="Sell price IDR" />
                                <InputError :message="form.errors.sell_idr" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="margin">Margin</Label>
                                <Input id="margin" v-model="form.margin" type="number" step="0.01" placeholder="Margin" />
                                <InputError :message="form.errors.margin" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="fee">Fee</Label>
                                <Input id="fee" v-model="form.fee" type="number" step="0.01" placeholder="Fee" />
                                <InputError :message="form.errors.fee" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="stock_jpy">Stock JPY</Label>
                                <Input id="stock_jpy" v-model="form.stock_jpy" type="number" step="0.01" placeholder="Stock JPY" />
                                <InputError :message="form.errors.stock_jpy" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="stock_idr">Stock IDR</Label>
                                <Input id="stock_idr" v-model="form.stock_idr" type="number" step="0.01" placeholder="Stock IDR" />
                                <InputError :message="form.errors.stock_idr" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="sell_to">Sell To</Label>
                                <Input id="sell_to" v-model="form.sell_to" placeholder="Sold to" />
                                <InputError :message="form.errors.sell_to" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="profit">Profit</Label>
                                <Input id="profit" v-model="form.profit" type="number" step="0.01" placeholder="Profit" />
                                <InputError :message="form.errors.profit" />
                            </div>
                            <DialogFooter>
                                <Button type="submit" :disabled="form.processing">Create</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- DataTable -->
            <div class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                <!-- Toolbar: Search + Per Page -->
                <div
                    class="flex flex-col gap-2 border-b border-border p-4 sm:flex-row sm:items-center sm:justify-between">
                    <Input :model-value="search" @update:model-value="onSearch" placeholder="Search name, code or breeder..."
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

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('code')">
                                    Code <span class="ml-1">{{ sortIcon('code') }}</span>
                                </th>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('name')">
                                    Name <span class="ml-1">{{ sortIcon('name') }}</span>
                                </th>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('breeder')">
                                    Breeder <span class="ml-1">{{ sortIcon('breeder') }}</span>
                                </th>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('qty')">
                                    Qty <span class="ml-1">{{ sortIcon('qty') }}</span>
                                </th>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('status')">
                                    Status <span class="ml-1">{{ sortIcon('status') }}</span>
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-background">
                            <tr v-if="paginatedReports.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                    {{ search ? 'Tidak ada hasil untuk pencarian tersebut.' : 'Belum ada data laporan penjualan.' }}
                                </td>
                            </tr>
                            <tr v-for="report in paginatedReports" :key="report.id"
                                class="transition-colors hover:bg-muted/50">
                                <td class="px-4 py-3 text-sm text-foreground">{{ report.code }}</td>
                                <td class="px-4 py-3 text-sm text-foreground">{{ report.name }}</td>
                                <td class="px-4 py-3 text-sm text-foreground">{{ report.breeder }}</td>
                                <td class="px-4 py-3 text-sm text-foreground">{{ report.qty }}</td>
                                <td class="px-4 py-3 text-sm text-foreground">{{ report.status }}</td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <Link :href="`/sales-reports/${report.id}`"
                                        class="mr-2 rounded-md border border-border px-3 py-1 text-xs font-medium text-foreground hover:bg-muted">
                                        Detail
                                    </Link>
                                    <Link :href="`/sales-reports/${report.id}/edit`"
                                        class="mr-2 rounded-md border border-border px-3 py-1 text-xs font-medium text-foreground hover:bg-muted">
                                        Edit
                                    </Link>
                                    <button type="button"
                                        class="rounded-md border border-red-500 bg-red-500/10 px-3 py-1 text-xs font-medium text-red-700 hover:bg-red-500/20"
                                        @click="confirmDelete(report.id, report.name)">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer: Info + Pagination -->
                <div
                    class="flex flex-col items-center justify-between gap-2 border-t border-border px-4 py-3 sm:flex-row">
                    <p class="text-sm text-muted-foreground">
                        Showing {{ showingFrom }} to {{ showingTo }} of {{ filteredReports.length }} entries
                        <span v-if="search">(filtered from {{ salesReports.length }} total)</span>
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