<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue';
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue';
import { Link } from '@inertiajs/vue3';
import UserIcon from '@/Icons/UserIcon.vue';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const can = computed(() => page.props.auth.can);

const props = defineProps({
    user: { type: Object, required: true },
});

const breadcrumbItems = [
    {name: 'Dashboard', link: '/admin'},
    {name: 'Pengurus', link: '/admin/list'},
    {name: 'Detail Pengurus'},
];

console.log(props.user);

</script>

<template>
    <AdminLayout title="Detail Pengurus">
        <div class="flex flex-col">
            <PageBreadcrumb :page-title="'Detail Pengurus'" :items="breadcrumbItems" />
            <div class="flex flex-col gap-6">
                <div class="card-layout flex justify-between items-center">
                    <div class="flex gap-6">
                        <div v-if="user.profile_picture">
                            <img class="w-20 h-20 rounded-full object-cover bg-gray-400" :src="user.profile_picture"
                                alt="User avatar">
                        </div>
                        <div v-else
                            class="w-20 h-20 rounded-full bg-white border border-stroke flex items-center justify-center text-gray-500">
                            <UserIcon />
                        </div>
                        <div class="flex flex-col justify-center gap-1">
                            <h1 class="card-title">{{ user.name }}</h1>
                            <p class="text-gray-500">
                                {{ user.roles[0].name }}
                            </p>
                        </div>
                    </div>
                    <Link v-if="can['edit_pengurus']" :href="`/admin/edit/${user.id}`"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-theme-sm font-medium text-dark-text shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/3 dark:hover:text-gray-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current fill-white dark:fill-gray-800"
                            width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path
                                d="M4.16667 15.8333H5.35417L13.5 7.6875L12.3125 6.5L4.16667 14.6458V15.8333ZM2.5 17.5V13.9583L13.5 2.97917C13.6667 2.82639 13.8508 2.70833 14.0525 2.625C14.2542 2.54167 14.4658 2.5 14.6875 2.5C14.9092 2.5 15.1244 2.54167 15.3333 2.625C15.5422 2.70833 15.7228 2.83333 15.875 3L17.0208 4.16667C17.1875 4.31944 17.3092 4.5 17.3858 4.70833C17.4625 4.91667 17.5006 5.125 17.5 5.33333C17.5 5.55556 17.4619 5.7675 17.3858 5.96917C17.3097 6.17083 17.1881 6.35472 17.0208 6.52083L6.04167 17.5H2.5ZM12.8958 7.10417L12.3125 6.5L13.5 7.6875L12.8958 7.10417Z"
                                fill="#1D2939" />
                        </svg>
                        Edit
                    </Link>
                </div>
                <div class="card-layout grid gap-5">
                    <div class="card-layout py-0! grid grid-cols-2">
                        <div class="grid grid-cols-1 gap-8 py-6">
                            <h1 class="card-title">Identitas</h1>
                            <ul class="grid xl:grid-cols-2 grid-cols-1 gap-6">
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">NIK</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.nik }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Jenis Kelamin</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.gender ?? '-'
                                        }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Tempat, Tanggal Lahir</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ (user.birth_place && user.birth_date) ? (user.birth_place + ', ' + user.birth_date) : '-' }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Pendidikan Terakhir</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.last_education ??
                                        '-'
                                        }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-layout py-0! grid grid-cols-2">
                        <div class="grid grid-cols-1 gap-8 py-6">
                            <h1 class="card-title">Kontak dan Alamat</h1>
                            <ul class="grid xl:grid-cols-2 grid-cols-1 gap-6">
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Nomor Telepon</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.phone_number ?? '-'
                                    }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Email</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.email }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Alamat Sesuai KTP</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.domicile_address ?? '-'
                                        }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Alamat Domisili</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.residential_address
                                        ?? '-'
                                        }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
