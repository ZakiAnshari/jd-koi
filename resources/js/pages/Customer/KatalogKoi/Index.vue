<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import type { BreadcrumbItem } from '@/types'
import { Search } from 'lucide-vue-next'

type Breeder = {
    name_breeder: string
}

type Koi = {
    id: number
    nama: string
    kode: string
    photo?: string
    breeder: Breeder
}

type Katalog = {
    id: number
    tanggal: string
    qty: number
    sell_idr: number
    koi: Koi
}

type PaginatedKatalogs = {
    data: Katalog[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number
    to: number
    prev_page_url: string | null
    next_page_url: string | null
    path: string
}

const props = defineProps<{
    katalogs: PaginatedKatalogs
}>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Katalog Koi',
        href: '/customer/katalog',
    },
]

const searchQuery = ref('')

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

const hasData = computed(() => (props.katalogs?.data?.length ?? 0) > 0)
const currentPage = computed(() => props.katalogs?.current_page ?? 1)
const lastPage = computed(() => props.katalogs?.last_page ?? 1)

const pagination = computed(() => {
    const pages = []
    const start = Math.max(1, currentPage.value - 2)
    const end = Math.min(lastPage.value, currentPage.value + 2)
    
    if (start > 1) pages.push(1)
    if (start > 2) pages.push('...')
    
    for (let i = start; i <= end; i++) {
        pages.push(i)
    }
    
    if (end < lastPage.value - 1) pages.push('...')
    if (end < lastPage.value) pages.push(lastPage.value)
    
    return pages
})

const filteredKatalogs = computed(() => {
    if (!searchQuery.value.trim()) return (props.katalogs?.data ?? [])
    
    const q = searchQuery.value.toLowerCase()
    return (props.katalogs?.data ?? []).filter(k => 
        k.koi.nama.toLowerCase().includes(q) ||
        k.koi.kode.toLowerCase().includes(q) ||
        k.koi.breeder.name_breeder.toLowerCase().includes(q)
    )
})

const hasFilteredData = computed(() => filteredKatalogs.value.length > 0)
</script>

