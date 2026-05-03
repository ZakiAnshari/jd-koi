<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes'
import type { BreadcrumbItem } from '@/types'
import { Users, Fish, TrendingUp, Percent, Store, ShoppingBag, Wallet, BarChart3, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { Bar } from 'vue-chartjs'
import {
    Chart as ChartJS,
    CategoryScale, LinearScale, BarElement,
    Tooltip, Legend,
} from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip, Legend)

type ActivityLogItem = {
    id: number
    user: { name: string } | null
    action: string
    description: string
    created_at: string
}

type RecentTrx = {
    id: number
    code: string
    name: string
    sale_invoice: string | null
    sale_tanggal: string | null
    sale_status: string | null
    sell_idr: string | null
    breeder: string | null
    customer: string | null
}

type TrendItem = { label: string; sales: number; profit: number; count: number; fee: number }

const page = usePage()
const stats              = computed(() => (page.props.stats as any) ?? {})
const recentTransactions = computed<RecentTrx[]>(() => (page.props.recentTransactions as any) ?? [])
const latestActivityLogs = computed<ActivityLogItem[]>(() => (page.props.latestActivityLogs as any) ?? [])
const trendData          = computed<TrendItem[]>(() => (page.props.trendData as any) ?? [])

// Chart metric toggle
const chartMetric = ref<'sales' | 'profit' | 'fee'>('sales')

const chartLabels     = computed(() => trendData.value.map(d => d.label))
const chartDataValues = computed(() => trendData.value.map(d => d[chartMetric.value]))

const chartColors: Record<string, { bg: string; border: string }> = {
    sales:  { bg: 'rgba(245,158,11,0.25)',  border: 'rgba(245,158,11,1)'  },
    profit: { bg: 'rgba(34,197,94,0.25)',   border: 'rgba(34,197,94,1)'   },
    fee:    { bg: 'rgba(168,85,247,0.25)',  border: 'rgba(168,85,247,1)'  },
}

const chartMetricLabel: Record<string, string> = {
    sales:  'Total Penjualan (IDR)',
    profit: 'Total Profit (IDR)',
    fee:    'Total Fee (IDR)',
}

const chartData = computed(() => ({
    labels: chartLabels.value,
    datasets: [{
        label: chartMetricLabel[chartMetric.value],
        data:  chartDataValues.value,
        backgroundColor: chartColors[chartMetric.value].bg,
        borderColor:     chartColors[chartMetric.value].border,
        borderWidth: 2,
        borderRadius: 6,
        borderSkipped: false,
    }],
}))

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (ctx: any) => ' ' + new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(ctx.raw),
            },
        },
    },
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 11 } } },
        y: {
            grid: { color: 'rgba(0,0,0,0.06)' },
            ticks: {
                font: { size: 11 },
                callback: (v: any) => {
                    if (v >= 1_000_000_000) return 'Rp ' + (v / 1_000_000_000).toFixed(1) + 'M'
                    if (v >= 1_000_000)     return 'Rp ' + (v / 1_000_000).toFixed(0) + 'jt'
                    if (v >= 1_000)         return 'Rp ' + (v / 1_000).toFixed(0) + 'rb'
                    return 'Rp ' + v
                },
            },
        },
    },
}))

const now = new Date()
const selectedMonth = ref<number>(stats.value.selected_month ?? now.getMonth() + 1)
const selectedYear  = ref<number>(stats.value.selected_year  ?? now.getFullYear())

function navigate(delta: number) {
    let m = selectedMonth.value + delta
    let y = selectedYear.value
    if (m < 1)  { m = 12; y-- }
    if (m > 12) { m = 1;  y++ }
    if (y < 2020 || (y > now.getFullYear()) || (y === now.getFullYear() && m > now.getMonth() + 1)) return
    selectedMonth.value = m
    selectedYear.value  = y
    router.get('/dashboard', { month: m, year: y }, { preserveScroll: true, preserveState: false })
}

