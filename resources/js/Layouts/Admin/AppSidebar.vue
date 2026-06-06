<script setup>
import { computed } from "vue";
import { Link, usePage } from '@inertiajs/vue3'
import { useSidebar } from "@/Composables/useSidebar";

// Icons
import GridIcon from "@/Icons/GridIcon.vue";
import ChevronDownIcon from "@/Icons/ChevronDownIcon.vue";
import SettingsIcon from "@/Icons/SettingsIcon.vue";
import AccessIcon from "@/Icons/AccessIcon.vue";
import HorizontalDots from "@/Icons/HorizontalDots.vue";
import MoneyIcon from "@/Icons/MoneyIcon.vue";
import EmployeeIcon from "@/Icons/EmployeeIcon.vue";
import MembersIcon from "@/Icons/MembersIcon.vue";
import SavingsIcon from "@/Icons/SavingsIcon.vue";
import PersonAlertIcon from "@/Icons/PersonAlertIcon.vue";
import FinanceIcon from "@/Icons/FinanceIcon.vue";
import AccountIcon from "@/Icons/AccountIcon.vue";
import NotificationsIcon from "@/Icons/NotificationsIcon.vue";

const page = usePage()

const { isExpanded, isMobileOpen, isHovered, openSubmenu } = useSidebar();

const isItemVisible = (item) => {
    if (!item.exclude) return true
    return user.value?.role?.name !== item.exclude
}

const isSubItemVisible = (subItem) => {
    if (!subItem?.exclude) return true
    return user.value?.role?.name !== subItem.exclude
}

const user = computed(() => {
    return page.props.auth?.user || null
})

const menuGroups = [
    {
        title: "Menu",
        items: [
            {
                icon: GridIcon,
                name: "Dashboard",
                path: "/admin/dashboard",
            },
            {
                icon: MembersIcon,
                name: "Keanggotaan",
                path: "/admin/users/list",
                permission: "view_anggota"
            },
            {
                icon: MembersIcon,
                name: "Alokasi Penanggung Jawab",
                path: "/admin/users/allocation",
                permission: "edit_anggota"
            },
            {
                icon: PersonAlertIcon,
                name: "Pengunduran Diri",
                path: "/admin/resignations/list",
                permission: "view_anggota"
            },
            {
                icon: EmployeeIcon,
                name: "Pengurus",
                path: "/admin/list",
                permission: "view_pengurus"
            },
            {
                icon: AccountIcon,
                name: "Pengelolaan Akun",
                path: "/admin/accounts/list",
                permission: "view_kas"
            },
            {
                icon: FinanceIcon,
                name: "Pengelolaan Kas",
                path: "/admin",
                permission: "view_kas"
            },
            {
                icon: NotificationsIcon,
                name: "Monitoring Notifikasi",
                path: "/admin/notifications",
                permission: "view_notifikasi"
            },
            {
                icon: AccessIcon,
                name: "Peran dan Akses",
                path: "/admin/roles",
                permission: "view_peran_akses"
            },
        ],
    },
    {
        title: "Produk",
        items: [
            {
                icon: SavingsIcon,
                name: "Simpanan",
                path: "/admin/savings/list",
                permission: "view_simpanan"
            },
            {
                icon: MoneyIcon,
                name: "Pembiayaan Murabahah",
                path: "/admin/financings",
                permission: "view_murabahah"
            }
        ],
    },
    {
        title: "Lainnya",
        items: [
            {
                icon: SettingsIcon,
                name: "Pengaturan Umum",
                path: "/admin/settings",
                permission: "view_pengaturan"
            }
        ],
        permission: "view_pengaturan"
    }
];

// Menggunakan Inertia untuk check active route
const isActive = (path) => {
    const currentPath = page.url.split('?')[0];

    return currentPath === path || currentPath.startsWith(path + '/');
};

