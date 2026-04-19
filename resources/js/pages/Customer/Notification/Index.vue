<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { read as notifRead, readAll as notifReadAll } from '@/routes/customer/notifikasi'

type Notifikasi = {
    id: number
    title: string
    message: string
    type: string
    pesanan_id: number | null
    is_read: boolean
    created_at: string
}

type PaginatedNotifikasi = {
    data: Notifikasi[]
    current_page: number
    last_page: number
    prev_page_url: string | null
    next_page_url: string | null
}

const props = defineProps<{
    notifications: PaginatedNotifikasi
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Notifikasi', href: '/customer/notifikasi' },
]

const hasUnread = computed(() => props.notifications.data.some(n => !n.is_read))

const timeAgo = (dateString: string) => {
    const isoDate = dateString.endsWith('Z') || dateString.includes('+') ? dateString : dateString + 'Z'
    const diff = Date.now() - new Date(isoDate).getTime()
    const seconds = Math.floor(diff / 1000)
    const minutes = Math.floor(seconds / 60)
    const hours = Math.floor(minutes / 60)
    const days = Math.floor(hours / 24)

    if (seconds < 60) return `${seconds} detik lalu`
    if (minutes < 60) return `${minutes} menit lalu`
    if (hours < 24) return `${hours} jam lalu`
    if (days === 1) return 'kemarin'
    return `${days} hari lalu`
}

const typeIcon = (type: string) => type

const handleClick = (notif: Notifikasi) => {
    router.put(markAsRead.url(notif.id))
}

const handleMarkAllRead = () => {
    router.put(markAllAsRead.url())
}
</script>

<template>
    <Head title="Notifikasi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="text-4xl font-bold text-foreground">Notifikasi</h1>
                    <p class="text-base text-muted-foreground">Informasi terbaru tentang pesanan Anda</p>
                </div>
                <button
                    v-if="hasUnread"
                    @click="handleMarkAllRead"
                    class="px-4 py-2 text-sm font-semibold rounded-lg border border-sidebar-border/70 dark:border-sidebar-border text-foreground hover:bg-muted transition-colors"
                >
                    Tandai Semua Dibaca
                </button>
            </div>

            <!-- Notification List -->
            <div v-if="notifications.data.length > 0" class="space-y-3">
                <button
                    v-for="notif in notifications.data"
                    :key="notif.id"
                    @click="handleClick(notif)"
                    class="w-full text-left rounded-xl border transition-colors"
                    :class="notif.is_read
                        ? 'border-sidebar-border/70 dark:border-sidebar-border bg-background hover:bg-muted/30'
                        : 'border-blue-200 dark:border-blue-900/50 bg-blue-50/60 dark:bg-blue-950/20 hover:bg-blue-50 dark:hover:bg-blue-950/30'"
                >
                    <div class="flex items-start gap-4 p-4">
                        <!-- Icon -->
                        <div class="flex-shrink-0 mt-0.5">
                            <!-- order_confirmed -->
                            <div v-if="typeIcon(notif.type) === 'order_confirmed'" class="w-9 h-9 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <!-- order_completed -->
                            <div v-else-if="typeIcon(notif.type) === 'order_completed'" class="w-9 h-9 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                </svg>
                            </div>
                            <!-- order_cancelled -->
                            <div v-else class="w-9 h-9 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <p :class="['text-sm', notif.is_read ? 'font-medium text-foreground' : 'font-bold text-foreground']">
                                    {{ notif.title }}
                                </p>
                                <span v-if="!notif.is_read" class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0" />
                            </div>
                            <p class="text-sm text-muted-foreground leading-relaxed">{{ notif.message }}</p>
                        </div>

                        <!-- Time -->
                        <p class="flex-shrink-0 text-xs text-muted-foreground whitespace-nowrap">
                            {{ timeAgo(notif.created_at) }}
                        </p>
                    </div>
                </button>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="flex flex-col items-center justify-center rounded-xl border border-dashed border-border bg-muted/30 p-16 text-center"
            >
                <div class="w-16 h-16 rounded-full bg-muted flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-muted-foreground/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
                <p class="text-lg font-semibold text-foreground mb-1">Belum ada notifikasi</p>
                <p class="text-sm text-muted-foreground">Notifikasi pesanan Anda akan muncul di sini</p>
            </div>

            <!-- Pagination -->
            <div v-if="notifications.last_page > 1" class="flex items-center justify-center gap-2 mt-4 pt-4 border-t border-border">
                <a
                    v-if="notifications.prev_page_url"
                    :href="notifications.prev_page_url"
                    class="px-3 py-2 text-sm font-medium rounded-lg border border-sidebar-border/70 dark:border-sidebar-border text-foreground hover:bg-muted transition-colors"
                >
                    ← Sebelumnya
                </a>
                <button v-else disabled class="px-3 py-2 text-sm font-medium rounded-lg border border-sidebar-border/70 dark:border-sidebar-border text-muted-foreground opacity-50 cursor-not-allowed">
                    ← Sebelumnya
                </button>
                <span class="px-2 py-2 text-sm text-muted-foreground">
                    Halaman {{ notifications.current_page }} dari {{ notifications.last_page }}
                </span>
                <a
                    v-if="notifications.next_page_url"
                    :href="notifications.next_page_url"
                    class="px-3 py-2 text-sm font-medium rounded-lg border border-sidebar-border/70 dark:border-sidebar-border text-foreground hover:bg-muted transition-colors"
                >
                    Selanjutnya →
                </a>
                <button v-else disabled class="px-3 py-2 text-sm font-medium rounded-lg border border-sidebar-border/70 dark:border-sidebar-border text-muted-foreground opacity-50 cursor-not-allowed">
                    Selanjutnya →
                </button>
            </div>
        </div>
    </AppLayout>
</template>
