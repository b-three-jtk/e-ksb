<script setup>
import Base from '@/Layouts/Base.vue';
import Footer from '@/Layouts/Footer.vue';
import AccordionPanel from '@/Components/AccordionPanel.vue';
import CheckIcon from '@/Icons/CheckIcon.vue';
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
    <Base title="Produk Koperasi">
        <div class="h-full w-full">
            <section class="flex items-center justify-center rounded-b-2xl dark:rounded-b-none relative overflow-hidden h-fit">
                <div class="absolute inset-0 -z-10" :style="{ transform: `translateY(${parallaxOffset}px)` }">
                    <img src="/public/images/home/polban.avif" class="w-full h-full object-cover" alt="Politeknik Negeri Bandung campus building">
                    <div class="absolute inset-0 bg-primary dark:bg-dark-text opacity-70"></div>
                </div>
                <div class="text-center py-40 pb-20 px-4">
                    <h1 class="text-4xl font-semibold text-white mb-4">Produk <span class="text-accent">Koperasi
                            Syariah</span></h1>
                    <p class="text-lg font-body md:text-xl text-white">Solusi keuangan syariah untuk kesejahteraan
                        bersama</p>
                </div>
            </section>
            <section class="px-20 lg:px-52 py-12 grid gap-4 dark:bg-gray-900">
                <h1 class="text-2xl font-semibold text-dark-text dark:text-gray-300">Produk Simpanan</h1>
                <AccordionPanel title="Simpanan Pokok & Wajib" subTitle="Wadiah Yad Amanah"
                    ariaTitle="Simpanan Pokok Panel">
                    <p class="font-body text-md py-4 dark:text-gray-200">Anggota koperasi menitipkan dana untuk dikelola dalam bentuk usaha
                        riil. Dana dapat digunakan sepanjang belum diambil oleh pemiliknya.</p>
                    <div class="card-layout">
                        <h2 class="card-title">Karakteristik</h2>
                        <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body pt-2">
                            <li>Koperasi Syariah bertindak sebagai penerima simpanan, anggota sebagai penyimpan.</li>
                            <li>Koperasi tidak boleh menjanjikan bonus atau imbalan tetap.</li>
                            <li>Koperasi diperbolehkan (tidak wajib) memberikan bonus.</li>
                            <li>Biaya administrasi dapat dibebankan untuk pengelolaan rekening.</li>
                        </ul>
                    </div>
                </AccordionPanel>
                <AccordionPanel title="Simpanan Sukarela" subTitle="Murabahah Mutlaqah"
                    ariaTitle="Simpanan Sukarela Panel">
                    <p class="font-body text-md py-4 dark:text-gray-300">Simpanan untuk kepentingan usaha/bisnis dengan mekanisme bagi
                        hasil. Kerja sama antara pemilik dana (Shahibul Maal) dengan Koperasi Syariah (Mudharib) yang
                        tidak dibatasi jenis usaha, waktu, maupun daerah bisnis.</p>
                    <div class="card-layout">
                        <h2 class="card-title">Karakteristik</h2>
                        <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body pt-2">
                            <li>Koperasi sebagai pengelola dana (Mudharib), anggota sebagai pemilik dana (Shahibul
                                Maal).</li>
                            <li>Pembagian keuntungan dalam bentuk nisbah yang disepakati.</li>
                            <li>Penarikan dana sesuai waktu yang disepakati.</li>
                            <li>Biaya pembukaan dan penutupan rekening dapat dibebankan.</li>
                            <li>Nisbah tidak boleh dikurangi tanpa persetujuan anggota.</li>
                        </ul>
                    </div>
                </AccordionPanel>
                <h1 class="text-2xl font-semibold text-dark-text mt-6 dark:text-gray-300">Produk Pembiayaan</h1>
                <AccordionPanel title="Pembiayaan Murabahah" subTitle="Akad Jual Beli Murabahah"
                    ariaTitle="Pembiayaan Murabahah Panel">
                    <p class="font-body text-md py-4 dark:text-gray-200">Pembiayaan dengan akad jual beli barang, dimana Koperasi Syariah
                        menjual barang kepada anggota dengan harga perolehan ditambah margin keuntungan yang disepakati.
                    </p>
                    <div class="card-layout">
                        <h2 class="card-title">Ketentuan Barang:</h2>
                        <ul class="list-disc text-dark-text dark:text-gray-300 font-body pt-2">
                            <li class="flex items-center gap-2">
                                <CheckIcon width="20px" height="20px" />
                                <p>Pemohon merupakan anggota Koperasi Syariah Warga Polban</p>
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckIcon width="20px" height="20px" />
                                <p>Memiliki spesifikasi jelas</p>
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckIcon width="20px" height="20px" />
                                <p>Dapat diserahterimakan</p>
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckIcon width="20px" height="20px" />
                                <p>Bermanfaat dan tidak dilarang perundang-undangan</p>
                            </li>
                        </ul>
                    </div>
                    <div class="card-layout mt-4">
                        <h2 class="card-title">Ketentuan Harga & Pembayaran:</h2>
                        <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body pt-2">
                            <li>Koperasi wajib menguasai barang secara fisik/hukum sebelum akad (kecuali melalui
                                wakalah).</li>
                            <li>Harga = Harga Perolehan (Ra'sul Mal) + Margin (Ribh).</li>
                            <li>Margin bersifat tetap (fixed) selama jangka waktu pembiayaan.</li>
                            <li>Biaya pembukaan dan penutupan rekening dapat dibebankan.</li>
                            <li>Pembayaran: tunai, tangguh, atau cicilan sesuai akad.</li>
                        </ul>
                    </div>
                </AccordionPanel>
                <div class="card-layout shadow-md">
                    <h1 class="card-title">Persyaratan Pembiayaan</h1>
                    <ul class="flex flex-col gap-3 mt-4 dark:text-gray-200">
                        <li class="py-4 px-6 border-l-2 border-l-primary bg-primary/20 rounded-lg flex items-center gap-2 shadow shadow-primary/80">
                            <CheckIcon width="20px" height="20px" />
                            <p>Pemohon merupakan anggota Koperasi Syariah Warga Polban</p>
                        </li>
                        <li class="py-4 px-6 border-l-2 border-l-primary bg-primary/20 rounded-lg flex items-center gap-2 shadow shadow-primary/80">
                            <CheckIcon width="20px" height="20px" />
                            <p>Tidak memiliki tunggakan dengan koperasi/pihak lain</p>
                        </li>
                        <li class="py-4 px-6 border-l-2 border-l-primary bg-primary/20 rounded-lg flex items-center gap-2 shadow shadow-primary/80">
                            <CheckIcon width="20px" height="20px" />
                            <p>Memiliki karakter dan moral yang baik</p>
                        </li>
                    </ul>
                </div>
                <div class="card-layout shadow-md">
                    <h1 class="card-title">Akad Wakalah</h1>
                    <p class="font-body text-md py-4 dark:text-gray-200">Pelaksanaan Akad Wakalah dilakukan atas persetujuan anggota dengan
                        memenuhi rukun dan syarat
                        sebagai berikut:</p>
                    <h2 class="font-semibold dark:text-gray-300">Syarat Muwakkil (yang mewakilkan)</h2>
                    <ul class="list-disc text-gray-400 dark:text-gray-300 pl-10 font-body">
                        <li>Pemilih sah yang dapat bertindak terhadap hal yang diwakilkan</li>
                        <li>Orang mukallaf atau anak mumayyiz dalam batas tertentu</li>
                    </ul>
                    <h2 class="font-semibold mt-4 dark:text-gray-300">Syarat Wakil (yang mewakili)</h2>
                    <ul class="list-disc text-gray-400 dark:text-gray-300 pl-10 font-body">
                        <li>Cakap hukum</li>
                        <li>Dapat mengerjakan tugas yang diwakilkan</li>
                        <li>Wakil adalah orang yang diberi amanat</li>
                    </ul>
                    <h2 class="font-semibold mt-4 dark:text-gray-300">Hal yang diwakilkan:</h2>
                    <ul class="list-disc text-gray-400 dark:text-gray-300 pl-10 font-body">
                        <li>Diketahui dengan jelas oleh yang mewakili</li>
                        <li>Tidak bertentangan dengan syariah Islam</li>
                        <li>Dapat diwakilkan menurut syariah Islam</li>
                    </ul>
                    <div class="p-6 mt-4 border-l-2 border-l-gray-400 shadow-sm shadow-gray-200 dark:shadow-gray-700 dark:text-gray-200 dark:bg-gray-700 rounded-xl flex font-semibold gap-2">
                        <span class="icon-[tabler--alert-triangle-filled]"
                            style="width: 24px; height: 24px; color: #FFA629;"></span>
                        <p>Ketentuan Penting: Wakalah dengan imbalan bersifat mengikat dan tidak boleh dibatalkan secara
                            sepihak.</p>
                    </div>
                </div>
            </section>
            <Footer />
        </div>
    </Base>
</template>
