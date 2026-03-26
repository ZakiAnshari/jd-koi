<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
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

type Breeder = {
    id: number
    name_breeder: string
}

type Koi = {
    id: number
    breeder_id: number
    code: string
    name: string
    photo: string | null
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

// ─── SweetAlert Helper ──────────────────────────────────
function fireSwal(options: SweetAlertOptions) {
    return Swal.fire({
        ...options,
        scrollbarPadding: false,
        heightAuto: false,
    })
}

// ─── Form ───────────────────────────────────────────────
const photoInputRef = ref<HTMLInputElement | null>(null)
const photoError = ref(false)
const breederSearch = ref('')

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
})

function onPhotoChange(e: Event) {
    const target = e.target as HTMLInputElement
    form.photo = target.files?.[0] ?? null
}

const submit = () => {
    form.post(`/master-data/koi/${koi.id}?_method=PUT`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            fireSwal({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data koi berhasil diperbarui.',
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
                    </div>

                    <!-- Current Photo Preview -->
                    <div v-if="koi.photo" class="rounded-md border border-border bg-muted/50 p-3">
                        <p class="mb-2 text-xs font-medium text-muted-foreground">Current photo:</p>
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