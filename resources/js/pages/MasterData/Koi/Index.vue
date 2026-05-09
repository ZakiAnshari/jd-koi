<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { X, ImagePlus, Eye, Pencil, Trash2, ExternalLink, ChevronDown, Check, Search, FileText } from 'lucide-vue-next'
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
type Customer = { id: number; name: string }
type Invoice  = { sale_invoice: string; koi_count: number }
type KoiImage = { id: number; koi_id: number; image_path: string; sort_order: number; url: string }
type Koi = {
    id: number; breeder_id: number; jenis_koi_id: number | null
    code: string; name: string; description?: string | null; age: number | null; size: number | null
    box_qty: number | null; weight: number | null; total_weight: number | null
    breeder?: Breeder; jenis_koi?: JenisKoi; customer?: Customer | null
    customer_id: number | null; sale_status: string | null; created_at: string
    images?: KoiImage[]
    sale_invoice?: string | null; sale_tanggal?: string | null; sale_shipped_date?: string | null
    sale_qty?: number | null; unit_price?: string | null; buy_jpy?: string | null
    kurs?: string | null; sell_idr?: string | null; stock_price?: string | null; fee?: string | null
    shipping_cost?: string | null; shipping_qty?: number | null; shipping_batch?: string | null; shipping_note?: string | null; shipping_date?: string | null
    azukari_period?: string | null
    azukari_qty?: number | null
    azukari_price_jpy?: number | null
}

// ─── Page props ───────────────────────────────────────────
const page      = usePage()
const kois            = computed(() => (page.props.kois            as Koi[]      | null) ?? [])
const breeders        = computed(() => (page.props.breeders        as Breeder[]  | null) ?? [])
const jenisKois       = computed(() => (page.props.jenisKois       as JenisKoi[] | null) ?? [])
const customers       = computed(() => (page.props.customers       as Customer[] | null) ?? [])
const invoices        = computed(() => (page.props.invoices        as Invoice[]  | null) ?? [])
const shippingBatches = computed(() => (page.props.shippingBatches as string[]   | null) ?? [])
const extraBatches    = ref<string[]>([])
const allBatchOptions = computed(() => [...shippingBatches.value, ...extraBatches.value.filter(b => !shippingBatches.value.includes(b))])

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data', href: '#' },
    { title: 'Data Koi', href: '/master-data/koi' },
]

// ─── DataTable ────────────────────────────────────────────
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

function getBreederName(koi: Koi) {
    return koi.breeder?.name_breeder ?? breeders.value.find(b => b.id === koi.breeder_id)?.name_breeder ?? '-'
}
function getJenisName(koi: Koi) {
    if (koi.jenis_koi?.nama_jenis) return koi.jenis_koi.nama_jenis
    if (!koi.jenis_koi_id) return '-'
    return jenisKois.value.find(j => j.id === koi.jenis_koi_id)?.nama_jenis ?? '-'
}
function getFirstImage(koi: Koi): string | null {
    if (koi.images && koi.images.length > 0) {
        return koi.images[0].url
    }
    return null
}
function openImageModal(koi: Koi) {
    selectedKoiImages.value = koi.images || []
    showImageModal.value = true
}
function quickUpdateStatus(id: number, status: string) {
    router.put(`/master-data/koi/${id}/status`, { sale_status: status || null }, {
        preserveScroll: true,
        onSuccess: () => {
            useSwal().fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Status berhasil diperbarui.',
                timer: 2000,
                showConfirmButton: false,
            })
        },
    })
}

// ─── Modal state ──────────────────────────────────────────
const showModal      = ref(false)
const showImageModal = ref(false)
const selectedKoiImages = ref<KoiImage[]>([])

const breederSearch   = ref('')
const jenisSearch     = ref('')
const customerSearch  = ref('')
const invoiceDropdownOpen = ref(false)
const invoiceSearch       = ref('')

const filteredInvoiceOptions = computed(() => {
    const q = (form.sale_invoice as string ?? '').trim().toLowerCase()
    if (!q) return invoices.value
    return invoices.value.filter(i => i.sale_invoice.toLowerCase().includes(q))
})

function selectInvoice(val: string | null) {
    form.sale_invoice = val ?? ''
    invoiceDropdownOpen.value = false
    invoiceSearch.value = ''
}

function closeInvoiceDropdown(e: FocusEvent) {
    if (!(e.relatedTarget as HTMLElement)?.closest?.('.invoice-dropdown-panel')) {
        invoiceDropdownOpen.value = false
    }
}

// ─── Edit Modal ───────────────────────────────────────────
const showEditModal       = ref(false)
const editingKoi          = ref<Koi | null>(null)
const editExistingImages  = ref<KoiImage[]>([])
const editFotoPreviews    = ref<{ file: File; url: string }[]>([])
const editBreederSearch   = ref('')
const editJenisSearch     = ref('')
const editCustomerSearch  = ref('')
const editFeePct          = ref<string | null>(null)
const editKursInput       = ref('')
const editKursIsManual    = ref(false)
const editInvoiceDropdownOpen = ref(false)

const editFilteredBreeders  = computed(() => !editBreederSearch.value  ? breeders.value  : breeders.value.filter(b => b.name_breeder.toLowerCase().includes(editBreederSearch.value.toLowerCase())))
const editFilteredJenisKois = computed(() => !editJenisSearch.value    ? jenisKois.value : jenisKois.value.filter(j => j.nama_jenis.toLowerCase().includes(editJenisSearch.value.toLowerCase())))
const editFilteredCustomers = computed(() => !editCustomerSearch.value ? customers.value : customers.value.filter(c => c.name.toLowerCase().includes(editCustomerSearch.value.toLowerCase())))
const editFilteredInvoices  = computed(() => {
    const q = (editForm.sale_invoice ?? '').trim().toLowerCase()
    if (!q) return invoices.value
    return invoices.value.filter(i => i.sale_invoice.toLowerCase().includes(q))
})

const editModalQty             = ref('')
const editStockPriceDisplay    = ref('')
const editUnitPriceDisplay     = ref('')
const editShippingCostDisplay2 = ref('')
const editAzukariPriceDisplay  = ref('')

const editForm = useForm({
    breeder_id: '', jenis_koi_id: '', code: '', name: '', description: '',
    age: '', size: '', box_qty: '', weight: '', total_weight: '',
    images: [] as File[], delete_images: [] as number[],
    customer_id: '', sale_tanggal: '', sale_qty: '', sale_invoice: '', azukari_period: '', azukari_qty: '', azukari_price_jpy: '',
    buy_jpy: '', unit_price: '', kurs: '', sell_idr: '', stock_price: '', fee: '0',
    sale_status: '', sale_shipped_date: '',
    shipping_cost: '', shipping_qty: '', shipping_batch: '', shipping_note: '', shipping_date: '',
})

function openEditModal(koi: Koi) {
    editingKoi.value          = koi
    editExistingImages.value  = koi.images ? [...koi.images] : []
    editFotoPreviews.value    = []
    editBreederSearch.value   = ''
    editJenisSearch.value     = ''
    editCustomerSearch.value  = ''
    editInvoiceDropdownOpen.value = false

    editForm.breeder_id        = String(koi.breeder_id)
    editForm.jenis_koi_id      = koi.jenis_koi_id ? String(koi.jenis_koi_id) : ''
    editForm.code              = koi.code
    editForm.name              = koi.name
    editForm.description       = koi.description ?? ''
    editForm.age               = koi.age != null ? String(koi.age) : ''
    editForm.size              = koi.size != null ? String(koi.size) : ''
    editForm.box_qty           = koi.box_qty != null ? String(koi.box_qty) : ''
    editForm.weight            = koi.weight != null ? String(koi.weight) : ''
    editForm.total_weight      = koi.total_weight != null ? String(koi.total_weight) : ''
    editForm.images            = []
    editForm.delete_images     = []
    editForm.customer_id       = koi.customer_id ? String(koi.customer_id) : ''
    editForm.sale_tanggal      = koi.sale_tanggal ? String(koi.sale_tanggal).substring(0, 10) : ''
    editForm.sale_shipped_date = ''
    editForm.sale_qty          = koi.sale_qty != null ? String(koi.sale_qty) : '1'
    editForm.unit_price        = koi.unit_price != null ? String(Math.round(Number(koi.unit_price))) : ''
    editForm.buy_jpy           = koi.buy_jpy != null ? String(Math.round(Number(koi.buy_jpy))) : ''
    editForm.sell_idr          = koi.sell_idr != null ? String(Math.round(Number(koi.sell_idr))) : ''
    editForm.stock_price       = koi.stock_price != null ? String(Math.round(Number(koi.stock_price))) : ''
    editForm.fee               = koi.fee != null ? String(Math.round(Number(koi.fee))) : '0'
    editForm.sale_status       = koi.sale_status ?? ''
    editForm.sale_invoice      = koi.sale_invoice ?? ''
    editForm.azukari_period    = koi.azukari_period ?? ''
    editForm.azukari_qty       = koi.azukari_qty != null ? String(koi.azukari_qty) : ''
    editForm.azukari_price_jpy = koi.azukari_price_jpy != null ? String(koi.azukari_price_jpy) : ''
    editForm.shipping_cost     = koi.shipping_cost != null ? String(Number(koi.shipping_cost)) : ''
    editForm.shipping_qty      = koi.shipping_qty != null ? String(koi.shipping_qty) : ''
    editForm.shipping_batch    = koi.shipping_batch ?? ''
    editForm.shipping_note     = koi.shipping_note ?? ''
    editForm.shipping_date     = koi.shipping_date ? String(koi.shipping_date).substring(0, 10) : ''
    editModalQty.value             = koi.sale_qty != null ? String(koi.sale_qty) : ''
    editStockPriceDisplay.value    = addDots(koi.stock_price)
    editUnitPriceDisplay.value     = addDots(koi.unit_price)
    editShippingCostDisplay2.value = addDots(koi.shipping_cost)
    editAzukariPriceDisplay.value  = addDots(koi.azukari_price_jpy)

    const existingKurs = koi.kurs ? Number(koi.kurs) : 0
    editKursIsManual.value = existingKurs > 0
    editKursInput.value    = existingKurs > 0 ? String(existingKurs) : String(exchangeRate.value)
    editForm.kurs          = editKursInput.value

    const fee = Number(koi.fee ?? 0); const sellIdr = Number(koi.sell_idr ?? 0)
    editFeePct.value = fee > 0 && sellIdr > 0 ? String(Math.round(fee / sellIdr * 100)) : null

    showEditModal.value = true
}

watch(editKursInput, v => { editForm.kurs = v })
watch([() => editForm.weight, () => editForm.box_qty], ([w, q]) => {
    const weight = Number(w) || 0; const qty = Number(q) || 0
    editForm.total_weight = weight > 0 && qty > 0 ? String(parseFloat((weight * qty).toFixed(3))) : ''
})
watch([() => editForm.unit_price, () => editForm.sale_qty, editKursInput], ([price, qty, kurs]) => {
    const p = Number(price) || 0; const q = Number(qty) || 1; const k = Number(kurs) || 0
    editForm.sell_idr = p > 0 && k > 0 ? String(Math.round(p * q * k)) : editForm.sell_idr
})

const editModalPriceIdr  = computed(() => { const m = Number(editForm.stock_price) || 0; const k = Number(editKursInput.value) || 0; return m > 0 && k > 0 ? Math.round(m * k) : null })
const editTotalModalIdr  = computed(() => { const m = editModalPriceIdr.value || 0; const q = Number(editModalQty.value) || 0; return m > 0 && q > 0 ? Math.round(m * q) : null })
const editModalTerjualIdr= computed(() => { const m = editModalPriceIdr.value || 0; const q = Number(editForm.sale_qty) || 1; return m > 0 ? Math.round(m * q) : null })
const editUnitPriceIdr   = computed(() => { const p = Number(editForm.unit_price) || 0; const k = Number(editKursInput.value) || 0; return p > 0 && k > 0 ? Math.round(p * k) : null })
const editAzukariPriceIdr = computed(() => { const p = Number(editForm.azukari_price_jpy) || 0; const k = Number(editKursInput.value) || 0; return p > 0 && k > 0 ? Math.round(p * k) : null })
const editSalesPriceJpy  = computed(() => { const p = Number(editForm.unit_price) || 0; const q = Number(editForm.sale_qty) || 1; return p > 0 ? p * q : null })
const editSalesPriceIdr  = computed(() => { const jpy = editSalesPriceJpy.value; const k = Number(editKursInput.value) || 0; return jpy && k > 0 ? Math.round(jpy * k) : null })
const editShippingCostIdr= computed(() => { const c = Number(editForm.shipping_cost) || 0; return c > 0 ? c : null })
const editShippingPerEkor= computed(() => { const cIdr = editShippingCostIdr.value || 0; const q = Number(editForm.shipping_qty) || 0; return cIdr > 0 && q > 0 ? Math.round(cIdr / q) : null })
const editShippingKena   = computed(() => { const p = editShippingPerEkor.value || 0; const q = Number(editForm.sale_qty) || 1; return p > 0 ? Math.round(p * q) : null })
const editProfit         = computed(() => (editSalesPriceIdr.value ?? 0) - (editModalTerjualIdr.value ?? 0) + Number(editForm.fee || 0))
const editProfitPositive = computed(() => editProfit.value >= 0)

