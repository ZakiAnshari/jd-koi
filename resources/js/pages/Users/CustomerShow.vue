<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

type Customer = {
    id: number
    name: string
    email: string
    no_telepon?: string | null
    alamat?: string | null
    created_at: string
}

const props = defineProps<{ customer: Customer }>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Hak Akses', href: '#' },
    { title: 'Customer', href: '/admin/hak-akses/customer' },
    { title: props.customer.name, href: '#' },
]

function fmtDate(d: string) {
    return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Customer: ${customer.name}`" />

        <div class="flex flex-col gap-4 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Detail Customer</h1>
                    <p class="text-sm text-muted-foreground">Informasi akun customer.</p>
                </div>
                <Link
                    href="/admin/hak-akses/customer"
                    class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted self-start"
                >
                    Kembali
                </Link>
            </div>

            <!-- Detail Card -->
            <div class="overflow-hidden rounded-lg border border-sidebar-border/70 bg-card p-6 dark:border-sidebar-border">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
                    <!-- Avatar -->
                    <div class="flex items-center gap-4 rounded-lg border border-border bg-muted p-5">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-primary text-lg font-semibold text-primary-foreground">
                            {{ customer.name?.charAt(0)?.toUpperCase() ?? 'C' }}
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-foreground">{{ customer.name }}</h2>
                            <p class="text-sm text-muted-foreground">{{ customer.email }}</p>
                        </div>
                    </div>

                    <!-- Fields -->
                    <div class="grid w-full grid-cols-1 gap-4 sm:grid-cols-2 lg:flex-1">
                        <div class="rounded-lg border border-border bg-muted p-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Nama</dt>
                            <dd class="mt-1 text-sm font-medium text-foreground">{{ customer.name }}</dd>
                        </div>
                        <div class="rounded-lg border border-border bg-muted p-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Email</dt>
                            <dd class="mt-1 text-sm font-medium text-foreground">{{ customer.email }}</dd>
                        </div>
                        <div class="rounded-lg border border-border bg-muted p-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">No Telepon</dt>
                            <dd class="mt-1 text-sm font-medium text-foreground">{{ customer.no_telepon ?? '-' }}</dd>
                        </div>
                        <div class="rounded-lg border border-border bg-muted p-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Alamat</dt>
                            <dd class="mt-1 text-sm font-medium text-foreground">{{ customer.alamat ?? '-' }}</dd>
                        </div>
                        <div class="rounded-lg border border-border bg-muted p-4 sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Tanggal Daftar</dt>
                            <dd class="mt-1 text-sm font-medium text-foreground">{{ fmtDate(customer.created_at) }}</dd>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
