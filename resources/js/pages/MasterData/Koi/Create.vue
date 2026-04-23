<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { X, ImagePlus } from 'lucide-vue-next'
import AppLayout from '@/layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import { useSwal } from '@/composables/useSwal'
import type { BreadcrumbItem } from '@/types'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'

type Breeder       = { id: number; name_breeder: string }
type JenisKoi      = { id: number; nama_jenis: string }
type PembelianItem = {
    id: number
    koi_id: number
    date: string
    qty: number
    price_jpy: number
    price_idr: number
    status: string
    koi?: { id: number; code: string; name: string }
}
type KatalogItem = {
    id: number
    koi_id: number
    pembelian_id: number
    tanggal: string
    sell_idr: number
    koi?: { id: number; code: string; name: string }
}

const page       = usePage()
const breeders   = (page.props.breeders   as Breeder[]       ) || []
const jenisKois  = (page.props.jenisKois  as JenisKoi[]      ) || []
const pembelians = (page.props.pembelians as PembelianItem[]  ) || []
const katalogs   = (page.props.katalogs   as KatalogItem[]   ) || []

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data', href: '#' },
    { title: 'Data Koi', href: '/master-data/koi' },
    { title: 'Tambah Koi', href: '/master-data/koi/create' },
]

// ─── Breeder / Jenis search ───────────────────────────────
const breederSearch = ref('')
const jenisSearch   = ref('')
const filteredBreeders = computed(() => {
    if (!breederSearch.value) return breeders
    const q = breederSearch.value.toLowerCase()
    return breeders.filter(b => b.name_breeder.toLowerCase().includes(q))
})
const filteredJenisKois = computed(() => {
    if (!jenisSearch.value) return jenisKois
    const q = jenisSearch.value.toLowerCase()
    return jenisKois.filter(j => j.nama_jenis.toLowerCase().includes(q))
})

// ─── Images ───────────────────────────────────────────────
const fotoPreviews = ref<{ file: File; url: string }[]>([])

// ─── Section enable flags ─────────────────────────────────
const pembelianEnabled = ref(false)
const pembelianMode    = ref<'existing' | 'create'>('create')
const katalogEnabled   = ref(false)
const katalogMode      = ref<'existing' | 'create'>('create')

// ─── Exchange rate (for create-pembelian mode) ────────────
const exchangeRate        = ref(0)
const exchangeRateLoading = ref(false)
const exchangeRateError   = ref(false)
const isPriceIdrManual    = ref(false)

async function fetchExchangeRate() {
    if (exchangeRate.value > 0) return
    exchangeRateLoading.value = true
    exchangeRateError.value   = false
    try {
        const res  = await fetch('https://api.exchangerate-api.com/v4/latest/JPY')
        if (!res.ok) throw new Error()
        const data = await res.json()
        exchangeRate.value = Math.round(Number(data?.rates?.IDR ?? 0))
        if (!exchangeRate.value) throw new Error()
    } catch {
        exchangeRateError.value = true
        exchangeRate.value      = 110
    } finally {
        exchangeRateLoading.value = false
    }
}

watch([pembelianEnabled, pembelianMode], ([enabled, mode]) => {
    if (enabled && mode === 'create') fetchExchangeRate()
})

// ─── Form ─────────────────────────────────────────────────
const form = useForm({
    // Data Koi
    breeder_id:   '',
    jenis_koi_id: '',
    code:         '',
    name:         '',
    age:          '',
    size:         '',
    box_qty:      '',
    weight:       '',
    total_weight: '',
    images:       [] as File[],

    // Pembelian
    pembelian_mode:        'none' as 'none' | 'existing' | 'create',
    pembelian_existing_id: '',
    pembelian_date:        '',
    pembelian_qty:         '1',
    pembelian_price_jpy:   '',
    pembelian_price_idr:   '',

    // Katalog
    katalog_mode:        'none' as 'none' | 'existing' | 'create',
    katalog_existing_id: '',
    katalog_tanggal:     '',
    katalog_qty:         '1',
    katalog_sell_jpy:    '',
    katalog_kurs:        '',
    katalog_sell_idr:    '',
    katalog_fee:         '0',
})

// Sync mode flags → form fields
watch([pembelianEnabled, pembelianMode], () => {
    form.pembelian_mode = pembelianEnabled.value ? pembelianMode.value : 'none'
    if (!pembelianEnabled.value) {
        isPriceIdrManual.value = false
    }
})
watch([katalogEnabled, katalogMode], () => {
    form.katalog_mode = katalogEnabled.value ? katalogMode.value : 'none'
})