function onEditStockPriceChange(val: string | number)    { const r = String(val).replace(/\D/g, ''); editForm.stock_price = r; editStockPriceDisplay.value = addDots(r) }
function onEditUnitPriceChange(val: string | number)     { const r = String(val).replace(/\D/g, ''); editForm.unit_price = r; editUnitPriceDisplay.value = addDots(r) }
function onEditShippingCostChange(val: string | number)  { const r = String(val).replace(/\D/g, ''); editForm.shipping_cost = r; editShippingCostDisplay2.value = addDots(r) }
function onEditAzukariPriceChange(val: string | number)  { const r = String(val).replace(/\D/g, ''); editForm.azukari_price_jpy = r; editAzukariPriceDisplay.value = addDots(r) }
function onEditFeeSelect(val: unknown) {
    editFeePct.value = val !== null ? String(val) : null
    editForm.fee = String(Math.round((editSalesPriceIdr.value ?? 0) * (Number(val) / 100)))
}
function handleEditImagesChange(e: Event) {
    const input = e.target as HTMLInputElement
    if (!input.files?.length) return
    const newFiles = Array.from(input.files)
    editForm.images = [...editForm.images, ...newFiles]
    newFiles.forEach(file => { const r = new FileReader(); r.onload = ev => editFotoPreviews.value.push({ file, url: ev.target?.result as string }); r.readAsDataURL(file) })
    input.value = ''
}
function removeEditNewImage(i: number) { editFotoPreviews.value.splice(i, 1); editForm.images = editForm.images.filter((_, idx) => idx !== i) }
function markDeleteEditExisting(id: number) { editForm.delete_images = [...editForm.delete_images, id]; editExistingImages.value = editExistingImages.value.filter(img => img.id !== id) }
function selectEditInvoice(val: string | null) { editForm.sale_invoice = val ?? ''; editInvoiceDropdownOpen.value = false }
function closeEditInvoiceDropdown(e: FocusEvent) { if (!(e.relatedTarget as HTMLElement)?.closest?.('.edit-invoice-panel')) editInvoiceDropdownOpen.value = false }

function sanitizeInvoice(target: EventTarget | null, setter: (v: string) => void) {
    const el = target as HTMLInputElement
    const clean = el.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase()
    el.value = clean
    setter(clean)
}
function submitEdit() {
    if (!editingKoi.value) return
    editForm.post(`/master-data/koi/${editingKoi.value.id}?_method=PUT`, {
        forceFormData: true, preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false
            setTimeout(() => useSwal().fire({ icon: 'success', title: 'Berhasil!', text: 'Data koi berhasil diperbarui.', timer: 3000, showConfirmButton: false }), 300)
            editFotoPreviews.value = []; editForm.images = []; editForm.delete_images = []
        },
        onError: () => useSwal().fire({ icon: 'error', title: 'Gagal!', text: 'Terjadi kesalahan saat memperbarui data.', timer: 3000, showConfirmButton: false }),
    })
}

const fotoPreviews   = ref<{ file: File; url: string }[]>([])
const feePct              = ref<string | null>(null)
const exchangeRate        = ref(0)
const exchangeRateLoading = ref(false)
const exchangeRateError   = ref(false)
const kursInput           = ref('')
const kursIsManual        = ref(false)

async function fetchExchangeRate() {
    exchangeRateLoading.value = true; exchangeRateError.value = false
    try {
        const res  = await fetch('https://api.exchangerate-api.com/v4/latest/JPY')
        if (!res.ok) throw new Error()
        const data = await res.json()
        exchangeRate.value = Math.round(Number(data?.rates?.IDR ?? 0))
        if (!exchangeRate.value) throw new Error()
    } catch { exchangeRateError.value = true; exchangeRate.value = 110 }
    finally  { exchangeRateLoading.value = false }
}
fetchExchangeRate()

watch(showModal, open => {
    if (!open) return
    kursIsManual.value = false
    if (exchangeRate.value > 0) {
        kursInput.value = String(exchangeRate.value)
    } else {
        fetchExchangeRate()
    }
})
watch(exchangeRate, rate => { if (rate > 0 && !kursIsManual.value) kursInput.value = String(rate) })
watch(kursInput, v => { form.kurs = v })
function resetKurs() { kursIsManual.value = false; kursInput.value = String(exchangeRate.value) }

// ─── Form ─────────────────────────────────────────────────
const form = useForm({
    breeder_id: '', jenis_koi_id: '', code: '', name: '', description: '',
    age: '', size: '', box_qty: '', weight: '', total_weight: '',
    images: [] as File[],
    customer_id: '', sale_tanggal: '', sale_qty: '', sale_invoice: '', azukari_period: '', azukari_qty: '0', azukari_price_jpy: '',
    buy_jpy: '', unit_price: '', kurs: '', sell_idr: '', stock_price: '', fee: '0',
    sale_status: 'shipped',
    shipping_cost: '', shipping_qty: '', shipping_batch: '', shipping_note: '', shipping_date: '',
})

const modalQty = ref('')

// weight × box_qty → total_weight
watch([() => form.weight, () => form.box_qty], ([w, q]) => {
    const weight = Number(w) || 0; const qty = Number(q) || 0
    form.total_weight = weight > 0 && qty > 0 ? String(parseFloat((weight * qty).toFixed(3))) : ''
})

// unit_price × qty × kurs → sell_idr
watch([() => form.unit_price, () => form.sale_qty, kursInput], ([price, qty, kurs]) => {
    const p = Number(price) || 0
    const q = Number(qty)   || 1
    const k = Number(kurs)  || 0
    form.sell_idr = p > 0 && k > 0 ? String(Math.round(p * q * k)) : ''
})

// ─── Computed previews ────────────────────────────────────
// Modal Price IDR per ekor = stock_price (JPY) × kurs
const modalPriceIdr = computed(() => {
    const m = Number(form.stock_price) || 0
    const k = Number(kursInput.value)  || 0
    return m > 0 && k > 0 ? Math.round(m * k) : null
})
// Total Modal IDR = modal price IDR × modal_qty (stok disiapkan)
const totalModalIdr = computed(() => {
    const m = modalPriceIdr.value      || 0
    const q = Number(modalQty.value)   || 0
    return m > 0 && q > 0 ? Math.round(m * q) : null
})
// Modal IDR untuk qty yang terjual (dipakai di profit)
const modalTerjualIdr = computed(() => {
    const m = modalPriceIdr.value   || 0
    const q = Number(form.sale_qty) || 1
    return m > 0 ? Math.round(m * q) : null
})
// Unit price IDR preview
const unitPriceIdr = computed(() => {
    const p = Number(form.unit_price) || 0
    const k = Number(kursInput.value) || 0
    return p > 0 && k > 0 ? Math.round(p * k) : null
})
// Azukari price IDR preview (add form)
const azukariPriceIdr = computed(() => {
    const p = Number(form.azukari_price_jpy) || 0
    const k = Number(kursInput.value) || 0
    return p > 0 && k > 0 ? Math.round(p * k) : null
})
// Sales Price JPY (unit × qty)
const salesPriceJpy = computed(() => {
    const p = Number(form.unit_price) || 0
    const q = Number(form.sale_qty)   || 1
    return p > 0 ? p * q : null
})
// Sales Price IDR
const salesPriceIdr = computed(() => {
    const jpy = salesPriceJpy.value
    const k   = Number(kursInput.value) || 0
    return jpy && k > 0 ? Math.round(jpy * k) : null
})
// Shipping: input langsung IDR
const shippingCostIdr = computed(() => {
    const cost = Number(form.shipping_cost) || 0
    return cost > 0 ? cost : null
})
const shippingPerEkor = computed(() => {
    const costIdr = shippingCostIdr.value   || 0
    const qty     = Number(form.shipping_qty) || 0
    return costIdr > 0 && qty > 0 ? Math.round(costIdr / qty) : null
})
const shippingKena = computed(() => {
    const p = shippingPerEkor.value    || 0
    const q = Number(form.sale_qty)   || 1
    return p > 0 ? Math.round(p * q) : null
})

// Profit = Sales − Modal + Fee (shipping ditanggung customer)
const profit = computed(() =>
    (salesPriceIdr.value ?? 0) - (modalTerjualIdr.value ?? 0) + Number(form.fee || 0)
)

const filteredBreeders  = computed(() => !breederSearch.value  ? breeders.value  : breeders.value.filter(b => b.name_breeder.toLowerCase().includes(breederSearch.value.toLowerCase())))
const filteredJenisKois = computed(() => !jenisSearch.value   ? jenisKois.value  : jenisKois.value.filter(j => j.nama_jenis.toLowerCase().includes(jenisSearch.value.toLowerCase())))
const filteredCustomers = computed(() => !customerSearch.value ? customers.value : customers.value.filter(c => c.name.toLowerCase().includes(customerSearch.value.toLowerCase())))


// ─── Status badge ─────────────────────────────────────────
const STATUS_LABEL: Record<string, string> = {
    cancel: 'Cancel', dead_refund: 'Dead Refund', refund: 'Refund',
    cancel_refund: 'Cancel Refund', pending: 'Pending', shipped: 'Shipped',
    azukari: 'Azukari', dead: 'Dead',
}
const STATUS_CLASS: Record<string, string> = {
    shipped:      'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
    pending:      'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
    azukari:      'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
    refund:       'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300',
    cancel:       'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
    cancel_refund:'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
    dead_refund:  'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
    dead:         'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
}
function statusLabel(s?: string | null) { return s ? (STATUS_LABEL[s] ?? s) : '-' }
function statusClass(s?: string | null) { return s ? (STATUS_CLASS[s] ?? '') : '' }

// ─── Formatters ───────────────────────────────────────────
const fmtIDR  = (v: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v)
const fmtDate = (d: string) => new Date(d).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' })

function fmtInput(val: string | number): string {
    const n = String(val).replace(/\D/g, '')
    return n ? Number(n).toLocaleString('id-ID') : ''
}
function parseInput(e: Event): string {
    return (e.target as HTMLInputElement).value.replace(/\D/g, '')
}
function addDots(val: string | number | null) {
    const n = Math.round(Number(val))
    return n > 0 ? n.toLocaleString('id-ID') : ''
}

// Display refs — format hanya saat blur, bukan tiap ketukan
const stockPriceDisplay    = ref('')
const shippingCostDisplay  = ref('')
const unitPriceDisplay     = ref('')
const azukariPriceDisplay  = ref('')

function onStockPriceChange(val: string | number)   { const r = String(val).replace(/\D/g, ''); form.stock_price = r; stockPriceDisplay.value = addDots(r) }
function onStockPriceBlur()             { stockPriceDisplay.value = addDots(form.stock_price) }
function onShippingCostChange(val: string | number) { const r = String(val).replace(/\D/g, ''); form.shipping_cost = r; shippingCostDisplay.value = addDots(r) }
function onShippingCostBlur()           { shippingCostDisplay.value = addDots(form.shipping_cost) }
function onUnitPriceChange(val: string | number)    { const r = String(val).replace(/\D/g, ''); form.unit_price = r; unitPriceDisplay.value = addDots(r) }
function onUnitPriceBlur()              { unitPriceDisplay.value = addDots(form.unit_price) }
function onAzukariPriceChange(val: string | number) { const r = String(val).replace(/\D/g, ''); form.azukari_price_jpy = r; azukariPriceDisplay.value = addDots(r) }

const kursDisplay = ref(addDots(kursInput.value))
watch(kursInput, v => { kursDisplay.value = addDots(v) })

function onKursInput(e: Event) {
    const r = parseInput(e)
    kursIsManual.value = true
    kursInput.value    = r
    kursDisplay.value  = r
}
function onKursBlur() { kursDisplay.value = addDots(kursInput.value) }
function onBuyJpyInput(e: Event) { form.buy_jpy = parseInput(e) }
function onFeeSelect(val: unknown) {
    feePct.value = val !== null ? String(val) : null
    form.fee = String(Math.round((salesPriceIdr.value ?? 0) * (Number(val) / 100)))
}

function generateBatchCode() {
    const d = new Date()
    const ymd = d.getFullYear().toString()
        + String(d.getMonth() + 1).padStart(2, '0')
        + String(d.getDate()).padStart(2, '0')
    const allBatches = [...shippingBatches.value, ...extraBatches.value]
    const existing = allBatches.filter(b => b.startsWith(`SHP-${ymd}-`))
    const nextNum = existing.length + 1
    const newCode = `SHP-${ymd}-${String(nextNum).padStart(2, '0')}`
    if (!allBatches.includes(newCode)) extraBatches.value.push(newCode)
    form.shipping_batch = newCode
}
function generateEditBatchCode() {
    const d = new Date()
    const ymd = d.getFullYear().toString()
        + String(d.getMonth() + 1).padStart(2, '0')
        + String(d.getDate()).padStart(2, '0')
    const allBatches = [...shippingBatches.value, ...extraBatches.value]
    const existing = allBatches.filter(b => b.startsWith(`SHP-${ymd}-`))
    const nextNum = existing.length + 1
    const newCode = `SHP-${ymd}-${String(nextNum).padStart(2, '0')}`
    if (!allBatches.includes(newCode)) extraBatches.value.push(newCode)
    editForm.shipping_batch = newCode
}

