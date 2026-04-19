<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes'
import type { BreadcrumbItem } from '@/types'
import {
    ShoppingCart, Clock, Users, Fish, DollarSign, CheckCircle, Bell, XCircle, PackageSearch,
    CalendarDays, TrendingUp, TrendingDown, Store
} from 'lucide-vue-next'

type ActivityLogItem = {
    id: number
    user: { name: string } | null
    action: string
    description: string
    created_at: string
}

type PesananItem = {
    id: number
    kode_pesanan: string
    qty: number
    total_harga: number
    status: string
    created_at: string
    koi_nama: string
    customer?: { name: string; email: string } | null
}

type NotifItem = {
    id: number
    title: string
    message: string
    type: string
    created_at: string
}

const page = usePage()

const summary = computed(() => (page.props.summary as any) ?? {})
const latestActivityLogs = computed<ActivityLogItem[]>(() => (page.props.latestActivityLogs as any) ?? [])
const adminStats = computed(() => {
    const s = (page.props.adminStats as any)
    return s && !Array.isArray(s) ? s : null
})
const customerStats = computed(() => {
    const s = (page.props.customerStats as any)
    return s && !Array.isArray(s) ? s : null
})

const authUser = computed(() => (page.props.auth as any)?.user)
const isAdmin = computed(() => authUser.value?.role_id === 1)
const isCustomer = computed(() => authUser.value?.role_id === 2)

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }]

const currentDate = new Date()

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value ?? 0)

const getInitials = (name?: string) => {
    if (!name) return 'U'
    return name.split(' ').filter(Boolean).slice(0, 2).map(p => p[0].toUpperCase()).join('')
}

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

const revenueChange = computed(() => {
    if (!adminStats.value) return 0
    const now = Number(adminStats.value.revenue_bulan_ini ?? 0)
    const prev = Number(adminStats.value.revenue_bulan_lalu ?? 0)
    if (prev === 0) return now > 0 ? 100 : 0
    return Math.round(((now - prev) / prev) * 100)
})

