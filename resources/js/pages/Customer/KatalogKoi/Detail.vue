<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { checkout } from '@/routes/customer/pesanan'

type Breeder = {
    name_breeder: string
}

type Koi = {
    id: number
    nama: string
    kode: string
    photo?: string
    deskripsi?: string
    breeder: Breeder
}

type Katalog = {
    id: number
    tanggal: string
    qty: number
    sell_idr: number
    koi: Koi
}

const props = defineProps<{
    katalog: Katalog
}>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Katalog Koi',
        href: '/customer/katalog',
    },
    {
        title: props.katalog.koi.nama,
        href: `/customer/katalog/${props.katalog.id}`,
    },
]

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value)
}

const storagePath = (photo?: string) => {
    if (!photo) return ''
    return `/storage/${photo}`
}

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
}
</script>

<template>
    <Head :title="`Detail - ${katalog.koi.nama}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Back Button -->
            <Link
                href="/customer/katalog"
                class="inline-flex items-center text-sm font-medium text-primary hover:text-primary/80 mb-4 w-fit"
            >
                ← Kembali ke Katalog
            </Link>

            <!-- Detail Card -->
            <div class="rounded-xl border border-border bg-background overflow-hidden">
                <div class="grid md:grid-cols-2 gap-0">
                    <!-- Left - Photo -->
                    <div class="bg-muted flex items-center justify-center aspect-video md:aspect-square md:h-96">
                        <img
                            v-if="katalog.koi.photo"
                            :src="storagePath(katalog.koi.photo)"
                            :alt="katalog.koi.nama"
                            class="w-full h-full object-cover"
                        />
                        <div
                            v-else
                            class="w-full h-full flex items-center justify-center text-muted-foreground"
                        >
                            <span>Foto Tidak Tersedia</span>
                        </div>
                    </div>

                    <!-- Right - Details -->
                    <div class="p-6 sm:p-8 space-y-6 flex flex-col justify-between bg-gradient-to-b from-background to-muted/30">
                        <!-- Header Section -->
                        <div class="space-y-4">
                            <!-- Title & Breeder -->
                            <div class="space-y-2">
                                <h1 class="text-4xl font-bold text-foreground leading-tight">
                                    {{ katalog.koi.nama }}
                                </h1>
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-primary" />
                                    <p class="text-sm font-semibold text-muted-foreground">
                                        Breeder: {{ katalog.koi.breeder.name_breeder }}
                                    </p>
                                </div>
                            </div>

                            <!-- Price Highlight -->
                            <div class="bg-primary/10 border border-primary/20 rounded-xl p-4 space-y-1">
                                <p class="text-xs font-semibold text-primary uppercase tracking-wide">Harga Jual</p>
                                <p class="text-3xl font-bold text-primary">
                                    {{ formatCurrency(katalog.sell_idr).replace('Rp ', '') }}
                                </p>
                            </div>
                        </div>

                        <!-- Key Info Grid -->
                        <div class="grid grid-cols-2 gap-3">
                            <!-- Kode -->
                            <div class="bg-muted rounded-lg p-3 space-y-1">
                                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Kode</p>
                                <p class="font-mono font-bold text-foreground text-sm">
                                    {{ katalog.koi.kode }}
                                </p>
                            </div>

                            <!-- Stok -->
                            <div class="bg-muted rounded-lg p-3 space-y-1">
                                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Stok</p>
                                <p v-if="katalog.qty > 0" class="font-bold text-green-600 dark:text-green-400 text-sm flex items-baseline gap-1">
                                    {{ katalog.qty }}
                                    <span class="text-xs font-medium text-muted-foreground">ekor tersedia</span>
                                </p>
                                <p v-else class="font-bold text-red-600 dark:text-red-400 text-sm">
                                    Stok Habis
                                </p>
                            </div>

                            <!-- Tanggal Masuk -->
                            <div class="bg-muted rounded-lg p-3 space-y-1 col-span-2">
                                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Tanggal Masuk</p>
                                <p class="text-sm font-medium text-foreground">
                                    {{ formatDate(katalog.tanggal) }}
                                </p>
                            </div>
                        </div>

                        <!-- Deskripsi Section -->
                        <div v-if="katalog.koi.deskripsi" class="border-t border-border pt-4">
                            <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-3">
                                Deskripsi Produk
                            </p>
                            <p class="text-sm leading-relaxed text-foreground/80">
                                {{ katalog.koi.deskripsi }}
                            </p>
                        </div>

                        <!-- CTA Button -->
                        <Link
                            v-if="katalog.qty > 0"
                            :href="checkout.url(katalog.id)"
                            class="block w-full bg-primary hover:bg-primary/90 text-primary-foreground font-bold py-3 text-base rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl text-center"
                        >
                            Pesan Koi Ini
                        </Link>
                        <button
                            v-else
                            disabled
                            class="w-full bg-muted text-muted-foreground font-bold py-3 text-base rounded-xl cursor-not-allowed"
                        >
                            Stok Habis
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