// Auto-fill shipping_cost & shipping_qty dari batch details (create modal)
watch(() => form.shipping_batch, (batch) => {
    const details = (page.props.shippingBatchDetails as any)
    if (batch && details?.[batch]) {
        const d = details[batch]
        if (d.shipping_cost != null) { form.shipping_cost = String(Math.round(d.shipping_cost)); shippingCostDisplay.value = addDots(d.shipping_cost) }
        if (d.shipping_qty  != null)   form.shipping_qty = String(d.shipping_qty)
    }
})

// Auto-fill shipping_cost & shipping_qty dari batch details (edit modal)
watch(() => editForm.shipping_batch, (batch) => {
    const details = (page.props.shippingBatchDetails as any)
    if (batch && details?.[batch]) {
        const d = details[batch]
        if (d.shipping_cost != null) { editForm.shipping_cost = String(Math.round(d.shipping_cost)); editShippingCostDisplay2.value = addDots(d.shipping_cost) }
        if (d.shipping_qty  != null)   editForm.shipping_qty = String(d.shipping_qty)
    }
})

// ─── Image handlers ───────────────────────────────────────
function handleImagesChange(e: Event) {
    const input = e.target as HTMLInputElement
    if (!input.files?.length) return
    const files = Array.from(input.files)
    form.images = [...form.images, ...files]
    files.forEach(f => { const r = new FileReader(); r.onload = ev => fotoPreviews.value.push({ file: f, url: ev.target?.result as string }); r.readAsDataURL(f) })
    input.value = ''
}
function removeNewImage(i: number) { fotoPreviews.value.splice(i, 1); form.images = form.images.filter((_, idx) => idx !== i) }

// ─── Submit ───────────────────────────────────────────────
function submit() {
    form.post('/master-data/koi', {
        forceFormData: true, preserveScroll: true,
        onSuccess: () => {
            showModal.value = false
            setTimeout(() => useSwal().fire({ icon: 'success', title: 'Berhasil!', text: 'Data berhasil disimpan.', timer: 3000, showConfirmButton: false }), 300)
        },
        onError: () => {
            useSwal().fire({ icon: 'error', title: 'Gagal!', text: 'Terjadi kesalahan. Periksa kembali isian.', timer: 4000, showConfirmButton: false })
        },
    })
}

