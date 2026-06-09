<script setup>
import { Link } from '@inertiajs/vue3';
import CardInfo from '@/Components/CardInfo.vue';
import AreaChart from '@/Components/Dashboard/AreaChart.vue'
import SkeletonChartCard from '@/Components/Dashboard/Loading/SkeletonChartCard.vue';

const props = defineProps({
    stats: Object,
    pertumbuhan_anggota: Object,
});

</script>

<template>
    <div class="grid grid-cols-5 gap-3.5">
        <SkeletonChartCard v-if="!pertumbuhan_anggota" class="col-span-3" :bars="12" :legend="2" />
        <div v-else class="card-layout col-span-3">
            <h1 class="card-title">Grafik Pertumbuhan Anggota</h1>
            <AreaChart :data="pertumbuhan_anggota" />
        </div>
        <div class="col-span-2 grid grid-cols-2 gap-3.5">
            <CardInfo
                title="Total Anggota Aktif"
                :content="props.stats.total_anggota_aktif"
                :percentage="props.stats.total_anggota_aktif_persen"
                :filter="selectedFilter"
            />
            <CardInfo
                title="Total Anggota Non-Aktif"
                :content="props.stats.total_anggota_non_aktif"
                :percentage="props.stats.total_anggota_non_aktif_persen"
                :filter="selectedFilter"
            />
            <div class="card-layout col-span-2 bg-light-bg! dark:bg-brand-900/60!">
                <h1 class="card-title text-center">Menu Pintasan</h1>
                <div class="flex flex-col gap-3.5 mt-6">
                    <Link href="/admin/users/create"
                        class="bg-white dark:bg-light-bg/20 dark:border-stroke/30 border border-stroke px-4 py-6 flex justify-between items-center rounded-xl hover:bg-gray-50 transition">
                        <div class="flex items-center gap-3.5">
                            <div
                                class="bg-secondary text-white  rounded-full flex justify-center text-2xl items-center w-11 h-11">
                                <span class="icon-[mdi--users]"></span>
                            </div>
                            <div class="flex flex-col">
                                <h2 class="text-lg font-medium dark:text-gray-200">Tambah Anggota Baru</h2>
                                <p class="text-gray-500 text-sm font-body dark:text-gray-300">Registrasi anggota koperasi baru di sini</p>
                            </div>
                        </div>
                        <div class="text-secondary dark:text-gray-300 text-3xl">
                            <span class="icon-[material-symbols--chevron-right-rounded]"></span>
                        </div>
                    </Link>
                    <Link href="/admin/create"
                        class="bg-white dark:bg-light-bg/20 dark:border-stroke/30 border border-stroke px-4 py-6 flex justify-between items-center rounded-xl hover:bg-gray-50 transition">
                        <div class="flex items-center gap-3.5">
                            <div
                                class="bg-secondary text-white  rounded-full flex justify-center text-2xl items-center w-11 h-11">
                                <span class="icon-[clarity--employee-group-solid]"></span>
                            </div>
                            <div class="flex flex-col">
                                <h2 class="text-lg font-medium dark:text-gray-200">Tambah Pengurus Baru</h2>
                                <p class="text-gray-500 text-sm font-body dark:text-gray-300">Registrasi pengurus koperasi baru di sini</p>
                            </div>
                        </div>
                        <div class="text-secondary dark:text-gray-300 text-3xl">
                            <span class="icon-[material-symbols--chevron-right-rounded]"></span>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </div>

</template>
