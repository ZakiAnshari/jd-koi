<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import InputError from '@/components/InputError.vue'
import type { BreadcrumbItem } from '@/types'

interface Pembelian {
    id: number
    date: string
    price_jpy: number
    price_idr: number
}

interface Breeder {
    id: number
    name_breeder: string
}

interface KatalogKoi {
    id: number
    pembelian_id: number
    kode_koi: string
    nama_koi: string
    breeder_id: number | null
    foto: string | null
    ukuran: number | null
    jenis_kelamin: string
    umur: string | null
    deskripsi: string | null
    harga_beli_jpy: number
    harga_beli_idr: number
    harga_jual_jpy: number | null
    harga_jual_idr: number
    fee: number
    qty: number
    status: string
    pembelian?: Pembelian
    breeder?: Breeder
}

const props = defineProps<{
    katalog: KatalogKoi
    breeders: Breeder[]
}>()

const page = usePage()
const fotoPreview = ref<string | null>(null)

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '#',
    },
    {
        title: 'Katalog Koi',
        href: '/admin/katalog-koi',
    },
    {
        title: 'Edit',
        href: `/admin/katalog-koi/${props.katalog.id}/edit`,
    },
]

const form = useForm({
    pembelian_id: props.katalog.pembelian_id,
    kode_koi: props.katalog.kode_koi,
    nama_koi: props.katalog.nama_koi,
    breeder_id: props.katalog.breeder_id?.toString() || '',
    foto: null as File | null,
    ukuran: props.katalog.ukuran,
    jenis_kelamin: props.katalog.jenis_kelamin,
    umur: props.katalog.umur || '',
    deskripsi: props.katalog.deskripsi || '',
    harga_beli_jpy: props.katalog.harga_beli_jpy,
    harga_beli_idr: props.katalog.harga_beli_idr,
    harga_jual_jpy: props.katalog.harga_jual_jpy,
    harga_jual_idr: props.katalog.harga_jual_idr,
    fee: props.katalog.fee,
    qty: props.katalog.qty,
    status: props.katalog.status,
})

const handleFotoChange = (e: Event) => {
    const input = e.target as HTMLInputElement
    if (input.files && input.files[0]) {
        form.foto = input.files[0]
        const reader = new FileReader()
        reader.onload = (event) => {
            fotoPreview.value = event.target?.result as string
        }
        reader.readAsDataURL(input.files[0])
    }
}

const margin = computed(() => {
    return form.harga_jual_idr - form.harga_beli_idr
})

const profit = computed(() => {
    return margin.value - form.fee
})

const profitPercentage = computed(() => {
    if (form.harga_beli_idr === 0) return 0
    return (profit.value / form.harga_beli_idr) * 100
})

const isProfitPositive = computed(() => {
    return profit.value >= 0
})

const formatIDR = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value)
}

const formatJPY = (value: number) => {
    return new Intl.NumberFormat('ja-JP', {
        style: 'currency',
        currency: 'JPY',
        maximumFractionDigits: 0,
    }).format(value)
}

const getPhotoUrl = () => {
    if (fotoPreview.value) {
        return fotoPreview.value
    }
    if (props.katalog.foto) {
        return `/storage/${props.katalog.foto}`
    }
    return null
}

const submit = () => {
    form.post(route('admin.katalog-koi.update', props.katalog.id), {
        method: 'put',
        preserveScroll: true,
    })
}

