<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { X, ImagePlus, Eye, Pencil, Trash2, Link2, Plus } from 'lucide-vue-next'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { useSwal } from '@/composables/useSwal'
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'

// ─── Types ────────────────────────────────────────────────
type Breeder  = { id: number; name_breeder: string }
type JenisKoi = { id: number; nama_jenis: string }
type Koi = {
    id: number; breeder_id: number; jenis_koi_id: number | null
    code: string; name: string; age: number | null; size: number | null
    box_qty: number | null; weight: number | null; total_weight: number | null
    breeder?: Breeder; jenis_koi?: JenisKoi; created_at: string
}
type PembelianItem = {
    id: number; koi_id: number; date: string; qty: number
    price_jpy: number; price_idr: number; status: string; created_at: string
    koi?: { id: number; code: string; name: string }
}
type KatalogItem = {
    id: number; koi_id: number; pembelian_id: number; tanggal: string
    qty: number; sell_jpy: number; kurs: number; sell_idr: number
    margin: number; fee: number; profit: number; created_at: string
    koi?: { id: number; code: string; name: string }
}

// ─── Page props ───────────────────────────────────────────
const page       = usePage()
const kois       = computed(() => (page.props.kois       as Koi[]          | null) ?? [])
const breeders   = computed(() => (page.props.breeders   as Breeder[]      | null) ?? [])
const jenisKois  = computed(() => (page.props.jenisKois  as JenisKoi[]     | null) ?? [])
const pembelians = computed(() => (page.props.pembelians as PembelianItem[] | null) ?? [])
const katalogs   = computed(() => (page.props.katalogs   as KatalogItem[]  | null) ?? [])

// ─── Tab state (synced with server prop after redirect) ──
const activeTab = ref<'koi' | 'pembelian' | 'katalog'>((page.props.activeTab as any) ?? 'koi')
watch(() => page.props.activeTab, (v) => { if (v) activeTab.value = v as any })

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data', href: '#' },
    { title: 'Data Koi', href: '/master-data/koi' },
]

// ══════════════════════════════════════════════════════════
//  TAB 1 — DataTable: Koi
// ══════════════════════════════════════════════════════════
const kSearch    = ref('')
const kSortKey   = ref<keyof Koi>('created_at')
const kSortOrder = ref<'asc' | 'desc'>('desc')
const kPage      = ref(1)
const kPerPage   = ref(10)

const filteredKois = computed(() => {
    let r = kois.value
    if (kSearch.value) {
        const q = kSearch.value.toLowerCase()
        r = r.filter(k =>
            k.code.toLowerCase().includes(q) ||
            k.name.toLowerCase().includes(q) ||
            (k.breeder?.name_breeder ?? '').toLowerCase().includes(q),
        )
    }
    return [...r].sort((a, b) => {
        const av = a[kSortKey.value] ?? ''; const bv = b[kSortKey.value] ?? ''
        if (typeof av === 'string' && typeof bv === 'string')
            return kSortOrder.value === 'asc' ? av.localeCompare(bv) : bv.localeCompare(av)
        if (typeof av === 'number' && typeof bv === 'number')
            return kSortOrder.value === 'asc' ? av - bv : bv - av
        return 0
    })
})
const kTotalPages   = computed(() => Math.ceil(filteredKois.value.length / kPerPage.value) || 1)
const paginatedKois = computed(() => filteredKois.value.slice((kPage.value - 1) * kPerPage.value, kPage.value * kPerPage.value))
const kFrom = computed(() => filteredKois.value.length === 0 ? 0 : (kPage.value - 1) * kPerPage.value + 1)
const kTo   = computed(() => Math.min(kPage.value * kPerPage.value, filteredKois.value.length))

function kToggleSort(key: keyof Koi) {
    if (kSortKey.value === key) kSortOrder.value = kSortOrder.value === 'asc' ? 'desc' : 'asc'
    else { kSortKey.value = key; kSortOrder.value = 'asc' }
}
function kSortIcon(key: keyof Koi) { return kSortKey.value !== key ? '↕' : kSortOrder.value === 'asc' ? '↑' : '↓' }

function getBreederName(koi: Koi) { return koi.breeder?.name_breeder ?? breeders.value.find(b => b.id === koi.breeder_id)?.name_breeder ?? '-' }
function getJenisName(koi: Koi) {
    if (koi.jenis_koi?.nama_jenis) return koi.jenis_koi.nama_jenis
    if (!koi.jenis_koi_id) return '-'
    return jenisKois.value.find(j => j.id === koi.jenis_koi_id)?.nama_jenis ?? '-'
}

// ══════════════════════════════════════════════════════════
//  TAB 2 — DataTable: Pembelian
// ══════════════════════════════════════════════════════════
const pSearch    = ref('')
const pSortKey   = ref('date')
const pSortOrder = ref<'asc' | 'desc'>('desc')
const pPage      = ref(1)
const pPerPage   = ref(10)

const filteredPembelians = computed(() => {
    let r = pembelians.value
    if (pSearch.value) {
        const q = pSearch.value.toLowerCase()
        r = r.filter(p =>
            (p.koi?.code ?? '').toLowerCase().includes(q) ||
            (p.koi?.name ?? '').toLowerCase().includes(q) ||
            p.status.toLowerCase().includes(q),
        )
    }
    return [...r].sort((a, b) => {
        const av = (a as any)[pSortKey.value] ?? ''
        const bv = (b as any)[pSortKey.value] ?? ''
        if (typeof av === 'string' && typeof bv === 'string')
            return pSortOrder.value === 'asc' ? av.localeCompare(bv) : bv.localeCompare(av)
        if (typeof av === 'number' && typeof bv === 'number')
            return pSortOrder.value === 'asc' ? av - bv : bv - av
        return 0
    })
})
const pTotalPages        = computed(() => Math.ceil(filteredPembelians.value.length / pPerPage.value) || 1)
const paginatedPembelians = computed(() => filteredPembelians.value.slice((pPage.value - 1) * pPerPage.value, pPage.value * pPerPage.value))
const pFrom = computed(() => filteredPembelians.value.length === 0 ? 0 : (pPage.value - 1) * pPerPage.value + 1)
const pTo   = computed(() => Math.min(pPage.value * pPerPage.value, filteredPembelians.value.length))

