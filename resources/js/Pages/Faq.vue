<script setup>
import Base from '@/Layouts/Base.vue';
import Footer from '@/Layouts/Footer.vue';
import AccordionPanel from '@/Components/AccordionPanel.vue';
import HelpButton from '@/Components/HelpButton.vue';
import { ref, onMounted, onUnmounted } from 'vue';

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
    <Base title="FAQ - Pusat Bantuan">
        <div class="h-full w-full">
            <section class="flex items-center justify-center rounded-b-xl dark:rounded-b-none relative overflow-hidden h-fit">
                <div class="absolute inset-0 -z-10" :style="{ transform: `translateY(${parallaxOffset}px)` }">
                    <img src="/public/images/home/al-hikmah.png" class="w-full h-full object-cover" alt="Politeknik Negeri Bandung campus building">
                    <div class="absolute inset-0 bg-primary dark:bg-dark-text opacity-70"></div>
                </div>
                <div class="text-center py-40 pb-20 px-4">
                    <h1 class="text-4xl font-semibold text-white mb-4">Pusat <span class="text-accent">Bantuan</span></h1>
                    <p class="text-lg font-body md:text-xl text-white">Pertanyaan yang sering diajukan seputar produk dan layanan koperasi</p>
                </div>
            </section>
            <section class="relative px-6 md:px-10 lg:px-20 xl:px-52 py-12 md:py-16">
                <div class="max-w-6xl mx-auto grid gap-8">
                    <div class="bg-white dark:bg-gray-800 border border-stroke dark:border-gray-700 rounded-xl p-6 md:p-8 shadow-sm">
                        <h2 class="text-xl md:text-xl font-bold text-primary dark:text-green-500 font-head mb-3">Butuh bantuan cepat?</h2>
                        <p class="font-body text-gray-text dark:text-gray-300 leading-7">Temukan jawaban seputar keanggotaan, simpanan, dan pembiayaan di bawah ini. Jika membutuhkan informasi detail data produk, pengurus koperasi siap membantu.</p>
                    </div>

                    <!-- Umum Section -->
                    <div class="bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm border border-stroke dark:border-gray-700 rounded-xl p-4 md:p-6 grid gap-4">
                        <div class="flex items-center justify-between gap-4 border-b border-stroke dark:border-gray-700 pb-3">
                            <h1 class="text-xl font-head font-bold text-green-800 dark:text-green-500">Umum</h1>
                            <span class="text-sm font-medium text-primary dark:text-accent bg-light-bg dark:bg-primary/20 px-3 py-1 rounded-full">Informasi Dasar</span>
                        </div>
                        <AccordionPanel title="Apa Itu Koperasi Syariah ?"
                            ariaTitle="Apa Itu Koperasi Syariah">
                            <p class="font-body text-md py-4 dark:text-gray-200">
                                Koperasi Syariah adalah lembaga keuangan yang beroperasi berdasarkan prinsip syariah Islam,
                                menghindari riba, dan mengutamakan keadilan dalam setiap transaksi. Semua produk dan layanan kami sesuai dengan hukum syariah.
                            </p>
                        </AccordionPanel>
                        <AccordionPanel title="Bagaimana cara menjadi anggota Koperasi?"
                            ariaTitle="Bagaimana cara menjadi anggota Koperasi">
                            <p class="font-body text-md py-4 dark:text-gray-200">
                                Untuk menjadi anggota Koperasi Syariah Berkah, Anda dapat langsung datang dan mendaftar melalui
                                pengurus koperasi dengan menyiapkan foto KTP dan foto Kartu Keluarga (KK).
                                Pengurus akan membantu proses pendaftaran hingga selesai.
                            </p>
                        </AccordionPanel>
                        <AccordionPanel title="Bagaimana cara mengakses produk koperasi?"
                            ariaTitle="Bagaimana cara mengakses produk koperasi">
                            <p class="font-body text-md py-4 dark:text-gray-200">
                                Sebagai anggota, Anda dapat mengakses informasi produk simpanan dan pembiayaan yang Anda miliki
                                melalui aplikasi web KSB. Saat ini seluruh layanan produk Koperasi Syariah Berkah,
                                termasuk pengajuan simpanan maupun pembiayaan, dilakukan langsung melalui pengurus.
                            </p>
                        </AccordionPanel>
                    </div>

                    <!-- Produk Simpanan Section -->
                    <div class="bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm border border-stroke dark:border-gray-700 rounded-xl p-4 md:p-6 grid gap-4">
                        <div class="flex items-center justify-between gap-4 border-b border-stroke dark:border-gray-700 pb-3">
                            <h1 class="text-xl font-head font-bold text-green-800 dark:text-green-500">Produk Simpanan</h1>
                            <span class="text-sm font-medium text-secondary dark:text-accent bg-secondary/10 dark:bg-primary/20 px-3 py-1 rounded-full">Simpanan Anggota</span>
                        </div>
                        <AccordionPanel title="Apa keuntungan simpanan sukarela dengan akad Mudharabah?"
                            ariaTitle="Keuntungan simpanan sukarela dengan akad Mudharabah">
                            <p class="font-body text-md py-4 dark:text-gray-200">
                                Dengan akad Mudharabah, Anda akan mendapatkan bagi hasil dari keuntungan usaha yang dijalankan koperasi.
                                Nisbah bagi hasil disepakati di awal dan lebih menguntungkan dibanding bunga bank konvensional.
                            </p>
                        </AccordionPanel>
                        <AccordionPanel title="Apakah simpanan saya dijamin aman?"
                            ariaTitle="Apakah simpanan saya dijamin aman">
                            <p class="font-body text-md py-4 dark:text-gray-200">
                                Ya, simpanan anggota dijaga dengan baik oleh Koperasi Syariah Berkah. Setiap simpanan
                                anggota tercatat dengan jelas dalam ledger anggota. Pengelolaan keuangan koperasi diawasi oleh
                                Pengurus dan Pengawas sesuai dengan Anggaran Dasar dan Anggaran Rumah Tangga (AD/ART) koperasi.
                            </p>
                        </AccordionPanel>
                        <AccordionPanel title="Berapa minimal simpanan sukarela?"
                            ariaTitle="Berapa minimal simpanan sukarela">
                            <p class="font-body text-md py-4 dark:text-gray-200">
                                Jumlah minimal simpanan sukarela mengikuti kebijakan Koperasi Syariah Berkah.
                                Untuk informasi nominal terkini dan ketentuan lainnya, silakan langsung menghubungi
                                pengurus koperasi.
                            </p>
                        </AccordionPanel>
                    </div>

                    <!-- Penjualan AMDK Section -->
                    <div class="bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm border border-stroke dark:border-gray-700 rounded-xl p-4 md:p-6 grid gap-4">
                        <div class="flex items-center justify-between gap-4 border-b border-stroke dark:border-gray-700 pb-3">
                            <h1 class="text-xl font-head font-bold text-green-800 dark:text-green-500">Penjualan AMDK</h1>
                            <span class="text-sm font-medium text-secondary dark:text-accent bg-secondary/10 dark:bg-primary/20 px-3 py-1 rounded-full">Layanan Penjualan</span>
                        </div>
                        <AccordionPanel title="Bagaimana cara memesan AMDK?"
                            ariaTitle="Cara memesan AMDK">
                            <p class="font-body text-md py-4 dark:text-gray-200">
                                Pemesanan AMDK dilakukan langsung melalui pengurus koperasi.
                                Silakan hubungi pengurus untuk menyampaikan kebutuhan jumlah dan jenis AMDK.
                            </p>
                        </AccordionPanel>
                        <AccordionPanel title="Siapa saja yang bisa membeli AMDK?"
                            ariaTitle="Siapa yang bisa membeli AMDK">
                            <p class="font-body text-md py-4 dark:text-gray-200">
                                Penjualan AMDK ditujukan untuk anggota koperasi.
                                Untuk ketersediaan layanan bagi non-anggota, silakan konfirmasi langsung ke pengurus.
                            </p>
                        </AccordionPanel>
                        <AccordionPanel title="Berapa harga AMDK terbaru?"
                            ariaTitle="Harga AMDK terbaru">
                            <p class="font-body text-md py-4 dark:text-gray-200">
                                Harga AMDK mengikuti ketentuan terbaru dari koperasi.
                                Untuk mendapatkan informasi harga terkini, silakan langsung menghubungi pengurus.
                            </p>
                        </AccordionPanel>
                        <AccordionPanel title="Bagaimana metode pembayaran AMDK?"
                            ariaTitle="Metode pembayaran AMDK">
                            <p class="font-body text-md py-4 dark:text-gray-200">
                                Metode pembayaran AMDK diinformasikan langsung oleh pengurus saat pemesanan.
                                Detail pembayaran dan pengambilan produk akan disesuaikan dengan ketentuan yang berlaku.
                            </p>
                        </AccordionPanel>
                    </div>

                    <!-- Produk Pembiayaan Section -->
                    <div class="bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm border border-stroke dark:border-gray-700 rounded-xl p-4 md:p-6 grid gap-4">
                        <div class="flex items-center justify-between gap-4 border-b border-stroke dark:border-gray-700 pb-3">
                            <h1 class="text-xl font-head font-bold text-green-800 dark:text-green-500">Produk Pembiayaan</h1>
                            <span class="text-sm font-medium text-primary dark:text-accent bg-primary/10 dark:bg-primary/20 px-3 py-1 rounded-full">Pembiayaan Murabahah</span>
                        </div>
                        <AccordionPanel title="Bagaimana proses pengajuan pembiayaan?"
                            ariaTitle="Proses pengajuan pembiayaan">
                            <p class="font-body text-md py-4 dark:text-gray-200">
                                Pengajuan pembiayaan saat ini dilakukan langsung melalui pengurus koperasi.
                                Karena seluruh proses ditangani langsung oleh pengurus, tidak ada waktu tunggu
                                persetujuan seperti proses online.
                            </p>
                        </AccordionPanel>
                        <AccordionPanel title="Apa saja persyaratan untuk mengajukan pembiayaan?"
                            ariaTitle="Persyaratan mengajukan pembiayaan">
                            <p class="font-body text-md py-4 dark:text-gray-200">
                                Untuk mengajukan pembiayaan, Anda harus memenuhi persyaratan yang telah ditentukan:
                            </p>
                            <div class="card-layout">
                                <h2 class="card-title">Persyaratan Umum:</h2>
                                <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body pt-2">
                                    <li>Merupakan anggota aktif Koperasi Syariah Berkah</li>
                                    <li>Tidak memiliki tunggakan dengan koperasi atau pihak lain</li>
                                    <li>Memiliki karakter dan moral yang baik</li>
                                    <li>Menyediakan agunan/jaminan sesuai ketentuan</li>
                                    <li>Penghasilan/usaha yang stabil</li>
                                </ul>
                            </div>
                            <div class="card-layout mt-4">
                                <h2 class="card-title">Dokumen yang Diperlukan:</h2>
                                <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body pt-2">
                                    <li>Fotokopi KTP dan Kartu Keluarga</li>
                                    <li>Fotokopi Rekening Tabungan/Giro</li>
                                    <li>Slip Gaji</li>
                                    <li>Dokumen pendukung lainnya sesuai kebutuhan</li>
                                </ul>
                            </div>
                        </AccordionPanel>
                    </div>
                </div>
            </section>
            <Footer />
            <HelpButton />
        </div>
    </Base>
</template>
