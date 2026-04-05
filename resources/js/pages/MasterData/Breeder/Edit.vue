<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { edit, index } from '@/routes/breeders'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import { useForm } from '@inertiajs/vue3'
import { useSwal } from '@/composables/useSwal'
import type { BreadcrumbItem } from '@/types'

type Breeder = {
    id: number
    name_breeder: string
}

const page = usePage()
const breeder = page.props.breeder as Breeder

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Master Data',
        href: '#',
    },
    {
        title: 'Breeder',
        href: index(),
    },
    {
        title: 'Edit',
        href: edit(breeder.id),
    },
]

const form = useForm({
    name_breeder: breeder.name_breeder,
})

const submit = () => {
    form.put(`/master-data/breeder/${breeder.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            useSwal().fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data breeder berhasil diperbarui.',
                timer: 3000,
                showConfirmButton: false,
            })
        },
        onError: () => {
            useSwal().fire({
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
        <Head title="Edit Breeder" />

        <div class="space-y-6 p-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Edit Breeder</h1>
                    <p class="text-sm text-muted-foreground">Update breeder information.</p>
                </div>
                <Link
                    :href="index()"
                    class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted"
                >
                    Back to list
                </Link>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name_breeder">Name Breeder</Label>
                        <Input id="name_breeder" v-model="form.name_breeder" placeholder="Breeder name" />
                        <InputError :message="form.errors.name_breeder" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing">Save</Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>