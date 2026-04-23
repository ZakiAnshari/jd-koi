<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { Eye } from 'lucide-vue-next'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import type { BreadcrumbItem } from '@/types'
const adminPesananShow = { url: (id: number) => `/admin/pesanan/${id}` }
const adminPesananUpdateStatus = { url: (id: number) => `/admin/pesanan/${id}/status` }

type Customer = {
    id: number
    name: string
    email: string
}

type Koi = {
    id: number
    name: string
    code: string
}

type KatalogKoi = {
    id: number
    koi: Koi
}

type Pesanan = {
    id: number
    kode_pesanan: string
    qty: number
    harga_satuan: number
    total_harga: number
    status: 'pending' | 'shipped' | 'azukari' | 'dead'
    catatan?: string
    created_at: string
    customer: Customer
    katalog_koi: KatalogKoi
}

const props = defineProps<{
    pesanans: Pesanan[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pesanan Customer', href: '/admin/pesanan' },
]

// ─── DataTable State ─────────────────────────────────────
const search = ref('')
const sortKey = ref<keyof Pesanan>('created_at')
const sortOrder = ref<'asc' | 'desc'>('desc')
const currentPage = ref(1)
const perPage = ref(10)

const selectedStatus = ref<Record<number, string>>(
    Object.fromEntries((props.pesanans ?? []).map(p => [p.id, p.status]))
)

const filteredPesanans = computed(() => {
    let result = props.pesanans ?? []

    if (search.value) {
        const q = search.value.toLowerCase()
        result = result.filter(p =>
            p.kode_pesanan.toLowerCase().includes(q) ||
            p.customer.name.toLowerCase().includes(q) ||
            p.customer.email.toLowerCase().includes(q) ||
            p.katalog_koi.koi.name.toLowerCase().includes(q) ||
            p.status.toLowerCase().includes(q)
        )
    }

    result = [...result].sort((a, b) => {
        const aVal = (a as any)[sortKey.value]
        const bVal = (b as any)[sortKey.value]
        if (aVal == null || bVal == null) return 0
        if (typeof aVal === 'string' && typeof bVal === 'string') {
            return sortOrder.value === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal)
        }
        if (typeof aVal === 'number' && typeof bVal === 'number') {
            return sortOrder.value === 'asc' ? aVal - bVal : bVal - aVal
        }
        return 0
    })

    return result
})

const totalPages = computed(() => Math.ceil(filteredPesanans.value.length / perPage.value) || 1)

watch(totalPages, (newTotal) => {
    if (currentPage.value > newTotal) currentPage.value = 1
})

const paginatedPesanans = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return filteredPesanans.value.slice(start, start + perPage.value)
})

const showingFrom = computed(() =>
    filteredPesanans.value.length === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1
)
const showingTo = computed(() =>
    Math.min(currentPage.value * perPage.value, filteredPesanans.value.length)
)

function onSearch(value: string | number) {
    search.value = String(value)
    currentPage.value = 1
}

function toggleSort(key: keyof Pesanan) {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortKey.value = key
        sortOrder.value = 'asc'
    }
}

function sortIcon(key: keyof Pesanan) {
    if (sortKey.value !== key) return '↕'
    return sortOrder.value === 'asc' ? '↑' : '↓'
}

// ─── Helpers ─────────────────────────────────────────────
const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value)

const statusBadge = (status: string) => {
    const badges: Record<string, string> = {
        pending:   'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        shipped: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        azukari: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        dead:    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    }
    return badges[status] || 'bg-gray-100 text-gray-800'
}

const statusLabel = (status: string) => {
    const labels: Record<string, string> = {
        pending: 'Pending', shipped: 'Shipped', azukari: 'Azukari', dead: 'Dead',
    }
    return labels[status] || status
}

const updateStatus = (pesanan: Pesanan) => {
    const newStatus = selectedStatus.value[pesanan.id]
    if (!newStatus || newStatus === pesanan.status) return
    router.put(adminPesananUpdateStatus.url(pesanan.id), { status: newStatus })
}
</script>

<template>
    <Head title="Pesanan Customer" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Pesanan Customer</h1>
                    <p class="text-sm text-muted-foreground">Kelola pesanan yang masuk dari customer.</p>
                </div>
            </div>

            <!-- DataTable -->
            <div class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                <!-- Search & Per-page -->
                <div class="flex flex-col gap-2 border-b border-border p-4 sm:flex-row sm:items-center sm:justify-between">
                    <Input
                        :model-value="search"
                        @update:model-value="onSearch"
                        placeholder="Cari kode, customer, koi, atau status..."
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

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th
                                    class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('kode_pesanan')"
                                >
                                    Kode Pesanan <span class="ml-1">{{ sortIcon('kode_pesanan') }}</span>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Customer
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Nama Koi
                                </th>
                                <th
                                    class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('qty')"
                                >
                                    Qty <span class="ml-1">{{ sortIcon('qty') }}</span>
                                </th>
                                <th
                                    class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('total_harga')"
                                >
                                    Total Harga <span class="ml-1">{{ sortIcon('total_harga') }}</span>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Status
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-background">
                            <tr v-if="paginatedPesanans.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                    {{ search ? 'Tidak ada hasil untuk pencarian tersebut.' : 'Belum ada pesanan.' }}
                                </td>
                            </tr>
                            <tr
                                v-for="pesanan in paginatedPesanans"
                                :key="pesanan.id"
                                class="transition-colors hover:bg-muted/50"
                            >
                                <td class="px-4 py-3 text-sm font-mono font-semibold text-foreground">
                                    {{ pesanan.kode_pesanan }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm">
                                        <p class="font-medium text-foreground">{{ pesanan.customer.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ pesanan.customer.email }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-foreground">
                                    {{ pesanan.katalog_koi.koi.name }}
                                </td>
                                <td class="px-4 py-3 text-sm text-foreground">
                                    {{ pesanan.qty }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-foreground">
                                    {{ formatCurrency(pesanan.total_harga) }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span :class="`inline-block px-2 py-1 rounded-full text-xs font-semibold ${statusBadge(pesanan.status)}`">
                                            {{ statusLabel(pesanan.status) }}
                                        </span>
                                        <select
                                            v-model="selectedStatus[pesanan.id]"
                                            @change="updateStatus(pesanan)"
                                            class="rounded-md border border-input bg-background px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-primary"
                                        >
                                            <option value="pending">Pending</option>
                                            <option value="shipped">Shipped</option>
                                            <option value="azukari">Azukari</option>
                                            <option value="dead">Dead</option>
                                        </select>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link
                                        :href="adminPesananShow.url(pesanan.id)"
                                        class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background p-2 text-foreground shadow-sm hover:bg-muted"
                                        title="Detail"
                                    >
                                        <Eye :size="16" />
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer: info + pagination -->
                <div class="flex flex-col items-center justify-between gap-2 border-t border-border px-4 py-3 sm:flex-row">
                    <p class="text-sm text-muted-foreground">
                        Showing {{ showingFrom }} to {{ showingTo }} of
                        {{ filteredPesanans.length }} entries
                        <span v-if="search">(filtered from {{ pesanans.length }} total)</span>
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
                        <Button variant="outline" size="sm" :disabled="currentPage >= totalPages" @click="currentPage++">
                            Next
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
