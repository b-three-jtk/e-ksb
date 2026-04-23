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
    { key: 'penjualan', label: 'Jual Beli AMDK' },
]

const infoMenu = [
    { key: 'akad', label: 'Akad & Prinsip' },
    { key: 'syarat', label: 'Syarat Keanggotaan' },
]
</script>

<template>
    <Base title="Produk Koperasi">
        <div class="h-full w-full">
            <section
                class="flex items-center justify-center rounded-b-2xl dark:rounded-b-none relative overflow-hidden h-fit">
                <div class="absolute inset-0 -z-10" :style="{ transform: `translateY(${parallaxOffset}px)` }">
                    <img src="/public/images/home/alhikmah.webp" class="w-full h-full object-cover"
                        alt="Politeknik Negeri Bandung campus building">
                    <div class="absolute inset-0 bg-primary dark:bg-dark-text opacity-70"></div>
                </div>
                <div class="text-center py-40 pb-20 px-4">
                    <h1 class="text-4xl font-semibold text-white mb-4">Produk <span class="text-accent">Koperasi
                            Syariah Berkah</span></h1>
                    <p class="text-lg font-body md:text-xl text-white">Solusi keuangan syariah untuk kesejahteraan
                        bersama</p>
                </div>
            </section>
            <section class="px-20 lg:px-52 py-12 flex min-h-screen gap-4 dark:bg-gray-900">
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
                <main class="flex-1 px-10 py-8 overflow-y-auto">
                    <section v-if="activeSection === 'simpanan'">
                        <h1 class="text-2xl font-semibold text-dark-text dark:text-gray-300">Produk Simpanan</h1>
                        <div class="flex flex-col gap-2 mt-4">
                            <h2 class="text-xl font-semibold text-primary dark:text-gray-300">Simpanan Pokok</h2>
                            <p>Simpanan Pokok adalah simpanan yang wajib disetorkan oleh anggota koperasi sebagai bentuk
                                penyertaan modal awal dalam koperasi. Berfungsi untuk membentuk dan memperkuat ekuitas
                                (modal)
                                koperasi.</p>
                            <AccordionPanel title="Akad yang digunakan" subTitle="Musyarakah (Syirkah Inan)"
                                ariaTitle="Simpanan Pokok Panel">
                                <div class="flex flex-col gap-2">
                                    <p class="font-body text-md dark:text-gray-200 pt-3">Anggota koperasi menitipkan
                                        dana
                                        kepada koperasi dengan akad musyarakah, dimana anggota dan koperasi menjadi
                                        mitra
                                        dalam
                                        usaha koperasi. Koperasi mengelola dana tersebut untuk kegiatan usaha yang
                                        menghasilkan
                                        keuntungan, yang kemudian dibagi sesuai dengan nisbah yang disepakati.</p>
                                    <h3 class="text-lg font-semibold text-dark-text dark:text-gray-300">Posisi Para
                                        Pihak:
                                    </h3>
                                    <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body">
                                        <li>Koperasi Syariah sebagai pengelola dana (Mudharib)</li>
                                        <li>Anggota sebagai pemilik dana (Shahibul Maal)</li>
                                    </ul>
                                    <h3 class="text-lg font-semibold text-dark-text dark:text-gray-300">Objek Akad:
                                    </h3>
                                    <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body">
                                        <li>Modal yang disetorkan oleh anggota</li>
                                        <li>Aktivitas usaha yang dilakukan oleh koperasi</li>
                                    </ul>
                                    <div class="card-layout">
                                        <h2 class="card-title">Karakteristik</h2>
                                        <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body">
                                            <li>Disetor satu kali saat masuk menjadi anggota</li>
                                            <li>Tidak dapat ditarik selama status keanggotaan aktif</li>
                                            <li>Diakui sebagai modal permanen (equity)</li>
                                            <li>Mendapat bagian SHU sesuai porsi modal</li>
                                            <li>Menanggung risiko usaha proporsional terhadap modal</li>
                                            <li>Tidak ada jaminan imbal hasil tetap</li>
                                        </ul>
                                    </div>
                                </div>
                            </AccordionPanel>
                        </div>
                        <div class="flex flex-col gap-2 mt-4">
                            <h2 class="text-xl font-semibold text-primary dark:text-gray-300">Simpanan Wajib</h2>
                            <p>Simpanan Wajib adalah simpanan yang wajib disetorkan oleh anggota koperasi setiap bulan. Berfungsi untuk meningkatkan kapasitas pembiayaan koperasi.</p>
                            <AccordionPanel title="Akad yang digunakan" subTitle="Musyarakah (Syirkah Inan)"
                                ariaTitle="Simpanan Wajib Panel">
                                <div class="flex flex-col gap-2">
                                    <p class="font-body text-md dark:text-gray-200 pt-3">Anggota koperasi menitipkan
                                        dana
                                        kepada koperasi dengan akad musyarakah, dimana anggota dan koperasi menjadi
                                        mitra
                                        dalam
                                        usaha koperasi. Koperasi mengelola dana tersebut untuk kegiatan usaha yang
                                        menghasilkan
                                        keuntungan, yang kemudian dibagi sesuai dengan nisbah yang disepakati.</p>
                                    <h3 class="text-lg font-semibold text-dark-text dark:text-gray-300">Posisi Para
                                        Pihak:
                                    </h3>
                                    <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body">
                                        <li>Koperasi Syariah sebagai pengelola dana (Mudharib)</li>
                                        <li>Anggota sebagai pemilik dana (Shahibul Maal)</li>
                                    </ul>
                                    <h3 class="text-lg font-semibold text-dark-text dark:text-gray-300">Objek Akad:
                                    </h3>
                                    <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body">
                                        <li>Modal yang disetorkan oleh anggota</li>
                                        <li>Aktivitas usaha yang dilakukan oleh koperasi</li>
                                    </ul>
                                    <div class="card-layout">
                                        <h2 class="card-title">Karakteristik</h2>
                                        <ul class="list-disc text-dark-text dark:text-gray-300 pl-5 font-body">
                                            <li>Disetor satu kali saat masuk menjadi anggota</li>
                                            <li>Tidak dapat ditarik selama status keanggotaan aktif</li>
                                            <li>Diakui sebagai modal permanen (equity)</li>
                                            <li>Mendapat bagian SHU sesuai porsi modal</li>
                                            <li>Menanggung risiko usaha proporsional terhadap modal</li>
                                            <li>Tidak ada jaminan imbal hasil tetap</li>
                                        </ul>
                                    </div>
                                </div>
                            </AccordionPanel>
                        </div>
                    </section>
                    <section v-if="activeSection === 'pembiayaan'">
                        <h1 class="text-2xl font-semibold text-dark-text mt-6 dark:text-gray-300">Produk Pembiayaan</h1>
                        <AccordionPanel title="Pembiayaan Murabahah" subTitle="Akad Jual Beli Murabahah"
                            ariaTitle="Pembiayaan Murabahah Panel">
                            <p class="font-body text-md py-4 dark:text-gray-200">Pembiayaan dengan akad jual beli
                                barang,
                                dimana
                                Koperasi Syariah
                                menjual barang kepada anggota dengan harga perolehan ditambah margin keuntungan yang
                                disepakati.
                            </p>
                            <div class="card-layout">
                                <h2 class="card-title">Ketentuan Barang:</h2>
                                <ul class="list-disc text-dark-text dark:text-gray-300 font-body pt-2">
                                    <li class="flex items-center gap-2">
                                        <CheckIcon width="20px" height="20px" />
                                        <p>Pemohon merupakan anggota Koperasi Syariah Berkah</p>
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
                                <li
                                    class="py-4 px-6 border-l-2 border-l-primary bg-primary/20 rounded-lg flex items-center gap-2 shadow shadow-primary/80">
                                    <CheckIcon width="20px" height="20px" />
                                    <p>Pemohon merupakan anggota Koperasi Syariah Berkah</p>
                                </li>
                                <li
                                    class="py-4 px-6 border-l-2 border-l-primary bg-primary/20 rounded-lg flex items-center gap-2 shadow shadow-primary/80">
                                    <CheckIcon width="20px" height="20px" />
                                    <p>Tidak memiliki tunggakan dengan koperasi/pihak lain</p>
                                </li>
                                <li
                                    class="py-4 px-6 border-l-2 border-l-primary bg-primary/20 rounded-lg flex items-center gap-2 shadow shadow-primary/80">
                                    <CheckIcon width="20px" height="20px" />
                                    <p>Memiliki karakter dan moral yang baik</p>
                                </li>
                            </ul>
                        </div>
                        <div class="card-layout shadow-md">
                            <h1 class="card-title">Akad Wakalah</h1>
                            <p class="font-body text-md py-4 dark:text-gray-200">Pelaksanaan Akad Wakalah dilakukan atas
                                persetujuan anggota dengan
                                memenuhi rukun dan syarat
                                sebagai berikut:</p>
                            <h2 class="font-semibold dark:text-gray-300">Syarat Muwakkil (yang mewakilkan)</h2>
                            <ul class="list-disc text-gray-400 dark:text-gray-300 pl-10 font-body">
                                <li>Pemilik sah yang dapat bertindak terhadap hal yang diwakilkan</li>
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
                            <div
                                class="p-6 mt-4 border-l-2 border-l-gray-400 shadow-sm shadow-gray-200 dark:shadow-gray-700 dark:text-gray-200 dark:bg-gray-700 rounded-xl flex font-semibold gap-2">
                                <span class="icon-[tabler--alert-triangle-filled] w-6 h-6 text-orange-400"></span>
                                <p>Ketentuan Penting: Wakalah dengan imbalan bersifat mengikat dan tidak boleh
                                    dibatalkan
                                    secara
                                    sepihak.</p>
                            </div>
                        </div>
                    </section>
                    <section v-if="activeSection === 'penjualan'">

                    </section>
                    <section v-if="activeSection === 'akad'">
                    </section>
                    <section v-if="activeSection === 'syarat'">
                    </section>
                </main>
            </section>
            <Footer />
            <HelpButton />
        </div>
    </Base>
</template>
