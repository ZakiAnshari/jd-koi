<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { show as adminPesananShow, updateStatus as adminPesananUpdateStatus } from '@/routes/admin/pesanan'

type Customer = {
    id: number
    name: string
    email: string
}

type Breeder = {
    id: number
    name_breeder: string
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
    status: 'pending' | 'confirmed' | 'cancelled' | 'completed'
    catatan?: string
    created_at: string
    customer: Customer
    katalog_koi: KatalogKoi
}

type PaginatedPesanans = {
    data: Pesanan[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from?: number
    to?: number
    prev_page_url?: string
    next_page_url?: string
    path: string
}

const props = defineProps<{
    pesanans: PaginatedPesanans
}>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pesanan Customer',
        href: '/admin/pesanan',
    },
]

const selectedStatus = ref<Record<number, string>>(
    Object.fromEntries((props.pesanans?.data ?? []).map(p => [p.id, p.status]))
)

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value)
}

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    })
}

const statusBadge = (status: string) => {
    const badges: Record<string, string> = {
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        confirmed: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        completed: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    }
    return badges[status] || 'bg-gray-100 text-gray-800'
}

const statusLabel = (status: string) => {
    const labels: Record<string, string> = {
        pending: 'Pending',
        confirmed: 'Confirmed',
        completed: 'Completed',
        cancelled: 'Cancelled',
    }
    return labels[status] || status
}

const updateStatus = (pesanan: Pesanan) => {
    const newStatus = selectedStatus.value[pesanan.id]
    if (!newStatus || newStatus === pesanan.status) return

    router.put(adminPesananUpdateStatus.url(pesanan.id), {
        status: newStatus,
    })
}

const hasData = (props.pesanans?.data?.length ?? 0) > 0
</script>

<template>
    <Head title="Pesanan Customer" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="space-y-2 mb-2">
                <h1 class="text-4xl font-bold text-foreground">Pesanan Customer</h1>
                <p class="text-base text-muted-foreground">
                    Kelola pesanan yang masuk dari customer
                </p>
            </div>

            <!-- Table -->
            <div v-if="hasData" class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-muted/30 border-b border-border">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Kode Pesanan</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Nama Koi</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Qty</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Total Harga</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="pesanan in pesanans.data"
                                :key="pesanan.id"
                                class="hover:bg-muted/20 transition-colors"
                            >
                                <td class="px-6 py-4">
                                    <span class="font-mono font-semibold text-foreground text-sm">
                                        {{ pesanan.kode_pesanan }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm">
                                        <p class="font-medium text-foreground">{{ pesanan.customer.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ pesanan.customer.email }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-foreground font-medium">
                                        {{ pesanan.katalog_koi.koi.name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-foreground">{{ pesanan.qty }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-primary">
                                        {{ formatCurrency(pesanan.total_harga) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-muted-foreground">
                                        {{ formatDate(pesanan.created_at) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <select
                                            v-model="selectedStatus[pesanan.id]"
                                            @change="updateStatus(pesanan)"
                                            class="px-2 py-1 text-xs rounded-lg border border-sidebar-border/70 dark:border-sidebar-border bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                        >
                                            <option value="pending">Pending</option>
                                            <option value="confirmed">Confirmed</option>
                                            <option value="completed">Completed</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <Link
                                        :href="adminPesananShow.url(pesanan.id)"
                                        class="text-sm px-3 py-1 rounded-lg border border-sidebar-border/70 dark:border-sidebar-border text-foreground hover:bg-muted transition-colors"
                                    >
                                        Detail
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="flex flex-col items-center justify-center rounded-xl border border-dashed border-border bg-muted/30 p-16 text-center"
            >
                <p class="text-lg font-semibold text-foreground mb-2">
                    Belum Ada Pesanan
                </p>
                <p class="text-sm text-muted-foreground">
                    Pesanan dari customer akan muncul di sini
                </p>
            </div>

            <!-- Pagination -->
            <div v-if="hasData && pesanans.last_page > 1" class="flex items-center justify-center gap-2 mt-8 pt-4 border-t border-border">
                <Link
                    v-if="pesanans.prev_page_url"
                    :href="pesanans.prev_page_url"
                    class="px-3 py-2 text-sm font-medium rounded-lg border border-sidebar-border/70 dark:border-sidebar-border text-foreground hover:bg-muted transition-colors"
                >
                    ← Sebelumnya
                </Link>
                <button
                    v-else
                    disabled
                    class="px-3 py-2 text-sm font-medium rounded-lg border border-sidebar-border/70 dark:border-sidebar-border text-muted-foreground opacity-50 cursor-not-allowed"
                >
                    ← Sebelumnya
                </button>

                <div class="flex gap-1 items-center">
                    <span class="px-2 py-2 text-sm text-muted-foreground">
                        Halaman {{ pesanans.current_page }} dari {{ pesanans.last_page }}
                    </span>
                </div>

                <Link
                    v-if="pesanans.next_page_url"
                    :href="pesanans.next_page_url"
                    class="px-3 py-2 text-sm font-medium rounded-lg border border-sidebar-border/70 dark:border-sidebar-border text-foreground hover:bg-muted transition-colors"
                >
                    Selanjutnya →
                </Link>
                <button
                    v-else
                    disabled
                    class="px-3 py-2 text-sm font-medium rounded-lg border border-sidebar-border/70 dark:border-sidebar-border text-muted-foreground opacity-50 cursor-not-allowed"
                >
                    Selanjutnya →
                </button>
            </div>
        </div>
    </AppLayout>
</template>
