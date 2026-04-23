<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { ShoppingCart, Clock, CheckCircle, XCircle, Fish, Bell, PackageSearch } from 'lucide-vue-next'

type PesananItem = {
    id: number
    kode_pesanan: string
    qty: number
    total_harga: number
    status: string
    created_at: string
    koi_nama: string
}

type NotifItem = {
    id: number
    title: string
    message: string
    type: string
    created_at: string
}

type CustomerStats = {
    nama: string
    total_pesanan: number
    pesanan_diproses: number
    pesanan_azukari: number
    pesanan_dead: number
    total_belanja: number
    pesanan_terbaru: PesananItem[]
    notifikasi_terbaru: NotifItem[]
}

const props = defineProps<{
    customerStats: CustomerStats
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/customer/dashboard' },
]

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value ?? 0)

const formatDate = (dateString: string) =>
    new Date(dateString).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })

const getInitials = (name: string) =>
    name.split(' ').filter(Boolean).slice(0, 2).map(p => p[0].toUpperCase()).join('')

const timeAgo = (dateString: string) => {
    const isoDate = dateString.endsWith('Z') || dateString.includes('+') ? dateString : dateString + 'Z'
    const diff = Date.now() - new Date(isoDate).getTime()
    const seconds = Math.floor(diff / 1000)
    const minutes = Math.floor(seconds / 60)
    const hours = Math.floor(minutes / 60)
    const days = Math.floor(hours / 24)
    if (seconds < 60) return `${seconds} detik lalu`
    if (minutes < 60) return `${minutes} menit lalu`
    if (hours < 24) return `${hours} jam lalu`
    if (days === 1) return 'kemarin'
    return `${days} hari lalu`
}

const statusBadge = (status: string) => {
    const map: Record<string, string> = {
        pending:   'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
        shipped: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
        azukari: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
        dead:    'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
    }
    return map[status] ?? 'bg-gray-100 text-gray-800'
}