const toggleSubmenu = (groupIndex, itemIndex) => {
    const key = `${groupIndex}-${itemIndex}`;
    openSubmenu.value = openSubmenu.value === key ? null : key;
};

const isAnySubmenuRouteActive = computed(() => {
    return menuGroups.some((group) =>
        group.items.some(
            (item) =>
                item.subItems && item.subItems.some((subItem) => isActive(subItem.path))
        )
    );
});

const isSubmenuOpen = (groupIndex, itemIndex) => {
    const key = `${groupIndex}-${itemIndex}`;
    return (
        openSubmenu.value === key ||
        (isAnySubmenuRouteActive.value &&
            menuGroups[groupIndex].items[itemIndex].subItems?.some((subItem) =>
                isActive(subItem.path)
            ))
    );
};

const startTransition = (el) => {
    el.style.height = "auto";
    const height = el.scrollHeight;
    el.style.height = "0px";
    el.offsetHeight; // force reflow
    el.style.height = height + "px";
};

const endTransition = (el) => {
    el.style.height = "";
};

const hasPermission = (permission) => {
    if (!permission) return true;
    return page.props.auth?.can?.[permission];
};

const isGroupVisible = (group) => {
    if (group.permission && !hasPermission(group.permission)) {
        return false;
    }

    return group.items.some(item => hasPermission(item.permission) && isItemVisible(item));
};

</script>

