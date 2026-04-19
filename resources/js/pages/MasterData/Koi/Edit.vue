<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { X } from 'lucide-vue-next'
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
    sort_order: number
}

type Koi = {
    id: number
    breeder_id: number
    code: string
    name: string
    photo: string | null
    images: KoiImage[]
}

const page = usePage()
const koi = page.props.koi as Koi
const breeders = (page.props.breeders as Breeder[]) || []

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Master Data',
        href: '#',
    },
    {
        title: 'Data Koi',
        href: '/master-data/koi',
    },
    {
        title: 'Edit',
        href: `/master-data/koi/${koi.id}/edit`,
    },
]

// ─── Form ───────────────────────────────────────────────
const photoInputRef = ref<HTMLInputElement | null>(null)
const imagesInputRef = ref<HTMLInputElement | null>(null)
const photoError = ref(false)
const breederSearch = ref('')
const selectedImages = ref<File[]>([])
const imagePreviews = ref<string[]>([])
const deletedImageIds = ref<number[]>([])

const filteredBreeders = computed(() => {
    if (!breederSearch.value) return breeders
    const q = breederSearch.value.toLowerCase()
    return breeders.filter(b => b.name_breeder.toLowerCase().includes(q))
})

const form = useForm({
    breeder_id: String(koi.breeder_id),
    code: koi.code,
    name: koi.name,
    photo: null as File | null,
    images: [] as File[],
    delete_images: [] as number[],
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

function deleteExistingImage(imageId: number) {
    if (!deletedImageIds.value.includes(imageId)) {
        deletedImageIds.value.push(imageId)
        form.delete_images = deletedImageIds.value
    }
}

function undoDeleteExistingImage(imageId: number) {
    const index = deletedImageIds.value.indexOf(imageId)
    if (index > -1) {
        deletedImageIds.value.splice(index, 1)
        form.delete_images = deletedImageIds.value
    }
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
                    <p class="text-sm text-muted-foreground">Update koi information.</p>
                </div>
                <Link
                    href="/master-data/koi"
                    class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted"
                >
                    Back to list
                </Link>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="breeder_id">Breeder *</Label>
                            <Select v-model="form.breeder_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select a breeder" />
                                </SelectTrigger>
                                <SelectContent class="w-[--radix-select-trigger-width] min-w-[20rem] p-0">
                                    <div class="sticky top-0 border-b bg-background p-2">
                                        <Input
                                            v-model="breederSearch"
                                            placeholder="Search breeder..."
                                            class="h-8"
                                        />
                                    </div>
                                    <div class="max-h-48 overflow-y-auto">
                                        <div v-if="filteredBreeders.length === 0" class="px-2 py-2 text-sm text-muted-foreground">
                                            No breeders found
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
                            <Input
                                id="photo"
                                ref="photoInputRef"
                                type="file"
                                accept="image/*"
                                @change="onPhotoChange"
                            />
                            <p class="text-xs text-muted-foreground">JPG, PNG, GIF (max 2MB)</p>
                            <InputError :message="form.errors.photo" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="images">Additional Images (Multiple)</Label>
                            <Input
                                id="images"
                                ref="imagesInputRef"
                                type="file"
                                multiple
                                accept="image/*"
                                @change="onImagesChange"
                            />
                            <p class="text-xs text-muted-foreground">JPG, PNG, GIF, WebP (max 2MB per file)</p>
                            <InputError :message="form.errors['images.*']" />
                        </div>
                    </div>

                    <!-- Existing Images -->
                    <div v-if="koi.images && koi.images.length > 0" class="rounded-md border border-border bg-muted/50 p-3">
                        <p class="mb-3 text-xs font-medium text-muted-foreground">Existing images:</p>
                        <div class="flex flex-wrap gap-3">
                            <div 
                                v-for="image in koi.images" 
                                :key="image.id"
                                :class="[
                                    'relative',
                                    deletedImageIds.includes(image.id) && 'opacity-50'
                                ]"
                            >
                                <img
                                    :src="`/storage/${image.image_path}`"
                                    :alt="`Koi image ${image.id}`"
                                    class="h-20 w-20 rounded-md border border-border object-cover shadow-sm"
                                />
                                <button
                                    v-if="!deletedImageIds.includes(image.id)"
                                    type="button"
                                    @click="deleteExistingImage(image.id)"
                                    class="absolute -right-2 -top-2 rounded-full bg-red-500 p-1 text-white hover:bg-red-600"
                                    title="Delete image"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    @click="undoDeleteExistingImage(image.id)"
                                    class="absolute inset-0 rounded-md bg-black/30 flex items-center justify-center hover:bg-black/50 transition-colors"
                                    title="Undo delete"
                                >
                                    <span class="text-white text-xs font-medium">Undo</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- New Image Previews -->
                    <div v-if="imagePreviews.length > 0" class="rounded-md border border-border bg-muted/50 p-3">
                        <p class="mb-3 text-xs font-medium text-muted-foreground">New images to upload:</p>
                        <div class="flex flex-wrap gap-3">
                            <div v-for="(preview, index) in imagePreviews" :key="index" class="relative">
                                <img 
                                    :src="preview" 
                                    :alt="`Preview ${index + 1}`"
                                    class="h-20 w-20 rounded-md border border-border object-cover shadow-sm"
                                />
                                <button
                                    type="button"
                                    @click="removeImagePreview(index)"
                                    class="absolute -right-2 -top-2 rounded-full bg-red-500 p-1 text-white hover:bg-red-600"
                                    title="Remove from upload"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Current Photo Preview -->
                    <div v-if="koi.photo" class="rounded-md border border-border bg-muted/50 p-3">
                        <p class="mb-2 text-xs font-medium text-muted-foreground">Current main photo:</p>
                        <img
                            v-if="!photoError"
                            :src="`/storage/${koi.photo}`"
                            alt="Current photo"
                            class="h-32 w-32 rounded-md border border-border object-cover shadow-sm"
                            @error="photoError = true"
                        />
                        <p v-else class="text-xs text-red-500">Photo tidak dapat dimuat</p>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing">Save</Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>