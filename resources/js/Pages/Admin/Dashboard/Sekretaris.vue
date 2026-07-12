<script setup>
import { Link } from '@inertiajs/vue3';
import CardInfo from '@/Components/CardInfo.vue';

const props = defineProps({
    stats: Object,
    selectedFilter: String,
});

const emit = defineEmits(['update:selectedFilter']);
</script>

<template>
    <div class="w-full grid grid-cols-1 lg:grid-cols-3 gap-6 mt-4 lg:mt-0">
        <!-- Kolom Kiri: 3 Kartu Info (Bertumpuk Vertikal) -->
        <div class="lg:col-span-1 flex flex-col gap-4">
            <CardInfo
                title="Total Anggota Aktif"
                :content="props.stats.total_anggota_aktif"
                :percentage="props.stats.total_anggota_aktif_persen"
                :filter="selectedFilter"
            />
            <CardInfo
                title="Total Pengurus"
                :content="props.stats.total_pengurus"
                :percentage="props.stats.total_pengurus_persen"
                :filter="selectedFilter"
            />
            <CardInfo
                title="Total Anggota Belum Dialokasikan"
                :content="props.stats.total_anggota_belum_dialokasi"
                :percentage="props.stats.total_anggota_belum_dialokasi_persen"
                :filter="selectedFilter"
            />
        </div>

        <!-- Kolom Kanan: Menu Pintasan -->
        <div class="card-layout lg:col-span-2 bg-light-bg! dark:bg-brand-900/60! flex flex-col">
            <h1 class="card-title mb-2">Menu Pintasan</h1>
            <p class="text-sm text-gray-500 mb-6">Akses cepat menu administratif</p>
            
            <div class="flex flex-col gap-3.5">
                <Link href="/admin/users/create"
                    class="bg-white dark:bg-light-bg/20 dark:border-stroke/30 border border-stroke px-4 py-5 flex justify-between items-center rounded-xl hover:bg-gray-50 transition group">
                    <div class="flex items-center gap-4">
                        <div
                            class="bg-secondary text-white rounded-full flex justify-center text-2xl items-center w-12 h-12 shadow-sm group-hover:scale-105 transition-transform">
                            <span class="icon-[mdi--users]"></span>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Tambah Anggota Baru</h2>
                            <p class="text-gray-500 text-sm font-body dark:text-gray-400 mt-0.5">Registrasi anggota koperasi baru di sini</p>
                        </div>
                    </div>
                    <div class="text-secondary dark:text-gray-300 text-3xl opacity-60 group-hover:opacity-100 transition-opacity">
                        <span class="icon-[material-symbols--chevron-right-rounded]"></span>
                    </div>
                </Link>
                <Link href="/admin/pengurus/create"
                    class="bg-white dark:bg-light-bg/20 dark:border-stroke/30 border border-stroke px-4 py-5 flex justify-between items-center rounded-xl hover:bg-gray-50 transition group">
                    <div class="flex items-center gap-4">
                        <div
                            class="bg-secondary text-white rounded-full flex justify-center text-2xl items-center w-12 h-12 shadow-sm group-hover:scale-105 transition-transform">
                            <span class="icon-[clarity--employee-group-solid]"></span>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Tambah Pengurus Baru</h2>
                            <p class="text-gray-500 text-sm font-body dark:text-gray-400 mt-0.5">Registrasi pengurus koperasi baru di sini</p>
                        </div>
                    </div>
                    <div class="text-secondary dark:text-gray-300 text-3xl opacity-60 group-hover:opacity-100 transition-opacity">
                        <span class="icon-[material-symbols--chevron-right-rounded]"></span>
                    </div>
                </Link>
                <Link href="/admin/allocation"
                    class="bg-white dark:bg-light-bg/20 dark:border-stroke/30 border border-stroke px-4 py-5 flex justify-between items-center rounded-xl hover:bg-gray-50 transition group">
                    <div class="flex items-center gap-4">
                        <div
                            class="bg-secondary text-white rounded-full flex justify-center text-2xl items-center w-12 h-12 shadow-sm group-hover:scale-105 transition-transform">
                            <span class="icon-[mdi--account-cog-outline]"></span>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Alokasi Penanggung Jawab</h2>
                            <p class="text-gray-500 text-sm font-body dark:text-gray-400 mt-0.5">Bagikan anggota ke penanggung jawab masing-masing</p>
                        </div>
                    </div>
                    <div class="text-secondary dark:text-gray-300 text-3xl opacity-60 group-hover:opacity-100 transition-opacity">
                        <span class="icon-[material-symbols--chevron-right-rounded]"></span>
                    </div>
                </Link>
            </div>
        </div>
    </div>
</template>
