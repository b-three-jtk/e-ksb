<script setup>
import Base from '../Layouts/Base.vue'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import Footer from '../Layouts/Footer.vue'

const testimonials = [
    { quote: 'Berkat pembiayaan murabahah, saya bisa renovasi rumah tanpa riba. Angsuran ringan dan sesuai syariat.', name: 'Diana Latifah', title: 'Dosen Akuntansi', avatar: '/images/user/owner.jpg' },
    { quote: 'Layanannya cepat, transparan, dan bagi hasilnya adil. Sangat membantu keuangan keluarga.', name: 'Rifqi Maulana', title: 'Staf Laboratorium', avatar: '/images/user/owner.jpg' },
    { quote: 'Saya suka bagi hasilnya fair dan sesuai syariah.', name: 'Oneng', title: 'Pemilik Kantin', avatar: '/images/user/owner.jpg' },
    { quote: 'Prosesnya cepat, tanpa riba, dan jelas akadnya.', name: 'Andi Nugraha', title: 'Teknisi', avatar: '/images/user/owner.jpg' },
    { quote: 'Simpanan dan pembiayaannya bikin tenang.', name: 'Siti Rahma', title: 'Administrasi', avatar: '/images/user/owner.jpg' },
]

const activeIndex = ref(0)
const activeTestimonial = computed(() => testimonials[activeIndex.value] || {})

const setActive = (i) => { activeIndex.value = i }

