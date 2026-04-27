<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Search, RotateCcw, Eye } from 'lucide-vue-next'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'

type Breeder  = { id: number; name_breeder: string }
type Customer = { id: number; name: string }
type KoiRow = {
    id: number
    code: string
    sale_invoice: string | null
    sale_tanggal: string | null
    sale_shipped_date: string | null
    sale_status: string | null
    sale_qty: number | null
    sell_jpy: string | null
    sell_idr: string | null
    box_qty: number | null
    weight: string | null
    total_weight: string | null
    age: number | null
    size: string | null
    breeder: Breeder | null
    jenis_koi: { id: number; nama_jenis: string } | null
    customer: Customer | null
}

type Paginator = {
    data: KoiRow[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
    links: { url: string | null; label: string; active: boolean }[]
}

const page      = usePage()
const kois      = computed(() => page.props.kois as Paginator)
const breeders  = computed(() => (page.props.breeders  as Breeder[])  || [])
const customers = computed(() => (page.props.customers as Customer[]) || [])
const isAdmin   = computed(() => page.props.isAdmin as boolean)
const filters   = page.props.filters as Record<string, string>

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Rekap Transaksi', href: '/rekap-transaksi' },
]

const fStatus   = ref(filters.status      ?? '')
const fCode     = ref(filters.code        ?? '')
const fInvoice  = ref(filters.invoice     ?? '')
const fDateFrom = ref(filters.date_from   ?? '')
const fDateTo   = ref(filters.date_to     ?? '')
const fBreeder  = ref(filters.breeder_id  ?? '')
const fCustomer = ref(filters.customer_id ?? '')
const fPerPage  = ref(filters.per_page    ?? '50')

