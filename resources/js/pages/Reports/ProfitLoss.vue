<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Button } from '@/components/ui/button'
import { Printer } from 'lucide-vue-next'

type PageProps = {
    summary: {
        total_revenue: number
        total_profit: number
        total_fee: number
        total_margin: number
        total_expense: number
        net_profit: number
    }
    koiBreakdown: Array<{
        koi_id: number
        koi_code: string
        koi_name: string
        qty_buy: number
        qty_sell: number
        total_buy: number
        total_sell: number
        profit: number
    }>
    month: number
    year: number
}

const page = usePage<PageProps>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/' },
    { title: 'Laporan Laba Rugi', href: '/reports/profit-loss' },
]

const selectedMonth = ref(page.props.month)
const selectedYear = ref(page.props.year)

const monthNames = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
]

const years = computed(() => {
    const currentYear = new Date().getFullYear()
    const years = []
    for (let i = currentYear - 5; i <= currentYear + 1; i++) {
        years.push(i)
    }
    return years
})

const handleFilterChange = () => {
    router.get('/reports/profit-loss', {
        month: selectedMonth.value,
        year: selectedYear.value,
    }, { 
        preserveState: true,
        preserveScroll: true,
    })
}

const formatCurrency = (value: number, currency: 'IDR' | 'JPY' = 'IDR') => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value)
}

const formatNumber = (value: number) => {
    return new Intl.NumberFormat('id-ID').format(value)
}

