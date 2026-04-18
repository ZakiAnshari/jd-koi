<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import type { BreadcrumbItem } from '@/types'

type Breeder = {
    name_breeder: string
}

type Koi = {
    id: number
    nama: string
    kode: string
    photo?: string
    breeder: Breeder
}

type Katalog = {
    id: number
    tanggal: string
    qty: number
    sell_idr: number
    koi: Koi
}

type PaymentInfo = {
    bank: string
    account_name: string
    account_number: string
}

const props = defineProps<{
    katalog: Katalog
    payment_info: PaymentInfo
}>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Katalog Koi',
        href: '/customer/katalog',
    },
    {
        title: 'Checkout',
        href: '#',
    },
]

const form = useForm({
    katalog_koi_id: props.katalog.id,
    qty: 1,
    catatan: '',
})

const quantity = ref(1)

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value)
}

const storagePath = (photo?: string) => {
    if (!photo) return ''
    return `/storage/${photo}`
}

const decreaseQty = () => {
    if (quantity.value > 1) {
        quantity.value--
        form.qty = quantity.value
    }
}

const increaseQty = () => {
    if (quantity.value < props.katalog.qty) {
        quantity.value++
        form.qty = quantity.value
    }
}

const totalPrice = computed(() => quantity.value * props.katalog.sell_idr)

const submit = () => {
    form.post(route('customer.pesanan.store'))
}
</script>

<template>
    <Head title="Checkout - Pesanan Koi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="space-y-2 mb-2">
                <h1 class="text-4xl font-bold text-foreground">Checkout</h1>
                <p class="text-base text-muted-foreground">
                    Lanjutkan pesanan koi pilihan Anda
                </p>
            </div>

            <!-- Main Content -->
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Left Column - Koi Information -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6 space-y-4">
                    <h2 class="text-lg font-bold text-foreground">Informasi Koi</h2>

                    <!-- Koi Image -->
                    <div class="rounded-lg overflow-hidden bg-muted aspect-square">
                        <img
                            v-if="katalog.koi.photo"
                            :src="storagePath(katalog.koi.photo)"
                            :alt="katalog.koi.nama"
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-muted-foreground">
                            <span class="text-sm">Foto Tidak Tersedia</span>
                        </div>
                    </div>

                    <!-- Koi Details -->
                    <div class="space-y-3">
                        <div class="pb-3 border-b border-border">
                            <p class="text-xs text-muted-foreground uppercase tracking-wide mb-1">
                                Nama Koi
                            </p>
                            <p class="font-semibold text-foreground">
                                {{ katalog.koi.nama }}
                            </p>
                        </div>

                        <div class="pb-3 border-b border-border">
                            <p class="text-xs text-muted-foreground uppercase tracking-wide mb-1">
                                Breeder
                            </p>
                            <p class="font-semibold text-foreground">
                                {{ katalog.koi.breeder.name_breeder }}
                            </p>
                        </div>

                        <div class="pb-3">
                            <p class="text-xs text-muted-foreground uppercase tracking-wide mb-1">
                                Harga Satuan
                            </p>
                            <p class="text-xl font-bold text-primary">
                                {{ formatCurrency(katalog.sell_idr) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Checkout Form -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6 space-y-6">
                    <!-- Payment Information -->
                    <div class="space-y-3">
                        <h3 class="font-bold text-foreground">Informasi Pembayaran</h3>

                        <div class="space-y-2 text-sm bg-muted/30 p-4 rounded-lg">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Bank:</span>
                                <span class="font-semibold text-foreground">{{ payment_info.bank }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Atas Nama:</span>
                                <span class="font-semibold text-foreground">{{ payment_info.account_name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Nomor Rekening:</span>
                                <span class="font-mono font-semibold text-foreground">{{ payment_info.account_number }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="h-px bg-border" />

                    <!-- Quantity Section -->
                    <div class="space-y-3">
                        <Label for="quantity" class="font-bold text-foreground">Jumlah Pesanan</Label>
                        
                        <div class="flex items-center justify-center gap-3 bg-muted/20 p-3 rounded-lg">
                            <Button
                                @click="decreaseQty"
                                variant="outline"
                                size="sm"
                                class="w-10 h-10 p-0"
                            >
                                −
                            </Button>
                            
                            <Input
                                v-model.number="quantity"
                                type="number"
                                min="1"
                                :max="katalog.qty"
                                class="w-16 text-center"
                                readonly
                            />
                            
                            <Button
                                @click="increaseQty"
                                variant="outline"
                                size="sm"
                                class="w-10 h-10 p-0"
                            >
                                +
                            </Button>
                        </div>

                        <p class="text-xs text-muted-foreground">
                            Stok tersedia: <span class="font-semibold">{{ katalog.qty }} ekor</span>
                        </p>
                    </div>

                    <!-- Divider -->
                    <div class="h-px bg-border" />

                    <!-- Order Summary -->
                    <div class="space-y-3">
                        <h3 class="font-bold text-foreground">Ringkasan Pesanan</h3>

                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Harga Satuan:</span>
                                <span class="font-semibold text-foreground">{{ formatCurrency(katalog.sell_idr) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Jumlah:</span>
                                <span class="font-semibold text-foreground">{{ quantity }} ekor</span>
                            </div>
                        </div>

                        <!-- Grand Total -->
                        <div class="pt-3 border-t border-border">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-foreground">Total Pesanan:</span>
                                <span class="text-2xl font-bold text-primary">
                                    {{ formatCurrency(totalPrice) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="space-y-2">
                        <Label for="catatan" class="text-sm">Catatan (Opsional)</Label>
                        <textarea
                            v-model="form.catatan"
                            placeholder="Tambahkan catatan khusus untuk pesanan ini..."
                            class="w-full px-3 py-2 rounded-lg border border-sidebar-border/70 dark:border-sidebar-border bg-background text-foreground text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            rows="3"
                        />
                    </div>

                    <!-- Order Button -->
                    <Button
                        @click="submit"
                        :disabled="form.processing"
                        class="w-full bg-primary hover:bg-primary/90 text-primary-foreground font-semibold py-3 text-base"
                    >
                        {{ form.processing ? 'Memproses...' : 'Order Now' }}
                    </Button>

                    <!-- Info Text -->
                    <p class="text-xs text-muted-foreground text-center">
                        Harap selesaikan pembayaran dan hubungi kami untuk konfirmasi pesanan.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
