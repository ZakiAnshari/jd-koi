<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import Swal, { type SweetAlertOptions } from 'sweetalert2'
import type { BreadcrumbItem } from '@/types'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'

type Koi = {
    id: number
    code: string
    name: string
}

type Stock = {
    id: number
    koi_id: number
    date: string
    qty: number
    price_jpy: number
    price_idr: number
    total_jpy: number
    total_idr: number
}

const page = usePage()
const stock = page.props.stock as Stock
const kois = (page.props.kois as Koi[]) || []

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Manajemen Stok',
        href: '/inventory',
    },
    {
        title: 'Edit',
        href: `/inventory/${stock.id}/edit`,
    },
]

function fireSwal(options: SweetAlertOptions) {
    return Swal.fire({
        ...options,
        scrollbarPadding: false,
        heightAuto: false,
    })
}

const koiSearch = ref('')

const filteredKois = computed(() => {
    if (!koiSearch.value) return kois
    const q = koiSearch.value.toLowerCase()
    return kois.filter(k => k.code.toLowerCase().includes(q) || k.name.toLowerCase().includes(q))
})

const form = useForm({
    koi_id: String(stock.koi_id),
    date: stock.date,
    qty: stock.qty,
    price_jpy: stock.price_jpy,
    price_idr: stock.price_idr,
    total_jpy: stock.total_jpy,
    total_idr: stock.total_idr,
})

// Auto-calculate totals when price or qty changes
watch([() => form.qty, () => form.price_jpy, () => form.price_idr], () => {
    const qty = parseInt(String(form.qty)) || 0
    const priceJpy = parseInt(parseCurrency(String(form.price_jpy))) || 0
    const priceIdr = parseInt(parseCurrency(String(form.price_idr))) || 0
    
    form.total_jpy = String(qty * priceJpy)
    form.total_idr = String(qty * priceIdr)
})

const submit = () => {
    form.post(`/inventory/${stock.id}?_method=PUT`, {
        preserveScroll: true,
        onSuccess: () => {
            fireSwal({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data stok berhasil diperbarui.',
                timer: 3000,
                showConfirmButton: false,
            })
        },
        onError: () => {
            fireSwal({
                icon: 'error',
                title: 'Gagal!',
                text: 'Terjadi kesalahan saat memperbarui data.',
                timer: 3000,
                showConfirmButton: false,
            })
        },
    })
}

function formatCurrency(value: string | number): string {
    if (!value) return ''
    const num = String(value).replace(/\D/g, '')
    return num.replace(/\B(?=(\d{3})+(?!\d))/g, '.')
}

function parseCurrency(value: string): string {
    return value.replace(/\./g, '')
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Edit Stock" />

        <div class="space-y-6 p-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Edit Stock</h1>
                    <p class="text-sm text-muted-foreground">Update stock information.</p>
                </div>
                <Link
                    href="/inventory"
                    class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted"
                >
                    Back to list
                </Link>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="koi_id">Koi *</Label>
                            <Select v-model="form.koi_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select koi" />
                                </SelectTrigger>
                                <SelectContent class="p-0 w-[--radix-select-trigger-width] min-w-[20rem]">
                                    <div class="sticky top-0 bg-background p-2 border-b">
                                        <Input v-model="koiSearch" placeholder="Search koi..." class="h-8" />
                                    </div>
                                    <div class="max-h-48 overflow-y-auto">
                                        <div v-if="filteredKois.length === 0" class="px-2 py-2 text-sm text-muted-foreground">
                                            No kois found
                                        </div>
                                        <SelectItem v-for="koi in filteredKois" :key="koi.id" :value="String(koi.id)">
                                            {{ koi.code }} - {{ koi.name }}
                                        </SelectItem>
                                    </div>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.koi_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="date">Date *</Label>
                            <Input id="date" v-model="form.date" type="date" />
                            <InputError :message="form.errors.date" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="qty">Quantity *</Label>
                            <Input id="qty" v-model="form.qty" type="number" placeholder="0" />
                            <InputError :message="form.errors.qty" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="price_jpy">Price JPY *</Label>
                                <Input id="price_jpy" :model-value="formatCurrency(form.price_jpy)" @update:model-value="form.price_jpy = parseCurrency($event)" type="text" placeholder="0" />
                                <InputError :message="form.errors.price_jpy" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="price_idr">Price IDR *</Label>
                                <Input id="price_idr" :model-value="formatCurrency(form.price_idr)" @update:model-value="form.price_idr = parseCurrency($event)" type="text" placeholder="0" />
                                <InputError :message="form.errors.price_idr" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="total_jpy">Total JPY * (Auto)</Label>
                                <Input id="total_jpy" :model-value="formatCurrency(form.total_jpy)" type="text" placeholder="0" disabled class="bg-muted cursor-not-allowed" />
                                <InputError :message="form.errors.total_jpy" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="total_idr">Total IDR * (Auto)</Label>
                                <Input id="total_idr" :model-value="formatCurrency(form.total_idr)" type="text" placeholder="0" disabled class="bg-muted cursor-not-allowed" />
                                <InputError :message="form.errors.total_idr" />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing">Save</Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
