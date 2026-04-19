<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

type Breeder = {
    name_breeder: string
}

type Koi = {
    id: number
    nama: string
    kode: string
    photo?: string
    images?: Array<{ id: number; url: string }>
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
    katalogKoi: KatalogKoi
}

type PaymentInfo = {
    bank: string
    account_name: string
    account_number: string
}

const props = defineProps<{
    pesanan: Pesanan
    payment_info: PaymentInfo
    whatsapp_url: string
}>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pesanan Saya',
        href: '/customer/pesanan',
    },
    {
        title: props.pesanan.kode_pesanan,
        href: `/customer/pesanan/${props.pesanan.id}`,
    },
]

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
        month: 'short',
        day: '2-digit',
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
        pending: 'Menunggu',
        confirmed: 'Terkonfirmasi',
        completed: 'Selesai',
        cancelled: 'Dibatalkan',
    }
    return labels[status] || status
}

const storagePath = (photo?: string) => {
    if (!photo) return ''
    return `/storage/${photo}`
}

const thumbUrl = () => {
    if (props.pesanan.katalogKoi.koi.images && props.pesanan.katalogKoi.koi.images.length > 0) {
        return props.pesanan.katalogKoi.koi.images[0].url
    }
    return storagePath(props.pesanan.katalogKoi.koi.photo)
}

const openWhatsApp = () => {
    window.open(props.whatsapp_url, '_blank')
}
</script>

<template>
    <Head :title="`Pesanan ${pesanan.kode_pesanan}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Detail Pesanan</h1>
                    <p class="text-sm text-muted-foreground">Informasi lengkap pesanan Anda.</p>
                </div>
                <Link
                    href="/customer/pesanan"
                    class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted self-start md:self-auto"
                >
                    Kembali
                </Link>
            </div>

            <!-- Main Content — 2-col desktop -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- LEFT COLUMN -->
                <div class="space-y-6">
                    <!-- Card 1: Informasi Pesanan -->
                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6 space-y-4">
                        <h2 class="text-base font-semibold text-foreground">Informasi Pesanan</h2>

                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between pb-3 border-b border-border">
                                <span class="text-muted-foreground">Kode Pesanan</span>
                                <span class="font-mono font-semibold text-primary">{{ pesanan.kode_pesanan }}</span>
                            </div>
                            <div class="flex justify-between pb-3 border-b border-border">
                                <span class="text-muted-foreground">Tanggal Pesanan</span>
                                <span class="font-medium text-foreground">{{ formatDate(pesanan.created_at) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-muted-foreground">Status</span>
                                <span :class="`px-2 py-0.5 rounded-full text-xs font-semibold ${statusBadge(pesanan.status)}`">
                                    {{ statusLabel(pesanan.status) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Informasi Koi -->
                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6 space-y-4">
                        <h2 class="text-base font-semibold text-foreground">Informasi Koi</h2>

                        <!-- Thumbnail -->
                        <div class="rounded-lg overflow-hidden bg-muted aspect-video">
                            <img
                                v-if="pesanan.katalogKoi.koi.images && pesanan.katalogKoi.koi.images.length > 0"
                                :src="pesanan.katalogKoi.koi.images[0].url"
                                :alt="pesanan.katalogKoi.koi.nama"
                                class="w-full h-full object-cover"
                            />
                            <img
                                v-else-if="pesanan.katalogKoi.koi.photo"
                                :src="storagePath(pesanan.katalogKoi.koi.photo)"
                                :alt="pesanan.katalogKoi.koi.nama"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-muted-foreground text-sm">
                                Foto Tidak Tersedia
                            </div>
                        </div>

                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between pb-3 border-b border-border">
                                <span class="text-muted-foreground">Nama Koi</span>
                                <span class="font-medium text-foreground">{{ pesanan.katalogKoi.koi.nama }}</span>
                            </div>
                            <div class="flex justify-between pb-3 border-b border-border">
                                <span class="text-muted-foreground">Breeder</span>
                                <span class="font-medium text-foreground">{{ pesanan.katalogKoi.koi.breeder.name_breeder }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-muted-foreground">Harga per Ekor</span>
                                <span class="font-semibold text-primary">{{ formatCurrency(pesanan.katalogKoi.sell_idr) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN -->
                <div>
                    <!-- Card: Ringkasan Pesanan -->
                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6 space-y-5">
                        <h2 class="text-base font-semibold text-foreground">Ringkasan Pesanan</h2>

                        <!-- Informasi Pembayaran -->
                        <div class="space-y-2">
                            <h3 class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Informasi Pembayaran</h3>
                            <div class="bg-muted/30 rounded-lg p-4 space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Bank</span>
                                    <span class="font-medium text-foreground">{{ payment_info.bank }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Atas Nama</span>
                                    <span class="font-medium text-foreground">{{ payment_info.account_name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Nomor Rekening</span>
                                    <span class="font-mono font-semibold text-foreground">{{ payment_info.account_number }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Rincian Harga -->
                        <div class="space-y-2">
                            <h3 class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Rincian Harga</h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Koi</span>
                                    <span class="font-medium text-foreground">{{ pesanan.katalogKoi.koi.nama }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Harga per Ekor</span>
                                    <span class="font-medium text-foreground">{{ formatCurrency(pesanan.harga_satuan) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Jumlah</span>
                                    <span class="font-medium text-foreground">{{ pesanan.qty }} ekor</span>
                                </div>
                            </div>
                        </div>

                        <!-- Divider -->
                        <div class="h-px bg-border" />

                        <!-- Grand Total -->
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-foreground text-sm">Total Pembayaran</span>
                            <span class="text-xl font-bold text-green-600 dark:text-green-400">
                                {{ formatCurrency(pesanan.total_harga) }}
                            </span>
                        </div>

                        <!-- Catatan pesanan -->
                        <div v-if="pesanan.catatan" class="space-y-1">
                            <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Catatan</p>
                            <p class="text-sm text-foreground bg-muted/30 p-3 rounded-lg border border-border">
                                {{ pesanan.catatan }}
                            </p>
                        </div>

                        <!-- Pending info box -->
                        <div
                            v-if="pesanan.status === 'pending'"
                            class="rounded-lg border border-yellow-200 dark:border-yellow-900/50 bg-yellow-50 dark:bg-yellow-950/20 p-3 text-sm text-yellow-800 dark:text-yellow-200"
                        >
                            Pesanan Anda sedang menunggu konfirmasi. Silakan selesaikan pembayaran dan hubungi kami untuk konfirmasi.
                        </div>

                        <!-- WhatsApp Button -->
                        <button
                            @click="openWhatsApp"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm font-semibold transition-colors"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            Hubungi via WhatsApp
                        </button>

                        <p class="text-xs text-muted-foreground text-center">
                            Selesaikan pembayaran dan hubungi kami untuk konfirmasi pesanan.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
