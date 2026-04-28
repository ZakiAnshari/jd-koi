<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Download, Eye, Pencil, Trash2 } from 'lucide-vue-next'
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

type User = {
    id: number
    name: string
    email: string
    no_telepon?: string | null
    alamat?: string | null
    created_at: string
    updated_at: string
}

const page = usePage()
const users = computed(() => (page.props.users as User[] | null) ?? [])
const activeRole = computed(() => (page.props.activeRole as string) ?? '')

const pageTitle = computed(() => {
    if (activeRole.value === 'admin') return 'Daftar Admin'
    if (activeRole.value === 'customer') return 'Daftar Customer'
    return 'Users'
})

const breadcrumbs = computed<BreadcrumbItem[]>(() => {
    if (activeRole.value === 'admin') return [
        { title: 'Hak Akses', href: '#' },
        { title: 'Admin', href: '/admin/hak-akses/admin' },
    ]
    if (activeRole.value === 'customer') return [
        { title: 'Hak Akses', href: '#' },
        { title: 'Customer', href: '/admin/hak-akses/customer' },
    ]
    return [{ title: 'Users', href: usersIndex() }]
})

// ─── DataTable State ────────────────────────────────────
const search = ref('')
const sortKey = ref<keyof User>('name')
const sortOrder = ref<'asc' | 'desc'>('asc')
const currentPage = ref(1)
const perPage = ref(10)

// ─── Filtered + Sorted + Paginated ─────────────────────
const filteredUsers = computed(() => {
    let result = users.value

    if (search.value) {
        const q = search.value.toLowerCase()
        result = result.filter(
            (u) =>
                u.name.toLowerCase().includes(q) ||
                u.email.toLowerCase().includes(q),
        )
    }

    result = [...result].sort((a, b) => {
        const aVal = a[sortKey.value]
        const bVal = b[sortKey.value]
        if (typeof aVal === 'string' && typeof bVal === 'string') {
            return sortOrder.value === 'asc'
                ? aVal.localeCompare(bVal)
                : bVal.localeCompare(aVal)
        }
        if (typeof aVal === 'number' && typeof bVal === 'number') {
            return sortOrder.value === 'asc' ? aVal - bVal : bVal - aVal
        }
        return 0
    })

    return result
})

const totalPages = computed(() => Math.ceil(filteredUsers.value.length / perPage.value) || 1)

const paginatedUsers = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return filteredUsers.value.slice(start, start + perPage.value)
})

const showingFrom = computed(() =>
    filteredUsers.value.length === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1,
)
const showingTo = computed(() =>
    Math.min(currentPage.value * perPage.value, filteredUsers.value.length),
)

function onSearch(value: string | number) {
    search.value = String(value)
    currentPage.value = 1
}

function toggleSort(key: keyof User) {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortKey.value = key
        sortOrder.value = 'asc'
    }
}

function sortIcon(key: keyof User) {
    if (sortKey.value !== key) return '↕'
    return sortOrder.value === 'asc' ? '↑' : '↓'
}

// ─── Modal + Form (Admin) ───────────────────────────────
const showModal = ref(false)

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post('/users', {
        preserveScroll: true,
        onSuccess: () => {
            useSwal().fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data user berhasil ditambahkan.',
                timer: 3000,
                showConfirmButton: false,
            })
            form.reset()
            showModal.value = false
        },
        onError: () => {
            useSwal().fire({
                icon: 'error',
                title: 'Gagal!',
                text: 'Terjadi kesalahan saat menyimpan data.',
                timer: 3000,
                showConfirmButton: false,
            })
        },
    })
}

// ─── Modal + Form (Customer) ─────────────────────────────
const showCustomerModal = ref(false)

const customerForm = useForm({
    name: '',
    email: '',
    no_telepon: '',
    alamat: '',
    password: '',
    password_confirmation: '',
})

const submitCustomer = () => {
    customerForm.post('/admin/hak-akses/customer', {
        preserveScroll: true,
        onSuccess: () => {
            useSwal().fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data customer berhasil ditambahkan.',
                timer: 3000,
                showConfirmButton: false,
            })
            customerForm.reset()
            showCustomerModal.value = false
        },
        onError: () => {
            useSwal().fire({
                icon: 'error',
                title: 'Gagal!',
                text: 'Terjadi kesalahan saat menyimpan data.',
                timer: 3000,
                showConfirmButton: false,
            })
        },
    })
}