const isCurrentMonth = computed(() =>
    selectedMonth.value === now.getMonth() + 1 && selectedYear.value === now.getFullYear()
)
const canGoNext = computed(() =>
    !(selectedYear.value === now.getFullYear() && selectedMonth.value >= now.getMonth() + 1)
)
watch(stats, s => {
    if (s.selected_month) selectedMonth.value = s.selected_month
    if (s.selected_year)  selectedYear.value  = s.selected_year
})

function goToCurrentMonth() {
    selectedMonth.value = now.getMonth() + 1
    selectedYear.value  = now.getFullYear()
    router.get('/dashboard', {}, { preserveScroll: true, preserveState: false })
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }]

const fmtIDR = (v: number | string | null) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Number(v ?? 0))

const fmtDate = (d: string | null) =>
    d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-'

const getInitials = (name?: string) => {
    if (!name) return 'U'
    return name.split(' ').filter(Boolean).slice(0, 2).map(p => p[0].toUpperCase()).join('')
}

const timeAgo = (dateString: string) => {
    const isoDate = dateString.endsWith('Z') || dateString.includes('+') ? dateString : dateString + 'Z'
    const diff = Date.now() - new Date(isoDate).getTime()
    const s = Math.floor(diff / 1000), m = Math.floor(s / 60), h = Math.floor(m / 60), d = Math.floor(h / 24)
    if (s < 60) return `${s} detik lalu`
    if (m < 60) return `${m} menit lalu`
    if (h < 24) return `${h} jam lalu`
    if (d === 1) return 'kemarin'
    return `${d} hari lalu`
}

const actionBadgeClass = (action: string) => ({
    create: 'bg-emerald-100 text-emerald-700',
    update: 'bg-sky-100 text-sky-700',
    delete: 'bg-rose-100 text-rose-700',
}[action] ?? 'bg-slate-100 text-slate-700')

