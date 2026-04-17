<script setup>
import { ref, computed } from 'vue'
import { Link, usePage, useForm } from '@inertiajs/vue3'
import UserIcon from '../Icons/UserIcon.vue'
import ThemeToggler from '../Components/ThemeToggler.vue'
import Logo from '@/Components/Logo.vue'
import Swal from 'sweetalert2'
import { toast } from "vue3-toastify";
import ChevronDownIcon from '@/Icons/ChevronDownIcon.vue'

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
        path: "/products"
    },
    {
        name: "Tentang Kami",
        path: "/about"
    },
    {
        name: "Bantuan",
        path: "/faq"
    }
]

const form = useForm({})

const logout = () => {
    Swal.fire({
        title: 'Konfirmasi',
        text: 'Apakah Anda yakin ingin keluar?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, keluar',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#007943',
    }).then((result) => {
        if (result.isConfirmed) {
            form.post(('/auth/logout'), {
                onSuccess: () => {
                    toast("Sampai jumpa!", {
                        "type": "success",
                        "position": "bottom-right",
                        "transition": "slide",
                        "dangerouslyHTMLString": true
                    }).then(() => {
                        window.location.href = route('landing')
                    })
                },
                onError: () => {
                    toast("Gagal keluar.", {
                        "type": "error",
                        "position": "bottom-right",
                        "transition": "slide",
                        "dangerouslyHTMLString": true
                    })
                }
            })
        }
    })
}
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
                            class="inline-flex items-center justify-center rounded-xl bg-white px-8 py-2 font-body font-semibold text-brand-600 shadow-lg transition-colors hover:text-brand-700 hover:bg-gray-100">
                            Masuk
                        </Link>
                    </div>
                </template>

                <!-- Authenticated User Section -->
                <template v-else>
                    <!-- User Avatar & Dropdown -->
                    <Link :href="user.role?.name === 'Anggota' ? '/user/dashboard' : '/admin/dashboard'"
                        class="relative flex items-center justify-center text-dark-text transition-colors bg-transparent border border-gray-200 rounded-full hover:text-dark-900 h-11 w-11 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white">
                        <span class="icon-[material-symbols-light--home-outline-rounded]"
                            style="width: 24px; height: 24px;"></span>
                    </Link>
                    <div class="relative">
                        <button class="flex items-center text-gray-700 dark:text-gray-400"
                            @click.prevent="toggleUserDropdown">
                            <span v-if="photoUrl" class="mr-3 overflow-hidden rounded-full h-11 w-11">
                                <img :src="photoUrl" alt="User" />
                            </span>
                            <span v-else
                                class="w-11 h-11 mr-3 rounded-full border border-stroke bg-white flex items-center justify-center text-gray-500 cursor-pointer">
                                <UserIcon />
                            </span>
                            <div class="flex flex-col text-left">
                                <span class="block mr-1 font-medium text-theme-sm">{{ user.name }}</span>
                                <span class="mt-0.5 block text-theme-xs text-accent">
                                    {{ user.member_code }}
                                </span>
                            </div>

                            <ChevronDownIcon :class="{ 'rotate-180 transition-all duration-300': isUserDropdownOpen }" />
                        </button>

                        <!-- Dropdown Menu -->
                        <div v-if="isUserDropdownOpen"
                            class="absolute right-0 z-10 mt-2 w-44 divide-y divide-gray-100 rounded-lg hover:rounded-lg shadow bg-white dark:bg-gray-800 dark:divide-gray-600">
                            <div
                                class="px-4 py-3 text-sm cursor-default text-gray-900 dark:text-white dark:hover:bg-gray-600">
                                <div>{{ user.name }}</div>
                                <div class="font-medium truncate">{{ user.email }}</div>
                            </div>
                            <ul class="text-sm text-gray-700 dark:text-gray-200">
                                <li>
                                    <Link href="/user/profile"
                                        class="block px-4 py-4 hover:bg-gray-100 dark:hover:bg-gray-600">
                                        Profil
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/user/resign"
                                        class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">
                                        Pengunduran Diri
                                    </Link>
                                </li>
                            </ul>
                            <button type="button" @click="logout"
                                class="w-full text-left block px-4 py-4 text-sm text-gray-700 hover:bg-gray-100 hover:rounded-b-lg dark:hover:bg-gray-600 dark:text-gray-200">
                                Keluar
                            </button>
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
