<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import InputError from '@/components/InputError.vue'
import type { BreadcrumbItem } from '@/types'
import { useSwal } from '@/composables/useSwal'

type User = {
    id: number
    name: string
    email: string
    no_telepon: string | null
    alamat: string | null
}

const props = defineProps<{ user: User }>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/customer/dashboard' },
    { title: 'Profil Saya', href: '/customer/profile' },
]

// ─── Form Data Diri ───────────────────────────────────────
const profileForm = useForm({
    name:       props.user.name,
    email:      props.user.email,
    no_telepon: props.user.no_telepon ?? '',
    alamat:     props.user.alamat ?? '',
})

function submitProfile() {
    profileForm.put('/customer/profile', {
        preserveScroll: true,
        onSuccess: () => {
            useSwal().fire({ icon: 'success', title: 'Berhasil!', text: 'Profil berhasil diperbarui.', timer: 2000, showConfirmButton: false })
        },
        onError: () => {
            useSwal().fire({ icon: 'error', title: 'Gagal!', text: 'Periksa kembali isian.', timer: 2500, showConfirmButton: false })
        },
    })
}

// ─── Form Password ────────────────────────────────────────
const passwordForm = useForm({
    current_password:      '',
    password:              '',
    password_confirmation: '',
})

function submitPassword() {
    passwordForm.put('/customer/profile/password', {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset()
            useSwal().fire({ icon: 'success', title: 'Berhasil!', text: 'Password berhasil diperbarui.', timer: 2000, showConfirmButton: false })
        },
        onError: () => {
            useSwal().fire({ icon: 'error', title: 'Gagal!', text: 'Periksa kembali isian.', timer: 2500, showConfirmButton: false })
        },
    })
}
</script>

<template>
    <Head title="Profil Saya" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6 max-w-2xl">

            <div>
                <h1 class="text-2xl font-semibold">Profil Saya</h1>
                <p class="text-sm text-muted-foreground">Kelola informasi akun Anda.</p>
            </div>

            <!-- ── Data Diri ── -->
            <div class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6 space-y-4">
                <h2 class="text-base font-semibold">Data Diri</h2>

                <div class="grid gap-1.5">
                    <Label>Nama Lengkap <span class="text-red-500">*</span></Label>
                    <Input v-model="profileForm.name" placeholder="Nama lengkap" />
                    <InputError :message="profileForm.errors.name" />
                </div>

                <div class="grid gap-1.5">
                    <Label>Email <span class="text-red-500">*</span></Label>
                    <Input v-model="profileForm.email" type="email" placeholder="email@example.com" />
                    <InputError :message="profileForm.errors.email" />
                </div>

                <div class="grid gap-1.5">
                    <Label>No. Telepon</Label>
                    <Input v-model="profileForm.no_telepon" placeholder="08xxxxxxxxxx" />
                    <InputError :message="profileForm.errors.no_telepon" />
                </div>

                <div class="grid gap-1.5">
                    <Label>Alamat</Label>
                    <textarea
                        v-model="profileForm.alamat"
                        rows="3"
                        placeholder="Alamat lengkap..."
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                    />
                    <InputError :message="profileForm.errors.alamat" />
                </div>

                <div class="pt-2">
                    <Button @click="submitProfile" :disabled="profileForm.processing">
                        {{ profileForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                    </Button>
                </div>
            </div>

            <!-- ── Ganti Password ── -->
            <div class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border bg-background p-6 space-y-4">
                <h2 class="text-base font-semibold">Ganti Password</h2>

                <div class="grid gap-1.5">
                    <Label>Password Saat Ini <span class="text-red-500">*</span></Label>
                    <Input v-model="passwordForm.current_password" type="password" placeholder="••••••••" autocomplete="current-password" />
                    <InputError :message="passwordForm.errors.current_password" />
                </div>

                <div class="grid gap-1.5">
                    <Label>Password Baru <span class="text-red-500">*</span></Label>
                    <Input v-model="passwordForm.password" type="password" placeholder="••••••••" autocomplete="new-password" />
                    <InputError :message="passwordForm.errors.password" />
                </div>

                <div class="grid gap-1.5">
                    <Label>Konfirmasi Password Baru <span class="text-red-500">*</span></Label>
                    <Input v-model="passwordForm.password_confirmation" type="password" placeholder="••••••••" autocomplete="new-password" />
                    <InputError :message="passwordForm.errors.password_confirmation" />
                </div>

                <div class="pt-2">
                    <Button @click="submitPassword" :disabled="passwordForm.processing">
                        {{ passwordForm.processing ? 'Menyimpan...' : 'Ganti Password' }}
                    </Button>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