const STATUS_LABEL: Record<string, string> = {
    pending: 'Pending', shipped: 'Shipped', azukari: 'Azukari', dead: 'Dead',
    cancel: 'Cancel', refund: 'Refund', dead_refund: 'Dead Refund', cancel_refund: 'Cancel Refund',
}
const STATUS_COLOR: Record<string, string> = {
    shipped:       'bg-blue-100 text-blue-700',
    pending:       'bg-yellow-100 text-yellow-700',
    azukari:       'bg-purple-100 text-purple-700',
    dead:          'bg-red-100 text-red-700',
    cancel:        'bg-gray-100 text-gray-600',
    refund:        'bg-orange-100 text-orange-700',
    dead_refund:   'bg-red-100 text-red-700',
    cancel_refund: 'bg-gray-100 text-gray-600',
}
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">

            <!-- ── Stat Cards Bulan Ini ── -->
            <div>
                <!-- Month/Year Navigator -->
                <div class="flex items-center gap-2 mb-3">
                    <button @click="navigate(-1)" class="inline-flex items-center justify-center rounded-md border border-border bg-background hover:bg-muted w-7 h-7 transition-colors">
                        <ChevronLeft class="w-4 h-4" />
                    </button>
                    <span class="text-sm font-semibold min-w-[140px] text-center">{{ stats.current_month }}</span>
                    <button @click="navigate(1)" :disabled="!canGoNext" class="inline-flex items-center justify-center rounded-md border border-border bg-background hover:bg-muted w-7 h-7 transition-colors disabled:opacity-30 disabled:cursor-not-allowed">
                        <ChevronRight class="w-4 h-4" />
                    </button>
                    <span v-if="!isCurrentMonth" class="text-[11px] text-muted-foreground bg-muted rounded-full px-2 py-0.5">bukan bulan ini</span>
                    <button v-if="!isCurrentMonth" @click="goToCurrentMonth"
                        class="text-[11px] underline text-primary hover:text-primary/80">
                        Kembali ke bulan ini
                    </button>
                </div>
                <div class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-4">

                    <!-- Modal -->
                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-5 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/40 flex items-center justify-center flex-shrink-0">
                            <Wallet class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground">Total Modal</p>
                            <p class="text-sm font-bold text-blue-600 dark:text-blue-400 leading-tight">{{ fmtIDR(stats.total_modal ?? 0) }}</p>
                        </div>
                    </div>

                    <!-- Penjualan -->
                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-5 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/40 flex items-center justify-center flex-shrink-0">
                            <ShoppingBag class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground">Total Penjualan</p>
                            <p class="text-sm font-bold text-amber-600 dark:text-amber-400 leading-tight">{{ fmtIDR(stats.total_sales_idr ?? 0) }}</p>
                        </div>
                    </div>

                    <!-- Profit -->
                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-5 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-green-50 dark:bg-green-950/40 flex items-center justify-center flex-shrink-0">
                            <BarChart3 class="w-5 h-5 text-green-600 dark:text-green-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground">Total Profit</p>
                            <p class="text-sm font-bold leading-tight" :class="(stats.total_profit ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500'">
                                {{ fmtIDR(stats.total_profit ?? 0) }}
                            </p>
                        </div>
                    </div>

                    <!-- Fee -->
                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-5 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-950/40 flex items-center justify-center flex-shrink-0">
                            <Percent class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground">Total Fee</p>
                            <p class="text-sm font-bold text-purple-600 dark:text-purple-400 leading-tight">{{ fmtIDR(stats.total_fee ?? 0) }}</p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ── Chart Trend 6 Bulan ── -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
                    <div>
                        <p class="text-sm font-semibold">Trend 6 Bulan Terakhir</p>
                        <p class="text-xs text-muted-foreground">Data berdasarkan bulan yang dipilih</p>
                    </div>
                    <!-- Metric toggle -->
                    <div class="flex rounded-lg border border-border overflow-hidden text-xs font-medium">
                        <button
                            @click="chartMetric = 'sales'"
                            :class="chartMetric === 'sales' ? 'bg-amber-500 text-white' : 'bg-background text-muted-foreground hover:bg-muted'"
                            class="px-3 py-1.5 transition-colors"
                        >Penjualan</button>
                        <button
                            @click="chartMetric = 'profit'"
                            :class="chartMetric === 'profit' ? 'bg-green-500 text-white' : 'bg-background text-muted-foreground hover:bg-muted'"
                            class="px-3 py-1.5 border-x border-border transition-colors"
                        >Profit</button>
                        <button
                            @click="chartMetric = 'fee'"
                            :class="chartMetric === 'fee' ? 'bg-purple-500 text-white' : 'bg-background text-muted-foreground hover:bg-muted'"
                            class="px-3 py-1.5 transition-colors"
                        >Fee</button>
                    </div>
                </div>
                <div class="h-56">
                    <Bar :data="chartData" :options="(chartOptions as any)" />
                </div>
                <!-- Mini summary row -->
                <div class="mt-3 grid grid-cols-3 gap-2 border-t border-border pt-3">
                    <div v-for="d in trendData.slice(-1)" :key="d.label" class="contents">
                        <div class="text-center">
                            <p class="text-[10px] text-muted-foreground">Penjualan (bulan ini)</p>
                            <p class="text-xs font-semibold text-amber-600">{{ fmtIDR(d.sales) }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] text-muted-foreground">Profit (bulan ini)</p>
                            <p class="text-xs font-semibold" :class="d.profit >= 0 ? 'text-green-600' : 'text-red-500'">{{ fmtIDR(d.profit) }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] text-muted-foreground">Koi terjual</p>
                            <p class="text-xs font-semibold text-foreground">{{ d.count }} ekor</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Stat Cards Keseluruhan ── -->
            <div>
                <div class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Koi Terjual -->
                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-5 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-orange-50 dark:bg-orange-950/40 flex items-center justify-center flex-shrink-0">
                            <Fish class="w-5 h-5 text-orange-600 dark:text-orange-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground">Koi Terjual</p>
                            <p class="text-2xl font-bold text-foreground leading-tight">{{ stats.total_koi_sold ?? 0 }}</p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-5 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-muted flex items-center justify-center flex-shrink-0">
                            <Fish class="w-5 h-5 text-muted-foreground" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground">Total Koi</p>
                            <p class="text-2xl font-bold text-foreground">{{ stats.total_koi ?? 0 }}</p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-5 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-muted flex items-center justify-center flex-shrink-0">
                            <Users class="w-5 h-5 text-muted-foreground" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground">Total Customer</p>
                            <p class="text-2xl font-bold text-foreground">{{ stats.total_customer ?? 0 }}</p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-5 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-muted flex items-center justify-center flex-shrink-0">
                            <Store class="w-5 h-5 text-muted-foreground" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground">Total Breeder</p>
                            <p class="text-2xl font-bold text-foreground">{{ stats.total_breeder ?? 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Transaksi Terbaru + Aktivitas ── -->
            <div class="grid gap-6 lg:grid-cols-2">

                <!-- Transaksi Terbaru -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-base font-semibold">Transaksi Terbaru</h2>
                            <p class="text-xs text-muted-foreground">8 transaksi terakhir</p>
                        </div>
                        <Link href="/rekap-transaksi" class="text-xs font-medium text-primary hover:text-primary/80">
                            Lihat semua →
                        </Link>
                    </div>

                    <div v-if="recentTransactions.length > 0" class="space-y-2">
                        <div v-for="trx in recentTransactions" :key="trx.id"
                            class="flex items-center justify-between gap-3 rounded-lg border border-border px-3 py-2 text-sm hover:bg-muted/30 transition-colors">
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-foreground truncate">{{ trx.code }}</p>
                                <p class="text-xs text-muted-foreground truncate">{{ trx.customer ?? '-' }} · {{ fmtDate(trx.sale_tanggal) }}</p>
                            </div>
                            <div class="flex flex-col items-end gap-1 flex-shrink-0">
                                <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', STATUS_COLOR[trx.sale_status ?? ''] ?? 'bg-gray-100 text-gray-600']">
                                    {{ STATUS_LABEL[trx.sale_status ?? ''] ?? trx.sale_status ?? '-' }}
                                </span>
                                <span class="text-xs text-muted-foreground">{{ fmtIDR(trx.sell_idr) }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-8 text-center text-sm text-muted-foreground">
                        Belum ada transaksi.
                    </div>
                </div>

                <!-- Aktivitas Terbaru -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-base font-semibold">Aktivitas Terbaru</h2>
                            <p class="text-xs text-muted-foreground">10 log terakhir</p>
                        </div>
                        <Link href="/log-aktivitas" class="text-xs font-medium text-primary hover:text-primary/80">
                            Lihat semua →
                        </Link>
                    </div>

                    <div v-if="latestActivityLogs.length > 0" class="space-y-2">
                        <div v-for="log in latestActivityLogs" :key="log.id"
                            class="flex items-start gap-3 rounded-lg border border-border px-3 py-2">
                            <div class="w-8 h-8 flex-shrink-0 rounded-full bg-primary/10 text-primary text-xs font-bold flex items-center justify-center">
                                {{ getInitials(log.user?.name) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <p class="text-xs font-semibold text-foreground truncate">{{ log.user?.name ?? 'Unknown' }}</p>
                                    <span :class="[actionBadgeClass(log.action), 'rounded-full px-1.5 py-0.5 text-[10px] font-semibold uppercase']">{{ log.action }}</span>
                                </div>
                                <p class="text-xs text-muted-foreground line-clamp-1 mt-0.5">{{ log.description }}</p>
                            </div>
                            <p class="text-xs text-muted-foreground whitespace-nowrap flex-shrink-0">{{ timeAgo(log.created_at) }}</p>
                        </div>
                    </div>
                    <div v-else class="py-8 text-center text-sm text-muted-foreground">
                        Belum ada aktivitas.
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
