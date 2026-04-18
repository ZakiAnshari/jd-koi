<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayoutAdmin from '@/layouts/AppLayoutAdmin.vue';
import AppLayoutCustomer from '@/layouts/AppLayoutCustomer.vue';
import { Toaster } from '@/components/ui/sonner'
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const authUser = computed(() => page.props.auth?.user);
const isAdmin = computed(() => authUser.value?.role?.name === 'admin');
</script>

<template>
    <AppLayoutAdmin v-if="isAdmin" :breadcrumbs="breadcrumbs">
        <slot />
        <Toaster position="top-right" :richColors="true" />
    </AppLayoutAdmin>
    <AppLayoutCustomer v-else :breadcrumbs="breadcrumbs">
        <slot />
        <Toaster position="top-right" :richColors="true" />
    </AppLayoutCustomer>
</template>
