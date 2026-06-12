<script setup>
import Base from '@/Layouts/Base.vue';
import Footer from '@/Layouts/Footer.vue';
import AccordionPanel from '@/Components/AccordionPanel.vue';
import CheckIcon from '@/Icons/CheckIcon.vue';
import HelpButton from '@/Components/HelpButton.vue';
import SidebarItem from '@/Components/SidebarItem.vue'
import { ref, onMounted, onUnmounted } from 'vue';

const parallaxOffset = ref(0)
const activeSection = ref('simpanan')

const handleScroll = () => {
    parallaxOffset.value = window.scrollY * 0.5
}

onMounted(() => {
    window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll)
})

const produkMenu = [
    { key: 'simpanan', label: 'Simpanan & Tabungan' },
    { key: 'pembiayaan', label: 'Pembiayaan' },
]

const infoMenu = [
    { key: 'akad', label: 'Akad & Prinsip' },
    { key: 'syarat', label: 'Syarat Keanggotaan' },
]
</script>

<template>
    <Base title="Produk Koperasi">
        <div class="h-full w-full">
            <!-- HERO -->
            <section
                class="flex items-center justify-center rounded-b-2xl dark:rounded-b-none relative overflow-hidden h-fit">
                <div class="absolute inset-0 -z-10" :style="{ transform: `translateY(${parallaxOffset}px)` }">
                    <img src="/public/images/home/alhikmah.webp" class="w-full h-full object-cover"
                        alt="Masjid building">
                    <div class="absolute inset-0 bg-primary dark:bg-dark-text opacity-70"></div>
                </div>
                <div class="text-center py-40 pb-20 px-4">
                    <h1 class="text-4xl font-semibold text-white mb-4">
                        Produk <span class="text-accent">Koperasi Syariah</span>
                    </h1>
                    <p class="text-lg font-body md:text-xl text-white">
                        Layanan keuangan yang amanah, jelas, dan sesuai prinsip syariah untuk kesejahteraan bersama
                    </p>
                </div>
            </section>

            <section class="px-20 lg:px-52 py-12 flex min-h-screen gap-4 dark:bg-gray-900">
                <!-- SIDEBAR -->
                <nav class="w-56 shrink-0 border-r border-gray-200 sticky top-0 h-screen overflow-y-auto py-6">
                    <div class="mb-6">
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 px-4 mb-1">Produk
                        </p>
                        <SidebarItem v-for="item in produkMenu" :key="item.key" :label="item.label"
                            :active="activeSection === item.key" @click="activeSection = item.key" />
                    </div>

                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 px-4 mb-1">Informasi
                        </p>
                        <SidebarItem v-for="item in infoMenu" :key="item.key" :label="item.label"
                            :active="activeSection === item.key" @click="activeSection = item.key" />
                    </div>
                </nav>

                <!-- KONTEN -->
                <main class="flex-1 px-10 py-8 overflow-y-auto">

                    <!-- ============== SIMPANAN & TABUNGAN ============== -->
                    <section v-if="activeSection === 'simpanan'">
                        <h1 class="text-2xl font-semibold text-dark-text dark:text-gray-300">Produk Simpanan & Tabungan
                        </h1>
                        <p class="font-body mt-2 dark:text-gray-300">
                            Koperasi Syariah Berkah menyediakan beberapa pilihan simpanan dan tabungan untuk membantu
                            Anda menabung
                            dengan aman dan sesuai prinsip syariah. Pilih jenis simpanan yang paling sesuai dengan
                            kebutuhan Anda.
                        </p>

                        <!-- Simpanan Pokok -->
                        <div class="flex flex-col gap-2 mt-6">
                            <h2 class="text-xl font-semibold text-primary dark:text-gray-300">1. Simpanan Pokok</h2>
                            <p class="dark:text-gray-200">
                                Simpanan Pokok adalah setoran awal yang dibayarkan <b>satu kali saja</b> saat Anda
                                pertama kali resmi
                                menjadi anggota koperasi. Simpanan ini menjadi tanda bahwa Anda ikut memiliki koperasi.
                            </p>
                            <AccordionPanel title="Akad yang digunakan" subTitle="Musyarakah (Syirkah Inan)"
                                ariaTitle="Simpanan Pokok Panel">
                                <div class="flex flex-col gap-2">
                                    <p class="font-body text-md dark:text-gray-200 pt-3">
                                        Akad <i>Musyarakah</i> berarti Anda dan koperasi <b>bekerja sama dalam
                                            usaha</b>. Modal yang Anda
                                        setor akan dikelola koperasi untuk usaha yang halal. Keuntungan dan risiko
                                        ditanggung bersama
                                        sesuai porsi modal.
                                    </p>

                                    <h3 class="text-lg font-semibold text-dark-text dark:text-gray-300">Posisi Para
                                        Pihak:</h3>
                                    <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body">
                                        <li>Koperasi Syariah sebagai pengelola usaha</li>
                                        <li>Anggota sebagai pemilik modal</li>
                                    </ul>

                                    <h3 class="text-lg font-semibold text-dark-text dark:text-gray-300">Objek Akad:</h3>
                                    <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body">
                                        <li>Modal yang Anda setorkan</li>
                                        <li>Kegiatan usaha yang dijalankan koperasi</li>
                                    </ul>

                                    <div class="card-layout">
                                        <h2 class="card-title">Karakteristik</h2>
                                        <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body">
                                            <li>Disetor satu kali saat menjadi anggota</li>
                                            <li>Tidak dapat ditarik selama Anda masih menjadi anggota aktif</li>
                                            <li>Diakui sebagai modal permanen koperasi</li>
                                            <li>Berhak menerima bagian Sisa Hasil Usaha (SHU)</li>
                                            <li>Ikut menanggung risiko usaha sesuai porsi modal</li>
                                            <li>Tidak ada janji imbal hasil tetap (sesuai prinsip syariah)</li>
                                        </ul>
                                    </div>
                                </div>
                            </AccordionPanel>
                        </div>

                        <!-- Simpanan Wajib -->
                        <div class="flex flex-col gap-2 mt-6">
                            <h2 class="text-xl font-semibold text-primary dark:text-gray-300">2. Simpanan Wajib</h2>
                            <p class="dark:text-gray-200">
                                Simpanan Wajib adalah setoran <b>rutin setiap bulan</b> dari setiap anggota. Simpanan
                                ini membantu
                                memperkuat modal koperasi agar dapat memberi manfaat lebih banyak kepada anggota.
                            </p>
                            <AccordionPanel title="Akad yang digunakan" subTitle="Musyarakah (Syirkah Inan)"
                                ariaTitle="Simpanan Wajib Panel">
                                <div class="flex flex-col gap-2">
                                    <p class="font-body text-md dark:text-gray-200 pt-3">
                                        Sama seperti Simpanan Pokok, akad yang digunakan adalah <i>Musyarakah</i>.
                                        Anggota dan koperasi
                                        menjadi mitra usaha. Hasil usaha dibagi sesuai porsi modal dan kesepakatan.
                                    </p>

                                    <h3 class="text-lg font-semibold text-dark-text dark:text-gray-300">Posisi Para
                                        Pihak:</h3>
                                    <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body">
                                        <li>Koperasi sebagai pengelola usaha</li>
                                        <li>Anggota sebagai pemilik modal</li>
                                    </ul>

                                    <div class="card-layout">
                                        <h2 class="card-title">Karakteristik</h2>
                                        <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body">
                                            <li>Disetor secara rutin setiap bulan</li>
                                            <li>Tidak dapat ditarik selama keanggotaan masih aktif</li>
                                            <li>Menambah modal koperasi (ekuitas)</li>
                                            <li>Berhak menerima bagian SHU sesuai porsi modal</li>
                                            <li>Ikut menanggung risiko usaha</li>
                                        </ul>
                                    </div>
                                </div>
                            </AccordionPanel>
                        </div>

                        <!-- Tabungan Anggota -->
                        <div class="flex flex-col gap-2 mt-6">
                            <h2 class="text-xl font-semibold text-primary dark:text-gray-300">3. Tabungan Anggota</h2>
                            <p class="dark:text-gray-200">
                                Tabungan Anggota adalah tabungan yang <b>dapat disetor dan ditarik kapan saja</b> sesuai
                                kebutuhan
                                harian Anda. Saldo Anda dijamin penuh oleh koperasi.
                            </p>
                            <AccordionPanel title="Akad yang digunakan"
                                subTitle="Wadi'ah Yad Dhamanah (Titipan dengan Jaminan)"
                                ariaTitle="Tabungan Anggota Panel">
                                <div class="flex flex-col gap-2">
                                    <p class="font-body text-md dark:text-gray-200 pt-3">
                                        Akad <i>Wadi'ah Yad Dhamanah</i> artinya Anda <b>menitipkan</b> uang kepada
                                        koperasi. Koperasi
                                        boleh menggunakan dana titipan tersebut, namun <b>wajib mengembalikan saldo Anda
                                            secara penuh</b>
                                        kapan pun Anda menariknya.
                                    </p>

                                    <h3 class="text-lg font-semibold text-dark-text dark:text-gray-300">Posisi Para
                                        Pihak:</h3>
                                    <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body">
                                        <li>Anggota sebagai penitip dana</li>
                                        <li>Koperasi sebagai penerima titipan yang menjamin pengembalian</li>
                                    </ul>

                                    <div class="card-layout">
                                        <h2 class="card-title">Karakteristik</h2>
                                        <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body">
                                            <li>Dapat disetor dan ditarik kapan saja</li>
                                            <li>Saldo dijamin penuh oleh koperasi</li>
                                            <li>Tidak ada bagi hasil tetap, namun koperasi dapat memberikan bonus
                                                sukarela</li>
                                            <li>Cocok untuk kebutuhan transaksi sehari-hari</li>
                                        </ul>
                                    </div>

                                    <div
                                        class="p-6 mt-2 border-l-2 border-l-primary bg-primary/10 dark:bg-gray-700 rounded-xl flex gap-2 font-body dark:text-gray-200">
                                        <span class="icon-[tabler--info-circle-filled] w-6 h-6 text-primary"></span>
                                        <p>Penarikan tabungan tidak boleh melebihi saldo yang Anda miliki.</p>
                                    </div>
                                </div>
                            </AccordionPanel>
                        </div>

                        <!-- Tabungan Berjangka -->
                        <div class="flex flex-col gap-2 mt-6">
                            <h2 class="text-xl font-semibold text-primary dark:text-gray-300">4. Tabungan Berjangka</h2>
                            <p class="dark:text-gray-200">
                                Tabungan Berjangka adalah simpanan dengan <b>jangka waktu tertentu</b>, misalnya 3, 6,
                                atau 12 bulan.
                                Dana baru dapat dicairkan setelah jatuh tempo. Cocok untuk Anda yang ingin menabung
                                sekaligus
                                berinvestasi sesuai prinsip syariah.
                            </p>
                            <AccordionPanel title="Akad yang digunakan"
                                subTitle="Mudharabah Muthlaqah (Kerja Sama Bagi Hasil)"
                                ariaTitle="Tabungan Berjangka Panel">
                                <div class="flex flex-col gap-2">
                                    <p class="font-body text-md dark:text-gray-200 pt-3">
                                        Akad <i>Mudharabah Muthlaqah</i> adalah <b>kerja sama bagi hasil</b>. Anda
                                        menyerahkan dana untuk
                                        dikelola koperasi dalam usaha yang halal. Keuntungan dibagi sesuai <i>nisbah</i>
                                        (perbandingan
                                        bagi hasil) yang disepakati di awal.
                                    </p>

                                    <h3 class="text-lg font-semibold text-dark-text dark:text-gray-300">Posisi Para
                                        Pihak:</h3>
                                    <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body">
                                        <li>Anggota sebagai pemilik modal (<i>shahibul maal</i>)</li>
                                        <li>Koperasi sebagai pengelola dana (<i>mudharib</i>)</li>
                                    </ul>

                                    <div class="card-layout">
                                        <h2 class="card-title">Karakteristik</h2>
                                        <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body">
                                            <li>Memiliki jangka waktu tertentu yang Anda pilih saat pembukaan</li>
                                            <li>Tidak dapat ditarik sebelum jatuh tempo</li>
                                            <li>Imbal hasil berupa bagi hasil sesuai <i>nisbah</i> yang disepakati</li>
                                            <li>Besaran imbal hasil tidak tetap, mengikuti hasil usaha koperasi</li>
                                        </ul>
                                    </div>

                                    <div
                                        class="p-6 mt-2 border-l-2 border-l-orange-400 shadow-sm bg-orange-50 dark:bg-gray-700 dark:text-gray-200 rounded-xl flex gap-2 font-body">
                                        <span
                                            class="icon-[tabler--alert-triangle-filled] w-6 h-6 text-orange-400"></span>
                                        <p>Penting: Dana Tabungan Berjangka <b>tidak dapat ditarik</b> sebelum tanggal
                                            jatuh tempo.</p>
                                    </div>
                                </div>
                            </AccordionPanel>
                        </div>

                        <!-- Tabungan Ibadah -->
                        <div class="flex flex-col gap-2 mt-6">
                            <h2 class="text-xl font-semibold text-primary dark:text-gray-300">5. Tabungan Ibadah</h2>
                            <p class="dark:text-gray-200">
                                Tabungan Ibadah membantu Anda <b>menabung secara bertahap</b> untuk biaya ibadah,
                                seperti Haji, Umrah,
                                Qurban, atau Aqiqah. Anda menetapkan target nominal, lalu menabung rutin sampai target
                                tercapai.
                            </p>
                            <AccordionPanel title="Akad yang digunakan"
                                subTitle="Mudharabah Muthlaqah (Kerja Sama Bagi Hasil)"
                                ariaTitle="Tabungan Ibadah Panel">
                                <div class="flex flex-col gap-2">
                                    <p class="font-body text-md dark:text-gray-200 pt-3">
                                        Akad yang digunakan sama seperti Tabungan Berjangka, yaitu <i>Mudharabah
                                            Muthlaqah</i>.
                                        Dana Anda dikelola koperasi dalam usaha halal, dengan pembagian hasil sesuai
                                        <i>nisbah</i> yang
                                        disepakati.
                                    </p>

                                    <div class="card-layout">
                                        <h2 class="card-title">Karakteristik</h2>
                                        <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body">
                                            <li>Anda menetapkan tujuan ibadah (Haji, Umrah, Qurban, atau Aqiqah)</li>
                                            <li>Anda menetapkan target nominal yang ingin dicapai</li>
                                            <li>Disetor secara rutin sampai target tercapai</li>
                                            <li>Dana baru dapat dicairkan setelah target nominal tercapai</li>
                                            <li>Rekening lama harus dicairkan penuh sebelum membuka rekening baru</li>
                                        </ul>
                                    </div>
                                </div>
                            </AccordionPanel>
                        </div>
                    </section>

                    <!-- ============== PEMBIAYAAN ============== -->
                    <section v-if="activeSection === 'pembiayaan'">
                        <h1 class="text-2xl font-semibold text-dark-text mt-2 dark:text-gray-300">Produk Pembiayaan</h1>
                        <p class="font-body mt-2 dark:text-gray-300">
                            Koperasi membantu Anda memiliki barang yang dibutuhkan melalui mekanisme jual beli yang
                            transparan dan
                            sesuai syariah. Tidak ada bunga, yang ada adalah <b>margin keuntungan yang disepakati di
                                awal</b> dan
                            <b>tetap sampai pembiayaan lunas</b>.
                        </p>

                        <AccordionPanel title="Pembiayaan Murabahah" subTitle="Akad Jual Beli dengan Margin yang Jelas"
                            ariaTitle="Pembiayaan Murabahah Panel">
                            <p class="font-body text-md py-4 dark:text-gray-200">
                                <b>Apa itu Murabahah?</b> Koperasi membelikan barang yang Anda butuhkan, kemudian
                                menjualnya kembali
                                kepada Anda dengan harga pokok ditambah keuntungan (margin) yang disepakati. Anda
                                membayar dengan
                                cara dicicil sesuai jangka waktu yang dipilih, dan <b>cicilan tidak berubah</b> sampai
                                lunas.
                            </p>

                            <div class="card-layout">
                                <h2 class="card-title">Syarat Barang yang Dapat Dibiayai:</h2>
                                <ul class="list-disc text-dark-text dark:text-gray-300 font-body pt-2">
                                    <li class="flex items-center gap-2">
                                        <CheckIcon width="20px" height="20px" />
                                        <p>Barang halal menurut syariah</p>
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <CheckIcon width="20px" height="20px" />
                                        <p>Memiliki spesifikasi yang jelas (jenis, merek, jumlah)</p>
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <CheckIcon width="20px" height="20px" />
                                        <p>Dapat diserahterimakan secara nyata</p>
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <CheckIcon width="20px" height="20px" />
                                        <p>Bermanfaat dan tidak dilarang oleh undang-undang</p>
                                    </li>
                                </ul>
                            </div>

                            <div class="card-layout mt-4">
                                <h2 class="card-title">Ketentuan Harga & Pembayaran:</h2>
                                <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body pt-2">
                                    <li>Koperasi wajib memiliki barang (secara fisik maupun hukum) sebelum dijual ke
                                        Anda,
                                        kecuali melalui akad <i>Wakalah</i> (perwakilan).</li>
                                    <li>Harga jual = Harga Perolehan (Ra'sul Mal) + Margin (Ribh).</li>
                                    <li>Margin keuntungan <b>tetap</b> selama jangka waktu pembiayaan — tidak berubah.
                                    </li>
                                    <li>Pembayaran dapat dilakukan secara tunai, tangguh, atau dicicil sesuai akad.</li>
                                    <li>Biaya pembukaan dan penutupan rekening dapat dibebankan sesuai ketentuan.</li>
                                </ul>
                            </div>
                        </AccordionPanel>

                        <!-- Persyaratan -->
                        <div class="card-layout shadow-md mt-6">
                            <h1 class="card-title">Persyaratan Pengajuan Pembiayaan</h1>
                            <p class="font-body py-2 dark:text-gray-300">Untuk mengajukan pembiayaan, Anda harus
                                memenuhi syarat
                                berikut:</p>
                            <ul class="flex flex-col gap-3 mt-4 dark:text-gray-200">
                                <li
                                    class="py-4 px-6 border-l-2 border-l-primary bg-primary/20 rounded-lg flex items-center gap-2 shadow shadow-primary/80">
                                    <CheckIcon width="20px" height="20px" />
                                    <p>Merupakan anggota aktif Koperasi Syariah Berkah</p>
                                </li>
                                <li
                                    class="py-4 px-6 border-l-2 border-l-primary bg-primary/20 rounded-lg flex items-center gap-2 shadow shadow-primary/80">
                                    <CheckIcon width="20px" height="20px" />
                                    <p>Memiliki usaha atau penghasilan tetap</p>
                                </li>
                                <li
                                    class="py-4 px-6 border-l-2 border-l-primary bg-primary/20 rounded-lg flex items-center gap-2 shadow shadow-primary/80">
                                    <CheckIcon width="20px" height="20px" />
                                    <p>Memiliki Tabungan Anggota yang sudah berjalan minimal satu bulan</p>
                                </li>
                                <li
                                    class="py-4 px-6 border-l-2 border-l-primary bg-primary/20 rounded-lg flex items-center gap-2 shadow shadow-primary/80">
                                    <CheckIcon width="20px" height="20px" />
                                    <p>Tidak memiliki tunggakan dengan koperasi maupun pihak lain</p>
                                </li>
                                <li
                                    class="py-4 px-6 border-l-2 border-l-primary bg-primary/20 rounded-lg flex items-center gap-2 shadow shadow-primary/80">
                                    <CheckIcon width="20px" height="20px" />
                                    <p>Tidak pernah tersangkut masalah pidana</p>
                                </li>
                                <li
                                    class="py-4 px-6 border-l-2 border-l-primary bg-primary/20 rounded-lg flex items-center gap-2 shadow shadow-primary/80">
                                    <CheckIcon width="20px" height="20px" />
                                    <p>Memiliki karakter dan akhlak yang baik</p>
                                </li>
                            </ul>
                        </div>

                        <!-- Dokumen yang Disiapkan -->
                        <div class="card-layout shadow-md mt-6">
                            <h1 class="card-title">Dokumen yang Perlu Disiapkan</h1>
                            <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body pt-2">
                                <li>Slip gaji atau bukti penghasilan</li>
                                <li>Fotokopi KTP pemohon (dan pasangan jika sudah menikah)</li>
                                <li>Fotokopi Kartu Keluarga</li>
                                <li>Fotokopi buku tabungan/rekening koran 3 bulan terakhir</li>
                                <li>Fotokopi dokumen kepemilikan jaminan (jika ada)</li>
                            </ul>
                        </div>

                        <!-- Akad Wakalah -->
                        <div class="card-layout shadow-md mt-6">
                            <h1 class="card-title">Akad Wakalah (Perwakilan)</h1>
                            <p class="font-body text-md py-4 dark:text-gray-200">
                                Pada kondisi tertentu, koperasi dapat memberi kuasa kepada Anda untuk <b>membeli
                                    sendiri</b> barang
                                yang dibutuhkan, atas nama koperasi. Inilah yang disebut akad <i>Wakalah</i>. Akad ini
                                dilaksanakan
                                dengan persetujuan kedua belah pihak dan harus memenuhi syarat berikut:
                            </p>

                            <h2 class="font-semibold dark:text-gray-300">Syarat Muwakkil (pihak yang mewakilkan)</h2>
                            <ul class="list-disc text-gray-400 dark:text-gray-300 pl-10 font-body">
                                <li>Pemilik sah yang dapat bertindak terhadap hal yang diwakilkan</li>
                                <li>Orang dewasa (<i>mukallaf</i>) atau anak <i>mumayyiz</i> dalam batas tertentu</li>
                            </ul>

                            <h2 class="font-semibold mt-4 dark:text-gray-300">Syarat Wakil (pihak yang mewakili)</h2>
                            <ul class="list-disc text-gray-400 dark:text-gray-300 pl-10 font-body">
                                <li>Cakap secara hukum</li>
                                <li>Mampu mengerjakan tugas yang diwakilkan</li>
                                <li>Merupakan orang yang dapat dipercaya (amanah)</li>
                            </ul>

                            <h2 class="font-semibold mt-4 dark:text-gray-300">Hal yang diwakilkan harus:</h2>
                            <ul class="list-disc text-gray-400 dark:text-gray-300 pl-10 font-body">
                                <li>Diketahui dengan jelas oleh wakil</li>
                                <li>Tidak bertentangan dengan syariat Islam</li>
                                <li>Dapat diwakilkan menurut syariat Islam</li>
                            </ul>

                            <div
                                class="p-6 mt-4 border-l-2 border-l-gray-400 shadow-sm shadow-gray-200 dark:shadow-gray-700 dark:text-gray-200 dark:bg-gray-700 rounded-xl flex font-semibold gap-2">
                                <span class="icon-[tabler--alert-triangle-filled] w-6 h-6 text-orange-400"></span>
                                <p>Ketentuan Penting: Wakalah dengan imbalan bersifat mengikat dan tidak boleh
                                    dibatalkan secara
                                    sepihak.</p>
                            </div>
                        </div>

                        <!-- Alur Pembiayaan -->
                        <div class="card-layout shadow-md mt-6">
                            <h1 class="card-title">Alur Pengajuan Pembiayaan</h1>
                            <ol
                                class="list-decimal text-dark-text dark:text-gray-300 pl-5 font-body pt-2 flex flex-col gap-2">
                                <li>Anggota mengisi formulir permohonan pembiayaan dan melengkapi dokumen pendukung.
                                </li>
                                <li>Staf Murabahah memeriksa kelengkapan dan kelayakan pembiayaan Anda.</li>
                                <li>Ketua Murabahah (atau Ketua Koperasi pada kondisi tertentu) memvalidasi permohonan.
                                </li>
                                <li>Jika disetujui, dilanjutkan dengan pembayaran uang muka (jika diminta).</li>
                                <li>Pembelian barang dilakukan oleh koperasi atau melalui akad Wakalah.</li>
                                <li>Penandatanganan akad Murabahah dan penyerahan barang kepada anggota.</li>
                                <li>Anggota membayar cicilan sesuai jadwal yang telah disepakati.</li>
                            </ol>
                        </div>

                        <!-- Pembayaran Angsuran -->
                        <div class="card-layout shadow-md mt-6">
                            <h1 class="card-title">Pembayaran Angsuran</h1>
                            <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body pt-2">
                                <li>Cicilan dibayarkan sesuai jadwal dan nominal yang tercantum di akad.</li>
                                <li>Nominal cicilan <b>tetap</b> sejak awal hingga lunas, tidak berubah.</li>
                                <li>Pembayaran dapat dilakukan secara tunai di koperasi atau transfer ke rekening
                                    koperasi.</li>
                                <li>Anggota akan menerima kuitansi sebagai bukti setiap kali membayar.</li>
                                <li>Setelah seluruh cicilan lunas, koperasi menerbitkan Berita Acara Pelunasan.</li>
                            </ul>
                        </div>

                        <!-- Pelunasan Sebelum Jatuh Tempo -->
                        <div class="card-layout shadow-md mt-6">
                            <h1 class="card-title">Pelunasan Sebelum Jatuh Tempo</h1>
                            <p class="font-body py-2 dark:text-gray-300">
                                Anda dapat melunasi pembiayaan lebih cepat dari jadwal. Berikut ketentuannya:
                            </p>
                            <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body pt-2">
                                <li>Pengajuan dilakukan paling lambat <b>2 hari sebelum tanggal jatuh tempo</b>
                                    berikutnya.</li>
                                <li>Anda hanya membayar sisa pokok dan margin sampai bulan berjalan.</li>
                                <li>Margin untuk bulan-bulan setelahnya <b>tidak ditagihkan</b>.</li>
                                <li>Koperasi akan menerbitkan Berita Acara Pelunasan setelah pembayaran selesai.</li>
                            </ul>
                        </div>
                    </section>

                    <!-- ============== AKAD & PRINSIP ============== -->
                    <section v-if="activeSection === 'akad'">
                        <h1 class="text-2xl font-semibold text-dark-text dark:text-gray-300">Akad & Prinsip Syariah</h1>
                        <p class="font-body mt-2 dark:text-gray-300">
                            Seluruh layanan koperasi dijalankan dengan mengikuti prinsip syariah Islam. Berikut
                            penjelasan
                            singkat tentang akad-akad yang digunakan, agar Anda dapat memahami setiap layanan dengan
                            jelas.
                        </p>

                        <!-- Musyarakah -->
                        <div class="flex flex-col gap-2 mt-6">
                            <h2 class="text-xl font-semibold text-primary dark:text-gray-300">Musyarakah</h2>
                            <p class="dark:text-gray-200">
                                <b>Akad kerja sama usaha</b> antara dua pihak atau lebih. Masing-masing pihak
                                menyetorkan modal,
                                dan keuntungan dibagi sesuai porsi modal atau kesepakatan. Risiko juga ditanggung
                                bersama.
                                Akad ini digunakan pada <b>Simpanan Pokok</b> dan <b>Simpanan Wajib</b>.
                            </p>
                        </div>

                        <!-- Wadi'ah Yad Dhamanah -->
                        <div class="flex flex-col gap-2 mt-6">
                            <h2 class="text-xl font-semibold text-primary dark:text-gray-300">Wadi'ah Yad Dhamanah</h2>
                            <p class="dark:text-gray-200">
                                <b>Akad titipan dengan jaminan.</b> Anda menitipkan uang kepada koperasi. Koperasi boleh
                                memanfaatkan dana tersebut, namun wajib mengembalikan saldo Anda secara penuh kapan saja
                                diminta.
                                Akad ini digunakan pada <b>Tabungan Anggota</b>.
                            </p>
                        </div>

                        <!-- Mudharabah Muthlaqah -->
                        <div class="flex flex-col gap-2 mt-6">
                            <h2 class="text-xl font-semibold text-primary dark:text-gray-300">Mudharabah Muthlaqah</h2>
                            <p class="dark:text-gray-200">
                                <b>Akad kerja sama bagi hasil.</b> Anda menyediakan modal, koperasi yang mengelolanya
                                dalam usaha
                                halal. Keuntungan dibagi sesuai <i>nisbah</i> yang disepakati di awal. Kerugian (selama
                                bukan
                                karena kelalaian pengelola) ditanggung oleh pemilik modal. Akad ini digunakan pada
                                <b>Tabungan Berjangka</b> dan <b>Tabungan Ibadah</b>.
                            </p>
                        </div>

                        <!-- Murabahah -->
                        <div class="flex flex-col gap-2 mt-6">
                            <h2 class="text-xl font-semibold text-primary dark:text-gray-300">Murabahah</h2>
                            <p class="dark:text-gray-200">
                                <b>Akad jual beli dengan margin yang transparan.</b> Koperasi membelikan barang yang
                                Anda butuhkan,
                                lalu menjualnya kembali kepada Anda dengan harga pokok ditambah margin (keuntungan) yang
                                disepakati.
                                Cicilan bersifat tetap sampai lunas. Akad ini digunakan pada <b>produk Pembiayaan</b>.
                            </p>
                        </div>

                        <!-- Wakalah -->
                        <div class="flex flex-col gap-2 mt-6">
                            <h2 class="text-xl font-semibold text-primary dark:text-gray-300">Wakalah</h2>
                            <p class="dark:text-gray-200">
                                <b>Akad pemberian kuasa.</b> Koperasi memberi kuasa kepada Anda (atau pihak lain) untuk
                                membeli barang
                                atas nama koperasi. Setelah barang dimiliki koperasi (secara prinsip), barulah dilakukan
                                akad jual
                                beli Murabahah dengan Anda. Akad ini melengkapi <b>Pembiayaan Murabahah bil Wakalah</b>.
                            </p>
                        </div>

                        <!-- Prinsip Dasar -->
                        <div class="card-layout shadow-md mt-6">
                            <h2 class="card-title">Prinsip Dasar yang Dipegang Koperasi</h2>
                            <ul class="flex flex-col gap-3 mt-4 dark:text-gray-200">
                                <li class="flex items-center gap-2">
                                    <CheckIcon width="20px" height="20px" />
                                    <p><b>Bebas Riba</b> — tidak ada bunga, hanya margin yang disepakati di awal</p>
                                </li>
                                <li class="flex items-center gap-2">
                                    <CheckIcon width="20px" height="20px" />
                                    <p><b>Bebas Gharar</b> — tidak ada transaksi yang tidak jelas atau menipu</p>
                                </li>
                                <li class="flex items-center gap-2">
                                    <CheckIcon width="20px" height="20px" />
                                    <p><b>Bebas Maysir</b> — tidak ada unsur judi atau spekulasi</p>
                                </li>
                                <li class="flex items-center gap-2">
                                    <CheckIcon width="20px" height="20px" />
                                    <p><b>Halal dan Thayyib</b> — semua usaha dan barang yang dibiayai harus halal dan
                                        baik</p>
                                </li>
                                <li class="flex items-center gap-2">
                                    <CheckIcon width="20px" height="20px" />
                                    <p><b>Diawasi DPS</b> — Dewan Pengawas Syariah memastikan semua transaksi sesuai
                                        prinsip syariah</p>
                                </li>
                            </ul>
                        </div>
                    </section>

                    <!-- ============== SYARAT KEANGGOTAAN ============== -->
                    <section v-if="activeSection === 'syarat'">
                        <h1 class="text-2xl font-semibold text-dark-text dark:text-gray-300">Syarat Menjadi Anggota</h1>
                        <p class="font-body mt-2 dark:text-gray-300">
                            Untuk dapat menikmati seluruh layanan koperasi (simpanan, tabungan, dan pembiayaan), Anda
                            harus terlebih
                            dahulu menjadi anggota Koperasi Syariah Berkah. Berikut syarat dan tata caranya.
                        </p>

                        <!-- Syarat -->
                        <div class="card-layout shadow-md mt-6">
                            <h2 class="card-title">Syarat Pendaftaran Anggota</h2>
                            <ul class="flex flex-col gap-3 mt-4 dark:text-gray-200">
                                <li
                                    class="py-4 px-6 border-l-2 border-l-primary bg-primary/20 rounded-lg flex items-center gap-2 shadow shadow-primary/80">
                                    <CheckIcon width="20px" height="20px" />
                                    <p>Warga Negara Indonesia</p>
                                </li>
                                <li
                                    class="py-4 px-6 border-l-2 border-l-primary bg-primary/20 rounded-lg flex items-center gap-2 shadow shadow-primary/80">
                                    <CheckIcon width="20px" height="20px" />
                                    <p>Berdomisili di wilayah kerja koperasi</p>
                                </li>
                                <li
                                    class="py-4 px-6 border-l-2 border-l-primary bg-primary/20 rounded-lg flex items-center gap-2 shadow shadow-primary/80">
                                    <CheckIcon width="20px" height="20px" />
                                    <p>Tunduk pada Anggaran Dasar dan Anggaran Rumah Tangga koperasi</p>
                                </li>
                                <li
                                    class="py-4 px-6 border-l-2 border-l-primary bg-primary/20 rounded-lg flex items-center gap-2 shadow shadow-primary/80">
                                    <CheckIcon width="20px" height="20px" />
                                    <p>Cakap melakukan tindakan hukum (dewasa)</p>
                                </li>
                                <li
                                    class="py-4 px-6 border-l-2 border-l-primary bg-primary/20 rounded-lg flex items-center gap-2 shadow shadow-primary/80">
                                    <CheckIcon width="20px" height="20px" />
                                    <p>Bersedia membayar Simpanan Pokok dan Simpanan Wajib sesuai ketentuan</p>
                                </li>
                            </ul>
                        </div>

                        <!-- Dokumen -->
                        <div class="card-layout shadow-md mt-6">
                            <h2 class="card-title">Dokumen yang Perlu Disiapkan</h2>
                            <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body pt-2">
                                <li>Fotokopi KTP yang masih berlaku</li>
                                <li>Fotokopi Kartu Keluarga</li>
                                <li>Pas foto terbaru</li>
                                <li>Surat persetujuan menjadi anggota koperasi (disediakan oleh koperasi)</li>
                                <li>Data ahli waris (nama, hubungan keluarga, dan kontak)</li>
                            </ul>
                        </div>

                        <!-- Alur -->
                        <div class="card-layout shadow-md mt-6">
                            <h2 class="card-title">Alur Pendaftaran</h2>
                            <ol
                                class="list-decimal text-dark-text dark:text-gray-300 pl-5 font-body pt-2 flex flex-col gap-2">
                                <li>Datang ke kantor koperasi dan menemui Sekretaris.</li>
                                <li>Mengisi formulir pendaftaran dan melengkapi dokumen pendukung.</li>
                                <li>Sekretaris akan memeriksa kelengkapan data dan dokumen Anda.</li>
                                <li>Jika sudah lengkap, Anda menyetorkan Simpanan Pokok sebagai tanda resmi menjadi
                                    anggota.</li>
                                <li>Anda akan menerima kredensial akun (kode anggota dan kata sandi) untuk mengakses
                                    aplikasi.</li>
                            </ol>
                        </div>

                        <!-- Hak & Kewajiban -->
                        <div class="grid md:grid-cols-2 gap-4 mt-6">
                            <div class="card-layout shadow-md">
                                <h2 class="card-title">Hak Anggota</h2>
                                <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body pt-2">
                                    <li>Menghadiri dan menyampaikan pendapat dalam Rapat Anggota</li>
                                    <li>Memilih dan dipilih sebagai pengurus atau pengawas</li>
                                    <li>Mendapatkan pelayanan koperasi</li>
                                    <li>Memperoleh informasi perkembangan koperasi</li>
                                    <li>Menerima bagian Sisa Hasil Usaha (SHU)</li>
                                </ul>
                            </div>

                            <div class="card-layout shadow-md">
                                <h2 class="card-title">Kewajiban Anggota</h2>
                                <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body pt-2">
                                    <li>Mematuhi AD/ART dan keputusan Rapat Anggota</li>
                                    <li>Membayar Simpanan Pokok dan Simpanan Wajib</li>
                                    <li>Berpartisipasi aktif dalam kegiatan koperasi</li>
                                    <li>Menjaga nama baik koperasi</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Info Box -->
                        <div
                            class="p-6 mt-6 border-l-2 border-l-primary bg-primary/10 dark:bg-gray-700 rounded-xl flex gap-2 font-body dark:text-gray-200">
                            <span class="icon-[tabler--info-circle-filled] w-6 h-6 text-primary"></span>
                            <p>Untuk informasi lebih lanjut atau bantuan pendaftaran, silakan hubungi Sekretaris
                                Koperasi Syariah
                                Berkah di kantor koperasi.</p>
                        </div>
                    </section>

                </main>
            </section>

            <Footer />
            <HelpButton />
        </div>
    </Base>
</template>
