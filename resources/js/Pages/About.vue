<script setup>
import Base from '@/Layouts/Base.vue';
import Footer from '@/Layouts/Footer.vue';
import HelpButton from '@/Components/HelpButton.vue';
</script>

<template>
    <Base>
        <div class="font-body min-h-screen bg-white dark:bg-gray-900 transition-colors">

            <!--Header Tentang Koperasi -->
            <section class="max-w-7xl mx-auto px-6 py-16 mt-15">
                <div class="relative flex items-center justify-center mb-10">
                    <div class="absolute left-0 top-1/2 w-1/2 border-t border-l border-primary rounded-tl-xl h-25 dark:border-gray-100"></div>
                    <div class="absolute right-0 top-1/2 w-1/2 border-t border-r border-primary rounded-tr-xl h-25 dark:border-gray-100"></div>

                    <h1
                        class="relative z-10 bg-white dark:bg-gray-900 px-6 text-3xl font-bold text-primary font-head dark:text-gray-100">
                        Tentang Koperasi
                    </h1>
                </div>

                <p class="max-w-6xl mx-auto text-theme-xl text-center text-gray-600 dark:text-gray-300 leading-relaxed">
                    Koperasi Syariah Warga Polban merupakan lembaga layanan keuangan berbasis
                    syariah Islam. Seluruh akad, transaksi, dan layanan dijalankan sesuai
                    dengan ketentuan syariah, merujuk pada Fatwa DSN-MUI No.141 tentang
                    Pedoman Pendirian dan Operasional Koperasi Syariah. Melalui penerapan
                    prinsip syariah, koperasi memastikan setiap mekanisme transaksi
                    terbebas dari unsur riba, gharar, dan praktik yang dilarang dalam Islam.
                </p>
            </section>

            <!-- Misi -->
            <section class="max-w-7xl mx-auto px-6 py-20 grid grid-cols-1 md:grid-cols-2 gap-16 items-center">
                <!-- Gambar -->
                <div
                class="w-80 h-96 bg-no-repeat bg-contain bg-center"
                style="background-image: url('/images/home/misi.webp');"
                ></div>

                <!-- Konten -->
                <div>
                    <div class="flex justify-center">
                        <h2 class="text-5xl font-bold text-orange-500 mb-10 font-head">
                            Misi
                        </h2>
                    </div>
                    <div class="flex justify-center">
                        <p class="text-gray-600 text-theme-xl dark:text-gray-300 text-center">
                            “Menjadi unit usaha unggulan yang mampu membantu anggota
                            dalam mengatasi permodalan usaha.”
                        </p>
                    </div>
                </div>
            </section>

            <!-- Visi -->
            <section class="max-w-7xl mx-auto px-6 py-20 grid grid-cols-1 md:grid-cols-2 gap-16 items-center">
                <!-- Konten -->
                <div>
                    <h2 class="text-5xl font-bold text-primary mb-10 font-head">
                        Visi
                    </h2>
                    <div class="text-gray-600 text-theme-xl dark:text-gray-300 space-y-3">
                        <ol class="list-decimal list-inside space-y-2">
                            <li>
                                Menyelenggarakan pelayanan prima kepada anggota sesuai
                                jati diri koperasi
                            </li>
                            <li>
                                Menjalankan usaha simpan pinjam secara efektif, efisien,
                                dan transparan
                            </li>
                            <li>
                                Menjalin kerja sama dengan berbagai pihak untuk
                                meningkatkan manfaat bagi anggota
                            </li>
                        </ol>
                    </div>
                </div>

                <!-- Gambar -->
                 <div class="flex justify-end">
                    <div
                    class="w-80 h-96 bg-no-repeat bg-contain bg-center"
                    style="background-image: url('/images/home/visi.png');"
                    ></div>
                </div>
            </section>
        </div>
        <Footer />
        <HelpButton />
    </Base>
</template>
