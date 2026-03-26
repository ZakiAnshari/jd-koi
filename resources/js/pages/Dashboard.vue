<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

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
        title: 'Dashboard',
        href: dashboard(),
    },
];

const currentDate = new Date()
const currentMonth = currentDate.getMonth() + 1
const currentYear = currentDate.getFullYear()
const chartView = ref<'daily' | 'monthly'>('daily')
const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']

const totalSalesThisMonth = computed(() => {
    return salesReports.value
        .filter(report => report.month === currentMonth && report.year === currentYear)
        .reduce((sum, report) => sum + (report.sell_idr || 0), 0)
})

const totalProfitThisMonth = computed(() => {
    return salesReports.value
        .filter(report => report.month === currentMonth && report.year === currentYear)
        .reduce((sum, report) => sum + (report.profit || 0), 0)
})

const totalKoiSoldThisMonth = computed(() => {
    return salesReports.value
        .filter(report => report.month === currentMonth && report.year === currentYear)
        .reduce((sum, report) => sum + (report.qty || 0), 0)
})

const totalRemainingStock = computed(() => {
    return salesReports.value
        .filter(report => report.month === currentMonth && report.year === currentYear)
        .reduce((sum, report) => sum + (report.stock_idr || 0), 0)
})

const salesByDay = computed(() => {
    const dailySales: Record<number, number> = {}
    
    salesReports.value
        .filter(report => report.month === currentMonth && report.year === currentYear)
        .forEach(report => {
            if (!dailySales[report.day]) {
                dailySales[report.day] = 0
            }
            dailySales[report.day] += report.sell_idr || 0
        })
    
    // Get the max day in current month
    const daysInMonth = new Date(currentYear, currentMonth, 0).getDate()
    const labels = Array.from({ length: daysInMonth }, (_, i) => `${i + 1}`)
    const data = labels.map((_, i) => dailySales[i + 1] || 0)
    
    return { labels, data }
})

const salesByMonth = computed(() => {
    const monthlySales: Record<number, number> = {}
    
    salesReports.value
        .filter(report => report.year === currentYear)
        .forEach(report => {
            if (!monthlySales[report.month]) {
                monthlySales[report.month] = 0
            }
            monthlySales[report.month] += report.sell_idr || 0
        })
    
    const labels = monthNames
    const data = labels.map((_, i) => monthlySales[i + 1] || 0)
    
    return { labels, data }
})

const chartData = computed(() => {
    return chartView.value === 'daily' ? salesByDay.value : salesByMonth.value
})

const maxChartValue = computed(() => {
    return Math.max(...chartData.value.data, 1)
})

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value)
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
                            <h2 class="text-3xl font-bold text-foreground">{{ formatCurrency(totalSalesThisMonth) }}</h2>
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
                            <h2 class="text-3xl font-bold text-foreground">{{ formatCurrency(totalProfitThisMonth) }}</h2>
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
                            <h2 class="text-3xl font-bold text-foreground">{{ totalKoiSoldThisMonth }}</h2>
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
                            <h2 class="text-3xl font-bold text-foreground">{{ totalRemainingStock }}</h2>
                        </div>
                        <p class="text-xs text-muted-foreground mt-4">
                            {{ currentDate.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }) }}
                        </p>
                    </div>
                </div>
            </div>
            <div
                class="relative flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-foreground">Grafik Penjualan</h3>
                    <div class="flex gap-2">
                        <button
                            @click="chartView = 'daily'"
                            :class="[
                                'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                                chartView === 'daily'
                                    ? 'bg-blue-500 text-white'
                                    : 'border border-input text-foreground hover:bg-muted'
                            ]">
                            Per Hari
                        </button>
                        <button
                            @click="chartView = 'monthly'"
                            :class="[
                                'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                                chartView === 'monthly'
                                    ? 'bg-blue-500 text-white'
                                    : 'border border-input text-foreground hover:bg-muted'
                            ]">
                            Per Bulan
                        </button>
                    </div>
                </div>
                <div class="w-full h-96 flex items-end justify-start gap-1 overflow-x-auto pb-8">
                    <div v-if="chartData.data.length === 0" class="w-full h-full flex items-center justify-center text-muted-foreground">
                        Tidak ada data penjualan
                    </div>
                    <div v-for="(value, index) in chartData.data" :key="index" class="flex flex-col items-center gap-2 min-w-fit">
                        <div class="flex items-end gap-1">
                            <div
                                :style="{ height: `${Math.max((value / maxChartValue) * 300, 10)}px` }"
                                class="w-8 bg-blue-500 rounded-t cursor-pointer hover:bg-blue-600 transition-colors"
                                :title="`${chartView === 'daily' ? 'Hari ' : ''}${chartData.labels[index]}: ${formatCurrency(value)}`">
                            </div>
                        </div>
                        <span class="text-xs text-muted-foreground whitespace-nowrap">{{ chartData.labels[index] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
