<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

type Koi = {
    id: number
    code: string
    name: string
    photo?: string
}

type Purchase = {
    id: number
    koi_id: number
    date: string
    qty: number
    price_jpy: number
    price_idr: number
    status: string
    created_at: string
    koi?: Koi
}

const page = usePage()
const purchases = computed(() => (page.props.purchases as { data: Purchase[] } | null)?.data ?? [])
const kois = computed(() => (page.props.kois as Koi[] | null) ?? [])

function formatJPY(value: number | string | undefined | null): string {
    if (value === undefined || value === null || value === '') return '¥ 0'
    const num = Number(value)
    if (isNaN(num)) return '¥ 0'
    return '¥ ' + num.toLocaleString('en-US')
}

function formatIDR(value: number | string | undefined | null): string {
    if (value === undefined || value === null || value === '') return 'Rp 0'
    const num = Number(value)
    if (isNaN(num)) return 'Rp 0'
    return 'Rp ' + num.toLocaleString('id-ID')
}

function getKoiInfo(purchase: Purchase): { code: string; name: string } {
    if (purchase.koi) {
        return { code: purchase.koi.code, name: purchase.koi.name }
    }
    const koi = kois.value.find((k) => k.id === purchase.koi_id)
    return { code: koi?.code ?? '-', name: koi?.name ?? '-' }
}

const currentDate = new Date()
const totalAmount = computed(() => purchases.value.reduce((sum, p) => sum + (p.price_idr * p.qty), 0))
</script>

<template>
    <div class="min-h-screen bg-white p-0">
        <!-- Print Content -->
        <div class="mx-auto max-w-5xl bg-white p-8 print:p-0">
            <!-- Header -->
            <div class="mb-6 text-center">
                <h1 class="mb-1 text-2xl font-bold text-gray-900">LAPORAN PEMBELIAN KOI</h1>
                <p class="text-xs text-gray-600">PT. JD-KOI | {{ currentDate.toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
            </div>

            <!-- Divider -->
            <div class="mb-6 border-b-2 border-gray-900"></div>

            <!-- Main Table -->
            <div class="mb-8">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-800 text-white">
                            <th class="border border-gray-300 px-2 py-2 text-left text-xs font-semibold">No</th>
                            <th class="border border-gray-300 px-2 py-2 text-left text-xs font-semibold">Tanggal</th>
                            <th class="border border-gray-300 px-2 py-2 text-left text-xs font-semibold">Kode</th>
                            <th class="border border-gray-300 px-2 py-2 text-left text-xs font-semibold">Nama Koi</th>
                            <th class="border border-gray-300 px-2 py-2 text-center text-xs font-semibold">Qty</th>
                            <th class="border border-gray-300 px-2 py-2 text-right text-xs font-semibold">Harga JPY</th>
                            <th class="border border-gray-300 px-2 py-2 text-right text-xs font-semibold">Harga IDR</th>
                            <th class="border border-gray-300 px-2 py-2 text-right text-xs font-semibold">Total IDR</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="purchases.length === 0">
                            <td colspan="8" class="border border-gray-300 px-2 py-3 text-center text-xs text-gray-500">
                                Tidak ada data
                            </td>
                        </tr>
                        <tr v-for="(purchase, idx) in purchases" :key="purchase.id">
                            <td class="border border-gray-300 px-2 py-2 text-center text-xs font-medium text-gray-900">{{ idx + 1 }}</td>
                            <td class="border border-gray-300 px-2 py-2 text-xs text-gray-700">{{ new Date(purchase.date).toLocaleDateString('id-ID') }}</td>
                            <td class="border border-gray-300 px-2 py-2 font-mono text-xs font-bold text-gray-900">{{ getKoiInfo(purchase).code }}</td>
                            <td class="border border-gray-300 px-2 py-2 text-xs text-gray-700">{{ getKoiInfo(purchase).name }}</td>
                            <td class="border border-gray-300 px-2 py-2 text-center text-xs text-gray-700">{{ purchase.qty }}</td>
                            <td class="border border-gray-300 px-2 py-2 text-right font-mono text-xs text-gray-700">{{ formatJPY(purchase.price_jpy) }}</td>
                            <td class="border border-gray-300 px-2 py-2 text-right font-mono text-xs text-gray-700">{{ formatIDR(purchase.price_idr) }}</td>
                            <td class="border border-gray-300 px-2 py-2 text-right font-mono text-xs font-semibold text-gray-900">{{ formatIDR(purchase.price_idr * purchase.qty) }}</td>
                        </tr>

                        <!-- Total Row -->
                        <tr class="bg-gray-100 font-bold text-gray-900">
                            <td colspan="7" class="border border-gray-300 px-2 py-2 text-right text-xs uppercase">Total</td>
                            <td class="border border-gray-300 px-2 py-2 text-right font-mono text-xs">{{ formatIDR(totalAmount) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Spacer for Signature -->
            <div class="mt-12 flex justify-end">
                <div class="w-48 text-center">
                    <div class="mb-8 h-16"></div>
                    <p class="border-t border-gray-900 pt-1 text-xs font-semibold text-gray-900">______________________</p>
                    <p class="mt-0.5 text-xs text-gray-700">Penyetuju</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@media print {
    body,
    html {
        margin: 0;
        padding: 0;
        background: white;
    }

    .print\:p-0 {
        padding: 0;
    }

    /* Ensure proper page breaks */
    table {
        page-break-inside: avoid;
    }

    tr {
        page-break-inside: avoid;
    }

    /* Hide unnecessary elements on print */
    @page {
        margin: 0;
        size: A4;
    }
}
</style>
