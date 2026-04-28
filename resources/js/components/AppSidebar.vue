<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, FolderGit2, LayoutGrid, User, Users, BarChart3, Package, RotateCcw, ShieldCheck, Fish, Grid, ClipboardList, UserCircle } from 'lucide-vue-next';
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

const page = usePage();
const authUser = computed(() => page.props.auth?.user);
const isAdmin = computed(() => authUser.value?.role_id === 1);
const activityLogTodayCount = computed(() => Number(page.props.activityLogTodayCount ?? 0));

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
            title: 'Breeder',
            href: '/master-data/breeder',
            icon: FolderGit2,
        });

        items.push({
            title: 'Jenis Koi',
            href: '/master-data/jenis-koi',
            icon: Fish,
        });

        items.push({
            title: 'Data Koi',
            href: '/master-data/koi',
            icon: Grid,
        });


        // items.push({
        //     title: 'Stok Koi',
        //     href: '/inventory',
        //     icon: Database,
        // });

        items.push({
            title: 'Rekap Transaksi',
            href: '/rekap-transaksi',
            icon: ClipboardList,
        });

        items.push({
            title: 'Log Aktivitas',
            href: '/log-aktivitas',
            icon: RotateCcw,
            badgeCount: activityLogTodayCount.value,
        });

    } else {
        items.push({
            title: 'Rekap Transaksi',
            href: '/rekap-transaksi',
            icon: ClipboardList,
        });

        items.push({
            title: 'Profil Saya',
            href: '/customer/profile',
            icon: UserCircle,
        });
    }

    if (isAdmin.value) {
        items.push({
            title: 'Hak Akses',
            icon: ShieldCheck,
            items: [
                {
                    title: 'Admin',
                    href: '/admin/hak-akses/admin',
                    icon: User,
                },
                {
                    title: 'Customer',
                    href: '/admin/hak-akses/customer',
                    icon: Users,
                },
            ],
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