const cancel = () => {
    window.location.href = route('admin.katalog-koi.index')
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Edit Katalog Koi" />

        <div class="space-y-6 p-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Edit Katalog Koi</h1>
                    <p class="text-sm text-muted-foreground">Update informasi katalog koi.</p>
                </div>
                <Link
                    :href="route('admin.katalog-koi.index')"
                    class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted"
                >
                    Back to list
                </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- SECTION 1: Data Pembelian (Read-only) -->
                <div class="rounded-xl border border-sidebar-border/70 bg-background p-6">
                    <h2 class="mb-4 text-lg font-semibold">Data Pembelian</h2>

                    <div v-if="katalog.pembelian" class="space-y-3 rounded-lg bg-blue-50 p-4 dark:bg-blue-950">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">Tanggal Pembelian</span>
                                <p class="font-medium">
                                    {{ new Date(katalog.pembelian.date).toLocaleDateString('id-ID') }}
                                </p>
                            </div>
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">Harga JPY</span>
                                <p class="font-medium">{{ formatJPY(katalog.pembelian.price_jpy) }}</p>
                            </div>
                            <div colspan="2">
                                <span class="text-gray-600 dark:text-gray-400">Harga IDR</span>
                                <p class="font-medium">{{ formatIDR(katalog.pembelian.price_idr) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: Data Utama Koi -->
                <div class="rounded-xl border border-sidebar-border/70 bg-background p-6">
                    <h2 class="mb-4 text-lg font-semibold">Data Utama Koi</h2>

                    <div class="space-y-4">
                        <!-- Row 1: Kode & Nama -->
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="kode_koi">Kode Koi <span class="text-red-500">*</span></Label>
                                <Input
                                    id="kode_koi"
                                    v-model="form.kode_koi"
                                    placeholder="Contoh: KOI001"
                                />
                                <InputError :message="form.errors.kode_koi" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="nama_koi">Nama/Jenis Koi <span class="text-red-500">*</span></Label>
                                <Input
                                    id="nama_koi"
                                    v-model="form.nama_koi"
                                    placeholder="Contoh: Kohaku, Sanke, Showa"
                                />
                                <InputError :message="form.errors.nama_koi" />
                            </div>
                        </div>

                        <!-- Row 2: Breeder & Foto -->
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="breeder_id">Breeder</Label>
                                <Select v-model="form.breeder_id">
                                    <SelectTrigger id="breeder_id">
                                        <SelectValue placeholder="Pilih breeder..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">Tidak Ada</SelectItem>
                                        <SelectItem v-for="b in breeders" :key="b.id" :value="String(b.id)">
                                            {{ b.name_breeder }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.breeder_id" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="foto">Foto Koi</Label>
                                <Input
                                    id="foto"
                                    type="file"
                                    accept="image/*"
                                    @change="handleFotoChange"
                                />
                                <p class="text-xs text-gray-500">Biarkan kosong jika tidak ingin mengubah foto</p>
                                <InputError :message="form.errors.foto" />
                            </div>
                        </div>

                        <!-- Photo Preview -->
                        <div v-if="getPhotoUrl()" class="flex justify-center">
                            <img :src="getPhotoUrl()!" alt="Preview" class="h-48 w-48 rounded-lg object-cover" />
                        </div>

                        <!-- Row 3: Ukuran, Gender, Umur -->
                        <div class="grid gap-4 md:grid-cols-3">
                            <div class="grid gap-2">
                                <Label for="ukuran">Ukuran (cm)</Label>
                                <Input
                                    id="ukuran"
                                    v-model.number="form.ukuran"
                                    type="number"
                                    step="0.1"
                                    placeholder="Contoh: 25.5"
                                />
                                <InputError :message="form.errors.ukuran" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="jenis_kelamin">Jenis Kelamin <span class="text-red-500">*</span></Label>
                                <Select v-model="form.jenis_kelamin">
                                    <SelectTrigger id="jenis_kelamin">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="unknown">Unknown</SelectItem>
                                        <SelectItem value="male">Male</SelectItem>
                                        <SelectItem value="female">Female</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.jenis_kelamin" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="umur">Umur</Label>
                                <Select v-model="form.umur">
                                    <SelectTrigger id="umur">
                                        <SelectValue placeholder="Pilih umur..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">Tidak Diketahui</SelectItem>
                                        <SelectItem value="Tosai">Tosai</SelectItem>
                                        <SelectItem value="Nisai">Nisai</SelectItem>
                                        <SelectItem value="Sansai">Sansai</SelectItem>
                                        <SelectItem value="Yonsai">Yonsai</SelectItem>
                                        <SelectItem value="Gosai">Gosai</SelectItem>
                                        <SelectItem value="Rokusai">Rokusai</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.umur" />
                            </div>
                        </div>

                        <!-- Deskripsi -->
                        <div class="grid gap-2">
                            <Label for="deskripsi">Deskripsi</Label>
                            <textarea
                                id="deskripsi"
                                v-model="form.deskripsi"
                                placeholder="Kualitas, body, pattern, dll"
                                class="min-h-24 rounded-md border border-neutral-200 bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:border-neutral-400 focus:outline-none focus:ring-1 focus:ring-neutral-400 disabled:cursor-not-allowed disabled:opacity-50"
                            ></textarea>
                            <InputError :message="form.errors.deskripsi" />
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: Harga & Perhitungan -->
                <div class="rounded-xl border border-sidebar-border/70 bg-background p-6">
                    <h2 class="mb-4 text-lg font-semibold">Harga & Perhitungan</h2>

                    <div class="space-y-6">
                        <!-- Row 1: Harga Beli (Readonly) -->
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="harga_beli_jpy">Harga Beli JPY</Label>
                                <Input
                                    id="harga_beli_jpy"
                                    v-model.number="form.harga_beli_jpy"
                                    type="number"
                                    readonly
                                    class="bg-gray-100 dark:bg-gray-800"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="harga_beli_idr">Harga Beli IDR</Label>
                                <Input
                                    id="harga_beli_idr"
                                    v-model.number="form.harga_beli_idr"
                                    type="number"
                                    readonly
                                    class="bg-gray-100 dark:bg-gray-800"
                                />
                            </div>
                        </div>

                        <!-- Row 2: Harga Jual -->
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="harga_jual_jpy">Harga Jual JPY (Opsional)</Label>
                                <Input
                                    id="harga_jual_jpy"
                                    v-model.number="form.harga_jual_jpy"
                                    type="number"
                                    placeholder="Opsional"
                                />
                                <InputError :message="form.errors.harga_jual_jpy" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="harga_jual_idr">Harga Jual IDR <span class="text-red-500">*</span></Label>
                                <Input
                                    id="harga_jual_idr"
                                    v-model.number="form.harga_jual_idr"
                                    type="number"
                                    placeholder="0"
                                />
                                <InputError :message="form.errors.harga_jual_idr" />
                            </div>
                        </div>

                        <!-- Row 3: Fee & Qty -->
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="fee">Fee IDR</Label>
                                <Input
                                    id="fee"
                                    v-model.number="form.fee"
                                    type="number"
                                    placeholder="0"
                                />
                                <InputError :message="form.errors.fee" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="qty">Qty/Stok <span class="text-red-500">*</span></Label>
                                <Input
                                    id="qty"
                                    v-model.number="form.qty"
                                    type="number"
                                    min="1"
                                    placeholder="1"
                                />
                                <InputError :message="form.errors.qty" />
                            </div>
                        </div>

                        <!-- Calculation Box -->
                        <div class="space-y-3 rounded-lg bg-gray-100 p-4 dark:bg-gray-900">
                            <h3 class="font-semibold">Perhitungan Otomatis</h3>
                            <div class="grid gap-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Harga Beli IDR:</span>
                                    <span class="font-medium">{{ formatIDR(form.harga_beli_idr) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Harga Jual IDR:</span>
                                    <span class="font-medium">{{ formatIDR(form.harga_jual_idr) }}</span>
                                </div>
                                <div class="flex justify-between border-t pt-2">
                                    <span class="text-gray-600 dark:text-gray-400">Margin:</span>
                                    <span class="font-medium">{{ formatIDR(margin) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Fee:</span>
                                    <span class="font-medium">{{ formatIDR(form.fee) }}</span>
                                </div>
                                <div
                                    class="flex justify-between border-t pt-2"
                                    :class="isProfitPositive ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                                >
                                    <span class="font-semibold">Profit:</span>
                                    <span class="font-bold">
                                        {{ formatIDR(profit) }} ({{ profitPercentage.toFixed(2) }}%)
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 4: Status -->
                <div class="rounded-xl border border-sidebar-border/70 bg-background p-6">
                    <h2 class="mb-4 text-lg font-semibold">Status</h2>

                    <div class="grid gap-2">
                        <Label for="status">Status <span class="text-red-500">*</span></Label>
                        <Select v-model="form.status">
                            <SelectTrigger id="status">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="draft">Draft</SelectItem>
                                <SelectItem value="available">Available</SelectItem>
                                <SelectItem value="reserved">Reserved</SelectItem>
                                <SelectItem value="sold">Sold</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.status" />
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="form.processing">Update Katalog</Button>
                    <Button type="button" variant="outline" @click="cancel">Batal</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