const parallaxOffset = ref(0)

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
    <Base>
        <div class="landing-page-content h-full w-full">
            <section class="hero-section flex items-center h-full w-full relative overflow-hidden">
                <div class="flex flex-col gap-4 justify-left pl-12 pr-40">
                    <div class="absolute inset-0 -z-10" :style="{ transform: `translateY(${parallaxOffset}px)` }">
                        <img src="/public/images/home/polban.png" class="w-full h-full object-cover"
                            alt="Hero Background">
                        <div class="absolute inset-0 bg-white opacity-85"></div>
                    </div>
                    <img src="/public/images/home/ornament_blue.svg" class="w-72 h-auto absolute -z-10 -left-0 top-24" alt="">

                    <h1 class="font-semibold text-5xl w-lg leading-20 tracking-wide">Sejahtera <span
                            class="font-light">bersama</span> Koperasi Syariah Warga Polban</h1>
                    <p class="text-xl w-md tracking-wide leading-8">Rasakan aman, nyaman, dan berkahnya bertransaksi
                        sesuai Al-Qur'an dan Sunnah</p>
                    <div class="flex gap-4">
                        <button class="bg-secondary text-white px-6 py-3 rounded-xl">
                            Daftar Menjadi Anggota
                        </button>
                        <button class="bg-light-bg font-semibold text-dark-text px-6 py-3 rounded-xl">
                            Pelajari Lebih Lanjut
                        </button>
                    </div>
                </div>
                <div class="bg-primary h-full w-full rounded-tl-[100px]">
                    <img src="/public/images/home/hero_02.jpg" class="w-40 h-40 rounded-full object-cover absolute top-56 -ml-20 z-11" alt="">
                    <div class="w-36 bg-secondary h-36 absolute z-10 top-64 -ml-8 rounded-full"></div>
                    <img src="/public/images/home/hero_01.jpg" class="w-80 h-80 rounded-full object-cover absolute top-56 z-12 right-96" alt="">
                    <img src="/public/images/home/ornament_orange.svg" class="w-80 h-80 object-cover absolute top-72 z-11 right-72" alt="">
                    <img src="/public/images/home/hero_04.jpg" class="w-40 h-40 rounded-full object-cover absolute ml-52 bottom-52 z-11" alt="">
                    <div class="w-40 bg-blue-accent h-40 absolute z-10 bottom-52 ml-64 rounded-full"></div>
                    <img src="/public/images/home/hero_03.jpg" class="w-44 h-44 rounded-full object-cover absolute right-96 bottom-16 z-11" alt="">
                    <div class="w-32 bg-accent h-32 absolute z-10 bottom-8 -right-16 rounded-full"></div>
                </div>
            </section>

            <section class="features-strip bg-white relative overflow-x-hidden">
                <div class="marquee group">
                    <!-- track 1 -->
                    <ul class="marquee__inner flex w-max text-2xl font-semibold">
                        <li class="shrink-0 border-l-2 border-stroke px-12 py-10 whitespace-nowrap">
                            Simpanan <span class="text-green-700">Syariah</span>
                        </li>
                        <li class="shrink-0 border-l-2 border-stroke px-12 py-10 whitespace-nowrap">
                            Pembayaran Tanpa <span class="text-accent">Riba</span>
                        </li>
                        <li class="shrink-0 border-l-2 border-stroke px-12 py-10 whitespace-nowrap">
                            Keuntungan <span class="text-blue-accent">Halal</span> Dibagi Rata
                        </li>
                        <li class="shrink-0 border-l-2 border-stroke px-12 py-10 whitespace-nowrap">
                            Simpanan <span class="text-green-700">Syariah</span>
                        </li>
                        <li class="shrink-0 border-l-2 border-stroke px-12 py-10 whitespace-nowrap">
                            Pembayaran Tanpa <span class="text-accent">Riba</span>
                        </li>
                        <li class="shrink-0 border-l-2 border-stroke px-12 py-10 whitespace-nowrap">
                            Keuntungan <span class="text-blue-accent">Halal</span> Dibagi Rata
                        </li>
                    </ul>
                    <!-- track 2 -->
                    <ul class="marquee__inner flex w-max text-2xl font-semibold" aria-hidden="true">
                        <li class="shrink-0 border-l-2 border-stroke px-12 py-10 whitespace-nowrap">
                            Simpanan <span class="text-green-700">Syariah</span>
                        </li>
                        <li class="shrink-0 border-l-2 border-stroke px-12 py-10 whitespace-nowrap">
                            Pembayaran Tanpa <span class="text-accent">Riba</span>
                        </li>
                        <li class="shrink-0 border-l-2 border-stroke px-12 py-10 whitespace-nowrap">
                            Keuntungan <span class="text-blue-accent">Halal</span> Dibagi Rata
                        </li>
                        <li class="shrink-0 border-l-2 border-stroke px-12 py-10 whitespace-nowrap">
                            Simpanan <span class="text-green-700">Syariah</span>
                        </li>
                        <li class="shrink-0 border-l-2 border-stroke px-12 py-10 whitespace-nowrap">
                            Pembayaran Tanpa <span class="text-accent">Riba</span>
                        </li>
                        <li class="shrink-0 border-l-2 border-stroke px-12 py-10 whitespace-nowrap">
                            Keuntungan <span class="text-blue-accent">Halal</span> Dibagi Rata
                        </li>
                    </ul>
                </div>
            </section>

            <section class="why-section flex h-fit w-full bg-light-bg p-52 items-center justify-between">
                <div class="flex flex-col gap-8">
                    <h1 class="font-accent text-3xl font-bold w-xl">Mengapa memilih kami daripada bank konvensional
                        &
                        koperasi biasa?</h1>
                    <ul class="flex flex-col gap-8">
                        <li class="flex items-center gap-6">
                            <svg xmlns="http://www.w3.org/2000/svg" width="58" height="58" viewBox="0 0 28 28"
                                fill="none" class="bg-success-50 rounded-lg p-4 drop-accent shadow-2xl">
                                <path
                                    d="M9.9882 14C9.98816 11.093 11.1388 8.30415 13.1888 6.24296C15.2387 4.18178 18.0212 3.01586 20.9282 3C18.9614 1.76124 16.6985 1.07247 14.375 1.00541C12.0516 0.938355 9.75271 1.49547 7.71775 2.61873C5.68279 3.74199 3.98621 5.39031 2.8047 7.39202C1.6232 9.39373 1 11.6756 1 14C1 16.3244 1.6232 18.6063 2.8047 20.608C3.98621 22.6097 5.68279 24.258 7.71775 25.3813C9.75271 26.5045 12.0516 27.0616 14.375 26.9946C16.6985 26.9275 18.9614 26.2388 20.9282 25C18.0212 24.9841 15.2387 23.8182 13.1888 21.757C11.1388 19.6959 9.98816 16.907 9.9882 14Z"
                                    stroke="#007943" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path
                                    d="M20.7082 8.08008L22.5282 11.7001H26.1482L23.4282 14.5001L24.2882 18.5001L20.7082 16.5001L17.2882 18.5001L18.0082 14.5001L15.2882 11.7001H18.9082L20.7082 8.08008Z"
                                    stroke="#007943" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <p class="text-2xl w-md">Transaksi 100% sesuai fatwa DSN-MUI (tanpa riba, gharar,
                                maysir)
                            </p>
                        </li>
                        <li class="flex items-center gap-6">
                            <svg xmlns="http://www.w3.org/2000/svg" width="58" height="58" viewBox="0 0 28 28"
                                fill="none" class="bg-success-50 rounded-lg p-4 drop-accent shadow-2xl">
                                <path
                                    d="M4 11.0002V22.0002M10.666 11.0002V22.0002M17.334 11.0002V22.0002M24 11.0002V22.0002M25.82 11.0002H2.18C1.06 11.0002 0.58 9.78023 1.46 9.20023L13.28 1.46023C13.4982 1.33186 13.7468 1.26416 14 1.26416C14.2532 1.26416 14.5018 1.33186 14.72 1.46023L26.54 9.20023C27.42 9.78023 26.94 11.0002 25.82 11.0002ZM26 22.0002H2C1.73478 22.0002 1.48043 22.1056 1.29289 22.2931C1.10536 22.4807 1 22.735 1 23.0002V26.0002C1 26.2654 1.10536 26.5198 1.29289 26.7073C1.48043 26.8949 1.73478 27.0002 2 27.0002H26C26.2652 27.0002 26.5196 26.8949 26.7071 26.7073C26.8946 26.5198 27 26.2654 27 26.0002V23.0002C27 22.735 26.8946 22.4807 26.7071 22.2931C26.5196 22.1056 26.2652 22.0002 26 22.0002Z"
                                    stroke="#007943" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <p class="text-2xl w-md">Pembiayaan cepat tanpa BI checking ketat & angsuran ringan</p>
                        </li>
                        <li class="flex items-center gap-6">
                            <svg xmlns="http://www.w3.org/2000/svg" width="58" height="58" viewBox="0 0 28 28"
                                fill="none" class="bg-success-50 rounded-lg p-4 drop-accent shadow-2xl">
                                <path
                                    d="M8.75 19.25C9.21413 19.25 9.65925 19.0656 9.98744 18.7374C10.3156 18.4092 10.5 17.9641 10.5 17.5C10.5 17.0359 10.3156 16.5908 9.98744 16.2626C9.65925 15.9344 9.21413 15.75 8.75 15.75C8.28587 15.75 7.84075 15.9344 7.51256 16.2626C7.18437 16.5908 7 17.0359 7 17.5C7 17.9641 7.18437 18.4092 7.51256 18.7374C7.84075 19.0656 8.28587 19.25 8.75 19.25ZM15.75 17.5C15.75 17.9641 15.5656 18.4092 15.2374 18.7374C14.9092 19.0656 14.4641 19.25 14 19.25C13.5359 19.25 13.0908 19.0656 12.7626 18.7374C12.4344 18.4092 12.25 17.9641 12.25 17.5C12.25 17.0359 12.4344 16.5908 12.7626 16.2626C13.0908 15.9344 13.5359 15.75 14 15.75C14.4641 15.75 14.9092 15.9344 15.2374 16.2626C15.5656 16.5908 15.75 17.0359 15.75 17.5ZM19.25 19.25C19.7141 19.25 20.1592 19.0656 20.4874 18.7374C20.8156 18.4092 21 17.9641 21 17.5C21 17.0359 20.8156 16.5908 20.4874 16.2626C20.1592 15.9344 19.7141 15.75 19.25 15.75C18.7859 15.75 18.3408 15.9344 18.0126 16.2626C17.6844 16.5908 17.5 17.0359 17.5 17.5C17.5 17.9641 17.6844 18.4092 18.0126 18.7374C18.3408 19.0656 18.7859 19.25 19.25 19.25ZM5.36375 3.57613C5.61006 3.27857 5.91901 3.03905 6.26855 2.87464C6.61809 2.71023 6.9996 2.62499 7.38587 2.625H20.615C21.0014 2.62486 21.3831 2.71004 21.7328 2.87446C22.0825 3.03887 22.3916 3.27846 22.638 3.57613L26.7199 8.50938C27.664 9.65038 26.852 11.375 25.3715 11.375H24.5V22.5312C24.5 22.9047 24.4264 23.2745 24.2835 23.6195C24.1406 23.9645 23.9312 24.278 23.6671 24.5421C23.403 24.8062 23.0895 25.0156 22.7445 25.1585C22.3995 25.3014 22.0297 25.375 21.6562 25.375H6.34375C5.58954 25.375 4.86622 25.0754 4.33291 24.5421C3.79961 24.0088 3.5 23.2855 3.5 22.5312V11.375H2.63025C1.14887 11.375 0.337749 9.65038 1.28187 8.50938L5.36375 3.57613ZM5.25 11.375V22.5312C5.25 23.135 5.74 23.625 6.34375 23.625H21.6562C22.26 23.625 22.75 23.135 22.75 22.5312V11.375H5.25ZM7.38587 4.375C7.25719 4.375 7.13009 4.40339 7.01363 4.45814C6.89717 4.51289 6.79423 4.59266 6.71212 4.69175L2.63025 9.625H25.3706L21.2887 4.69175C21.2066 4.59266 21.1037 4.51289 20.9872 4.45814C20.8708 4.40339 20.7437 4.375 20.615 4.375H7.38587Z"
                                    fill="#007943" />
                            </svg>
                            <p class="text-2xl w-md">Mini market ritel halal dengan harga grosir khusus anggota</p>
                        </li>
                    </ul>
                </div>
                <h1 class="font-semibold w-lg text-right text-5xl text-dark-text leading-20 tracking-wide"><span
                        class="font-normal">Mengapa memilih</span> Koperasi Syariah Warga Polban?</h1>
            </section>

            <section class="grid grid-cols-2 h-fit py-36 px-32 relative">
                <img src="/public/images/home/ornament_blue.svg" class="w-60 h-auto absolute -z-1 top-20" alt="">
                <img src="/public/images/home/ornament_orange.svg" class="w-60 h-auto absolute -z-1 bottom-8 right-0"
                    alt="">
                <img src="/public/images/home/ornament_circle.svg" class="w-32 h-auto absolute -z-1 bottom-36 left-16"
                    alt="">
                <img src="/public/images/home/about_us.png" class="w-3xl h-full" />
                <div class="flex flex-col gap-6 mt-auto">
                    <h2 class="text-3xl font-bold text-primary">Tentang Kami</h2>
                    <p class="text-lg text-dark-text leading-8">Koperasi Syariah Warga Polban didirikan pada tahun
                        2024 dengan tujuan memberikan solusi keuangan yang sesuai dengan prinsip-prinsip syariah
                        kepada masyarakat Politeknik Negeri Bandung. Kami berkomitmen untuk menyediakan layanan
                        keuangan yang transparan, adil, dan bermanfaat bagi seluruh anggota kami.</p>
                </div>
            </section>

            <section class="px-32 py-36 flex flex-col gap-8">
                <h2 class="text-3xl font-bold text-primary">Produk Koperasi</h2>
                <ul class="flex gap-6">
                    <li
                        class="group duration-300 inline-flex flex-col bg-light-bg hover:bg-secondary rounded-2xl overflow-hidden relative">
                        <div class="relative z-2">
                            <img src="/public/images/home/product_murabaha.png" class="w-96 h-96 object-cover" alt="">
                            <span class="absolute inset-0"
                                style="background: linear-gradient(180deg, rgba(255, 255, 255, 0) 56.09%, #FFF 100%);"></span>
                        </div>
                        <!-- ornament normal -->
                        <img src="/public/images/home/ornament_dark.svg"
                            class="w-60 h-auto absolute z-1 bottom-14 right-0 transition-opacity duration-300 group-hover:opacity-0"
                            alt="">
                        <!-- ornament hover -->
                        <img src="/public/images/home/ornament_orange.svg"
                            class="w-60 h-auto absolute z-1 bottom-14 right-0 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                            alt="">

                        <h1 class="text-dark-text font-medium group-hover:text-white text-xl px-6 py-4 w-xs z-2">
                            Pembiayaan Murabahah dan Murabahah bil Wakalah
                        </h1>
                        <div
                            class="bg-light-accent rounded-full mr-auto p-2 text-dark-text group-hover:text-white ml-6 mb-6">
                            <span class="icon-[tabler--arrow-right]" style="width: 32px; height: 32px;"></span>
                        </div>
                    </li>
                    <li
                        class="group duration-300 inline-flex flex-col bg-light-bg hover:bg-secondary rounded-2xl overflow-hidden relative">
                        <div class="relative z-2">
                            <img src="/public/images/home/product_saving.png" class="w-96 h-96 object-cover" alt="">
                            <span class="absolute inset-0"
                                style="background: linear-gradient(180deg, rgba(255, 255, 255, 0) 56.09%, #FFF 100%);"></span>
                        </div>
                        <!-- ornament normal -->
                        <img src="/public/images/home/ornament_dark.svg"
                            class="w-60 h-auto absolute z-1 bottom-14 right-0 transition-opacity duration-300 group-hover:opacity-0"
                            alt="">
                        <!-- ornament hover -->
                        <img src="/public/images/home/ornament_orange.svg"
                            class="w-60 h-auto absolute z-1 bottom-14 right-0 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                            alt="">

                        <div class="flex flex-col justify-between h-full">
                            <h1 class="text-dark-text font-medium group-hover:text-white text-xl px-6 py-4 w-xs z-2">
                                Simpanan Pokok
                            </h1>
                            <div
                                class="bg-light-accent rounded-full mr-auto p-2 text-dark-text group-hover:text-white ml-6 mb-6">
                                <span class="icon-[tabler--arrow-right]" style="width: 32px; height: 32px;"></span>
                            </div>
                        </div>
                    </li>
                    <li
                        class="group duration-300 inline-flex flex-col bg-light-bg hover:bg-secondary rounded-2xl overflow-hidden relative">
                        <div class="relative z-2">
                            <img src="/public/images/home/product_saving.png" class="w-96 h-96 object-cover" alt="">
                            <span class="absolute inset-0"
                                style="background: linear-gradient(180deg, rgba(255, 255, 255, 0) 56.09%, #FFF 100%);"></span>
                        </div>
                        <!-- ornament normal -->
                        <img src="/public/images/home/ornament_dark.svg"
                            class="w-60 h-auto absolute z-1 bottom-14 right-0 transition-opacity duration-300 group-hover:opacity-0"
                            alt="">
                        <!-- ornament hover -->
                        <img src="/public/images/home/ornament_orange.svg"
                            class="w-60 h-auto absolute z-1 bottom-14 right-0 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                            alt="">

                        <div class="flex flex-col justify-between h-full">
                            <h1 class="text-dark-text font-medium group-hover:text-white text-xl px-6 py-4 w-xs z-2">
                                Simpanan Wajib
                            </h1>
                            <div
                                class="bg-light-accent rounded-full mr-auto p-2 text-dark-text group-hover:text-white ml-6 mb-6">
                                <span class="icon-[tabler--arrow-right]" style="width: 32px; height: 32px;"></span>
                            </div>
                        </div>

                    </li>
                    <li
                        class="group duration-300 inline-flex flex-col bg-light-bg hover:bg-secondary rounded-2xl overflow-hidden relative">
                        <div class="relative z-2">
                            <img src="/public/images/home/product_saving.png" class="w-96 h-96 object-cover" alt="">
                            <span class="absolute inset-0"
                                style="background: linear-gradient(180deg, rgba(255, 255, 255, 0) 56.09%, #FFF 100%);"></span>
                        </div>
                        <!-- ornament normal -->
                        <img src="/public/images/home/ornament_dark.svg"
                            class="w-60 h-auto absolute z-1 bottom-14 right-0 transition-opacity duration-300 group-hover:opacity-0"
                            alt="">
                        <!-- ornament hover -->
                        <img src="/public/images/home/ornament_orange.svg"
                            class="w-60 h-auto absolute z-1 bottom-14 right-0 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                            alt="">

                        <div class="flex flex-col justify-between h-full">
                            <h1 class="text-dark-text font-medium group-hover:text-white text-xl px-6 py-4 w-xs z-2">
                                Simpanan Sukarela Wadiah Yad Dhamanah
                            </h1>
                            <div
                                class="bg-light-accent rounded-full mr-auto p-2 text-dark-text group-hover:text-white ml-6 mb-6">
                                <span class="icon-[tabler--arrow-right]" style="width: 32px; height: 32px;"></span>
                            </div>
                        </div>
                    </li>
                </ul>
            </section>

            <section class="bg-light-bg h-fit flex flex-col items-center py-36 px-32 gap-14 relative">
                <img src="/public/images/home/ornament_orange.svg" class="w-60 h-auto absolute z-1 -top-20 right-0"
                    alt="">
                <h1 class="text-5xl text-secondary font-semibold">Apa Kata Anggota Kami?</h1>

                <div class="flex flex-col">
                    <div class="bg-white px-12 pt-10 pb-32 rounded-2xl w-6xl h-72 flex gap-6">
                        <span class="text-6xl font-semibold">“</span>
                        <p class="font-body text-2xl leading-10 tracking-wide text-justify">
                            {{ activeTestimonial.quote || 'Belum ada testimoni.' }}
                        </p>
                    </div>
                    <div
                        class="w-0 h-0 ml-12 border-l-20 border-l-transparent border-r-20 border-r-transparent border-t-30 border-t-white">
                    </div>

                    <div class="flex mt-10 px-10 justify-between">
                        <template v-for="(t, i) in testimonials" :key="i">
                            <button type="button" class="flex items-center gap-2 text-gray-700 dark:text-gray-400"
                                @click="setActive(i)">
                                <span class="mr-3 overflow-hidden rounded-full h-16 w-16 ring-2 ring-transparent"
                                    :class="i === activeIndex ? 'ring-secondary' : ''">
                                    <img :src="t.avatar" :alt="t.name" />
                                </span>
                                <div v-if="i === activeIndex" class="flex flex-col text-left">
                                    <span class="text-dark-text">{{ t.name }}</span>
                                    <span class="text-dark-text font-semibold">{{ t.title }}</span>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>
            </section>

            <section class="relative overflow-hidden bg-linear-to-r from-brand-500 to-brand-900 py-24 px-32">
                <!-- Ornaments -->
                <span
                    class="absolute top-0 right-0 w-96 h-96 bg-white opacity-5 rounded-full blur-3xl -mr-48 -mt-48"></span>
                <span
                    class="absolute bottom-0 left-0 w-80 h-80 bg-white opacity-5 rounded-full blur-3xl -ml-40 -mb-40"></span>

                <div class="relative z-10 flex flex-col items-center text-center gap-8 max-w-4xl mx-auto">
                    <div class="flex flex-col gap-4">
                        <h2 class="text-5xl font-bold text-white leading-tight">
                            Mulai Perjalanan Finansial Syariah Anda Hari Ini
                        </h2>
                        <p class="text-xl text-white/90">
                            Bergabunglah dengan ribuan anggota yang telah merasakan manfaat keuangan syariah yang
                            transparan, adil,
                            dan berkah.
                        </p>
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button
                            class="bg-white text-secondary hover:bg-gray-100 px-8 py-4 rounded-xl font-semibold transition-colors duration-300">
                            Daftar Sekarang
                        </button>
                        <button
                            class="border-2 border-white text-white hover:bg-white/10 px-8 py-4 rounded-xl font-semibold transition-colors duration-300">
                            Hubungi Kami
                        </button>
                    </div>
                </div>
            </section>
            <Footer />
        </div>
    </Base>
</template>