// Auto-calc price_idr ketika price_jpy berubah
watch(() => form.pembelian_price_jpy, (jpy) => {
    if (!isPriceIdrManual.value && exchangeRate.value > 0 && jpy) {
        form.pembelian_price_idr = String(Math.round(Number(jpy) * exchangeRate.value))
    }
})

// Auto-calc sell_idr ketika sell_jpy × kurs berubah
watch([() => form.katalog_sell_jpy, () => form.katalog_kurs], ([jpy, kurs]) => {
    if (jpy && kurs) {
        form.katalog_sell_idr = String(Math.round(Number(jpy) * Number(kurs)))
    }
})

// ─── Computed helpers untuk Katalog ──────────────────────
const selectedPembelianForKatalog = computed<{ price_jpy: number; price_idr: number } | null>(() => {
    if (!pembelianEnabled.value) return null
    if (pembelianMode.value === 'existing' && form.pembelian_existing_id) {
        const p = pembelians.find(p => p.id === Number(form.pembelian_existing_id))
        return p ? { price_jpy: p.price_jpy, price_idr: p.price_idr } : null
    }
    if (pembelianMode.value === 'create') {
        return {
            price_jpy: Number(form.pembelian_price_jpy) || 0,
            price_idr: Number(form.pembelian_price_idr) || 0,
        }
    }
    return null
})

const katalogMargin          = computed(() => (Number(form.katalog_sell_idr) || 0) - (selectedPembelianForKatalog.value?.price_idr ?? 0))
const katalogProfit          = computed(() => katalogMargin.value - (Number(form.katalog_fee) || 0))
const katalogProfitPositive  = computed(() => katalogProfit.value >= 0)
const katalogProfitPct       = computed(() => {
    const base = selectedPembelianForKatalog.value?.price_idr ?? 0
    return base > 0 ? (katalogProfit.value / base * 100).toFixed(2) : '0.00'
})
const katalogNeedsPembelian  = computed(() => katalogEnabled.value && katalogMode.value === 'create' && !pembelianEnabled.value)

// ─── Image handlers ───────────────────────────────────────
function handleImagesChange(e: Event) {
    const input = e.target as HTMLInputElement
    if (!input.files?.length) return
    const newFiles = Array.from(input.files)
    form.images = [...form.images, ...newFiles]
    newFiles.forEach(file => {
        const reader = new FileReader()
        reader.onload = ev => fotoPreviews.value.push({ file, url: ev.target?.result as string })
        reader.readAsDataURL(file)
    })
    input.value = ''
}
function removeNewImage(index: number) {
    fotoPreviews.value.splice(index, 1)
    form.images = form.images.filter((_, i) => i !== index)
}

// ─── Formatters ───────────────────────────────────────────
const fmtIDR = (v: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v)
const fmtJPY = (v: number) =>
    new Intl.NumberFormat('ja-JP', { style: 'currency', currency: 'JPY', maximumFractionDigits: 0 }).format(v)
const fmtDate = (d: string) =>
    new Date(d).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' })

function pembelianLabel(p: PembelianItem) {
    const koi = p.koi ? `${p.koi.code} - ${p.koi.name}` : `ID ${p.koi_id}`
    return `${fmtDate(p.date)} | ${koi} | ${fmtIDR(p.price_idr)}`
}
function katalogLabel(k: KatalogItem) {
    const koi = k.koi ? `${k.koi.code} - ${k.koi.name}` : `KoiID ${k.koi_id}`
    return `${fmtDate(k.tanggal)} | ${koi} | ${fmtIDR(k.sell_idr)}`
}

