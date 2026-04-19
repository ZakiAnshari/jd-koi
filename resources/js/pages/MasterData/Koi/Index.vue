<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { X } from 'lucide-vue-next'
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

type Breeder = {
    id: number
    name_breeder: string
}

type Koi = {
    id: number
    breeder_id: number
    code: string
    name: string
    breeder?: Breeder
    created_at: string
}

const page = usePage()
const kois = computed(() => (page.props.kois as Koi[] | null) ?? [])
const breeders = computed(() => (page.props.breeders as Breeder[] | null) ?? [])

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Master Data',
        href: '#',
    },
    {
        title: 'Data Koi',
        href: '/master-data/koi',
    },
]

// ─── DataTable State ────────────────────────────────────
const search = ref('')
const sortKey = ref<keyof Koi>('created_at')
const sortOrder = ref<'asc' | 'desc'>('desc')
const currentPage = ref(1)
const perPage = ref(10)

const filteredKois = computed(() => {
    let result = kois.value

    if (search.value) {
        const q = search.value.toLowerCase()
        result = result.filter(
            (k) =>
                k.code.toLowerCase().includes(q) ||
                k.name.toLowerCase().includes(q),
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

const totalPages = computed(() => Math.ceil(filteredKois.value.length / perPage.value) || 1)

const paginatedKois = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return filteredKois.value.slice(start, start + perPage.value)
})

const showingFrom = computed(() =>
    filteredKois.value.length === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1,
)
const showingTo = computed(() =>
    Math.min(currentPage.value * perPage.value, filteredKois.value.length),
)

function onSearch(value: string | number) {
    search.value = String(value)
    currentPage.value = 1
}

function toggleSort(key: keyof Koi) {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortKey.value = key
        sortOrder.value = 'asc'
    }
}

function sortIcon(key: keyof Koi) {
    if (sortKey.value !== key) return '↕'
    return sortOrder.value === 'asc' ? '↑' : '↓'
}

// ─── Modal + Form ───────────────────────────────────────
const showModal = ref(false)
const photoInputRef = ref<HTMLInputElement | null>(null)
const imagesInputRef = ref<HTMLInputElement | null>(null)
const breederSearch = ref('')
const selectedImages = ref<File[]>([])
const imagePreviews = ref<string[]>([])

// Bersihkan error dan search saat modal ditutup
watch(showModal, (isOpen) => {
    if (!isOpen) {
        form.clearErrors()
        breederSearch.value = ''
        selectedImages.value = []
        imagePreviews.value = []
        if (imagesInputRef.value) {
            imagesInputRef.value.value = ''
        }
    }
})

const filteredBreeders = computed(() => {
    if (!breederSearch.value) return breeders.value
    const q = breederSearch.value.toLowerCase()
    return breeders.value.filter(b => b.name_breeder.toLowerCase().includes(q))
})

const form = useForm({
    breeder_id: '',
    code: '',
    name: '',
    photo: null as File | null,
    images: [] as File[],
})

function onPhotoChange(e: Event) {
    const target = e.target as HTMLInputElement
    form.photo = target.files?.[0] ?? null
}

function onImagesChange(e: Event) {
    const target = e.target as HTMLInputElement
    const files = target.files ? Array.from(target.files) : []
    selectedImages.value = files
    
    // Generate previews
    imagePreviews.value = []
    files.forEach(file => {
        const reader = new FileReader()
        reader.onload = (event) => {
            imagePreviews.value.push(event.target?.result as string)
        }
        reader.readAsDataURL(file)
    })
    
    form.images = files
}

function removeImagePreview(index: number) {
    selectedImages.value.splice(index, 1)
    imagePreviews.value.splice(index, 1)
    form.images = selectedImages.value
    
    if (imagesInputRef.value) {
        const dataTransfer = new DataTransfer()
        selectedImages.value.forEach(file => dataTransfer.items.add(file))
        imagesInputRef.value.files = dataTransfer.files
    }
}

const submit = () => {
    form.post('/master-data/koi', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            useSwal().fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data koi berhasil ditambahkan.',
                timer: 3000,
                showConfirmButton: false,
            })
            form.reset()
            if (photoInputRef.value) {
                photoInputRef.value.value = ''
            }
            if (imagesInputRef.value) {
                imagesInputRef.value.value = ''
            }
            selectedImages.value = []
            imagePreviews.value = []
            showModal.value = false
            breederSearch.value = ''
        },
        onError: () => {
            // Modal tetap terbuka — error tampil langsung di field via InputError
        },
    })
}

