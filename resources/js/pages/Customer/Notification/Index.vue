<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import type { BreadcrumbItem } from '@/types'
import { CheckCheck, Check, Bell } from 'lucide-vue-next'
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

const props = defineProps<{ notifications: Notifikasi[] }>()

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Notifikasi', href: '/customer/notifikasi' }]

// ─── DataTable State ──────────────────────────────────────
const search = ref('')
const filterUnread = ref(false)
const currentPage = ref(1)
const perPage = ref(10)

const filteredNotifications = computed(() => {
    let result = props.notifications ?? []
    if (filterUnread.value) result = result.filter(n => !n.is_read)
    if (search.value) {
        const q = search.value.toLowerCase()
        result = result.filter(n =>
            n.title.toLowerCase().includes(q) ||
            n.message.toLowerCase().includes(q)
        )
    }
    return result
})

const totalPages = computed(() => Math.ceil(filteredNotifications.value.length / perPage.value) || 1)

watch(totalPages, (n) => { if (currentPage.value > n) currentPage.value = 1 })

const paginatedNotifications = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return filteredNotifications.value.slice(start, start + perPage.value)
})

const showingFrom = computed(() =>
    filteredNotifications.value.length === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1
)
const showingTo = computed(() =>
    Math.min(currentPage.value * perPage.value, filteredNotifications.value.length)
)

const hasUnread = computed(() => props.notifications.some(n => !n.is_read))
const unreadCount = computed(() => props.notifications.filter(n => !n.is_read).length)

function onSearch(v: string | number) { search.value = String(v); currentPage.value = 1 }

const timeAgo = (dateString: string) => {
    const isoDate = dateString.endsWith('Z') || dateString.includes('+') ? dateString : dateString + 'Z'
    const diff = Date.now() - new Date(isoDate).getTime()
    const s = Math.floor(diff / 1000), m = Math.floor(s / 60), h = Math.floor(m / 60), d = Math.floor(h / 24)
    if (s < 60) return `${s} detik lalu`
    if (m < 60) return `${m} menit lalu`
    if (h < 24) return `${h} jam lalu`
    if (d === 1) return 'kemarin'
    return `${d} hari lalu`
}

function handleClick(notif: Notifikasi) {
    if (!notif.is_read) router.put(notifRead.url(notif.id), {}, { preserveScroll: true })
}

function handleMarkAllRead() {
    router.put(notifReadAll.url(), {}, { preserveScroll: true })
}
</script>

<template>
    <Head title="Notifikasi" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Notifikasi</h1>
                    <p class="text-sm text-muted-foreground">Informasi terbaru tentang pesanan Anda.</p>
                </div>
                <button
                    v-if="hasUnread"
                    @click="handleMarkAllRead"
                    class="inline-flex items-center gap-2 rounded-md border border-neutral-200 bg-background px-3 py-2 text-sm text-foreground shadow-sm hover:bg-muted self-start md:self-auto"
                    title="Tandai semua dibaca"
                >
                    <CheckCheck class="h-4 w-4" />
                    <span>Tandai Semua Dibaca</span>
                </button>
            </div>

            <!-- Filter bar -->
            <div class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                <div class="flex flex-col gap-2 border-b border-border p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <Input
                            :model-value="search"
                            @update:model-value="onSearch"
                            placeholder="Cari notifikasi..."
                            class="max-w-sm"
                        />
                        <button
                            @click="filterUnread = !filterUnread; currentPage = 1"
                            :class="[
                                'inline-flex items-center gap-1.5 rounded-md border px-3 py-2 text-sm transition-colors',
                                filterUnread
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'border-input bg-background text-foreground hover:bg-muted'
                            ]"
                        >
                            <Bell class="h-3.5 w-3.5" />
                            Belum dibaca
                            <span v-if="unreadCount > 0" class="ml-1 rounded-full bg-background/20 px-1.5 py-0.5 text-xs font-semibold">
                                {{ unreadCount }}
                            </span>
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted-foreground">Show</span>
                        <select
                            :value="perPage"
                            @change="(e) => { perPage = Number((e.target as HTMLSelectElement).value); currentPage = 1 }"
                            class="rounded-md border border-input bg-background px-3 py-1 text-sm"
                        >
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        <span class="text-sm text-muted-foreground">entries</span>
                    </div>
                </div>

                <!-- Notification List -->
                <div class="divide-y divide-border bg-background">
                    <div v-if="paginatedNotifications.length === 0" class="flex flex-col items-center justify-center gap-3 py-16 text-center">
                        <Bell class="h-10 w-10 text-muted-foreground/40" />
                        <p class="text-sm text-muted-foreground">
                            {{ search || filterUnread ? 'Tidak ada notifikasi yang sesuai filter.' : 'Belum ada notifikasi.' }}
                        </p>
                    </div>

                    <div
                        v-for="notif in paginatedNotifications"
                        :key="notif.id"
                        :class="[
                            'flex items-start gap-4 p-4 transition-colors',
                            notif.is_read ? 'bg-background hover:bg-muted/30' : 'bg-blue-50/40 dark:bg-blue-950/10 hover:bg-blue-50/60 dark:hover:bg-blue-950/20'
                        ]"
                    >
                        <!-- Type icon -->
                        <div class="mt-0.5 flex-shrink-0">
                            <div v-if="notif.type === 'order_confirmed'" class="w-9 h-9 rounded-full bg-muted flex items-center justify-center">
                                <svg class="w-5 h-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div v-else-if="notif.type === 'order_completed'" class="w-9 h-9 rounded-full bg-muted flex items-center justify-center">
                                <svg class="w-5 h-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div v-else class="w-9 h-9 rounded-full bg-muted flex items-center justify-center">
                                <Bell class="w-5 h-5 text-muted-foreground" />
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 mb-0.5">
                                <p :class="['text-sm', notif.is_read ? 'font-medium text-foreground' : 'font-bold text-foreground']">
                                    {{ notif.title }}
                                </p>
                                <span v-if="!notif.is_read" class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0" />
                            </div>
                            <p class="text-sm text-muted-foreground leading-relaxed">{{ notif.message }}</p>
                            <p class="mt-1 text-xs text-muted-foreground/70">{{ timeAgo(notif.created_at) }}</p>
                        </div>

                        <!-- Mark as read button -->
                        <button
                            v-if="!notif.is_read"
                            @click="handleClick(notif)"
                            class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background p-2 text-foreground shadow-sm hover:bg-muted flex-shrink-0"
                            title="Tandai sudah dibaca"
                        >
                            <Check class="h-4 w-4" />
                        </button>
                        <div v-else class="w-9 flex-shrink-0" />
                    </div>
                </div>

                <!-- Footer: info + pagination -->
                <div class="flex flex-col items-center justify-between gap-2 border-t border-border px-4 py-3 sm:flex-row">
                    <p class="text-sm text-muted-foreground">
                        Showing {{ showingFrom }} to {{ showingTo }} of {{ filteredNotifications.length }} entries
                        <span v-if="search || filterUnread">(filtered from {{ notifications.length }} total)</span>
                    </p>
                    <div class="flex items-center gap-1">
                        <Button variant="outline" size="sm" :disabled="currentPage <= 1" @click="currentPage--">Previous</Button>
                        <template v-for="p in totalPages" :key="p">
                            <Button size="sm" :variant="p === currentPage ? 'default' : 'outline'" @click="currentPage = p">
                                {{ p }}
                            </Button>
                        </template>
                        <Button variant="outline" size="sm" :disabled="currentPage >= totalPages" @click="currentPage++">Next</Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
