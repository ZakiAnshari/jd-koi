<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <!-- Item without submenu -->
                <template v-if="!item.items || item.items.length === 0">
                    <SidebarMenuButton as-child :is-active="item.href ? isCurrentUrl(item.href) : false" :tooltip="item.title">
                        <Link :href="item.href || '#'">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </template>

                <!-- Item with submenu -->
                <template v-else>
                    <SidebarMenuButton :tooltip="item.title">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </SidebarMenuButton>
                    <SidebarMenuSub>
                        <SidebarMenuSubItem v-for="subitem in item.items" :key="subitem.title">
                            <SidebarMenuSubButton as-child :is-active="subitem.href ? isCurrentUrl(subitem.href) : false">
                                <Link :href="subitem.href || '#'">
                                    <span>{{ subitem.title }}</span>
                                </Link>
                            </SidebarMenuSubButton>
                        </SidebarMenuSubItem>
                    </SidebarMenuSub>
                </template>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>

    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Management</SidebarGroupLabel>
        <!-- add more menu items here if needed -->
        <SidebarMenu>
            <!-- empty for now, populate with management links later -->
        </SidebarMenu>
    </SidebarGroup>
</template>