function pToggleSort(key: string) {
    if (pSortKey.value === key) pSortOrder.value = pSortOrder.value === 'asc' ? 'desc' : 'asc'
    else { pSortKey.value = key; pSortOrder.value = 'asc' }
}
function pSortIcon(key: string) { return pSortKey.value !== key ? '↕' : pSortOrder.value === 'asc' ? '↑' : '↓' }

// ══════════════════════════════════════════════════════════
//  TAB 3 — DataTable: Katalog Koi
// ══════════════════════════════════════════════════════════
const katSearch    = ref('')
const katSortKey   = ref('tanggal')
const katSortOrder = ref<'asc' | 'desc'>('desc')
const katPage      = ref(1)
const katPerPage   = ref(10)

const filteredKatalogs = computed(() => {
    let r = katalogs.value
    if (katSearch.value) {
        const q = katSearch.value.toLowerCase()
        r = r.filter(k =>
            (k.koi?.code ?? '').toLowerCase().includes(q) ||
            (k.koi?.name ?? '').toLowerCase().includes(q),
        )
    }
    return [...r].sort((a, b) => {
        const av = (a as any)[katSortKey.value] ?? ''
        const bv = (b as any)[katSortKey.value] ?? ''
        if (typeof av === 'string' && typeof bv === 'string')
            return katSortOrder.value === 'asc' ? av.localeCompare(bv) : bv.localeCompare(av)
        if (typeof av === 'number' && typeof bv === 'number')
            return katSortOrder.value === 'asc' ? av - bv : bv - av
        return 0
    })
})
const katTotalPages      = computed(() => Math.ceil(filteredKatalogs.value.length / katPerPage.value) || 1)
const paginatedKatalogs  = computed(() => filteredKatalogs.value.slice((katPage.value - 1) * katPerPage.value, katPage.value * katPerPage.value))
const katFrom = computed(() => filteredKatalogs.value.length === 0 ? 0 : (katPage.value - 1) * katPerPage.value + 1)
const katTo   = computed(() => Math.min(katPage.value * katPerPage.value, filteredKatalogs.value.length))

function katToggleSort(key: string) {
    if (katSortKey.value === key) katSortOrder.value = katSortOrder.value === 'asc' ? 'desc' : 'asc'
    else { katSortKey.value = key; katSortOrder.value = 'asc' }
}
function katSortIcon(key: string) { return katSortKey.value !== key ? '↕' : katSortOrder.value === 'asc' ? '↑' : '↓' }

// ══════════════════════════════════════════════════════════
//  Modal — shared state
// ══════════════════════════════════════════════════════════
const showModal = ref(false)
const koiMode       = ref<'existing' | 'create'>('create')
const pembelianMode = ref<'existing' | 'create'>('existing')
const katalogMode   = ref<'existing' | 'create'>('create')

const breederSearch = ref('')
const jenisSearch   = ref('')
const koiSearch     = ref('')
const fotoPreviews  = ref<{ file: File; url: string }[]>([])

const exchangeRate        = ref(0)
const exchangeRateLoading = ref(false)
const exchangeRateError   = ref(false)
const isPriceIdrManual    = ref(false)
const isKursManual        = ref(false)

async function fetchExchangeRate() {
    if (exchangeRate.value > 0) return
    exchangeRateLoading.value = true; exchangeRateError.value = false
    try {
        const res = await fetch('https://api.exchangerate-api.com/v4/latest/JPY')
        if (!res.ok) throw new Error()
        const data = await res.json()
        exchangeRate.value = Math.round(Number(data?.rates?.IDR ?? 0))
        if (!exchangeRate.value) throw new Error()
    } catch { exchangeRateError.value = true; exchangeRate.value = 110 }
    finally  { exchangeRateLoading.value = false }
}

// ─── Form ─────────────────────────────────────────────────
const form = useForm({
    koi_mode: 'create' as 'existing' | 'create',
    koi_existing_id: '',
    breeder_id: '', jenis_koi_id: '', code: '', name: '',
    age: '', size: '', box_qty: '', weight: '', total_weight: '',
    images: [] as File[],
    pembelian_mode: 'none' as 'none' | 'existing' | 'create',
    pembelian_existing_id: '', pembelian_date: '', pembelian_qty: '1',
    pembelian_price_jpy: '', pembelian_price_idr: '',
    katalog_mode: 'none' as 'none' | 'existing' | 'create',
    katalog_existing_id: '', katalog_tanggal: '', katalog_qty: '1',
    katalog_sell_jpy: '', katalog_kurs: '', katalog_sell_idr: '', katalog_fee: '0',
})

watch(koiMode, (m) => { form.koi_mode = m })
watch([pembelianMode, () => form.pembelian_existing_id], () => {
    form.pembelian_mode = pembelianMode.value === 'create' ? 'create'
        : form.pembelian_existing_id ? 'existing' : 'none'
})
watch([katalogMode, () => form.katalog_existing_id], () => {
    form.katalog_mode = katalogMode.value === 'create' ? 'create'
        : form.katalog_existing_id ? 'existing' : 'none'
})
watch([showModal, pembelianMode], ([open, mode]) => { if (open && mode === 'create') fetchExchangeRate() })
watch(exchangeRate, (rate) => {
    if (rate > 0 && !isKursManual.value) form.katalog_kurs = String(rate)
})
watch(() => form.pembelian_price_jpy, (jpy) => {
    if (!isPriceIdrManual.value && exchangeRate.value > 0 && jpy)
        form.pembelian_price_idr = String(Math.round(Number(jpy) * exchangeRate.value))
})
watch([() => form.katalog_sell_jpy, () => form.katalog_kurs], ([jpy, kurs]) => {
    if (jpy && kurs) form.katalog_sell_idr = String(Math.round(Number(jpy) * Number(kurs)))
})
watch(showModal, (open) => {
    if (!open) {
        form.reset(); form.clearErrors()
        koiMode.value = 'create'; pembelianMode.value = 'existing'; katalogMode.value = 'create'
        breederSearch.value = ''; jenisSearch.value = ''; koiSearch.value = ''
        fotoPreviews.value = []; isPriceIdrManual.value = false; isKursManual.value = false
    }
})

