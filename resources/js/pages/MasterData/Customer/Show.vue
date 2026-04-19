<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

type Customer = {
    id: number
    name: string
    phone: string | null
    address: string | null
    created_at: string
    updated_at: string
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
        title: customer.name,
        href: `/master-data/customer/${customer.id}`,
    },
]
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Customer: ${customer.name}`" />

        <div class="space-y-6 p-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">{{ customer.name }}</h1>
                    <p class="text-sm text-muted-foreground">Detail lengkap customer dan informasi kontak.</p>
                </div>
                <div class="flex gap-2">
                    <Link href="/master-data/customer" class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted">Kembali</Link>
                </div>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                <div class="grid gap-6 md:grid-cols-3">
                    <section class="md:col-span-2 rounded-xl border border-border bg-background p-6">
                        <h2 class="text-base font-semibold text-foreground">Informasi Customer</h2>
                        <div class="mt-4 space-y-4">
                            <div>
                                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Nama</p>
                                <p class="mt-1 text-sm font-medium text-foreground">{{ customer.name }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Telepon</p>
                                <p class="mt-1 text-sm font-medium text-foreground">{{ customer.phone || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Alamat</p>
                                <p class="mt-1 whitespace-pre-line text-sm font-medium text-foreground">{{ customer.address || '-' }}</p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-border bg-background p-6">
                        <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Detail Waktu</p>
                        <div class="mt-4 space-y-4">
                            <div class="rounded-lg bg-muted/40 p-4">
                                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Dibuat</p>
                                <p class="mt-1 text-sm font-medium text-foreground">{{ new Date(customer.created_at).toLocaleString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</p>
                            </div>
                            <div class="rounded-lg bg-muted/40 p-4">
                                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Terakhir diperbarui</p>
                                <p class="mt-1 text-sm font-medium text-foreground">{{ new Date(customer.updated_at).toLocaleString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
