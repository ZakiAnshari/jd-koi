<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Input } from '@/components/ui/input'
import type { BreadcrumbItem } from '@/types'
import { Search, ImageOff, SlidersHorizontal } from 'lucide-vue-next'

type KoiImage = {
    id: number
    url: string
}

type Breeder = {
    name_breeder: string
}

type Koi = {
    id: number
    nama: string
    kode: string
    images: KoiImage[]
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
    sort: string
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Katalog Koi', href: '/customer/katalog' },
]

const searchQuery = ref('')

const sortOptions = [
    { value: 'latest',     label: 'Terbaru' },
    { value: 'oldest',     label: 'Terlama' },
    { value: 'price_high', label: 'Harga Tertinggi' },
    { value: 'price_low',  label: 'Harga Terendah' },
    { value: 'name_asc',   label: 'Nama A-Z' },
    { value: 'name_desc',  label: 'Nama Z-A' },
]

function onSortChange(e: Event) {
    const value = (e.target as HTMLSelectElement).value
    router.get('/customer/katalog', { sort: value }, { preserveState: false, replace: true })
}

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value)
}

const thumbUrl = (koi: Koi) => koi.images?.[0]?.url ?? null

const hasData = computed(() => (props.katalogs?.data?.length ?? 0) > 0)
const currentPage = computed(() => props.katalogs?.current_page ?? 1)
const lastPage = computed(() => props.katalogs?.last_page ?? 1)

const pagination = computed(() => {
    const pages: (number | string)[] = []
    const start = Math.max(1, currentPage.value - 2)
    const end = Math.min(lastPage.value, currentPage.value + 2)
    if (start > 1) pages.push(1)
    if (start > 2) pages.push('...')
    for (let i = start; i <= end; i++) pages.push(i)
    if (end < lastPage.value - 1) pages.push('...')
    if (end < lastPage.value) pages.push(lastPage.value)
    return pages
})

const filteredKatalogs = computed(() => {
    if (!searchQuery.value.trim()) return props.katalogs?.data ?? []
    const q = searchQuery.value.toLowerCase()
    return (props.katalogs?.data ?? []).filter(k =>
        k.koi.nama.toLowerCase().includes(q) ||
        k.koi.kode.toLowerCase().includes(q) ||
        k.koi.breeder.name_breeder.toLowerCase().includes(q)
    )
})

const hasFilteredData = computed(() => filteredKatalogs.value.length > 0)

function pageUrl(page: number) {
    return `/customer/katalog?page=${page}&sort=${props.sort}`
}
</script>

