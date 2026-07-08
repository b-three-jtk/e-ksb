<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'

const props = defineProps({
    data: Object,
});

console.log('Received data:', props.data)

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin/dashboard' },
    { name: 'Pengelolaan Pembiayaan', link: '/admin/pembiayaan' },
    { name: 'Detail Pembiayaan', link: `/admin/pembiayaan/${props.data.pembiayaan_id}` },
    { name: 'Permohonan Pelunasan' },
];
</script>

<template>
    <AdminLayout title="Pembayaran Pembiayaan Murabahah">
        <PageBreadcrumb :page-title="'Pembayaran Pembiayaan Murabahah'" :items="breadcrumbItems" />
        <div class="card-layout">
            <h2 class="card-title">Pembayaran Berhasil</h2>
            <p class="mb-4 text-gray-500 text-sm">Pembayaran telah berhasil diproses. Berikut adalah struk pembayaran Anda:</p>
            <div v-if="props.data.installment_payment_receipt" class="border p-4 rounded">
                <iframe :src="props.data.installment_payment_receipt" class="w-full h-full min-h-150" frameborder="0"></iframe>
            </div>
            <div v-else class="text-red-500">
                Struk pembayaran tidak tersedia.
            </div>
        </div>
    </AdminLayout>
</template>
