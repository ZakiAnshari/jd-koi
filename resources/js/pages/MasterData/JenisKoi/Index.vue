<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
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
import { Pencil, Trash2 } from 'lucide-vue-next'

type JenisKoi = {
    id: number
    nama_jenis: string
    deskripsi: string | null
    created_at: string
}

const page = usePage()
const jenisKois = computed(() => (page.props.jenisKois as JenisKoi[] | null) ?? [])

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data', href: '#' },
    { title: 'Jenis Koi', href: '/master-data/jenis-koi' },
]

// ─── DataTable State ─────────────────────────────────────
const search = ref('')
const sortKey = ref<keyof JenisKoi>('created_at')
const sortOrder = ref<'asc' | 'desc'>('desc')
const currentPage = ref(1)
const perPage = ref(10)

const filteredData = computed(() => {
    let result = jenisKois.value

    if (search.value) {
        const q = search.value.toLowerCase()
        result = result.filter(
            (j) =>
                j.nama_jenis.toLowerCase().includes(q) ||
                (j.deskripsi ?? '').toLowerCase().includes(q),
        )
    }

    result = [...result].sort((a, b) => {
        const aVal = a[sortKey.value] ?? ''
        const bVal = b[sortKey.value] ?? ''
        if (typeof aVal === 'string' && typeof bVal === 'string') {
            return sortOrder.value === 'asc'
                ? aVal.localeCompare(bVal)
                : bVal.localeCompare(aVal)
        }
        return 0
    })

    return result
})

const totalPages = computed(() => Math.ceil(filteredData.value.length / perPage.value) || 1)

const paginatedData = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return filteredData.value.slice(start, start + perPage.value)
})

const showingFrom = computed(() =>
    filteredData.value.length === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1,
)
const showingTo = computed(() =>
    Math.min(currentPage.value * perPage.value, filteredData.value.length),
)

function onSearch(value: string | number) {
    search.value = String(value)
    currentPage.value = 1
}

function toggleSort(key: keyof JenisKoi) {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortKey.value = key
        sortOrder.value = 'asc'
    }
}

function sortIcon(key: keyof JenisKoi) {
    if (sortKey.value !== key) return '↕'
    return sortOrder.value === 'asc' ? '↑' : '↓'
}

// ─── Modal Tambah ────────────────────────────────────────
const showCreateModal = ref(false)

const createForm = useForm({
    nama_jenis: '',
    deskripsi: '',
})

watch(showCreateModal, (isOpen) => {
    if (!isOpen) {
        createForm.reset()
        createForm.clearErrors()
    }
})

function submitCreate() {
    createForm.post('/master-data/jenis-koi', {
        preserveScroll: true,
        onSuccess: () => {
            useSwal().fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data jenis koi berhasil ditambahkan.',
                timer: 3000,
                showConfirmButton: false,
            })
            createForm.reset()
            showCreateModal.value = false
        },
    })
}

// ─── Modal Edit ──────────────────────────────────────────
const showEditModal = ref(false)
const editingId = ref<number | null>(null)

const editForm = useForm({
    nama_jenis: '',
    deskripsi: '',
})

watch(showEditModal, (isOpen) => {
    if (!isOpen) {
        editForm.clearErrors()
        editingId.value = null
    }
})

function openEdit(item: JenisKoi) {
    editingId.value = item.id
    editForm.nama_jenis = item.nama_jenis
    editForm.deskripsi = item.deskripsi ?? ''
    showEditModal.value = true
}

function submitEdit() {
    if (!editingId.value) return
    editForm.put(`/master-data/jenis-koi/${editingId.value}`, {
        preserveScroll: true,
        onSuccess: () => {
            useSwal().fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data jenis koi berhasil diperbarui.',
                timer: 3000,
                showConfirmButton: false,
            })
            showEditModal.value = false
        },
    })
}

