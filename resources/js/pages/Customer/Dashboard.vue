<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Fish, CheckCircle, Clock, XCircle, Eye } from 'lucide-vue-next'

type CustomerStats = {
    nama: string
    total_koi: number
    total_pending: number
    status_breakdown: Record<string, number>
}

type RecentTrx = {
    id: number
    code: string
    name: string
    sale_invoice: string | null
    sale_tanggal: string | null
    sale_status: string | null
    sell_idr: string | null
    sale_qty: number | null
    breeder: string | null
}

const props = defineProps<{
    customerStats: CustomerStats
    recentTransactions: RecentTrx[]
}>()

const page = usePage()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/customer/dashboard' },
]

const fmtIDR = (v: number | string | null) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Number(v ?? 0))

const fmtDate = (d: string | null) =>
    d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-'

const getInitials = (name: string) =>
    name.split(' ').filter(Boolean).slice(0, 2).map(p => p[0].toUpperCase()).join('')

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

const breakdown = computed(() => props.customerStats.status_breakdown ?? {})
const statusList = [
    { key: 'shipped',  label: 'Shipped',  icon: CheckCircle, color: 'text-blue-500' },
    { key: 'azukari',  label: 'Azukari',  icon: CheckCircle, color: 'text-purple-500' },
    { key: 'pending',  label: 'Pending',  icon: Clock,       color: 'text-yellow-500' },
    { key: 'dead',     label: 'Dead',     icon: XCircle,     color: 'text-red-500' },
    { key: 'cancel',   label: 'Cancel',   icon: XCircle,     color: 'text-gray-400' },
    { key: 'refund',   label: 'Refund',   icon: XCircle,     color: 'text-orange-500' },
]
const activeStatuses = computed(() =>
    statusList.filter(s => (breakdown.value[s.key] ?? 0) > 0)
)
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">

            <!-- Welcome Banner -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-5">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0 text-primary text-lg font-bold select-none">
                        {{ getInitials(customerStats.nama) }}
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-foreground">Selamat datang, {{ customerStats.nama }}!</h1>
                        <p class="text-sm text-muted-foreground">Berikut ringkasan transaksi koi Anda.</p>
                    </div>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="grid gap-4 grid-cols-2 md:grid-cols-3">
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-5 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-yellow-50 dark:bg-yellow-950/40 flex items-center justify-center flex-shrink-0">
                        <Clock class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground">Koi Pending</p>
                        <p class="text-2xl font-bold text-foreground">{{ customerStats.total_pending }}</p>
                    </div>
                </div>

                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-5 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-orange-50 dark:bg-orange-950/40 flex items-center justify-center flex-shrink-0">
                        <Fish class="w-5 h-5 text-orange-600 dark:text-orange-400" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground">Total Koi</p>
                        <p class="text-2xl font-bold text-foreground">{{ customerStats.total_koi }}</p>
                    </div>
                </div>

                <div class="col-span-2 md:col-span-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-5">
                    <p class="text-xs text-muted-foreground mb-2">Status Koi</p>
                    <div v-if="activeStatuses.length > 0" class="flex flex-wrap gap-2">
                        <div v-for="s in activeStatuses" :key="s.key"
                            :class="['inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium', STATUS_COLOR[s.key]]">
                            <component :is="s.icon" :class="['w-3 h-3', s.color]" />
                            {{ s.label }}: {{ breakdown[s.key] }}
                        </div>
                    </div>
                    <p v-else class="text-xs text-muted-foreground">Belum ada transaksi.</p>
                </div>
            </div>

            <!-- Transaksi Terbaru -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-semibold">Transaksi Terbaru</h2>
                        <p class="text-xs text-muted-foreground">5 transaksi terakhir Anda</p>
                    </div>
                    <Link href="/rekap-transaksi" class="text-xs font-medium text-primary hover:text-primary/80">
                        Lihat semua →
                    </Link>
                </div>

                <div v-if="recentTransactions.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Invoice</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Koi</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Breeder</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Tanggal</th>
                                <th class="px-4 py-2.5 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground">Status</th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Total</th>
                                <th class="px-4 py-2.5"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-background">
                            <tr v-for="trx in recentTransactions" :key="trx.id" class="hover:bg-muted/50 transition-colors">
                                <td class="px-4 py-3 text-xs font-mono text-muted-foreground whitespace-nowrap">{{ trx.sale_invoice ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm font-medium whitespace-nowrap">{{ trx.code }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground whitespace-nowrap">{{ trx.breeder ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm whitespace-nowrap">{{ fmtDate(trx.sale_tanggal) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span v-if="trx.sale_status"
                                        :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium', STATUS_COLOR[trx.sale_status] ?? 'bg-gray-100 text-gray-600']">
                                        {{ STATUS_LABEL[trx.sale_status] ?? trx.sale_status }}
                                    </span>
                                    <span v-else class="text-xs text-muted-foreground">-</span>
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-medium whitespace-nowrap">{{ fmtIDR(trx.sell_idr) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="`/rekap-transaksi/${trx.id}`"
                                        class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background p-2 text-foreground shadow-sm hover:bg-muted"
                                        title="Detail">
                                        <Eye :size="15" />
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="py-10 text-center text-sm text-muted-foreground">
                    Belum ada transaksi.
                </div>
            </div>

        </div>
    </AppLayout>
</template>
