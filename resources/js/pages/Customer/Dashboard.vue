<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { ClipboardList, Fish } from 'lucide-vue-next'

type CustomerStats = {
    nama: string
}

const props = defineProps<{
    customerStats: CustomerStats
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/customer/dashboard' },
]

const getInitials = (name: string) =>
    name.split(' ').filter(Boolean).slice(0, 2).map(p => p[0].toUpperCase()).join('')
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">

            <!-- Welcome Banner -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-muted flex items-center justify-center flex-shrink-0 text-foreground text-xl font-bold select-none">
                        {{ getInitials(customerStats.nama) }}
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-foreground">
                            Selamat datang, {{ customerStats.nama }}!
                        </h1>
                        <p class="text-sm text-muted-foreground mt-0.5">
                            Pantau transaksi koi Anda di menu Rekap Transaksi.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="grid gap-4 grid-cols-1 sm:grid-cols-2">
                <a href="/rekap-transaksi"
                    class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6 flex items-center gap-4 hover:bg-muted/30 transition-colors">
                    <div class="w-12 h-12 rounded-xl bg-muted flex items-center justify-center flex-shrink-0">
                        <ClipboardList class="w-6 h-6 text-muted-foreground" />
                    </div>
                    <div>
                        <p class="text-base font-semibold text-foreground">Rekap Transaksi</p>
                        <p class="text-sm text-muted-foreground">Lihat riwayat transaksi koi Anda</p>
                    </div>
                </a>

                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-muted flex items-center justify-center flex-shrink-0">
                        <Fish class="w-6 h-6 text-muted-foreground" />
                    </div>
                    <div>
                        <p class="text-base font-semibold text-foreground">Data Koi</p>
                        <p class="text-sm text-muted-foreground">Informasi koi yang Anda miliki</p>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
