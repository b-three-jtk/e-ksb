<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue';
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue';
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue';
import { useForm } from '@inertiajs/vue3';
import Button from '@/Components/Form/Button.vue'
import ModalDocument from '@/Components/ModalDocument.vue'
import { ref } from 'vue'
import Swal from 'sweetalert2'
import { toast } from "vue3-toastify";

const props = defineProps({
    data: { type: Object, required: true },
});

console.log(props.data.total_obligations)

const showModal = () => {
    document.getElementById('modal').classList.remove('hidden');
};
const hideModal = () => {
    document.getElementById('modal').classList.add('hidden');
};

const buktiResignRef = ref(null)

const openDocModal = () => buktiResignRef.value?.openModal()

const form = useForm({
    notes: props.data.notes || '',
    status: '',
});

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin' },
    { name: 'Pengunduran Diri Anggota', link: '/admin/resignation/list' },
    { name: 'Validasi' },
];

const acceptTransaction = () => {
    form.status = 'accept'
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

const rejectTransaction = () => {
    form.status = 'reject'
    Swal.fire({
        title: 'Konfirmasi',
        text: 'Apakah Anda yakin ingin menolak permohonan pengunduran diri ini?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, tolak',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#007943',
    }).then((result) => {
        if (result.isConfirmed) {
            hideModal()
            form.put('/admin/resignation/' + props.data.id, {
                onSuccess: () => {
                    toast("Permohonan pengunduran diri berhasil ditolak!", {
                        "type": "success",
                        "position": "bottom-right",
                        "transition": "slide",
                        "dangerouslyHTMLString": true
                    }).then(() => {
                        router.visit(route('admin.resignations.index'))
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
        <div class="flex flex-col">
            <PageBreadcrumb :items="breadcrumbItems" :page-title="'Validasi Permohonan Pengunduran Diri'" />
            <div class="grid lg:grid-cols-3 grid-cols-1 gap-6">
                <div class="card-layout px-0! md:col-span-2">
                    <h1 class="card-title border-b border-b-stroke px-6 pb-6">Detail Data Pengunduran Diri Anggota</h1>
                    <div class="grid grid-cols-2 gap-6 p-8 pb-6">
                        <BaseInputAdmin label="Nama" type="text" v-model="props.data.name" isDisabled />
                        <BaseInputAdmin label="Nomor Anggota" type="text" v-model="props.data.member_number" isDisabled />
                        <BaseInputAdmin label="Tanggal Bergabung" type="date" v-model="props.data.joined_date" isDisabled />
                        <BaseInputAdmin label="Email" type="email" v-model="props.data.email" isDisabled />
                        <BaseInputAdmin label="Unit Kerja" type="text" v-model="props.data.work_unit.name" isDisabled />
                        <div class="grid grid-cols-2 col-span-2 gap-6">
                            <BaseInputAdmin label="Total Simpanan" type="string" v-model="props.data.total_savings" isMoney
                                isDisabled />
                            <BaseInputAdmin label="Total Kewajiban" type="string" v-model="props.data.total_obligations"
                                isMoney isDisabled />
                        </div>
                    </div>
                    <div class="flex gap-6 items-center justify-center">
                        <Button variant="secondary" @click="acceptTransaction()">Terima</Button>
                        <Button variant="danger" @click.prevent="showModal()">Tolak</Button>
                    </div>
                </div>
                <div class="card-layout md:col-span-1 h-fit!">
                    <h1 class="card-title">Dokumen Permohonan Pengunduran Diri</h1>
                    <div class="flex flex-col items-center justify-center gap-4 mt-4 px-6">
                        <div
                            class="mx-auto flex w-60 aspect-square items-center justify-center rounded-lg border-2 bg-gray-50 dark:border-gray-700">
                            <img v-if="form.photo_url" :src="form.photo_url" alt="Foto calon anggota"
                                class="h-full w-full rounded-lg object-cover" />
                        </div>
                        <Button @click="openDocModal()" variant="secondary">Lihat Detail</Button>
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
                    <Button size="small" variant="light" @click="hideModal()">Batal</Button>
                    <Button @click="rejectTransaction()" variant="secondary" size="small">Simpan</Button>
                </div>
            </div>
        </div>
        <ModalDocument ref="buktiResignRef" modal-id="modal-doc" title="Dokumen Pengunduran Diri Anggota" name="Dokumen Pengunduran Diri Anggota" :attachment="props.data.resignation_doc" />
    </AdminLayout>
</template>
