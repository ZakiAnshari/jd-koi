<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Button } from '@/components/ui/button'

interface Breeder {
    id: number
    name_breeder: string
}

interface KatalogKoi {
    id: number
    kode_koi: string
    nama_koi: string
    foto: string | null
    ukuran: number | null
    jenis_kelamin: string
    umur: string | null
    deskripsi: string | null
    harga_jual_idr: number
    qty: number
    breeder?: Breeder
}

defineProps<{
    katalog: KatalogKoi
}>()

const formatIDR = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value)
}

const getPhotoUrl = (foto: string | null) => {
    if (!foto) return 'https://via.placeholder.com/400x400?text=Foto+Tidak+Tersedia'
    return `/storage/${foto}`
}

const getGenderLabel = (gender: string) => {
    const labels = {
        male: 'Jantan',
        female: 'Betina',
        unknown: 'Tidak Diketahui',
    }
    return labels[gender as keyof typeof labels] || gender
}
</script>

<template>
    <div class="min-h-screen bg-background">
        <Head :title="katalog.nama_koi" />

        <!-- Header dengan Back Button -->
        <div class="border-b border-sidebar-border/70 bg-background dark:border-sidebar-border">
            <div class="mx-auto max-w-6xl px-4 py-4 sm:px-6 lg:px-8">
                <Link
                    :href="route('customer.katalog.index')"
                    class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                >
                    ← Kembali ke Katalog
                </Link>
            </div>
        </div>

        <!-- Main Content -->
        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-2">
                <!-- Kolom Kiri: Foto -->
                <div class="flex items-start justify-center">
                    <img
                        :src="getPhotoUrl(katalog.foto)"
                        :alt="katalog.nama_koi"
                        class="h-full max-h-96 w-full rounded-lg object-cover shadow-lg lg:max-h-96"
                    />
                </div>

                <!-- Kolom Kanan: Detail -->
                <div class="space-y-6">
                    <!-- Nama Koi -->
                    <div>
                        <h1 class="text-3xl font-bold text-foreground">{{ katalog.nama_koi }}</h1>
                        <p v-if="katalog.breeder" class="mt-2 text-gray-600 dark:text-gray-400">
                            Breeder: <span class="font-medium">{{ katalog.breeder.name_breeder }}</span>
                        </p>
                    </div>

                    <!-- Harga -->
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Harga</p>
                        <p class="text-4xl font-bold text-blue-600 dark:text-blue-400">
                            {{ formatIDR(katalog.harga_jual_idr) }}
                        </p>
                    </div>

                    <!-- Info Box -->
                    <div class="rounded-lg border border-sidebar-border/70 bg-muted/30 p-4 dark:border-sidebar-border">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <!-- Kode Koi -->
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Kode Koi</p>
                                <p class="font-medium">{{ katalog.kode_koi }}</p>
                            </div>

                            <!-- Ukuran -->
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Ukuran</p>
                                <p class="font-medium">
                                    {{ katalog.ukuran ? `${katalog.ukuran} cm` : '-' }}
                                </p>
                            </div>

                            <!-- Jenis Kelamin -->
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Jenis Kelamin</p>
                                <p class="font-medium">{{ getGenderLabel(katalog.jenis_kelamin) }}</p>
                            </div>

                            <!-- Umur -->
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Umur</p>
                                <p class="font-medium">{{ katalog.umur || '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Deskripsi -->
                    <div v-if="katalog.deskripsi">
                        <h2 class="mb-2 text-lg font-semibold">Deskripsi</h2>
                        <p class="whitespace-pre-line text-gray-700 dark:text-gray-300">
                            {{ katalog.deskripsi }}
                        </p>
                    </div>

                    <!-- Stok Tersedia -->
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Stok Tersedia</p>
                        <p class="text-lg font-semibold text-foreground">
                            {{ katalog.qty }} ekor
                        </p>
                    </div>

                    <!-- Tombol Pesan -->
                    <Button class="w-full bg-blue-600 py-6 text-lg hover:bg-blue-700">
                        Pesan Koi Ini
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
