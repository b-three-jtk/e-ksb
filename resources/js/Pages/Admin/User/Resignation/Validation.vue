<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue';
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue';
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue';
import { useForm } from '@inertiajs/vue3';

const prop = defineProps({
    data: { type: Object, required: true },
    total_savings: { type: Number, required: true },
});

const showModal = () => {
    document.getElementById('modal').classList.remove('hidden');
};
const hideModal = () => {
    document.getElementById('modal').classList.add('hidden');
};

const form = useForm({
    name: prop.data.name || '',
    member_number: prop.data.member_number || '',
    join_date: prop.data.join_date || '',
    email: prop.data.email || '',
    work_unit: prop.data.work_unit.name || '',
    total_savings: prop.total_savings || '',
    total_liabilities: prop.data.total_liabilities || '',
    notes: prop.data.notes || '',
    photo_url: prop.data.user_docs[0]?.attachment || '',
    status: '',
});

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin' },
    { name: 'Pengunduran Diri Anggota', link: '/admin/resignation/list' },
    { name: 'Validasi' },
];

const acceptTransaction = () => {
    form.status = 'accepted'
    Swal.fire({
        title: 'Konfirmasi',
        text: 'Apakah Anda yakin ingin menerima permohonan pengunduran diri ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, terima',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#007943',
    }).then((result) => {
        if (result.isConfirmed) {
            form.put('/admin/savings/validate/' + props.data.id, {
                onSuccess: () => {
                    toast("Permohonan pengunduran diri berhasil diterima!", {
                        "type": "success",
                        "position": "bottom-right",
                        "transition": "slide",
                        "dangerouslyHTMLString": true
                    }).then(() => {
                        router.visit(route('admin.dashboard'))
                    })
                },
                onError: () => {
                    toast("Gagal menerima permohonan pengunduran diri.", {
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

const rejectTransaction = () => {
    form.status = 'rejected'
    Swal.fire({
        title: 'Konfirmasi',
        text: 'Apakah Anda yakin ingin menolak permohonan pengunduran diri ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, tolak',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#007943',
    }).then((result) => {
        if (result.isConfirmed) {
            hideModal()
            form.put('/admin/savings/validate/' + props.data.id, {
                onSuccess: () => {
                    toast("Permohonan pengunduran diri berhasil ditolak!", {
                        "type": "success",
                        "position": "bottom-right",
                        "transition": "slide",
                        "dangerouslyHTMLString": true
                    }).then(() => {
                        router.visit(route('admin.dashboard'))
                    })
                },
                onError: () => {
                    toast("Gagal menolak permohonan pengunduran diri.", {
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
    <AdminLayout title="Validasi Permohonan Pengunduran Diri">
        <div class="flex flex-col mx-16">
            <PageBreadcrumb :items="breadcrumbItems" :page-title="'Validasi Permohonan Pengunduran Diri'" />
            <div class="grid grid-cols-3 gap-6">
                <div class="card-layout px-0! col-span-2">
                    <h1 class="card-title border-b border-b-stroke px-6 pb-6">Detail Data Pengunduran Diri Anggota</h1>
                    <div class="grid grid-cols-2 gap-6 p-8 pb-6">
                        <BaseInputAdmin label="Nama" type="text" v-model="form.name" isDisabled />
                        <BaseInputAdmin label="Nomor Anggota" type="text" v-model="form.member_number" isDisabled />
                        <BaseInputAdmin label="Tanggal Bergabung" type="date" v-model="form.join_date" isDisabled />
                        <BaseInputAdmin label="Email" type="email" v-model="form.email" isDisabled />
                        <BaseInputAdmin label="Unit Kerja" type="text" v-model="form.work_unit" isDisabled />
                        <div class="grid grid-cols-2 col-span-2 gap-6">
                            <BaseInputAdmin label="Total Simpanan" type="string" v-model="form.total_savings" isMoney
                                isDisabled />
                            <BaseInputAdmin label="Total Kewajiban" type="number" v-model="form.total_liabilities"
                                isMoney isDisabled />
                        </div>
                    </div>
                    <div class="flex gap-6 items-center justify-center">
                        <button
                            class="bg-secondary rounded-xl px-10 py-3 text-white hover:bg-secondary/90">Terima</button>
                        <button @click.prevent="showModal()" class="bg-red-700 rounded-xl px-10 py-3 text-white hover:bg-red-800">Tolak</button>
                    </div>
                </div>
                <div class="card-layout col-span-1 h-fit!">
                    <h1 class="card-title">Dokumen Permohonan Pengunduran Diri</h1>
                    <div class="flex flex-col gap-4 mt-4 px-6">
                        <div
                            class="mx-auto flex w-60 aspect-square items-center justify-center rounded-lg border-2 bg-gray-50 dark:border-gray-700">
                            <img v-if="form.photo_url" :src="form.photo_url" alt="Foto calon anggota"
                                class="h-full w-full rounded-lg object-cover" />
                        </div>
                        <button class="bg-secondary rounded-xl px-6 py-3 text-white mx-auto">Lihat Detail</button>
                    </div>
                </div>
            </div>
        </div>
        <div id="modal" @click.self="hideModal()"
            class="fixed inset-0 bg-black/50 flex items-center justify-center hidden">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-96">
                <h2 class="text-lg font-semibold mb-4 text-dark-text dark:text-white">Alasan Penolakan</h2>
                <textarea rows="4" v-model="form.notes"
                    class="w-full p-2 border font-body border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    placeholder="Masukkan alasan penolakan..."></textarea>
                <div class="flex justify-end mt-4 gap-2">
                    <button @click="hideModal()" type="button"
                        class="px-8 text-theme-sm py-2.5 bg-gray-300 text-dark-text dark:text-gray-800 rounded-lg hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-500">
                        Batal
                    </button>
                    <button @click="rejectTransaction()" type="button"
                        class="px-8 py-2.5 text-theme-sm bg-primary text-white rounded-lg hover:bg-brand-800">
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
