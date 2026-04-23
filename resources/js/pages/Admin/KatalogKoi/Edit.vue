<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, onMounted, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import { useSwal } from '@/composables/useSwal'
import type { BreadcrumbItem } from '@/types'

interface Katalog {
    id: number
    tanggal: string
    qty: number
    sell_jpy: number
    kurs: number
    sell_idr: number
    fee: number
    stock_jpy: number
    stock_idr: number
    koi: { id: number; code: string; name: string } | null
    pembelian: { id: number; date: string; price_jpy: number; price_idr: number } | null
}

const props = defineProps<{ katalog: Katalog }>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data', href: '#' },
    { title: 'Data Koi', href: '/master-data/koi?tab=katalog' },
    { title: 'Edit Katalog', href: '#' },
]

// ─── Exchange rate ─────────────────────────────────────────
const exchangeRate        = ref(0)
const exchangeRateLoading = ref(false)
const exchangeRateError   = ref(false)

// ─── Form ─────────────────────────────────────────────────
const form = useForm({
    tanggal:      props.katalog.tanggal ? String(props.katalog.tanggal).slice(0, 10) : '',
    qty:          String(props.katalog.qty),
    sell_jpy:     String(Math.round(Number(props.katalog.sell_jpy))),
    kurs:         String(Math.round(Number(props.katalog.kurs))),
    sell_idr:     String(Math.round(Number(props.katalog.sell_idr))),
    fee:          String(Math.round(Number(props.katalog.fee))),
    stock_jpy:    String(props.katalog.stock_jpy),
    stock_idr:    String(props.katalog.stock_idr),
    koi_id:       String(props.katalog.koi?.id ?? ''),
    pembelian_id: String(props.katalog.pembelian?.id ?? ''),
})

function applyLatestRate() {
    if (exchangeRate.value > 0) {
        form.kurs = String(exchangeRate.value)
        if (form.sell_jpy)
            form.sell_idr = String(Math.round(Number(form.sell_jpy) * exchangeRate.value))
    }
}

async function fetchExchangeRate() {
    exchangeRateLoading.value = true; exchangeRateError.value = false
    try {
        const res = await fetch('https://api.exchangerate-api.com/v4/latest/JPY')
        if (!res.ok) throw new Error()
        const data = await res.json()
        exchangeRate.value = Math.round(Number(data?.rates?.IDR ?? 0))
        if (!exchangeRate.value) throw new Error()
    } catch { exchangeRateError.value = true; exchangeRate.value = 110 }
    finally {
        exchangeRateLoading.value = false
        applyLatestRate()
    }
}

onMounted(fetchExchangeRate)

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

// auto-calc sell_idr from sell_jpy * kurs
function onSellJpyInput(e: Event) {
    form.sell_jpy = parseInput(e)
    if (form.sell_jpy && form.kurs)
        form.sell_idr = String(Math.round(Number(form.sell_jpy) * Number(form.kurs)))
}
function onKursInput(e: Event) {
    form.kurs = parseInput(e)
    if (form.sell_jpy && form.kurs)
        form.sell_idr = String(Math.round(Number(form.sell_jpy) * Number(form.kurs)))
}

// ─── Profit preview ───────────────────────────────────────
const margin  = computed(() => (Number(form.sell_idr) || 0) - (props.katalog.stock_idr ?? 0))
const profit  = computed(() => margin.value - (Number(form.fee) || 0))
const profitOk = computed(() => profit.value >= 0)
const profitPct = computed(() => {
    const b = props.katalog.stock_idr ?? 0
    return b > 0 ? (profit.value / b * 100).toFixed(2) : '0.00'
})

