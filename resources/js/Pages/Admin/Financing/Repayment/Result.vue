<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'

const props = defineProps({
    data: Object,
});

console.log('Received data:', props.data)

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin/dashboard' },
    { name: 'Pengelolaan Pembiayaan', link: '/admin/financings' },
    { name: 'Detail Pembiayaan', link: `/admin/financings/${props.data.financing_id}` },
    { name: 'Permohonan Pelunasan' },
];
</script>

<template>
    <AdminLayout title="Pelunasan Sebelum Jatuh Tempo">
        <PageBreadcrumb :page-title="'Pelunasan Sebelum Jatuh Tempo'" :items="breadcrumbItems" />
        <div class="card-layout">
            <h2 class="card-title">Pelunasan Berhasil</h2>
            <p class="mb-4 text-gray-500 text-sm">Pembayaran pelunasan telah berhasil diproses. Berikut adalah struk pembayaran Anda:</p>
            <div v-if="props.data.installment_payment_receipt" class="border p-4 rounded">
                <iframe :src="props.data.installment_payment_receipt" class="w-full h-full min-h-150" frameborder="0"></iframe>
            </div>
            <div v-else class="text-red-500">
                Struk pembayaran tidak tersedia.
            </div>
        </div>
    </AdminLayout>
</template>
