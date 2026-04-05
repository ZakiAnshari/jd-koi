<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog'

type Koi = {
    id: number
    code: string
    name: string
    photo?: string
}

type Stock = {
    id: number
    koi_id: number
    qty: number
    created_at: string
    koi?: Koi
}

const page = usePage()
const stocks = computed<Stock[]>(() => {
    const prop = page.props.stocks as unknown
    if (Array.isArray(prop)) {
        return prop as Stock[]
    }
    return ((prop as { data?: Stock[] })?.data ?? []) as Stock[]
})

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Manajemen Stok',
        href: '/inventory',
    },
]

const search = ref('')
const sortKey = ref<'code' | 'name' | 'qty' | 'created_at'>('created_at')
const sortOrder = ref<'asc' | 'desc'>('desc')
const currentPage = ref(1)
const perPage = ref(10)
const previewOpen = ref(false)
const previewImage = ref<string | null>(null)
const previewKoiName = ref('')

const filteredStocks = computed(() => {
    let result = stocks.value

    if (search.value) {
        const q = search.value.toLowerCase()
        result = result.filter(
            (s) =>
                s.koi?.code.toLowerCase().includes(q) ||
                s.koi?.name.toLowerCase().includes(q),
        )
    }

    return [...result].sort((a, b) => {
        if (sortKey.value === 'code' || sortKey.value === 'name') {
            const aVal = sortKey.value === 'code' ? a.koi?.code : a.koi?.name
            const bVal = sortKey.value === 'code' ? b.koi?.code : b.koi?.name
            return sortOrder.value === 'asc'
                ? String(aVal ?? '').localeCompare(String(bVal ?? ''))
                : String(bVal ?? '').localeCompare(String(aVal ?? ''))
        }

        const aVal = a[sortKey.value]
        const bVal = b[sortKey.value]
        if (typeof aVal === 'string' && typeof bVal === 'string') {
            return sortOrder.value === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal)
        }
        if (typeof aVal === 'number' && typeof bVal === 'number') {
            return sortOrder.value === 'asc' ? aVal - bVal : bVal - aVal
        }
        return 0
    })
})

const totalPages = computed(() => Math.ceil(filteredStocks.value.length / perPage.value) || 1)
const paginatedStocks = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return filteredStocks.value.slice(start, start + perPage.value)
})
const showingFrom = computed(() =>
    filteredStocks.value.length === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1,
)
const showingTo = computed(() => Math.min(currentPage.value * perPage.value, filteredStocks.value.length))

const dateTimeFormatter = new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
})

function formatDateTime(value: string | Date | null | undefined) {
    if (!value) return '-'
    return dateTimeFormatter.format(new Date(value))
}

function storagePath(photo: string | undefined | null) {
    if (!photo) return ''
    return `/storage/${photo}`
}

function onSearch(value: string | number) {
    search.value = String(value)
    currentPage.value = 1
}

function toggleSort(key: 'code' | 'name' | 'qty' | 'created_at') {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortKey.value = key
        sortOrder.value = 'asc'
    }
}

function sortIcon(key: 'code' | 'name' | 'qty' | 'created_at') {
    if (sortKey.value !== key) return '↕'
    return sortOrder.value === 'asc' ? '↑' : '↓'
}

function openPreview(stock: Stock) {
    if (!stock.koi?.photo) return
    previewImage.value = storagePath(stock.koi.photo)
    previewKoiName.value = stock.koi.name ?? 'Koi'
    previewOpen.value = true
}

function closePreview() {
    previewOpen.value = false
    previewImage.value = null
    previewKoiName.value = ''
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Ringkasan Stok" />

        <div class="flex flex-col gap-4 p-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Ringkasan Stok Koi</h1>
                    <p class="text-sm text-muted-foreground">Tabel ringkasan stok per koi (real-time).</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-background dark:border-sidebar-border">
                <div class="flex flex-col gap-3 border-b border-border px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <Input :model-value="search" @update:model-value="onSearch" placeholder="Cari koi..." class="max-w-sm" />
                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                        <span>Show</span>
                        <select :value="perPage" @change="(e) => { perPage = Number((e.target as HTMLSelectElement).value); currentPage = 1 }" class="rounded-md border border-input bg-background px-3 py-1">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        <span>entries</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Koi</th>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="toggleSort('qty')">Qty <span class="ml-1">{{ sortIcon('qty') }}</span></th>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="toggleSort('created_at')">Updated <span class="ml-1">{{ sortIcon('created_at') }}</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-background">
                            <tr v-if="paginatedStocks.length === 0">
                                <td colspan="3" class="px-4 py-10 text-center text-sm text-muted-foreground">
                                    {{ search ? 'Tidak ada hasil pencarian.' : 'Belum ada data stok.' }}
                                </td>
                            </tr>
                            <tr v-for="stock in paginatedStocks" :key="stock.id" class="hover:bg-muted/70">
                                <td class="px-4 py-3 text-sm text-foreground">
                                    <div class="flex items-center gap-3">
                                        <button
                                            v-if="stock.koi?.photo"
                                            type="button"
                                            class="h-10 w-10 overflow-hidden rounded-full border border-border bg-muted/50 transition hover:scale-105"
                                            @click="openPreview(stock)"
                                        >
                                            <img
                                                :src="storagePath(stock.koi.photo)"
                                                :alt="stock.koi?.name ?? 'Koi'"
                                                class="h-full w-full object-cover"
                                            />
                                        </button>
                                        <div>
                                            <div class="font-semibold">{{ stock.koi?.code ?? '-' }}</div>
                                            <div class="text-sm text-muted-foreground">{{ stock.koi?.name ?? '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-foreground">
                                    <span class="font-semibold">{{ stock.qty || 'Kosong' }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-foreground">{{ formatDateTime(stock.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Dialog v-model:open="previewOpen">
                    <DialogContent class="sm:max-w-[650px]">
                        <DialogHeader>
                            <DialogTitle>{{ previewKoiName || 'Preview Gambar Koi' }}</DialogTitle>
                            <DialogDescription>Perbesar gambar koi dengan klik thumbnail.</DialogDescription>
                        </DialogHeader>
                        <div class="p-4">
                            <div v-if="previewImage" class="mx-auto max-h-[70vh] w-full overflow-hidden rounded-2xl border border-border bg-background p-4">
                                <img :src="previewImage" :alt="previewKoiName" class="mx-auto h-full w-full max-w-full object-contain" />
                            </div>
                            <div v-else class="flex h-48 items-center justify-center rounded-2xl border border-border bg-muted text-muted-foreground">
                                Gambar tidak tersedia.
                            </div>
                        </div>
                    </DialogContent>
                </Dialog>

                <div class="flex flex-col gap-2 border-t border-border px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-muted-foreground">Showing {{ showingFrom }} to {{ showingTo }} of {{ filteredStocks.length }} entries</p>
                    <div class="flex flex-wrap items-center gap-1">
                        <Button variant="outline" size="sm" :disabled="currentPage <= 1" @click="currentPage--">Previous</Button>
                        <template v-for="p in totalPages" :key="p">
                            <Button size="sm" :variant="p === currentPage ? 'default' : 'outline'" @click="currentPage = p">{{ p }}</Button>
                        </template>
                        <Button variant="outline" size="sm" :disabled="currentPage >= totalPages" @click="currentPage++">Next</Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>