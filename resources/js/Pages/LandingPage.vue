<script setup>
import Base from '@/Layouts/Base.vue'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import Footer from '@/Layouts/Footer.vue'
import { Link } from '@inertiajs/vue3'
import CardProduct from '@/Components/CardProduct.vue'
import UserIcon from '@/Icons/UserIcon.vue'

const testimonials = [
    { quote: 'Berkat pembiayaan murabahah, saya bisa renovasi rumah tanpa riba. Angsuran ringan dan sesuai syariat.', name: 'Diana Latifah', title: 'Dosen Akuntansi'},
    { quote: 'Layanannya cepat, transparan, dan bagi hasilnya adil. Sangat membantu keuangan keluarga.', name: 'Rifqi Maulana', title: 'Staf Laboratorium'},
    { quote: 'Saya suka bagi hasilnya fair dan sesuai syariah.', name: 'Oneng', title: 'Pemilik Kantin'},
    { quote: 'Prosesnya cepat, tanpa riba, dan jelas akadnya.', name: 'Andi Nugraha', title: 'Teknisi'},
    { quote: 'Simpanan dan pembiayaannya bikin tenang.', name: 'Siti Rahma', title: 'Administrasi'},
]

const marqueeItems = [
    {
        content: 'Simpanan',
        highlight: 'Syariah',
        color: 'text-green-700'
    },
    {
        content:'Pembiayaan Bebas',
        highlight: 'Riba',
        color: 'text-accent'
    },
    {
        content: 'Keuntungan',
        highlight: 'Halal',
        after: 'Dibagi Rata',
        color: 'text-blue-accent'
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
</script>

<template>
    <Base title="Beranda">
        <div class="landing-page-content h-full w-full">
            <section class="hero-section flex xl:flex-row flex-col items-center h-full w-full relative overflow-hidden">
                <div class="flex flex-col gap-4 justify-left xl:pl-12 xl:pr-40 px-40 pt-50">
                    <div class="absolute inset-0 -z-10" :style="{ transform: `translateY(${parallaxOffset}px)` }">
                        <img src="/public/images/home/polban.avif" class="w-full h-full object-cover"
                            alt="Hero Background">
                        <div class="absolute inset-0 bg-white dark:bg-dark-text opacity-85"></div>
                    </div>
                    <img src="/public/images/home/ornament_blue.svg" class="w-72 h-auto absolute -z-10 dark:opacity-10 left-0 top-24"
                        alt="">

                    <h1 class="font-semibold text-5xl w-lg leading-20 tracking-wide dark:text-gray-100">Sejahtera <span
                            class="font-light">bersama</span> Koperasi Syariah Warga Polban</h1>
                    <p class="text-xl w-md tracking-wide leading-8 dark:text-gray-300">Rasakan aman, nyaman, dan berkahnya bertransaksi
                        sesuai Al-Qur'an dan Sunnah</p>
                    <div class="flex gap-4">
                        <Link href="/register" class="bg-secondary text-white px-6 py-3 rounded-xl hover:bg-brand-900">
                            Daftar Menjadi Anggota
                        </Link>
                        <Link href="#"
                            class="bg-light-bg font-semibold text-dark-text px-6 py-3 rounded-xl hover:bg-gray-200">
                            Pelajari Lebih Lanjut
                        </Link>
                    </div>
                </div>
                <div class="bg-primary h-full w-full rounded-tl-[100px] xl:opacity-100 opacity-0">
                    <img src="/public/images/home/hero_02.avif"
                        class="w-40 h-40 rounded-full object-cover absolute top-56 -ml-20 z-11" alt="">
                    <div class="w-36 bg-secondary h-36 absolute z-10 top-64 -ml-8 rounded-full"></div>
                    <img src="/public/images/home/hero_01.avif"
                        class="w-80 h-80 rounded-full object-cover absolute top-56 z-12 right-96" alt="">
                    <img src="/public/images/home/ornament_orange.svg"
                        class="w-80 h-80 object-cover absolute top-72 z-11 right-72" alt="">
                    <img src="/public/images/home/hero_04.avif"
                        class="w-40 h-40 rounded-full object-cover absolute ml-52 bottom-52 z-11" alt="">
                    <div class="w-40 bg-blue-accent h-40 absolute z-10 bottom-52 ml-64 rounded-full"></div>
                    <img src="/public/images/home/hero_03.avif"
                        class="w-44 h-44 rounded-full object-cover absolute right-96 bottom-16 z-11" alt="">
                    <div class="w-32 bg-accent h-32 absolute z-10 bottom-8 -right-16 rounded-full"></div>
                </div>
            </section>

            <section class="features-strip bg-white dark:bg-dark-text relative overflow-x-hidden">
                <div class="marquee group">
                    <!-- track 1 -->
                    <ul class="marquee__inner flex w-max text-2xl font-semibold">
                        <li v-for="item in marqueeItems" class="shrink-0 border-l-2 border-stroke dark:border-gray-600 dark:text-gray-300 px-12 py-10 whitespace-nowrap">
                            {{ item.content }} <span :class="item.color">{{ item.highlight }}</span> <span v-if="item.after"> {{ item.after }}</span>
                        </li>
                        <li v-for="item in marqueeItems" class="shrink-0 border-l-2 border-stroke dark:border-gray-600 dark:text-gray-300 px-12 py-10 whitespace-nowrap">
                            {{ item.content }} <span :class="item.color">{{ item.highlight }}</span> <span v-if="item.after"> {{ item.after }}</span>
                        </li>
                    </ul>
                    <!-- track 2 -->
                    <ul class="marquee__inner flex w-max text-2xl font-semibold" aria-hidden="true">
                        <li v-for="item in marqueeItems" class="shrink-0 border-l-2 border-stroke dark:border-gray-600 dark:text-gray-300 px-12 py-10 whitespace-nowrap">
                            {{ item.content }} <span :class="item.color">{{ item.highlight }}</span> <span v-if="item.after"> {{ item.after }}</span>
                        </li>
                        <li v-for="item in marqueeItems" class="shrink-0 border-l-2 border-stroke dark:border-gray-600 dark:text-gray-300 px-12 py-10 whitespace-nowrap">
                            {{ item.content }} <span :class="item.color">{{ item.highlight }}</span> <span v-if="item.after"> {{ item.after }}</span>
                        </li>
                    </ul>
                </div>
            </section>

            <section
                class="flex xl:flex-row flex-col-reverse gap-10 h-fit w-full bg-linear-to-r from-white to-brand-900/50 dark:from-primary dark:to-primary/50 p-52 items-center justify-between">
                <div class="flex flex-col gap-8">
                    <h1 class="font-accent text-3xl font-bold w-xl dark:text-gray-300">Mengapa memilih kami daripada bank konvensional
                        &
                        koperasi biasa?</h1>
                    <ul class="flex flex-col gap-8">
                        <li class="flex items-center gap-6">
                            <div class="bg-success-50 dark:bg-success-100 rounded-lg p-4 drop-accent shadow-2xl">
                                <span class="icon-[streamline--islam]"
                                    style="width: 28px; height: 28px; color: #007943;"></span>
                            </div>
                            <p class="text-2xl w-md dark:text-gray-100">Transaksi 100% sesuai fatwa DSN-MUI (tanpa riba, gharar,
                                maysir)
                            </p>
                        </li>
                        <li class="flex items-center gap-6">
                            <div class="bg-success-50 dark:bg-success-100 rounded-lg p-4 drop-accent shadow-2xl">
                                <span class="icon-[proicons--bank]"
                                    style="width: 28px; height: 28px; color: #007943;"></span>
                            </div>
                            <p class="text-2xl w-md dark:text-gray-100">Pembiayaan cepat tanpa BI checking ketat & angsuran ringan</p>
                        </li>
                        <li class="flex items-center gap-6">
                            <div class="bg-success-50 dark:bg-success-100 rounded-lg p-4 drop-accent shadow-2xl">
                                <span class="icon-[lucide--wallet]"
                                    style="width: 28px; height: 28px; color: #007943;"></span>
                            </div>
                            <p class="text-2xl w-md dark:text-gray-100">Simpan uang Anda dengan aman dan sesuai prinsip syariah</p>
                        </li>
                    </ul>
                </div>
                <h1 class="font-semibold w-lg text-right text-5xl text-dark-text dark:text-white/80 leading-20 tracking-wide"><span
                        class="font-normal">Mengapa memilih</span> Koperasi Syariah Warga Polban?</h1>
            </section>

            <section class="grid xl:grid-cols-2 grid-cols-1 h-fit py-36 px-32 relative dark:bg-brand-950">
                <div>
                    <img src="/public/images/home/ornament_blue.svg" class="w-60 h-auto absolute z-10 dark:opacity-20 top-20" alt="">
                    <img src="/public/images/home/ornament_orange.svg"
                        class="w-60 h-auto absolute z-10 bottom-8 right-0" alt="">
                    <img src="/public/images/home/ornament_circle.svg"
                        class="w-32 h-auto absolute z-10 bottom-36 left-16" alt="">
                    <img src="/public/images/home/about_us.png" class="w-3xl h-full" />
                </div>
                <div class="flex flex-col gap-6 mt-auto">
                    <h2 class="text-3xl font-bold text-primary dark:text-blue-accent">Tentang Kami</h2>
                    <p class="text-lg text-dark-text leading-8 dark:text-gray-300">Koperasi Syariah Warga Polban didirikan pada tahun
                        2024 dengan tujuan memberikan solusi keuangan yang sesuai dengan prinsip-prinsip syariah
                        kepada masyarakat Politeknik Negeri Bandung. Kami berkomitmen untuk menyediakan layanan
                        keuangan yang transparan, adil, dan bermanfaat bagi seluruh anggota kami.</p>
                </div>
            </section>

            <section class="px-32 py-36 flex flex-col gap-8 dark:bg-brand-950">
                <h2 class="text-3xl font-bold text-primary dark:text-blue-accent">Produk Koperasi</h2>
                <ul class="flex xl:flex-row flex-col gap-6">
                    <CardProduct productImage="/images/home/product_murabaha.avif" productTitle="Pembiayaan Murabahah"
                        productLink="#" :main="true" />
                    <CardProduct productImage="/images/home/product_saving.avif" productTitle="Simpanan Sukarela"
                        productLink="#" :main="false" />
                    <CardProduct productImage="/images/home/hero_01.avif" productTitle="Simpanan Wajib" productLink="#"
                        :main="false" />
                    <CardProduct productImage="/images/home/hero_02.avif" productTitle="Simpanan Pokok" productLink="#"
                        :main="false" />
                </ul>
            </section>

            <section class="bg-light-bg dark:bg-primary/90 h-fit flex flex-col items-center py-36 px-32 gap-14 relative">
                <img src="/public/images/home/ornament_orange.svg" class="w-60 h-auto absolute z-1 -top-20 right-0"
                    alt="">
                <h1 class="text-5xl text-secondary font-semibold dark:text-blue-accent">Apa Kata Anggota Kami?</h1>

                <div class="flex flex-col">
                    <div class="bg-white dark:bg-brand-950 dark:border dark:border-stroke px-12 pt-10 pb-32 rounded-2xl xl:w-6xl w-full h-72 flex gap-6 dark:text-gray-300">
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
                                    class="mr-3 overflow-hidden rounded-full dark:bg-gray-200 h-16 w-16 ring-2 bg-white flex items-center justify-center ring-transparent"
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
                    <p class="text-gray-200">Koperasi Syariah Polban akan membantu memenuhi kebutuhan finansial Anda dengan prinsip syariah yang terpercaya.</p>
                </div>
                <div class="flex gap-4 mx-auto">
                    <Link href="/auth/register" class="px-8 py-4 my-auto bg-secondary rounded-xl text-white hover:bg-brand-800">Daftar Sekarang</Link>
                    <Link href="/" class="px-8 py-4 my-auto bg-white/70 font-medium rounded-xl text-dark-text hover:bg-gray-200 flex items-center gap-2">Pelajari Lebih Lanjut <span class="icon-[system-uicons--arrow-top-right]" style="width: 24px; height: 24px;"></span></Link>
                </div>
            </section>
        <Footer />
        </div>
    </Base>
</template>
