<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Eye, RotateCcw, Search } from 'lucide-vue-next'

type Breeder  = { id: number; name_breeder: string }
type Customer = { id: number; name: string }
type JenisKoi = { id: number; nama_jenis: string }

type Koi = {
    id: number
    code: string
    name: string
    age: number | null
    size: number | null
    box_qty: number | null
    weight: number | null
    total_weight: number | null
    breeder?: Breeder
    jenis_koi?: JenisKoi
}

type KatalogKoi = {
    id: number
    sell_jpy: number
    sell_idr: number
    koi?: Koi
}

type Pesanan = {
    id: number
    kode_pesanan: string
    qty: number
    harga_satuan: number
    total_harga: number
    status: string
    catatan?: string
    created_at: string
    customer?: Customer
    katalog_koi?: KatalogKoi
}

type PaginatedPesanan = {
    data: Pesanan[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
    links: { url: string | null; label: string; active: boolean }[]
}

const props = defineProps<{
    pesanans: PaginatedPesanan
    breeders: Breeder[]
    customers: Customer[]
    filters: {
        status?: string
        invoice?: string
        code?: string
        date_from?: string
        date_to?: string
        breeder_id?: string
        customer_id?: string
    }
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Rekap Transaksi', href: '/admin/rekap-transaksi' },
]

// ─── Filter state ─────────────────────────────────────────
const filterStatus     = ref(props.filters.status ?? '')
const filterInvoice    = ref(props.filters.invoice ?? '')
const filterCode       = ref(props.filters.code ?? '')
const filterDateFrom   = ref(props.filters.date_from ?? '')
const filterDateTo     = ref(props.filters.date_to ?? '')
const filterBreederId  = ref(props.filters.breeder_id ?? '')
const filterCustomerId = ref(props.filters.customer_id ?? '')

function applyFilter() {
    router.get('/admin/rekap-transaksi', {
        status:      filterStatus.value || undefined,
        invoice:     filterInvoice.value || undefined,
        code:        filterCode.value || undefined,
        date_from:   filterDateFrom.value || undefined,
        date_to:     filterDateTo.value || undefined,
        breeder_id:  filterBreederId.value || undefined,
        customer_id: filterCustomerId.value || undefined,
    }, { preserveScroll: true })
}

function resetFilter() {
    filterStatus.value     = ''
    filterInvoice.value    = ''
    filterCode.value       = ''
    filterDateFrom.value   = ''
    filterDateTo.value     = ''
    filterBreederId.value  = ''
    filterCustomerId.value = ''
    router.get('/admin/rekap-transaksi', {}, { preserveScroll: true })
}

// ─── Helpers ──────────────────────────────────────────────
const statusMap: Record<string, { label: string; cls: string }> = {
    pending:   { label: 'On Hold',       cls: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' },
    shipped: { label: 'Shipped', cls: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' },
    azukari: { label: 'Azukari', cls: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' },
    dead:    { label: 'Dead',    cls: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' },
}

function statusLabel(s: string) { return statusMap[s]?.label ?? s }
function statusCls(s: string)   { return statusMap[s]?.cls ?? '' }

function fmtDate(d: string) {
    return new Date(d).toLocaleDateString('id-ID', { year: 'numeric', month: '2-digit', day: '2-digit' })
}

function fmtCurrency(n: number | null | undefined, prefix = '¥') {
    if (n == null) return '-'
    return prefix + Number(n).toLocaleString('id-ID')
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Rekap Transaksi" />

        <div class="flex flex-col gap-4 p-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-semibold">Rekap Transaksi</h1>
                <p class="text-sm text-muted-foreground">Ringkasan seluruh data transaksi pesanan.</p>
            </div>

            <!-- Filter Panel -->
            <div class="rounded-xl border border-sidebar-border/70 bg-background p-4 dark:border-sidebar-border">
                <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-muted-foreground">
                    <Search class="h-4 w-4" />
                    Search Filters
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Status -->
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-muted-foreground">Status</label>
                        <select
                            v-model="filterStatus"
                            class="rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option value="">Semua Status</option>
                            <option value="pending">On Hold</option>
                            <option value="shipped">Shipped</option>
                            <option value="azukari">Azukari</option>
                            <option value="dead">Dead</option>
                        </select>
                    </div>

                    <!-- Invoice -->
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-muted-foreground">Invoice</label>
                        <Input v-model="filterInvoice" placeholder="Cari kode pesanan..." @keyup.enter="applyFilter" />
                    </div>

                    <!-- Code -->
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-muted-foreground">KOI CODE</label>
                        <Input v-model="filterCode" placeholder="Cari kode koi..." @keyup.enter="applyFilter" />
                    </div>

                    <!-- Sales Date range -->
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-muted-foreground">Sales Date</label>
                        <div class="flex items-center gap-1">
                            <Input v-model="filterDateFrom" type="date" class="text-xs" />
                            <span class="text-muted-foreground">~</span>
                            <Input v-model="filterDateTo" type="date" class="text-xs" />
                        </div>
                    </div>

                    <!-- Breeder -->
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-muted-foreground">Breeder</label>
                        <select
                            v-model="filterBreederId"
                            class="rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option value="">Semua Breeder</option>
                            <option v-for="b in breeders" :key="b.id" :value="String(b.id)">{{ b.name_breeder }}</option>
                        </select>
                    </div>

                    <!-- Customer -->
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-muted-foreground">Customer</label>
                        <select
                            v-model="filterCustomerId"
                            class="rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option value="">Semua Customer</option>
                            <option v-for="c in customers" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-2">
                    <Button variant="outline" @click="resetFilter" class="gap-1">
                        <RotateCcw class="h-4 w-4" /> Reset
                    </Button>
                    <Button @click="applyFilter" class="gap-1">
                        <Search class="h-4 w-4" /> Filter
                    </Button>
                </div>
            </div>

            <!-- Results info -->
            <div class="flex items-center justify-between text-sm text-muted-foreground">
                <span>
                    Results: <strong class="text-xl font-bold text-foreground">{{ pesanans.total }}</strong>
                </span>
                <span v-if="pesanans.from != null">
                    Showing {{ pesanans.from }}–{{ pesanans.to }} of {{ pesanans.total }}
                </span>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border text-xs">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-3 py-3 text-left font-semibold uppercase tracking-wide text-muted-foreground">Invoice</th>
                                <th class="px-3 py-3 text-left font-semibold uppercase tracking-wide text-muted-foreground">KOI Code</th>
                                <th class="px-3 py-3 text-left font-semibold uppercase tracking-wide text-muted-foreground">Sales Date</th>
                                <th class="px-3 py-3 text-left font-semibold uppercase tracking-wide text-muted-foreground">Status</th>
                                <th class="px-3 py-3 text-left font-semibold uppercase tracking-wide text-muted-foreground">Customer</th>
                                <th class="px-3 py-3 text-left font-semibold uppercase tracking-wide text-muted-foreground">Breeder</th>
                                <th class="px-3 py-3 text-left font-semibold uppercase tracking-wide text-muted-foreground">Jenis</th>
                                <th class="px-3 py-3 text-center font-semibold uppercase tracking-wide text-muted-foreground">QTY</th>
                                <th class="px-3 py-3 text-right font-semibold uppercase tracking-wide text-muted-foreground">Unit Price</th>
                                <th class="px-3 py-3 text-right font-semibold uppercase tracking-wide text-muted-foreground">Total Price</th>
                                <th class="px-3 py-3 text-center font-semibold uppercase tracking-wide text-muted-foreground">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-background">
                            <tr v-if="pesanans.data.length === 0">
                                <td colspan="11" class="px-4 py-10 text-center text-sm text-muted-foreground">
                                    Tidak ada data transaksi.
                                </td>
                            </tr>
                            <tr
                                v-for="p in pesanans.data"
                                :key="p.id"
                                class="transition-colors hover:bg-muted/50"
                            >
                                <td class="px-3 py-2 font-mono text-foreground">{{ p.kode_pesanan }}</td>
                                <td class="px-3 py-2 font-medium text-foreground">{{ p.katalog_koi?.koi?.code ?? '-' }}</td>
                                <td class="px-3 py-2 text-foreground">{{ fmtDate(p.created_at) }}</td>
                                <td class="px-3 py-2">
                                    <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', statusCls(p.status)]">
                                        {{ statusLabel(p.status) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-foreground">{{ p.customer?.name ?? '-' }}</td>
                                <td class="px-3 py-2 text-foreground">{{ p.katalog_koi?.koi?.breeder?.name_breeder ?? '-' }}</td>
                                <td class="px-3 py-2 text-foreground">{{ p.katalog_koi?.koi?.jenis_koi?.nama_jenis ?? '-' }}</td>
                                <td class="px-3 py-2 text-center text-foreground">{{ p.qty }}</td>
                                <td class="px-3 py-2 text-right text-foreground">{{ fmtCurrency(p.harga_satuan) }}</td>
                                <td class="px-3 py-2 text-right text-foreground">{{ fmtCurrency(p.total_harga) }}</td>
                                <td class="px-3 py-2 text-center">
                                    <Link
                                        :href="`/admin/rekap-transaksi/${p.id}`"
                                        class="inline-flex items-center justify-center rounded-md hover:bg-muted p-1"
                                        title="Lihat Detail"
                                    >
                                        <Eye class="h-4 w-4 text-primary" />
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex flex-wrap items-center justify-between gap-2 border-t border-border px-4 py-3">
                    <p class="text-sm text-muted-foreground">
                        Halaman {{ pesanans.current_page }} dari {{ pesanans.last_page }}
                    </p>
                    <div class="flex flex-wrap items-center gap-1">
                        <template v-for="link in pesanans.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'inline-flex h-8 min-w-[2rem] items-center justify-center rounded-md border px-2 text-xs font-medium transition-colors',
                                    link.active
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'border-input bg-background text-foreground hover:bg-muted',
                                ]"
                                v-html="link.label"
                                preserve-scroll
                            />
                            <span
                                v-else
                                :class="[
                                    'inline-flex h-8 min-w-[2rem] items-center justify-center rounded-md border px-2 text-xs font-medium',
                                    'border-input bg-background text-muted-foreground opacity-50',
                                ]"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