const actionBadgeClasses = (action: string) => {
    switch (action) {
        case 'create': return 'bg-emerald-100 text-emerald-700'
        case 'update': return 'bg-sky-100 text-sky-700'
        case 'delete': return 'bg-rose-100 text-rose-700'
        default: return 'bg-slate-100 text-slate-700'
    }
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
        pending: 'Pending', confirmed: 'Confirmed', completed: 'Completed', cancelled: 'Cancelled',
    }
    return labels[status] || status
}
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">

            <!-- ===== ADMIN DASHBOARD ===== -->
            <template v-if="isAdmin && adminStats">
                <!-- Row 1: 5 stat cards -->
                <div class="grid gap-4 grid-cols-2 md:grid-cols-3 xl:grid-cols-5">
                    <!-- Total Revenue -->
                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-5 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-muted flex items-center justify-center flex-shrink-0">
                            <DollarSign class="w-5 h-5 text-muted-foreground" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground truncate">Total Revenue</p>
                            <p class="text-base font-bold text-foreground leading-tight">{{ formatCurrency(adminStats.total_revenue) }}</p>
                            <p class="text-xs text-muted-foreground/70">Confirmed + Selesai</p>
                        </div>
                    </div>
                    <!-- Pesanan Hari Ini -->
                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-5 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-muted flex items-center justify-center flex-shrink-0">
                            <CalendarDays class="w-5 h-5 text-muted-foreground" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground truncate">Pesanan Hari Ini</p>
                            <p class="text-2xl font-bold text-foreground leading-tight">{{ adminStats.pesanan_hari_ini }}</p>
                        </div>
                    </div>
                    <!-- Total Customer -->
                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-5 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-muted flex items-center justify-center flex-shrink-0">
                            <Users class="w-5 h-5 text-muted-foreground" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground truncate">Total Customer</p>
                            <p class="text-2xl font-bold text-foreground leading-tight">{{ adminStats.total_customer }}</p>
                        </div>
                    </div>
                    <!-- Koi Tersedia -->
                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-5 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-muted flex items-center justify-center flex-shrink-0">
                            <Store class="w-5 h-5 text-muted-foreground" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground truncate">Koi Tersedia</p>
                            <p class="text-2xl font-bold text-foreground leading-tight">{{ adminStats.total_koi_tersedia }}</p>
                            <p class="text-xs text-muted-foreground/70">Katalog aktif</p>
                        </div>
                    </div>
                    <!-- Pesanan Pending -->
                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-5 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-muted flex items-center justify-center flex-shrink-0">
                            <Clock class="w-5 h-5 text-muted-foreground" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground truncate">Pesanan Pending</p>
                            <p class="text-2xl font-bold text-foreground leading-tight">{{ adminStats.pesanan_pending }}</p>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Pesanan Terbaru -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-lg font-semibold text-foreground">Pesanan Terbaru</h2>
                            <p class="text-sm text-muted-foreground">10 pesanan terbaru dari customer</p>
                        </div>
                        <Link href="/admin/pesanan" class="text-sm font-medium text-primary hover:text-primary/80">
                            Lihat Semua →
                        </Link>
                    </div>
                    <div v-if="adminStats.pesanan_terbaru?.length" class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-border">
                                    <th class="pb-3 text-left text-xs font-semibold text-muted-foreground uppercase">Order #</th>
                                    <th class="pb-3 text-left text-xs font-semibold text-muted-foreground uppercase">Customer</th>
                                    <th class="pb-3 text-left text-xs font-semibold text-muted-foreground uppercase">Koi</th>
                                    <th class="pb-3 text-left text-xs font-semibold text-muted-foreground uppercase">Total</th>
                                    <th class="pb-3 text-left text-xs font-semibold text-muted-foreground uppercase">Status</th>
                                    <th class="pb-3 text-left text-xs font-semibold text-muted-foreground uppercase">Tanggal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-for="p in adminStats.pesanan_terbaru" :key="p.id" class="hover:bg-muted/20">
                                    <td class="py-3 text-sm font-mono font-semibold text-primary">{{ p.kode_pesanan }}</td>
                                    <td class="py-3 text-sm text-foreground">{{ p.customer?.name ?? '-' }}</td>
                                    <td class="py-3 text-sm text-foreground">{{ p.koi_nama }}</td>
                                    <td class="py-3 text-sm font-semibold text-foreground">{{ formatCurrency(p.total_harga) }}</td>
                                    <td class="py-3">
                                        <span :class="`px-2 py-1 rounded-full text-xs font-semibold ${statusBadge(p.status)}`">
                                            {{ statusLabel(p.status) }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-xs text-muted-foreground">{{ timeAgo(p.created_at) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-sm text-muted-foreground text-center py-8">Belum ada pesanan.</div>
                </div>

                <!-- Row 3: Koi Terlaris + Revenue Bulanan -->
                <div class="grid gap-4 grid-cols-1 lg:grid-cols-2">
                    <!-- Koi Terlaris -->
                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6">
                        <div class="mb-4">
                            <h2 class="text-lg font-semibold text-foreground">Koi Terlaris</h2>
                            <p class="text-sm text-muted-foreground">5 koi paling banyak dipesan (Confirmed + Selesai)</p>
                        </div>
                        <div v-if="adminStats.koi_terlaris?.length" class="space-y-3">
                            <div
                                v-for="(koi, idx) in adminStats.koi_terlaris"
                                :key="koi.id"
                                class="flex items-center gap-3"
                            >
                                <span class="w-6 text-xs font-semibold text-muted-foreground text-right flex-shrink-0">{{ idx + 1 }}</span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2 mb-1">
                                        <span class="text-sm font-medium text-foreground truncate">{{ koi.name }}</span>
                                        <span class="text-xs font-semibold text-muted-foreground flex-shrink-0">{{ koi.total_dipesan }} ekor</span>
                                    </div>
                                    <div class="h-1.5 w-full rounded-full bg-muted overflow-hidden">
                                        <div
                                            class="h-full rounded-full bg-foreground/30"
                                            :style="{ width: `${Math.round((koi.total_dipesan / adminStats.koi_terlaris[0].total_dipesan) * 100)}%` }"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-sm text-muted-foreground text-center py-8">Belum ada data pesanan.</div>
                    </div>

                    <!-- Revenue Bulanan -->
                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6">
                        <div class="mb-4">
                            <h2 class="text-lg font-semibold text-foreground">Revenue Bulanan</h2>
                            <p class="text-sm text-muted-foreground">Perbandingan bulan ini vs bulan lalu</p>
                        </div>
                        <div class="space-y-4">
                            <div class="rounded-lg border border-border p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Bulan Ini</p>
                                <p class="text-2xl font-bold text-foreground mt-1">{{ formatCurrency(adminStats.revenue_bulan_ini) }}</p>
                            </div>
                            <div class="rounded-lg border border-border p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Bulan Lalu</p>
                                <p class="text-2xl font-bold text-foreground mt-1">{{ formatCurrency(adminStats.revenue_bulan_lalu) }}</p>
                            </div>
                            <div class="rounded-lg border border-border p-4 flex items-center justify-between">
                                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Perubahan</p>
                                <div class="flex items-center gap-1.5">
                                    <TrendingUp v-if="revenueChange >= 0" class="w-4 h-4 text-muted-foreground" />
                                    <TrendingDown v-else class="w-4 h-4 text-muted-foreground" />
                                    <span class="text-sm font-bold text-foreground">
                                        {{ revenueChange >= 0 ? '+' : '' }}{{ revenueChange }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Legacy activity logs -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
                        <div>
                            <h2 class="text-lg font-semibold text-foreground">Aktivitas Terbaru</h2>
                            <p class="text-sm text-muted-foreground">10 log aktivitas terbaru</p>
                        </div>
                        <Link href="/log-aktivitas" class="text-sm font-medium text-primary hover:text-primary/80">Lihat semua aktivitas →</Link>
                    </div>
                    <div class="space-y-4">
                        <template v-if="latestActivityLogs.length > 0">
                            <div v-for="log in latestActivityLogs" :key="log.id" class="flex items-start gap-4 rounded-2xl border border-border bg-surface p-4">
                                <div class="flex h-11 w-11 flex-none items-center justify-center rounded-full bg-primary/10 text-primary font-semibold">
                                    {{ getInitials(log.user?.name) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="truncate text-sm font-semibold text-foreground">{{ log.user?.name || 'Pengguna tidak dikenal' }}</p>
                                        <span :class="actionBadgeClasses(log.action) + ' rounded-full px-2 py-0.5 text-[11px] font-semibold uppercase tracking-[0.08em]'">{{ log.action }}</span>
                                    </div>
                                    <p class="mt-1 text-sm text-muted-foreground line-clamp-2">{{ log.description }}</p>
                                </div>
                                <p class="whitespace-nowrap text-xs text-muted-foreground">{{ timeAgo(log.created_at) }}</p>
                            </div>
                        </template>
                        <div v-else class="rounded-2xl border border-dashed border-border/80 bg-background p-8 text-center text-sm text-muted-foreground">
                            Belum ada aktivitas terbaru.
                        </div>
                    </div>
                </div>
            </template>

            <!-- ===== CUSTOMER DASHBOARD ===== -->
            <template v-if="isCustomer && customerStats">

                <!-- Welcome Banner -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-full bg-muted flex items-center justify-center flex-shrink-0 text-foreground text-xl font-bold select-none">
                            {{ getInitials(customerStats.nama) }}
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-foreground">Selamat datang, {{ customerStats.nama }}!</h1>
                            <p class="text-sm text-muted-foreground mt-0.5">Berikut ringkasan aktivitas pesanan Anda.</p>
                        </div>
                    </div>
                </div>

                <!-- Row 1: 4 stat cards -->
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
                            <p class="text-xs text-muted-foreground">Pending & Confirmed</p>
                        </div>
                    </div>
                    <!-- Selesai -->
                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-muted flex items-center justify-center flex-shrink-0">
                            <CheckCircle class="w-6 h-6 text-muted-foreground" />
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Selesai</p>
                            <p class="text-3xl font-bold text-foreground">{{ customerStats.pesanan_completed }}</p>
                        </div>
                    </div>
                    <!-- Dibatalkan -->
                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-muted flex items-center justify-center flex-shrink-0">
                            <XCircle class="w-6 h-6 text-muted-foreground" />
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Dibatalkan</p>
                            <p class="text-3xl font-bold text-foreground">{{ customerStats.pesanan_cancelled }}</p>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Recent Orders Table -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h2 class="text-lg font-semibold text-foreground">Pesanan Terbaru</h2>
                            <p class="text-sm text-muted-foreground">5 pesanan terakhir Anda</p>
                        </div>
                        <Link href="/customer/pesanan" class="text-sm font-medium text-primary hover:text-primary/80">
                            Lihat Semua →
                        </Link>
                    </div>

                    <div v-if="customerStats.pesanan_terbaru?.length" class="overflow-x-auto">
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
                                <tr v-for="p in customerStats.pesanan_terbaru" :key="p.id" class="hover:bg-muted/20 transition-colors">
                                    <td class="py-3.5 text-sm text-muted-foreground whitespace-nowrap">
                                        {{ new Date(p.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) }}
                                    </td>
                                    <td class="py-3.5">
                                        <div class="flex items-center gap-2">
                                            <Fish class="w-4 h-4 text-muted-foreground flex-shrink-0" />
                                            <span class="text-sm font-medium text-foreground">{{ p.koi_nama }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 text-sm text-foreground text-center">{{ p.qty }} ekor</td>
                                    <td class="py-3.5 text-sm font-semibold text-foreground text-right">{{ formatCurrency(p.total_harga) }}</td>
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
                        <Link href="/customer/katalog" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-semibold hover:bg-primary/90 transition">
                            Jelajahi Katalog
                        </Link>
                    </div>
                </div>

                <!-- Row 3: Notifications -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-lg font-semibold text-foreground">Notifikasi Terbaru</h2>
                            <p class="text-sm text-muted-foreground">Notifikasi yang belum dibaca</p>
                        </div>
                        <Link href="/customer/notifikasi" class="text-sm font-medium text-primary hover:text-primary/80">Lihat Semua →</Link>
                    </div>
                    <div v-if="customerStats.notifikasi_terbaru?.length" class="space-y-3">
                        <div
                            v-for="n in customerStats.notifikasi_terbaru" :key="n.id"
                            class="flex items-start gap-3 p-4 rounded-lg border border-sidebar-border/70 dark:border-sidebar-border bg-muted/30"
                        >
                            <div class="w-8 h-8 rounded-full bg-muted flex items-center justify-center flex-shrink-0 mt-0.5">
                                <Bell class="w-4 h-4 text-muted-foreground" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-foreground">{{ n.title }}</p>
                                <p class="text-xs text-muted-foreground line-clamp-2 mt-0.5">{{ n.message }}</p>
                            </div>
                            <p class="text-xs text-muted-foreground whitespace-nowrap flex-shrink-0">{{ timeAgo(n.created_at) }}</p>
                        </div>
                    </div>
                    <div v-else class="flex items-center gap-3 py-6 justify-center text-sm text-muted-foreground">
                        <Bell class="w-4 h-4" />
                        Tidak ada notifikasi baru.
                    </div>
                </div>

            </template>

        </div>
    </AppLayout>
</template>
