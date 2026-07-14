<script setup>
import { computed, onMounted } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import Base from '../../Layouts/Base.vue'
import BaseTable from '../../Components/Table/BaseTable.vue';
import { Icon } from '@iconify/vue';
import { toast } from 'vue3-toastify';

const page = usePage()

const user = computed(() => page.props.auth.user)
const summary = computed(() => page.props.summary)
const tabungan = computed(() => page.props.tabungan)

const rupiah = (value) =>
    'Rp ' + new Intl.NumberFormat('id-ID').format(value ?? 0)

onMounted(() => {
    if (page.props.flash?.login_success) {
        toast.success('Login berhasil, Selamat Datang!', {
            autoClose: 3000,
            position: 'bottom-right',
        })
    }
})
</script>

<template>
    <Base title="Dashboard">
        <div class="font-head min-h-screen bg-gray-900/20 dark:bg-gray-900 transition-colors pb-12">
            <section
                class="relative h-112.5 flex items-center"
                style="background-image: url('/images/home/al-hikmah.png');
                    background-size: cover;
                    background-position: center;"
            >

                <div class="absolute inset-0 bg-gray-dark/75"></div>

                <div class="relative z-10 max-w-7xl mx-auto px-6 text-white">
                    <div class="text-center mt-10">
                        <h1 class="text-4xl md:text-5xl font-semibold mb-2 text-white">
                            Halo selamat datang
                            <span class="text-accent">{{ user?.nama }}</span>
                            !
                        </h1>
                    </div>
                </div>
            </section>

            <!-- Summary -->
            <section class="-mt-16 relative z-20 max-w-7xl mx-auto px-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Total Simpanan -->
                    <div class="bg-primary rounded-2xl shadow-lg p-8 text-white flex items-center justify-center min-h-45">
                        <div class="flex items-center gap-5 w-full">
                            <div class="shrink-0">
                                <Icon icon="tabler:wallet" class="w-16 h-16" />
                            </div>

                            <div class="flex-1">
                                <p class="font-body text-base opacity-90 mb-1">Total Simpanan</p>
                                <h2 class="text-3xl font-bold">{{ rupiah(summary.total_saving) }}</h2>
                            </div>
                        </div>
                    </div>

                    <!-- Total Angsuran -->
                    <div class="bg-primary rounded-2xl shadow-lg p-8 text-white flex items-center justify-center min-h-45">
                        <div class="flex items-center gap-5 w-full">
                            <div class="shrink-0">
                                <Icon icon="tabler:receipt" class="w-16 h-16" />
                            </div>

                            <div class="flex-1">
                                <p class="font-body text-base opacity-90 mb-1">Total Sisa Angsuran</p>
                                <h2 class="text-3xl font-bold">{{ rupiah(summary.total_installment) }}</h2>
                            </div>
                        </div>
                    </div>

                    <!-- Jumlah Pembiayaan Murabahah -->
                    <div class="bg-primary rounded-2xl shadow-lg p-8 text-white flex items-center justify-center min-h-45">
                        <div class="flex items-center gap-5 w-full">
                            <div class="flex-shrink-0">
                                <Icon icon="streamline-block:money-coin" class="w-16 h-16" />
                            </div>

                            <div class="flex-1">
                                <p class="font-body text-base opacity-90 mb-1">Total Poin</p>
                                <h2 class="text-3xl font-bold">{{ summary.total_points }}</h2>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Quick Access-->
            <section class="max-w-7xl mx-auto px-6 mt-12 grid md:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow dark:text-gray-100 h-75 flex flex-col">
                    <h3 class="font-semibold text-xl mb-6">Akses Cepat</h3>

                    <div class="flex justify-around text-center flex-1 items-center">
                        <Link href="/user/tabungan" class="group">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-24 h-24 rounded-full bg-secondary flex items-center justify-center text-white group-hover:bg-green-600 transition-colors">
                                    <Icon icon="uil:transaction" class="w-11 h-11" />
                                </div>
                                <div class="mt-1">
                                    <p class="text-base font-medium">
                                        Tabungan
                                    </p>
                                    <p class="text-base font-medium mt-0.5 ">
                                        Pribadi
                                    </p>
                                </div>
                            </div>
                        </Link>

                        <Link href="/user/profile" class="group">
                            <div class="flex flex-col items-center gap-2">
                                <div class="w-24 h-24 rounded-full bg-secondary flex items-center justify-center text-white group-hover:bg-green-600 transition-colors">
                                    <Icon icon="subway:coin" class="w-11 h-11" />
                                </div>
                                <div class="mt-1">
                                    <p class="text-base font-medium">
                                        Riwayat
                                    </p>
                                    <p class="text-base font-medium mt-0.5 ">
                                        Poin
                                    </p>
                                </div>
                            </div>
                        </Link>

                        <Link href="/user/pembiayaan" class="group">
                            <div class="flex flex-col items-center gap-2">
                                <div class="w-24 h-24 rounded-full bg-secondary flex items-center justify-center text-white group-hover:bg-green-600 transition-colors">
                                    <Icon icon="carbon:finance" class="w-11 h-11" />
                                </div>
                                <div class="mt-1">
                                    <p class="text-base font-medium">
                                        Pembiayaan
                                    </p>
                                    <p class="text-base font-medium mt-0.5 ">
                                        Murabahah
                                    </p>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Mini Tabungan -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow dark:text-gray-100 h-75 flex flex-col">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-xl">Riwayat Transaksi Terbaru Anda</h3>
                        <Link
                            href="/user/tabungan"
                            class="text-base text-primary font-medium border border-primary hover:bg-secondary hover:text-white dark:hover:bg-accent/20 px-3 py-1.5 rounded-lg transition-colors">
                            Lihat Semua
                        </Link>
                    </div>

                    <BaseTable
                        :columns="[
                            { key: 'date', label: 'Tanggal' },
                            { key: 'product', label: 'Produk' },
                            { key: 'type', label: 'Jenis' },
                            { key: 'amount', label: 'Nominal', align: 'right' }
                        ]"
                        :data="tabungan"
                    />
                </div>
            </section>
        </div>
    </Base>
</template>
