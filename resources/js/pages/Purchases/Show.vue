<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

type Koi = {
    id: number
    code: string
    name: string
}

type Purchase = {
    id: number
    date: string
    qty: number
    price_jpy: number
    price_idr: number
    status: string
    created_at: string
    koi: Koi
}

const page = usePage()
const purchase = page.props.purchase as Purchase

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pembelian',
        href: '/pembelian',
    },
    {
        title: 'Detail',
        href: `/pembelian/${purchase.id}`,
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
        <Head title="Detail Pembelian" />

        <div class="space-y-6 p-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Detail Pembelian</h1>
                    <p class="text-sm text-muted-foreground">Informasi lengkap pembelian koi.</p>
                </div>
                <Link
                    href="/pembelian"
                    class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted"
                >
                    Kembali
                </Link>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <div class="text-sm text-muted-foreground">Tanggal</div>
                        <div class="text-base font-medium">{{ new Date(purchase.date).toLocaleDateString() }}</div>
                    </div>
                    <div class="space-y-2">
                        <div class="text-sm text-muted-foreground">Status</div>
                        <div class="text-base font-medium capitalize">{{ purchase.status }}</div>
                    </div>
                    <div class="space-y-2">
                        <div class="text-sm text-muted-foreground">Koi</div>
                        <div class="text-base font-medium">{{ purchase.koi.code }} - {{ purchase.koi.name }}</div>
                    </div>
                    <div class="space-y-2">
                        <div class="text-sm text-muted-foreground">Qty</div>
                        <div class="text-base font-medium">{{ purchase.qty }}</div>
                    </div>
                    <div class="space-y-2">
                        <div class="text-sm text-muted-foreground">Harga JPY</div>
                        <div class="text-base font-medium">{{ formatJPY(purchase.price_jpy) }}</div>
                    </div>
                    <div class="space-y-2">
                        <div class="text-sm text-muted-foreground">Harga IDR</div>
                        <div class="text-base font-medium">{{ formatIDR(purchase.price_idr) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
