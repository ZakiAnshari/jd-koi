<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
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

type Breeder = {
    id: number
    name_breeder: string
    created_at: string
}

const page = usePage()
const breeders = computed(() => (page.props.breeders as Breeder[] | null) ?? [])

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Master Data',
        href: '#',
    },
    {
        title: 'Breeder',
        href: '/master-data/breeder',
    },
]

// ─── DataTable State ────────────────────────────────────
const search = ref('')
const sortKey = ref<keyof Breeder>('created_at')
const sortOrder = ref<'asc' | 'desc'>('desc')
const currentPage = ref(1)
const perPage = ref(10)

const filteredBreeders = computed(() => {
    let result = breeders.value

    if (search.value) {
        const q = search.value.toLowerCase()
        result = result.filter(
            (b) => b.name_breeder.toLowerCase().includes(q),
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

const totalPages = computed(() => Math.ceil(filteredBreeders.value.length / perPage.value) || 1)

const paginatedBreeders = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return filteredBreeders.value.slice(start, start + perPage.value)
})

const showingFrom = computed(() =>
    filteredBreeders.value.length === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1,
)
const showingTo = computed(() =>
    Math.min(currentPage.value * perPage.value, filteredBreeders.value.length),
)

function onSearch(value: string | number) {
    search.value = String(value)
    currentPage.value = 1
}

function toggleSort(key: keyof Breeder) {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortKey.value = key
        sortOrder.value = 'asc'
    }
}

function sortIcon(key: keyof Breeder) {
    if (sortKey.value !== key) return '↕'
    return sortOrder.value === 'asc' ? '↑' : '↓'
}

// ─── Modal + Form ───────────────────────────────────────
const showModal = ref(false)

// Bersihkan error saat modal ditutup
watch(showModal, (isOpen) => {
    if (!isOpen) {
        form.clearErrors()
    }
})

const form = useForm({
    name_breeder: '',
})

const submit = () => {
    form.post('/master-data/breeder', {
        preserveScroll: true,
        onSuccess: () => {
            useSwal().fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data breeder berhasil ditambahkan.',
                timer: 3000,
                showConfirmButton: false,
            })
            form.reset()
            showModal.value = false
        },
        onError: () => {
            // Modal tetap terbuka — error tampil langsung di field via InputError
        },
    })
}

function confirmDelete(breederId: number, breederName: string) {
    useSwal().fire({
        title: 'Hapus breeder?',
        text: `Breeder "${breederName}" akan dihapus permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/master-data/breeder/${breederId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    useSwal().fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Breeder berhasil dihapus.',
                        timer: 3000,
                        showConfirmButton: false,
                    })
                },
                onError: () => {
                    useSwal().fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Terjadi kesalahan saat menghapus breeder.',
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
        <Head title="Breeder" />

        <div class="flex flex-col gap-4 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Breeder</h1>
                    <p class="text-sm text-muted-foreground">Manage the breeders in your system.</p>
                </div>

                <Dialog v-model:open="showModal">
                    <DialogTrigger as-child>
                        <Button>New Breeder</Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-[425px]">
                        <DialogHeader>
                            <DialogTitle>Create Breeder</DialogTitle>
                            <DialogDescription>Create a new breeder.</DialogDescription>
                        </DialogHeader>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div class="grid gap-2">
                                <Label for="name_breeder">Name Breeder</Label>
                                <Input id="name_breeder" v-model="form.name_breeder" placeholder="Breeder name" />
                                <InputError :message="form.errors.name_breeder" />
                            </div>
                            <DialogFooter>
                                <Button type="button" variant="outline" @click="showModal = false">Batal</Button>
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
                    <Input :model-value="search" @update:model-value="onSearch" placeholder="Search breeder name..."
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
                                    @click="toggleSort('name_breeder')">
                                    Name Breeder <span class="ml-1">{{ sortIcon('name_breeder') }}</span>
                                </th>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('created_at')">
                                    Created <span class="ml-1">{{ sortIcon('created_at') }}</span>
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-background">
                            <tr v-if="paginatedBreeders.length === 0">
                                <td colspan="3" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                    {{ search ? 'Tidak ada hasil untuk pencarian tersebut.' : 'Belum ada data breeder.' }}
                                </td>
                            </tr>
                            <tr v-for="breeder in paginatedBreeders" :key="breeder.id"
                                class="transition-colors hover:bg-muted/50">
                                <td class="px-4 py-3 text-sm text-foreground">{{ breeder.name_breeder }}</td>
                                <td class="px-4 py-3 text-sm text-foreground">{{ new Intl.DateTimeFormat('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }).format(new Date(breeder.created_at)) }}</td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="`/master-data/breeder/${breeder.id}/edit`"
                                            class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background p-2 text-foreground shadow-sm hover:bg-muted"
                                            title="Edit">
                                            <Pencil :size="16" />
                                        </Link>
                                        <button type="button"
                                            class="inline-flex items-center justify-center rounded-md border border-red-300 bg-red-50 p-2 text-red-700 shadow-sm hover:bg-red-100 dark:border-red-700 dark:bg-red-900 dark:text-red-200 dark:hover:bg-red-800"
                                            title="Hapus"
                                            @click="confirmDelete(breeder.id, breeder.name_breeder)">
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
                        Showing {{ showingFrom }} to {{ showingTo }} of {{ filteredBreeders.length }} entries
                        <span v-if="search">(filtered from {{ breeders.length }} total)</span>
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