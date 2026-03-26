<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';
// static wallpaper asset
import wallpaper from '@/components/ui/images/Wallpaper.jpg';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>

    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="flex min-h-screen flex-col items-center p-6 text-[#1b1b18] lg:justify-center lg:p-8"
        :style="{ backgroundImage: `url(${wallpaper})`, backgroundSize: 'cover', backgroundPosition: 'center' }">
        <header class="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl">
            <nav class="flex items-center justify-end gap-2">
                <Link v-if="$page.props.auth.user" :href="dashboard()"
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]">
                    Dashboard
                </Link>
                <template v-else>
                    <Link :href="login()"
                        class="inline-block rounded-sm border border-white px-5 py-1.5 text-sm leading-normal text-white hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]">
                        Log in
                    </Link>
                    <!-- <Link v-if="canRegister" :href="register()"
                        class="inline-block rounded-sm border border-white px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]">
                        Register
                    </Link> -->
                </template>
            </nav>
        </header>
        <div
            class="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
            <div class="w-full p-6 text-center lg:max-w-2xl bg-transparent">
                <h1 class="mb-4 text-3xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]">
                    Welcome to KOI - Umeda
                </h1>
                <p class="text-sm leading-normal text-[#1b1b18] dark:text-[#EDEDEC]">
                    Please log in to access the dashboard and manage your data.
                </p>
            </div>
        </div>
        <div class="hidden h-14.5 lg:block"></div>
    </div>
</template>
