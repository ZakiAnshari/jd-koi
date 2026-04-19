<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    SidebarMenuBadge,
    useSidebar,
} from '@/components/ui/sidebar';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { ChevronRight } from 'lucide-vue-next';
import type { NavItem } from '@/types';

const props = defineProps<{
    items: NavItem[];
}>();

const page = usePage();
const sidebar = useSidebar();
const currentUrl = computed(() => page.url);

const isActive = (href?: string) => {
    if (!href) return false;
    const path = currentUrl.value.split('?')[0];
    return path === href || path.startsWith(href + '/');
};

const openMenus = ref<Record<string, boolean>>({});

props.items.forEach((item) => {
    if (item.items) {
        openMenus.value[item.title] = item.items.some((sub) => isActive(sub.href));
    }
});

// Prevent sidebar collapse on navigation
onMounted(() => {
    router.on('navigate', () => {
        // Keep sidebar from collapsing during navigation
        if (sidebar?.state?.value === 'collapsed') {
            sidebar.toggleSidebar();
        }
    });
});
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <template v-for="item in props.items" :key="item.title">
                <SidebarMenuItem v-if="!item.items || item.items.length === 0">
                    <SidebarMenuButton as-child :is-active="item.href ? isActive(item.href) : false" :tooltip="item.title">
                        <Link :href="item.href || '#'">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                            <SidebarMenuBadge v-if="item.badgeCount && item.badgeCount > 0">{{ item.badgeCount }}</SidebarMenuBadge>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>

                <Collapsible v-else v-model:open="openMenus[item.title]" as-child>
                    <SidebarMenuItem>
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton class="w-full" :tooltip="item.title">
                                <component :is="item.icon" />
                                <span class="flex-1 text-left">{{ item.title }}</span>
                                <ChevronRight
                                    class="h-4 w-4 transition-transform duration-200"
                                    :class="{ 'rotate-90': openMenus[item.title] }"
                                />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem v-for="subitem in item.items" :key="subitem.title">
                                    <SidebarMenuSubButton as-child :is-active="subitem.href ? isActive(subitem.href) : false">
                                        <Link :href="subitem.href || '#'">
                                            <component v-if="subitem.icon" :is="subitem.icon" />
                                            <span>{{ subitem.title }}</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </SidebarMenuItem>
                </Collapsible>
            </template>
        </SidebarMenu>
    </SidebarGroup>

    <SidebarGroup class="px-2 py-0">
        <!-- <SidebarGroupLabel>Management</SidebarGroupLabel> -->
        <!-- add more menu items here if needed -->
        <SidebarMenu>
            <!-- empty for now, populate with management links later -->
        </SidebarMenu>
    </SidebarGroup>
</template>
