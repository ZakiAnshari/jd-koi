<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import { useForm } from '@inertiajs/vue3'
import { useSwal } from '@/composables/useSwal'
import type { BreadcrumbItem } from '@/types'

type User = {
    id: number
    name: string
    email: string
    role_id: number
}

const page = usePage()
const user = page.props.user as User
const formUrl = (page.props.formUrl as string | undefined) ?? `/users/${user.id}`

const backUrl = user.role_id === 1
    ? '/admin/hak-akses/admin'
    : user.role_id === 2
        ? '/admin/hak-akses/customer'
        : '/users'

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: user.role_id === 1 ? 'Hak Akses Admin' : user.role_id === 2 ? 'Hak Akses Customer' : 'Users',
        href: backUrl,
    },
    {
        title: 'Edit',
        href: formUrl + '/edit',
    },
]

const form = useForm({
    name: user.name,
    email: user.email,
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.put(formUrl, {
        preserveScroll: true,
        onSuccess: () => {
            useSwal().fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data user berhasil diperbarui.',
                timer: 3000,
                showConfirmButton: false,
            })
            // Reset password fields only
            form.password = ''
            form.password_confirmation = ''
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
        <Head title="Edit user" />

        <div class="space-y-6 p-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Edit user</h1>
                    <p class="text-sm text-muted-foreground">Update user information.</p>
                </div>
                <Link
                    :href="backUrl"
                    class="inline-flex items-center justify-center rounded-md border border-neutral-200 bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted"
                >
                    Kembali
                </Link>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" placeholder="Full name" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input id="email" v-model="form.email" type="email" placeholder="Email address" />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">New password (leave blank to keep current)</Label>
                        <Input id="password" v-model="form.password" type="password" placeholder="New password" />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation">Confirm password</Label>
                        <Input id="password_confirmation" v-model="form.password_confirmation" type="password"
                            placeholder="Confirm new password" />
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing">Save</Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>