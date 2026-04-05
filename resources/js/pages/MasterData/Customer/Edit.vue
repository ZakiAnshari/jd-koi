<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import { useSwal } from '@/composables/useSwal'
import type { BreadcrumbItem } from '@/types'

type Customer = {
    id: number
    name: string
    phone: string | null
    address: string | null
}

const page = usePage()
const customer = page.props.customer as Customer

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Master Data',
        href: '#',
    },
    {
        title: 'Customer',
        href: '/master-data/customer',
    },
    {
        title: 'Edit',
        href: `/master-data/customer/${customer.id}/edit`,
    },
]

const form = useForm({
    name: customer.name,
    phone: customer.phone ?? '',
    address: customer.address ?? '',
})

const submit = () => {
    form.put(`/master-data/customer/${customer.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            useSwal().fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data customer berhasil diperbarui.',
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
        <Head title="Edit Customer" />

        <div class="space-y-6 p-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Edit Customer</h1>
                    <p class="text-sm text-muted-foreground">Perbarui data customer sesuai kebutuhan.</p>
                </div>
                <Link
                    href="/master-data/customer"
                    class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted"
                >
                    Back to list
                </Link>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="name">Nama *</Label>
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
                            <textarea
                                id="address"
                                v-model="form.address"
                                placeholder="Alamat customer"
                                class="min-h-[120px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground outline-none transition focus:border-primary focus:ring-1 focus:ring-primary/20"
                            ></textarea>
                            <InputError :message="form.errors.address" />
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing">Simpan</Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
