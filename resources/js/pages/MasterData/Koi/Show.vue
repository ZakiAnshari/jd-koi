<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Button } from '@/components/ui/button'

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
    photo?: string
    images?: KoiImage[]
    breeder?: Breeder
    created_at: string
}

const page = usePage()
const koi = page.props.koi as Koi

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
        title: koi.name,
        href: `/master-data/koi/${koi.id}`,
    },
]

const photoUrl = koi.photo ? `/storage/${koi.photo}` : null
const photoError = ref(false)

function getBreederName(): string {
    return koi.breeder?.name_breeder ?? '-'
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">

        <Head :title="`Koi: ${koi.name}`" />

        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">{{ koi.name }}</h1>
                    <p class="text-sm text-muted-foreground">Koi Code: {{ koi.code }}</p>
                </div>
                <div class="flex gap-2">

                    <Link href="/master-data/koi"
                        class="rounded-md border border-neutral-200 bg-background px-4 py-2 text-sm font-medium text-foreground hover:bg-muted">
                        Back
                    </Link>
                </div>
            </div>

            <!-- Content: Photo + Details -->
            <div class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <!-- Photo Section (Left) -->
                    <div class="md:col-span-1">
                        <div class="flex flex-col gap-4">
                            <div class="overflow-hidden rounded-lg border border-border bg-muted">
                                <div v-if="photoUrl && !photoError" class="flex items-center justify-center bg-muted">
                                    <img :src="photoUrl" :alt="koi.name" class="h-80 w-full object-cover"
                                        @error="photoError = true" />
                                </div>
                                <div v-else class="flex h-80 items-center justify-center bg-muted">
                                    <div class="text-center">
                                        <p class="text-sm text-muted-foreground">No photo</p>
                                    </div>
                                </div>
                            </div>
                            <p class="text-xs text-muted-foreground text-center">Photo Detail</p>
                        </div>
                    </div>

                    <!-- Details Section (Right) -->
                    <div class="md:col-span-2 space-y-6">
                        <!-- Basic Information -->
                        <div class="space-y-4">
                            <h3 class="text-base font-semibold text-foreground border-b border-border pb-2">Informasi Koi</h3>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="rounded-lg p-4 border border-border">
                                    <p class="text-xs text-muted-foreground uppercase tracking-wide">Code</p>
                                    <p class="text-sm font-medium text-foreground mt-1">{{ koi.code }}</p>
                                </div>
                                <div class="rounded-lg p-4 border border-border">
                                    <p class="text-xs text-muted-foreground uppercase tracking-wide">Name</p>
                                    <p class="text-sm font-medium text-foreground mt-1">{{ koi.name }}</p>
                                </div>
                                <div class="rounded-lg p-4 border border-border">
                                    <p class="text-xs text-muted-foreground uppercase tracking-wide">Breeder</p>
                                    <p class="text-sm font-medium text-foreground mt-1">{{ getBreederName() }}</p>
                                </div>
                                <div class="rounded-lg p-4 border border-border">
                                    <p class="text-xs text-muted-foreground uppercase tracking-wide">Created</p>
                                    <p class="text-sm font-medium text-foreground mt-1">{{ new Date(koi.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Image Gallery -->
                <div v-if="koi.images && koi.images.length > 0" class="mt-8 border-t border-border pt-8">
                    <h3 class="text-base font-semibold text-foreground border-b border-border pb-2 mb-4">Image Gallery</h3>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                        <div v-for="image in koi.images" :key="image.id" class="group relative overflow-hidden rounded-lg border border-border bg-muted">
                            <img 
                                :src="`/storage/${image.image_path}`"
                                :alt="`Koi gallery image ${image.id}`"
                                class="h-24 w-full object-cover transition-transform group-hover:scale-110"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
