<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen, FolderGit2, LayoutGrid, User, BarChart3, Database, Package, ShoppingCart } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as usersIndex } from '@/routes/users';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Master Data',
        icon: Database,
        items: [
            {
                title: 'Breeder',
                href: '/master-data/breeder',
            },
            {
                title: 'Data Koi',
                href: '/master-data/koi',
            },
        ],
    },
    {
        title: 'Manajemen Stok',
        href: '/inventory',
        icon: Package,
    },
    {
        title: 'Transaksi Penjualan',
        href: '/transactions',
        icon: ShoppingCart,
    },
    {
        title: 'User',
        href: usersIndex(),
        icon: User,
    },
    // Sales menu hidden per request
    // {
    //     title: 'Sales',
    //     href: '/sales',
    //     icon: BarChart3,
    // },

];

// define footer navigation items (empty for now or add actual links)
const footerNavItems: NavItem[] = [];


</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