<template>
    <Head title="Katalog Koi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-6">
            <!-- Page heading -->
            <div>
                <h1 class="text-xl font-semibold text-foreground">Katalog Koi</h1>
                <p class="text-sm text-muted-foreground mt-0.5">
                    Temukan koi berkualitas premium dari breeder terpercaya kami
                </p>
            </div>

            <!-- Toolbar: Search + Sort -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Search -->
                <div class="relative flex-1 min-w-[200px] max-w-sm">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
                    <Input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama, kode, atau breeder..."
                        class="pl-9 h-9 text-sm bg-background border-sidebar-border/70"
                    />
                </div>

                <!-- Sort dropdown -->
                <div class="flex items-center gap-2">
                    <SlidersHorizontal class="h-4 w-4 text-muted-foreground flex-shrink-0" />
                    <select
                        :value="sort"
                        @change="onSortChange"
                        class="h-9 rounded-md border border-sidebar-border/70 bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                        <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">
                            {{ opt.label }}
                        </option>
                    </select>
                </div>

                <!-- Count -->
                <span v-if="hasData" class="text-xs text-muted-foreground ml-auto">
                    {{ hasFilteredData ? filteredKatalogs.length : (katalogs?.total ?? 0) }} koi
                </span>
            </div>

            <!-- Grid Katalog -->
            <div v-if="hasFilteredData" class="grid gap-4 grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                <template v-for="katalog in filteredKatalogs" :key="katalog.id">

                    <!-- Card: Stok Tersedia -->
                    <Link
                        v-if="katalog.qty > 0"
                        :href="`/customer/katalog/${katalog.id}`"
                        class="group flex flex-col rounded-xl overflow-hidden border border-sidebar-border/70 dark:border-sidebar-border bg-background hover:border-primary/50 hover:shadow-md transition-all duration-200"
                    >
                        <!-- Thumbnail -->
                        <div class="relative overflow-hidden bg-muted aspect-square">
                            <img
                                v-if="thumbUrl(katalog.koi)"
                                :src="thumbUrl(katalog.koi)!"
                                :alt="katalog.koi.nama"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            />
                            <div v-else class="w-full h-full flex flex-col items-center justify-center gap-1 text-muted-foreground bg-muted/50">
                                <ImageOff class="h-6 w-6 opacity-40" />
                                <span class="text-xs">Belum ada foto</span>
                            </div>
                            <div class="absolute top-2 right-2 bg-primary/90 text-primary-foreground px-2 py-0.5 rounded-full text-[10px] font-semibold">
                                {{ katalog.qty }} ekor
                            </div>
                        </div>
                        <!-- Content -->
                        <div class="p-3 flex flex-col gap-2 flex-1">
                            <div>
                                <p class="font-semibold text-sm text-foreground line-clamp-2 leading-snug">{{ katalog.koi.nama }}</p>
                                <p class="text-xs text-muted-foreground mt-0.5">{{ katalog.koi.breeder.name_breeder }}</p>
                            </div>
                            <div class="h-px bg-border mt-auto" />
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-muted-foreground font-mono">{{ katalog.koi.kode }}</span>
                                <span class="text-sm font-bold text-primary">{{ formatCurrency(katalog.sell_idr).replace('Rp\u00a0', '') }}</span>
                            </div>
                            <div class="w-full text-center bg-primary hover:bg-primary/90 text-primary-foreground text-xs font-semibold py-1.5 rounded-md transition-colors">
                                Lihat Detail
                            </div>
                        </div>
                    </Link>

                    <!-- Card: Stok Habis -->
                    <div
                        v-else
                        class="flex flex-col rounded-xl overflow-hidden border border-sidebar-border/70 dark:border-sidebar-border bg-background opacity-70"
                    >
                        <div class="relative overflow-hidden bg-muted aspect-square">
                            <img
                                v-if="thumbUrl(katalog.koi)"
                                :src="thumbUrl(katalog.koi)!"
                                :alt="katalog.koi.nama"
                                class="w-full h-full object-cover opacity-40"
                            />
                            <div v-else class="w-full h-full flex flex-col items-center justify-center gap-1 text-muted-foreground bg-muted/50 opacity-40">
                                <ImageOff class="h-6 w-6 opacity-40" />
                                <span class="text-xs">Belum ada foto</span>
                            </div>
                            <div class="absolute inset-0 flex items-center justify-center bg-black/35">
                                <span class="text-red-400 font-bold text-sm tracking-wide drop-shadow">Stok Habis</span>
                            </div>
                        </div>
                        <div class="p-3 flex flex-col gap-2 flex-1">
                            <div>
                                <p class="font-semibold text-sm text-foreground line-clamp-2 leading-snug">{{ katalog.koi.nama }}</p>
                                <p class="text-xs text-muted-foreground mt-0.5">{{ katalog.koi.breeder.name_breeder }}</p>
                            </div>
                            <div class="h-px bg-border mt-auto" />
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-muted-foreground font-mono">{{ katalog.koi.kode }}</span>
                                <span class="text-sm font-bold text-muted-foreground">{{ formatCurrency(katalog.sell_idr).replace('Rp\u00a0', '') }}</span>
                            </div>
                            <div class="w-full text-center bg-muted text-muted-foreground text-xs font-semibold py-1.5 rounded-md cursor-not-allowed">
                                Stok Habis
                            </div>
                        </div>
                    </div>

                </template>
            </div>

            <!-- Empty: no search results -->
            <div
                v-else-if="searchQuery"
                class="flex flex-col items-center justify-center rounded-xl border border-dashed border-border bg-muted/30 p-12 text-center"
            >
                <Search class="h-10 w-10 text-muted-foreground/30 mb-3" />
                <p class="text-sm font-semibold text-foreground mb-1">Tidak ada hasil pencarian</p>
                <p class="text-xs text-muted-foreground mb-3">Coba kata kunci yang berbeda</p>
                <button @click="searchQuery = ''" class="text-xs font-medium text-primary hover:text-primary/80">
                    Bersihkan pencarian
                </button>
            </div>

            <!-- Empty: no data -->
            <div
                v-else
                class="flex flex-col items-center justify-center rounded-xl border border-dashed border-border bg-muted/30 p-16 text-center"
            >
                <ImageOff class="h-10 w-10 text-muted-foreground/30 mb-3" />
                <p class="text-sm font-semibold text-foreground mb-1">Katalog Kosong</p>
                <p class="text-xs text-muted-foreground max-w-xs">
                    Koleksi koi kami sedang diperbarui. Silakan kembali lagi nanti.
                </p>
            </div>

            <!-- Pagination -->
            <div v-if="hasData && lastPage > 1 && !searchQuery" class="flex items-center justify-center gap-2 pt-4 border-t border-border">
                <Link
                    v-if="katalogs?.prev_page_url"
                    :href="pageUrl(currentPage - 1)"
                    class="px-3 py-1.5 text-xs font-medium rounded-md border border-sidebar-border/70 text-foreground hover:bg-muted transition-colors"
                >
                    ← Sebelumnya
                </Link>
                <button v-else disabled class="px-3 py-1.5 text-xs font-medium rounded-md border border-sidebar-border/70 text-muted-foreground opacity-40 cursor-not-allowed">
                    ← Sebelumnya
                </button>

                <div class="flex gap-1 items-center">
                    <template v-for="page in pagination" :key="page">
                        <Link
                            v-if="typeof page === 'number' && page !== currentPage"
                            :href="pageUrl(page)"
                            class="px-2.5 py-1.5 text-xs font-medium rounded-md border border-sidebar-border/70 text-foreground hover:bg-muted transition-colors"
                        >
                            {{ page }}
                        </Link>
                        <span
                            v-else-if="typeof page === 'number'"
                            class="px-2.5 py-1.5 text-xs font-medium rounded-md bg-primary text-primary-foreground"
                        >
                            {{ page }}
                        </span>
                        <span v-else class="px-1 text-xs text-muted-foreground">{{ page }}</span>
                    </template>
                </div>

                <Link
                    v-if="katalogs?.next_page_url"
                    :href="pageUrl(currentPage + 1)"
                    class="px-3 py-1.5 text-xs font-medium rounded-md border border-sidebar-border/70 text-foreground hover:bg-muted transition-colors"
                >
                    Selanjutnya →
                </Link>
                <button v-else disabled class="px-3 py-1.5 text-xs font-medium rounded-md border border-sidebar-border/70 text-muted-foreground opacity-40 cursor-not-allowed">
                    Selanjutnya →
                </button>
            </div>
        </div>
    </AppLayout>
</template>
