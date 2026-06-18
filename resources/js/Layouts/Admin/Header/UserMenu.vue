<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { ref, onMounted, onUnmounted, computed } from 'vue'
import ChevronDownIcon from '@/Icons/ChevronDownIcon.vue'
import LogoutIcon from '@/Icons/LogoutIcon.vue'
import UserIcon from '@/Icons/UserIcon.vue'
import Swal from 'sweetalert2'
import { toast } from "vue3-toastify"
import { useForm } from '@inertiajs/vue3'

const page = usePage()
const dropdownOpen = ref(false)
const dropdownRef = ref(null)
const form = useForm({})
// Get actual user data from auth
const user = computed(() => page.props.auth?.user || {
    name: 'User',
    email: 'user@example.com',
    profile_picture: '/public/images/user/owner.jpg',
})

const userRole = computed(() => page.props.auth?.role || 'User')

const photoUrl = computed(() => {
    if (user.value?.profile_picture) {
        return `/storage/${user.value.profile_picture}`
    }
    return null
})

const menuItems = [
    { href: '/admin/profile', icon: UserIcon, text: 'Profil' },
]

const toggleDropdown = () => {
    dropdownOpen.value = !dropdownOpen.value
}

const closeDropdown = () => {
    dropdownOpen.value = false
}

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        closeDropdown()
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})

const logout = () => {
    Swal.fire({
        title: 'Konfirmasi',
        text: 'Apakah Anda yakin ingin keluar?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, keluar',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#009141',
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
    <div class="relative" ref="dropdownRef">
        <button class="flex items-center text-gray-700 dark:text-gray-400" @click.prevent="toggleDropdown">
            <span v-if="photoUrl" class="mr-3 overflow-hidden rounded-full h-11 w-11">
                <img :src="photoUrl" alt="User" />
            </span>
            <span v-else
                class="w-11 h-11 mr-3 rounded-full border border-stroke bg-white flex items-center justify-center text-gray-500 cursor-pointer">
                <UserIcon />
            </span>
            <div class="flex flex-col text-left">
                <span class="block mr-1 font-medium text-theme-sm">{{ user.name }}</span>
                <span class="mt-0.5 block text-theme-xs text-gray-500 dark:text-gray-400">
                    {{ userRole }}
                </span>
            </div>

            <ChevronDownIcon :class="{ 'rotate-180': dropdownOpen }" />
        </button>

        <!-- Dropdown Start -->
        <div v-if="dropdownOpen"
            class="absolute right-0 mt-4.25 flex w-65 flex-col rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark">
            <div>
                <span class="block font-medium text-gray-700 text-theme-sm dark:text-gray-400">
                    {{ user.name }}
                </span>
                <span class="mt-0.5 block text-theme-xs text-gray-500 dark:text-gray-400">
                    {{ userRole }}
                </span>
            </div>

            <ul class="flex flex-col gap-1 pt-4 pb-3 border-b border-gray-200 dark:border-gray-500">
                <li v-for="item in menuItems" :key="item.href">
                    <Link :href="item.href"
                        class="flex items-center gap-3 px-3 py-2 font-medium text-gray-700 rounded-lg group text-theme-sm hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300">
                        <component :is="item.icon"
                            class="text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300" />
                        {{ item.text }}
                    </Link>
                </li>
            </ul>
            <button @click="logout" type="button"
                class="flex items-center gap-3 px-3 py-2 mt-3 font-medium text-gray-700 rounded-lg group text-theme-sm hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300">
                <LogoutIcon class="text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300" />
                Keluar
            </button>
        </div>
        <!-- Dropdown End -->
    </div>
</template>
