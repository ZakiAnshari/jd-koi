<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { useSwal } from '@/composables/useSwal'
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

type Customer = {
    id: number
    name: string
    phone: string | null
    address: string | null
    created_at: string
}

const page = usePage()
const customers = computed(() => (page.props.customers as { data: Customer[] } | null)?.data ?? [])

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Master Data',
        href: '#',
    },
    {
        title: 'Customer',
        href: '/master-data/customer',
    },
]

const search = ref('')
const sortKey = ref<keyof Customer>('created_at')
const sortOrder = ref<'asc' | 'desc'>('desc')
const currentPage = ref(1)
const perPage = ref(10)
const showModal = ref(false)

const form = useForm({
    name: '',
    phone: '',
    address: '',
})

const filteredCustomers = computed(() => {
    let result = customers.value

    if (search.value) {
        const q = search.value.toLowerCase()
        result = result.filter((customer) => {
            return [customer.name, customer.phone, customer.address]
                .filter(Boolean)
                .some((value) => value?.toLowerCase().includes(q))
        })
    }

    return [...result].sort((a, b) => {
        const aVal = a[sortKey.value] ?? ''
        const bVal = b[sortKey.value] ?? ''

        if (typeof aVal === 'string' && typeof bVal === 'string') {
            return sortOrder.value === 'asc'
                ? aVal.localeCompare(bVal)
                : bVal.localeCompare(aVal)
        }

        return 0
    })
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredCustomers.value.length / perPage.value)))

const paginatedCustomers = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return filteredCustomers.value.slice(start, start + perPage.value)
})

const showingFrom = computed(() =>
    filteredCustomers.value.length === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1,
)

const showingTo = computed(() =>
    Math.min(currentPage.value * perPage.value, filteredCustomers.value.length),
)

function onSearch(value: string | number) {
    search.value = String(value)
    currentPage.value = 1
}

function toggleSort(key: keyof Customer) {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortKey.value = key
        sortOrder.value = 'asc'
    }
}

function sortIcon(key: keyof Customer) {
    if (sortKey.value !== key) return '↕'
    return sortOrder.value === 'asc' ? '↑' : '↓'
}

const submit = () => {
    const swal = useSwal()
    form.post('/master-data/customer', {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Customer berhasil ditambahkan.',
                timer: 3000,
                showConfirmButton: false,
            })
            form.reset()
            showModal.value = false
        },
        onError: () => {
            swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: 'Terjadi kesalahan saat menyimpan data.',
                timer: 3000,
                showConfirmButton: false,
            })
        },
    })
}

function confirmDelete(customerId: number, customerName: string) {
    const swal = useSwal()
    swal.fire({
        title: 'Hapus customer?',
        text: `Customer "${customerName}" akan dihapus permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/master-data/customer/${customerId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Customer berhasil dihapus.',
                        timer: 3000,
                        showConfirmButton: false,
                    })
                },
                onError: () => {
                    swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Terjadi kesalahan saat menghapus customer.',
                        timer: 3000,
                        showConfirmButton: false,
                    })
                },
            })
        }
    })
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">

        <Head title="Customer" />

        <div class="flex flex-col gap-4 p-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Customer</h1>
                    <p class="text-sm text-muted-foreground">Manage Customer Data.</p>
                </div>
                <Dialog v-model:open="showModal">
                    <DialogTrigger as-child>
                        <Button>New Customer</Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-[425px]">
                        <DialogHeader>
                            <DialogTitle>Create Customer</DialogTitle>
                            <DialogDescription>Fill in new customer data.</DialogDescription>
                        </DialogHeader>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div class="grid gap-2">
                                <Label for="name">Nama</Label>
                                <Input id="name" v-model="form.name" placeholder="Nama customer" />
                                <InputError :message="form.errors.name" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="phone">Telepon</Label>
                                <Input id="phone" type="number" v-model="form.phone" placeholder="Nomor telepon" />
                                <InputError :message="form.errors.phone" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="address">Alamat</Label>
                                <textarea id="address" v-model="form.address" placeholder="Alamat customer"
                                    class="min-h-[120px] w-full rounded-md border px-3 py-2" />
                                <InputError :message="form.errors.address" />
                            </div>
                            <DialogFooter>
                                <Button type="submit" :disabled="form.processing">Simpan</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                <div
                    class="flex flex-col gap-2 border-b border-border p-4 sm:flex-row sm:items-center sm:justify-between">
                    <Input :model-value="search" @update:model-value="onSearch" placeholder="Cari customer..."
                        class="max-w-sm" />
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted-foreground">Tampilkan</span>
                        <select :value="perPage"
                            @change="(e) => { perPage = Number((e.target as HTMLSelectElement).value); currentPage = 1 }"
                            class="rounded-md border border-input bg-background px-3 py-1 text-sm">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        <span class="text-sm text-muted-foreground">entri</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('name')">
                                    Nama <span class="ml-1">{{ sortIcon('name') }}</span>
                                </th>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('phone')">
                                    Telepon <span class="ml-1">{{ sortIcon('phone') }}</span>
                                </th>

                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('created_at')">
                                    Dibuat <span class="ml-1">{{ sortIcon('created_at') }}</span>
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-background">
                            <tr v-if="paginatedCustomers.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                    {{ search ? 'Tidak ada hasil untuk pencarian tersebut.' : 'Belum ada data customer.'
                                    }}
                                </td>
                            </tr>
                            <tr v-for="customer in paginatedCustomers" :key="customer.id"
                                class="transition-colors hover:bg-muted/50">
                                <td class="px-4 py-3 text-sm text-foreground">{{ customer.name }}</td>
                                <td class="px-4 py-3 text-sm text-foreground">{{ customer.phone || '-' }}</td>

                                <td class="px-4 py-3 text-sm text-foreground">{{ new
                                    Date(customer.created_at).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }) }}</td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <Link :href="`/master-data/customer/${customer.id}`"
                                        class="mr-2 rounded-md border border-border px-3 py-1 text-xs font-medium text-foreground hover:bg-muted">
                                        Lihat</Link>
                                    <Link :href="`/master-data/customer/${customer.id}/edit`"
                                        class="mr-2 rounded-md border border-border px-3 py-1 text-xs font-medium text-foreground hover:bg-muted">
                                        Edit</Link>
                                    <button type="button"
                                        class="rounded-md border border-red-500 bg-red-500/10 px-3 py-1 text-xs font-medium text-red-700 hover:bg-red-500/20"
                                        @click="confirmDelete(customer.id, customer.name)">Hapus</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    class="flex flex-col items-center justify-between gap-2 border-t border-border px-4 py-3 sm:flex-row">
                    <p class="text-sm text-muted-foreground">
                        Menampilkan {{ showingFrom }} sampai {{ showingTo }} dari {{ filteredCustomers.length }} entri
                        <span v-if="search">(difilter dari {{ customers.length }} total)</span>
                    </p>
                    <div class="flex items-center gap-1">
                        <Button variant="outline" size="sm" :disabled="currentPage <= 1"
                            @click="currentPage--">Sebelumnya</Button>
                        <template v-for="pageIndex in totalPages" :key="pageIndex">
                            <Button size="sm" :variant="pageIndex === currentPage ? 'default' : 'outline'"
                                @click="currentPage = pageIndex">{{ pageIndex }}</Button>
                        </template>
                        <Button variant="outline" size="sm" :disabled="currentPage >= totalPages"
                            @click="currentPage++">Berikutnya</Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
