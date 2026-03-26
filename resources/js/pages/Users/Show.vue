<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as usersIndex } from '@/routes/users'
import type { BreadcrumbItem } from '@/types'

type User = {
    id: number
    name: string
    email: string
    created_at: string
    updated_at: string
}

const page = usePage()
const user = computed(() => page.props.user as User)

const breadcrumbs = computed((): BreadcrumbItem[] => [
    {
        title: 'Users',
        href: usersIndex(),
    },
    {
        title: user.value?.name || 'User',
        href: '#',
    },
])
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`User ${user?.name || 'Details'}`" />

        <div class="flex flex-col gap-4 p-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">User Details</h1>
                    <p class="text-sm text-muted-foreground">View user information.</p>
                </div>

                <div class="flex gap-2">
                    <Link :href="usersIndex()"
                        class="inline-flex items-center justify-center rounded-md bg-secondary px-4 py-2 text-sm font-medium text-secondary-foreground shadow-sm hover:bg-secondary/90">
                        Back to Users
                    </Link>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border p-6 bg-card">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
                    <div class="flex items-center gap-4 rounded-lg border border-border bg-muted p-5">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-primary text-lg font-semibold text-primary-foreground">
                            {{ user.name?.charAt(0) || 'U' }}
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-foreground">{{ user.name }}</h2>
                            <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                        </div>
                    </div>

                    <div class="grid w-full grid-cols-1 gap-4 sm:grid-cols-2 lg:flex-1">
                        <div class="rounded-lg border border-border bg-muted p-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">ID</dt>
                            <dd class="mt-1 text-sm font-medium text-foreground">{{ user.id }}</dd>
                        </div>
                        <div class="rounded-lg border border-border bg-muted p-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Created</dt>
                            <dd class="mt-1 text-sm font-medium text-foreground">{{ user?.created_at ? new Date(user.created_at.replace(' ', 'T')).toLocaleString() : 'N/A' }}</dd>
                        </div>
                        <div class="rounded-lg border border-border bg-muted p-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Updated</dt>
                            <dd class="mt-1 text-sm font-medium text-foreground">{{ user?.updated_at ? new Date(user.updated_at.replace(' ', 'T')).toLocaleString() : 'N/A' }}</dd>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>