// ─── Submit ───────────────────────────────────────────────
function submit() {
    form.post('/master-data/koi', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            useSwal().fire({ icon: 'success', title: 'Berhasil!', text: 'Data koi berhasil ditambahkan.', timer: 3000, showConfirmButton: false })
        },
        onError: () => {
            useSwal().fire({ icon: 'error', title: 'Gagal!', text: 'Terjadi kesalahan. Periksa kembali isian form.', timer: 3000, showConfirmButton: false })
        },
    })
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Tambah Koi" />

        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Tambah Koi</h1>
                    <p class="text-sm text-muted-foreground">Isi data koi beserta data pembelian dan katalog (opsional).</p>
                </div>
                <Link href="/master-data/koi"
                    class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted">
                    Kembali
                </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- ═══════════════════════════════════════════
                     SECTION 1: DATA KOI
                ═══════════════════════════════════════════════ -->
                <div class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                    <h2 class="mb-5 text-base font-semibold">Data Koi</h2>

                    <div class="space-y-4">
                        <!-- Breeder -->
                        <div class="grid gap-2">
                            <Label>Breeder <span class="text-red-500">*</span></Label>
                            <Select v-model="form.breeder_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih breeder" />
                                </SelectTrigger>
                                <SelectContent class="w-[--radix-select-trigger-width] min-w-[20rem] p-0">
                                    <div class="sticky top-0 border-b bg-background p-2">
                                        <Input v-model="breederSearch" placeholder="Cari breeder..." class="h-8" @keydown.stop />
                                    </div>
                                    <div class="max-h-48 overflow-y-auto">
                                        <div v-if="filteredBreeders.length === 0" class="px-2 py-2 text-sm text-muted-foreground">Breeder tidak ditemukan</div>
                                        <SelectItem v-for="b in filteredBreeders" :key="b.id" :value="String(b.id)">
                                            {{ b.name_breeder }}
                                        </SelectItem>
                                    </div>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.breeder_id" />
                        </div>

                        <!-- Jenis Koi -->
                        <div class="grid gap-2">
                            <Label>Jenis Koi</Label>
                            <Select v-model="form.jenis_koi_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih jenis koi (opsional)" />
                                </SelectTrigger>
                                <SelectContent class="w-[--radix-select-trigger-width] min-w-[20rem] p-0">
                                    <div class="sticky top-0 border-b bg-background p-2">
                                        <Input v-model="jenisSearch" placeholder="Cari jenis koi..." class="h-8" @keydown.stop />
                                    </div>
                                    <div class="max-h-48 overflow-y-auto">
                                        <div v-if="filteredJenisKois.length === 0" class="px-2 py-2 text-sm text-muted-foreground">Jenis koi tidak ditemukan</div>
                                        <SelectItem v-for="j in filteredJenisKois" :key="j.id" :value="String(j.id)">
                                            {{ j.nama_jenis }}
                                        </SelectItem>
                                    </div>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.jenis_koi_id" />
                        </div>

                        <!-- Code & Name -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="code">Kode Koi <span class="text-red-500">*</span></Label>
                                <Input id="code" v-model="form.code" placeholder="Contoh: KOI-001" />
                                <InputError :message="form.errors.code" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="name">Nama Koi <span class="text-red-500">*</span></Label>
                                <Input id="name" v-model="form.name" placeholder="Nama koi" />
                                <InputError :message="form.errors.name" />
                            </div>
                        </div>

                        <!-- Data Fisik -->
                        <div class="rounded-lg border border-border bg-muted/30 p-4 space-y-3">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Data Fisik</h3>
                            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                                <div class="grid gap-2">
                                    <Label for="age" class="text-xs">Age (bulan)</Label>
                                    <Input id="age" v-model="form.age" type="number" min="0" placeholder="0" />
                                    <InputError :message="form.errors.age" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="size" class="text-xs">Size (cm)</Label>
                                    <Input id="size" v-model="form.size" type="number" min="0" step="0.01" placeholder="0.00" />
                                    <InputError :message="form.errors.size" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="box_qty" class="text-xs">Box QTY</Label>
                                    <Input id="box_qty" v-model="form.box_qty" type="number" min="0" placeholder="0" />
                                    <InputError :message="form.errors.box_qty" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="weight" class="text-xs">Weight (kg)</Label>
                                    <Input id="weight" v-model="form.weight" type="number" min="0" step="0.001" placeholder="0.000" />
                                    <InputError :message="form.errors.weight" />
                                </div>
                            </div>
                        </div>

                        <!-- Foto Koi -->
                        <div class="rounded-lg border border-sidebar-border/70 bg-muted/30 p-4">
                            <h3 class="mb-3 font-semibold">Foto Koi</h3>
                            <div v-if="fotoPreviews.length > 0" class="mb-3 flex flex-wrap gap-3">
                                <div v-for="(preview, i) in fotoPreviews" :key="i"
                                    class="relative h-24 w-24 overflow-hidden rounded-lg border border-border group">
                                    <img :src="preview.url" :alt="`Preview ${i + 1}`" class="h-full w-full object-cover" />
                                    <button type="button" @click="removeNewImage(i)"
                                        class="absolute inset-0 flex items-center justify-center bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <X class="h-5 w-5 text-white" />
                                    </button>
                                </div>
                            </div>
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border-2 border-dashed border-border px-4 py-3 text-sm text-muted-foreground hover:border-primary/50 hover:text-foreground transition-colors">
                                <ImagePlus class="h-5 w-5 flex-shrink-0" />
                                <span>Pilih foto (jpg, jpeg, png, webp · maks. 2 MB per file · bisa pilih beberapa)</span>
                                <input type="file" accept="image/jpg,image/jpeg,image/png,image/webp" multiple class="sr-only" @change="handleImagesChange" />
                            </label>
                            <InputError :message="(form.errors as any)['images.*']" class="mt-1" />
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════
                     SECTION 2: PEMBELIAN
                ═══════════════════════════════════════════════ -->
                <div class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                    <!-- Section header + toggle enable -->
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-base font-semibold">Pembelian</h2>
                            <p class="text-xs text-muted-foreground">Tambahkan data pembelian untuk koi ini (opsional).</p>
                        </div>
                        <label class="flex cursor-pointer items-center gap-2 select-none">
                            <span class="text-sm text-muted-foreground">Aktifkan</span>
                            <div class="relative">
                                <input type="checkbox" v-model="pembelianEnabled" class="sr-only peer" />
                                <div class="h-6 w-11 rounded-full bg-muted peer-checked:bg-primary transition-colors"></div>
                                <div class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></div>
                            </div>
                        </label>
                    </div>

                    <div v-if="pembelianEnabled" class="space-y-5">
                        <!-- Mode toggle -->
                        <div class="flex rounded-lg border border-border overflow-hidden text-sm font-medium w-fit">
                            <button type="button"
                                :class="pembelianMode === 'existing' ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground hover:bg-muted'"
                                class="px-4 py-2 transition-colors"
                                @click="pembelianMode = 'existing'">
                                Pilih yang Ada
                            </button>
                            <button type="button"
                                :class="pembelianMode === 'create' ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground hover:bg-muted'"
                                class="px-4 py-2 border-l border-border transition-colors"
                                @click="pembelianMode = 'create'">
                                Buat Baru
                            </button>
                        </div>

                        <!-- Select Existing -->
                        <div v-if="pembelianMode === 'existing'" class="space-y-3">
                            <div class="grid gap-2">
                                <Label>Pilih Pembelian <span class="text-red-500">*</span></Label>
                                <Select v-model="form.pembelian_existing_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih data pembelian..." />
                                    </SelectTrigger>
                                    <SelectContent class="w-[--radix-select-trigger-width] p-0">
                                        <div class="max-h-56 overflow-y-auto">
                                            <SelectItem v-for="p in pembelians" :key="p.id" :value="String(p.id)">
                                                {{ pembelianLabel(p) }}
                                            </SelectItem>
                                        </div>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.pembelian_existing_id" />
                            </div>

                            <!-- Info box selected pembelian -->
                            <div v-if="form.pembelian_existing_id" class="rounded-lg bg-blue-50 dark:bg-blue-950 p-4 text-sm">
                                <template v-if="pembelians.find(p => p.id === Number(form.pembelian_existing_id))">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <p class="text-xs text-muted-foreground">Tanggal</p>
                                            <p class="font-medium">{{ fmtDate(pembelians.find(p => p.id === Number(form.pembelian_existing_id))!.date) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-muted-foreground">Qty</p>
                                            <p class="font-medium">{{ pembelians.find(p => p.id === Number(form.pembelian_existing_id))!.qty }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-muted-foreground">Harga JPY</p>
                                            <p class="font-medium">{{ fmtJPY(pembelians.find(p => p.id === Number(form.pembelian_existing_id))!.price_jpy) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-muted-foreground">Harga IDR</p>
                                            <p class="font-medium">{{ fmtIDR(pembelians.find(p => p.id === Number(form.pembelian_existing_id))!.price_idr) }}</p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Create New -->
                        <div v-if="pembelianMode === 'create'" class="space-y-4">
                            <!-- Exchange rate info -->
                            <div class="rounded-lg bg-muted/50 px-4 py-2 text-xs flex items-center gap-2">
                                <span v-if="exchangeRateLoading" class="text-muted-foreground">Memuat kurs JPY→IDR...</span>
                                <span v-else-if="exchangeRateError" class="text-orange-500">Gagal memuat kurs (menggunakan fallback {{ exchangeRate }})</span>
                                <span v-else class="text-muted-foreground">Kurs JPY→IDR: <strong>Rp {{ exchangeRate.toLocaleString('id-ID') }}</strong></span>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="pem_date">Tanggal Pembelian <span class="text-red-500">*</span></Label>
                                    <Input id="pem_date" v-model="form.pembelian_date" type="date" />
                                    <InputError :message="form.errors.pembelian_date" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="pem_qty">Qty <span class="text-red-500">*</span></Label>
                                    <Input id="pem_qty" v-model="form.pembelian_qty" type="number" min="1" placeholder="1" />
                                    <InputError :message="form.errors.pembelian_qty" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="pem_jpy">Harga Beli JPY <span class="text-red-500">*</span></Label>
                                    <Input id="pem_jpy" v-model="form.pembelian_price_jpy" type="number" min="0" placeholder="0" />
                                    <InputError :message="form.errors.pembelian_price_jpy" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="pem_idr">
                                        Harga Beli IDR <span class="text-red-500">*</span>
                                        <span class="ml-1 text-xs font-normal text-muted-foreground">(auto dari kurs)</span>
                                    </Label>
                                    <Input id="pem_idr" v-model="form.pembelian_price_idr" type="number" min="0" placeholder="0"
                                        @input="isPriceIdrManual = true" @focus="isPriceIdrManual = true" />
                                    <InputError :message="form.errors.pembelian_price_idr" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Disabled state info -->
                    <p v-else class="text-sm text-muted-foreground italic">Aktifkan untuk menambahkan data pembelian sekaligus.</p>
                </div>

                <!-- ═══════════════════════════════════════════
                     SECTION 3: KATALOG KOI
                ═══════════════════════════════════════════════ -->
                <div class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                    <!-- Section header + toggle enable -->
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-base font-semibold">Katalog Koi</h2>
                            <p class="text-xs text-muted-foreground">Tambahkan entri katalog untuk koi ini (opsional).</p>
                        </div>
                        <label class="flex cursor-pointer items-center gap-2 select-none">
                            <span class="text-sm text-muted-foreground">Aktifkan</span>
                            <div class="relative">
                                <input type="checkbox" v-model="katalogEnabled" class="sr-only peer" />
                                <div class="h-6 w-11 rounded-full bg-muted peer-checked:bg-primary transition-colors"></div>
                                <div class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></div>
                            </div>
                        </label>
                    </div>

                    <div v-if="katalogEnabled" class="space-y-5">
                        <!-- Mode toggle -->
                        <div class="flex rounded-lg border border-border overflow-hidden text-sm font-medium w-fit">
                            <button type="button"
                                :class="katalogMode === 'existing' ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground hover:bg-muted'"
                                class="px-4 py-2 transition-colors"
                                @click="katalogMode = 'existing'">
                                Pilih yang Ada
                            </button>
                            <button type="button"
                                :class="katalogMode === 'create' ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground hover:bg-muted'"
                                class="px-4 py-2 border-l border-border transition-colors"
                                @click="katalogMode = 'create'">
                                Buat Baru
                            </button>
                        </div>

                        <!-- Select Existing -->
                        <div v-if="katalogMode === 'existing'" class="space-y-3">
                            <p class="text-xs text-muted-foreground">Hubungkan koi baru ini ke entri katalog yang sudah ada (koi_id pada katalog tersebut akan diperbarui).</p>
                            <div class="grid gap-2">
                                <Label>Pilih Katalog <span class="text-red-500">*</span></Label>
                                <Select v-model="form.katalog_existing_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih entri katalog..." />
                                    </SelectTrigger>
                                    <SelectContent class="w-[--radix-select-trigger-width] p-0">
                                        <div class="max-h-56 overflow-y-auto">
                                            <SelectItem v-for="k in katalogs" :key="k.id" :value="String(k.id)">
                                                {{ katalogLabel(k) }}
                                            </SelectItem>
                                        </div>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.katalog_existing_id" />
                            </div>
                        </div>

                        <!-- Create New -->
                        <div v-if="katalogMode === 'create'" class="space-y-4">
                            <!-- Warning jika pembelian tidak aktif -->
                            <div v-if="katalogNeedsPembelian" class="rounded-lg border border-orange-200 bg-orange-50 dark:border-orange-800 dark:bg-orange-950 px-4 py-3 text-sm text-orange-700 dark:text-orange-300">
                                Aktifkan section <strong>Pembelian</strong> di atas (pilih yang ada atau buat baru) sebelum membuat katalog baru — pembelian diperlukan untuk mengisi data harga beli.
                            </div>

                            <template v-if="!katalogNeedsPembelian">
                                <!-- Info harga beli dari pembelian -->
                                <div v-if="selectedPembelianForKatalog" class="rounded-lg bg-muted/50 p-4 text-sm">
                                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Harga Beli (dari Pembelian)</p>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <p class="text-xs text-muted-foreground">Harga JPY</p>
                                            <p class="font-medium">{{ fmtJPY(selectedPembelianForKatalog.price_jpy) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-muted-foreground">Harga IDR</p>
                                            <p class="font-medium">{{ fmtIDR(selectedPembelianForKatalog.price_idr) }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="rounded-lg bg-muted/50 p-3 text-xs text-muted-foreground italic">
                                    Pilih atau isi data pembelian di atas untuk menampilkan harga beli.
                                </div>

                                <!-- Tanggal & Qty -->
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="grid gap-2">
                                        <Label for="kat_tanggal">Tanggal Katalog <span class="text-red-500">*</span></Label>
                                        <Input id="kat_tanggal" v-model="form.katalog_tanggal" type="date" />
                                        <InputError :message="form.errors.katalog_tanggal" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="kat_qty">Qty/Stok <span class="text-red-500">*</span></Label>
                                        <Input id="kat_qty" v-model="form.katalog_qty" type="number" min="1" placeholder="1" />
                                        <InputError :message="form.errors.katalog_qty" />
                                    </div>
                                </div>

                                <!-- Harga Jual -->
                                <div class="grid gap-4 sm:grid-cols-3">
                                    <div class="grid gap-2">
                                        <Label for="kat_sell_jpy">Harga Jual JPY <span class="text-red-500">*</span></Label>
                                        <Input id="kat_sell_jpy" v-model="form.katalog_sell_jpy" type="number" min="0" placeholder="0" />
                                        <InputError :message="form.errors.katalog_sell_jpy" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="kat_kurs">Kurs (IDR/JPY) <span class="text-red-500">*</span></Label>
                                        <Input id="kat_kurs" v-model="form.katalog_kurs" type="number" min="0" placeholder="0" />
                                        <InputError :message="form.errors.katalog_kurs" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="kat_sell_idr">
                                            Harga Jual IDR <span class="text-red-500">*</span>
                                            <span class="ml-1 text-xs font-normal text-muted-foreground">(auto JPY×kurs)</span>
                                        </Label>
                                        <Input id="kat_sell_idr" v-model="form.katalog_sell_idr" type="number" min="0" placeholder="0" />
                                        <InputError :message="form.errors.katalog_sell_idr" />
                                    </div>
                                </div>

                                <!-- Fee -->
                                <div class="grid gap-2 sm:w-1/3">
                                    <Label for="kat_fee">Fee IDR</Label>
                                    <Input id="kat_fee" v-model="form.katalog_fee" type="number" min="0" placeholder="0" />
                                    <InputError :message="form.errors.katalog_fee" />
                                </div>

                                <!-- Perhitungan otomatis -->
                                <div class="rounded-lg bg-muted/60 p-4 space-y-2 text-sm">
                                    <p class="font-semibold text-xs uppercase tracking-wide text-muted-foreground mb-3">Perhitungan Otomatis</p>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Harga Beli IDR:</span>
                                        <span class="font-medium">{{ fmtIDR(selectedPembelianForKatalog?.price_idr ?? 0) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Harga Jual IDR:</span>
                                        <span class="font-medium">{{ fmtIDR(Number(form.katalog_sell_idr) || 0) }}</span>
                                    </div>
                                    <div class="flex justify-between border-t border-border pt-2">
                                        <span class="text-muted-foreground">Margin:</span>
                                        <span class="font-medium">{{ fmtIDR(katalogMargin) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Fee:</span>
                                        <span class="font-medium">{{ fmtIDR(Number(form.katalog_fee) || 0) }}</span>
                                    </div>
                                    <div class="flex justify-between border-t border-border pt-2"
                                        :class="katalogProfitPositive ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                        <span class="font-semibold">Profit:</span>
                                        <span class="font-bold">{{ fmtIDR(katalogProfit) }} ({{ katalogProfitPct }}%)</span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Disabled state info -->
                    <p v-else class="text-sm text-muted-foreground italic">Aktifkan untuk menambahkan entri katalog sekaligus.</p>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                    </Button>
                    <Link href="/master-data/koi" class="text-sm text-muted-foreground hover:text-foreground">
                        Batal
                    </Link>
                </div>

            </form>
        </div>
    </AppLayout>
</template>
