<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import Base from '../../Layouts/Base.vue'
import BaseTable from '../../Components/Table/BaseTable.vue';
import { Icon } from '@iconify/vue';

const page = usePage()

const user = computed(() => page.props.auth.user)
const summary = computed(() => page.props.summary)
const ledger = computed(() => page.props.ledger)

const rupiah = (value) =>
    'Rp ' + new Intl.NumberFormat('id-ID').format(value ?? 0)
</script>

<template>
    <Base title="Dashboard">
        <div class="font-head min-h-screen bg-blue-900/20 dark:bg-gray-900 transition-colors">
            <section
                class="relative h-[450px] flex items-center"
                style="background-image: url('/images/home/polban_v2.png');
                    background-size: cover;
                    background-position: center;"
            >

                <div class="absolute inset-0 bg-blue-900/75"></div>

                <div class="relative z-10 max-w-7xl mx-auto px-6 text-white">
                    <div class="text-center mt-10">
                        <h1 class="text-3xl md:text-4xl font-semibold mb-2 text-white">
                            Halo selamat datang
                            <span class="text-orange-400">{{ user?.name }}</span>
                        </h1>

                        <p class="font-body text-lg mb-6 text-gray-200">
                            Punya kebutuhan pembiayaan?
                        </p>

                        <Link
                            href="/pembiayaan/murabahah"
                            class="inline-flex items-center gap-2 bg-orange-500 hover:bg-orange-600 text-white px-8 py-3 rounded-xl font-semibold transition-colors"
                        >
                            <Icon icon="tabler:cash" class="w-5 h-5" />
                            Ajukan Pembiayaan
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Summary -->
            <section class="-mt-16 relative z-20 max-w-7xl mx-auto px-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Total Simpanan -->
                    <div class="bg-blue-900 rounded-2xl shadow-lg p-6 text-white flex items-center justify-center min-h-[140px]">
                        <div class="flex items-center gap-4 w-full">
                            <div class="flex-shrink-0">
                                <Icon icon="tabler:wallet" class="w-12 h-12" />
                            </div>

                            <div class="flex-1">
                                <p class="font-body text-sm opacity-90 mb-1">Total Simpanan</p>
                                <h2 class="text-2xl font-bold">{{ rupiah(summary.total_saving) }}</h2>
                            </div>
                        </div>
                    </div>

                    <!-- Total Angsuran -->
                    <div class="bg-orange-500 rounded-2xl shadow-lg p-6 text-white flex items-center justify-center min-h-[140px]">
                        <div class="flex items-center gap-4 w-full">
                            <div class="flex-shrink-0">
                                <Icon icon="tabler:receipt" class="w-12 h-12" />
                            </div>

                            <div class="flex-1">
                                <p class="font-body text-sm opacity-90 mb-1">Total Angsuran</p>
                                <h2 class="text-2xl font-bold">{{ rupiah(summary.total_installment) }}</h2>
                            </div>
                        </div>
                    </div>

                    <!-- Cetak Ledger -->
                    <div class="bg-blue-light-400 rounded-2xl shadow-lg p-6 text-white flex items-center justify-center min-h-[140px]">
                        <div class="flex items-center gap-4 w-full">
                            <div class="flex-shrink-0">
                                <Icon icon="tabler:printer" class="w-12 h-12" />
                            </div>

                            <div class="flex-1">
                                <p class="font-body text-sm opacity-90 mb-1">Cetak Ledger</p>
                                <h2 class="text-2xl font-bold">{{ summary.ledger_count }}</h2>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Quick Access-->
            <section class="max-w-7xl mx-auto px-6 mt-12 grid md:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow dark:text-gray-100 h-[250px] flex flex-col">
                    <h3 class="font-semibold text-lg mb-6">Quick Access</h3>

                    <div class="flex justify-around text-center">
                        <Link href="/simpanan/setor" class="group">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-20 h-20 rounded-full bg-green-500 flex items-center justify-center text-white group-hover:bg-green-600 transition-colors">
                                    <Icon icon="uil:money-insert" class="w-9 h-9" />
                                </div>
                                <div class="mt-1">
                                    <p class="text-sm font-medium">
                                        Penyetoran
                                    </p>
                                    <p class="text-sm font-medium mt-0.5 ">
                                        Simpanan
                                    </p>
                                </div>
                            </div>
                        </Link>

                        <Link href="/simpanan/tarik" class="group">
                            <div class="flex flex-col items-center gap-2">
                                <div class="w-20 h-20 rounded-full bg-orange-500 flex items-center justify-center text-white group-hover:bg-orange-600 transition-colors">
                                    <Icon icon="uil:money-withdraw" class="w-9 h-9" />
                                </div>
                                <div class="mt-1">
                                    <p class="text-sm font-medium">
                                        Penarikan
                                    </p>
                                    <p class="text-sm font-medium mt-0.5 ">
                                        Simpanan
                                    </p>
                                </div>
                            </div>
                        </Link>

                        <Link href="/pembiayaan/murabahah" class="group">
                            <div class="flex flex-col items-center gap-2">
                                <div class="w-20 h-20 rounded-full bg-sky-400 flex items-center justify-center text-white group-hover:bg-blue-light-600 transition-colors">
                                    <Icon icon="uil:credit-card" class="w-9 h-9" />
                                </div>
                                <div class="mt-1">
                                    <p class="text-sm font-medium">
                                        Pembiayaan
                                    </p>
                                    <p class="text-sm font-medium mt-0.5 ">
                                        Murabahah
                                    </p>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Mini Ledger -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow dark:text-gray-100 h-[250px] flex flex-col">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-lg">Your Ledger</h3>
                        <Link
                            href="/ledger"
                            class="text-sm text-orange-500 font-medium border border-orange-500 hover:bg-orange-50 dark:hover:bg-orange-900/20 px-3 py-1.5 rounded-lg transition-colors">
                            See All
                        </Link>
                    </div>

                    <BaseTable
                        :columns="[
                            { key: 'date', label: 'Tanggal' },
                            { key: 'product', label: 'Produk' },
                            { key: 'type', label: 'Jenis' },
                            { key: 'amount', label: 'Saldo', align: 'right' }
                        ]"
                        :data="ledger"
                    />
                </div>
            </section>
        </div>
    </Base>
</template>