<template>
    <Head title="Katalog Koi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Hero Section -->
            <div class="space-y-2 mb-2">
                <h1 class="text-4xl font-bold text-foreground">Katalog Koi</h1>
                <p class="text-base text-muted-foreground">
                    Temukan koi berkualitas premium dari breeder terpercaya kami
                </p>
            </div>

            <!-- Search Section -->
            <div class="flex items-center gap-3 mb-2">
                <div class="relative flex-1 max-w-md">
                    <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" />
                    <Input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama koi, kode, atau breeder..."
                        class="pl-10 bg-background border-sidebar-border/70 dark:border-sidebar-border"
                    />
                </div>
                <div v-if="hasData" class="text-sm text-muted-foreground font-medium">
                    {{ hasFilteredData ? filteredKatalogs.length : props.katalogs?.total ?? 0 }} koi
                </div>
            </div>

            <!-- Results Info -->
            <div v-if="hasData && searchQuery" class="text-sm text-muted-foreground">
                Menampilkan {{ filteredKatalogs.length }} dari {{ props.katalogs?.total ?? 0 }} koi
            </div>

            <!-- Grid Katalog -->
            <div v-if="hasFilteredData" class="grid gap-5 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                <template v-for="katalog in filteredKatalogs" :key="katalog.id">
                    <!-- Card: Stok Tersedia -->
                    <Link
                        v-if="katalog.qty > 0"
                        :href="`/customer/katalog/${katalog.id}`"
                        class="group flex flex-col h-full rounded-xl overflow-hidden border border-sidebar-border/70 dark:border-sidebar-border bg-background hover:border-primary/50 hover:shadow-lg transition-all duration-300"
                    >
                        <!-- Foto Container -->
                        <div class="relative overflow-hidden bg-muted aspect-square">
                            <img
                                v-if="katalog.koi.photo"
                                :src="storagePath(katalog.koi.photo)"
                                :alt="katalog.koi.nama"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-muted-foreground bg-muted/50">
                                <span class="text-sm font-medium">Foto Tidak Tersedia</span>
                            </div>
                            <div class="absolute top-3 right-3 bg-primary/90 backdrop-blur-sm text-primary-foreground px-3 py-1 rounded-full text-xs font-semibold shadow-lg">
                                {{ katalog.qty }} ekor
                            </div>
                        </div>
                        <!-- Content -->
                        <div class="p-4 space-y-3 flex flex-col flex-1">
                            <div class="flex-1">
                                <h3 class="font-bold text-foreground line-clamp-2 text-sm md:text-base leading-tight mb-1">{{ katalog.koi.nama }}</h3>
                                <p class="text-xs text-muted-foreground font-medium">{{ katalog.koi.breeder.name_breeder }}</p>
                            </div>
                            <div class="h-px bg-border" />
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-muted-foreground">Kode:</span>
                                    <span class="font-mono font-semibold text-foreground text-xs">{{ katalog.koi.kode }}</span>
                                </div>
                                <div class="flex items-baseline justify-between">
                                    <span class="text-xs text-muted-foreground">Harga</span>
                                    <span class="text-lg font-bold text-primary">{{ formatCurrency(katalog.sell_idr).replace('Rp ', '') }}</span>
                                </div>
                            </div>
                            <Button class="w-full mt-2 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold h-9 text-sm">
                                Lihat Detail
                            </Button>
                        </div>
                    </Link>

                    <!-- Card: Stok Habis -->
                    <div
                        v-else
                        class="flex flex-col h-full rounded-xl overflow-hidden border border-sidebar-border/70 dark:border-sidebar-border bg-background cursor-not-allowed"
                    >
                        <!-- Foto Container with overlay -->
                        <div class="relative overflow-hidden bg-muted aspect-square">
                            <img
                                v-if="katalog.koi.photo"
                                :src="storagePath(katalog.koi.photo)"
                                :alt="katalog.koi.nama"
                                class="w-full h-full object-cover opacity-40"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-muted-foreground bg-muted/50 opacity-40">
                                <span class="text-sm font-medium">Foto Tidak Tersedia</span>
                            </div>
                            <!-- Stok Habis overlay -->
                            <div class="absolute inset-0 flex items-center justify-center bg-black/40">
                                <span class="text-red-400 font-bold text-xl tracking-wide drop-shadow-lg">Stok Habis</span>
                            </div>
                        </div>
                        <!-- Content -->
                        <div class="p-4 space-y-3 flex flex-col flex-1 opacity-60">
                            <div class="flex-1">
                                <h3 class="font-bold text-foreground line-clamp-2 text-sm md:text-base leading-tight mb-1">{{ katalog.koi.nama }}</h3>
                                <p class="text-xs text-muted-foreground font-medium">{{ katalog.koi.breeder.name_breeder }}</p>
                            </div>
                            <div class="h-px bg-border" />
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-muted-foreground">Kode:</span>
                                    <span class="font-mono font-semibold text-foreground text-xs">{{ katalog.koi.kode }}</span>
                                </div>
                                <div class="flex items-baseline justify-between">
                                    <span class="text-xs text-muted-foreground">Harga</span>
                                    <span class="text-lg font-bold text-muted-foreground">{{ formatCurrency(katalog.sell_idr).replace('Rp ', '') }}</span>
                                </div>
                            </div>
                            <button disabled class="w-full mt-2 bg-muted text-muted-foreground font-semibold h-9 text-sm rounded-md cursor-not-allowed">
                                Stok Habis
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Empty State - No Search Results -->
            <div
                v-else-if="searchQuery"
                class="flex flex-col items-center justify-center rounded-xl border border-dashed border-border bg-muted/30 p-12 text-center"
            >
                <Search class="w-12 h-12 text-muted-foreground/30 mb-4" />
                <p class="text-lg font-semibold text-foreground mb-2">
                    Tidak ada hasil pencarian
                </p>
                <p class="text-sm text-muted-foreground mb-4">
                    Coba cari dengan kata kunci yang berbeda
                </p>
                <button 
                    @click="searchQuery = ''"
                    class="text-sm font-medium text-primary hover:text-primary/80 transition"
                >
                    Bersihkan pencarian
                </button>
            </div>

            <!-- Empty State - No Data -->
            <div
                v-else
                class="flex flex-col items-center justify-center rounded-xl border border-dashed border-border bg-muted/30 p-16 text-center"
            >
                <div class="w-16 h-16 rounded-full bg-muted flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-muted-foreground/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                </div>
                <p class="text-lg font-semibold text-foreground mb-2">
                    Katalog Kosong
                </p>
                <p class="text-sm text-muted-foreground max-w-sm">
                    Koleksi koi kami sedang diperbarui. Silakan kembali lagi nanti untuk melihat koi-koi berkualitas terbaru dari kami.
                </p>
            </div>

            <!-- Pagination -->
            <div v-if="hasData && lastPage > 1 && !searchQuery" class="flex items-center justify-center gap-2 mt-8 pt-4 border-t border-border">
                <Link
                    v-if="props.katalogs?.prev_page_url"
                    :href="props.katalogs.prev_page_url"
                    class="px-3 py-2 text-sm font-medium rounded-lg border border-sidebar-border/70 dark:border-sidebar-border text-foreground hover:bg-muted transition-colors"
                >
                    ← Sebelumnya
                </Link>
                <button
                    v-else
                    disabled
                    class="px-3 py-2 text-sm font-medium rounded-lg border border-sidebar-border/70 dark:border-sidebar-border text-muted-foreground opacity-50 cursor-not-allowed"
                >
                    ← Sebelumnya
                </button>

                <div class="flex gap-1 items-center">
                    <template v-for="page in pagination" :key="page">
                        <Link
                            v-if="typeof page === 'number' && page !== currentPage"
                            :href="`/customer/katalog?page=${page}`"
                            class="px-2.5 py-2 text-sm font-medium rounded-lg border border-sidebar-border/70 dark:border-sidebar-border text-foreground hover:bg-muted transition-colors"
                        >
                            {{ page }}
                        </Link>
                        <button
                            v-else-if="typeof page === 'number'"
                            class="px-2.5 py-2 text-sm font-medium rounded-lg bg-primary text-primary-foreground"
                        >
                            {{ page }}
                        </button>
                        <span v-else class="px-1 py-2 text-muted-foreground">{{ page }}</span>
                    </template>
                </div>

                <Link
                    v-if="props.katalogs?.next_page_url"
                    :href="props.katalogs.next_page_url"
                    class="px-3 py-2 text-sm font-medium rounded-lg border border-sidebar-border/70 dark:border-sidebar-border text-foreground hover:bg-muted transition-colors"
                >
                    Selanjutnya →
                </Link>
                <button
                    v-else
                    disabled
                    class="px-3 py-2 text-sm font-medium rounded-lg border border-sidebar-border/70 dark:border-sidebar-border text-muted-foreground opacity-50 cursor-not-allowed"
                >
                    Selanjutnya →
                </button>
            </div>
        </div>
    </AppLayout>
</template>