const statusLabel = (status: string) => {
    const map: Record<string, string> = {
        pending: 'Pending', shipped: 'Shipped', azukari: 'Azukari', dead: 'Dead',
    }
    return map[status] ?? status
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">

            <!-- Welcome Banner -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-muted flex items-center justify-center flex-shrink-0 text-foreground text-xl font-bold select-none">
                        {{ getInitials(customerStats.nama) }}
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-foreground">
                            Selamat datang, {{ customerStats.nama }}!
                        </h1>
                        <p class="text-sm text-muted-foreground mt-0.5">
                            Berikut ringkasan aktivitas pesanan Anda.
                        </p>
                    </div>
                </div>
            </div>

            <!-- 4 Stat Cards -->
            <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">

                <!-- Total Pesanan -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-muted flex items-center justify-center flex-shrink-0">
                        <ShoppingCart class="w-6 h-6 text-muted-foreground" />
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Total Pesanan</p>
                        <p class="text-3xl font-bold text-foreground">{{ customerStats.total_pesanan }}</p>
                    </div>
                </div>

                <!-- Sedang Diproses -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-muted flex items-center justify-center flex-shrink-0">
                        <Clock class="w-6 h-6 text-muted-foreground" />
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Sedang Diproses</p>
                        <p class="text-3xl font-bold text-foreground">{{ customerStats.pesanan_diproses }}</p>
                        <p class="text-xs text-muted-foreground">Pending &amp; Confirmed</p>
                    </div>
                </div>

                <!-- Selesai -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-muted flex items-center justify-center flex-shrink-0">
                        <CheckCircle class="w-6 h-6 text-muted-foreground" />
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Azukari</p>
                        <p class="text-3xl font-bold text-foreground">{{ customerStats.pesanan_azukari }}</p>
                    </div>
                </div>

                <!-- Dibatalkan -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-muted flex items-center justify-center flex-shrink-0">
                        <XCircle class="w-6 h-6 text-muted-foreground" />
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Dead</p>
                        <p class="text-3xl font-bold text-foreground">{{ customerStats.pesanan_dead }}</p>
                    </div>
                </div>
            </div>

            <!-- Recent Orders Table -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h2 class="text-lg font-semibold text-foreground">Pesanan Terbaru</h2>
                        <p class="text-sm text-muted-foreground">5 pesanan terakhir Anda</p>
                    </div>
                    <Link
                        href="/customer/pesanan"
                        class="text-sm font-medium text-primary hover:text-primary/80 transition-colors"
                    >
                        Lihat Semua →
                    </Link>
                </div>

                <div v-if="customerStats.pesanan_terbaru.length > 0" class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="pb-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide">Tanggal</th>
                                <th class="pb-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide">Nama Koi</th>
                                <th class="pb-3 text-center text-xs font-semibold text-muted-foreground uppercase tracking-wide">Jumlah</th>
                                <th class="pb-3 text-right text-xs font-semibold text-muted-foreground uppercase tracking-wide">Total Harga</th>
                                <th class="pb-3 text-center text-xs font-semibold text-muted-foreground uppercase tracking-wide">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="p in customerStats.pesanan_terbaru"
                                :key="p.id"
                                class="hover:bg-muted/20 transition-colors"
                            >
                                <td class="py-3.5 text-sm text-muted-foreground whitespace-nowrap">
                                    {{ formatDate(p.created_at) }}
                                </td>
                                <td class="py-3.5">
                                    <div class="flex items-center gap-2">
                                        <Fish class="w-4 h-4 text-muted-foreground flex-shrink-0" />
                                        <span class="text-sm font-medium text-foreground">{{ p.koi_nama }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 text-sm text-foreground text-center">
                                    {{ p.qty }} ekor
                                </td>
                                <td class="py-3.5 text-sm font-semibold text-foreground text-right">
                                    {{ formatCurrency(p.total_harga) }}
                                </td>
                                <td class="py-3.5 text-center">
                                    <span :class="`inline-block px-2.5 py-1 rounded-full text-xs font-semibold ${statusBadge(p.status)}`">
                                        {{ statusLabel(p.status) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty state -->
                <div v-else class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="w-14 h-14 rounded-full bg-muted flex items-center justify-center mb-3">
                        <PackageSearch class="w-7 h-7 text-muted-foreground/50" />
                    </div>
                    <p class="text-sm font-semibold text-foreground">Belum ada pesanan</p>
                    <p class="text-xs text-muted-foreground mt-1 mb-4">Mulai belanja koi pilihan Anda sekarang</p>
                    <Link
                        href="/customer/katalog"
                        class="px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-semibold hover:bg-primary/90 transition"
                    >
                        Jelajahi Katalog
                    </Link>
                </div>
            </div>

            <!-- Notifications -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-foreground">Notifikasi Terbaru</h2>
                        <p class="text-sm text-muted-foreground">Notifikasi yang belum dibaca</p>
                    </div>
                    <Link
                        href="/customer/notifikasi"
                        class="text-sm font-medium text-primary hover:text-primary/80 transition-colors"
                    >
                        Lihat Semua →
                    </Link>
                </div>

                <div v-if="customerStats.notifikasi_terbaru.length > 0" class="space-y-3">
                    <div
                        v-for="n in customerStats.notifikasi_terbaru"
                        :key="n.id"
                        class="flex items-start gap-3 p-4 rounded-lg border border-sidebar-border/70 dark:border-sidebar-border bg-muted/30"
                    >
                        <div class="w-8 h-8 rounded-full bg-muted flex items-center justify-center flex-shrink-0 mt-0.5">
                            <Bell class="w-4 h-4 text-muted-foreground" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-foreground">{{ n.title }}</p>
                            <p class="text-xs text-muted-foreground line-clamp-2 mt-0.5">{{ n.message }}</p>
                        </div>
                        <p class="text-xs text-muted-foreground whitespace-nowrap flex-shrink-0">
                            {{ timeAgo(n.created_at) }}
                        </p>
                    </div>
                </div>

                <div v-else class="flex items-center justify-center gap-2 py-6 text-sm text-muted-foreground">
                    <Bell class="w-4 h-4" />
                    Tidak ada notifikasi baru.
                </div>
            </div>

        </div>
    </AppLayout>
</template>
