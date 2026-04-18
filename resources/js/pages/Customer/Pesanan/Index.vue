<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

type Breeder = {
    name_breeder: string
}

type Koi = {
    id: number
    nama: string
    kode: string
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
    katalogKoi: KatalogKoi
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
        title: 'Pesanan Saya',
        href: '/customer/pesanan',
    },
]

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
        month: 'long',
        day: 'numeric',
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
        pending: 'Menunggu Konfirmasi',
        confirmed: 'Terkonfirmasi',
        completed: 'Selesai',
        cancelled: 'Dibatalkan',
    }
    return labels[status] || status
}

const hasData = computed(() => (props.pesanans?.data?.length ?? 0) > 0)
</script>

<template>
    <Head title="Pesanan Saya" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="space-y-2 mb-2">
                <h1 class="text-4xl font-bold text-foreground">Pesanan Saya</h1>
                <p class="text-base text-muted-foreground">
                    Lihat status dan detail pesanan koi Anda
                </p>
            </div>

            <!-- Table -->
            <div v-if="hasData" class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-muted/30 border-b border-border">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Kode Pesanan</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Nama Koi</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Qty</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Total Harga</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Tanggal</th>
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
                                    <span class="text-foreground font-medium">
                                        {{ pesanan.katalogKoi.koi.nama }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-foreground">{{ pesanan.qty }} ekor</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-primary">
                                        {{ formatCurrency(pesanan.total_harga) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="`px-3 py-1 rounded-full text-xs font-semibold ${statusBadge(pesanan.status)}`"
                                    >
                                        {{ statusLabel(pesanan.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-muted-foreground">
                                        {{ formatDate(pesanan.created_at) }}
                                    </span>
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
                <div class="w-16 h-16 rounded-full bg-muted flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-muted-foreground/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <p class="text-lg font-semibold text-foreground mb-2">
                    Belum Ada Pesanan
                </p>
                <p class="text-sm text-muted-foreground mb-6 max-w-sm">
                    Anda belum membuat pesanan apapun. Mulai dengan menjelajahi katalog koi kami.
                </p>
                <Link
                    href="/customer/katalog"
                    class="px-6 py-2 bg-primary text-primary-foreground rounded-lg font-semibold hover:bg-primary/90 transition"
                >
                    Jelajahi Katalog
                </Link>
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