function applyFilter() {
    router.get('/rekap-transaksi', {
        status:      fStatus.value    || undefined,
        code:        fCode.value      || undefined,
        invoice:     fInvoice.value   || undefined,
        date_from:   fDateFrom.value  || undefined,
        date_to:     fDateTo.value    || undefined,
        breeder_id:  fBreeder.value   || undefined,
        customer_id: fCustomer.value  || undefined,
        per_page:    fPerPage.value   || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilter() {
    fStatus.value = ''; fCode.value = ''; fInvoice.value = ''
    fDateFrom.value = ''; fDateTo.value = ''
    fBreeder.value = ''; fCustomer.value = ''
    fPerPage.value = '50'
    router.get('/rekap-transaksi', {}, { preserveState: false, replace: true })
}

function goTo(url: string | null) { if (url) router.visit(url) }

function changePerPage(val: string) { fPerPage.value = val; applyFilter() }

const STATUS_LABELS: Record<string, string> = {
    pending: 'Pending', shipped: 'Shipped', azukari: 'Azukari', dead: 'Dead',
    cancel: 'Cancel', refund: 'Refund', dead_refund: 'Dead Refund', cancel_refund: 'Cancel Refund',
}
const STATUS_COLORS: Record<string, string> = {
    pending:       'bg-yellow-100 text-yellow-800',
    shipped:       'bg-blue-100 text-blue-800',
    azukari:       'bg-green-100 text-green-800',
    dead:          'bg-red-100 text-red-800',
    cancel:        'bg-gray-100 text-gray-700',
    refund:        'bg-orange-100 text-orange-800',
    dead_refund:   'bg-red-200 text-red-900',
    cancel_refund: 'bg-purple-100 text-purple-800',
}

const fmtJpy = (v: string | number | null) =>
    v != null && v !== '' ? '¥' + Number(v).toLocaleString('ja-JP') : '-'

const fmtDate = (d: string | null) =>
    d ? new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' }) : '-'

const pageLinks = computed(() => kois.value.links.slice(1, kois.value.links.length - 1))
</script>

<template>
    <Head title="Rekap Transaksi" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4">

            <!-- ── Filters (no card) ── -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-2">
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold text-muted-foreground">Status:</label>
                    <select v-model="fStatus"
                        class="w-full rounded-md border border-input bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                        <option value="" disabled>-- Pilih Status --</option>
                        <option value="">Semua</option>
                        <option v-for="(label, val) in STATUS_LABELS" :key="val" :value="val">{{ label }}</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold text-muted-foreground">CODE:</label>
                    <Input v-model="fCode" class="h-8 text-sm" @keyup.enter="applyFilter" />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold text-muted-foreground">Invoice:</label>
                    <Input v-model="fInvoice" class="h-8 text-sm" @keyup.enter="applyFilter" />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold text-muted-foreground">Sales Date:</label>
                    <div class="flex items-center gap-1">
                        <Input v-model="fDateFrom" type="date" class="h-8 text-sm flex-1" />
                        <span class="text-muted-foreground text-xs">~</span>
                        <Input v-model="fDateTo" type="date" class="h-8 text-sm flex-1" />
                    </div>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold text-muted-foreground">Bleeder:</label>
                    <select v-model="fBreeder"
                        class="w-full rounded-md border border-input bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                        <option value="" disabled>-- Pilih Bleeder --</option>
                        <option value="">Semua</option>
                        <option v-for="b in breeders" :key="b.id" :value="String(b.id)">{{ b.name_breeder }}</option>
                    </select>
                </div>

                <div v-if="isAdmin" class="flex flex-col gap-1">
                    <label class="text-xs font-semibold text-muted-foreground">Customer:</label>
                    <select v-model="fCustomer"
                        class="w-full rounded-md border border-input bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                        <option value="" disabled>-- Pilih Customer --</option>
                        <option value="">Semua</option>
                        <option v-for="c in customers" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                    </select>
                </div>

                <!-- Tombol di baris yang sama, rata kiri -->
                <div class="flex items-end gap-2 col-span-2 md:col-span-1">
                    <button @click="resetFilter"
                        class="inline-flex items-center gap-1.5 rounded-md border border-input bg-background px-3 py-1.5 text-sm font-medium hover:bg-muted transition">
                        <RotateCcw class="w-3.5 h-3.5" /> Reset
                    </button>
                    <button @click="applyFilter"
                        class="inline-flex items-center gap-1.5 rounded-md bg-green-700 hover:bg-green-800 text-white px-4 py-1.5 text-sm font-medium transition">
                        <Search class="w-3.5 h-3.5" /> Filter
                    </button>
                </div>
            </div>

            <!-- ── Results bar ── -->
            <div class="flex flex-wrap items-center gap-3">
                <p class="text-xs text-muted-foreground">
                    Results: <span class="font-semibold text-foreground">{{ kois.total }}</span>
                </p>
                <div class="flex items-center gap-1.5">
                    <span class="text-xs text-muted-foreground">Show</span>
                    <select :value="fPerPage" @change="changePerPage(($event.target as HTMLSelectElement).value)"
                        class="rounded border border-input bg-background px-2 py-1 text-xs focus:outline-none">
                        <option value="" disabled>Pilih</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span class="text-xs text-muted-foreground">entries</span>
                </div>
            </div>

            <!-- ── Table ── -->
            <div class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Invoice</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">KUJ CODE</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Sales Date</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground">Status</th>
                                <th v-if="isAdmin" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Customer</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Bleeder</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground">Size</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground">Koi QTY</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Unit Price</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Sales Price</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-background">
                            <tr v-if="kois.data.length === 0">
                                <td :colspan="isAdmin ? 11 : 10" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                    Tidak ada data transaksi.
                                </td>
                            </tr>
                            <tr v-for="koi in kois.data" :key="koi.id" class="transition-colors hover:bg-muted/50">
                                <td class="px-4 py-3 text-sm font-mono text-muted-foreground whitespace-nowrap">{{ koi.sale_invoice ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm font-medium whitespace-nowrap">{{ koi.code }}</td>
                                <td class="px-4 py-3 text-sm whitespace-nowrap">{{ fmtDate(koi.sale_tanggal) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span v-if="koi.sale_status"
                                        :class="`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap ${STATUS_COLORS[koi.sale_status] ?? 'bg-gray-100 text-gray-700'}`">
                                        {{ STATUS_LABELS[koi.sale_status] ?? koi.sale_status }}
                                    </span>
                                    <span v-else class="text-xs text-muted-foreground">-</span>
                                </td>
                                <td v-if="isAdmin" class="px-4 py-3 text-sm whitespace-nowrap">{{ koi.customer?.name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm whitespace-nowrap">{{ koi.breeder?.name_breeder ?? '-' }}</td>
                                <td class="px-4 py-3 text-center text-sm">{{ koi.size != null ? Number(koi.size).toFixed(0) + ' cm' : '-' }}</td>
                                <td class="px-4 py-3 text-center text-sm">{{ koi.sale_qty ?? '-' }}</td>
                                <td class="px-4 py-3 text-right text-sm whitespace-nowrap">{{ fmtJpy(koi.sell_jpy) }}</td>
                                <td class="px-4 py-3 text-right text-sm whitespace-nowrap">{{ fmtJpy(koi.sell_idr) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a :href="`/rekap-transaksi/${koi.id}`"
                                        class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background p-2 text-foreground shadow-sm hover:bg-muted"
                                        title="Lihat Detail">
                                        <Eye :size="16" />
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ── Pagination bottom ── -->
                <div class="flex flex-col items-center justify-between gap-2 border-t border-border px-4 py-3 sm:flex-row">
                    <p class="text-sm text-muted-foreground">
                        Showing {{ kois.from ?? 0 }} to {{ kois.to ?? 0 }} of {{ kois.total }} entries
                    </p>
                    <div class="flex items-center gap-1">
                        <Button variant="outline" size="sm" :disabled="!kois.links[1]?.url" @click="goTo(kois.links[1]?.url ?? null)">Previous</Button>
                        <template v-for="link in pageLinks" :key="link.label">
                            <Button size="sm" :variant="link.active ? 'default' : 'outline'" :disabled="!link.url" @click="goTo(link.url)" v-html="link.label" />
                        </template>
                        <Button variant="outline" size="sm" :disabled="!kois.links[kois.links.length - 2]?.url" @click="goTo(kois.links[kois.links.length - 2]?.url ?? null)">Next</Button>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
