<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import { computed, onMounted } from 'vue'

onMounted(() => {
    window.print()
})

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
        <div class="mx-auto max-w-4xl bg-white p-10">
            <!-- Header -->
            <div class="mb-10 border-b border-gray-200 pb-8">
                <div class="mb-2">
                    <h1 class="text-2xl font-light tracking-tight text-gray-900">LAPORAN PEMBELIAN KOI</h1>
                </div>
                <div class="flex justify-between items-center">
                    <p class="text-sm text-gray-600">PT. JD-KOI | Data Pembelian</p>
                    <p class="text-sm text-gray-500">{{ currentDate.toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
                </div>
            </div>

            <!-- Main Table -->
            <div class="mb-8">
                <table class="w-full">
                    <thead>
                        <tr class="border-b-2 border-gray-900">
                            <th class="px-3 py-3 text-left text-xs font-semibold text-gray-900 tracking-wider">No</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-gray-900 tracking-wider">Tanggal</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-gray-900 tracking-wider">Kode</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-gray-900 tracking-wider">Nama Koi</th>
                            <th class="px-3 py-3 text-center text-xs font-semibold text-gray-900 tracking-wider">Qty</th>
                            <th class="px-3 py-3 text-right text-xs font-semibold text-gray-900 tracking-wider">Harga JPY</th>
                            <th class="px-3 py-3 text-right text-xs font-semibold text-gray-900 tracking-wider">Harga IDR</th>
                            <th class="px-3 py-3 text-right text-xs font-semibold text-gray-900 tracking-wider">Total IDR</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="purchases.length === 0">
                            <td colspan="8" class="px-3 py-4 text-center text-sm text-gray-500">
                                Tidak ada data
                            </td>
                        </tr>
                        <tr v-for="(purchase, idx) in purchases" :key="purchase.id" class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="px-3 py-3 text-center text-sm text-gray-700">{{ idx + 1 }}</td>
                            <td class="px-3 py-3 text-sm text-gray-700">{{ new Date(purchase.date).toLocaleDateString('id-ID') }}</td>
                            <td class="px-3 py-3 font-mono text-sm font-medium text-gray-900">{{ getKoiInfo(purchase).code }}</td>
                            <td class="px-3 py-3 text-sm text-gray-700">{{ getKoiInfo(purchase).name }}</td>
                            <td class="px-3 py-3 text-center text-sm text-gray-700">{{ purchase.qty }}</td>
                            <td class="px-3 py-3 text-right font-mono text-sm text-gray-700">{{ formatJPY(purchase.price_jpy) }}</td>
                            <td class="px-3 py-3 text-right font-mono text-sm text-gray-700">{{ formatIDR(purchase.price_idr) }}</td>
                            <td class="px-3 py-3 text-right font-mono text-sm font-medium text-gray-900">{{ formatIDR(purchase.price_idr * purchase.qty) }}</td>
                        </tr>

                        <!-- Total Row -->
                        <tr class="border-t-2 border-gray-900 bg-gray-50">
                            <td colspan="7" class="px-3 py-4 text-right text-sm font-semibold text-gray-900">Total</td>
                            <td class="px-3 py-4 text-right font-mono text-sm font-semibold text-gray-900">{{ formatIDR(totalAmount) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Spacer for Signature -->
            <div class="mt-16 flex justify-end">
                <div class="w-40 text-center">
                    <div class="mb-10 h-14"></div>
                    <p class="border-t border-gray-900 pt-2 text-sm font-medium text-gray-900">_________________</p>
                    <p class="mt-1 text-xs text-gray-600">Penyetuju</p>
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

    :deep(div) {
        margin: 0;
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
        margin: 15mm;
        size: A4;
    }
}
</style>