// ─── Delete ───────────────────────────────────────────────
function confirmDeleteKoi(id: number, name: string) {
    useSwal().fire({ title: 'Hapus koi?', text: `Koi "${name}" akan dihapus permanen.`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', cancelButtonColor: '#6b7280', confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal' })
        .then(r => { if (r.isConfirmed) router.delete(`/master-data/koi/${id}`, { preserveScroll: true, onSuccess: () => { router.reload({ only: ['shippingBatches', 'shippingBatchDetails'] }); useSwal().fire({ icon: 'success', title: 'Berhasil!', text: 'Koi dihapus.', timer: 2000, showConfirmButton: false }) } }) })
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Data Koi" />

        <div class="flex flex-col gap-4 p-6">

            <!-- Header -->
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Data Koi</h1>
                    <p class="text-sm text-muted-foreground">Kelola data koi.</p>
                </div>

                <!-- MODAL -->
                <Dialog v-model:open="showModal">
                    <DialogTrigger as-child>
                        <Button>+ New Koi</Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-[660px] max-h-[92vh] overflow-y-auto">
                        <DialogHeader class="pb-2 border-b border-border">
                            <DialogTitle class="text-base font-semibold">Tambah Data Koi</DialogTitle>
                            <DialogDescription class="text-xs">Isi data koi dan informasi penjualan (opsional).</DialogDescription>
                        </DialogHeader>

                        <form @submit.prevent="submit" class="py-4 space-y-5 px-1">

                            <!-- ══ SECTION: Data Koi ══ -->
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="h-4 w-1 rounded-full bg-primary"></div>
                                    <p class="text-sm font-semibold">Data Koi</p>
                                </div>

                                <div class="space-y-3 pl-3">
                                    <!-- Breeder & Jenis Koi -->
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div class="grid gap-1.5">
                                            <Label class="text-xs font-medium text-muted-foreground">Breeder <span class="text-red-500">*</span></Label>
                                            <Select v-model="form.breeder_id">
                                                <SelectTrigger class="w-full"><SelectValue placeholder="Pilih breeder" /></SelectTrigger>
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
                                            <Label class="text-xs font-medium text-muted-foreground">Jenis Koi</Label>
                                            <Select v-model="form.jenis_koi_id">
                                                <SelectTrigger class="w-full"><SelectValue placeholder="Pilih jenis koi" /></SelectTrigger>
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
                                    </div>

                                    <!-- Kode & Nama -->
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div class="grid gap-1.5">
                                            <Label class="text-xs font-medium text-muted-foreground">Kode Koi <span class="text-red-500">*</span></Label>
                                            <Input v-model="form.code" placeholder="KOI-001" />
                                            <InputError :message="form.errors.code" />
                                        </div>
                                        <div class="grid gap-1.5">
                                            <Label class="text-xs font-medium text-muted-foreground">Nama Koi <span class="text-red-500">*</span></Label>
                                            <Input v-model="form.name" placeholder="Nama koi" />
                                            <InputError :message="form.errors.name" />
                                        </div>
                                    </div>

                                    <!-- Data Fisik -->
                                    <div class="rounded-md border border-border bg-muted/20 p-3 space-y-2.5">
                                        <p class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">Data Fisik</p>
                                        <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-5">
                                            <div class="grid gap-1">
                                                <Label class="text-[11px] text-muted-foreground">Age (bln)</Label>
                                                <Input v-model="form.age" type="number" min="0" placeholder="0" class="h-8 text-sm" />
                                                <InputError :message="form.errors.age" />
                                            </div>
                                            <div class="grid gap-1">
                                                <Label class="text-[11px] text-muted-foreground">Size (cm)</Label>
                                                <Input v-model="form.size" type="number" min="0" step="0.01" placeholder="0" class="h-8 text-sm" />
                                                <InputError :message="form.errors.size" />
                                            </div>
                                            <div class="grid gap-1">
                                                <Label class="text-[11px] text-muted-foreground">Box QTY</Label>
                                                <Input v-model="form.box_qty" type="number" min="0" placeholder="0" class="h-8 text-sm" />
                                                <InputError :message="form.errors.box_qty" />
                                            </div>
                                            <div class="grid gap-1">
                                                <Label class="text-[11px] text-muted-foreground">Weight (kg)</Label>
                                                <Input v-model="form.weight" type="number" min="0" step="0.001" placeholder="0" class="h-8 text-sm" />
                                                <InputError :message="form.errors.weight" />
                                            </div>
                                            <div class="grid gap-1">
                                                <Label class="text-[11px] text-muted-foreground">Total Weight (kg) <span class="opacity-60">auto</span></Label>
                                                <div class="flex h-8 items-center rounded-md border border-border bg-muted/50 px-2 text-sm font-medium text-foreground">
                                                    {{ form.total_weight || '-' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Foto Koi -->
                                    <div class="rounded-md border border-border bg-muted/20 p-3 space-y-2">
                                        <p class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">Foto Koi</p>
                                        <div v-if="fotoPreviews.length > 0" class="flex flex-wrap gap-2">
                                            <div v-for="(preview, i) in fotoPreviews" :key="i" class="relative h-16 w-16 overflow-hidden rounded-lg border group">
                                                <img :src="preview.url" class="h-full w-full object-cover" />
                                                <button type="button" @click="removeNewImage(i)" class="absolute inset-0 flex items-center justify-center bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <X class="h-4 w-4 text-white" />
                                                </button>
                                            </div>
                                        </div>
                                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border-2 border-dashed border-border px-3 py-2 text-xs text-muted-foreground hover:border-primary/50 hover:text-foreground transition-colors">
                                            <ImagePlus class="h-4 w-4 flex-shrink-0" />
                                            <span>Pilih foto (jpg, png, webp · maks. 2 MB · bisa beberapa)</span>
                                            <input type="file" accept="image/jpg,image/jpeg,image/png,image/webp" multiple class="sr-only" @change="handleImagesChange" />
                                        </label>
                                        <InputError :message="(form.errors as any)['images.*']" />
                                    </div>

                                    <!-- Description -->
                                    <div class="space-y-1.5">
                                        <label class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">Description <span class="font-normal normal-case opacity-60">(opsional)</span></label>
                                        <textarea
                                            v-model="form.description"
                                            rows="3"
                                            placeholder="Catatan atau keterangan tambahan tentang koi ini..."
                                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none transition-[color,box-shadow] placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] resize-none"
                                        />
                                        <InputError :message="(form.errors as any).description" />
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-border"></div>

                            <!-- ══ Kurs JPY → IDR ══ -->
                            <div class="rounded-lg border-2 border-red-400 dark:border-red-600 bg-red-50 dark:bg-red-950/30 p-3 space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <Label class="text-xs font-semibold text-red-700 dark:text-red-400">Kurs JPY → IDR</Label>
                                    <div class="flex items-center gap-1.5">
                                        <span v-if="exchangeRateLoading" class="text-[10px] text-red-400 animate-pulse">Memuat kurs...</span>
                                        <template v-else>
                                            <span v-if="kursIsManual" class="inline-flex items-center gap-1 rounded-full bg-orange-100 dark:bg-orange-900/50 px-2 py-0.5 text-[10px] font-medium text-orange-600 dark:text-orange-400">✎ Manual</span>
                                            <span v-else-if="exchangeRateError" class="inline-flex items-center gap-1 rounded-full bg-yellow-100 dark:bg-yellow-900/50 px-2 py-0.5 text-[10px] font-medium text-yellow-700 dark:text-yellow-400">⚠ Fallback</span>
                                            <span v-else class="inline-flex items-center gap-1 rounded-full bg-green-100 dark:bg-green-900/50 px-2 py-0.5 text-[10px] font-medium text-green-700 dark:text-green-400">✓ Live API</span>
                                            <button v-if="kursIsManual" type="button" @click="resetKurs" class="text-[10px] underline text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition-colors">Gunakan API</button>
                                        </template>
                                    </div>
                                </div>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-red-600 dark:text-red-400">Rp</span>
                                    <input type="text" inputmode="numeric" v-model="kursDisplay" @input="onKursInput" @blur="onKursBlur" placeholder="0"
                                        class="flex h-9 w-full rounded-md border-2 border-red-400 dark:border-red-600 bg-white dark:bg-red-900/20 pl-9 pr-14 text-sm font-semibold text-red-800 dark:text-red-100 placeholder:text-red-300 dark:placeholder:text-red-700 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-200 dark:focus:ring-red-800/50 transition-colors" />
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-red-500 dark:text-red-400">/ ¥1</span>
                                </div>
                                <p class="text-[11px] text-red-600/80 dark:text-red-400/80">Otomatis dari API · langsung ketik untuk ubah manual</p>
                            </div>

                            <div class="border-t border-border"></div>

                            <!-- ══ SECTION: Modal ══ -->
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="h-4 w-1 rounded-full bg-blue-500"></div>
                                    <p class="text-sm font-semibold">Modal</p>
                                </div>

                                <div class="space-y-3 pl-3">
                                    <div class="rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50/50 dark:bg-blue-950/20 p-3 space-y-3">
                                        <div class="grid grid-cols-2 gap-3">
                                            <!-- Modal Price JPY -->
                                            <div class="space-y-1.5">
                                                <Label class="text-xs font-semibold text-blue-700 dark:text-blue-400">Modal Price (JPY/ekor)</Label>
                                                <div class="relative">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-blue-500">¥</span>
                                                    <Input
                                                        type="text" inputmode="numeric"
                                                        :modelValue="stockPriceDisplay"
                                                        @update:modelValue="onStockPriceChange"
                                                        @blur="onStockPriceBlur"
                                                        placeholder="0"
                                                        class="h-9 text-sm pl-7 border-blue-300 dark:border-blue-700 focus:border-blue-500"
                                                    />
                                                </div>
                                                <div v-if="modalPriceIdr" class="rounded border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/30 px-2.5 py-1 text-[11px] flex justify-between">
                                                    <span class="text-muted-foreground">≈ IDR/ekor</span>
                                                    <span class="font-semibold text-blue-700 dark:text-blue-400">{{ fmtIDR(modalPriceIdr) }}</span>
                                                </div>
                                                <InputError :message="(form.errors as any).stock_price" />
                                            </div>
                                            <!-- Qty Modal (stok disiapkan) -->
                                            <div class="space-y-1.5">
                                                <Label class="text-xs font-semibold text-blue-700 dark:text-blue-400">Qty Modal (ekor)</Label>
                                                <Input
                                                    type="number" min="1" placeholder="0"
                                                    v-model="modalQty"
                                                    class="h-9 text-sm border-blue-300 dark:border-blue-700"
                                                />
                                                <p class="text-[10px] text-muted-foreground">Stok yang disiapkan</p>
                                            </div>
                                        </div>
                                        <!-- Total Modal -->
                                        <div v-if="totalModalIdr" class="rounded border border-blue-300 dark:border-blue-700 bg-blue-100 dark:bg-blue-900/40 px-3 py-2 text-[11px] flex justify-between items-center">
                                            <span class="text-blue-700 dark:text-blue-300 font-medium">Total Modal ({{ modalQty || 1 }} ekor)</span>
                                            <span class="font-bold text-blue-800 dark:text-blue-200">{{ fmtIDR(totalModalIdr) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-border"></div>

                            <!-- ══ SECTION: Shipping ══ -->
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="h-4 w-1 rounded-full bg-orange-500"></div>
                                    <p class="text-sm font-semibold">Shipping</p>
                                    <span class="text-xs text-muted-foreground">(opsional)</span>
                                </div>

                                <div class="space-y-3 pl-3">
                                    <div class="rounded-lg border border-orange-200 dark:border-orange-800 bg-orange-50/50 dark:bg-orange-950/20 p-3 space-y-3">
                                        <!-- Batch selector — selalu di atas -->
                                        <div class="space-y-1.5">
                                            <Label class="text-xs font-medium text-orange-700 dark:text-orange-400">Shipping Batch <span class="font-normal opacity-60">(untuk ikan patungan 1 box)</span></Label>
                                            <div class="flex gap-2">
                                                <select v-model="form.shipping_batch" class="flex-1 h-9 rounded-md border border-orange-300 dark:border-orange-700 bg-background px-3 text-sm">
                                                    <option value="">— Tanpa batch / isi manual —</option>
                                                    <option v-for="b in allBatchOptions" :key="b" :value="b"
                                                        :disabled="!!(page.props.shippingBatchDetails as any)?.[b] && (page.props.shippingBatchDetails as any)[b].shipping_qty != null && (page.props.shippingBatchDetails as any)[b].koi_count >= (page.props.shippingBatchDetails as any)[b].shipping_qty">
                                                        {{ b }}{{ (page.props.shippingBatchDetails as any)?.[b]?.shipping_qty != null ? ` (${(page.props.shippingBatchDetails as any)[b].koi_count}/${(page.props.shippingBatchDetails as any)[b].shipping_qty}${(page.props.shippingBatchDetails as any)[b].koi_count >= (page.props.shippingBatchDetails as any)[b].shipping_qty ? ' Full' : ''})` : '' }}
                                                    </option>
                                                </select>
                                                <button type="button" @click="generateBatchCode" :disabled="!!form.shipping_batch"
                                                    class="h-9 px-3 rounded-md border border-orange-300 dark:border-orange-700 bg-orange-50 dark:bg-orange-950 text-xs text-orange-700 dark:text-orange-300 hover:bg-orange-100 whitespace-nowrap disabled:opacity-40 disabled:cursor-not-allowed">
                                                    + Buat Baru
                                                </button>
                                            </div>
                                            <InputError :message="(form.errors as any).shipping_batch" />
                                        </div>

                                        <!-- Batch lama dipilih: info + kalkulasi -->
                                        <template v-if="form.shipping_batch && shippingBatches.includes(form.shipping_batch)">
                                            <div class="rounded border border-orange-300 dark:border-orange-700 bg-white dark:bg-orange-950/40 p-2.5 space-y-1.5">
                                                <p class="text-[10px] font-semibold uppercase tracking-wide text-orange-600 dark:text-orange-400">Info Patungan Shipping</p>
                                                <div class="flex justify-between text-[11px]">
                                                    <span class="text-muted-foreground">Total biaya box</span>
                                                    <span class="font-semibold">{{ (page.props.shippingBatchDetails as any)?.[form.shipping_batch]?.shipping_cost != null ? 'Rp ' + Number((page.props.shippingBatchDetails as any)[form.shipping_batch].shipping_cost).toLocaleString('id-ID') : '—' }}</span>
                                                </div>
                                                <div class="flex justify-between text-[11px]">
                                                    <span class="text-muted-foreground">Total ikan sepaket</span>
                                                    <span class="font-semibold">{{ (page.props.shippingBatchDetails as any)?.[form.shipping_batch]?.shipping_qty ?? '—' }} ekor</span>
                                                </div>
                                                <div class="flex justify-between text-[11px]">
                                                    <span class="text-muted-foreground">Sudah ada</span>
                                                    <span class="font-semibold">{{ (page.props.shippingBatchDetails as any)?.[form.shipping_batch]?.koi_count ?? 0 }} ekor dari customer lain</span>
                                                </div>
                                                <div v-if="(page.props.shippingBatchDetails as any)?.[form.shipping_batch]?.members?.length" class="border-t border-orange-200 dark:border-orange-800 pt-1.5 space-y-1">
                                                    <div v-for="m in (page.props.shippingBatchDetails as any)[form.shipping_batch].members" :key="m.code" class="flex justify-between text-[11px]">
                                                        <span class="text-muted-foreground">{{ m.customer ?? '—' }} <span class="font-mono">{{ m.code }}</span></span>
                                                        <span>{{ m.sale_qty ?? 1 }} ekor</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-if="shippingPerEkor" class="rounded border border-orange-300 dark:border-orange-700 bg-orange-100 dark:bg-orange-900/40 px-3 py-2 text-[11px] space-y-1">
                                                <div class="flex justify-between items-center">
                                                    <span class="text-orange-700 dark:text-orange-300 font-medium">Shipping/ekor</span>
                                                    <span class="font-semibold text-orange-800 dark:text-orange-200">{{ fmtIDR(shippingPerEkor) }}</span>
                                                </div>
                                                <div v-if="shippingKena" class="flex justify-between items-center border-t border-orange-200 dark:border-orange-800 pt-1">
                                                    <span class="text-orange-700 dark:text-orange-300 font-medium">Total Shipping ({{ form.sale_qty || 1 }} ekor)</span>
                                                    <span class="font-bold text-orange-800 dark:text-orange-200">{{ fmtIDR(shippingKena) }}</span>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Batch baru / tanpa batch: input manual -->
                                        <template v-else>
                                            <div class="grid grid-cols-2 gap-3">
                                                <div class="space-y-1.5">
                                                    <Label class="text-xs font-semibold text-orange-700 dark:text-orange-400">Total Biaya Shipping (IDR)</Label>
                                                    <div class="relative">
                                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-orange-500">Rp</span>
                                                        <Input type="text" inputmode="numeric" :modelValue="shippingCostDisplay" @update:modelValue="onShippingCostChange" @blur="onShippingCostBlur" placeholder="0" class="h-9 text-sm pl-9 border-orange-300 dark:border-orange-700" />
                                                    </div>
                                                    <InputError :message="(form.errors as any).shipping_cost" />
                                                </div>
                                                <div class="space-y-1.5">
                                                    <Label class="text-xs font-semibold text-orange-700 dark:text-orange-400">Jumlah Ikan Sepaket</Label>
                                                    <Input v-model="form.shipping_qty" type="number" min="1" placeholder="0" class="h-9 text-sm border-orange-300 dark:border-orange-700" />
                                                    <p class="text-[10px] text-muted-foreground">Total ikan dalam 1 pengiriman</p>
                                                    <InputError :message="(form.errors as any).shipping_qty" />
                                                </div>
                                            </div>
                                            <div v-if="shippingPerEkor" class="rounded border border-orange-300 dark:border-orange-700 bg-orange-100 dark:bg-orange-900/40 px-3 py-2 text-[11px] space-y-1">
                                                <div class="flex justify-between items-center">
                                                    <span class="text-orange-700 dark:text-orange-300 font-medium">Shipping/ekor</span>
                                                    <span class="font-semibold text-orange-800 dark:text-orange-200">{{ fmtIDR(shippingPerEkor) }}</span>
                                                </div>
                                                <div v-if="shippingKena" class="flex justify-between items-center border-t border-orange-200 dark:border-orange-800 pt-1">
                                                    <span class="text-orange-700 dark:text-orange-300 font-medium">Total Shipping ({{ form.sale_qty || 1 }} ekor)</span>
                                                    <span class="font-bold text-orange-800 dark:text-orange-200">{{ fmtIDR(shippingKena) }}</span>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Keterangan & Tanggal Shipping -->
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="space-y-1.5">
                                                <Label class="text-xs font-medium text-orange-700 dark:text-orange-400">Keterangan <span class="font-normal opacity-60">(opsional)</span></Label>
                                                <Input v-model="form.shipping_note" type="text" placeholder="Contoh: Sea freight Nov 2025, JNE Express..." class="h-9 text-sm border-orange-300 dark:border-orange-700" />
                                                <InputError :message="(form.errors as any).shipping_note" />
                                            </div>
                                            <div class="space-y-1.5">
                                                <Label class="text-xs font-medium text-orange-700 dark:text-orange-400">Shipping Date <span class="font-normal opacity-60">(opsional)</span></Label>
                                                <Input v-model="form.shipping_date" type="date" class="h-9 text-sm border-orange-300 dark:border-orange-700" />
                                                <InputError :message="(form.errors as any).shipping_date" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-border"></div>

                            <!-- ══ SECTION: Transaksi ══ -->
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="h-4 w-1 rounded-full bg-green-500"></div>
                                    <p class="text-sm font-semibold">Transaksi</p>
                                </div>

                                <div class="space-y-3 pl-3">

                                    <!-- Customer & Status -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="grid gap-1.5">
                                            <Label class="text-xs font-medium text-muted-foreground">Customer</Label>
                                            <Select v-model="form.customer_id">
                                                <SelectTrigger class="w-full"><SelectValue placeholder="Pilih customer" /></SelectTrigger>
                                                <SelectContent class="p-0 w-[--radix-select-trigger-width] min-w-[20rem]">
                                                    <div class="sticky top-0 bg-background p-2 border-b flex items-center gap-1.5">
                                                        <Input v-model="customerSearch" placeholder="Cari customer..." class="h-8 flex-1" @keydown.stop />
                                                        <a href="/admin/hak-akses/customer" target="_blank" title="Buka halaman Customer"
                                                            class="flex-shrink-0 inline-flex items-center justify-center h-8 w-8 rounded-md border border-border bg-background hover:bg-muted text-muted-foreground hover:text-foreground transition-colors">
                                                            <ExternalLink class="w-3.5 h-3.5" />
                                                        </a>
                                                    </div>
                                                    <div class="max-h-44 overflow-y-auto">
                                                        <div v-if="filteredCustomers.length === 0" class="px-3 py-2 text-sm text-muted-foreground">Tidak ada customer</div>
                                                        <SelectItem v-for="c in filteredCustomers" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                                                    </div>
                                                </SelectContent>
                                            </Select>
                                            <InputError :message="form.errors.customer_id" />
                                        </div>
                                        <div class="grid gap-1.5">
                                            <Label class="text-xs font-medium text-muted-foreground">Status</Label>
                                            <Select v-model="form.sale_status">
                                                <SelectTrigger class="w-full"><SelectValue placeholder="Pilih status" /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="pending">Pending</SelectItem>
                                                    <SelectItem value="shipped">Shipped</SelectItem>
                                                    <SelectItem value="azukari">Azukari</SelectItem>
                                                    <SelectItem value="dead">Dead</SelectItem>
                                                    <SelectItem value="cancel">Cancel</SelectItem>
                                                    <SelectItem value="refund">Refund</SelectItem>
                                                    <SelectItem value="dead_refund">Dead Refund</SelectItem>
                                                    <SelectItem value="cancel_refund">Cancel Refund</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError :message="form.errors.sale_status" />
                                        </div>
                                    </div>

                                    <!-- Invoice -->
                                    <div class="grid gap-1.5">
                                        <Label class="text-xs font-medium text-muted-foreground">
                                            No. Invoice
                                            <span class="font-normal opacity-60">(opsional)</span>
                                        </Label>

                                        <!-- Combobox: input langsung + dropdown -->
                                        <div class="relative">
                                            <div class="flex items-center rounded-md border border-input bg-background shadow-sm focus-within:ring-1 focus-within:ring-ring">
                                                <FileText class="ml-3 h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                                                <input
                                                    v-model="form.sale_invoice"
                                                    type="text"
                                                    placeholder="Ketik nomor invoice atau pilih existing..."
                                                    class="flex h-9 w-full bg-transparent px-2 text-sm font-mono outline-none placeholder:font-sans placeholder:text-muted-foreground"
                                                    @focus="invoiceDropdownOpen = true"
                                                    @blur="closeInvoiceDropdown"
                                                    @input="(e) => { sanitizeInvoice(e.target, v => { form.sale_invoice = v; invoiceSearch = v }); invoiceDropdownOpen = true }"
                                                    @keydown.enter.prevent="invoiceDropdownOpen = false"
                                                    @keydown.escape="invoiceDropdownOpen = false"
                                                />
                                                <button v-if="form.sale_invoice" type="button" @mousedown.prevent @click="selectInvoice(null)" class="mr-2 shrink-0 text-muted-foreground hover:text-foreground">
                                                    <X class="h-3.5 w-3.5" />
                                                </button>
                                                <button type="button" @mousedown.prevent @click="invoiceDropdownOpen = !invoiceDropdownOpen" class="mr-2 shrink-0 text-muted-foreground hover:text-foreground">
                                                    <ChevronDown class="h-3.5 w-3.5 transition-transform" :class="invoiceDropdownOpen ? 'rotate-180' : ''" />
                                                </button>
                                            </div>

                                            <!-- Dropdown panel -->
                                            <div
                                                v-if="invoiceDropdownOpen && (filteredInvoiceOptions.length > 0 || invoiceSearch.trim())"
                                                class="invoice-dropdown-panel absolute z-50 mt-1 w-full rounded-md border border-border bg-popover text-popover-foreground shadow-md"
                                                tabindex="-1"
                                            >
                                                <div class="max-h-52 overflow-y-auto py-1">
                                                    <!-- Existing invoices -->
                                                    <template v-if="filteredInvoiceOptions.length > 0">
                                                        <div class="px-3 py-1 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground/60">Invoice Existing</div>
                                                        <button
                                                            v-for="inv in filteredInvoiceOptions"
                                                            :key="inv.sale_invoice"
                                                            type="button"
                                                            @click="selectInvoice(inv.sale_invoice)"
                                                            class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                                                        >
                                                            <Check class="h-3.5 w-3.5 shrink-0" :class="form.sale_invoice === inv.sale_invoice ? 'text-primary' : 'opacity-0'" />
                                                            <div class="flex flex-1 items-center justify-between gap-2 min-w-0">
                                                                <span class="font-mono font-semibold text-sm truncate">{{ inv.sale_invoice }}</span>
                                                                <div class="flex items-center gap-1 shrink-0">
                                                                    <span class="rounded bg-muted px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground">{{ inv.koi_count }} koi</span>
                                                                    <span class="text-[10px] text-muted-foreground/60">→ {{ inv.koi_count + 1 }}</span>
                                                                </div>
                                                            </div>
                                                        </button>
                                                    </template>

                                                    <!-- Empty state -->
                                                    <div v-if="filteredInvoiceOptions.length === 0" class="px-3 py-4 text-center text-xs text-muted-foreground">
                                                        Tidak ada invoice yang cocok
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Preview: jika invoice dipilih dari existing -->
                                        <div v-if="form.sale_invoice && invoices.find(i => i.sale_invoice === form.sale_invoice)" class="rounded border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/30 px-2.5 py-1.5 text-[11px] flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-amber-600 dark:text-amber-400 font-medium">Invoice sudah ada:</span>
                                                <span class="text-amber-700 dark:text-amber-300">
                                                    {{ invoices.find(i => i.sale_invoice === form.sale_invoice)?.koi_count }} koi terdaftar
                                                    → akan menjadi {{ (invoices.find(i => i.sale_invoice === form.sale_invoice)?.koi_count ?? 0) + 1 }} koi
                                                </span>
                                            </div>
                                            <button type="button" @click="selectInvoice(null)" class="text-amber-500 hover:text-amber-700 dark:hover:text-amber-300">
                                                <X class="h-3 w-3" />
                                            </button>
                                        </div>


<InputError :message="(form.errors as any).sale_invoice" />
                                    </div>

                                    <!-- Azukari fields -->
                                    <template v-if="form.sale_status === 'azukari'">
                                        <div class="grid gap-1.5">
                                            <Label class="text-xs font-medium text-muted-foreground">Periode Azukari <span class="font-normal opacity-60">(opsional)</span></Label>
                                            <Input v-model="form.azukari_period" type="text" placeholder="Contoh: Nov 2024 – Nov 2025" class="h-9 text-sm" />
                                            <p class="text-[10px] text-muted-foreground">Teks bebas · akan tampil di invoice PDF</p>
                                            <InputError :message="(form.errors as any).azukari_period" />
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="grid gap-1.5">
                                                <Label class="text-xs font-medium text-muted-foreground">Qty Azukari</Label>
                                                <Input v-model="form.azukari_qty" type="number" min="1" placeholder="Qty" class="h-9 text-sm" />
                                                <InputError :message="(form.errors as any).azukari_qty" />
                                            </div>
                                            <div class="grid gap-1.5">
                                                <Label class="text-xs font-medium text-muted-foreground">Total Harga Azukari (JPY)</Label>
                                                <div class="relative">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-amber-500">¥</span>
                                                    <Input type="text" inputmode="numeric" :modelValue="azukariPriceDisplay" @update:modelValue="onAzukariPriceChange" @blur="() => { azukariPriceDisplay = addDots(form.azukari_price_jpy) }" placeholder="0" class="h-9 text-sm pl-7" />
                                                </div>
                                                <div v-if="azukariPriceIdr" class="rounded border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/30 px-2.5 py-1 text-[11px] flex justify-between">
                                                    <span class="text-muted-foreground">≈ IDR</span>
                                                    <span class="font-semibold text-amber-700 dark:text-amber-400">{{ fmtIDR(azukariPriceIdr) }}</span>
                                                </div>
                                                <InputError :message="(form.errors as any).azukari_price_jpy" />
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Tanggal & Qty -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="grid gap-1.5">
                                            <Label class="text-xs font-medium text-muted-foreground">Tanggal</Label>
                                            <Input v-model="form.sale_tanggal" type="date" class="h-9 text-sm" />
                                            <InputError :message="form.errors.sale_tanggal" />
                                        </div>
                                        <div class="grid gap-1.5">
                                            <Label class="text-xs font-medium text-muted-foreground">
                                                Qty Jual (ekor)
                                                <span v-if="modalQty" class="font-normal text-muted-foreground">maks. {{ modalQty }}</span>
                                            </Label>
                                            <Input
                                                v-model="form.sale_qty"
                                                type="number" min="1"
                                                :max="modalQty || undefined"
                                                placeholder="0"
                                                class="h-9 text-sm"
                                            />
                                            <p v-if="modalQty && Number(form.sale_qty) > Number(modalQty)" class="text-[10px] text-red-500">Melebihi qty modal ({{ modalQty }})</p>
                                            <p v-else class="text-[10px] text-muted-foreground">Bisa 1–{{ modalQty || '?' }} ekor</p>
                                            <InputError :message="form.errors.sale_qty" />
                                        </div>
                                    </div>

                                    <!-- Harga -->
                                    <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-950/20 p-3 space-y-3">
                                        <p class="text-[11px] font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wider">Harga</p>

                                        <!-- Stock JPY (dinonaktifkan sementara) -->
                                        <!-- <div class="space-y-1.5">
                                            <Label class="text-xs font-medium text-muted-foreground">Stock JPY</Label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-500">¥</span>
                                                <Input
                                                    type="text" inputmode="numeric"
                                                    :value="fmtInput(form.buy_jpy)"
                                                    @input="onBuyJpyInput"
                                                    placeholder="0"
                                                    class="h-9 text-sm pl-7"
                                                />
                                            </div>
                                            <InputError :message="(form.errors as any).buy_jpy" />
                                        </div> -->

                                        <!-- Sales Price JPY & Total Sales IDR -->
                                        <div class="grid grid-cols-2 gap-3">
                                            <!-- Sales Price JPY -->
                                            <div class="space-y-1.5">
                                                <Label class="text-xs font-semibold text-amber-700 dark:text-amber-400">Sales Price (JPY)</Label>
                                                <div class="relative">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-amber-500">¥</span>
                                                    <Input
                                                        type="text" inputmode="numeric"
                                                        :modelValue="unitPriceDisplay"
                                                        @update:modelValue="onUnitPriceChange"
                                                        @blur="onUnitPriceBlur"
                                                        placeholder="0"
                                                        class="h-9 text-sm pl-7"
                                                    />
                                                </div>
                                                <div v-if="unitPriceIdr" class="rounded border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/30 px-2.5 py-1 text-[11px] flex justify-between">
                                                    <span class="text-muted-foreground">≈ IDR/unit</span>
                                                    <span class="font-semibold text-amber-700 dark:text-amber-400">{{ fmtIDR(unitPriceIdr) }}</span>
                                                </div>
                                                <InputError :message="form.errors.sell_idr" />
                                            </div>
                                            <!-- Total Sales IDR (auto dari unit_price × qty × kurs) -->
                                            <div class="space-y-1.5">
                                                <Label class="text-xs font-semibold text-amber-700 dark:text-amber-400">Total Sales (IDR) <span class="font-normal opacity-60">auto</span></Label>
                                                <div class="flex h-9 items-center rounded-md border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-950/30 px-3 text-sm font-semibold text-amber-800 dark:text-amber-200">
                                                    {{ salesPriceIdr ? fmtIDR(salesPriceIdr) : '-' }}
                                                </div>
                                                <p class="text-[10px] text-muted-foreground">Sales JPY × {{ form.sale_qty || 1 }} ekor × kurs</p>
                                            </div>
                                        </div>

                                        <!-- Fee -->
                                        <div class="grid gap-1.5">
                                            <Label class="text-xs font-medium text-amber-700 dark:text-amber-400">Fee</Label>
                                            <Select v-model="feePct" @update:model-value="onFeeSelect">
                                                <SelectTrigger class="h-9 text-sm"><SelectValue placeholder="Pilih fee %" /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="0">No Fee</SelectItem>
                                                    <SelectItem value="5">5%</SelectItem>
                                                    <SelectItem value="7.5">7.5%</SelectItem>
                                                    <SelectItem value="10">10%</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError :message="form.errors.fee" />
                                        </div>
                                    </div>

                                    <!-- Ringkasan -->
                                    <div v-if="salesPriceIdr || totalModalIdr" class="rounded-md border border-border overflow-hidden text-xs">
                                        <div class="bg-muted/60 px-4 py-2 border-b border-border flex items-center justify-between">
                                            <p class="text-[11px] font-semibold text-foreground uppercase tracking-wider">Ringkasan</p>
                                            <span class="text-[10px] text-muted-foreground">Kurs ¥1 = Rp {{ Number(kursInput || 0).toLocaleString('id-ID') }}</span>
                                        </div>
                                        <div class="divide-y divide-border/60">
                                            <!-- Total Modal (per qty terjual) -->
                                            <div class="px-4 py-2.5 flex justify-between items-start">
                                                <div>
                                                    <p class="font-medium text-foreground">Modal Terjual</p>
                                                    <p class="text-[10px] text-muted-foreground mt-0.5">
                                                        ¥{{ Number(form.stock_price||0).toLocaleString('ja-JP') }} × kurs × {{ form.sale_qty || 1 }} ekor terjual
                                                    </p>
                                                </div>
                                                <span class="font-semibold text-blue-600 dark:text-blue-400">
                                                    {{ modalTerjualIdr ? fmtIDR(modalTerjualIdr) : '-' }}
                                                </span>
                                            </div>
                                            <!-- Total Sales -->
                                            <div class="px-4 py-2.5 flex justify-between items-start">
                                                <div>
                                                    <p class="font-medium text-foreground">Total Sales</p>
                                                    <p class="text-[10px] text-muted-foreground mt-0.5">
                                                        ¥{{ Number(form.unit_price||0).toLocaleString('ja-JP') }} × {{ form.sale_qty||1 }} ekor × kurs
                                                    </p>
                                                </div>
                                                <span class="font-semibold text-green-600 dark:text-green-400">
                                                    {{ salesPriceIdr ? fmtIDR(salesPriceIdr) : '-' }}
                                                </span>
                                            </div>
                                            <!-- Shipping -->
                                            <div v-if="shippingKena" class="px-4 py-2.5 flex justify-between items-start">
                                                <div>
                                                    <p class="font-medium text-foreground">Shipping</p>
                                                    <p class="text-[10px] text-muted-foreground mt-0.5">
                                                        {{ fmtIDR(shippingPerEkor) }}/ekor × {{ form.sale_qty || 1 }} ekor
                                                        <span v-if="form.shipping_note" class="ml-1 italic">· {{ form.shipping_note }}</span>
                                                    </p>
                                                </div>
                                                <span class="font-semibold text-orange-600 dark:text-orange-400">− {{ fmtIDR(shippingKena) }}</span>
                                            </div>
                                            <!-- Fee -->
                                            <div v-if="Number(form.fee) > 0" class="px-4 py-2.5 flex justify-between items-center">
                                                <p class="font-medium text-foreground">
                                                    Fee <span v-if="feePct && Number(feePct) > 0" class="font-normal text-muted-foreground">({{ feePct }}%)</span>
                                                </p>
                                                <span class="font-semibold text-green-600 dark:text-green-400">+ {{ fmtIDR(Number(form.fee)) }}</span>
                                            </div>
                                            <!-- Profit -->
                                            <div class="px-4 py-3 flex justify-between items-start bg-muted/30">
                                                <div>
                                                    <p class="font-bold text-sm text-foreground">Profit</p>
                                                    <p class="text-[10px] text-muted-foreground mt-0.5">Sales − Modal + Fee</p>
                                                </div>
                                                <span class="text-base font-bold"
                                                    :class="profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400'">
                                                    {{ fmtIDR(profit) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="border-t border-border pt-3 flex justify-end gap-2">
                                <Button type="button" variant="outline" @click="() => { form.reset(); form.clearErrors(); breederSearch = ''; jenisSearch = ''; customerSearch = ''; fotoPreviews = []; feePct = null; kursIsManual = false; modalQty = ''; showModal = false }"
>Batal</Button>
                                <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Menyimpan...' : 'Simpan' }}</Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- DataTable -->
            <div class="border border-sidebar-border/70 dark:border-sidebar-border rounded-lg overflow-visible">
                <div class="flex flex-col gap-2 border-b border-border p-4 rounded-t-lg sm:flex-row sm:items-center sm:justify-between">
                    <Input :model-value="kSearch" @update:model-value="v => { kSearch = String(v); kPage = 1 }" placeholder="Cari code, nama, atau breeder..." class="max-w-sm" />
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted-foreground">Show</span>
                        <select :value="kPerPage" @change="(e) => { kPerPage = Number((e.target as HTMLSelectElement).value); kPage = 1 }" class="rounded-md border border-input bg-background px-3 py-1 text-sm">
                            <option>5</option><option>10</option><option>25</option><option>50</option>
                        </select>
                        <span class="text-sm text-muted-foreground">entries</span>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-b-lg">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Foto</th>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="kToggleSort('code')">Code <span class="ml-1">{{ kSortIcon('code') }}</span></th>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="kToggleSort('name')">Nama <span class="ml-1">{{ kSortIcon('name') }}</span></th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Breeder</th>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground" @click="kToggleSort('created_at')">Created <span class="ml-1">{{ kSortIcon('created_at') }}</span></th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-background">
                            <tr v-if="paginatedKois.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-muted-foreground">{{ kSearch ? 'Tidak ada hasil.' : 'Belum ada data koi.' }}</td>
                            </tr>
                            <tr v-for="koi in paginatedKois" :key="koi.id" class="transition-colors hover:bg-muted/50">
                                <td class="px-4 py-3">
                                    <button type="button" @click="openImageModal(koi)" class="relative h-10 w-10 overflow-hidden rounded-full border border-border bg-muted">
                                        <img v-if="getFirstImage(koi)" :src="getFirstImage(koi)!" class="h-full w-full object-cover" />
                                        <div v-else class="flex h-full w-full items-center justify-center text-muted-foreground">
                                            <ImagePlus :size="16" />
                                        </div>
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium">{{ koi.code }}</td>
                                <td class="px-4 py-3 text-sm">{{ koi.name }}</td>
                                <td class="px-4 py-3 text-sm">{{ getBreederName(koi) }}</td>
                                <td class="px-4 py-3 text-sm">{{ fmtDate(koi.created_at) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span v-if="koi.sale_status" :class="statusClass(koi.sale_status)" class="inline-block rounded-full px-3 py-1 text-xs font-semibold">{{ statusLabel(koi.sale_status) }}</span>
                                    <span v-else class="text-sm text-muted-foreground">-</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Select :model-value="koi.sale_status || ''" @update:model-value="(val) => quickUpdateStatus(koi.id, String(val))">
                                            <SelectTrigger class="h-9 w-32 border border-input bg-background rounded-md px-3 py-1.5 text-sm hover:bg-muted/50">
                                                <SelectValue :placeholder="'Pilih Status'" />
                                            </SelectTrigger>
                                            <SelectContent side="top" class="z-50">
                                                <SelectItem value="cancel">Cancel</SelectItem>
                                                <SelectItem value="dead_refund">Dead Refund</SelectItem>
                                                <SelectItem value="refund">Refund</SelectItem>
                                                <SelectItem value="cancel_refund">Cancel Refund</SelectItem>
                                                <SelectItem value="pending">Pending</SelectItem>
                                                <SelectItem value="shipped">Shipped</SelectItem>
                                                <SelectItem value="azukari">Azukari</SelectItem>
                                                <SelectItem value="dead">Dead</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <Link :href="`/master-data/koi/${koi.id}`" class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background p-2 text-foreground shadow-sm hover:bg-muted" title="Detail"><Eye :size="16" /></Link>
                                        <button type="button" @click="openEditModal(koi)" class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background p-2 text-foreground shadow-sm hover:bg-muted" title="Edit"><Pencil :size="16" /></button>
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
                        <template v-for="p in kTotalPages" :key="p">
                            <Button size="sm" :variant="p === kPage ? 'default' : 'outline'" @click="kPage = p">{{ p }}</Button>
                        </template>
                        <Button variant="outline" size="sm" :disabled="kPage >= kTotalPages" @click="kPage++">Next</Button>
                    </div>
                </div>
            </div>

        </div>

        <!-- ══ Edit Modal ══ -->
        <Dialog v-model:open="showEditModal">
            <DialogContent class="sm:max-w-[680px] max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Edit Koi</DialogTitle>
                    <DialogDescription class="text-xs">Perbarui data koi dan informasi penjualan.</DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitEdit" class="py-4 space-y-5 px-1">

                    <!-- ══ SECTION: Data Koi ══ -->
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="h-4 w-1 rounded-full bg-primary"></div>
                            <p class="text-sm font-semibold">Data Koi</p>
                        </div>
                        <div class="space-y-3 pl-3">
                            <!-- Breeder & Jenis Koi -->
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="grid gap-1.5">
                                    <Label class="text-xs font-medium text-muted-foreground">Breeder <span class="text-red-500">*</span></Label>
                                    <Select v-model="editForm.breeder_id">
                                        <SelectTrigger class="w-full"><SelectValue placeholder="Pilih breeder" /></SelectTrigger>
                                        <SelectContent class="p-0 w-[--radix-select-trigger-width] min-w-[20rem]">
                                            <div class="sticky top-0 bg-background p-2 border-b">
                                                <Input v-model="editBreederSearch" placeholder="Cari breeder..." class="h-8" @keydown.stop />
                                            </div>
                                            <div class="max-h-44 overflow-y-auto">
                                                <div v-if="editFilteredBreeders.length === 0" class="px-3 py-2 text-sm text-muted-foreground">Tidak ada breeder</div>
                                                <SelectItem v-for="b in editFilteredBreeders" :key="b.id" :value="String(b.id)">{{ b.name_breeder }}</SelectItem>
                                            </div>
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="editForm.errors.breeder_id" />
                                </div>
                                <div class="grid gap-1.5">
                                    <Label class="text-xs font-medium text-muted-foreground">Jenis Koi</Label>
                                    <Select v-model="editForm.jenis_koi_id">
                                        <SelectTrigger class="w-full"><SelectValue placeholder="Pilih jenis koi" /></SelectTrigger>
                                        <SelectContent class="p-0 w-[--radix-select-trigger-width] min-w-[20rem]">
                                            <div class="sticky top-0 bg-background p-2 border-b">
                                                <Input v-model="editJenisSearch" placeholder="Cari jenis koi..." class="h-8" @keydown.stop />
                                            </div>
                                            <div class="max-h-44 overflow-y-auto">
                                                <div v-if="editFilteredJenisKois.length === 0" class="px-3 py-2 text-sm text-muted-foreground">Tidak ada</div>
                                                <SelectItem v-for="j in editFilteredJenisKois" :key="j.id" :value="String(j.id)">{{ j.nama_jenis }}</SelectItem>
                                            </div>
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="editForm.errors.jenis_koi_id" />
                                </div>
                            </div>

                            <!-- Kode & Nama -->
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="grid gap-1.5">
                                    <Label class="text-xs font-medium text-muted-foreground">Kode Koi <span class="text-red-500">*</span></Label>
                                    <Input v-model="editForm.code" placeholder="KOI-001" />
                                    <InputError :message="editForm.errors.code" />
                                </div>
                                <div class="grid gap-1.5">
                                    <Label class="text-xs font-medium text-muted-foreground">Nama Koi <span class="text-red-500">*</span></Label>
                                    <Input v-model="editForm.name" placeholder="Nama koi" />
                                    <InputError :message="editForm.errors.name" />
                                </div>
                            </div>

                            <!-- Data Fisik -->
                            <div class="rounded-md border border-border bg-muted/20 p-3 space-y-2.5">
                                <p class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">Data Fisik</p>
                                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-5">
                                    <div class="grid gap-1">
                                        <Label class="text-[11px] text-muted-foreground">Age (bln)</Label>
                                        <Input v-model="editForm.age" type="number" min="0" placeholder="0" class="h-8 text-sm" />
                                    </div>
                                    <div class="grid gap-1">
                                        <Label class="text-[11px] text-muted-foreground">Size (cm)</Label>
                                        <Input v-model="editForm.size" type="number" min="0" step="0.01" placeholder="0" class="h-8 text-sm" />
                                    </div>
                                    <div class="grid gap-1">
                                        <Label class="text-[11px] text-muted-foreground">Box QTY</Label>
                                        <Input v-model="editForm.box_qty" type="number" min="0" placeholder="0" class="h-8 text-sm" />
                                    </div>
                                    <div class="grid gap-1">
                                        <Label class="text-[11px] text-muted-foreground">Weight (kg)</Label>
                                        <Input v-model="editForm.weight" type="number" min="0" step="0.001" placeholder="0" class="h-8 text-sm" />
                                    </div>
                                    <div class="grid gap-1">
                                        <Label class="text-[11px] text-muted-foreground">Total Weight (kg) <span class="opacity-60">auto</span></Label>
                                        <div class="flex h-8 items-center rounded-md border border-border bg-muted/50 px-2 text-sm font-medium text-foreground">
                                            {{ editForm.total_weight || '-' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Foto Koi -->
                            <div class="rounded-md border border-border bg-muted/20 p-3 space-y-2">
                                <p class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">Foto Koi</p>
                                <div v-if="editExistingImages.length > 0" class="flex flex-wrap gap-2">
                                    <div v-for="img in editExistingImages" :key="img.id" class="relative h-16 w-16 overflow-hidden rounded-lg border group">
                                        <img :src="img.url" class="h-full w-full object-cover" />
                                        <button type="button" @click="markDeleteEditExisting(img.id)" class="absolute inset-0 flex items-center justify-center bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <X class="h-4 w-4 text-white" />
                                        </button>
                                    </div>
                                </div>
                                <div v-if="editFotoPreviews.length > 0" class="flex flex-wrap gap-2">
                                    <div v-for="(p, i) in editFotoPreviews" :key="i" class="relative h-16 w-16 overflow-hidden rounded-lg border group">
                                        <img :src="p.url" class="h-full w-full object-cover" />
                                        <button type="button" @click="removeEditNewImage(i)" class="absolute inset-0 flex items-center justify-center bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <X class="h-4 w-4 text-white" />
                                        </button>
                                    </div>
                                </div>
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border-2 border-dashed border-border px-3 py-2 text-xs text-muted-foreground hover:border-primary/50 hover:text-foreground transition-colors">
                                    <ImagePlus class="h-4 w-4 flex-shrink-0" />
                                    <span>Pilih foto (jpg, png, webp · maks. 2 MB · bisa beberapa)</span>
                                    <input type="file" accept="image/jpg,image/jpeg,image/png,image/webp" multiple class="sr-only" @change="handleEditImagesChange" />
                                </label>

                                <!-- Description -->
                                <div class="space-y-1.5">
                                    <label class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">Description <span class="font-normal normal-case opacity-60">(opsional)</span></label>
                                    <textarea
                                        v-model="editForm.description"
                                        rows="3"
                                        placeholder="Catatan atau keterangan tambahan tentang koi ini..."
                                        class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none transition-[color,box-shadow] placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] resize-none"
                                    />
                                    <InputError :message="(editForm.errors as any).description" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-border"></div>

                    <!-- ══ Kurs JPY → IDR ══ -->
                    <div class="rounded-lg border-2 border-red-400 dark:border-red-600 bg-red-50 dark:bg-red-950/30 p-3 space-y-1.5">
                        <div class="flex items-center justify-between">
                            <Label class="text-xs font-semibold text-red-700 dark:text-red-400">Kurs JPY → IDR</Label>
                            <div class="flex items-center gap-1.5">
                                <span v-if="exchangeRateLoading" class="text-[10px] text-red-400 animate-pulse">Memuat kurs...</span>
                                <template v-else>
                                    <span v-if="editKursIsManual" class="inline-flex items-center gap-1 rounded-full bg-orange-100 dark:bg-orange-900/50 px-2 py-0.5 text-[10px] font-medium text-orange-600 dark:text-orange-400">✎ Manual</span>
                                    <span v-else-if="exchangeRateError" class="inline-flex items-center gap-1 rounded-full bg-yellow-100 dark:bg-yellow-900/50 px-2 py-0.5 text-[10px] font-medium text-yellow-700 dark:text-yellow-400">⚠ Fallback</span>
                                    <span v-else class="inline-flex items-center gap-1 rounded-full bg-green-100 dark:bg-green-900/50 px-2 py-0.5 text-[10px] font-medium text-green-700 dark:text-green-400">✓ Live API</span>
                                    <button v-if="editKursIsManual" type="button" @click="editKursIsManual = false; editKursInput = String(exchangeRate)" class="text-[10px] underline text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition-colors">Gunakan API</button>
                                </template>
                            </div>
                        </div>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-red-600 dark:text-red-400">Rp</span>
                            <input type="text" inputmode="numeric" :value="fmtInput(editKursInput)"
                                @input="(e: Event) => { editKursIsManual = true; editKursInput = parseInput(e) }"
                                placeholder="0"
                                class="flex h-9 w-full rounded-md border-2 border-red-400 dark:border-red-600 bg-white dark:bg-red-900/20 pl-9 pr-14 text-sm font-semibold text-red-800 dark:text-red-100 placeholder:text-red-300 dark:placeholder:text-red-700 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-200 dark:focus:ring-red-800/50 transition-colors" />
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-red-500 dark:text-red-400">/ ¥1</span>
                        </div>
                        <p class="text-[11px] text-red-600/80 dark:text-red-400/80">Otomatis dari API · langsung ketik untuk ubah manual</p>
                    </div>

                    <div class="border-t border-border"></div>

                    <!-- ══ SECTION: Modal ══ -->
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="h-4 w-1 rounded-full bg-blue-500"></div>
                            <p class="text-sm font-semibold">Modal</p>
                        </div>
                        <div class="space-y-3 pl-3">
                            <div class="rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50/50 dark:bg-blue-950/20 p-3 space-y-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <!-- Modal Price JPY -->
                                    <div class="space-y-1.5">
                                        <Label class="text-xs font-semibold text-blue-700 dark:text-blue-400">Modal Price (JPY/ekor)</Label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-blue-500">¥</span>
                                            <Input
                                                type="text" inputmode="numeric"
                                                :modelValue="editStockPriceDisplay"
                                                @update:modelValue="onEditStockPriceChange"
                                                placeholder="0"
                                                class="h-9 text-sm pl-7 border-blue-300 dark:border-blue-700 focus:border-blue-500"
                                            />
                                        </div>
                                        <div v-if="editModalPriceIdr" class="rounded border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/30 px-2.5 py-1 text-[11px] flex justify-between">
                                            <span class="text-muted-foreground">≈ IDR/ekor</span>
                                            <span class="font-semibold text-blue-700 dark:text-blue-400">{{ fmtIDR(editModalPriceIdr) }}</span>
                                        </div>
                                        <InputError :message="(editForm.errors as any).stock_price" />
                                    </div>
                                    <!-- Qty Modal (stok disiapkan) -->
                                    <div class="space-y-1.5">
                                        <Label class="text-xs font-semibold text-blue-700 dark:text-blue-400">Qty Modal (ekor)</Label>
                                        <Input
                                            type="number" min="1" placeholder="0"
                                            v-model="editModalQty"
                                            class="h-9 text-sm border-blue-300 dark:border-blue-700"
                                        />
                                        <p class="text-[10px] text-muted-foreground">Stok yang disiapkan</p>
                                    </div>
                                </div>
                                <!-- Total Modal -->
                                <div v-if="editTotalModalIdr" class="rounded border border-blue-300 dark:border-blue-700 bg-blue-100 dark:bg-blue-900/40 px-3 py-2 text-[11px] flex justify-between items-center">
                                    <span class="text-blue-700 dark:text-blue-300 font-medium">Total Modal ({{ editModalQty || 1 }} ekor)</span>
                                    <span class="font-bold text-blue-800 dark:text-blue-200">{{ fmtIDR(editTotalModalIdr) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-border"></div>

                    <!-- ══ SECTION: Shipping ══ -->
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="h-4 w-1 rounded-full bg-orange-500"></div>
                            <p class="text-sm font-semibold">Shipping</p>
                            <span class="text-xs text-muted-foreground">(opsional)</span>
                        </div>
                        <div class="space-y-3 pl-3">
                            <div class="rounded-lg border border-orange-200 dark:border-orange-800 bg-orange-50/50 dark:bg-orange-950/20 p-3 space-y-3">
                                <!-- Batch selector — selalu di atas -->
                                <div class="space-y-1.5">
                                    <Label class="text-xs font-medium text-orange-700 dark:text-orange-400">Shipping Batch <span class="font-normal opacity-60">(untuk ikan patungan 1 box)</span></Label>
                                    <div class="flex gap-2">
                                        <select v-model="editForm.shipping_batch" class="flex-1 h-9 rounded-md border border-orange-300 dark:border-orange-700 bg-background px-3 text-sm">
                                            <option value="">— Tanpa batch / isi manual —</option>
                                            <option v-for="b in allBatchOptions" :key="b" :value="b"
                                                :disabled="!!(page.props.shippingBatchDetails as any)?.[b] && (page.props.shippingBatchDetails as any)[b].shipping_qty != null && (page.props.shippingBatchDetails as any)[b].koi_count >= (page.props.shippingBatchDetails as any)[b].shipping_qty">
                                                {{ b }}{{ (page.props.shippingBatchDetails as any)?.[b]?.shipping_qty != null ? ` (${(page.props.shippingBatchDetails as any)[b].koi_count}/${(page.props.shippingBatchDetails as any)[b].shipping_qty}${(page.props.shippingBatchDetails as any)[b].koi_count >= (page.props.shippingBatchDetails as any)[b].shipping_qty ? ' Full' : ''})` : '' }}
                                            </option>
                                        </select>
                                        <button type="button" @click="generateEditBatchCode" :disabled="!!editForm.shipping_batch"
                                            class="h-9 px-3 rounded-md border border-orange-300 dark:border-orange-700 bg-orange-50 dark:bg-orange-950 text-xs text-orange-700 dark:text-orange-300 hover:bg-orange-100 whitespace-nowrap disabled:opacity-40 disabled:cursor-not-allowed">
                                            + Buat Baru
                                        </button>
                                    </div>
                                    <InputError :message="(editForm.errors as any).shipping_batch" />
                                </div>

                                <!-- Batch lama dipilih: info + kalkulasi -->
                                <template v-if="editForm.shipping_batch && shippingBatches.includes(editForm.shipping_batch)">
                                    <div class="rounded border border-orange-300 dark:border-orange-700 bg-white dark:bg-orange-950/40 p-2.5 space-y-1.5">
                                        <p class="text-[10px] font-semibold uppercase tracking-wide text-orange-600 dark:text-orange-400">Info Patungan Shipping</p>
                                        <div class="flex justify-between text-[11px]">
                                            <span class="text-muted-foreground">Total biaya box</span>
                                            <span class="font-semibold">{{ (page.props.shippingBatchDetails as any)?.[editForm.shipping_batch]?.shipping_cost != null ? 'Rp ' + Number((page.props.shippingBatchDetails as any)[editForm.shipping_batch].shipping_cost).toLocaleString('id-ID') : '—' }}</span>
                                        </div>
                                        <div class="flex justify-between text-[11px]">
                                            <span class="text-muted-foreground">Total ikan sepaket</span>
                                            <span class="font-semibold">{{ (page.props.shippingBatchDetails as any)?.[editForm.shipping_batch]?.shipping_qty ?? '—' }} ekor</span>
                                        </div>
                                        <div class="flex justify-between text-[11px]">
                                            <span class="text-muted-foreground">Sudah ada</span>
                                            <span class="font-semibold">{{ (page.props.shippingBatchDetails as any)?.[editForm.shipping_batch]?.koi_count ?? 0 }} ekor dari customer lain</span>
                                        </div>
                                        <div v-if="(page.props.shippingBatchDetails as any)?.[editForm.shipping_batch]?.members?.length" class="border-t border-orange-200 dark:border-orange-800 pt-1.5 space-y-1">
                                            <div v-for="m in (page.props.shippingBatchDetails as any)[editForm.shipping_batch].members" :key="m.code" class="flex justify-between text-[11px]">
                                                <span class="text-muted-foreground">{{ m.customer ?? '—' }} <span class="font-mono">{{ m.code }}</span></span>
                                                <span>{{ m.sale_qty ?? 1 }} ekor</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="editShippingPerEkor" class="rounded border border-orange-300 dark:border-orange-700 bg-orange-100 dark:bg-orange-900/40 px-3 py-2 text-[11px] space-y-1">
                                        <div class="flex justify-between items-center">
                                            <span class="text-orange-700 dark:text-orange-300 font-medium">Shipping/ekor</span>
                                            <span class="font-semibold text-orange-800 dark:text-orange-200">{{ fmtIDR(editShippingPerEkor) }}</span>
                                        </div>
                                        <div v-if="editShippingKena" class="flex justify-between items-center border-t border-orange-200 dark:border-orange-800 pt-1">
                                            <span class="text-orange-700 dark:text-orange-300 font-medium">Total Shipping ({{ editForm.sale_qty || 1 }} ekor)</span>
                                            <span class="font-bold text-orange-800 dark:text-orange-200">{{ fmtIDR(editShippingKena) }}</span>
                                        </div>
                                    </div>
                                </template>

                                <!-- Batch baru / tanpa batch: input manual -->
                                <template v-else>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="space-y-1.5">
                                            <Label class="text-xs font-semibold text-orange-700 dark:text-orange-400">Total Biaya Shipping (IDR)</Label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-orange-500">Rp</span>
                                                <Input type="text" inputmode="numeric" :modelValue="editShippingCostDisplay2" @update:modelValue="onEditShippingCostChange" placeholder="0" class="h-9 text-sm pl-9 border-orange-300 dark:border-orange-700" />
                                            </div>
                                            <InputError :message="(editForm.errors as any).shipping_cost" />
                                        </div>
                                        <div class="space-y-1.5">
                                            <Label class="text-xs font-semibold text-orange-700 dark:text-orange-400">Jumlah Ikan Sepaket</Label>
                                            <Input v-model="editForm.shipping_qty" type="number" min="1" placeholder="0" class="h-9 text-sm border-orange-300 dark:border-orange-700" />
                                            <p class="text-[10px] text-muted-foreground">Total ikan dalam 1 pengiriman</p>
                                            <InputError :message="(editForm.errors as any).shipping_qty" />
                                        </div>
                                    </div>
                                    <div v-if="editShippingPerEkor" class="rounded border border-orange-300 dark:border-orange-700 bg-orange-100 dark:bg-orange-900/40 px-3 py-2 text-[11px] space-y-1">
                                        <div class="flex justify-between items-center">
                                            <span class="text-orange-700 dark:text-orange-300 font-medium">Shipping/ekor</span>
                                            <span class="font-semibold text-orange-800 dark:text-orange-200">{{ fmtIDR(editShippingPerEkor) }}</span>
                                        </div>
                                        <div v-if="editShippingKena" class="flex justify-between items-center border-t border-orange-200 dark:border-orange-800 pt-1">
                                            <span class="text-orange-700 dark:text-orange-300 font-medium">Total Shipping ({{ editForm.sale_qty || 1 }} ekor)</span>
                                            <span class="font-bold text-orange-800 dark:text-orange-200">{{ fmtIDR(editShippingKena) }}</span>
                                        </div>
                                    </div>
                                </template>

                                <!-- Keterangan & Tanggal Shipping -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="space-y-1.5">
                                        <Label class="text-xs font-medium text-orange-700 dark:text-orange-400">Keterangan <span class="font-normal opacity-60">(opsional)</span></Label>
                                        <Input v-model="editForm.shipping_note" type="text" placeholder="Contoh: Sea freight Nov 2025, JNE Express..." class="h-9 text-sm border-orange-300 dark:border-orange-700" />
                                        <InputError :message="(editForm.errors as any).shipping_note" />
                                    </div>
                                    <div class="space-y-1.5">
                                        <Label class="text-xs font-medium text-orange-700 dark:text-orange-400">Shipping Date <span class="font-normal opacity-60">(opsional)</span></Label>
                                        <Input v-model="editForm.shipping_date" type="date" class="h-9 text-sm border-orange-300 dark:border-orange-700" />
                                        <InputError :message="(editForm.errors as any).shipping_date" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-border"></div>

                    <!-- ══ SECTION: Transaksi ══ -->
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="h-4 w-1 rounded-full bg-green-500"></div>
                            <p class="text-sm font-semibold">Transaksi</p>
                        </div>
                        <div class="space-y-3 pl-3">

                            <!-- Customer & Status -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="grid gap-1.5">
                                    <Label class="text-xs font-medium text-muted-foreground">Customer</Label>
                                    <Select v-model="editForm.customer_id">
                                        <SelectTrigger class="w-full"><SelectValue placeholder="Pilih customer" /></SelectTrigger>
                                        <SelectContent class="p-0 w-[--radix-select-trigger-width] min-w-[20rem]">
                                            <div class="sticky top-0 bg-background p-2 border-b flex items-center gap-1.5">
                                                <Input v-model="editCustomerSearch" placeholder="Cari customer..." class="h-8 flex-1" @keydown.stop />
                                                <a href="/admin/hak-akses/customer" target="_blank" title="Buka halaman Customer"
                                                    class="flex-shrink-0 inline-flex items-center justify-center h-8 w-8 rounded-md border border-border bg-background hover:bg-muted text-muted-foreground hover:text-foreground transition-colors">
                                                    <ExternalLink class="w-3.5 h-3.5" />
                                                </a>
                                            </div>
                                            <div class="max-h-44 overflow-y-auto">
                                                <div v-if="editFilteredCustomers.length === 0" class="px-3 py-2 text-sm text-muted-foreground">Tidak ada customer</div>
                                                <SelectItem v-for="c in editFilteredCustomers" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                                            </div>
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="editForm.errors.customer_id" />
                                </div>
                                <div class="grid gap-1.5">
                                    <Label class="text-xs font-medium text-muted-foreground">Status</Label>
                                    <Select v-model="editForm.sale_status">
                                        <SelectTrigger class="w-full"><SelectValue placeholder="Pilih status" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="pending">Pending</SelectItem>
                                            <SelectItem value="shipped">Shipped</SelectItem>
                                            <SelectItem value="azukari">Azukari</SelectItem>
                                            <SelectItem value="dead">Dead</SelectItem>
                                            <SelectItem value="cancel">Cancel</SelectItem>
                                            <SelectItem value="refund">Refund</SelectItem>
                                            <SelectItem value="dead_refund">Dead Refund</SelectItem>
                                            <SelectItem value="cancel_refund">Cancel Refund</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="editForm.errors.sale_status" />
                                </div>
                            </div>

                            <!-- Invoice -->
                            <div class="grid gap-1.5">
                                <Label class="text-xs font-medium text-muted-foreground">
                                    No. Invoice
                                    <span class="font-normal opacity-60">(opsional)</span>
                                </Label>
                                <div class="relative">
                                    <div class="flex items-center rounded-md border border-input bg-background shadow-sm focus-within:ring-1 focus-within:ring-ring">
                                        <FileText class="ml-3 h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                                        <input
                                            v-model="editForm.sale_invoice"
                                            type="text"
                                            placeholder="Ketik nomor invoice atau pilih existing..."
                                            class="flex h-9 w-full bg-transparent px-2 text-sm font-mono outline-none placeholder:font-sans placeholder:text-muted-foreground"
                                            @focus="editInvoiceDropdownOpen = true"
                                            @blur="closeEditInvoiceDropdown"
                                            @input="(e) => { sanitizeInvoice(e.target, v => { editForm.sale_invoice = v }); editInvoiceDropdownOpen = true }"
                                            @keydown.enter.prevent="editInvoiceDropdownOpen = false"
                                            @keydown.escape="editInvoiceDropdownOpen = false"
                                        />
                                        <button v-if="editForm.sale_invoice" type="button" @mousedown.prevent @click="selectEditInvoice(null)" class="mr-2 shrink-0 text-muted-foreground hover:text-foreground">
                                            <X class="h-3.5 w-3.5" />
                                        </button>
                                        <button type="button" @mousedown.prevent @click="editInvoiceDropdownOpen = !editInvoiceDropdownOpen" class="mr-2 shrink-0 text-muted-foreground hover:text-foreground">
                                            <ChevronDown class="h-3.5 w-3.5 transition-transform" :class="editInvoiceDropdownOpen ? 'rotate-180' : ''" />
                                        </button>
                                    </div>
                                    <div
                                        v-if="editInvoiceDropdownOpen && (editFilteredInvoices.length > 0 || editForm.sale_invoice)"
                                        class="edit-invoice-panel absolute z-50 mt-1 w-full rounded-md border border-border bg-popover text-popover-foreground shadow-md"
                                        tabindex="-1"
                                    >
                                        <div class="max-h-52 overflow-y-auto py-1">
                                            <template v-if="editFilteredInvoices.length > 0">
                                                <div class="px-3 py-1 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground/60">Invoice Existing</div>
                                                <button
                                                    v-for="inv in editFilteredInvoices"
                                                    :key="inv.sale_invoice"
                                                    type="button"
                                                    @click="selectEditInvoice(inv.sale_invoice)"
                                                    class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                                                >
                                                    <Check class="h-3.5 w-3.5 shrink-0" :class="editForm.sale_invoice === inv.sale_invoice ? 'text-primary' : 'opacity-0'" />
                                                    <div class="flex flex-1 items-center justify-between gap-2 min-w-0">
                                                        <span class="font-mono font-semibold text-sm truncate">{{ inv.sale_invoice }}</span>
                                                        <span class="rounded bg-muted px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground shrink-0">{{ inv.koi_count }} koi</span>
                                                    </div>
                                                </button>
                                            </template>
                                            <div v-if="editFilteredInvoices.length === 0" class="px-3 py-4 text-center text-xs text-muted-foreground">
                                                Tidak ada invoice yang cocok
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <InputError :message="(editForm.errors as any).sale_invoice" />
                            </div>

                            <!-- Azukari fields -->
                            <template v-if="editForm.sale_status === 'azukari'">
                                <div class="grid gap-1.5">
                                    <Label class="text-xs font-medium text-muted-foreground">Periode Azukari <span class="font-normal opacity-60">(opsional)</span></Label>
                                    <Input v-model="editForm.azukari_period" type="text" placeholder="Contoh: Nov 2024 – Nov 2025" class="h-9 text-sm" />
                                    <p class="text-[10px] text-muted-foreground">Teks bebas · akan tampil di invoice PDF</p>
                                    <InputError :message="(editForm.errors as any).azukari_period" />
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="grid gap-1.5">
                                        <Label class="text-xs font-medium text-muted-foreground">Qty Azukari</Label>
                                        <Input v-model="editForm.azukari_qty" type="number" min="1" placeholder="Qty" class="h-9 text-sm" />
                                        <InputError :message="(editForm.errors as any).azukari_qty" />
                                    </div>
                                    <div class="grid gap-1.5">
                                        <Label class="text-xs font-medium text-muted-foreground">Total Harga Azukari (JPY)</Label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-amber-500">¥</span>
                                            <Input type="text" inputmode="numeric" :modelValue="editAzukariPriceDisplay" @update:modelValue="onEditAzukariPriceChange" @blur="() => { editAzukariPriceDisplay = addDots(editForm.azukari_price_jpy) }" placeholder="0" class="h-9 text-sm pl-7" />
                                        </div>
                                        <div v-if="editAzukariPriceIdr" class="rounded border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/30 px-2.5 py-1 text-[11px] flex justify-between">
                                            <span class="text-muted-foreground">≈ IDR</span>
                                            <span class="font-semibold text-amber-700 dark:text-amber-400">{{ fmtIDR(editAzukariPriceIdr) }}</span>
                                        </div>
                                        <InputError :message="(editForm.errors as any).azukari_price_jpy" />
                                    </div>
                                </div>
                            </template>

                            <!-- Tanggal & Qty -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="grid gap-1.5">
                                    <Label class="text-xs font-medium text-muted-foreground">Tanggal</Label>
                                    <Input v-model="editForm.sale_tanggal" type="date" class="h-9 text-sm" />
                                    <InputError :message="editForm.errors.sale_tanggal" />
                                </div>
                                <div class="grid gap-1.5">
                                    <Label class="text-xs font-medium text-muted-foreground">
                                        Qty Jual (ekor)
                                        <span v-if="editModalQty" class="font-normal text-muted-foreground">maks. {{ editModalQty }}</span>
                                    </Label>
                                    <Input
                                        v-model="editForm.sale_qty"
                                        type="number" min="1"
                                        :max="editModalQty || undefined"
                                        placeholder="0"
                                        class="h-9 text-sm"
                                    />
                                    <p v-if="editModalQty && Number(editForm.sale_qty) > Number(editModalQty)" class="text-[10px] text-red-500">Melebihi qty modal ({{ editModalQty }})</p>
                                    <p v-else class="text-[10px] text-muted-foreground">Bisa 1–{{ editModalQty || '?' }} ekor</p>
                                    <InputError :message="editForm.errors.sale_qty" />
                                </div>
                            </div>


                            <!-- Harga -->
                            <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-950/20 p-3 space-y-3">
                                <p class="text-[11px] font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wider">Harga</p>

                                <!-- Sales Price JPY & Total Sales IDR -->
                                <div class="grid grid-cols-2 gap-3">
                                    <!-- Sales Price JPY -->
                                    <div class="space-y-1.5">
                                        <Label class="text-xs font-semibold text-amber-700 dark:text-amber-400">Sales Price (JPY)</Label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-amber-500">¥</span>
                                            <Input
                                                type="text" inputmode="numeric"
                                                :modelValue="editUnitPriceDisplay"
                                                @update:modelValue="onEditUnitPriceChange"
                                                placeholder="0"
                                                class="h-9 text-sm pl-7"
                                            />
                                        </div>
                                        <div v-if="editUnitPriceIdr" class="rounded border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/30 px-2.5 py-1 text-[11px] flex justify-between">
                                            <span class="text-muted-foreground">≈ IDR/unit</span>
                                            <span class="font-semibold text-amber-700 dark:text-amber-400">{{ fmtIDR(editUnitPriceIdr) }}</span>
                                        </div>
                                        <InputError :message="editForm.errors.sell_idr" />
                                    </div>
                                    <!-- Total Sales IDR (auto) -->
                                    <div class="space-y-1.5">
                                        <Label class="text-xs font-semibold text-amber-700 dark:text-amber-400">Total Sales (IDR) <span class="font-normal opacity-60">auto</span></Label>
                                        <div class="flex h-9 items-center rounded-md border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-950/30 px-3 text-sm font-semibold text-amber-800 dark:text-amber-200">
                                            {{ editSalesPriceIdr ? fmtIDR(editSalesPriceIdr) : '-' }}
                                        </div>
                                        <p class="text-[10px] text-muted-foreground">Sales JPY × {{ editForm.sale_qty || 1 }} ekor × kurs</p>
                                    </div>
                                </div>

                                <!-- Fee -->
                                <div class="grid gap-1.5">
                                    <Label class="text-xs font-medium text-amber-700 dark:text-amber-400">Fee</Label>
                                    <Select v-model="editFeePct" @update:model-value="onEditFeeSelect">
                                        <SelectTrigger class="h-9 text-sm"><SelectValue placeholder="Pilih fee %" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="0">No Fee</SelectItem>
                                            <SelectItem value="5">5%</SelectItem>
                                            <SelectItem value="7.5">7.5%</SelectItem>
                                            <SelectItem value="10">10%</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="editForm.errors.fee" />
                                </div>
                            </div>

                            <!-- Ringkasan -->
                            <div v-if="editSalesPriceIdr || editModalTerjualIdr" class="rounded-md border border-border overflow-hidden text-xs">
                                <div class="bg-muted/60 px-4 py-2 border-b border-border flex items-center justify-between">
                                    <p class="text-[11px] font-semibold text-foreground uppercase tracking-wider">Ringkasan</p>
                                    <span class="text-[10px] text-muted-foreground">Kurs ¥1 = Rp {{ Number(editKursInput || 0).toLocaleString('id-ID') }}</span>
                                </div>
                                <div class="divide-y divide-border/60">
                                    <!-- Modal Terjual -->
                                    <div class="px-4 py-2.5 flex justify-between items-start">
                                        <div>
                                            <p class="font-medium text-foreground">Modal Terjual</p>
                                            <p class="text-[10px] text-muted-foreground mt-0.5">
                                                ¥{{ Number(editForm.stock_price||0).toLocaleString('ja-JP') }} × kurs × {{ editForm.sale_qty || 1 }} ekor terjual
                                            </p>
                                        </div>
                                        <span class="font-semibold text-blue-600 dark:text-blue-400">
                                            {{ editModalTerjualIdr ? fmtIDR(editModalTerjualIdr) : '-' }}
                                        </span>
                                    </div>
                                    <!-- Total Sales -->
                                    <div class="px-4 py-2.5 flex justify-between items-start">
                                        <div>
                                            <p class="font-medium text-foreground">Total Sales</p>
                                            <p class="text-[10px] text-muted-foreground mt-0.5">
                                                ¥{{ Number(editForm.unit_price||0).toLocaleString('ja-JP') }} × {{ editForm.sale_qty||1 }} ekor × kurs
                                            </p>
                                        </div>
                                        <span class="font-semibold text-green-600 dark:text-green-400">
                                            {{ editSalesPriceIdr ? fmtIDR(editSalesPriceIdr) : '-' }}
                                        </span>
                                    </div>
                                    <!-- Shipping -->
                                    <div v-if="editShippingKena" class="px-4 py-2.5 flex justify-between items-start">
                                        <div>
                                            <p class="font-medium text-foreground">Shipping</p>
                                            <p class="text-[10px] text-muted-foreground mt-0.5">
                                                {{ fmtIDR(editShippingPerEkor) }}/ekor × {{ editForm.sale_qty || 1 }} ekor
                                                <span v-if="editForm.shipping_note" class="ml-1 italic">· {{ editForm.shipping_note }}</span>
                                            </p>
                                        </div>
                                        <span class="font-semibold text-orange-600 dark:text-orange-400">− {{ fmtIDR(editShippingKena) }}</span>
                                    </div>
                                    <!-- Fee -->
                                    <div v-if="Number(editForm.fee) > 0" class="px-4 py-2.5 flex justify-between items-center">
                                        <p class="font-medium text-foreground">
                                            Fee <span v-if="editFeePct && Number(editFeePct) > 0" class="font-normal text-muted-foreground">({{ editFeePct }}%)</span>
                                        </p>
                                        <span class="font-semibold text-green-600 dark:text-green-400">+ {{ fmtIDR(Number(editForm.fee)) }}</span>
                                    </div>
                                    <!-- Profit -->
                                    <div class="px-4 py-3 flex justify-between items-start bg-muted/30">
                                        <div>
                                            <p class="font-bold text-sm text-foreground">Profit</p>
                                            <p class="text-[10px] text-muted-foreground mt-0.5">Sales − Modal + Fee</p>
                                        </div>
                                        <span class="text-base font-bold"
                                            :class="editProfitPositive ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400'">
                                            {{ fmtIDR(editProfit) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="border-t border-border pt-3 flex justify-end gap-2">
                        <Button type="button" variant="outline" @click="showEditModal = false">Batal</Button>
                        <Button type="submit" :disabled="editForm.processing">{{ editForm.processing ? 'Menyimpan...' : 'Simpan' }}</Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Image Modal -->
        <Dialog v-model:open="showImageModal">
            <DialogContent class="sm:max-w-[600px] max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Foto Koi</DialogTitle>
                    <DialogDescription>Daftar foto untuk koi ini.</DialogDescription>
                </DialogHeader>
                <div v-if="selectedKoiImages.length > 0" class="grid grid-cols-2 gap-3">
                    <div v-for="img in selectedKoiImages" :key="img.id" class="relative aspect-square overflow-hidden rounded-lg border">
                        <img :src="img.url" class="h-full w-full object-cover" />
                    </div>
                </div>
                <div v-else class="py-8 text-center text-muted-foreground">
                    <ImagePlus class="mx-auto h-12 w-12 opacity-50" />
                    <p class="mt-2 text-sm">Tidak ada foto</p>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="showImageModal = false">Tutup</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
