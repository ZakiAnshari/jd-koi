<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Button } from '@/components/ui/button'

type Koi = {
    id: number
    code: string
    name: string
    breeder_id: number
}

type Stock = {
    id: number
    koi_id: number
    date: string
    qty: number
    price_jpy: number
    price_idr: number
    total_jpy: number
    total_idr: number
    created_at: string
    koi?: Koi
}

const page = usePage()
const stock = page.props.stock as Stock

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Manajemen Stok',
        href: '/inventory',
    },
    {
        title: 'Detail',
        href: `/inventory/${stock.id}`,
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
        <Head title="Stock Detail" />

        <div class="space-y-6 p-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Stock Detail</h1>
                    <p class="text-sm text-muted-foreground">View stock information.</p>
                </div>
                <Link
                    href="/inventory"
                    class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted"
                >
                    Back to list
                </Link>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                <div class="space-y-6">
                    <!-- Basic Information -->
                    <div class="space-y-2 border-b pb-4">
                        <h3 class="text-sm font-semibold text-foreground">Informasi Stok</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-xs font-medium text-muted-foreground">Date</p>
                                <p class="text-sm font-semibold">{{ new Date(stock.date).toLocaleDateString() }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-muted-foreground">Koi</p>
                                <p class="text-sm font-semibold">{{ stock.koi?.code }} - {{ stock.koi?.name }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-muted-foreground">Quantity</p>
                                <p class="text-sm font-semibold">{{ stock.qty }} pcs</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-muted-foreground">Created</p>
                                <p class="text-sm font-semibold">{{ new Date(stock.created_at).toLocaleString() }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- JPY Pricing -->
                    <div class="space-y-2 border-b pb-4">
                        <h3 class="text-sm font-semibold text-foreground">Harga Yen (JPY) 🇯🇵</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-xs font-medium text-muted-foreground">Price JPY</p>
                                <p class="text-sm font-semibold">{{ formatJPY(stock.price_jpy) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-muted-foreground">Total JPY</p>
                                <p class="text-lg font-bold text-blue-600">{{ formatJPY(stock.total_jpy) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- IDR Pricing -->
                    <div class="space-y-2">
                        <h3 class="text-sm font-semibold text-foreground">Harga Rupiah (IDR) 🇮🇩</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-xs font-medium text-muted-foreground">Price IDR</p>
                                <p class="text-sm font-semibold">{{ formatIDR(stock.price_idr) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-muted-foreground">Total IDR</p>
                                <p class="text-lg font-bold text-red-600">{{ formatIDR(stock.total_idr) }}</p>
                            </div>
                        </div>
                    </div>

                    
                </div>
            </div>
        </div>
    </AppLayout>
</template>
