<script setup>
    import AdminLayout from '@/Layouts/Admin/Layout.vue';
    import { Link } from '@inertiajs/vue3';
</script>

<template>
    <AdminLayout title="403 - Forbidden">
        <div class="flex flex-col items-center justify-center h-full px-4 py-12 text-center">
            <h1 class="text-9xl font-extrabold text-gray-300 dark:text-gray-600">403</h1>
            <p class="mt-4 text-2xl font-semibold text-gray-700 dark:text-gray-300">Forbidden</p>
            <p class="mt-2 text-gray-500 dark:text-gray-400">Anda tidak memiliki izin untuk mengakses halaman ini.</p>
            <Link href="/admin/dashboard" class="mt-6 inline-block px-6 py-3 bg-blue-600 text-white font-medium rounded hover:bg-blue-700 transition">
                Kembali ke Dashboard
            </Link>
        </div>
    </AdminLayout>
</template>
