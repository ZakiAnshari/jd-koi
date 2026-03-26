<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

type Koi = {
    id: number
    code: string
    name: string
}

type Transaction = {
    id: number
    koi_id: number
    date: string
    status: string
    qty: number
    sell_jpy: number
    sell_idr: number
    margin: number
    fee: number
    stock_jpy: number
    stock_idr: number
    sell_to: string
    profit: number
    created_at: string
    koi?: Koi
}

const page = usePage()
const transaction = page.props.transaction as Transaction

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Transaksi Penjualan',
        href: '/transactions',
    },
    {
        title: 'Detail',
        href: `/transactions/${transaction.id}`,
    },
]

function formatJPY(value: number | string | undefined): string {
    if (!value) return '¥ 0'
    const num = Number(value)
    return '¥ ' + num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
}

function formatIDR(value: number | string | undefined): string {
    if (!value) return 'Rp 0'
    const num = Number(value)
    return 'Rp ' + num.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Transaction Detail" />

        <div class="space-y-6 p-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Transaksi Penjualan</h1>
                    <p class="text-sm text-muted-foreground">Detail transaksi penjualan</p>
                </div>
                <div class="flex gap-2">
                    <Link
                        :href="`/transactions/${transaction.id}/edit`"
                        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        Edit
                    </Link>
                    <Link
                        href="/transactions"
                        class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted"
                    >
                        Back to list
                    </Link>
                </div>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                <div class="space-y-6">
                    <!-- Transaction Info Section -->
                    <div class="space-y-2 border-b pb-4">
                        <h3 class="text-sm font-semibold text-foreground">Informasi Transaksi</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-xs text-muted-foreground">Date</p>
                                <p class="text-sm">{{ new Date(transaction.date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Status</p>
                                <p class="text-sm font-medium">{{ transaction.status }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Koi</p>
                                <p class="text-sm">{{ transaction.koi?.code }} - {{ transaction.koi?.name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Pembeli</p>
                                <p class="text-sm">{{ transaction.sell_to }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Created</p>
                                <p class="text-sm">{{ new Date(transaction.created_at).toLocaleString('id-ID') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing JPY Section -->
                    <div class="space-y-2 border-b pb-4">
                        <h3 class="text-sm font-semibold text-foreground">Harga JPY (¥)</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-xs text-muted-foreground">Quantity</p>
                                <p class="text-sm">{{ transaction.qty }} pcs</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Harga Jual</p>
                                <p class="text-sm">{{ formatJPY(transaction.sell_jpy) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Nilai Stok</p>
                                <p class="text-sm">{{ formatJPY(transaction.stock_jpy) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Margin (%)</p>
                                <p class="text-sm">{{ transaction.margin }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Fee</p>
                                <p class="text-sm text-orange-600">{{ formatJPY(transaction.fee) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Profit</p>
                                <p class="text-sm font-semibold text-green-600">{{ formatJPY(transaction.profit) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing IDR Section -->
                    <div class="space-y-2 border-b pb-4">
                        <h3 class="text-sm font-semibold text-foreground">Harga IDR (Rp)</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-xs text-muted-foreground">Harga Jual</p>
                                <p class="text-sm">{{ formatIDR(transaction.sell_idr) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Nilai Stok</p>
                                <p class="text-sm">{{ formatIDR(transaction.stock_idr) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="flex items-center gap-4 border-t pt-6">
                        <Link
                            :href="`/transactions/${transaction.id}/edit`"
                            class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            Edit Transaksi
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
