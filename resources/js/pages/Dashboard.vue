<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes'
import type { BreadcrumbItem } from '@/types'
import { RotateCcw, Users, Fish, DollarSign, TrendingUp, Package } from 'lucide-vue-next'

type ActivityLogItem = {
    id: number
    user: { name: string } | null
    action: string
    description: string
    created_at: string
}

const page = usePage()

const summary = computed(() => (page.props.summary as any) ?? {})
const latestActivityLogs = computed<ActivityLogItem[]>(() => (page.props.latestActivityLogs as any) ?? [])
const adminStats = computed(() => {
    const s = (page.props.adminStats as any)
    return s && !Array.isArray(s) ? s : null
})

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }]

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

const actionBadgeClasses = (action: string) => {
    switch (action) {
        case 'create': return 'bg-emerald-100 text-emerald-700'
        case 'update': return 'bg-sky-100 text-sky-700'
        case 'delete': return 'bg-rose-100 text-rose-700'
        default: return 'bg-slate-100 text-slate-700'
    }
}
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">

            <!-- Stat Cards -->
            <div class="grid gap-4 grid-cols-2 md:grid-cols-3 xl:grid-cols-5">
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-5 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-muted flex items-center justify-center flex-shrink-0">
                        <DollarSign class="w-5 h-5 text-muted-foreground" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground truncate">Total Penjualan</p>
                        <p class="text-base font-bold text-foreground leading-tight">{{ formatCurrency(summary.totalSales ?? 0) }}</p>
                        <p class="text-xs text-muted-foreground/70">Bulan ini</p>
                    </div>
                </div>

                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-5 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-muted flex items-center justify-center flex-shrink-0">
                        <TrendingUp class="w-5 h-5 text-muted-foreground" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground truncate">Total Profit</p>
                        <p class="text-base font-bold text-foreground leading-tight">{{ formatCurrency(summary.totalProfit ?? 0) }}</p>
                        <p class="text-xs text-muted-foreground/70">Bulan ini</p>
                    </div>
                </div>

                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-5 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-muted flex items-center justify-center flex-shrink-0">
                        <Fish class="w-5 h-5 text-muted-foreground" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground truncate">Koi Terjual</p>
                        <p class="text-2xl font-bold text-foreground leading-tight">{{ summary.totalKoiSold ?? 0 }}</p>
                        <p class="text-xs text-muted-foreground/70">Bulan ini</p>
                    </div>
                </div>

                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-5 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-muted flex items-center justify-center flex-shrink-0">
                        <Users class="w-5 h-5 text-muted-foreground" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground truncate">Total Customer</p>
                        <p class="text-2xl font-bold text-foreground leading-tight">{{ adminStats?.total_customer ?? 0 }}</p>
                    </div>
                </div>

                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-5 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-muted flex items-center justify-center flex-shrink-0">
                        <Package class="w-5 h-5 text-muted-foreground" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground truncate">Total Koi</p>
                        <p class="text-2xl font-bold text-foreground leading-tight">{{ adminStats?.total_koi ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <!-- Aktivitas Terbaru -->
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

        </div>
    </AppLayout>
</template>