const filteredBreeders   = computed(() => !breederSearch.value ? breeders.value : breeders.value.filter(b => b.name_breeder.toLowerCase().includes(breederSearch.value.toLowerCase())))
const filteredJenisKois  = computed(() => !jenisSearch.value  ? jenisKois.value  : jenisKois.value.filter(j  => j.nama_jenis.toLowerCase().includes(jenisSearch.value.toLowerCase())))
const filteredKoisSelect = computed(() => !koiSearch.value    ? kois.value       : kois.value.filter(k => k.code.toLowerCase().includes(koiSearch.value.toLowerCase()) || k.name.toLowerCase().includes(koiSearch.value.toLowerCase())))

const selectedPembelianForKatalog = computed(() => {
    if (pembelianMode.value === 'existing' && form.pembelian_existing_id)
        return pembelians.value.find(p => p.id === Number(form.pembelian_existing_id)) ?? null
    if (pembelianMode.value === 'create')
        return { price_jpy: Number(form.pembelian_price_jpy) || 0, price_idr: Number(form.pembelian_price_idr) || 0 }
    return null
})
const katalogMargin         = computed(() => (Number(form.katalog_sell_idr) || 0) - (selectedPembelianForKatalog.value?.price_idr ?? 0))
const katalogProfit         = computed(() => katalogMargin.value - (Number(form.katalog_fee) || 0))
const katalogProfitPositive = computed(() => katalogProfit.value >= 0)
const katalogProfitPct      = computed(() => { const b = selectedPembelianForKatalog.value?.price_idr ?? 0; return b > 0 ? (katalogProfit.value / b * 100).toFixed(2) : '0.00' })
const katalogNeedsPembelian = computed(() => katalogMode.value === 'create' && pembelianMode.value === 'existing' && !form.pembelian_existing_id)

const showPembelianSection = computed(() => koiMode.value === 'existing' && !!form.koi_existing_id)
const showKatalogSection   = computed(() => showPembelianSection.value && pembelianMode.value === 'existing' && !!form.pembelian_existing_id)

watch(showKatalogSection, (visible) => { if (visible) fetchExchangeRate() })

function handleImagesChange(e: Event) {
    const input = e.target as HTMLInputElement
    if (!input.files?.length) return
    const files = Array.from(input.files)
    form.images = [...form.images, ...files]
    files.forEach(f => { const r = new FileReader(); r.onload = ev => fotoPreviews.value.push({ file: f, url: ev.target?.result as string }); r.readAsDataURL(f) })
    input.value = ''
}
function removeNewImage(i: number) { fotoPreviews.value.splice(i, 1); form.images = form.images.filter((_, idx) => idx !== i) }

// ─── Formatters ───────────────────────────────────────────
const fmtIDR  = (v: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v)
const fmtJPY  = (v: number) => new Intl.NumberFormat('ja-JP', { style: 'currency', currency: 'JPY', maximumFractionDigits: 0 }).format(v)
const fmtDate = (d: string) => new Date(d).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' })

function fmtInput(val: string | number): string {
    const n = String(val).replace(/\D/g, '')
    return n ? Number(n).toLocaleString('id-ID') : ''
}
function parseInput(e: Event): string {
    return (e.target as HTMLInputElement).value.replace(/\D/g, '')
}

function pembelianLabel(p: PembelianItem) {
    return `${fmtDate(p.date)} | ${p.koi ? p.koi.code + ' – ' + p.koi.name : 'KoiID ' + p.koi_id} | ${fmtIDR(p.price_idr)}`
}
function katalogLabel(k: KatalogItem) {
    return `${fmtDate(k.tanggal)} | ${k.koi ? k.koi.code + ' – ' + k.koi.name : 'KoiID ' + k.koi_id} | ${fmtIDR(k.sell_idr)}`
}

// ─── Submit modal ─────────────────────────────────────────
function submit() {
    form.post('/master-data/koi', {
        forceFormData: true, preserveScroll: true,
        onSuccess: () => { useSwal().fire({ icon: 'success', title: 'Berhasil!', text: 'Data berhasil disimpan.', timer: 3000, showConfirmButton: false }); showModal.value = false },
        onError:   () => { useSwal().fire({ icon: 'error',   title: 'Gagal!',    text: 'Terjadi kesalahan. Periksa kembali isian.',   timer: 3000, showConfirmButton: false }) },
    })
}

