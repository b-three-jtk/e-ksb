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

const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1280)
const handleResize = () => { windowWidth.value = window.innerWidth }
onMounted(() => window.addEventListener('resize', handleResize))
onUnmounted(() => window.removeEventListener('resize', handleResize))

const visibleCount = computed(() => {
    if (windowWidth.value < 640) return 1
    if (windowWidth.value < 1024) return 2
    return 4
})

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
    ]
}

const current = computed(() => products[activeTab.value])
const maxIndex = computed(() => Math.max(0, current.value.length - visibleCount.value))
const visible = computed(() => current.value.slice(currentIndex.value, currentIndex.value + visibleCount.value))

const setTab = (tab) => {
    activeTab.value = tab
    currentIndex.value = 0
}
const prev = () => { if (currentIndex.value > 0) currentIndex.value-- }
const next = () => { if (currentIndex.value < maxIndex.value) currentIndex.value++ }
</script>

<template>
    <Base title="Beranda">
        <div class="h-full w-full overflow-x-hidden">
            <section class="hero-section grid grid-cols-1 h-screen lg:grid-cols-2 items-center w-full relative overflow-hidden">
                <div class="flex flex-col gap-4 px-6 sm:px-10 md:px-16 lg:pl-12 xl:px-40 pt-40 sm:pt-24 md:pt-32 lg:pt-40 xl:pt-50 pb-16 lg:pb-12">
                    <div class="absolute inset-0 -z-10" :style="{ transform: `translateY(${parallaxOffset}px)` }">
                        <img src="/public/images/home/alhikmah.webp" class="w-full h-full object-cover"
                            alt="Hero Background">
                        <div class="absolute inset-0 bg-white dark:bg-dark-text opacity-85"></div>
                    </div>
                    <svg class="hidden sm:block w-48 h-64 md:w-64 md:h-80 lg:w-sm lg:h-100 absolute -z-10 dark:opacity-10 left-0 top-20 lg:top-32 opacity-70"
                        xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <pattern id="tp" width="60" height="60" patternUnits="userSpaceOnUse">
                                <path d="M30,9 L33.6,26.4 L51,30 L33.6,33.6 L30,51 L26.4,33.6 L9,30 L26.4,26.4 Z"
                                    fill="#EDFFEC" />
                            </pattern>
                        </defs>
                        <rect fill="url(#tp)" width="100%" height="100%" />
                    </svg>

                    <h1 class="font-semibold text-3xl sm:text-4xl md:text-5xl lg:text-6xl max-w-xl leading-tight md:leading-snug lg:leading-20 tracking-wide dark:text-gray-100">Sejahtera <span
                            class="font-light">bersama</span> Koperasi Syariah Berkah</h1>
                    <p class="text-base sm:text-lg lg:text-xl max-w-md tracking-wide leading-7 lg:leading-8 dark:text-gray-300">Rasakan aman, nyaman, dan
                        berkahnya bertransaksi
                        sesuai Al-Qur'an dan Sunnah</p>
                    <div class="flex flex-wrap gap-3 sm:gap-4 pt-2">
                        <Link href="/auth/login"
                            class="bg-secondary text-white text-sm sm:text-base px-5 sm:px-6 py-2.5 sm:py-3 rounded-xl hover:bg-brand-900">
                            Masuk Sekarang
                        </Link>
                        <Link href="#"
                            class="bg-light-bg font-semibold text-dark-text text-sm sm:text-base px-5 sm:px-6 py-2.5 sm:py-3 rounded-xl hover:bg-gray-200">
                            Pelajari Lebih Lanjut
                        </Link>
                    </div>
                </div>

                <div class="hidden lg:block bg-primary h-full w-full rounded-tl-[100px] relative">
                    <img src="/public/images/home/hero_02.webp"
                        class="w-32 h-32 lg:w-40 lg:h-40 rounded-full object-cover absolute top-56 -ml-20 z-11" alt="">
                    <div class="w-36 bg-secondary h-36 absolute z-10 top-64 -ml-8 rounded-full"></div>
                    <img src="/public/images/home/hero_01.webp"
                        class="w-64 h-64 lg:w-80 lg:h-80 rounded-full object-cover absolute top-40 z-12 right-56 xl:right-80" alt="">
                    <svg class="w-64 h-64 lg:w-80 lg:h-80 object-cover absolute top-52 z-11 right-48 xl:right-72 opacity-70"
                        xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <pattern id="sp" width="40" height="40" patternUnits="userSpaceOnUse">
                                <path d="M24,12 L26.5,20.5 L35,24 L26.5,27.5 L24,36 L21.5,27.5 L13,24 L21.5,20.5 Z"
                                    fill="#CEE381" />
                            </pattern>
                        </defs>
                        <rect fill="url(#sp)" width="100%" height="100%" />
                    </svg>
                    <img src="/public/images/home/hero_04.webp"
                        class="w-32 h-32 lg:w-40 lg:h-40 rounded-full object-cover absolute ml-40 lg:ml-52 bottom-52 z-11" alt="">
                    <div class="w-40 bg-green-accent h-40 absolute z-10 bottom-52 ml-64 rounded-full"></div>
                    <img src="/public/images/home/hero_03.webp"
                        class="w-36 h-36 lg:w-44 lg:h-44 rounded-full object-cover absolute right-48 xl:right-72 bottom-16 z-11" alt="">

                    <div class="w-32 bg-accent h-32 absolute z-10 bottom-8 -right-16 rounded-full"></div>
                </div>
            </section>

            <section class="features-strip bg-white dark:bg-dark-text relative overflow-x-hidden">
                <div class="marquee group">
                    <!-- track 1 -->
                    <ul class="marquee__inner flex w-max text-base sm:text-xl md:text-2xl font-semibold">
                        <li v-for="item in marqueeItems"
                            class="shrink-0 border-l-2 border-stroke dark:border-gray-600 dark:text-gray-300 px-6 sm:px-8 md:px-12 py-6 sm:py-8 md:py-10 whitespace-nowrap">
                            {{ item.content }} <span :class="item.color">{{ item.highlight }}</span> <span
                                v-if="item.after"> {{ item.after }}</span>
                        </li>
                        <li v-for="item in marqueeItems"
                            class="shrink-0 border-l-2 border-stroke dark:border-gray-600 dark:text-gray-300 px-6 sm:px-8 md:px-12 py-6 sm:py-8 md:py-10 whitespace-nowrap">
                            {{ item.content }} <span :class="item.color">{{ item.highlight }}</span> <span
                                v-if="item.after"> {{ item.after }}</span>
                        </li>
                    </ul>
                    <!-- track 2 -->
                    <ul class="marquee__inner flex w-max text-base sm:text-xl md:text-2xl font-semibold" aria-hidden="true">
                        <li v-for="item in marqueeItems"
                            class="shrink-0 border-l-2 border-stroke dark:border-gray-600 dark:text-gray-300 px-6 sm:px-8 md:px-12 py-6 sm:py-8 md:py-10 whitespace-nowrap">
                            {{ item.content }} <span :class="item.color">{{ item.highlight }}</span> <span
                                v-if="item.after"> {{ item.after }}</span>
                        </li>
                        <li v-for="item in marqueeItems"
                            class="shrink-0 border-l-2 border-stroke dark:border-gray-600 dark:text-gray-300 px-6 sm:px-8 md:px-12 py-6 sm:py-8 md:py-10 whitespace-nowrap">
                            {{ item.content }} <span :class="item.color">{{ item.highlight }}</span> <span
                                v-if="item.after"> {{ item.after }}</span>
                        </li>
                    </ul>
                </div>
            </section>

            <section
                class="flex lg:flex-row flex-col-reverse gap-8 sm:gap-10 w-full bg-linear-to-r from-white to-brand-900/50 dark:from-primary dark:to-primary/50 px-6 sm:px-10 md:px-16 lg:px-24 xl:px-52 py-12 sm:py-16 md:py-24 xl:py-52 items-center justify-between">
                <div class="flex flex-col gap-6 sm:gap-8">
                    <h1 class="font-accent text-xl sm:text-2xl md:text-3xl font-bold max-w-xl dark:text-gray-300">Mengapa memilih kami daripada
                        bank konvensional
                        &
                        koperasi biasa?</h1>
                    <ul class="flex flex-col font-body gap-5 sm:gap-8">
                        <li class="flex items-center gap-4 sm:gap-6">
                            <div class="bg-success-50 dark:bg-success-100 rounded-lg p-3 sm:p-4 drop-accent shadow-2xl shrink-0">
                                <span class="icon-[streamline--islam]"
                                    style="width: 24px; height: 24px; color: #007943;"></span>
                            </div>
                            <p class="text-base sm:text-lg md:text-xl lg:text-2xl max-w-md dark:text-gray-100">Transaksi 100% sesuai fatwa <span
                                    class="font-bold">DSN-MUI</span> (tanpa riba,
                                gharar,
                                maysir)
                            </p>
                        </li>
                        <li class="flex items-center gap-4 sm:gap-6">
                            <div class="bg-success-50 dark:bg-success-100 rounded-lg p-3 sm:p-4 drop-accent shadow-2xl shrink-0">
                                <span class="icon-[proicons--bank]"
                                    style="width: 24px; height: 24px; color: #007943;"></span>
                            </div>
                            <p class="text-base sm:text-lg md:text-xl lg:text-2xl max-w-md dark:text-gray-100">Pembiayaan cepat tanpa BI checking ketat &
                                <span class="font-bold">angsuran ringan</span>
                            </p>
                        </li>
                        <li class="flex items-center gap-4 sm:gap-6">
                            <div class="bg-success-50 dark:bg-success-100 rounded-lg p-3 sm:p-4 drop-accent shadow-2xl shrink-0">
                                <span class="icon-[lucide--wallet]"
                                    style="width: 24px; height: 24px; color: #007943;"></span>
                            </div>
                            <p class="text-base sm:text-lg md:text-xl lg:text-2xl max-w-md dark:text-gray-100">Simpan uang Anda dengan aman dan sesuai <span
                                    class="font-bold">prinsip syariah</span></p>
                        </li>
                    </ul>
                </div>
                <h1
                    class="font-semibold max-w-lg text-left lg:text-right text-2xl sm:text-3xl md:text-4xl lg:text-5xl text-dark-text dark:text-white/80 leading-tight lg:leading-20 tracking-wide">
                    <span class="font-normal">Mengapa memilih</span> Koperasi Syariah Berkah?
                </h1>
            </section>

            <section
                class="bg-white grid lg:grid-cols-2 grid-cols-1 gap-6 lg:gap-4 h-fit py-16 sm:py-20 md:py-28 xl:py-36 px-6 sm:px-10 md:px-16 xl:px-32 relative dark:bg-brand-950">
                <div class="relative">
                    <svg class="hidden sm:block w-32 h-32 md:w-60 md:h-60 absolute z-10 dark:opacity-20 top-10 md:top-20 opacity-50"
                        xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <pattern id="sp2" width="60" height="60" patternUnits="userSpaceOnUse">
                                <path d="M36,18 L40,31 L52,36 L40,41 L36,54 L32,41 L20,36 L32,31 Z" fill="#EDFFEC" />
                            </pattern>
                        </defs>
                        <rect fill="url(#sp2)" width="100%" height="100%" />
                    </svg>
                    <svg class="hidden sm:block w-32 h-32 md:w-60 md:h-60 absolute z-10 bottom-8 right-0 opacity-60" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <pattern id="sp3" width="40" height="40" patternUnits="userSpaceOnUse">
                                <path d="M24,12 L26.5,20.5 L35,24 L26.5,27.5 L24,36 L21.5,27.5 L13,24 L21.5,20.5 Z"
                                    fill="#CEE381" />
                            </pattern>
                        </defs>
                        <rect fill="url(#sp3)" width="100%" height="100%" />
                    </svg>
                    <svg class="hidden md:block w-32 h-auto absolute z-10 bottom-36 left-16 opacity-40"
                        xmlns="http://www.w3.org/2000/svg">
                        <circle cx="64" cy="64" r="56" fill="none" stroke="#007031" stroke-width="6" />
                    </svg>
                    <img src="/public/images/home/about_us.webp" class="w-full max-w-3xl h-auto md:h-full mx-auto object-cover rounded-xl" />
                </div>
                <div class="flex flex-col gap-4 sm:gap-6 lg:mt-auto">
                    <h2 class="text-2xl sm:text-3xl font-bold text-primary dark:text-green-accent">Tentang Kami</h2>
                    <p class="text-base sm:text-lg md:text-xl text-dark-text leading-7 md:leading-8 dark:text-gray-300">Koperasi Syariah Berkah didirikan
                        pada tahun
                        2020 dengan tujuan memberikan solusi keuangan yang sesuai dengan prinsip-prinsip syariah
                        kepada masyarakat. Kami berkomitmen untuk menyediakan layanan keuangan yang transparan,
                        adil, dan bermanfaat bagi seluruh anggota.</p>
                </div>
            </section>

            <section class="bg-white px-6 sm:px-10 md:px-16 xl:px-32 py-16 sm:py-20 md:py-28 xl:py-36 flex flex-col gap-6 sm:gap-8 dark:bg-brand-950">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-2xl sm:text-3xl font-bold text-primary dark:text-green-accent">Produk Koperasi</h2>
                    <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                        <button @click="setTab('simpanan')"
                            :class="activeTab === 'simpanan'
                                ? 'bg-secondary text-white'
                                : 'bg-white text-dark-text dark:bg-brand-900 border border-stroke dark:text-gray-300 hover:bg-gray-200'"
                            class="px-3 sm:px-5 py-1.5 sm:py-2 rounded-xl text-xs sm:text-sm font-medium transition-colors duration-200 flex items-center gap-1">
                            Simpanan & Tabungan
                            <span class="icon-[tabler--chevron-right]" style="width:16px;height:16px;"></span>
                        </button>
                        <button @click="setTab('pembiayaan')"
                            :class="activeTab === 'pembiayaan'
                                ? 'bg-secondary text-white'
                                : 'bg-white text-dark-text border border-stroke dark:bg-brand-900 dark:text-gray-300 hover:bg-gray-200'"
                            class="px-3 sm:px-5 py-1.5 sm:py-2 rounded-xl text-xs sm:text-sm font-medium transition-colors duration-200 flex items-center gap-1">
                            Pembiayaan & Penjualan
                            <span class="icon-[tabler--chevron-right]" style="width:16px;height:16px;"></span>
                        </button>
                        <div class="flex gap-2 sm:ml-4">
                            <button @click="prev" :disabled="currentIndex === 0"
                                class="w-8 h-8 sm:w-9 sm:h-9 rounded-full border border-stroke flex items-center justify-center dark:text-white transition-colors duration-200"
                                :class="currentIndex === 0 ? 'opacity-30 cursor-not-allowed' : 'hover:bg-light-bg dark:hover:bg-brand-900'">
                                <span class="icon-[tabler--chevron-left]" style="width:18px;height:18px;"></span>
                            </button>
                            <button @click="next" :disabled="currentIndex >= maxIndex"
                                class="w-8 h-8 sm:w-9 sm:h-9 rounded-full border border-stroke flex items-center justify-center dark:text-white transition-colors duration-200"
                                :class="currentIndex >= maxIndex ? 'opacity-30 cursor-not-allowed' : 'hover:bg-light-bg dark:hover:bg-brand-900'">
                                <span class="icon-[tabler--chevron-right]" style="width:18px;height:18px;"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <ul class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                    <li v-for="(p, i) in visible" :key="p.title"
                        class="group duration-300 flex flex-col rounded-2xl overflow-hidden relative p-5 sm:p-6 md:p-8 gap-3 sm:gap-4 cursor-pointer bg-light-bg hover:bg-secondary dark:bg-brand-900 dark:hover:bg-secondary">

                        <!-- Icon -->
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl flex items-center justify-center flex-shrink-0"
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
                        </div>

                        <!-- Text -->
                        <h3
                            class="font-medium text-base sm:text-lg z-2 transition-colors duration-300 text-dark-text group-hover:text-white dark:text-gray-100">
                            {{ p.title }}
                        </h3>
                        <p
                            class="text-sm leading-6 z-2 transition-colors duration-300 text-dark-text/60 group-hover:text-white/70 dark:text-gray-400">
                            {{ p.desc }}
                        </p>

                        <!-- Arrow -->
                        <Link :href="p.link"
                            class="rounded-full w-fit p-2 mt-auto z-2 transition-colors duration-300 bg-light-accent text-dark-text group-hover:text-white group-hover:bg-white/20">
                            <span class="icon-[tabler--arrow-right]" style="width:24px;height:24px;"></span>
                        </Link>
                    </li>
                </ul>
            </section>

            <section
                class="bg-light-bg dark:bg-primary/90 h-fit flex flex-col items-center py-16 sm:py-24 md:py-28 xl:py-36 px-6 sm:px-10 md:px-16 xl:px-32 gap-8 sm:gap-10 md:gap-14 relative overflow-hidden">
                <svg class="hidden sm:block w-40 h-40 md:w-60 md:h-60 absolute z-1 -top-10 md:-top-20 right-0 opacity-60" xmlns="http://www.w3.org/2000/svg">
                    <rect fill="url(#sp3)" width="100%" height="100%" />
                </svg>
                <h1 class="text-2xl sm:text-3xl md:text-4xl xl:text-5xl text-secondary font-semibold dark:text-gray-300 text-center">Apa Kata Anggota Kami?</h1>

                <div class="flex flex-col w-full items-center">
                    <div
                        class="bg-white dark:bg-brand-950 dark:border dark:border-stroke px-6 sm:px-8 md:px-12 py-8 sm:py-10 rounded-2xl max-w-6xl w-full min-h-[12rem] flex gap-4 sm:gap-6 dark:text-gray-300">
                        <span class="text-3xl sm:text-5xl md:text-6xl font-semibold leading-none shrink-0">“</span>
                        <p class="font-body text-base sm:text-lg md:text-2xl leading-7 sm:leading-8 md:leading-10 tracking-wide text-justify">
                            {{ activeTestimonial.quote || 'Belum ada testimoni.' }}
                        </p>
                    </div>
                    <div
                        class="hidden sm:block w-0 h-0 ml-12 border-l-20 border-l-transparent border-r-20 border-r-transparent border-t-30 border-t-white dark:border-t-stroke">
                    </div>

                    <div class="flex flex-wrap mt-6 sm:mt-10 justify-center w-full gap-4">
                        <template v-for="(t, i) in testimonials" :key="i">
                            <button type="button" class="flex items-center gap-2 text-gray-700 dark:text-gray-400"
                                @click="setActive(i)">
                                <span
                                    class="mr-2 sm:mr-3 overflow-hidden rounded-full border border-stroke dark:bg-gray-200 h-12 w-12 sm:h-16 sm:w-16 ring-2 bg-white flex items-center justify-center ring-transparent"
                                    :class="i === activeIndex ? 'ring-secondary' : ''">
                                    <UserIcon />
                                </span>
                                <div v-if="i === activeIndex" class="flex flex-col text-left">
                                    <span class="text-sm sm:text-base text-dark-text dark:text-gray-200">{{ t.name }}</span>
                                    <span class="text-sm sm:text-base text-dark-text font-semibold dark:text-gray-300">{{ t.title }}</span>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>
            </section>

            <section class="w-full h-fit px-6 sm:px-10 md:px-16 xl:px-32 py-12 sm:py-16 md:py-24 xl:py-35 flex lg:flex-row flex-col gap-8 lg:gap-12 bg-primary">
                <div class="flex flex-col gap-3 sm:gap-4">
                    <p class="text-brand-300 text-sm sm:text-base">MULAI SEKARANG</p>
                    <h2 class="text-xl sm:text-2xl md:text-3xl font-bold text-white">Bergabunglah bersama kami dan rasakan manfaatnya
                        sekarang
                        juga!</h2>
                    <p class="text-sm sm:text-base text-gray-200">Koperasi Syariah Berkah akan membantu memenuhi kebutuhan finansial Anda
                        dengan prinsip
                        syariah yang terpercaya.</p>
                </div>
                <div class="flex gap-4 lg:mx-auto lg:my-auto">
                    <Link href="/"
                        class="px-6 sm:px-8 py-3 sm:py-4 my-auto bg-white/70 font-medium text-sm sm:text-base rounded-xl text-dark-text hover:bg-gray-200 flex items-center gap-2 w-fit">
                        Pelajari Lebih Lanjut <span class="icon-[system-uicons--arrow-top-right]"
                            style="width: 20px; height: 20px;"></span></Link>
                </div>
            </section>
            <Footer />
            <HelpButton />
        </div>
    </Base>
</template>