// ─── Submit ───────────────────────────────────────────────
function submit() {
    form.post(`/admin/katalog-koi/${props.katalog.id}?_method=PUT`, {
        preserveScroll: true,
        onSuccess: () => useSwal().fire({ icon: 'success', title: 'Berhasil!', text: 'Katalog berhasil diperbarui.', timer: 3000, showConfirmButton: false }),
        onError:   () => useSwal().fire({ icon: 'error',   title: 'Gagal!',    text: 'Periksa kembali isian.',       timer: 3000, showConfirmButton: false }),
    })
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Edit Katalog Koi" />

        <div class="space-y-6 p-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Edit Katalog Koi</h1>
                    <p class="text-sm text-muted-foreground">Perbarui data katalog koi.</p>
                </div>
                <Link
                    href="/master-data/koi?tab=katalog"
                    class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted"
                >
                    Kembali
                </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- Info Koi & Pembelian (read-only) -->
                <div class="rounded-xl border border-sidebar-border/70 bg-background p-6 space-y-4">
                    <h2 class="text-base font-semibold">Informasi Koi & Pembelian</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-lg bg-muted/50 p-4 space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Koi</p>
                            <p class="font-medium">{{ katalog.koi ? katalog.koi.code + ' — ' + katalog.koi.name : '-' }}</p>
                        </div>
                        <div v-if="katalog.pembelian" class="rounded-lg bg-muted/50 p-4 space-y-2 text-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Pembelian</p>
                            <div class="grid grid-cols-2 gap-2">
                                <div><p class="text-muted-foreground">Tanggal</p><p class="font-medium">{{ fmtDate(katalog.pembelian.date) }}</p></div>
                                <div><p class="text-muted-foreground">Harga Beli JPY</p><p class="font-medium">{{ fmtJPY(katalog.pembelian.price_jpy) }}</p></div>
                                <div><p class="text-muted-foreground">Harga Beli IDR</p><p class="font-medium">{{ fmtIDR(katalog.pembelian.price_idr) }}</p></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form utama -->
                <div class="rounded-xl border border-sidebar-border/70 bg-background p-6 space-y-4">
                    <h2 class="text-base font-semibold">Data Katalog</h2>

                    <!-- Kurs info banner -->
                    <div class="flex items-center justify-between rounded-md bg-muted/50 px-3 py-1.5 text-xs text-muted-foreground">
                        <span v-if="exchangeRateLoading">Memuat kurs JPY→IDR...</span>
                        <span v-else-if="exchangeRateError" class="text-orange-500">Kurs fallback: Rp {{ exchangeRate.toLocaleString('id-ID') }}</span>
                        <span v-else>Kurs saat ini JPY→IDR: <strong>Rp {{ exchangeRate.toLocaleString('id-ID') }}</strong></span>
                        <button v-if="!exchangeRateLoading && exchangeRate > 0" type="button"
                            class="ml-3 rounded border border-border bg-background px-2 py-0.5 text-xs hover:bg-muted"
                            @click="applyLatestRate">
                            ↻ Gunakan kurs terbaru
                        </button>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="tanggal">Tanggal *</Label>
                            <Input id="tanggal" v-model="form.tanggal" type="date" />
                            <InputError :message="form.errors.tanggal" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="qty">Qty *</Label>
                            <Input id="qty" v-model="form.qty" type="number" min="1" placeholder="1" />
                            <InputError :message="form.errors.qty" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="sell_jpy">Harga Jual JPY *</Label>
                            <Input id="sell_jpy" type="text" inputmode="numeric" :value="fmtInput(form.sell_jpy)" @input="onSellJpyInput" placeholder="0" />
                            <InputError :message="form.errors.sell_jpy" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="kurs">Kurs (IDR/JPY) *</Label>
                            <Input id="kurs" type="text" inputmode="numeric" :value="fmtInput(form.kurs)" @input="onKursInput" placeholder="0" />
                            <InputError :message="form.errors.kurs" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="sell_idr">Harga Jual IDR * <span class="text-xs font-normal text-muted-foreground">(auto)</span></Label>
                            <Input id="sell_idr" type="text" inputmode="numeric" :value="fmtInput(form.sell_idr)" @input="e => form.sell_idr = parseInput(e)" placeholder="0" />
                            <InputError :message="form.errors.sell_idr" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="fee">Fee IDR</Label>
                            <Input id="fee" type="text" inputmode="numeric" :value="fmtInput(form.fee)" @input="e => form.fee = parseInput(e)" placeholder="0" />
                            <InputError :message="form.errors.fee" />
                        </div>
                    </div>

                    <!-- Perhitungan -->
                    <div class="rounded-lg bg-muted/50 p-4 text-sm space-y-1.5">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Perhitungan</p>
                        <div class="flex justify-between"><span class="text-muted-foreground">Harga Beli IDR:</span><span>{{ fmtIDR(katalog.stock_idr) }}</span></div>
                        <div class="flex justify-between"><span class="text-muted-foreground">Harga Jual IDR:</span><span>{{ fmtIDR(Number(form.sell_idr) || 0) }}</span></div>
                        <div class="flex justify-between border-t border-border pt-1.5"><span class="text-muted-foreground">Margin:</span><span>{{ fmtIDR(margin) }}</span></div>
                        <div class="flex justify-between"><span class="text-muted-foreground">Fee:</span><span>{{ fmtIDR(Number(form.fee) || 0) }}</span></div>
                        <div class="flex justify-between border-t border-border pt-1.5 font-semibold"
                            :class="profitOk ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                            <span>Profit:</span><span>{{ fmtIDR(profit) }} ({{ profitPct }}%)</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Menyimpan...' : 'Simpan' }}</Button>
                    <Link href="/master-data/koi?tab=katalog" class="text-sm text-muted-foreground hover:text-foreground">Batal</Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
