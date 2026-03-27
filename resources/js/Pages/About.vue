<script setup>
import Base from '@/Layouts/Base.vue';
import Footer from '@/Layouts/Footer.vue';
import HelpButton from '@/Components/HelpButton.vue';
import { ref, onMounted, onUnmounted } from 'vue';

const parallaxOffset = ref(0);
const scrollY = ref(0);

const handleScroll = () => {
    parallaxOffset.value = window.scrollY * 0.4;
    scrollY.value = window.scrollY;
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <Base title="Tentang Koperasi">
        <div class="font-body min-h-screen dark:bg-gray-950 transition-colors overflow-x-hidden">

             <section class="flex items-center justify-center rounded-b-2xl dark:rounded-b-none relative overflow-hidden h-fit">
                <div class="absolute inset-0 -z-10" :style="{ transform: `translateY(${parallaxOffset}px)` }">
                    <img :src="'/images/home/al-hikmah.png'" class="w-full h-full object-cover" alt="Masjid Al-Hikmah">
                    <div class="absolute inset-0 bg-primary dark:bg-dark-text opacity-70"></div>
                </div>
                <div class="text-center py-40 pb-20 px-4">
                    <h1 class="text-4xl font-semibold text-white mb-4 font-head">Tentang <span class="text-accent">Koperasi</span></h1>
                    <p class="text-lg font-body md:text-xl text-white font-head">Mengenal lebih dekat Koperasi Syariah Berkah</p>
                </div>
            </section>

            <!--tentang-->
            <section class="max-w-6xl mx-auto px-6 py-10">
                <!-- Section label -->
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-10 h-px bg-accent"></div>
                    <span class="text-accent text-sm tracking-[0.25em] uppercase font-medium">Siapa Kami</span>
                </div>

                <div class="grid md:grid-cols-2 gap-16 items-center">
                    <div>
                        <h2 class="text-4xl md:text-5xl font-bold text-primary dark:text-white font-head leading-tight mb-8">
                            Koperasi Syariah<br/>
                            <span class="text-accent">Berkah</span>
                        </h2>
                        <p class="text-gray-600 dark:text-gray-300 text-lg leading-relaxed mb-6">
                            Koperasi Syariah Berkah merupakan lembaga layanan keuangan berbasis
                            syariah Islam. Seluruh akad, transaksi, dan layanan dijalankan sesuai
                            dengan ketentuan syariah, merujuk pada Fatwa DSN-MUI No.141 tentang
                            Pedoman Pendirian dan Operasional Koperasi Syariah.
                        </p>
                        <p class="text-gray-500 dark:text-gray-400 text-base leading-relaxed">
                            Melalui penerapan prinsip syariah, koperasi memastikan setiap
                            mekanisme transaksi terbebas dari unsur <em class="text-primary not-italic font-semibold">riba</em>,
                            <em class="text-primary not-italic font-semibold">gharar</em>, dan
                            praktik yang dilarang dalam Islam.
                        </p>
                    </div>

                    <!-- Decorative card -->
                    <div class="relative">
                        <!-- Background shape -->
                        <div class="absolute -top-6 -right-6 w-72 h-72 bg-[#1a3a2a]/5 dark:bg-white/5 rounded-3xl rotate-6"></div>
                        <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-xl border border-[#C8A96E]/20">
                            <!-- Islamic ornament top -->
                            <div class="flex justify-center mb-6">
                                <svg viewBox="0 0 120 40" class="w-32 text-accent" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M10 20 Q30 5 60 20 Q90 35 110 20" stroke="currentColor" stroke-width="1.5" fill="none"/>
                                    <path d="M10 20 Q30 35 60 20 Q90 5 110 20" stroke="currentColor" stroke-width="1.5" fill="none"/>
                                    <circle cx="60" cy="20" r="4" fill="currentColor"/>
                                    <circle cx="10" cy="20" r="2.5" fill="currentColor"/>
                                    <circle cx="110" cy="20" r="2.5" fill="currentColor"/>
                                </svg>
                            </div>

                            <p class="text-center text-primary dark:text-gray-200 text-xl italic leading-relaxed font-medium font-head">
                                "Bertransaksi dengan jujur, bermanfaat untuk bersama"
                            </p>

                            <div class="mt-6 pt-6 border-t border-[#C8A96E]/20 grid grid-cols-3 gap-4 text-center">
                                <div>
                                    <div class="text-2xl font-bold text-accent">100%</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Sesuai Syariah</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-accent">DSN</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Fatwa MUI</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-accent">Riba</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Free</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- divider ornament -->
            <div class="flex items-center justify-center py-4 px-6">
                <div class="flex-1 h-px bg-gradient-to-r from-transparent via-[#C8A96E]/40 to-transparent"></div>
                <div class="mx-6 flex gap-2 items-center">
                    <div class="w-2 h-2 rounded-full bg-primary/40"></div>
                    <div class="w-3 h-3 rounded-full bg-primary"></div>
                    <div class="w-2 h-2 rounded-full bg-primary/40"></div>
                </div>
                <div class="flex-1 h-px bg-gradient-to-l from-transparent via-primary/40 to-transparent"></div>
            </div>

            <!--visi -->
            <section class="max-w-6xl mx-auto px-6 py-24">
                <div class="grid md:grid-cols-2 gap-16 items-center">

                    <!-- Image side -->
                    <div class="relative flex justify-center">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="w-72 h-72 rounded-full bg-[#C8A96E]/10 dark:bg-[#C8A96E]/5 blur-2xl"></div>
                        </div>
                        <div class="relative">
                            <!-- Hexagonal frame using clip-path workaround -->
                            <div class="w-72 h-72 relative">
                                <div class="absolute inset-0 rounded-3xl rotate-3 bg-light-accent/20 dark:light-accent/10"></div>
                                <div class="absolute inset-2 rounded-2xl overflow-hidden shadow-2xl">
                                    <img
                                        :src="'/images/home/misi.webp'"
                                        class="w-full h-full object-cover"
                                        alt="Visi Koperasi"
                                        onerror="this.style.display='none'; this.parentElement.style.background='linear-gradient(135deg, #1a3a2a, #2d6a4f)';"
                                    />
                                </div>
                            </div>

                            <!-- Floating badge -->
                            <div class="absolute -bottom-4 -right-4 bg-primary text-white rounded-xl px-4 py-2 shadow-lg">
                                <span class="font-bold text-sm tracking-wider">VISI</span>
                            </div>
                        </div>
                    </div>

                    <!-- Content side -->
                    <div>
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-10 h-px bg-light-accent"></div>
                            <span class="text-accent text-sm tracking-[0.25em] uppercase font-medium">Tujuan Kami</span>
                        </div>
                        <h2 class="text-5xl font-bold text-accent mb-8 font-head">
                            Visi
                        </h2>

                        <!-- Quote-style visi -->
                        <div class="relative pl-6 border-l-4 border-primary">
                            <svg class="absolute -top-2 -left-2 ml-4 w-6 h-6 text-accent opacity-80" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                            </svg>
                            <p class="text-gray-700 ml-5 dark:text-gray-200 text-xl leading-relaxed italic" style="font-family: 'Georgia', serif;">
                                Menjadi unit usaha unggulan yang mampu membantu anggota
                                dalam mengatasi permodalan usaha.
                            </p>
                        </div>

                        <!-- Decorative bottom -->
                        <div class="mt-10 flex gap-4 justify-center">
                            <div class="flex-1 h-1.5 rounded-full bg-gradient-to-r from-primary to-light-accent"></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- misi -->
            <section class="bg-primary dark:bg-gray-900 py-24 relative overflow-hidden">
                <!-- Background pattern -->
                <div class="absolute inset-0 opacity-5 pointer-events-none">
                    <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <pattern id="misiPattern" x="0" y="0" width="60" height="60" patternUnits="userSpaceOnUse">
                                <path d="M30 5 L55 30 L30 55 L5 30 Z" fill="none" stroke="#C8A96E" stroke-width="1"/>
                                <circle cx="30" cy="30" r="6" fill="none" stroke="#C8A96E" stroke-width="1"/>
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#misiPattern)"/>
                    </svg>
                </div>

                <div class="max-w-6xl mx-auto px-6 relative z-10">
                    <div class="grid md:grid-cols-2 gap-16 items-center">

                        <!-- Content side -->
                        <div>
                            <div class="flex items-center gap-4 mb-6">
                                <div class="w-10 h-px bg-[#C8A96E]"></div>
                                <span class="text-light-accent text-sm tracking-[0.25em] uppercase font-medium font-head">Langkah Kami</span>
                            </div>
                            <h2 class="text-5xl font-bold text-white mb-10 font-head">
                                Misi
                            </h2>

                            <div class="space-y-6">
                                <!-- Misi Item 1 -->
                                <div class="flex items-start gap-5 group">
                                    <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-[#C8A96E]/20 border border-[#C8A96E]/30 flex items-center justify-center group-hover:bg-[#C8A96E]/30 transition-colors">
                                        <span class="text-light-accent font-bold text-lg">01</span>
                                    </div>
                                    <div class="pt-2">
                                        <p class="text-white/80 text-base leading-relaxed">
                                            Menyelenggarakan pelayanan prima kepada anggota sesuai
                                            jati diri koperasi
                                        </p>
                                    </div>
                                </div>

                                <!-- Connector -->
                                <div class="ml-6 w-px h-4 bg-[#C8A96E]/20"></div>

                                <!-- Misi Item 2 -->
                                <div class="flex items-start gap-5 group">
                                    <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-[#C8A96E]/20 border border-[#C8A96E]/30 flex items-center justify-center group-hover:bg-[#C8A96E]/30 transition-colors">
                                        <span class="text-light-accent font-bold text-lg">02</span>
                                    </div>
                                    <div class="pt-2">
                                        <p class="text-white/80 text-base leading-relaxed">
                                            Menjalankan usaha simpan pinjam secara efektif, efisien,
                                            dan transparan
                                        </p>
                                    </div>
                                </div>

                                <!-- Connector -->
                                <div class="ml-6 w-px h-4 bg-[#C8A96E]/20"></div>

                                <!-- Misi Item 3 -->
                                <div class="flex items-start gap-5 group">
                                    <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-[#C8A96E]/20 border border-[#C8A96E]/30 flex items-center justify-center group-hover:bg-[#C8A96E]/30 transition-colors">
                                        <span class="text-light-accent font-bold text-lg">03</span>
                                    </div>
                                    <div class="pt-2">
                                        <p class="text-white/80 text-base leading-relaxed">
                                            Menjalin kerja sama dengan berbagai pihak untuk
                                            meningkatkan manfaat bagi anggota
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Image side -->
                        <div class="relative flex justify-center md:justify-end">
                            <div class="relative">
                                <div class="absolute inset-0 bg-light-accent/20 rounded-3xl -rotate-3 scale-105"></div>
                                <div class="relative w-72 h-80 rounded-2xl overflow-hidden shadow-2xl border border-light-accent/30">
                                    <img
                                        :src="'/images/home/visi.png'"
                                        class="w-full h-full object-cover"
                                        alt="Misi Koperasi"
                                        onerror="this.style.display='none'; this.parentElement.style.background='linear-gradient(135deg, #2d6a4f, #1a3a2a)';"
                                    />
                                    <div class="absolute inset-0 bg-gradient-to-t from-[#1a3a2a]/60 to-transparent"></div>
                                </div>

                                <!-- Floating badge -->
                                <div class="absolute -top-4 -left-4 bg-white dark:bg-gray-800 text-primary dark:text-white rounded-xl px-4 py-2 shadow-xl border border-primary/20">
                                    <span class="font-bold text-sm tracking-wider">MISI</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- nilai-nilai utama -->
            <section class="max-w-6xl mx-auto px-6 py-24">
                <div class="text-center mb-16">
                    <div class="flex items-center justify-center gap-4 mb-4">
                        <div class="w-12 h-px bg-accent"></div>
                        <span class="text-accent text-sm tracking-[0.25em] uppercase font-medium">Fondasi Kami</span>
                        <div class="w-12 h-px bg-accent"></div>
                    </div>
                    <h2 class="text-4xl font-bold text-primary dark:text-white font-head">
                        Nilai-Nilai Kami
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Card 1 -->
                    <div class="group relative bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-md border border-primary/10 hover:border-primary/40 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                        <div class="w-14 h-14 rounded-2xl bg-[#1a3a2a]/5 dark:bg-white/5 flex items-center justify-center mb-6 group-hover:bg-accent/10 transition-colors">
                            <svg class="w-7 h-7 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <h3 class="font-bold text-primary dark:text-white text-xl mb-3">Amanah</h3>
                        <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">
                            Menjaga kepercayaan anggota dengan integritas dan transparansi dalam setiap transaksi.
                        </p>
                        <div class="absolute bottom-0 left-0 right-0 h-1 rounded-b-2xl bg-gradient-to-r from-primary to-light-accent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    </div>

                    <!-- Card 2 -->
                    <div class="group relative bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-md border border-primary/10 hover:border-primary/40 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                        <div class="w-14 h-14 rounded-2xl bg-[#1a3a2a]/5 dark:bg-white/5 flex items-center justify-center mb-6 group-hover:bg-accent/10 transition-colors">
                            <svg class="w-7 h-7 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                        <h3 class="font-bold text-primary dark:text-white text-xl mb-3">Keadilan</h3>
                        <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">
                            Memastikan distribusi manfaat yang adil dan merata bagi seluruh anggota koperasi.
                        </p>
                        <div class="absolute bottom-0 left-0 right-0 h-1 rounded-b-2xl bg-gradient-to-r from-primary to-light-accent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    </div>

                    <!-- Card 3 -->
                    <div class="group relative bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-md border border-primary/10 hover:border-primary/40 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                        <div class="w-14 h-14 rounded-2xl bg-[#1a3a2a]/5 dark:bg-white/5 flex items-center justify-center mb-6 group-hover:bg-accent/10 transition-colors">
                            <svg class="w-7 h-7 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <h3 class="font-bold text-primary dark:text-white text-xl mb-3">Kebersamaan</h3>
                        <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">
                            Membangun solidaritas dan gotong royong antar anggota untuk kemajuan bersama.
                        </p>
                        <div class="absolute bottom-0 left-0 right-0 h-1 rounded-b-2xl bg-gradient-to-r from-primary to-light-accent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    </div>
                </div>
            </section>

        </div>
        <Footer />
        <HelpButton />
    </Base>
</template>

<style scoped>
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(30px); }
    to   { opacity: 1; transform: translateY(0); }
}

section {
    animation: fadeUp 0.7s ease both;
}
</style>