// ─── Delete actions ───────────────────────────────────────
function confirmDeleteKoi(id: number, name: string) {
    useSwal().fire({ title: 'Hapus koi?', text: `Koi "${name}" akan dihapus permanen.`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', cancelButtonColor: '#6b7280', confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal' })
        .then(r => { if (r.isConfirmed) router.delete(`/master-data/koi/${id}`, { preserveScroll: true, onSuccess: () => useSwal().fire({ icon: 'success', title: 'Berhasil!', text: 'Koi dihapus.', timer: 2000, showConfirmButton: false }) }) })
}
function confirmDeletePembelian(id: number) {
    useSwal().fire({ title: 'Hapus pembelian?', text: 'Data pembelian akan dihapus permanen.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', cancelButtonColor: '#6b7280', confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal' })
        .then(r => { if (r.isConfirmed) router.delete(`/pembelian/${id}`, { preserveScroll: true, onSuccess: () => useSwal().fire({ icon: 'success', title: 'Berhasil!', text: 'Pembelian dihapus.', timer: 2000, showConfirmButton: false }) }) })
}
function confirmDeleteKatalog(id: number) {
    useSwal().fire({ title: 'Hapus katalog?', text: 'Entri katalog akan dihapus permanen.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', cancelButtonColor: '#6b7280', confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal' })
        .then(r => { if (r.isConfirmed) router.delete(`/admin/katalog-koi/${id}`, { preserveScroll: true, onSuccess: () => useSwal().fire({ icon: 'success', title: 'Berhasil!', text: 'Katalog dihapus.', timer: 2000, showConfirmButton: false }) }) })
}

// ─── Button style helper ──────────────────────────────────
function modeBtn(active: boolean) {
    return active
        ? 'inline-flex items-center gap-1.5 rounded-md border border-foreground/25 bg-secondary px-2.5 py-1 text-xs font-medium text-secondary-foreground transition-colors'
        : 'inline-flex items-center gap-1.5 rounded-md border border-border bg-background px-2.5 py-1 text-xs font-medium text-muted-foreground transition-colors hover:bg-muted'
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Data Koi" />

        <div class="flex flex-col gap-4 p-6">

            <!-- ── Header ──────────────────────────────── -->
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Data Koi</h1>
                    <p class="text-sm text-muted-foreground">Kelola data koi, pembelian, dan katalog.</p>
                </div>

                <!-- ══ MODAL ══════════════════════════════ -->
                <Dialog v-model:open="showModal">
                    <DialogTrigger as-child>
                        <Button>+ New Koi</Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-[680px] max-h-[92vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>Data Koi</DialogTitle>
                            <DialogDescription>Tambah atau hubungkan data koi, pembelian, dan katalog sekaligus.</DialogDescription>
                        </DialogHeader>
                        <form @submit.prevent="submit" class="space-y-3">

                            <!-- Section: Data Koi -->
                            <div class="rounded-lg border border-border p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold">Data Koi</span>
                                    <div class="flex gap-1.5">
                                        <button type="button" :class="modeBtn(koiMode === 'existing')" @click="koiMode = 'existing'"><Link2 class="h-3 w-3" /> Select existing</button>
                                        <button type="button" :class="modeBtn(koiMode === 'create')"   @click="koiMode = 'create'">  <Plus  class="h-3 w-3" /> Create new</button>
                                    </div>
                                </div>

                                <!-- Select existing koi -->
                                <div v-if="koiMode === 'existing'" class="space-y-2">
                                    <Label class="text-xs text-muted-foreground">Koi</Label>
                                    <Select v-model="form.koi_existing_id">
                                        <SelectTrigger><SelectValue placeholder="Select..." /></SelectTrigger>
                                        <SelectContent class="w-[--radix-select-trigger-width] p-0">
                                            <div class="sticky top-0 bg-background p-2 border-b">
                                                <Input v-model="koiSearch" placeholder="Cari koi..." class="h-8" @keydown.stop />
                                            </div>
                                            <div class="max-h-48 overflow-y-auto">
                                                <div v-if="filteredKoisSelect.length === 0" class="px-3 py-2 text-sm text-muted-foreground">Tidak ada koi</div>
                                                <SelectItem v-for="k in filteredKoisSelect" :key="k.id" :value="String(k.id)">{{ k.code }} — {{ k.name }}</SelectItem>
                                            </div>
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="form.errors.koi_existing_id" />
                                </div>

                                <!-- Create new koi -->
                                <div v-if="koiMode === 'create'" class="space-y-3">
                                    <div class="grid gap-1.5">
                                        <Label class="text-xs text-muted-foreground">Breeder *</Label>
                                        <Select v-model="form.breeder_id">
                                            <SelectTrigger><SelectValue placeholder="Pilih breeder" /></SelectTrigger>
                                            <SelectContent class="p-0 w-[--radix-select-trigger-width] min-w-[20rem]">
                                                <div class="sticky top-0 bg-background p-2 border-b">
                                                    <Input v-model="breederSearch" placeholder="Cari breeder..." class="h-8" @keydown.stop />
                                                </div>
                                                <div class="max-h-44 overflow-y-auto">
                                                    <div v-if="filteredBreeders.length === 0" class="px-3 py-2 text-sm text-muted-foreground">Tidak ada breeder</div>
                                                    <SelectItem v-for="b in filteredBreeders" :key="b.id" :value="String(b.id)">{{ b.name_breeder }}</SelectItem>
                                                </div>
                                            </SelectContent>
                                        </Select>
                                        <InputError :message="form.errors.breeder_id" />
                                    </div>
                                    <div class="grid gap-1.5">
                                        <Label class="text-xs text-muted-foreground">Jenis Koi</Label>
                                        <Select v-model="form.jenis_koi_id">
                                            <SelectTrigger><SelectValue placeholder="Pilih jenis koi (opsional)" /></SelectTrigger>
                                            <SelectContent class="p-0 w-[--radix-select-trigger-width] min-w-[20rem]">
                                                <div class="sticky top-0 bg-background p-2 border-b">
                                                    <Input v-model="jenisSearch" placeholder="Cari jenis koi..." class="h-8" @keydown.stop />
                                                </div>
                                                <div class="max-h-44 overflow-y-auto">
                                                    <div v-if="filteredJenisKois.length === 0" class="px-3 py-2 text-sm text-muted-foreground">Tidak ada</div>
                                                    <SelectItem v-for="j in filteredJenisKois" :key="j.id" :value="String(j.id)">{{ j.nama_jenis }}</SelectItem>
                                                </div>
                                            </SelectContent>
                                        </Select>
                                        <InputError :message="form.errors.jenis_koi_id" />
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="grid gap-1.5">
                                            <Label class="text-xs text-muted-foreground">Kode Koi *</Label>
                                            <Input v-model="form.code" placeholder="KOI-001" />
                                            <InputError :message="form.errors.code" />
                                        </div>
                                        <div class="grid gap-1.5">
                                            <Label class="text-xs text-muted-foreground">Nama Koi *</Label>
                                            <Input v-model="form.name" placeholder="Nama koi" />
                                            <InputError :message="form.errors.name" />
                                        </div>
                                    </div>
                                    <div class="rounded-md border border-border bg-muted/30 p-3 space-y-2">
                                        <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Data Fisik</p>
                                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                            <div class="grid gap-1"><Label class="text-xs text-muted-foreground">Age (bln)</Label><Input v-model="form.age" type="number" min="0" placeholder="0" class="h-8 text-sm" /><InputError :message="form.errors.age" /></div>
                                            <div class="grid gap-1"><Label class="text-xs text-muted-foreground">Size (cm)</Label><Input v-model="form.size" type="number" min="0" step="0.01" placeholder="0" class="h-8 text-sm" /><InputError :message="form.errors.size" /></div>
                                            <div class="grid gap-1"><Label class="text-xs text-muted-foreground">Box QTY</Label><Input v-model="form.box_qty" type="number" min="0" placeholder="0" class="h-8 text-sm" /><InputError :message="form.errors.box_qty" /></div>
                                            <div class="grid gap-1"><Label class="text-xs text-muted-foreground">Weight (kg)</Label><Input v-model="form.weight" type="number" min="0" step="0.001" placeholder="0" class="h-8 text-sm" /><InputError :message="form.errors.weight" /></div>
                                        </div>
                                    </div>
                                    <div class="rounded-md border border-border bg-muted/30 p-3">
                                        <p class="mb-2 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Foto Koi</p>
                                        <div v-if="fotoPreviews.length > 0" class="mb-2 flex flex-wrap gap-2">
                                            <div v-for="(preview, i) in fotoPreviews" :key="i" class="relative h-16 w-16 overflow-hidden rounded-lg border group">
                                                <img :src="preview.url" class="h-full w-full object-cover" />
                                                <button type="button" @click="removeNewImage(i)" class="absolute inset-0 flex items-center justify-center bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity"><X class="h-4 w-4 text-white" /></button>
                                            </div>
                                        </div>
                                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border-2 border-dashed border-border px-3 py-2 text-xs text-muted-foreground hover:border-primary/50 hover:text-foreground transition-colors">
                                            <ImagePlus class="h-4 w-4 flex-shrink-0" />
                                            <span>Pilih foto (jpg, png, webp · maks. 2 MB · bisa beberapa)</span>
                                            <input type="file" accept="image/jpg,image/jpeg,image/png,image/webp" multiple class="sr-only" @change="handleImagesChange" />
                                        </label>
                                        <InputError :message="(form.errors as any)['images.*']" class="mt-1" />
                                    </div>
                                </div>
                            </div>

                            <!-- Section: Pembelian -->
                            <div v-if="showPembelianSection" class="rounded-lg border border-border p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold">Pembelian</span>
                                    <div class="flex gap-1.5">
                                        <button type="button" :class="modeBtn(pembelianMode === 'existing')" @click="pembelianMode = 'existing'"><Link2 class="h-3 w-3" /> Select existing</button>
                                        <button type="button" :class="modeBtn(pembelianMode === 'create')"   @click="pembelianMode = 'create'">  <Plus  class="h-3 w-3" /> Create new</button>
                                    </div>
                                </div>
                                <div v-if="pembelianMode === 'existing'" class="space-y-2">
                                    <Label class="text-xs text-muted-foreground">Pembelian <span class="text-muted-foreground/60">(kosongkan untuk lewati)</span></Label>
                                    <Select v-model="form.pembelian_existing_id">
                                        <SelectTrigger><SelectValue placeholder="Select..." /></SelectTrigger>
                                        <SelectContent class="w-[--radix-select-trigger-width] p-0">
                                            <div class="max-h-56 overflow-y-auto">
                                                <SelectItem v-for="p in pembelians" :key="p.id" :value="String(p.id)">{{ pembelianLabel(p) }}</SelectItem>
                                            </div>
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="form.errors.pembelian_existing_id" />
                                    <div v-if="form.pembelian_existing_id" class="rounded-md bg-blue-50 dark:bg-blue-950 p-3 text-xs grid grid-cols-2 gap-2">
                                        <template v-if="pembelians.find(p => p.id === Number(form.pembelian_existing_id))">
                                            <div><p class="text-muted-foreground">Tanggal</p><p class="font-medium">{{ fmtDate(pembelians.find(p => p.id === Number(form.pembelian_existing_id))!.date) }}</p></div>
                                            <div><p class="text-muted-foreground">Qty</p><p class="font-medium">{{ pembelians.find(p => p.id === Number(form.pembelian_existing_id))!.qty }}</p></div>
                                            <div><p class="text-muted-foreground">Harga JPY</p><p class="font-medium">{{ fmtJPY(pembelians.find(p => p.id === Number(form.pembelian_existing_id))!.price_jpy) }}</p></div>
                                            <div><p class="text-muted-foreground">Harga IDR</p><p class="font-medium">{{ fmtIDR(pembelians.find(p => p.id === Number(form.pembelian_existing_id))!.price_idr) }}</p></div>
                                        </template>
                                    </div>
                                </div>
                                <div v-if="pembelianMode === 'create'" class="space-y-3">
                                    <div class="rounded-md bg-muted/50 px-3 py-1.5 text-xs text-muted-foreground">
                                        <span v-if="exchangeRateLoading">Memuat kurs JPY→IDR...</span>
                                        <span v-else-if="exchangeRateError" class="text-orange-500">Kurs fallback: {{ exchangeRate }}</span>
                                        <span v-else>Kurs JPY→IDR: <strong>Rp {{ exchangeRate.toLocaleString('id-ID') }}</strong></span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="grid gap-1.5"><Label class="text-xs text-muted-foreground">Tanggal *</Label><Input v-model="form.pembelian_date" type="date" class="h-9 text-sm" /><InputError :message="form.errors.pembelian_date" /></div>
                                        <div class="grid gap-1.5"><Label class="text-xs text-muted-foreground">Qty *</Label><Input v-model="form.pembelian_qty" type="number" min="1" placeholder="1" class="h-9 text-sm" /><InputError :message="form.errors.pembelian_qty" /></div>
                                        <div class="grid gap-1.5"><Label class="text-xs text-muted-foreground">Harga JPY *</Label><Input type="text" inputmode="numeric" :value="fmtInput(form.pembelian_price_jpy)" @input="e => { form.pembelian_price_jpy = parseInput(e) }" placeholder="0" class="h-9 text-sm" /><InputError :message="form.errors.pembelian_price_jpy" /></div>
                                        <div class="grid gap-1.5"><Label class="text-xs text-muted-foreground">Harga IDR * <span class="font-normal">(auto)</span></Label><Input type="text" inputmode="numeric" :value="fmtInput(form.pembelian_price_idr)" @input="e => { isPriceIdrManual = true; form.pembelian_price_idr = parseInput(e) }" @focus="isPriceIdrManual = true" placeholder="0" class="h-9 text-sm" /><InputError :message="form.errors.pembelian_price_idr" /></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Section: Katalog Koi -->
                            <div v-if="showKatalogSection" class="rounded-lg border border-border p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold">Katalog Koi</span>
                                </div>
                                <div class="space-y-3">
                                    <div v-if="katalogNeedsPembelian" class="rounded-md border border-orange-200 bg-orange-50 dark:border-orange-800 dark:bg-orange-950 px-3 py-2 text-xs text-orange-700 dark:text-orange-300">
                                        Pilih atau buat data <strong>Pembelian</strong> — diperlukan untuk harga beli katalog.
                                    </div>
                                    <template v-if="!katalogNeedsPembelian">
                                        <div v-if="selectedPembelianForKatalog" class="rounded-md bg-muted/50 p-3 text-xs">
                                            <p class="mb-1.5 font-semibold text-muted-foreground uppercase tracking-wide">Harga Beli (dari Pembelian)</p>
                                            <div class="grid grid-cols-2 gap-2">
                                                <div><p class="text-muted-foreground">JPY</p><p class="font-medium">{{ fmtJPY(selectedPembelianForKatalog.price_jpy) }}</p></div>
                                                <div><p class="text-muted-foreground">IDR</p><p class="font-medium">{{ fmtIDR(selectedPembelianForKatalog.price_idr) }}</p></div>
                                            </div>
                                        </div>
                                        <div v-else class="rounded-md bg-muted/50 px-3 py-2 text-xs text-muted-foreground italic">Pilih pembelian di atas untuk melihat harga beli.</div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="grid gap-1.5"><Label class="text-xs text-muted-foreground">Tanggal *</Label><Input v-model="form.katalog_tanggal" type="date" class="h-9 text-sm" /><InputError :message="form.errors.katalog_tanggal" /></div>
                                            <div class="grid gap-1.5"><Label class="text-xs text-muted-foreground">Qty *</Label><Input v-model="form.katalog_qty" type="number" min="1" placeholder="1" class="h-9 text-sm" /><InputError :message="form.errors.katalog_qty" /></div>
                                            <div class="grid gap-1.5"><Label class="text-xs text-muted-foreground">Harga Jual JPY *</Label><Input type="text" inputmode="numeric" :value="fmtInput(form.katalog_sell_jpy)" @input="e => { form.katalog_sell_jpy = parseInput(e) }" placeholder="0" class="h-9 text-sm" /><InputError :message="form.errors.katalog_sell_jpy" /></div>
                                            <div class="grid gap-1.5">
                                                <Label class="text-xs text-muted-foreground">
                                                    Kurs (IDR/JPY) *
                                                    <span v-if="exchangeRateLoading" class="ml-1 font-normal text-muted-foreground/60">(memuat...)</span>
                                                    <span v-else-if="exchangeRateError" class="ml-1 font-normal text-orange-500">(fallback)</span>
                                                    <span v-else-if="!isKursManual && exchangeRate > 0" class="ml-1 font-normal text-muted-foreground/60">(auto)</span>
                                                    <span v-else-if="isKursManual" class="ml-1 font-normal text-muted-foreground/60">(manual)</span>
                                                </Label>
                                                <Input type="text" inputmode="numeric" :value="fmtInput(form.katalog_kurs)" @input="e => { isKursManual = true; form.katalog_kurs = parseInput(e) }" placeholder="0" class="h-9 text-sm" />
                                                <InputError :message="form.errors.katalog_kurs" />
                                            </div>
                                            <div class="grid gap-1.5"><Label class="text-xs text-muted-foreground">Harga Jual IDR * <span class="font-normal">(auto)</span></Label><Input type="text" inputmode="numeric" :value="fmtInput(form.katalog_sell_idr)" @input="e => { form.katalog_sell_idr = parseInput(e) }" placeholder="0" class="h-9 text-sm" /><InputError :message="form.errors.katalog_sell_idr" /></div>
                                            <div class="grid gap-1.5"><Label class="text-xs text-muted-foreground">Fee IDR</Label><Input type="text" inputmode="numeric" :value="fmtInput(form.katalog_fee)" @input="e => { form.katalog_fee = parseInput(e) }" placeholder="0" class="h-9 text-sm" /><InputError :message="form.errors.katalog_fee" /></div>
                                        </div>
                                        <div class="rounded-md bg-muted/50 p-3 text-xs space-y-1.5">
                                            <p class="font-semibold text-muted-foreground uppercase tracking-wide mb-2">Perhitungan</p>
                                            <div class="flex justify-between"><span class="text-muted-foreground">Harga Beli IDR:</span><span>{{ fmtIDR(selectedPembelianForKatalog?.price_idr ?? 0) }}</span></div>
                                            <div class="flex justify-between"><span class="text-muted-foreground">Harga Jual IDR:</span><span>{{ fmtIDR(Number(form.katalog_sell_idr) || 0) }}</span></div>
                                            <div class="flex justify-between border-t border-border pt-1.5"><span class="text-muted-foreground">Margin:</span><span>{{ fmtIDR(katalogMargin) }}</span></div>
                                            <div class="flex justify-between"><span class="text-muted-foreground">Fee:</span><span>{{ fmtIDR(Number(form.katalog_fee) || 0) }}</span></div>
                                            <div class="flex justify-between border-t border-border pt-1.5 font-semibold" :class="katalogProfitPositive ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                                <span>Profit:</span><span>{{ fmtIDR(katalogProfit) }} ({{ katalogProfitPct }}%)</span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <DialogFooter>
                                <Button type="button" variant="outline" @click="showModal = false">Batal</Button>
                                <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Menyimpan...' : 'Simpan' }}</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- ══ TAB NAVIGATION ══════════════════════════ -->
            <div class="flex gap-0 rounded-lg border border-border bg-muted p-1 w-fit">
                <button
                    v-for="tab in [{ key: 'koi', label: 'Data Koi', count: kois.length }, { key: 'pembelian', label: 'Pembelian', count: pembelians.length }, { key: 'katalog', label: 'Katalog Koi', count: katalogs.length }]"
                    :key="tab.key"
                    type="button"
                    :class="activeTab === tab.key
                        ? 'bg-background text-foreground shadow-sm'
                        : 'text-muted-foreground hover:text-foreground'"
                    class="inline-flex items-center gap-1.5 rounded-md px-4 py-1.5 text-sm font-medium transition-all"
                    @click="activeTab = tab.key as any"
                >
                    {{ tab.label }}
                    <span class="rounded-full bg-muted px-1.5 py-0.5 text-xs font-semibold leading-none"
                        :class="activeTab === tab.key ? 'bg-primary/10 text-primary' : 'text-muted-foreground'">
                        {{ tab.count }}
                    </span>
                </button>
            </div>

            <!-- ══════════════════════════════════════════════
                 TAB 1: DATA KOI
            ═══════════════════════════════════════════════ -->
            <div v-show="activeTab === 'koi'" class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                <div class="flex flex-col gap-2 border-b border-border p-4 sm:flex-row sm:items-center sm:justify-between">
                    <Input :model-value="kSearch" @update:model-value="v => { kSearch = String(v); kPage = 1 }" placeholder="Cari code, nama, atau breeder..." class="max-w-sm" />
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted-foreground">Show</span>
                        <select :value="kPerPage" @change="(e) => { kPerPage = Number((e.target as HTMLSelectElement).value); kPage = 1 }" class="rounded-md border border-input bg-background px-3 py-1 text-sm">
                            <option>5</option><option>10</option><option>25</option><option>50</option>
                        </select>
                        <span class="text-sm text-muted-foreground">entries</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="kToggleSort('code')">Code <span class="ml-1">{{ kSortIcon('code') }}</span></th>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="kToggleSort('name')">Nama <span class="ml-1">{{ kSortIcon('name') }}</span></th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Breeder</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Jenis</th>
                                <th class="cursor-pointer select-none px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="kToggleSort('age')">Age <span class="ml-1">{{ kSortIcon('age') }}</span></th>
                                <th class="cursor-pointer select-none px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="kToggleSort('size')">Size <span class="ml-1">{{ kSortIcon('size') }}</span></th>
                                <th class="cursor-pointer select-none px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="kToggleSort('box_qty')">Box QTY <span class="ml-1">{{ kSortIcon('box_qty') }}</span></th>
                                <th class="cursor-pointer select-none px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="kToggleSort('weight')">Weight <span class="ml-1">{{ kSortIcon('weight') }}</span></th>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="kToggleSort('created_at')">Created <span class="ml-1">{{ kSortIcon('created_at') }}</span></th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-background">
                            <tr v-if="paginatedKois.length === 0"><td colspan="10" class="px-4 py-8 text-center text-sm text-muted-foreground">{{ kSearch ? 'Tidak ada hasil.' : 'Belum ada data koi.' }}</td></tr>
                            <tr v-for="koi in paginatedKois" :key="koi.id" class="transition-colors hover:bg-muted/50">
                                <td class="px-4 py-3 text-sm font-medium">{{ koi.code }}</td>
                                <td class="px-4 py-3 text-sm">{{ koi.name }}</td>
                                <td class="px-4 py-3 text-sm">{{ getBreederName(koi) }}</td>
                                <td class="px-4 py-3 text-sm">{{ getJenisName(koi) }}</td>
                                <td class="px-4 py-3 text-center text-sm">{{ koi.age ?? '-' }}</td>
                                <td class="px-4 py-3 text-center text-sm">{{ koi.size != null ? koi.size + ' cm' : '-' }}</td>
                                <td class="px-4 py-3 text-center text-sm">{{ koi.box_qty ?? '-' }}</td>
                                <td class="px-4 py-3 text-center text-sm">{{ koi.weight != null ? koi.weight + ' kg' : '-' }}</td>
                                <td class="px-4 py-3 text-sm">{{ fmtDate(koi.created_at) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="`/master-data/koi/${koi.id}`" class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background p-2 text-foreground shadow-sm hover:bg-muted" title="Detail"><Eye :size="16" /></Link>
                                        <Link :href="`/master-data/koi/${koi.id}/edit`" class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background p-2 text-foreground shadow-sm hover:bg-muted" title="Edit"><Pencil :size="16" /></Link>
                                        <button type="button" class="inline-flex items-center justify-center rounded-md border border-red-300 bg-red-50 p-2 text-red-700 shadow-sm hover:bg-red-100 dark:border-red-700 dark:bg-red-900 dark:text-red-200 dark:hover:bg-red-800" title="Hapus" @click="confirmDeleteKoi(koi.id, koi.name)"><Trash2 :size="16" /></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col items-center justify-between gap-2 border-t border-border px-4 py-3 sm:flex-row">
                    <p class="text-sm text-muted-foreground">Showing {{ kFrom }} to {{ kTo }} of {{ filteredKois.length }} entries<span v-if="kSearch"> (filtered from {{ kois.length }} total)</span></p>
                    <div class="flex items-center gap-1">
                        <Button variant="outline" size="sm" :disabled="kPage <= 1" @click="kPage--">Previous</Button>
                        <template v-for="p in kTotalPages" :key="p"><Button size="sm" :variant="p === kPage ? 'default' : 'outline'" @click="kPage = p">{{ p }}</Button></template>
                        <Button variant="outline" size="sm" :disabled="kPage >= kTotalPages" @click="kPage++">Next</Button>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════
                 TAB 2: PEMBELIAN
            ═══════════════════════════════════════════════ -->
            <div v-show="activeTab === 'pembelian'" class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                <div class="flex flex-col gap-2 border-b border-border p-4 sm:flex-row sm:items-center sm:justify-between">
                    <Input :model-value="pSearch" @update:model-value="v => { pSearch = String(v); pPage = 1 }" placeholder="Cari koi atau status..." class="max-w-sm" />
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted-foreground">Show</span>
                        <select :value="pPerPage" @change="(e) => { pPerPage = Number((e.target as HTMLSelectElement).value); pPage = 1 }" class="rounded-md border border-input bg-background px-3 py-1 text-sm">
                            <option>5</option><option>10</option><option>25</option><option>50</option>
                        </select>
                        <span class="text-sm text-muted-foreground">entries</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="pToggleSort('date')">Tanggal <span class="ml-1">{{ pSortIcon('date') }}</span></th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Koi</th>
                                <th class="cursor-pointer select-none px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="pToggleSort('qty')">Qty <span class="ml-1">{{ pSortIcon('qty') }}</span></th>
                                <th class="cursor-pointer select-none px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="pToggleSort('price_jpy')">Harga JPY <span class="ml-1">{{ pSortIcon('price_jpy') }}</span></th>
                                <th class="cursor-pointer select-none px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="pToggleSort('price_idr')">Harga IDR <span class="ml-1">{{ pSortIcon('price_idr') }}</span></th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-background">
                            <tr v-if="paginatedPembelians.length === 0"><td colspan="7" class="px-4 py-8 text-center text-sm text-muted-foreground">{{ pSearch ? 'Tidak ada hasil.' : 'Belum ada data pembelian.' }}</td></tr>
                            <tr v-for="p in paginatedPembelians" :key="p.id" class="transition-colors hover:bg-muted/50">
                                <td class="px-4 py-3 text-sm">{{ fmtDate(p.date) }}</td>
                                <td class="px-4 py-3 text-sm"><span class="font-medium">{{ p.koi?.code ?? '-' }}</span><span class="text-muted-foreground"> — {{ p.koi?.name ?? '-' }}</span></td>
                                <td class="px-4 py-3 text-center text-sm">{{ p.qty }}</td>
                                <td class="px-4 py-3 text-right text-sm font-mono">{{ fmtJPY(p.price_jpy) }}</td>
                                <td class="px-4 py-3 text-right text-sm font-mono">{{ fmtIDR(p.price_idr) }}</td>
                                <td class="px-4 py-3 text-center text-sm">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="p.status === 'completed' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'">
                                        {{ p.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="`/pembelian/${p.id}`" class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background p-2 text-foreground shadow-sm hover:bg-muted" title="Detail"><Eye :size="16" /></Link>
                                        <Link :href="`/pembelian/${p.id}/edit`" class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background p-2 text-foreground shadow-sm hover:bg-muted" title="Edit"><Pencil :size="16" /></Link>
                                        <button type="button" class="inline-flex items-center justify-center rounded-md border border-red-300 bg-red-50 p-2 text-red-700 shadow-sm hover:bg-red-100 dark:border-red-700 dark:bg-red-900 dark:text-red-200 dark:hover:bg-red-800" title="Hapus" @click="confirmDeletePembelian(p.id)"><Trash2 :size="16" /></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col items-center justify-between gap-2 border-t border-border px-4 py-3 sm:flex-row">
                    <p class="text-sm text-muted-foreground">Showing {{ pFrom }} to {{ pTo }} of {{ filteredPembelians.length }} entries<span v-if="pSearch"> (filtered from {{ pembelians.length }} total)</span></p>
                    <div class="flex items-center gap-1">
                        <Button variant="outline" size="sm" :disabled="pPage <= 1" @click="pPage--">Previous</Button>
                        <template v-for="p in pTotalPages" :key="p"><Button size="sm" :variant="p === pPage ? 'default' : 'outline'" @click="pPage = p">{{ p }}</Button></template>
                        <Button variant="outline" size="sm" :disabled="pPage >= pTotalPages" @click="pPage++">Next</Button>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════
                 TAB 3: KATALOG KOI
            ═══════════════════════════════════════════════ -->
            <div v-show="activeTab === 'katalog'" class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                <div class="flex flex-col gap-2 border-b border-border p-4 sm:flex-row sm:items-center sm:justify-between">
                    <Input :model-value="katSearch" @update:model-value="v => { katSearch = String(v); katPage = 1 }" placeholder="Cari kode atau nama koi..." class="max-w-sm" />
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted-foreground">Show</span>
                        <select :value="katPerPage" @change="(e) => { katPerPage = Number((e.target as HTMLSelectElement).value); katPage = 1 }" class="rounded-md border border-input bg-background px-3 py-1 text-sm">
                            <option>5</option><option>10</option><option>25</option><option>50</option>
                        </select>
                        <span class="text-sm text-muted-foreground">entries</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="katToggleSort('tanggal')">Tanggal <span class="ml-1">{{ katSortIcon('tanggal') }}</span></th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Koi</th>
                                <th class="cursor-pointer select-none px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="katToggleSort('qty')">Qty <span class="ml-1">{{ katSortIcon('qty') }}</span></th>
                                <th class="cursor-pointer select-none px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="katToggleSort('sell_jpy')">Jual JPY <span class="ml-1">{{ katSortIcon('sell_jpy') }}</span></th>
                                <th class="cursor-pointer select-none px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="katToggleSort('sell_idr')">Jual IDR <span class="ml-1">{{ katSortIcon('sell_idr') }}</span></th>
                                <th class="cursor-pointer select-none px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="katToggleSort('profit')">Profit <span class="ml-1">{{ katSortIcon('profit') }}</span></th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-background">
                            <tr v-if="paginatedKatalogs.length === 0"><td colspan="7" class="px-4 py-8 text-center text-sm text-muted-foreground">{{ katSearch ? 'Tidak ada hasil.' : 'Belum ada data katalog.' }}</td></tr>
                            <tr v-for="k in paginatedKatalogs" :key="k.id" class="transition-colors hover:bg-muted/50">
                                <td class="px-4 py-3 text-sm">{{ fmtDate(k.tanggal) }}</td>
                                <td class="px-4 py-3 text-sm"><span class="font-medium">{{ k.koi?.code ?? '-' }}</span><span class="text-muted-foreground"> — {{ k.koi?.name ?? '-' }}</span></td>
                                <td class="px-4 py-3 text-center text-sm">{{ k.qty }}</td>
                                <td class="px-4 py-3 text-right text-sm font-mono">{{ fmtJPY(k.sell_jpy) }}</td>
                                <td class="px-4 py-3 text-right text-sm font-mono">{{ fmtIDR(k.sell_idr) }}</td>
                                <td class="px-4 py-3 text-right text-sm font-mono"
                                    :class="Number(k.profit) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                    {{ fmtIDR(Number(k.profit)) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="`/admin/katalog-koi/${k.id}`" class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background p-2 text-foreground shadow-sm hover:bg-muted" title="Detail"><Eye :size="16" /></Link>
                                        <Link :href="`/admin/katalog-koi/${k.id}/edit`" class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background p-2 text-foreground shadow-sm hover:bg-muted" title="Edit"><Pencil :size="16" /></Link>
                                        <button type="button" class="inline-flex items-center justify-center rounded-md border border-red-300 bg-red-50 p-2 text-red-700 shadow-sm hover:bg-red-100 dark:border-red-700 dark:bg-red-900 dark:text-red-200 dark:hover:bg-red-800" title="Hapus" @click="confirmDeleteKatalog(k.id)"><Trash2 :size="16" /></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col items-center justify-between gap-2 border-t border-border px-4 py-3 sm:flex-row">
                    <p class="text-sm text-muted-foreground">Showing {{ katFrom }} to {{ katTo }} of {{ filteredKatalogs.length }} entries<span v-if="katSearch"> (filtered from {{ katalogs.length }} total)</span></p>
                    <div class="flex items-center gap-1">
                        <Button variant="outline" size="sm" :disabled="katPage <= 1" @click="katPage--">Previous</Button>
                        <template v-for="p in katTotalPages" :key="p"><Button size="sm" :variant="p === katPage ? 'default' : 'outline'" @click="katPage = p">{{ p }}</Button></template>
                        <Button variant="outline" size="sm" :disabled="katPage >= katTotalPages" @click="katPage++">Next</Button>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
