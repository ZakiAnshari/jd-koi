<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Button } from '@/components/ui/button'
import { Printer, Download } from 'lucide-vue-next'

type PageProps = {
    salesByDate: Array<{
        date: string
        total_qty: number
        total_jpy: number
        total_idr: number
        items: Array<{
            id: number
            koi_code: string
            koi_name: string
            customer_name: string
            qty: number
            sell_jpy: number
            sell_idr: number
        }>
    }>
    summary: {
        total_qty: number
        total_jpy: number
        total_idr: number
        avg_price_jpy: number
        avg_price_idr: number
    }
    currentMonth: number
    currentYear: number
    month: number
    year: number
}

const page = usePage<PageProps>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/' },
    { title: 'Laporan Penjualan', href: '/reports/sales' },
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

// Flatten salesByDate to get individual items
const salesData = computed(() => {
    const items: Array<{
        date: string
        koi_name: string
        customer_name: string
        quantity: number
        sell_jpy: number
        sell_idr: number
        fee: number
        profit: number
    }> = []
    
    page.props.salesByDate.forEach(dateGroup => {
        dateGroup.items.forEach((item: any) => {
            items.push({
                date: dateGroup.date,
                koi_name: item.koi_name || '-',
                customer_name: item.customer_name || '-',
                quantity: item.qty,
                sell_jpy: item.sell_jpy,
                sell_idr: item.sell_idr,
                fee: 0, // Fee not available in current data
                profit: 0, // Profit not available in current data
            })
        })
    })
    return items
})

const handleFilterChange = () => {
    router.get('/reports/sales', {
        month: selectedMonth.value,
        year: selectedYear.value,
    }, { 
        preserveState: true,
        preserveScroll: true,
    })
}

const formatCurrency = (value: number, currency: 'IDR' | 'JPY') => {
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
        <Head title="Laporan Penjualan" />

        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div>
                <h1 class="text-3xl font-bold">Laporan Penjualan</h1>
                <p class="mt-1 text-sm text-muted-foreground">Ringkasan penjualan koi berdasarkan koi dan tanggalnya</p>
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
                        :href="`/reports/sales/export-excel?month=${selectedMonth}&year=${selectedYear}`"
                    >
                        <Download class="h-4 w-4" />
                        Excel
                    </a>
                </Button>
            </div>

            <!-- Summary Cards -->
            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-lg border border-border bg-card p-6">
                    <p class="text-sm font-medium text-muted-foreground">Total IDR</p>
                    <p class="mt-2 text-2xl font-bold">{{ formatCurrency(page.props.summary.total_idr, 'IDR') }}</p>
                </div>

                <div class="rounded-lg border border-border bg-card p-6">
                    <p class="text-sm font-medium text-muted-foreground">Total JPY</p>
                    <p class="mt-2 text-2xl font-bold">{{ formatCurrency(page.props.summary.total_jpy, 'JPY') }}</p>
                </div>

                <div class="rounded-lg border border-border bg-card p-6">
                    <p class="text-sm font-medium text-muted-foreground">Total Qty</p>
                    <p class="mt-2 text-2xl font-bold">{{ formatNumber(page.props.summary.total_qty) }}</p>
                </div>

                <div class="rounded-lg border border-border bg-card p-6">
                    <p class="text-sm font-medium text-muted-foreground">Rata-rata Harga IDR</p>
                    <p class="mt-2 text-2xl font-bold">{{ formatCurrency(page.props.summary.avg_price_idr, 'IDR') }}</p>
                </div>
            </div>

            <!-- Data Table -->
            <div class="overflow-hidden rounded-lg border border-border">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Koi</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Customer</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Qty</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Sell JPY</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Sell IDR</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-background">
                            <tr v-if="salesData.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                    Tidak ada data penjualan untuk periode ini.
                                </td>
                            </tr>
                            <tr v-for="(item, index) in salesData" :key="index" class="transition-colors hover:bg-muted/50">
                                <td class="px-4 py-3 text-sm">{{ index + 1 }}</td>
                                <td class="px-4 py-3 text-sm">{{ item.date }}</td>
                                <td class="px-4 py-3 text-sm font-medium">{{ item.koi_name }}</td>
                                <td class="px-4 py-3 text-sm">{{ item.customer_name }}</td>
                                <td class="px-4 py-3 text-right text-sm">{{ item.quantity }}</td>
                                <td class="px-4 py-3 text-right text-sm">{{ formatCurrency(item.sell_jpy, 'JPY') }}</td>
                                <td class="px-4 py-3 text-right text-sm">{{ formatCurrency(item.sell_idr, 'IDR') }}</td>
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
            <h2 style="font-size: 16px; font-weight: bold; text-transform: uppercase;">LAPORAN PENJUALAN</h2>
            <p style="font-size: 12px;">Periode: {{ monthNames[selectedMonth - 1] }} {{ selectedYear }}</p>
        </div>

        <!-- TABEL DATA -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 40px;">
            <thead>
                <tr>
                    <th style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; background-color: #e5e5e5; font-weight: bold; text-align: center;">No</th>
                    <th style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; background-color: #e5e5e5; font-weight: bold; text-align: center;">Tanggal</th>
                    <th style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; background-color: #e5e5e5; font-weight: bold; text-align: center;">Koi</th>
                    <th style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; background-color: #e5e5e5; font-weight: bold; text-align: center;">Customer</th>
                    <th style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; background-color: #e5e5e5; font-weight: bold; text-align: center;">Qty</th>
                    <th style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; background-color: #e5e5e5; font-weight: bold; text-align: center;">Sell JPY</th>
                    <th style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; background-color: #e5e5e5; font-weight: bold; text-align: center;">Sell IDR</th>
                    <th style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; background-color: #e5e5e5; font-weight: bold; text-align: center;">Fee</th>
                    <th style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; background-color: #e5e5e5; font-weight: bold; text-align: center;">Profit</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(item, index) in salesData" :key="index">
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: center;">{{ index + 1 }}</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px;">{{ item.date }}</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px;">{{ item.koi_name }}</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px;">{{ item.customer_name }}</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: center;">{{ item.quantity }}</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: right;">{{ formatCurrency(item.sell_jpy, 'JPY') }}</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: right;">{{ formatCurrency(item.sell_idr, 'IDR') }}</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: right;">0</td>
                    <td style="border: 1px solid #000; padding: 6px 8px; font-size: 11px; text-align: right;">0</td>
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
        size: A4 landscape;
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
