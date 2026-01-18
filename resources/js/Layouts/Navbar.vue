<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import UserIcon from '../Icons/UserIcon.vue'
import ThemeToggler from '../Components/ThemeToggler.vue'
import Logo from '@/Components/Logo.vue'

const isMenuOpen = ref(true)
const isUserDropdownOpen = ref(false)
const page = usePage()
const isActive = (url) => {
    return page.url === url
}

// Get user from auth data
const user = computed(() => {
    return page.props.auth?.user || null
})

// Get photo URL from profile_picture
const photoUrl = computed(() => {
    if (user.value?.profile_picture) {
        return `/storage/${user.value.profile_picture}`
    }
    return null
})

// Get CSRF token
const csrfToken = computed(() => {
    return page.props.csrf_token || ''
})

const toggleMenu = () => {
    isMenuOpen.value = !isMenuOpen.value
}

const toggleUserDropdown = () => {
    isUserDropdownOpen.value = !isUserDropdownOpen.value
}

const menuItems = [
    {
        name: "Beranda",
        path: "/"
    },
    {
        name: "Produk",
        path: "/product"
    },
    {
        name: "Tentang Kami",
        path: "/about"
    },
    {
        name: "Bantuan",
        path: "/help"
    }
]
</script>

<template>
    <nav class="sticky nav-bar font-manrope border-gray-200 px-10">
        <div class="flex flex-wrap items-center justify-between mx-auto px-4 py-6">
            <!-- Logo -->
            <Logo />

            <!-- Right Section -->
            <div class="flex md:order-2 space-x-3 md:space-x-0 rtl:space-x-reverse items-center gap-4">
                <ThemeToggler />
                <!-- Guest Auth Section -->
                <template v-if="!user">
                    <div class="flex items-center gap-4 opacity-0 lg:opacity-100">
                        <Link href="/auth/login"
                            class="inline-flex items-center justify-center rounded-xl bg-white px-8 py-2 font-body font-semibold text-orange-500 shadow-lg transition-colors hover:text-orange-600 hover:bg-gray-100">
                            Masuk
                        </Link>

                        <Link href="/auth/register"
                            class="inline-flex items-center justify-center rounded-xl bg-orange-500 px-8 py-2 text-body font-semibold text-white hover:bg-orange-600 transition-colors shadow-lg">
                            Daftar
                        </Link>
                    </div>
                </template>

                <!-- Authenticated User Section -->
                <template v-else>
                    <!-- User Avatar & Dropdown -->
                    <Link :href="user.role?.name === 'Anggota' ? '/dashboard' : '/admin/dashboard'"
                        class="relative flex items-center justify-center text-dark-text transition-colors bg-transparent border border-gray-200 rounded-full hover:text-dark-900 h-11 w-11 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white">
                        <span class="icon-[material-symbols-light--home-outline-rounded]"
                            style="width: 24px; height: 24px;"></span>
                    </Link>
                    <div class="relative">
                        <button @click="toggleUserDropdown" class="flex items-center">
                            <div v-if="photoUrl" class="w-10 h-10 rounded-lg overflow-hidden cursor-pointer">
                                <img class="w-full h-full object-cover" :src="photoUrl" :alt="user.name">
                            </div>
                            <div v-else
                                class="w-10 h-10 rounded-lg bg-white flex items-center justify-center text-gray-500 cursor-pointer">
                                <UserIcon />
                            </div>
                        </button>

                        <!-- Dropdown Menu -->
                        <div v-if="isUserDropdownOpen"
                            class="absolute right-0 z-10 mt-2 w-44 divide-y divide-gray-100 rounded-lg shadow bg-white dark:bg-gray-800 dark:divide-gray-600">
                            <Link href="/user/profile" class="block">
                                <div
                                    class="px-4 py-3 text-sm text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <div>{{ user.name }}</div>
                                    <div class="font-medium truncate">{{ user.email }}</div>
                                </div>
                            </Link>
                            <ul class="py-2 text-sm text-gray-700 dark:text-gray-200">
                                <li>
                                    <Link href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">
                                        Settings
                                    </Link>
                                </li>
                            </ul>
                            <div class="py-1">
                                <form method="post" action="/auth/logout" style="display: inline;">
                                    <input type="hidden" name="_token" :value="csrfToken" />
                                    <button type="submit"
                                        class="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200">
                                        Keluar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Mobile Menu Button -->
                <button @click="toggleMenu" type="button"
                    class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700">
                    <span class="sr-only">Open main menu</span>
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 17 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M1 1h15M1 7h15M1 13h15" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Menu -->
            <div v-if="isMenuOpen" class="w-full md:flex md:w-auto md:order-1">
                <ul
                    class="flex flex-col gap-2 p-4 md:p-0 mt-4 border border-gray-100 rounded-lg md:space-x-8 rtl:space-x-reverse md:flex-row md:mt-0 md:border-0 dark:border-gray-700">
                    <li v-for="menu in menuItems">
                        <Link :href="menu.path"
                            :class="isActive(menu.path) ? 'block pb-2! px-3 text-gray-900 hover:bg-gray-100 font-semibold md:hover:bg-transparent md:p-0 dark:text-white border-b-accent border-b-2' : 'block pb-2! px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:p-0 dark:text-white'">
                            {{ menu.name }}
                        </Link>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</template>