function confirmDelete(userId: number, userName: string) {
    useSwal().fire({
        title: 'Hapus user?',
        text: `User "${userName}" akan dihapus permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/users/${userId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    useSwal().fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'User berhasil dihapus.',
                        timer: 3000,
                        showConfirmButton: false,
                    })
                },
                onError: () => {
                    useSwal().fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Terjadi kesalahan saat menghapus user.',
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
    <AppLayout :breadcrumbs="breadcrumbs as BreadcrumbItem[]">
        <Head :title="pageTitle" />

        <div class="flex flex-col gap-4 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">{{ pageTitle }}</h1>
                    <p class="text-sm text-muted-foreground">Kelola akun {{ activeRole || 'user' }} dalam sistem.</p>
                </div>

                <!-- Tombol Export + Tambah Customer -->
                <div v-if="activeRole === 'customer'" class="flex items-center gap-2">
                    <Button
                        as="a"
                        href="/admin/hak-akses/customer/export/excel"
                        variant="outline"
                        class="inline-flex items-center gap-2"
                    >
                        <Download class="h-4 w-4" />
                        Export Excel
                    </Button>

                    <Dialog v-model:open="showCustomerModal">
                        <DialogTrigger as-child>
                            <Button class="inline-flex items-center gap-2">+ Tambah Customer</Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-[480px]">
                            <DialogHeader>
                                <DialogTitle>Tambah Customer</DialogTitle>
                                <DialogDescription>Buat akun customer baru.</DialogDescription>
                            </DialogHeader>
                            <form @submit.prevent="submitCustomer" class="space-y-3">
                                <div class="grid gap-1.5">
                                    <Label>Nama Lengkap <span class="text-red-500">*</span></Label>
                                    <Input v-model="customerForm.name" placeholder="Nama lengkap" />
                                    <InputError :message="customerForm.errors.name" />
                                </div>
                                <div class="grid gap-1.5">
                                    <Label>Email <span class="text-red-500">*</span></Label>
                                    <Input v-model="customerForm.email" type="email" placeholder="email@example.com" />
                                    <InputError :message="customerForm.errors.email" />
                                </div>
                                <div class="grid gap-1.5">
                                    <Label>No. Telepon</Label>
                                    <Input v-model="customerForm.no_telepon" placeholder="08xxxxxxxxxx" />
                                    <InputError :message="customerForm.errors.no_telepon" />
                                </div>
                                <div class="grid gap-1.5">
                                    <Label>Alamat</Label>
                                    <textarea
                                        v-model="customerForm.alamat"
                                        rows="2"
                                        placeholder="Alamat lengkap..."
                                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                                    />
                                    <InputError :message="customerForm.errors.alamat" />
                                </div>
                                <div class="grid gap-1.5">
                                    <Label>Password <span class="text-red-500">*</span></Label>
                                    <Input v-model="customerForm.password" type="password" placeholder="Min. 8 karakter" />
                                    <InputError :message="customerForm.errors.password" />
                                </div>
                                <div class="grid gap-1.5">
                                    <Label>Konfirmasi Password <span class="text-red-500">*</span></Label>
                                    <Input v-model="customerForm.password_confirmation" type="password" placeholder="Ulangi password" />
                                    <InputError :message="customerForm.errors.password_confirmation" />
                                </div>
                                <DialogFooter class="pt-2">
                                    <Button type="button" variant="outline" @click="showCustomerModal = false">Batal</Button>
                                    <Button type="submit" :disabled="customerForm.processing">
                                        {{ customerForm.processing ? 'Menyimpan...' : 'Simpan' }}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                <!-- Tombol New User (Admin) -->
                <Dialog v-if="activeRole !== 'customer'" v-model:open="showModal">
                    <DialogTrigger as-child>
                        <Button>New user</Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-[425px]">
                        <DialogHeader>
                            <DialogTitle>Create user</DialogTitle>
                            <DialogDescription>Create a new user account.</DialogDescription>
                        </DialogHeader>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div class="grid gap-2">
                                <Label for="name">Name</Label>
                                <Input id="name" v-model="form.name" placeholder="Full name" />
                                <InputError :message="form.errors.name" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="email">Email</Label>
                                <Input id="email" v-model="form.email" type="email" placeholder="Email address" />
                                <InputError :message="form.errors.email" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="password">Password</Label>
                                <Input id="password" v-model="form.password" type="password" placeholder="Password" />
                                <InputError :message="form.errors.password" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="password_confirmation">Confirm password</Label>
                                <Input id="password_confirmation" v-model="form.password_confirmation" type="password"
                                    placeholder="Confirm password" />
                                <InputError :message="form.errors.password_confirmation" />
                            </div>
                            <DialogFooter>
                                <Button type="submit" :disabled="form.processing">Create</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- DataTable -->
            <div class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                <!-- Toolbar: Search + Per Page -->
                <div
                    class="flex flex-col gap-2 border-b border-border p-4 sm:flex-row sm:items-center sm:justify-between">
                    <Input :model-value="search" @update:model-value="onSearch" placeholder="Search name or email..."
                        class="max-w-sm" />
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted-foreground">Show</span>
                        <select :value="perPage"
                            @change="(e) => { perPage = Number((e.target as HTMLSelectElement).value); currentPage = 1 }"
                            class="rounded-md border border-input bg-background px-3 py-1 text-sm">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        <span class="text-sm text-muted-foreground">entries</span>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('name')">
                                    Nama <span class="ml-1">{{ sortIcon('name') }}</span>
                                </th>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('email')">
                                    Email <span class="ml-1">{{ sortIcon('email') }}</span>
                                </th>
                                <th v-if="activeRole === 'customer'"
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    No. Telepon
                                </th>
                                <th v-if="activeRole === 'customer'"
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Alamat
                                </th>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('created_at')">
                                    Tgl Daftar <span class="ml-1">{{ sortIcon('created_at') }}</span>
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-background">
                            <tr v-if="paginatedUsers.length === 0">
                                <td :colspan="activeRole === 'customer' ? 6 : 4" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                    {{ search ? 'Tidak ada hasil untuk pencarian tersebut.' : 'Belum ada data user.' }}
                                </td>
                            </tr>
                            <tr v-for="user in paginatedUsers" :key="user.id"
                                class="transition-colors hover:bg-muted/50">
                                <td class="px-4 py-3 text-sm text-foreground">{{ user.name }}</td>
                                <td class="px-4 py-3 text-sm text-foreground">{{ user.email }}</td>
                                <td v-if="activeRole === 'customer'" class="px-4 py-3 text-sm text-foreground">
                                    {{ user.no_telepon || '-' }}
                                </td>
                                <td v-if="activeRole === 'customer'" class="max-w-[200px] px-4 py-3 text-sm text-foreground">
                                    <span class="block truncate" :title="user.alamat ?? undefined">{{ user.alamat || '-' }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-foreground">{{
                                    new Date(user.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
                                }}</td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Tombol Detail disembunyikan di halaman admin, tetap tampil di halaman customer -->
                                        <Link v-if="activeRole !== 'admin'"
                                            :href="activeRole === 'customer' ? `/admin/hak-akses/customer/${user.id}` : usersShow(user.id)"
                                            class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background p-2 text-foreground shadow-sm hover:bg-muted"
                                            title="Detail">
                                            <Eye :size="16" />
                                        </Link>
                                        <!-- Tombol Edit disembunyikan di halaman customer, tetap tampil di halaman admin -->
                                        <Link v-if="activeRole !== 'customer'"
                                            :href="`/admin/hak-akses/admin/${user.id}/edit`"
                                            class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background p-2 text-foreground shadow-sm hover:bg-muted"
                                            title="Edit">
                                            <Pencil :size="16" />
                                        </Link>
                                        <button type="button"
                                            class="inline-flex items-center justify-center rounded-md border border-red-300 bg-red-50 p-2 text-red-700 shadow-sm hover:bg-red-100 dark:border-red-700 dark:bg-red-900 dark:text-red-200 dark:hover:bg-red-800"
                                            title="Hapus"
                                            @click="confirmDelete(user.id, user.name)">
                                            <Trash2 :size="16" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer: Info + Pagination -->
                <div
                    class="flex flex-col items-center justify-between gap-2 border-t border-border px-4 py-3 sm:flex-row">
                    <p class="text-sm text-muted-foreground">
                        Showing {{ showingFrom }} to {{ showingTo }} of {{ filteredUsers.length }} entries
                        <span v-if="search">(filtered from {{ users.length }} total)</span>
                    </p>

                    <div class="flex items-center gap-1">
                        <Button variant="outline" size="sm" :disabled="currentPage <= 1" @click="currentPage--">
                            Previous
                        </Button>

                        <template v-for="p in totalPages" :key="p">
                            <Button size="sm" :variant="p === currentPage ? 'default' : 'outline'"
                                @click="currentPage = p">
                                {{ p }}
                            </Button>
                        </template>

                        <Button variant="outline" size="sm" :disabled="currentPage >= totalPages"
                            @click="currentPage++">
                            Next
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>