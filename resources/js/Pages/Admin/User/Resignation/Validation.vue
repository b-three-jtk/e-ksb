<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue';
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue';
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue';
import Button from '@/Components/Form/Button.vue'
import ModalDocument from '@/Components/ModalDocument.vue'
import { ref } from 'vue'
import Swal from 'sweetalert2'
import { toast } from "vue3-toastify";

const props = defineProps({
    data: { type: Object, required: true },
});

const showModal = () => {
    document.getElementById('modal').classList.remove('hidden');
};
const hideModal = () => {
    document.getElementById('modal').classList.add('hidden');
};

const buktiResignRef = ref(null)

const openDocModal = () => buktiResignRef.value?.openModal()

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin' },
    { name: 'Pengunduran Diri Anggota', link: '/admin/resignations/list' },
    { name: 'Validasi' },
];

const acceptTransaction = () => {
    Swal.fire({
        title: 'Konfirmasi',
        text: 'Apakah Anda yakin ingin menerima permohonan pengunduran diri ini?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, terima',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#007943',
    }).then((result) => {
        if (result.isConfirmed) {
            form.put('/admin/resignation/' + props.data.id, {
                onSuccess: () => {
                    toast("Permohonan pengunduran diri berhasil diterima!", {
                        "type": "success",
                        "position": "bottom-right",
                        "transition": "slide",
                        "dangerouslyHTMLString": true
                    }).then(() => {
                        router.visit(route('admin.resignations.index'))
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
</script>

<template>
    <AdminLayout title="Validasi Permohonan Pengunduran Diri">
        <div class="flex flex-col">
            <PageBreadcrumb :items="breadcrumbItems" :page-title="'Validasi Permohonan Pengunduran Diri'" />
            <div class="grid lg:grid-cols-3 grid-cols-1 gap-6">
                <div class="card-layout px-0! md:col-span-2">
                    <h1 class="card-title border-b border-b-stroke px-6 pb-6">Detail Data Pengunduran Diri Anggota</h1>
                    <div class="grid grid-cols-2 gap-6 p-8 pb-6">
                        <BaseInputAdmin label="Nama" type="text" v-model="props.data.name" disabled />
                        <BaseInputAdmin label="Nomor Anggota" type="text" v-model="props.data.user_code" disabled />
                        <BaseInputAdmin label="Tanggal Bergabung" type="date" v-model="props.data.joined_date" disabled />
                        <BaseInputAdmin label="Email" type="email" v-model="props.data.email" disabled />
                        <div class="grid grid-cols-2 col-span-2 gap-6">
                            <BaseInputAdmin label="Total Simpanan" type="string" v-model="props.data.total_savings" isMoney
                                disabled />
                            <BaseInputAdmin label="Total Kewajiban" type="string" v-model="props.data.total_obligations"
                                isMoney disabled />
                        </div>
                    </div>
                    <div class="flex gap-6 items-center justify-center">
                        <Button variant="secondary" @click="acceptTransaction()">Terima</Button>
                    </div>
                </div>
                <div class="card-layout md:col-span-1 h-fit!">
                    <h1 class="card-title">Dokumen Permohonan Pengunduran Diri</h1>
                    <div class="flex flex-col items-center justify-center gap-4 mt-4 px-6">
                        <div
                            class="mx-auto flex w-60 aspect-square items-center justify-center rounded-lg border-2 bg-gray-50 dark:border-gray-700">
                            <!-- <img v-if="form.photo_url" :src="form.photo_url" alt="Foto calon anggota"
                                class="h-full w-full rounded-lg object-cover" /> -->
                        </div>
                        <Button @click="openDocModal()" variant="secondary">Lihat Detail</Button>
                    </div>
                </div>
            </div>
        </div>
        <ModalDocument ref="buktiResignRef" modal-id="modal-doc" title="Dokumen Pengunduran Diri Anggota" name="Dokumen Pengunduran Diri Anggota" :attachment="props.data.resignation_doc" />
    </AdminLayout>
</template>
