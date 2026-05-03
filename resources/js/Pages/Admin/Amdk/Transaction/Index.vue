<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue';

const breadcrumbItems = [
    { label: 'Dashboard', link: '/admin/dashboard' },
    { label: 'Pengelolaan Penjualan AMDK' },
];
</script>

<template>
    <AdminLayout title="Pengelolaan Penjualan AMDK">
        <PageBreadcrumb :page-title="'Pengelolaan Penjualan AMDK'" :items="breadcrumbItems" />
    </AdminLayout>
</template>
