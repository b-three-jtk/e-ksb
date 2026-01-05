<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'

const isMenuOpen = ref(true)
const isUserDropdownOpen = ref(false)

const toggleMenu = () => {
    isMenuOpen.value = !isMenuOpen.value
}

const toggleUserDropdown = () => {
    isUserDropdownOpen.value = !isUserDropdownOpen.value
}
</script>

<template>
    <nav class="sticky nav-bar font-manrope border-gray-200 dark:bg-gray-900 px-10">
        <div class="flex flex-wrap items-center justify-between mx-auto px-4 py-6">
            <!-- Logo -->
            <a href="#" class="flex items-center space-x-3 rtl:space-x-reverse">
                <img class="max-h-12" src="/public/images/logo/logo-icon.svg" alt="Logo">
            </a>

            <!-- Right Section -->
            <div class="flex md:order-2 space-x-3 md:space-x-0 rtl:space-x-reverse items-center">
                <!-- Auth Section -->
                <div class="flex items-center gap-4">
                    <Link href="/auth/register" class="inline-flex items-center justify-center rounded-lg bg-orange-500 px-8 py-2 text-base font-semibold text-white hover:bg-orange-600 transition-colors shadow-lg">
                        Daftar
                    </Link>
                    
                    <Link href="/admin/dashboard" class="inline-block text-sm px-6 py-2.5 leading-none border rounded-lg hover:bg-gray-200 font-medium border-transparent">
                        Dashboard
                    </Link>

                    <!-- User Avatar & Dropdown -->
                    <div class="relative">
                        <button @click="toggleUserDropdown" class="flex items-center">
                            <img class="w-10 h-10 rounded-lg cursor-pointer object-cover"
                                src="/public/images/user/owner.jpg" alt="User avatar">
                        </button>

                        <!-- Dropdown Menu -->
                        <div v-if="isUserDropdownOpen"
                            class="absolute right-0 z-10 mt-2 w-44 divide-y divide-gray-100 rounded-lg shadow bg-white dark:bg-gray-700 dark:divide-gray-600">
                            <div class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                <div>User Name</div>
                                <div class="font-medium truncate">user@email.com</div>
                            </div>
                            <ul class="py-2 text-sm text-gray-700 dark:text-gray-200">
                                <li>
                                    <a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">
                                        Settings
                                    </a>
                                </li>
                            </ul>
                            <div class="py-1">
                                <button class="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200">
                                    Sign out
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile Menu Button -->
                <button @click="toggleMenu" type="button"
                    class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700">
                    <span class="sr-only">Open main menu</span>
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h15M1 7h15M1 13h15" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Menu -->
            <div v-if="isMenuOpen" class="w-full md:flex md:w-auto md:order-1 hidden">
                <ul class="flex flex-col font-medium p-4 md:p-0 mt-4 border border-gray-100 rounded-lg md:space-x-8 rtl:space-x-reverse md:flex-row md:mt-0 md:border-0 dark:bg-gray-800 md:dark:bg-gray-900 dark:border-gray-700">
                    <li>
                        <a href="#" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:p-0 dark:text-white">
                            Beranda
                        </a>
                    </li>
                    <li>
                        <a href="#" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:p-0 dark:text-white">
                            Produk
                        </a>
                    </li>
                    <li>
                        <a href="#" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:p-0 dark:text-white">
                            Tentang Kami
                        </a>
                    </li>
                    <li>
                        <a href="#" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:p-0 dark:text-white">
                            Bantuan
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</template>
