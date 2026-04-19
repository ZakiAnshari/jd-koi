<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
const adminPesananUpdateStatus = { url: (id: number) => `/admin/pesanan/${id}/status` }

type Customer = {
    id: number
    name: string
    email: string
    no_telepon?: string
    alamat?: string
}

type Breeder = {
    id: number
    name_breeder: string
}

type Koi = {
    id: number
    name: string
    code: string
    photo?: string
    breeder: Breeder
}

type KatalogKoi = {
    id: number
    sell_idr: number
    koi: Koi
}

type Pesanan = {
    id: number
    kode_pesanan: string
    qty: number
    harga_satuan: number
    total_harga: number
    status: 'pending' | 'confirmed' | 'cancelled' | 'completed'
    catatan?: string
    created_at: string
    customer: Customer
    katalog_koi: KatalogKoi
}

type PaymentInfo = {
    bank: string
    account_name: string
    account_number: string
}

const props = defineProps<{
    pesanan: Pesanan
    payment_info: PaymentInfo
}>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pesanan Customer',
        href: '/admin/pesanan',
    },
    {
        title: props.pesanan.kode_pesanan,
        href: `/admin/pesanan/${props.pesanan.id}`,
    },
]

const form = useForm({
    status: props.pesanan.status,
})

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value)
}

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

const statusBadge = (status: string) => {
    const badges: Record<string, string> = {
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        confirmed: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        completed: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    }
    return badges[status] || 'bg-gray-100 text-gray-800'
}

const statusLabel = (status: string) => {
    const labels: Record<string, string> = {
        pending: 'Pending',
        confirmed: 'Confirmed',
        completed: 'Completed',
        cancelled: 'Cancelled',
    }
    return labels[status] || status
}

const updateStatus = () => {
    if (form.status === props.pesanan.status) return
    
    form.put(adminPesananUpdateStatus.url(props.pesanan.id))
}

const storagePath = (photo?: string) => {
    if (!photo) return ''
    return `/storage/${photo}`
}
</script>

