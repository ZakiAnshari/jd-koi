<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import type { BreadcrumbItem } from '@/types'
import { Eye } from 'lucide-vue-next'

type User = {
    id: number
    name: string
}

type ActivityLog = {
    id: number
    user?: User | null
    module: string
    action: string
    description: string
    old_values?: Record<string, unknown> | null
    new_values?: Record<string, unknown> | null
    ip_address?: string | null
    user_agent?: string | null
    created_at: string
}

const page = usePage()
const activityLogs = computed(() => (page.props.activityLogs as { data: ActivityLog[] }).data ?? [])
const users = computed(() => (page.props.users as User[]) ?? [])
const modules = computed(() => (page.props.modules as string[]) ?? [])
const actions = computed(() => (page.props.actions as string[]) ?? [])
const initialFilters = page.props.filters as {
    module: string
    action: string
    user_id: string
    from: string
    to: string
    per_page: string
}

const moduleFilter = ref(initialFilters.module || 'all')
const actionFilter = ref(initialFilters.action || 'all')
const userFilter = ref(initialFilters.user_id || 'all')
const fromDate = ref(initialFilters.from || '')
const toDate = ref(initialFilters.to || '')
const perPage = ref(initialFilters.per_page || '25')

const perPageOptions = [
    { value: '5',   label: '5' },
    { value: '10',  label: '10' },
    { value: '25',  label: '25' },
    { value: '50',  label: '50' },
    { value: '100', label: '100' },
]

const filters = computed(() => ({
    module:   moduleFilter.value,
    action:   actionFilter.value,
    user_id:  userFilter.value,
    from:     fromDate.value,
    to:       toDate.value,
    per_page: perPage.value,
}))

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Log Aktivitas',
        href: '/log-aktivitas',
    },
]

// ─── DataTable State ────────────────────────────────────
const search = ref('')
const sortKey = ref<keyof ActivityLog>('created_at')
const sortOrder = ref<'asc' | 'desc'>('desc')

const filteredLogs = computed(() => {
    let result = activityLogs.value

    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        result = result.filter(
            (log) =>
                (log.user?.name ?? 'System').toLowerCase().includes(q) ||
                log.module.toLowerCase().includes(q) ||
                log.action.toLowerCase().includes(q) ||
                log.description.toLowerCase().includes(q),
        )
    }

    return [...result].sort((a, b) => {
        const aVal = (a as any)[sortKey.value] ?? ''
        const bVal = (b as any)[sortKey.value] ?? ''
        if (typeof aVal === 'string' && typeof bVal === 'string') {
            return sortOrder.value === 'asc'
                ? aVal.localeCompare(bVal)
                : bVal.localeCompare(aVal)
        }
        return 0
    })
})

function onSearch(value: string | number) {
    search.value = String(value)
}

function toggleSort(key: keyof ActivityLog) {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortKey.value = key
        sortOrder.value = 'asc'
    }
}

function sortIcon(key: keyof ActivityLog) {
    if (sortKey.value !== key) return '↕'
    return sortOrder.value === 'asc' ? '↑' : '↓'
}

// ─── Detail Modal ────────────────────────────────────────
const selectedLog = ref<ActivityLog | null>(null)
const detailOpen = ref(false)

function openDetails(log: ActivityLog) {
    selectedLog.value = log
    detailOpen.value = true
}

function closeDetails() {
    detailOpen.value = false
    selectedLog.value = null
}

// ─── Formatting ───────────────────────────────────────────
function formatLogDate(value: string) {
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    }).format(new Date(value))
}

function badgeClass(moduleName: string) {
    switch (moduleName) {
        case 'Dashboard':
            return 'bg-slate-100 text-slate-700'
        case 'Data Koi':
            return 'bg-emerald-100 text-emerald-700'
        case 'Breeder':
            return 'bg-violet-100 text-violet-700'
        case 'Customer':
            return 'bg-blue-100 text-blue-700'
        case 'Pembelian':
            return 'bg-amber-100 text-amber-700'
        case 'Penjualan':
            return 'bg-fuchsia-100 text-fuchsia-700'
        case 'Stok Koi':
            return 'bg-cyan-100 text-cyan-700'
        case 'Autentikasi':
            return 'bg-slate-100 text-slate-700'
        default:
            return 'bg-muted text-foreground'
    }
}

function actionBadgeClass(action: string) {
    switch (action) {
        case 'create':
            return 'bg-emerald-100 text-emerald-700'
        case 'update':
            return 'bg-blue-100 text-blue-700'
        case 'delete':
            return 'bg-red-100 text-red-700'
        case 'login':
            return 'bg-teal-100 text-teal-700'
        case 'logout':
            return 'bg-slate-100 text-slate-700'
        default:
            return 'bg-muted text-foreground'
    }
}