function confirmDelete(koiId: number, koiName: string) {
    useSwal().fire({
        title: 'Hapus koi?',
        text: `Koi "${koiName}" akan dihapus permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/master-data/koi/${koiId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    useSwal().fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Koi berhasil dihapus.',
                        timer: 3000,
                        showConfirmButton: false,
                    })
                },
                onError: () => {
                    useSwal().fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Terjadi kesalahan saat menghapus koi.',
                        timer: 3000,
                        showConfirmButton: false,
                    })
                },
            })
        }
    })
}

function getBreederName(koi: Koi): string {
    if (koi.breeder?.name_breeder) return koi.breeder.name_breeder
    const breeder = breeders.value.find(b => b.id === koi.breeder_id)
    return breeder?.name_breeder ?? '-'
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
                    <p class="text-sm text-muted-foreground">Manage the koi data in your system.</p>
                </div>

                <Dialog v-model:open="showModal">
                    <DialogTrigger as-child>
                        <Button>New Koi</Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-[600px]">
                        <DialogHeader>
                            <DialogTitle>Create Koi</DialogTitle>
                            <DialogDescription>Create a new koi data.</DialogDescription>
                        </DialogHeader>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div class="space-y-4">
                                <div class="grid gap-2">
                                    <Label for="breeder_id">Breeder *</Label>
                                    <Select v-model="form.breeder_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select a breeder" />
                                        </SelectTrigger>
                                        <SelectContent class="p-0 w-[--radix-select-trigger-width] min-w-[20rem]">
                                            <div class="sticky top-0 bg-background p-2 border-b">
                                                <Input v-model="breederSearch" placeholder="Search breeder..."
                                                    class="h-8" @keydown.stop />
                                            </div>
                                            <div class="max-h-48 overflow-y-auto">
                                                <div v-if="filteredBreeders.length === 0"
                                                    class="px-2 py-2 text-sm text-muted-foreground">
                                                    No breeders found
                                                </div>
                                                <SelectItem v-for="breeder in filteredBreeders" :key="breeder.id"
                                                    :value="String(breeder.id)">
                                                    {{ breeder.name_breeder }}
                                                </SelectItem>
                                            </div>
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="form.errors.breeder_id" />
                                </div>
                                
                                <div class="grid gap-2">
                                    <Label for="code">Code *</Label>
                                    <Input id="code" v-model="form.code" placeholder="e.g., KOI-001" />
                                    <InputError :message="form.errors.code" />
                                </div>
                                
                                <div class="grid gap-2">
                                    <Label for="name">Name *</Label>
                                    <Input id="name" v-model="form.name" placeholder="Koi name" />
                                    <InputError :message="form.errors.name" />
                                </div>
                                
                                <div class="grid gap-2">
                                    <Label for="photo">Photo</Label>
                                    <Input id="photo" ref="photoInputRef" type="file" accept="image/*"
                                        @change="onPhotoChange" />
                                    <p class="text-xs text-muted-foreground">JPG, PNG, GIF (max 2MB)</p>
                                    <InputError :message="form.errors.photo" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="images">Additional Images (Multiple)</Label>
                                    <Input id="images" ref="imagesInputRef" type="file" multiple accept="image/*"
                                        @change="onImagesChange" />
                                    <p class="text-xs text-muted-foreground">JPG, PNG, GIF, WebP (max 2MB per file)</p>
                                    <InputError :message="form.errors['images.*']" />
                                </div>

                                <!-- Image Previews -->
                                <div v-if="imagePreviews.length > 0" class="grid gap-2">
                                    <p class="text-sm font-medium">Preview Images:</p>
                                    <div class="flex flex-wrap gap-3">
                                        <div v-for="(preview, index) in imagePreviews" :key="index" class="relative">
                                            <img 
                                                :src="preview" 
                                                :alt="`Preview ${index + 1}`"
                                                class="h-20 w-20 rounded-lg border border-border object-cover"
                                            />
                                            <button
                                                type="button"
                                                @click="removeImagePreview(index)"
                                                class="absolute -right-2 -top-2 rounded-full bg-red-500 p-1 text-white hover:bg-red-600"
                                            >
                                                <X class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
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
                    <Input :model-value="search" @update:model-value="onSearch" placeholder="Search code or name..."
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
                                    @click="toggleSort('code')">
                                    Code <span class="ml-1">{{ sortIcon('code') }}</span>
                                </th>
                                <th class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('name')">
                                    Name <span class="ml-1">{{ sortIcon('name') }}</span>
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Breeder
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
                            <tr v-if="paginatedKois.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                    {{ search ? 'Tidak ada hasil untuk pencarian tersebut.' : 'Belum ada data koi.' }}
                                </td>
                            </tr>
                            <tr v-for="koi in paginatedKois" :key="koi.id" class="transition-colors hover:bg-muted/50">
                                <td class="px-4 py-3 text-sm text-foreground">{{ koi.code }}</td>
                                <td class="px-4 py-3 text-sm text-foreground">{{ koi.name }}</td>
                                <td class="px-4 py-3 text-sm text-foreground">{{ getBreederName(koi) }}</td>
                                <td class="px-4 py-3 text-sm text-foreground">{{ new
                                    Date(koi.created_at).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }) }}</td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <Link :href="`/master-data/koi/${koi.id}`"
                                        class="mr-2 rounded-md border border-border px-3 py-1 text-xs font-medium text-foreground hover:bg-muted">
                                        Detail
                                    </Link>
                                    <Link :href="`/master-data/koi/${koi.id}/edit`"
                                        class="mr-2 rounded-md border border-border px-3 py-1 text-xs font-medium text-foreground hover:bg-muted">
                                        Edit
                                    </Link>
                                    <button type="button"
                                        class="rounded-md border border-red-500 bg-red-500/10 px-3 py-1 text-xs font-medium text-red-700 hover:bg-red-500/20"
                                        @click="confirmDelete(koi.id, koi.name)">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer: Info + Pagination -->
                <div
                    class="flex flex-col items-center justify-between gap-2 border-t border-border px-4 py-3 sm:flex-row">
                    <p class="text-sm text-muted-foreground">
                        Showing {{ showingFrom }} to {{ showingTo }} of {{ filteredKois.length }} entries
                        <span v-if="search">(filtered from {{ kois.length }} total)</span>
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