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

type Transaction = {
    id: number
    koi_id: number
    date: string
    status: string
    qty: number
    sell_jpy: number
    sell_idr: number
    margin: number
    fee: number
    stock_jpy: number
    stock_idr: number
    sell_to: string
    profit: number
}

const page = usePage()
const transaction = page.props.transaction as Transaction
const kois = (page.props.kois as Koi[]) || []

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Transaksi Penjualan',
        href: '/transactions',
    },
    {
        title: 'Edit',
        href: `/transactions/${transaction.id}/edit`,
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
    date: transaction.date,
    status: transaction.status,
    koi_id: String(transaction.koi_id),
    qty: transaction.qty,
    sell_jpy: transaction.sell_jpy,
    sell_idr: transaction.sell_idr,
    margin: transaction.margin,
    fee: transaction.fee,
    stock_jpy: transaction.stock_jpy,
    stock_idr: transaction.stock_idr,
    sell_to: transaction.sell_to,
    profit: transaction.profit,
})

const submit = () => {
    form.post(`/transactions/${transaction.id}?_method=PUT`, {
        preserveScroll: true,
        onSuccess: () => {
            fireSwal({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Transaksi penjualan berhasil diperbarui.',
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

function formatJPY(value: number | string | undefined): string {
    if (!value) return '¥ 0'
    const num = Number(value)
    return '¥ ' + num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
}

function formatIDR(value: number | string | undefined): string {
    if (!value) return 'Rp 0'
    const num = Number(value)
    return 'Rp ' + num.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Edit Transaction" />

        <div class="space-y-6 p-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Edit Transaksi Penjualan</h1>
                    <p class="text-sm text-muted-foreground">Update data transaksi penjualan</p>
                </div>
                <Link
                    href="/transactions"
                    class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted"
                >
                    Back
                </Link>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="date">Date *</Label>
                            <Input id="date" v-model="form.date" type="date" />
                            <InputError :message="form.errors.date" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="status">Status *</Label>
                            <Select v-model="form.status">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="completed">Completed</SelectItem>
                                    <SelectItem value="pending">Pending</SelectItem>
                                    <SelectItem value="cancelled">Cancelled</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.status" />
                        </div>

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
                            <Label for="sell_to">Pembeli *</Label>
                            <Input id="sell_to" v-model="form.sell_to" type="text" placeholder="Customer name" />
                            <InputError :message="form.errors.sell_to" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="qty">Qty *</Label>
                            <Input id="qty" v-model="form.qty" type="number" placeholder="0" />
                            <InputError :message="form.errors.qty" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="sell_jpy">Sell JPY *</Label>
                                <Input id="sell_jpy" v-model="form.sell_jpy" type="number" step="0.01" placeholder="0" />
                                <InputError :message="form.errors.sell_jpy" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="sell_idr">Sell IDR *</Label>
                                <Input id="sell_idr" v-model="form.sell_idr" type="number" step="0.01" placeholder="0" />
                                <InputError :message="form.errors.sell_idr" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="stock_jpy">Stock JPY *</Label>
                                <Input id="stock_jpy" v-model="form.stock_jpy" type="number" step="0.01" placeholder="0" />
                                <InputError :message="form.errors.stock_jpy" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="stock_idr">Stock IDR *</Label>
                                <Input id="stock_idr" v-model="form.stock_idr" type="number" step="0.01" placeholder="0" />
                                <InputError :message="form.errors.stock_idr" />
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div class="grid gap-2">
                                <Label for="margin">Margin *</Label>
                                <Input id="margin" v-model="form.margin" type="number" step="0.01" placeholder="0" />
                                <InputError :message="form.errors.margin" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="fee">Fee *</Label>
                                <Input id="fee" v-model="form.fee" type="number" step="0.01" placeholder="0" />
                                <InputError :message="form.errors.fee" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="profit">Profit *</Label>
                                <Input id="profit" v-model="form.profit" type="number" step="0.01" placeholder="0" />
                                <InputError :message="form.errors.profit" />
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
