<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { X, ImagePlus } from 'lucide-vue-next'
import AppLayout from '@/layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import { useSwal } from '@/composables/useSwal'
import type { BreadcrumbItem } from '@/types'
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

type KoiImage = {
    id: number
    image_path: string
    url: string
    sort_order: number
}

type Koi = {
    id: number
    breeder_id: number
    code: string
    name: string
    images: KoiImage[]
}

const page = usePage()
const koi = page.props.koi as Koi
const breeders = (page.props.breeders as Breeder[]) || []

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data', href: '#' },
    { title: 'Data Koi', href: '/master-data/koi' },
    { title: 'Edit', href: `/master-data/koi/${koi.id}/edit` },
]

// ─── Breeder search ──────────────────────────────────────
const breederSearch = ref('')
const filteredBreeders = computed(() => {
    if (!breederSearch.value) return breeders
    const q = breederSearch.value.toLowerCase()
    return breeders.filter(b => b.name_breeder.toLowerCase().includes(q))
})

// ─── Existing images state ───────────────────────────────
const existingImages = ref<KoiImage[]>(koi.images ? [...koi.images] : [])

// ─── New image previews ───────────────────────────────────
const fotoPreviews = ref<{ file: File; url: string }[]>([])

// ─── Form ─────────────────────────────────────────────────
const form = useForm({
    breeder_id: String(koi.breeder_id),
    code: koi.code,
    name: koi.name,
    images: [] as File[],
    delete_images: [] as number[],
})

function handleImagesChange(e: Event) {
    const input = e.target as HTMLInputElement
    if (!input.files?.length) return
    const newFiles = Array.from(input.files)
    form.images = [...form.images, ...newFiles]
    newFiles.forEach(file => {
        const reader = new FileReader()
        reader.onload = ev => {
            fotoPreviews.value.push({ file, url: ev.target?.result as string })
        }
        reader.readAsDataURL(file)
    })
    input.value = ''
}

function removeNewImage(index: number) {
    fotoPreviews.value.splice(index, 1)
    form.images = form.images.filter((_, i) => i !== index)
}

function markDeleteExisting(id: number) {
    form.delete_images = [...form.delete_images, id]
    existingImages.value = existingImages.value.filter(img => img.id !== id)
}

const submit = () => {
    const swal = useSwal()
    form.post(`/master-data/koi/${koi.id}?_method=PUT`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data koi berhasil diperbarui.',
                timer: 3000,
                showConfirmButton: false,
            })
            fotoPreviews.value = []
            form.images = []
            form.delete_images = []
        },
        onError: () => {
            swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: 'Terjadi kesalahan saat memperbarui data.',
                timer: 3000,
                showConfirmButton: false,
            })
        },
    })
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Edit Koi" />

        <div class="space-y-6 p-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Edit Koi</h1>
                    <p class="text-sm text-muted-foreground">Perbarui data dan foto koi.</p>
                </div>
                <Link
                    href="/master-data/koi"
                    class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted"
                >
                    Kembali
                </Link>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                <form @submit.prevent="submit" class="space-y-6">

                    <!-- Data Koi -->
                    <div class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="breeder_id">Breeder *</Label>
                            <Select v-model="form.breeder_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih breeder" />
                                </SelectTrigger>
                                <SelectContent class="w-[--radix-select-trigger-width] min-w-[20rem] p-0">
                                    <div class="sticky top-0 border-b bg-background p-2">
                                        <Input v-model="breederSearch" placeholder="Cari breeder..." class="h-8" />
                                    </div>
                                    <div class="max-h-48 overflow-y-auto">
                                        <div v-if="filteredBreeders.length === 0" class="px-2 py-2 text-sm text-muted-foreground">
                                            Breeder tidak ditemukan
                                        </div>
                                        <SelectItem v-for="breeder in filteredBreeders" :key="breeder.id" :value="String(breeder.id)">
                                            {{ breeder.name_breeder }}
                                        </SelectItem>
                                    </div>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.breeder_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="code">Kode Koi *</Label>
                            <Input id="code" v-model="form.code" placeholder="Contoh: KOI-001" />
                            <InputError :message="form.errors.code" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="name">Nama Koi *</Label>
                            <Input id="name" v-model="form.name" placeholder="Nama koi" />
                            <InputError :message="form.errors.name" />
                        </div>
                    </div>

                    <!-- Foto Koi -->
                    <div class="rounded-lg border border-sidebar-border/70 bg-muted/30 p-4">
                        <h3 class="mb-4 font-semibold">Foto Koi</h3>

                        <!-- Foto tersimpan -->
                        <div v-if="existingImages.length > 0" class="mb-4">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Foto Tersimpan</p>
                            <div class="flex flex-wrap gap-3">
                                <div v-for="img in existingImages" :key="img.id"
                                    class="relative h-24 w-24 overflow-hidden rounded-lg border border-border group">
                                    <img :src="img.url" alt="Foto koi" class="h-full w-full object-cover" />
                                    <button
                                        type="button"
                                        @click="markDeleteExisting(img.id)"
                                        class="absolute inset-0 flex items-center justify-center bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity"
                                        title="Hapus foto"
                                    >
                                        <X class="h-5 w-5 text-white" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="existingImages.length === 0 && fotoPreviews.length === 0"
                            class="mb-3 text-xs italic text-muted-foreground">
                            Belum ada foto tersimpan.
                        </div>

                        <!-- Preview foto baru -->
                        <div v-if="fotoPreviews.length > 0" class="mb-4">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Foto Baru</p>
                            <div class="flex flex-wrap gap-3">
                                <div v-for="(preview, i) in fotoPreviews" :key="i"
                                    class="relative h-24 w-24 overflow-hidden rounded-lg border border-border group">
                                    <img :src="preview.url" :alt="`Preview ${i + 1}`" class="h-full w-full object-cover" />
                                    <button
                                        type="button"
                                        @click="removeNewImage(i)"
                                        class="absolute inset-0 flex items-center justify-center bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity"
                                        title="Batalkan"
                                    >
                                        <X class="h-5 w-5 text-white" />
                                    </button>
                                    <span class="absolute bottom-1 left-1 right-1 truncate rounded bg-black/40 px-1 text-center text-[9px] text-white">
                                        {{ preview.file.name }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Input file -->
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border-2 border-dashed border-border px-4 py-3 text-sm text-muted-foreground transition-colors hover:border-primary/50 hover:text-foreground">
                            <ImagePlus class="h-5 w-5 flex-shrink-0" />
                            <span>Pilih foto (jpg, jpeg, png, webp · maks. 2 MB per file · bisa pilih beberapa)</span>
                            <input
                                type="file"
                                accept="image/jpg,image/jpeg,image/png,image/webp"
                                multiple
                                class="sr-only"
                                @change="handleImagesChange"
                            />
                        </label>
                        <InputError :message="(form.errors as any)['images.*']" class="mt-1" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                        </Button>
                        <Link href="/master-data/koi" class="text-sm text-muted-foreground hover:text-foreground">
                            Batal
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
