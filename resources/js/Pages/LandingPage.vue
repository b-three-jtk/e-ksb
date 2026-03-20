<script setup>
import Base from '@/Layouts/Base.vue'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import Footer from '@/Layouts/Footer.vue'
import { Link } from '@inertiajs/vue3'
import UserIcon from '@/Icons/UserIcon.vue'
import HelpButton from '@/Components/HelpButton.vue'

const testimonials = [
    { quote: 'Berkat pembiayaan murabahah, saya bisa beli laptop tanpa riba. Angsuran ringan dan sesuai syariat.', name: 'Diana Latifah', title: 'Dosen Akuntansi' },
    { quote: 'Layanannya cepat, transparan, dan bagi hasilnya adil. Sangat membantu keuangan keluarga.', name: 'Rifqi Maulana', title: 'Staf Laboratorium' },
    { quote: 'Saya suka bagi hasilnya fair dan sesuai syariah.', name: 'Oneng', title: 'Pemilik Kantin' },
    { quote: 'Prosesnya cepat, tanpa riba, dan jelas akadnya.', name: 'Andi Nugraha', title: 'Teknisi' },
    { quote: 'Simpanan dan pembiayaannya bikin tenang.', name: 'Siti Rahma', title: 'Administrasi' },
]

const marqueeItems = [
    {
        content: 'Simpanan',
        highlight: 'Syariah',
        color: 'text-green-700'
    },
    {
        content: 'Pembiayaan Bebas',
        highlight: 'Riba',
        color: 'text-accent'
    },
    {
        content: 'Keuntungan',
        highlight: 'Halal',
        after: 'Dibagi Rata',
        color: 'text-green-accent'
    },
]

const parallaxOffset = ref(0)
const activeIndex = ref(0)
const activeTestimonial = computed(() => testimonials[activeIndex.value] || {})

const setActive = (i) => { activeIndex.value = i }

const handleScroll = () => {
    parallaxOffset.value = window.scrollY * 0.5
}

onMounted(() => {
    window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll)
})

const activeTab = ref('simpanan')
const currentIndex = ref(0)
const visibleCount = 4

const products = {
    simpanan: [
        {
            title: 'Simpanan Pokok',
            desc: 'Penyertaan modal awal, disetor sekali saat mendaftar',
            iconBg: '#FAECE7',
            iconColor: '#993C1D',
            icon: 'pokok',
            link: '#'
        },
        {
            title: 'Simpanan Wajib',
            desc: 'Setoran rutin bulanan, tambah porsi kepemilikan koperasi',
            iconBg: '#EEEDFE',
            iconColor: '#534AB7',
            icon: 'wajib',
            link: '#'
        },
        {
            title: 'Tabungan Anggota',
            desc: 'Dana titipan fleksibel, bisa ditarik kapan saja',
            iconBg: '#E1F5EE',
            iconColor: '#0F6E56',
            icon: 'tabungan',
            link: '#'
        },
        {
            title: 'Tabungan Berjangka',
            desc: 'Investasi tenor 3–12 bulan dengan bagi hasil mudharabah',
            iconBg: '#FAEEDA',
            iconColor: '#854F0B',
            icon: 'berjangka',
            link: '#'
        },
        {
            title: 'Tabungan Ibadah',
            desc: 'Rencanakan dana haji, umrah, qurban secara bertahap',
            iconBg: '#E1F5EE',
            iconColor: '#0F6E56',
            icon: 'ibadah',
            link: '#'
        },
        {
            title: 'Tabungan Sosial',
            desc: 'Dana kebajikan anggota untuk pembiayaan Qardhul Hasan',
            iconBg: '#FBEAF0',
            iconColor: '#993556',
            icon: 'sosial',
            link: '#'
        },
    ],
    pembiayaan: [
        {
            title: 'Pembiayaan Murabahah',
            desc: 'Beli aset kebutuhan tanpa riba, cicilan tetap dan transparan',
            iconBg: '#E1F5EE',
            iconColor: '#0F6E56',
            icon: 'murabahah',
            link: '#'
        },
        {
            title: 'Penjualan AMDK',
            desc: 'Air minum via agen stokist dengan akad Wakalah bil Ujrah',
            iconBg: '#E6F1FB',
            iconColor: '#185FA5',
            icon: 'amdk',
            link: '#'
        },
    ]
}

const current = computed(() => products[activeTab.value])
const maxIndex = computed(() => Math.max(0, current.value.length - visibleCount))
const visible = computed(() => current.value.slice(currentIndex.value, currentIndex.value + visibleCount))