function formatJsonValue(value: unknown) {
    if (value === null || value === undefined) return '-'
    if (typeof value === 'object') {
        return JSON.stringify(value, null, 2)
    }
    return String(value)
}

// ─── Server-side Pagination ───────────────────────────────
const pagination = computed(() => {
    const p = page.props.activityLogs as any
    return {
        current_page: p.current_page ?? 1,
        last_page:    p.last_page    ?? 1,
        total:        p.total        ?? 0,
        from:         p.from         ?? 0,
        to:           p.to           ?? 0,
        links:        p.links        ?? [],
    }
})

const pageLinks = computed(() => {
    const links = pagination.value.links
    return links.slice(1, links.length - 1)
})

function goTo(url: string | null) { if (url) router.visit(url, { preserveState: true, preserveScroll: true }) }
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Log Aktivitas" />

        <div class="flex flex-col gap-4 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Log Aktivitas</h1>
                    <p class="text-sm text-muted-foreground">Riwayat seluruh aktivitas yang terjadi di sistem</p>
                </div>
            </div>

            <!-- Filter Panel -->
            <div class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                <div class="grid gap-4 md:grid-cols-6">
                    <div class="space-y-1">
                        <Label>Modul</Label>
                        <select v-model="moduleFilter" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="all">Semua Modul</option>
                            <option v-for="module in modules" :key="module" :value="module">{{ module }}</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <Label>Aksi</Label>
                        <select v-model="actionFilter" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="all">Semua Aksi</option>
                            <option v-for="action in actions" :key="action" :value="action">{{ action }}</option>
                        </select>
                    </div>
                    <div class="space-y-1 md:col-span-2">
                        <Label>User</Label>
                        <select v-model="userFilter" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="all">Semua User</option>
                            <option v-for="user in users" :key="user.id" :value="String(user.id)">{{ user.name }}</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <Label>Dari</Label>
                        <Input type="date" v-model="fromDate" />
                    </div>
                    <div class="space-y-1">
                        <Label>Sampai</Label>
                        <Input type="date" v-model="toDate" />
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <Button @click.prevent="router.get('/log-aktivitas', filters, { preserveState: true, preserveScroll: true })">Filter</Button>
                    <Button variant="outline" @click.prevent="() => { moduleFilter='all'; actionFilter='all'; userFilter='all'; fromDate=''; toDate=''; perPage='25'; router.get('/log-aktivitas', { module:'all', action:'all', user_id:'all', from:'', to:'', per_page:'25' }, { preserveState: true, preserveScroll: true }) }">Reset</Button>
                </div>
            </div>

            <!-- DataTable -->
            <div class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                <!-- Toolbar: Search + Per Page -->
                <div class="flex flex-col gap-2 border-b border-border p-4 sm:flex-row sm:items-center sm:justify-between">
                    <Input
                        :model-value="search"
                        @update:model-value="onSearch"
                        placeholder="Cari user, modul, aksi, atau deskripsi..."
                        class="max-w-sm"
                    />
                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                        <span>Show</span>
                        <select
                            v-model="perPage"
                            class="rounded-md border border-input bg-background px-2 py-1 text-sm"
                            @change="router.get('/log-aktivitas', filters, { preserveState: true, preserveScroll: true })"
                        >
                            <option v-for="opt in perPageOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                        <span>entries</span>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th
                                    class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('created_at')"
                                >
                                    Waktu <span class="ml-1">{{ sortIcon('created_at') }}</span>
                                </th>
                                <th
                                    class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('user')"
                                >
                                    User <span class="ml-1">{{ sortIcon('user') }}</span>
                                </th>
                                <th
                                    class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('module')"
                                >
                                    Modul <span class="ml-1">{{ sortIcon('module') }}</span>
                                </th>
                                <th
                                    class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('action')"
                                >
                                    Aksi <span class="ml-1">{{ sortIcon('action') }}</span>
                                </th>
                                <th
                                    class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                                    @click="toggleSort('description')"
                                >
                                    Deskripsi <span class="ml-1">{{ sortIcon('description') }}</span>
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-background">
                            <tr v-if="filteredLogs.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                    {{ search ? 'Tidak ada hasil untuk pencarian tersebut.' : 'Belum ada log aktivitas.' }}
                                </td>
                            </tr>
                            <tr
                                v-for="log in filteredLogs"
                                :key="log.id"
                                class="transition-colors hover:bg-muted/50"
                            >
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-foreground">{{ formatLogDate(log.created_at) }}</td>
                                <td class="px-4 py-3 text-sm text-foreground">{{ log.user?.name ?? 'System' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span :class="badgeClass(log.module) + ' inline-flex rounded-full px-3 py-1 text-xs font-semibold'">{{ log.module }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span :class="actionBadgeClass(log.action) + ' inline-flex rounded-full px-3 py-1 text-xs font-semibold'">{{ log.action }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-foreground">{{ log.description }}</td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background p-2 text-foreground shadow-sm hover:bg-muted"
                                            title="Detail"
                                            @click="openDetails(log)"
                                        >
                                            <Eye :size="16" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer: Info + Pagination -->
                <div class="flex flex-col items-center justify-between gap-2 border-t border-border px-4 py-3 sm:flex-row">
                    <p class="text-sm text-muted-foreground">
                        <template v-if="search">
                            Showing {{ filteredLogs.length }} of {{ activityLogs.length }} entries on this page
                            <span>(filtered)</span>
                        </template>
                        <template v-else>
                            Showing {{ pagination.from ?? 0 }} to {{ pagination.to ?? 0 }} of {{ pagination.total }} entries
                        </template>
                    </p>

                    <div class="flex items-center gap-1">
                        <Button variant="outline" size="sm" :disabled="!pagination.links[0]?.url" @click.prevent="goTo(pagination.links[0]?.url ?? null)">Previous</Button>
                        <template v-for="link in pageLinks" :key="link.label">
                            <Button size="sm" :variant="link.active ? 'default' : 'outline'" :disabled="!link.url" @click.prevent="goTo(link.url)" v-html="link.label" />
                        </template>
                        <Button variant="outline" size="sm" :disabled="!pagination.links[pagination.links.length - 1]?.url" @click.prevent="goTo(pagination.links[pagination.links.length - 1]?.url ?? null)">Next</Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <Dialog v-model:open="detailOpen">
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Detail Log Aktivitas</DialogTitle>
                    <DialogDescription>Informasi lengkap aktivitas dan perubahan yang tercatat.</DialogDescription>
                </DialogHeader>
                <div class="space-y-4 p-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">User</p>
                            <p class="text-sm text-foreground">{{ selectedLog?.user?.name ?? 'System' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">Modul</p>
                            <p class="text-sm text-foreground">{{ selectedLog?.module }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">Aksi</p>
                            <p class="text-sm text-foreground">{{ selectedLog?.action }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">Waktu</p>
                            <p class="text-sm text-foreground">{{ selectedLog ? formatLogDate(selectedLog.created_at) : '-' }}</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">IP address</p>
                            <p class="text-sm text-foreground">{{ selectedLog?.ip_address ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">User agent</p>
                            <p class="text-sm text-foreground break-words">{{ selectedLog?.user_agent ?? '-' }}</p>
                        </div>
                    </div>

                    <div v-if="selectedLog?.action === 'update' && selectedLog.old_values && selectedLog.new_values" class="overflow-x-auto rounded-xl border border-border bg-muted p-4">
                        <p class="text-sm font-semibold text-foreground mb-3">Perbandingan perubahan</p>
                        <table class="min-w-full divide-y divide-border text-sm">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Field</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Sebelum</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Sesudah</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-for="key in Object.keys(selectedLog.old_values)" :key="key">
                                    <td class="px-3 py-2 font-medium text-foreground">{{ key }}</td>
                                    <td class="px-3 py-2 text-red-700">{{ formatJsonValue(selectedLog.old_values?.[key]) }}</td>
                                    <td class="px-3 py-2 text-emerald-700">{{ formatJsonValue(selectedLog.new_values?.[key]) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else-if="selectedLog?.action === 'create' && selectedLog.new_values" class="rounded-xl border border-border bg-muted p-4">
                        <p class="text-sm font-semibold text-foreground mb-3">Data baru</p>
                        <pre class="whitespace-pre-wrap break-words text-sm text-foreground">{{ JSON.stringify(selectedLog.new_values, null, 2) }}</pre>
                    </div>

                    <div v-else-if="selectedLog?.action === 'delete' && selectedLog.old_values" class="rounded-xl border border-border bg-muted p-4">
                        <p class="text-sm font-semibold text-foreground mb-3">Data sebelum dihapus</p>
                        <pre class="whitespace-pre-wrap break-words text-sm text-foreground">{{ JSON.stringify(selectedLog.old_values, null, 2) }}</pre>
                    </div>

                    <div v-else-if="selectedLog && ['login', 'logout'].includes(selectedLog.action)">
                        <p class="text-sm text-muted-foreground">Aktivitas autentikasi tidak memiliki nilai data tambahan.</p>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t border-border px-4 py-4">
                    <Button variant="outline" @click="closeDetails">Tutup</Button>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