// ─── Hapus ───────────────────────────────────────────────
function confirmDelete(id: number, name: string) {
    useSwal().fire({
        title: 'Hapus jenis koi?',
        text: `Jenis Koi "${name}" akan dihapus permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/master-data/jenis-koi/${id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    useSwal().fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Jenis koi berhasil dihapus.',
                        timer: 3000,
                        showConfirmButton: false,
                    })
                },
                onError: () => {
                    useSwal().fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Terjadi kesalahan saat menghapus jenis koi.',
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
        <Head title="Jenis Koi" />

        <div class="flex flex-col gap-4 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Jenis Koi</h1>
                    <p class="text-sm text-muted-foreground">Kelola jenis-jenis koi dalam sistem.</p>
                </div>

                <!-- Modal Tambah -->
                <Dialog v-model:open="showCreateModal">
                    <DialogTrigger as-child>
                        <Button>Tambah Jenis Koi</Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-[425px]">
                        <DialogHeader>
                            <DialogTitle>Tambah Jenis Koi</DialogTitle>
                            <DialogDescription>Tambahkan jenis koi baru.</DialogDescription>
                        </DialogHeader>
                        <form @submit.prevent="submitCreate" class="space-y-4">
                            <div class="grid gap-2">
                                <Label for="create_nama_jenis">Nama Jenis</Label>
                                <Input id="create_nama_jenis" v-model="createForm.nama_jenis" placeholder="Nama jenis koi" />
                                <InputError :message="createForm.errors.nama_jenis" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="create_deskripsi">Deskripsi</Label>
                                <textarea
                                    id="create_deskripsi"
                                    v-model="createForm.deskripsi"
                                    placeholder="Deskripsi (opsional)"
                                    rows="3"
                                    class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                />
                                <InputError :message="createForm.errors.deskripsi" />
                            </div>
                            <DialogFooter>
                                <Button type="button" variant="outline" @click="showCreateModal = false">Batal</Button>
                                <Button type="submit" :disabled="createForm.processing">Simpan</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- DataTable -->
            <div class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                <!-- Toolbar -->
                <div class="flex flex-col gap-2 border-b border-border p-4 sm:flex-row sm:items-center sm:justify-between">
                    <Input :model-value="search" @update:model-value="onSearch" placeholder="Cari nama jenis..." class="max-w-sm" />
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted-foreground">Show</span>
                        <select
                            :value="perPage"
                            @change="(e) => { perPage = Number((e.target as HTMLSelectElement).value); currentPage = 1 }"
                            class="rounded-md border border-input bg-background px-3 py-1 text-sm"
                        >
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
                                <th
                                    class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('nama_jenis')"
                                >
                                    Nama Jenis <span class="ml-1">{{ sortIcon('nama_jenis') }}</span>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Deskripsi
                                </th>
                                <th
                                    class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('created_at')"
                                >
                                    Dibuat <span class="ml-1">{{ sortIcon('created_at') }}</span>
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-background">
                            <tr v-if="paginatedData.length === 0">
                                <td colspan="4" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                    {{ search ? 'Tidak ada hasil untuk pencarian tersebut.' : 'Belum ada data jenis koi.' }}
                                </td>
                            </tr>
                            <tr v-for="item in paginatedData" :key="item.id" class="transition-colors hover:bg-muted/50">
                                <td class="px-4 py-3 text-sm font-medium text-foreground">{{ item.nama_jenis }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ item.deskripsi ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-foreground">
                                    {{ new Intl.DateTimeFormat('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }).format(new Date(item.created_at)) }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background p-2 text-foreground shadow-sm hover:bg-muted"
                                            title="Edit"
                                            @click="openEdit(item)"
                                        >
                                            <Pencil :size="16" />
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center rounded-md border border-red-300 bg-red-50 p-2 text-red-700 shadow-sm hover:bg-red-100 dark:border-red-700 dark:bg-red-900 dark:text-red-200 dark:hover:bg-red-800"
                                            title="Hapus"
                                            @click="confirmDelete(item.id, item.nama_jenis)"
                                        >
                                            <Trash2 :size="16" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Pagination -->
                <div class="flex flex-col items-center justify-between gap-2 border-t border-border px-4 py-3 sm:flex-row">
                    <p class="text-sm text-muted-foreground">
                        Showing {{ showingFrom }} to {{ showingTo }} of {{ filteredData.length }} entries
                        <span v-if="search">(filtered from {{ jenisKois.length }} total)</span>
                    </p>
                    <div class="flex items-center gap-1">
                        <Button variant="outline" size="sm" :disabled="currentPage <= 1" @click="currentPage--">Previous</Button>
                        <template v-for="p in totalPages" :key="p">
                            <Button size="sm" :variant="p === currentPage ? 'default' : 'outline'" @click="currentPage = p">
                                {{ p }}
                            </Button>
                        </template>
                        <Button variant="outline" size="sm" :disabled="currentPage >= totalPages" @click="currentPage++">Next</Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Edit -->
        <Dialog v-model:open="showEditModal">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Edit Jenis Koi</DialogTitle>
                    <DialogDescription>Perbarui data jenis koi.</DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="edit_nama_jenis">Nama Jenis</Label>
                        <Input id="edit_nama_jenis" v-model="editForm.nama_jenis" placeholder="Nama jenis koi" />
                        <InputError :message="editForm.errors.nama_jenis" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit_deskripsi">Deskripsi</Label>
                        <textarea
                            id="edit_deskripsi"
                            v-model="editForm.deskripsi"
                            placeholder="Deskripsi (opsional)"
                            rows="3"
                            class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <InputError :message="editForm.errors.deskripsi" />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="showEditModal = false">Batal</Button>
                        <Button type="submit" :disabled="editForm.processing">Simpan</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