const setTab = (tab) => {
    activeTab.value = tab
    currentIndex.value = 0
}
const prev = () => { if (currentIndex.value > 0) currentIndex.value-- }
const next = () => { if (currentIndex.value < maxIndex.value) currentIndex.value++ }
</script>

<template>
    <Base title="Beranda">
        <div class="h-full w-full">
            <section class="hero-section grid grid-cols-2 items-center h-full w-full relative overflow-hidden">
                <div class="flex flex-col gap-4 justify-left xl:pl-12 px-40 pt-50">
                    <div class="absolute inset-0 -z-10" :style="{ transform: `translateY(${parallaxOffset}px)` }">
                        <img src="/public/images/home/alhikmah.webp" class="w-full h-full object-cover"
                            alt="Hero Background">
                        <div class="absolute inset-0 bg-white dark:bg-dark-text opacity-85"></div>
                    </div>
                    <svg class="w-sm h-100 absolute -z-10 dark:opacity-10 left-0 top-32 opacity-70"
                        xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <pattern id="tp" width="60" height="60" patternUnits="userSpaceOnUse">
                                <path d="M30,9 L33.6,26.4 L51,30 L33.6,33.6 L30,51 L26.4,33.6 L9,30 L26.4,26.4 Z"
                                    fill="#EDFFEC" />
                            </pattern>
                        </defs>
                        <rect fill="url(#tp)" width="100%" height="100%" />
                    </svg>

                    <h1 class="font-semibold text-6xl w-xl leading-20 tracking-wide dark:text-gray-100">Sejahtera <span
                            class="font-light">bersama</span> Koperasi Syariah Berkah</h1>
                    <p class="text-xl w-md tracking-wide leading-8 dark:text-gray-300">Rasakan aman, nyaman, dan
                        berkahnya bertransaksi
                        sesuai Al-Qur'an dan Sunnah</p>
                    <div class="flex gap-4">
                        <Link href="/auth/register"
                            class="bg-secondary text-white px-6 py-3 rounded-xl hover:bg-brand-900">
                            Masuk Sekarang
                        </Link>
                        <Link href="#"
                            class="bg-light-bg font-semibold text-dark-text px-6 py-3 rounded-xl hover:bg-gray-200">
                            Pelajari Lebih Lanjut
                        </Link>
                    </div>
                </div>
                <div class="bg-primary h-full w-full rounded-tl-[100px] xl:opacity-100 opacity-0">
                    <img src="/public/images/home/hero_02.webp"
                        class="w-40 h-40 rounded-full object-cover absolute top-56 -ml-20 z-11" alt="">
                    <div class="w-36 bg-secondary h-36 absolute z-10 top-64 -ml-8 rounded-full"></div>
                    <img src="/public/images/home/hero_01.webp"
                        class="w-80 h-80 rounded-full object-cover absolute top-40 z-12 right-80" alt="">
                    <svg class="w-80 h-80 object-cover absolute top-52 z-11 right-72 opacity-70"
                        xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <pattern id="sp" width="40" height="40" patternUnits="userSpaceOnUse">
                                <path d="M24,12 L26.5,20.5 L35,24 L26.5,27.5 L24,36 L21.5,27.5 L13,24 L21.5,20.5 Z"
                                    fill="#CEE381" />
                            </pattern>
                        </defs>
                        <rect fill="url(#sp)" width="100%" height="100%" />
                    </svg>
                    <rect fill="url(#sp)" width="200" height="200" />
                    <img src="/public/images/home/hero_04.webp"
                        class="w-40 h-40 rounded-full object-cover absolute ml-52 bottom-52 z-11" alt="">
                    <div class="w-40 bg-green-accent h-40 absolute z-10 bottom-52 ml-64 rounded-full"></div>
                    <img src="/public/images/home/hero_03.webp"
                        class="w-44 h-44 rounded-full object-cover absolute right-72 bottom-16 z-11" alt="">

                    <div class="w-32 bg-accent h-32 absolute z-10 bottom-8 -right-16 rounded-full"></div>
                </div>
            </section>

            <section class="features-strip bg-white dark:bg-dark-text relative overflow-x-hidden">
                <div class="marquee group">
                    <!-- track 1 -->
                    <ul class="marquee__inner flex w-max text-2xl font-semibold">
                        <li v-for="item in marqueeItems"
                            class="shrink-0 border-l-2 border-stroke dark:border-gray-600 dark:text-gray-300 px-12 py-10 whitespace-nowrap">
                            {{ item.content }} <span :class="item.color">{{ item.highlight }}</span> <span
                                v-if="item.after"> {{ item.after }}</span>
                        </li>
                        <li v-for="item in marqueeItems"
                            class="shrink-0 border-l-2 border-stroke dark:border-gray-600 dark:text-gray-300 px-12 py-10 whitespace-nowrap">
                            {{ item.content }} <span :class="item.color">{{ item.highlight }}</span> <span
                                v-if="item.after"> {{ item.after }}</span>
                        </li>
                    </ul>
                    <!-- track 2 -->
                    <ul class="marquee__inner flex w-max text-2xl font-semibold" aria-hidden="true">
                        <li v-for="item in marqueeItems"
                            class="shrink-0 border-l-2 border-stroke dark:border-gray-600 dark:text-gray-300 px-12 py-10 whitespace-nowrap">
                            {{ item.content }} <span :class="item.color">{{ item.highlight }}</span> <span
                                v-if="item.after"> {{ item.after }}</span>
                        </li>
                        <li v-for="item in marqueeItems"
                            class="shrink-0 border-l-2 border-stroke dark:border-gray-600 dark:text-gray-300 px-12 py-10 whitespace-nowrap">
                            {{ item.content }} <span :class="item.color">{{ item.highlight }}</span> <span
                                v-if="item.after"> {{ item.after }}</span>
                        </li>
                    </ul>
                </div>
            </section>

            <section
                class="flex xl:flex-row flex-col-reverse gap-10 h-fit w-full bg-linear-to-r from-white to-brand-900/50 dark:from-primary dark:to-primary/50 p-52 items-center justify-between">
                <div class="flex flex-col gap-8">
                    <h1 class="font-accent text-3xl font-bold w-xl dark:text-gray-300">Mengapa memilih kami daripada
                        bank konvensional
                        &
                        koperasi biasa?</h1>
                    <ul class="flex flex-col font-body gap-8">
                        <li class="flex items-center gap-6">
                            <div class="bg-success-50 dark:bg-success-100 rounded-lg p-4 drop-accent shadow-2xl">
                                <span class="icon-[streamline--islam]"
                                    style="width: 28px; height: 28px; color: #007943;"></span>
                            </div>
                            <p class="text-2xl w-md dark:text-gray-100">Transaksi 100% sesuai fatwa <span
                                    class="font-bold">DSN-MUI</span> (tanpa riba,
                                gharar,
                                maysir)
                            </p>
                        </li>
                        <li class="flex items-center gap-6">
                            <div class="bg-success-50 dark:bg-success-100 rounded-lg p-4 drop-accent shadow-2xl">
                                <span class="icon-[proicons--bank]"
                                    style="width: 28px; height: 28px; color: #007943;"></span>
                            </div>
                            <p class="text-2xl w-md dark:text-gray-100">Pembiayaan cepat tanpa BI checking ketat &
                                <span class="font-bold">angsuran ringan</span>
                            </p>
                        </li>
                        <li class="flex items-center gap-6">
                            <div class="bg-success-50 dark:bg-success-100 rounded-lg p-4 drop-accent shadow-2xl">
                                <span class="icon-[lucide--wallet]"
                                    style="width: 28px; height: 28px; color: #007943;"></span>
                            </div>
                            <p class="text-2xl w-md dark:text-gray-100">Simpan uang Anda dengan aman dan sesuai <span
                                    class="font-bold">prinsip syariah</span></p>
                        </li>
                    </ul>
                </div>
                <h1
                    class="font-semibold w-lg text-right text-5xl text-dark-text dark:text-white/80 leading-20 tracking-wide">
                    <span class="font-normal">Mengapa memilih</span> Koperasi Syariah Berkah?
                </h1>
            </section>

            <section
                class="bg-white grid xl:grid-cols-2 grid-cols-1 gap-4 h-fit py-36 px-32 relative dark:bg-brand-950">
                <div>
                    <svg class="w-60 h-60 absolute z-10 dark:opacity-20 top-20 opacity-50"
                        xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <pattern id="sp2" width="60" height="60" patternUnits="userSpaceOnUse">
                                <path d="M36,18 L40,31 L52,36 L40,41 L36,54 L32,41 L20,36 L32,31 Z" fill="#EDFFEC" />
                            </pattern>
                        </defs>
                        <rect fill="url(#sp2)" width="100%" height="100%" />
                    </svg>
                    <svg class="w-60 h-60 absolute z-10 bottom-8 right-0 opacity-60" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <pattern id="sp3" width="40" height="40" patternUnits="userSpaceOnUse">
                                <path d="M24,12 L26.5,20.5 L35,24 L26.5,27.5 L24,36 L21.5,27.5 L13,24 L21.5,20.5 Z"
                                    fill="#CEE381" />
                            </pattern>
                        </defs>
                        <rect fill="url(#sp3)" width="100%" height="100%" />
                    </svg>
                    <svg class="w-32 h-auto absolute z-10 bottom-36 left-16 opacity-40"
                        xmlns="http://www.w3.org/2000/svg">
                        <circle cx="64" cy="64" r="56" fill="none" stroke="#007031" stroke-width="6" />
                    </svg>
                    <img src="/public/images/home/about_us.webp" class="w-3xl h-full" />
                </div>
                <div class="flex flex-col gap-6 mt-auto">
                    <h2 class="text-3xl font-bold text-primary dark:text-green-accent">Tentang Kami</h2>
                    <p class="text-xl text-dark-text leading-8 dark:text-gray-300">Koperasi Syariah Berkah didirikan
                        pada tahun
                        2020 dengan tujuan memberikan solusi keuangan yang sesuai dengan prinsip-prinsip syariah
                        kepada masyarakat. Kami berkomitmen untuk menyediakan layanan keuangan yang transparan,
                        adil, dan bermanfaat bagi seluruh anggota.</p>
                </div>
            </section>

            <section class="bg-white px-32 py-36 flex flex-col gap-8 dark:bg-brand-950">
                <!-- Header row -->
                <div class="flex items-center justify-between">
                    <h2 class="text-3xl font-bold text-primary dark:text-green-accent">Produk Koperasi</h2>
                    <div class="flex items-center gap-3">
                        <!-- Tab buttons -->
                        <button @click="setTab('simpanan')"
                            :class="activeTab === 'simpanan'
                                ? 'bg-secondary text-white'
                                : 'bg-white text-dark-text dark:bg-brand-900 border border-stroke dark:text-gray-300 hover:bg-gray-200'"
                            class="px-5 py-2 rounded-xl text-sm font-medium transition-colors duration-200 flex items-center gap-1">
                            Simpanan & Tabungan
                            <span class="icon-[tabler--chevron-right]" style="width:16px;height:16px;"></span>
                        </button>
                        <button @click="setTab('pembiayaan')"
                            :class="activeTab === 'pembiayaan'
                                ? 'bg-secondary text-white'
                                : 'bg-white text-dark-text border border-stroke dark:bg-brand-900 dark:text-gray-300 hover:bg-gray-200'"
                            class="px-5 py-2 rounded-xl text-sm font-medium transition-colors duration-200 flex items-center gap-1">
                            Pembiayaan & Penjualan
                            <span class="icon-[tabler--chevron-right]" style="width:16px;height:16px;"></span>
                        </button>
                        <!-- Prev/Next -->
                        <div class="flex gap-2 ml-4">
                            <button @click="prev" :disabled="currentIndex === 0"
                                class="w-9 h-9 rounded-full border border-stroke flex items-center justify-center dark:text-white transition-colors duration-200"
                                :class="currentIndex === 0 ? 'opacity-30 cursor-not-allowed' : 'hover:bg-light-bg dark:hover:bg-brand-900'">
                                <span class="icon-[tabler--chevron-left]" style="width:18px;height:18px;"></span>
                            </button>
                            <button @click="next" :disabled="currentIndex >= maxIndex"
                                class="w-9 h-9 rounded-full border border-stroke flex items-center justify-center dark:text-white transition-colors duration-200"
                                :class="currentIndex >= maxIndex ? 'opacity-30 cursor-not-allowed' : 'hover:bg-light-bg dark:hover:bg-brand-900'">
                                <span class="icon-[tabler--chevron-right]" style="width:18px;height:18px;"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Cards -->
                <ul class="grid grid-cols-4 gap-6">
                    <li v-for="(p, i) in visible" :key="p.title"
                        class="group duration-300 flex flex-col rounded-2xl overflow-hidden relative p-8 gap-4 cursor-pointer bg-light-bg hover:bg-secondary dark:bg-brand-900 dark:hover:bg-secondary">

                        <!-- Icon -->
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center flex-shrink-0"
                            :style="{ background: p.iconBg }">
                            <!-- Murabahah / Pembiayaan -->
                            <svg v-if="p.icon === 'murabahah'" width="32" height="32" viewBox="0 0 32 32" fill="none">
                                <rect x="4" y="10" width="24" height="16" rx="3" :stroke="p.iconColor"
                                    stroke-width="1.5" />
                                <path d="M10 10V8a6 6 0 0 1 12 0v2" :stroke="p.iconColor" stroke-width="1.5"
                                    stroke-linecap="round" />
                                <circle cx="16" cy="18" r="2.5" :fill="p.iconColor" />
                                <path d="M16 20.5v2" :stroke="p.iconColor" stroke-width="1.5" stroke-linecap="round" />
                            </svg>
                            <!-- Tabungan Anggota -->
                            <svg v-else-if="p.icon === 'tabungan'" width="32" height="32" viewBox="0 0 32 32"
                                fill="none">
                                <rect x="5" y="8" width="22" height="16" rx="3" :stroke="p.iconColor"
                                    stroke-width="1.5" />
                                <path d="M5 13h22" :stroke="p.iconColor" stroke-width="1.5" />
                                <rect x="9" y="17" width="6" height="3" rx="1" :fill="p.iconColor" />
                            </svg>
                            <!-- Simpanan Wajib -->
                            <svg v-else-if="p.icon === 'wajib'" width="32" height="32" viewBox="0 0 32 32" fill="none">
                                <rect x="5" y="8" width="22" height="16" rx="3" :stroke="p.iconColor"
                                    stroke-width="1.5" />
                                <path d="M5 13h22" :stroke="p.iconColor" stroke-width="1.5" />
                                <rect x="9" y="17" width="6" height="3" rx="1" :fill="p.iconColor" />
                                <path d="M20 17l2 2-2 2" :stroke="p.iconColor" stroke-width="1.5"
                                    stroke-linecap="round" />
                            </svg>
                            <!-- Simpanan Pokok -->
                            <svg v-else-if="p.icon === 'pokok'" width="32" height="32" viewBox="0 0 32 32" fill="none">
                                <path d="M16 5l2.5 7.5H26l-6.5 4.5 2.5 7.5L16 20l-6 4.5 2.5-7.5L6 12.5h7.5L16 5z"
                                    :stroke="p.iconColor" stroke-width="1.5" stroke-linejoin="round" />
                            </svg>
                            <!-- Tabungan Berjangka -->
                            <svg v-else-if="p.icon === 'berjangka'" width="32" height="32" viewBox="0 0 32 32"
                                fill="none">
                                <circle cx="16" cy="16" r="11" :stroke="p.iconColor" stroke-width="1.5" />
                                <path d="M16 10v6l4 2" :stroke="p.iconColor" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                            <!-- Tabungan Ibadah -->
                            <svg v-else-if="p.icon === 'ibadah'" width="32" height="32" viewBox="0 0 32 32" fill="none">
                                <path d="M16 4C16 4 8 9 8 17a8 8 0 0 0 16 0c0-8-8-13-8-13z" :stroke="p.iconColor"
                                    stroke-width="1.5" stroke-linejoin="round" />
                                <path d="M12 17h8M16 13v8" :stroke="p.iconColor" stroke-width="1.5"
                                    stroke-linecap="round" />
                            </svg>
                            <!-- Tabungan Sosial -->
                            <svg v-else-if="p.icon === 'sosial'" width="32" height="32" viewBox="0 0 32 32" fill="none">
                                <path d="M16 6a4 4 0 1 1 0 8 4 4 0 0 1 0-8z" :stroke="p.iconColor" stroke-width="1.5" />
                                <path d="M8 26c0-4.4 3.6-8 8-8s8 3.6 8 8" :stroke="p.iconColor" stroke-width="1.5"
                                    stroke-linecap="round" />
                                <path d="M22 14a4 4 0 1 0 0-8" :stroke="p.iconColor" stroke-width="1.5"
                                    stroke-linecap="round" />
                                <path d="M26 26c0-3.3-1.8-6.2-4.5-7.7" :stroke="p.iconColor" stroke-width="1.5"
                                    stroke-linecap="round" />
                            </svg>
                            <!-- AMDK -->
                            <svg v-else-if="p.icon === 'amdk'" width="32" height="32" viewBox="0 0 32 32" fill="none">
                                <rect x="11" y="4" width="10" height="24" rx="5" :stroke="p.iconColor"
                                    stroke-width="1.5" />
                                <path d="M11 14h10" :stroke="p.iconColor" stroke-width="1.5" />
                                <path d="M14 4V2h4v2" :stroke="p.iconColor" stroke-width="1.5" stroke-linecap="round" />
                            </svg>
                        </div>

                        <!-- Text -->
                        <h3
                            class="font-medium text-lg z-2 transition-colors duration-300 text-dark-text group-hover:text-white dark:text-gray-100">
                            {{ p.title }}
                        </h3>
                        <p
                            class="text-sm leading-6 z-2 transition-colors duration-300 text-dark-text/60 group-hover:text-white/70 dark:text-gray-400">
                            {{ p.desc }}
                        </p>

                        <!-- Arrow -->
                        <Link :href="p.link"
                            class="rounded-full w-fit p-2 mt-auto z-2 transition-colors duration-300 bg-light-accent text-dark-text group-hover:text-white group-hover:bg-white/20">
                            <span class="icon-[tabler--arrow-right]" style="width:28px;height:28px;"></span>
                        </Link>
                    </li>
                </ul>
            </section>

            <section
                class="bg-light-bg dark:bg-primary/90 h-fit flex flex-col items-center py-36 px-32 gap-14 relative">
                <svg class="w-60 h-60 absolute z-1 -top-20 right-0 opacity-60" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern id="sp3" width="50" height="50" patternUnits="userSpaceOnUse">
                            <path d="M24,12 L26.5,20.5 L35,24 L26.5,27.5 L24,36 L21.5,27.5 L13,24 L21.5,20.5 Z"
                                fill="#CEE381" />
                        </pattern>
                    </defs>
                    <rect fill="url(#sp3)" width="100%" height="100%" />
                </svg>
                <h1 class="text-5xl text-secondary font-semibold dark:text-gray-300">Apa Kata Anggota Kami?</h1>

                <div class="flex flex-col">
                    <div
                        class="bg-white dark:bg-brand-950 dark:border dark:border-stroke px-12 pt-10 pb-32 rounded-2xl xl:w-6xl w-full h-72 flex gap-6 dark:text-gray-300">
                        <span class="text-6xl font-semibold">“</span>
                        <p class="font-body text-2xl leading-10 tracking-wide text-justify">
                            {{ activeTestimonial.quote || 'Belum ada testimoni.' }}
                        </p>
                    </div>
                    <div
                        class="w-0 h-0 ml-12 border-l-20 border-l-transparent border-r-20 border-r-transparent border-t-30 border-t-white dark:border-t-stroke">
                    </div>

                    <div class="flex mt-10 px-10 justify-between">
                        <template v-for="(t, i) in testimonials" :key="i">
                            <button type="button" class="flex items-center gap-2 text-gray-700 dark:text-gray-400"
                                @click="setActive(i)">
                                <span
                                    class="mr-3 overflow-hidden rounded-full border border-stroke dark:bg-gray-200 h-16 w-16 ring-2 bg-white flex items-center justify-center ring-transparent"
                                    :class="i === activeIndex ? 'ring-secondary' : ''">
                                    <UserIcon />
                                </span>
                                <div v-if="i === activeIndex" class="flex flex-col text-left">
                                    <span class="text-dark-text dark:text-gray-200">{{ t.name }}</span>
                                    <span class="text-dark-text font-semibold dark:text-gray-300">{{ t.title }}</span>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>
            </section>

            <section class="w-full h-fit px-32 py-35 flex xl:flex-row flex-col gap-12 bg-primary">
                <div class="flex flex-col gap-4">
                    <p class="text-brand-300">MULAI SEKARANG</p>
                    <h2 class="text-3xl font-bold text-white">Bergabunglah bersama kami dan rasakan manfaatnya
                        sekarang
                        juga!</h2>
                    <p class="text-gray-200">Koperasi Syariah Berkah akan membantu memenuhi kebutuhan finansial Anda
                        dengan prinsip
                        syariah yang terpercaya.</p>
                </div>
                <div class="flex gap-4 mx-auto">
                    <Link href="/auth/register"
                        class="px-8 py-4 my-auto bg-secondary rounded-xl text-white hover:bg-brand-800">
                        Daftar Sekarang</Link>
                    <Link href="/"
                        class="px-8 py-4 my-auto bg-white/70 font-medium rounded-xl text-dark-text hover:bg-gray-200 flex items-center gap-2">
                        Pelajari Lebih Lanjut <span class="icon-[system-uicons--arrow-top-right]"
                            style="width: 24px; height: 24px;"></span></Link>
                </div>
            </section>
            <Footer />
            <HelpButton />
        </div>
    </Base>
</template>
