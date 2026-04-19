<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, FolderGit2, LayoutGrid, User, Users, BarChart3, Database, Package, ShoppingCart, RotateCcw, FileBarChart, ShoppingBag, Bell } from 'lucide-vue-next';
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
import type { NavItem } from '@/types';
import { computed } from 'vue';
import { dashboard } from '@/routes';
import { index as customerKatalogIndex } from '@/routes/customer/katalog';
import { index as customerPesananIndex } from '@/routes/customer/pesanan';
import { index as customerNotifikasiIndex } from '@/routes/customer/notifikasi';
import { index as usersIndex } from '@/routes/users';

const adminKatalogKoiIndex = () => ({ url: '/admin/katalog-koi' });

const page = usePage();
const authUser = computed(() => page.props.auth?.user);
const isAdmin = computed(() => authUser.value?.role_id === 1);
const activityLogTodayCount = computed(() => Number(page.props.activityLogTodayCount ?? 0));
const unreadNotifications = computed(() => Number((page.props as any).unreadNotifications ?? 0));
const pendingPesananCount = computed(() => Number((page.props as any).pendingPesananCount ?? 0));

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: isAdmin.value ? dashboard().url : '/customer/dashboard',
            icon: LayoutGrid,
        },
    ];

    if (isAdmin.value) {
        items.push({
            title: 'Master Data',
            icon: Database,
            items: [
                {
                    title: 'Breeder',
                    href: '/master-data/breeder',
                    icon: FolderGit2,
                },
                {
                    title: 'Data Koi',
                    href: '/master-data/koi',
                    icon: BookOpen,
                },
                {
                    title: 'Customer',
                    href: '/master-data/customer',
                    icon: Users,
                },
            ],
        });

        items.push({
            title: 'Transaksi',
            icon: BarChart3,
            items: [
                {
                    title: 'Pembelian',
                    href: '/pembelian',
                    icon: Package,
                },

                {
                    title: 'Katalog Koi',
                    href: adminKatalogKoiIndex().url,
                    icon: ShoppingBag,
                },
                {
                    title: 'Pesanan',
                    href: '/admin/pesanan',
                    icon: ShoppingCart,
                    badgeCount: pendingPesananCount.value,
                },
            ],
        });

        items.push({
            title: 'Stok Koi',
            href: '/inventory',
            icon: Database,
        });

        items.push({
            title: 'Log Aktivitas',
            href: '/log-aktivitas',
            icon: RotateCcw,
            badgeCount: activityLogTodayCount.value,
        });

        items.push({
            title: 'Laporan',
            icon: FileBarChart,
            items: [
                {
                    title: 'Penjualan',
                    href: '/reports/sales',
                    icon: ShoppingCart,
                },
                {
                    title: 'Pembelian',
                    href: '/reports/purchases',
                    icon: Package,
                },
                {
                    title: 'Laba Rugi',
                    href: '/reports/profit-loss',
                    icon: BarChart3,
                },
                {
                    title: 'Stok Koi',
                    href: '/reports/stock',
                    icon: Database,
                },
            ],
        });
    } else {
        items.push({
            title: 'Katalog',
            href: customerKatalogIndex().url,
            icon: ShoppingBag,
        });
        items.push({
            title: 'Pesanan Saya',
            href: customerPesananIndex().url,
            icon: ShoppingCart,
        });
        items.push({
            title: 'Notifikasi',
            href: customerNotifikasiIndex().url,
            icon: Bell,
            badgeCount: unreadNotifications.value,
        });
    }

    if (isAdmin.value) {
        items.push({
            title: 'User',
            href: usersIndex().url,
            icon: User,
        });
    }

    return items;
});

// define footer navigation items (empty for now or add actual links)
const footerNavItems: NavItem[] = [];


</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="isAdmin ? dashboard().url : '/customer/dashboard'">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="overflow-y-auto scrollbar-hide">
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

<style>
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>