<template>
    <Head :title="`Detail Pesanan ${pesanan.kode_pesanan}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-foreground">Detail Pesanan</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ pesanan.kode_pesanan }} — informasi pesanan dan pelanggan.
                    </p>
                </div>
                <Link
                    href="/admin/pesanan"
                    class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted self-start md:self-auto"
                >
                    Kembali
                </Link>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column - Order & Customer Info -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Order Information Card -->
                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-muted/20 p-6 space-y-4">
                        <h2 class="text-base font-semibold text-foreground">Informasi Pesanan</h2>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between pb-4 border-b border-border">
                                <span class="text-sm font-medium text-muted-foreground">Kode Pesanan</span>
                                <span class="font-mono font-semibold text-primary text-lg">{{ pesanan.kode_pesanan }}</span>
                            </div>

                            <div class="flex items-center justify-between pb-4 border-b border-border">
                                <span class="text-sm font-medium text-muted-foreground">Tanggal Pesanan</span>
                                <span class="font-semibold text-foreground">{{ formatDate(pesanan.created_at) }}</span>
                            </div>

                            <div class="flex items-center justify-between pb-4 border-b border-border">
                                <span class="text-sm font-medium text-muted-foreground">Status</span>
                                <span :class="`px-3 py-1 rounded-full text-xs font-semibold ${statusBadge(pesanan.status)}`">
                                    {{ statusLabel(pesanan.status) }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-muted-foreground">Quantity</span>
                                <span class="font-semibold text-foreground">{{ pesanan.qty }} ekor</span>
                            </div>
                        </div>
                    </div>

                    <!-- Koi Information Card -->
                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-muted/20 p-6 space-y-4">
                        <h2 class="text-base font-semibold text-foreground">Informasi Koi</h2>

                        <div class="flex gap-4">
                            <!-- Photo -->
                            <div v-if="pesanan.katalog_koi.koi.photo" class="flex-shrink-0">
                                <img
                                    :src="storagePath(pesanan.katalog_koi.koi.photo)"
                                    :alt="pesanan.katalog_koi.koi.name"
                                    class="w-32 h-32 rounded-lg object-cover border border-border"
                                />
                            </div>

                            <!-- Info -->
                            <div class="flex-1 space-y-3">
                                <div>
                                    <p class="text-xs font-semibold text-muted-foreground uppercase">Nama Koi</p>
                                    <p class="text-lg font-bold text-foreground">{{ pesanan.katalog_koi.koi.name }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-muted-foreground uppercase">Kode Koi</p>
                                    <p class="font-mono text-sm text-muted-foreground">{{ pesanan.katalog_koi.koi.code }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-muted-foreground uppercase">Breeder</p>
                                    <p class="text-sm text-foreground">{{ pesanan.katalog_koi.koi.breeder.name_breeder }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-muted-foreground uppercase">Harga per ekor</p>
                                    <p class="text-sm font-semibold text-primary">{{ formatCurrency(pesanan.harga_satuan) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Information Card -->
                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-muted/20 p-6 space-y-4">
                        <h2 class="text-base font-semibold text-foreground">Informasi Pelanggan</h2>

                        <div class="space-y-4">
                            <div class="pb-4 border-b border-border">
                                <p class="text-xs font-semibold text-muted-foreground uppercase mb-1">Nama</p>
                                <p class="font-semibold text-foreground">{{ pesanan.customer.name }}</p>
                            </div>

                            <div class="pb-4 border-b border-border">
                                <p class="text-xs font-semibold text-muted-foreground uppercase mb-1">Email</p>
                                <p class="text-sm text-muted-foreground">{{ pesanan.customer.email }}</p>
                            </div>

                            <div class="pb-4 border-b border-border">
                                <p class="text-xs font-semibold text-muted-foreground uppercase mb-1">No. Telepon</p>
                                <p class="text-sm text-foreground">{{ pesanan.customer.no_telepon || '-' }}</p>
                            </div>

                            <div class="pb-4 border-b border-border">
                                <p class="text-xs font-semibold text-muted-foreground uppercase mb-1">Alamat</p>
                                <p class="text-sm text-foreground">{{ pesanan.customer.alamat || '-' }}</p>
                            </div>

                            <div v-if="pesanan.catatan" class="pb-4 border-b border-border">
                                <p class="text-xs font-semibold text-muted-foreground uppercase mb-1">Catatan</p>
                                <p class="text-sm text-foreground bg-background/50 p-3 rounded-lg border border-border">
                                    {{ pesanan.catatan }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Summary & Actions -->
                <div class="space-y-6">
                    <!-- Payment Information Card -->
                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-muted/20 p-6 space-y-4">
                        <h2 class="text-base font-semibold text-foreground">Informasi Pembayaran</h2>

                        <div class="space-y-3 text-sm bg-background/50 p-4 rounded-lg border border-border">
                            <div>
                                <p class="text-xs font-semibold text-muted-foreground uppercase mb-1">Bank</p>
                                <p class="font-medium text-foreground">{{ payment_info.bank }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-muted-foreground uppercase mb-1">Atas Nama</p>
                                <p class="font-medium text-foreground">{{ payment_info.account_name }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-muted-foreground uppercase mb-1">Nomor Rekening</p>
                                <p class="font-mono font-semibold text-foreground">{{ payment_info.account_number }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary Card -->
                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-muted/20 p-6 space-y-4">
                        <h2 class="text-base font-semibold text-foreground">Ringkasan Pesanan</h2>

                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between pb-3 border-b border-border">
                                <span class="text-muted-foreground">Harga per ekor</span>
                                <span class="font-semibold text-foreground">{{ formatCurrency(pesanan.harga_satuan) }}</span>
                            </div>
                            <div class="flex justify-between pb-3 border-b border-border">
                                <span class="text-muted-foreground">Quantity</span>
                                <span class="font-semibold text-foreground">{{ pesanan.qty }} ekor</span>
                            </div>

                            <div class="pt-3 border-t border-border">
                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-foreground">Total</span>
                                    <span class="text-xl font-bold text-primary">
                                        {{ formatCurrency(pesanan.total_harga) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Update Card -->
                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-muted/20 p-6 space-y-4">
                        <h2 class="text-base font-semibold text-foreground">Update Status</h2>

                        <select
                            v-model="form.status"
                            @change="updateStatus"
                            class="w-full px-3 py-2 rounded-lg border border-sidebar-border/70 dark:border-sidebar-border bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>

                        <p class="text-xs text-muted-foreground">
                            Status akan diperbarui setelah Anda mengubahnya.
                        </p>
                    </div>

                    <!-- Back Link -->
                    <Link
                        href="/admin/pesanan"
                        class="flex items-center justify-center px-6 py-3 rounded-lg border border-sidebar-border/70 dark:border-sidebar-border text-foreground font-semibold hover:bg-muted transition-colors w-full"
                    >
                        Kembali ke Daftar
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
