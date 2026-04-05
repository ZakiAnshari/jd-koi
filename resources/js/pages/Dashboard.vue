<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type ActivityLogItem = {
    id: number
    user: { name: string } | null
    action: string
    description: string
    created_at: string
}

const page = usePage()
const summary = computed(() => (page.props.summary as {
    totalSales: number
    totalProfit: number
    totalKoiSold: number
    totalStock: number
}) ?? {
    totalSales: 0,
    totalProfit: 0,
    totalKoiSold: 0,
    totalStock: 0,
})
const latestActivityLogs = computed<ActivityLogItem[]>(() => (page.props.latestActivityLogs as ActivityLogItem[] | null) ?? [])
const authUser = computed(() => page.props.auth?.user)
const isAdmin = computed(() => authUser.value?.email === 'admin@gmail.com')

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
];

const currentDate = new Date()
const currentMonth = currentDate.getMonth() + 1
const currentYear = currentDate.getFullYear()

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value)
}

const getInitials = (name?: string) => {
    if (!name) {
        return 'U'
    }

    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map(part => part[0].toUpperCase())
        .join('')
}

const timeAgo = (dateString: string) => {
    const isoDate = dateString.endsWith('Z') || dateString.includes('+')
        ? dateString
        : dateString + 'Z'
    const diff = Date.now() - new Date(isoDate).getTime()
    const seconds = Math.floor(diff / 1000)
    const minutes = Math.floor(seconds / 60)
    const hours = Math.floor(minutes / 60)
    const days = Math.floor(hours / 24)

    if (seconds < 60) {
        return `${seconds} detik lalu`
    }
    if (minutes < 60) {
        return `${minutes} menit lalu`
    }
    if (hours < 24) {
        return `${hours} jam lalu`
    }

    return `${days} hari lalu`
}

const actionBadgeClasses = (action: string) => {
    switch (action) {
        case 'create':
            return 'bg-emerald-100 text-emerald-700'
        case 'update':
            return 'bg-sky-100 text-sky-700'
        case 'delete':
            return 'bg-rose-100 text-rose-700'
        default:
            return 'bg-slate-100 text-slate-700'
    }
}
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-4">
                <div
                    class="relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6">
                    <div class="flex flex-col justify-between h-full">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground mb-2">Total Penjualan Bulan Ini</p>
                            <h2 class="text-3xl font-bold text-foreground">{{ formatCurrency(summary.totalSales) }}
                            </h2>
                        </div>
                        <p class="text-xs text-muted-foreground mt-4">
                            {{ currentDate.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }) }}
                        </p>
                    </div>
                </div>
                <div
                    class="relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6">
                    <div class="flex flex-col justify-between h-full">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground mb-2">Total Profit Bulan Ini</p>
                            <h2 class="text-3xl font-bold text-foreground">{{ formatCurrency(summary.totalProfit) }}
                            </h2>
                        </div>
                        <p class="text-xs text-muted-foreground mt-4">
                            {{ currentDate.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }) }}
                        </p>
                    </div>
                </div>
                <div
                    class="relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6">
                    <div class="flex flex-col justify-between h-full">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground mb-2">Total Koi Terjual</p>
                            <h2 class="text-3xl font-bold text-foreground">{{ summary.totalKoiSold }}</h2>
                        </div>
                        <p class="text-xs text-muted-foreground mt-4">
                            {{ currentDate.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }) }}
                        </p>
                    </div>
                </div>
                <div
                    class="relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6">
                    <div class="flex flex-col justify-between h-full">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground mb-2">Sisa Stok</p>
                            <h2 class="text-3xl font-bold text-foreground">{{ summary.totalStock }}</h2>
                        </div>
                        <p class="text-xs text-muted-foreground mt-4">
                            {{ currentDate.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }) }}
                        </p>
                    </div>
                </div>
            </div>
            <div v-if="isAdmin" class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-foreground">Aktivitas Terbaru</h2>
                        <p class="text-sm text-muted-foreground">10 log aktivitas terbaru</p>
                    </div>
                    <Link href="/log-aktivitas" class="text-sm font-medium text-primary hover:text-primary/80">
                        Lihat semua aktivitas →
                    </Link>
                </div>
                <div class="mt-6 space-y-4">
                    <template v-if="latestActivityLogs.length > 0">
                        <div v-for="log in latestActivityLogs" :key="log.id" class="flex items-start gap-4 rounded-2xl border border-border bg-surface p-4">
                            <div class="flex h-11 w-11 flex-none items-center justify-center rounded-full bg-primary/10 text-primary font-semibold">
                                {{ getInitials(log.user?.name) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate text-sm font-semibold text-foreground">{{ log.user?.name || 'Pengguna tidak dikenal' }}</p>
                                    <span :class="actionBadgeClasses(log.action) + ' rounded-full px-2 py-0.5 text-[11px] font-semibold uppercase tracking-[0.08em]'">
                                        {{ log.action }}
                                    </span>
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
