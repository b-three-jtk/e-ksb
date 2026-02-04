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
            <section class="flex items-center justify-center rounded-b-2xl dark:rounded-b-none relative overflow-hidden h-fit">
                <div class="absolute inset-0 -z-10" :style="{ transform: `translateY(${parallaxOffset}px)` }">
                    <img src="/public/images/home/polban.avif" class="w-full h-full object-cover" alt="Politeknik Negeri Bandung campus building">
                    <div class="absolute inset-0 bg-primary dark:bg-dark-text opacity-70"></div>
                </div>
                <div class="text-center py-40 pb-20 px-4">
                    <h1 class="text-4xl font-semibold text-white mb-4">Pusat <span class="text-accent">Bantuan</span></h1>
                    <p class="text-lg font-body md:text-xl text-white">Pertanyaan yang sering diajukan seputar produk dan layanan koperasi</p>
                </div>
            </section>
            <section class="px-20 lg:px-52 py-12 grid gap-4 dark:bg-gray-900">
                <!-- Umum Section -->
                <h1 class="text-2xl font-semibold text-dark-text dark:text-gray-300">Umum</h1>
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
                        Anda dapat mendaftar sebagai anggota dengan mengisi formulir pendaftaran, menyerahkan dokumen identitas KTP, Foto diri, 
                        dan membayar simpanan pokok sesuai ketentuan yang berlaku. Prosesnya mudah dan cepat.
                    </p>
                </AccordionPanel>

                <!-- Produk Simpanan Section -->
                <h1 class="text-2xl font-semibold text-dark-text mt-6 dark:text-gray-300">Produk Simpanan</h1>
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
                        Ya, simpanan anggota dijaga dengan baik oleh Koperasi Syariah Warga Polban. Setiap simpanan 
                        anggota tercatat dengan jelas dalam ledger anggota. Pengelolaan keuangan koperasi diawasi oleh 
                        Pengurus dan Pengawas sesuai dengan Anggaran Dasar dan Anggaran Rumah Tangga (AD/ART) koperasi.
                    </p>
                </AccordionPanel>
                <!-- TODO: ubah simpanan minimal sukarela -->
                <AccordionPanel title="Berapa minimal simpanan sukarela?" 
                    ariaTitle="Berapa minimal simpanan sukarela">
                    <p class="font-body text-md py-4 dark:text-gray-200">
                        Jumlah minimal simpanan sukarela tergantung kebijakan Koperasi Syariah 
                        Warga Polban. Untuk informasi lebih lengkap mengenai nominal minimal simpanan dan ketentuan 
                        lainnya, silakan menghubungi kantor pusat koperasi melalui help button yang tersedia.
                    </p> 
                </AccordionPanel>

                <!-- Produk Pembiayaan Section -->
                <h1 class="text-2xl font-semibold text-dark-text mt-6 dark:text-gray-300">Produk Pembiayaan</h1>
                <AccordionPanel title="Berapa lama proses persetujuan pembiayaan?" 
                    ariaTitle="Lama proses persetujuan pembiayaan">
                    <p class="font-body text-md py-4 dark:text-gray-200">
                        Proses persetujuan pembiayaan umumnya memerlukan waktu 3-7 hari kerja setelah dokumen 
                        persyaratan lengkap diterima. Waktu proses dapat bervariasi tergantung kelengkapan dokumen 
                        dan analisa yang diperlukan. Pihak koperasi akan memberikan informasi lebih detail mengenai 
                        timeline proses saat Anda mengajukan permohonan.
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
                            <li>Merupakan anggota aktif Koperasi Syariah Warga Polban</li>
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
            </section>
            <Footer />
            <HelpButton />
        </div>
    </Base>
</template>