<template>
    <aside :class="[
        'fixed mt-16 flex flex-col lg:mt-0 top-0 px-5 left-0 bg-white dark:bg-gray-900 dark:border-gray-800 text-gray-900 h-screen transition-all duration-300 ease-in-out z-99999 border-r border-gray-200',
        {
            'lg:w-72.5': isExpanded || isMobileOpen || isHovered,
            'lg:w-22.5': !isExpanded && !isHovered,
            'translate-x-0 w-72.5': isMobileOpen,
            '-translate-x-full': !isMobileOpen,
            'lg:translate-x-0': true,
        },
    ]" @mouseenter="!isExpanded && (isHovered = true)" @mouseleave="isHovered = false">
        <div :class="[
            'py-8 flex',
            !isExpanded && !isHovered ? 'lg:justify-center' : 'justify-start',
        ]">
            <Link href="/">
                <div v-if="isExpanded || isHovered || isMobileOpen"
                    class="flex items-center space-x-3 rtl:space-x-reverse">
                    <img class="max-h-12" src="/public/images/logo/logo-icon.svg" alt="Logo">
                    <span
                        class="self-center text-2xl text-brand-950 font-semibold whitespace-nowrap dark:text-white">KS<span
                            class="text-accent">B</span> Admin</span>
                </div>
                <img v-else src="/public/images/logo/logo-icon.svg" alt="Logo" width="32" height="32" />
            </Link>
        </div>
        <div class="flex flex-col overflow-y-auto duration-300 ease-linear no-scrollbar">
            <nav class="mb-6">
                <div class="flex flex-col gap-4">
                    <template v-for="(menuGroup, groupIndex) in menuGroups" :key="groupIndex">
                        <div v-if="isGroupVisible(menuGroup)">
                            <h2 :class="[
                                'mb-4 text-xs uppercase flex leading-5 text-gray-400',
                                !isExpanded && !isHovered
                                    ? 'lg:justify-center'
                                    : 'justify-start',
                            ]">
                                <template v-if="isExpanded || isHovered || isMobileOpen">
                                    {{ menuGroup.title }}
                                </template>
                                <HorizontalDots v-else />
                            </h2>
                            <ul class="flex flex-col gap-4">
                                <template v-for="(item, index) in menuGroup.items" :key="item.name">
                                    <li v-if="hasPermission(item.permission) && isItemVisible(item)">
                                        <button v-if="item.subItems && isItemVisible(item)"
                                            @click="toggleSubmenu(groupIndex, index)" :class="[
                                                'menu-item group w-full',
                                                {
                                                    'menu-item-active': isSubmenuOpen(groupIndex, index),
                                                    'menu-item-inactive': !isSubmenuOpen(groupIndex, index),
                                                },
                                                !isExpanded && !isHovered
                                                    ? 'lg:justify-center'
                                                    : 'lg:justify-start',
                                            ]">
                                            <span :class="[
                                                isSubmenuOpen(groupIndex, index)
                                                    ? 'menu-item-icon-active'
                                                    : 'menu-item-icon-inactive',
                                            ]">
                                                <component :is="item.icon" />
                                            </span>
                                            <span v-if="isExpanded || isHovered || isMobileOpen"
                                                class="menu-item-text">{{
                                                    item.name }}</span>
                                            <ChevronDownIcon v-if="isExpanded || isHovered || isMobileOpen" :class="[
                                                'ml-auto w-5 h-5 transition-transform duration-200',
                                                {
                                                    'rotate-180 text-brand-500': isSubmenuOpen(
                                                        groupIndex,
                                                        index
                                                    ),
                                                },
                                            ]" />
                                        </button>
                                        <Link
                                            v-else-if="item.path && isItemVisible(item) && hasPermission(item.permission)"
                                            :href="item.path" :class="[
                                                'menu-item group',
                                                {
                                                    'menu-item-active': isActive(item.path),
                                                    'menu-item-inactive': !isActive(item.path),
                                                },
                                            ]">
                                            <span :class="[
                                                isActive(item.path)
                                                    ? 'menu-item-icon-active'
                                                    : 'menu-item-icon-inactive',
                                            ]">
                                                <component :is="item.icon" />
                                            </span>
                                            <span v-if="isExpanded || isHovered || isMobileOpen"
                                                class="menu-item-text">{{
                                                    item.name }}</span>
                                        </Link>
                                        <transition @enter="startTransition" @after-enter="endTransition"
                                            @before-leave="startTransition" @after-leave="endTransition">
                                            <div v-show="isSubmenuOpen(groupIndex, index) &&
                                                (isExpanded || isHovered || isMobileOpen)
                                                ">
                                                <ul class="mt-2 space-y-1 ml-9">
                                                    <template v-for="subItem in item.subItems" :key="subItem.name">
                                                        <li
                                                            v-if="isSubItemVisible(subItem) && hasPermission(subItem.permission)">
                                                            <Link :href="subItem.path" :class="[
                                                                'menu-dropdown-item',
                                                                {
                                                                    'menu-dropdown-item-active': isActive(
                                                                        subItem.path
                                                                    ),
                                                                    'menu-dropdown-item-inactive': !isActive(
                                                                        subItem.path
                                                                    ),
                                                                },
                                                            ]">
                                                                {{ subItem.name }}
                                                                <span class="flex items-center gap-1 ml-auto">
                                                                    <span v-if="subItem.new" :class="[
                                                                        'menu-dropdown-badge',
                                                                        {
                                                                            'menu-dropdown-badge-active': isActive(
                                                                                subItem.path
                                                                            ),
                                                                            'menu-dropdown-badge-inactive': !isActive(
                                                                                subItem.path
                                                                            ),
                                                                        },
                                                                    ]">
                                                                        new
                                                                    </span>
                                                                    <span v-if="subItem.pro" :class="[
                                                                        'menu-dropdown-badge',
                                                                        {
                                                                            'menu-dropdown-badge-active': isActive(
                                                                                subItem.path
                                                                            ),
                                                                            'menu-dropdown-badge-inactive': !isActive(
                                                                                subItem.path
                                                                            ),
                                                                        },
                                                                    ]">
                                                                        pro
                                                                    </span>
                                                                </span>
                                                            </Link>
                                                        </li>
                                                    </template>
                                                </ul>
                                            </div>
                                        </transition>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>
                </div>
            </nav>
        </div>
    </aside>
</template>
