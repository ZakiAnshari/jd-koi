<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

type Koi = {
    id: number
    code: string
    name: string
}

type Customer = {
    id: number
    name: string
}

type Transaction = {
    id: number
    koi_id: number
    customer_id: number
    date: string
    status: string
    qty: number
    sell_jpy: number
    sell_idr: number
    fee: number
    margin: number
    profit: number
    created_at: string
    koi?: Koi
    customer?: Customer
}

const page = usePage()
const transaction = page.props.transaction as Transaction

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Transaksi Penjualan',
        href: '/penjualan',
    },
    {
        title: 'Detail',
        href: `/penjualan/${transaction.id}`,
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
                        :href="`/penjualan/${transaction.id}/edit`"
                        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        Edit
                    </Link>
                    <Link
                        href="/penjualan"
                        class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted"
                    >
                        Back to list
                    </Link>
                </div>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                <div class="space-y-6">
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
                                <p class="text-xs text-muted-foreground">Customer</p>
                                <p class="text-sm">{{ transaction.customer?.name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Created</p>
                                <p class="text-sm">{{ new Date(transaction.created_at).toLocaleString('id-ID') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2 border-b pb-4">
                        <h3 class="text-sm font-semibold text-foreground">Ringkasan Harga</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-xs text-muted-foreground">Qty</p>
                                <p class="text-sm">{{ transaction.qty }} pcs</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Sell JPY</p>
                                <p class="text-sm">{{ formatJPY(transaction.sell_jpy) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Sell IDR</p>
                                <p class="text-sm">{{ formatIDR(transaction.sell_idr) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Fee</p>
                                <p class="text-sm text-orange-600">{{ formatIDR(transaction.fee) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Margin (%)</p>
                                <p class="text-sm">{{ transaction.margin }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Profit</p>
                                <p class="text-sm font-semibold text-green-600">{{ formatIDR(transaction.profit) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