const handlePrint = () => {
    window.print()
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Laporan Laba Rugi" />

        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div>
                <h1 class="text-3xl font-bold">Laporan Laba Rugi</h1>
                <p class="mt-1 text-sm text-muted-foreground">Analisis keuntungan dan kerugian per koi</p>
            </div>

            <!-- Filters -->
            <div class="flex flex-col gap-4 rounded-lg border border-border bg-card p-4 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="month" class="block text-sm font-medium">Bulan</label>
                    <select
                        id="month"
                        v-model.number="selectedMonth"
                        @change="handleFilterChange"
                        class="mt-2 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option v-for="(month, index) in monthNames" :key="index" :value="index + 1">
                            {{ month }}
                        </option>
                    </select>
                </div>

                <div class="flex-1">
                    <label for="year" class="block text-sm font-medium">Tahun</label>
                    <select
                        id="year"
                        v-model.number="selectedYear"
                        @change="handleFilterChange"
                        class="mt-2 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option v-for="year in years" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                </div>

                <Button
                    @click="handlePrint"
                    class="gap-2"
                >
                    <Printer class="h-4 w-4" />
                    Cetak
                </Button>

                <Button
                    as-child
                    class="gap-2"
                >
                    <a
                        :href="`/reports/profit-loss/export-excel?month=${selectedMonth}&year=${selectedYear}`"
                    >
                        <Download class="h-4 w-4" />
                        Excel
                    </a>
                </Button>
            </div>

            <!-- Summary Cards -->
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-lg border border-border bg-card p-6">
                    <p class="text-sm font-medium text-muted-foreground">Total Pendapatan</p>
                    <p class="mt-2 text-2xl font-bold text-green-600">{{ formatCurrency(page.props.summary.total_revenue) }}</p>
                </div>

                <div class="rounded-lg border border-border bg-card p-6">
                    <p class="text-sm font-medium text-muted-foreground">Total Biaya</p>
                    <p class="mt-2 text-2xl font-bold text-red-600">{{ formatCurrency(page.props.summary.total_expense) }}</p>
                </div>

                <div class="rounded-lg border border-border bg-card p-6">
                    <p class="text-sm font-medium text-muted-foreground">Total Laba Bersih</p>
                    <p 
                        class="mt-2 text-2xl font-bold"
                        :class="page.props.summary.net_profit >= 0 ? 'text-green-600' : 'text-red-600'"
                    >
                        {{ formatCurrency(page.props.summary.net_profit) }}
                    </p>
                </div>
            </div>

            <!-- Data Table -->
            <div class="overflow-hidden rounded-lg border border-border">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Koi</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Pendapatan</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Biaya</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Laba Bersih</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-background">
                            <tr v-if="page.props.koiBreakdown.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                    Tidak ada data untuk periode ini.
                                </td>
                            </tr>
                            <tr v-for="(item, index) in page.props.koiBreakdown" :key="index" class="transition-colors hover:bg-muted/50">
                                <td class="px-4 py-3 text-sm">{{ index + 1 }}</td>
                                <td class="px-4 py-3 text-sm font-medium">{{ item.koi_name }}</td>
                                <td class="px-4 py-3 text-right text-sm text-green-600">{{ formatCurrency(item.total_sell) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-red-600">{{ formatCurrency(item.total_buy) }}</td>
                                <td 
                                    class="px-4 py-3 text-right text-sm font-medium"
                                    :class="item.profit >= 0 ? 'text-green-600' : 'text-red-600'"
                                >
                                    {{ formatCurrency(item.profit) }}
                                </td>
                            </tr>
                            <tr v-if="page.props.koiBreakdown.length > 0" class="border-t-2 border-t-border bg-muted font-semibold">
                                <td colspan="2" class="px-4 py-3 text-sm">Total</td>
                                <td class="px-4 py-3 text-right text-sm text-green-600">{{ formatCurrency(page.props.summary.total_revenue) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-red-600">{{ formatCurrency(page.props.summary.total_expense) }}</td>
                                <td 
                                    class="px-4 py-3 text-right text-sm"
                                    :class="page.props.summary.net_profit >= 0 ? 'text-green-600' : 'text-red-600'"
                                >
                                    {{ formatCurrency(page.props.summary.net_profit) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>

    <!-- Print Area - hanya muncul saat print -->
    <div class="print-area">
        <!-- KOP SURAT -->
        <div style="text-align: center; margin-bottom: 24px; border-bottom: 3px double #000; padding-bottom: 16px;">
            <h1 style="font-size: 20px; font-weight: bold; margin: 0;">JAPAN DIRECT KOI CENTER</h1>
            <p style="font-size: 12px; margin: 4px 0;">Jl. Contoh Alamat No. 123, Kota, Indonesia</p>
            <p style="font-size: 12px; margin: 0;">Telp: 0812-xxxx-xxxx | Email: info@jdkoi.com</p>
        </div>

        <!-- JUDUL LAPORAN -->
        <div style="text-align: center; margin-bottom: 20px;">
            <h2 style="font-size: 16px; font-weight: bold; text-transform: uppercase;">LAPORAN LABA RUGI</h2>
            <p style="font-size: 12px;">Periode: {{ monthNames[selectedMonth - 1] }} {{ selectedYear }}</p>
        </div>

        <!-- SUMMARY -->
        <div style="margin-bottom: 20px;">
            <p style="font-size: 12px; margin: 4px 0;">Total Pendapatan: {{ formatCurrency(page.props.summary.total_revenue) }}</p>
            <p style="font-size: 12px; margin: 4px 0;">Total Biaya: {{ formatCurrency(page.props.summary.total_expense) }}</p>
            <p style="font-size: 12px; margin: 4px 0;">Total Laba Bersih: {{ formatCurrency(page.props.summary.net_profit) }}</p>
        </div>

        <!-- TABEL DATA -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 40px;">
            <thead>
                <tr>
                    <th style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; background-color: #e5e5e5; font-weight: bold; text-align: center;">No</th>
                    <th style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; background-color: #e5e5e5; font-weight: bold; text-align: center;">Koi</th>
                    <th style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; background-color: #e5e5e5; font-weight: bold; text-align: center;">Qty Beli</th>
                    <th style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; background-color: #e5e5e5; font-weight: bold; text-align: center;">Qty Jual</th>
                    <th style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; background-color: #e5e5e5; font-weight: bold; text-align: center;">Total Beli IDR</th>
                    <th style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; background-color: #e5e5e5; font-weight: bold; text-align: center;">Total Jual IDR</th>
                    <th style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; background-color: #e5e5e5; font-weight: bold; text-align: center;">Profit</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(item, index) in page.props.koiBreakdown" :key="index">
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: center;">{{ index + 1 }}</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px;">{{ item.koi_name }}</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: center;">{{ item.qty_buy }}</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: center;">{{ item.qty_sell }}</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: right;">{{ formatCurrency(item.total_buy) }}</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: right;">{{ formatCurrency(item.total_sell) }}</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: right;">{{ formatCurrency(item.profit) }}</td>
                </tr>
                <tr style="font-weight: bold;">
                    <td colspan="2" style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: center;">Total</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: center;">-</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: center;">-</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: right;">{{ formatCurrency(page.props.summary.total_expense) }}</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: right;">{{ formatCurrency(page.props.summary.total_revenue) }}</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: right;">{{ formatCurrency(page.props.summary.net_profit) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- TANDA TANGAN -->
        <div style="margin-top: 40px; display: flex; justify-content: flex-end;">
            <div style="text-align: center; width: 200px;">
                <p style="font-size: 12px; margin-bottom: 60px;">{{ monthNames[selectedMonth - 1] }} {{ selectedYear }}</p>
                <p style="font-size: 12px; margin: 0; border-top: 1px solid #000; padding-top: 4px; font-weight: bold;">Manager</p>
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    /* Sembunyikan semua kecuali area cetak */
    body * {
        visibility: hidden !important;
    }
    .print-area,
    .print-area * {
        visibility: visible !important;
    }
    .print-area {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        padding: 20px !important;
        font-family: 'Times New Roman', serif !important;
        font-size: 12px !important;
        color: #000 !important;
        background: #fff !important;
    }
    .print-area table {
        width: 100% !important;
        border-collapse: collapse !important;
    }
    .print-area th,
    .print-area td {
        border: 1px solid #000 !important;
        padding: 6px 8px !important;
        font-size: 11px !important;
        color: #000 !important;
    }
    .print-area th {
        background-color: #e5e5e5 !important;
        font-weight: bold !important;
        text-align: center !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    @page {
        size: A4 portrait;
        margin: 15mm;
    }
}

/* Sembunyikan print area di layar normal */
.print-area {
    display: none;
}
@media print {
    .print-area {
        display: block !important;
    }
}
</style>
