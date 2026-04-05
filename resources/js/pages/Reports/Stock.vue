<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Button } from '@/components/ui/button'
import { Printer, Download } from 'lucide-vue-next'

type PageProps = {
    stocks: Array<{
        koi_id: number
        koi_code: string
        koi_name: string
        opening_stock: number
        stock_in: number
        stock_out: number
        closing_stock: number
        movements: Array<{
            date: string
            type: string
            qty: number
            notes: string
        }>
    }>
    month: number
    year: number
}

const page = usePage<PageProps>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/' },
    { title: 'Laporan Stok', href: '/reports/stock' },
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

const totalSummary = computed(() => {
    return {
        total_opening: page.props.stocks.reduce((sum, stock) => sum + stock.opening_stock, 0),
        total_in: page.props.stocks.reduce((sum, stock) => sum + stock.stock_in, 0),
        total_out: page.props.stocks.reduce((sum, stock) => sum + stock.stock_out, 0),
        total_closing: page.props.stocks.reduce((sum, stock) => sum + stock.closing_stock, 0),
    }
})

const handleFilterChange = () => {
    router.get('/reports/stock', {
        month: selectedMonth.value,
        year: selectedYear.value,
    }, { 
        preserveState: true,
        preserveScroll: true,
    })
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
        <Head title="Laporan Stok" />

        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div>
                <h1 class="text-3xl font-bold">Laporan Stok Koi</h1>
                <p class="mt-1 text-sm text-muted-foreground">Pergerakan stok koi berdasarkan bulan</p>
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
                        :href="`/reports/stock/export-excel?month=${selectedMonth}&year=${selectedYear}`"
                    >
                        <Download class="h-4 w-4" />
                        Excel
                    </a>
                </Button>
            </div>

            <!-- Summary Cards -->
            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-lg border border-border bg-card p-6">
                    <p class="text-sm font-medium text-muted-foreground">Stok Awal</p>
                    <p class="mt-2 text-2xl font-bold">{{ formatNumber(totalSummary.total_opening) }}</p>
                </div>

                <div class="rounded-lg border border-border bg-card p-6">
                    <p class="text-sm font-medium text-muted-foreground">Masuk</p>
                    <p class="mt-2 text-2xl font-bold text-green-600">{{ formatNumber(totalSummary.total_in) }}</p>
                </div>

                <div class="rounded-lg border border-border bg-card p-6">
                    <p class="text-sm font-medium text-muted-foreground">Keluar</p>
                    <p class="mt-2 text-2xl font-bold text-red-600">{{ formatNumber(totalSummary.total_out) }}</p>
                </div>

                <div class="rounded-lg border border-border bg-card p-6">
                    <p class="text-sm font-medium text-muted-foreground">Stok Akhir</p>
                    <p class="mt-2 text-2xl font-bold">{{ formatNumber(totalSummary.total_closing) }}</p>
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
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Stok Awal</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Masuk</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Keluar</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Stok Akhir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-background">
                            <tr v-if="page.props.stocks.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                    Tidak ada pergerakan stok untuk periode ini.
                                </td>
                            </tr>
                            <tr v-for="(item, index) in page.props.stocks" :key="index" class="transition-colors hover:bg-muted/50">
                                <td class="px-4 py-3 text-sm">{{ index + 1 }}</td>
                                <td class="px-4 py-3 text-sm font-medium">{{ item.koi_name }}</td>
                                <td class="px-4 py-3 text-right text-sm">{{ item.opening_stock }}</td>
                                <td class="px-4 py-3 text-right text-sm text-green-600">{{ item.stock_in }}</td>
                                <td class="px-4 py-3 text-right text-sm text-red-600">{{ item.stock_out }}</td>
                                <td class="px-4 py-3 text-right text-sm font-medium">{{ item.closing_stock }}</td>
                            </tr>
                            <tr v-if="page.props.stocks.length > 0" class="border-t-2 border-t-border bg-muted font-semibold">
                                <td colspan="2" class="px-4 py-3 text-sm">Total</td>
                                <td class="px-4 py-3 text-right text-sm">{{ formatNumber(totalSummary.total_opening) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-green-600">{{ formatNumber(totalSummary.total_in) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-red-600">{{ formatNumber(totalSummary.total_out) }}</td>
                                <td class="px-4 py-3 text-right text-sm">{{ formatNumber(totalSummary.total_closing) }}</td>
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
            <h2 style="font-size: 16px; font-weight: bold; text-transform: uppercase;">LAPORAN STOK KOI</h2>
            <p style="font-size: 12px;">Periode: {{ monthNames[selectedMonth - 1] }} {{ selectedYear }}</p>
        </div>

        <!-- TABEL DATA -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 40px;">
            <thead>
                <tr>
                    <th style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; background-color: #e5e5e5; font-weight: bold; text-align: center;">No</th>
                    <th style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; background-color: #e5e5e5; font-weight: bold; text-align: center;">Koi</th>
                    <th style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; background-color: #e5e5e5; font-weight: bold; text-align: center;">Stok Awal</th>
                    <th style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; background-color: #e5e5e5; font-weight: bold; text-align: center;">Masuk</th>
                    <th style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; background-color: #e5e5e5; font-weight: bold; text-align: center;">Keluar</th>
                    <th style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; background-color: #e5e5e5; font-weight: bold; text-align: center;">Stok Akhir</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(item, index) in page.props.stocks" :key="index">
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: center;">{{ index + 1 }}</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px;">{{ item.koi_name }}</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: center;">{{ item.opening_stock }}</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: center;">{{ item.stock_in }}</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: center;">{{ item.stock_out }}</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: center;">{{ item.closing_stock }}</td>
                </tr>
                <tr style="font-weight: bold;">
                    <td colspan="2" style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: center;">Total</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: center;">{{ formatNumber(totalSummary.total_opening) }}</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: center;">{{ formatNumber(totalSummary.total_in) }}</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: center;">{{ formatNumber(totalSummary.total_out) }}</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: center;">{{ formatNumber(totalSummary.total_closing) }}</td>
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
