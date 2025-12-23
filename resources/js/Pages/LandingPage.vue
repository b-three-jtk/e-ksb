<script setup>
import Base from '../Layouts/Base.vue'
</script>

<template>
    <Base>
        <div class="landing-page-content bg-light-bg h-full w-full">
            <section class="hero-section flex items-center h-full w-full">
                <div class="flex flex-col gap-4 justify-left pl-12 pr-40">
                    <h1 class="font-semibold text-5xl w-lg leading-20 tracking-wide">Sejahtera <span
                            class="font-light">bersama</span> Koperasi Syariah Warga Polban</h1>
                    <p class="text-xl w-md tracking-wide leading-8">Rasakan aman, nyaman, dan berkahnya bertransaksi
                        sesuai Al-Qur'an dan Sunnah</p>
                    <div class="flex gap-4">
                        <button class="bg-secondary text-white px-6 py-3 rounded-xl">
                            Daftar Menjadi Anggota
                        </button>
                        <button class="bg-white font-semibold text-dark-text px-6 py-3 rounded-xl">
                            Pelajari Lebih Lanjut
                        </button>
                    </div>
                </div>
                <div class="bg-primary h-full w-full rounded-tl-[100px]">p</div>
            </section>

            <section class="why-section flex h-fit w-full bg-light-bg py-32 px-52 items-center justify-between">
                <div class="flex flex-col gap-12">
                    <h1 class="font-accent text-3xl font-bold w-xl">Mengapa memilih kami daripada bank konvensional &
                        koperasi biasa?</h1>
                    <ul class="flex flex-col gap-12">
                        <li class="flex items-center gap-10">
                            <div class="bg-success-50 rounded-2xl p-4 shadow-lg text-success-700">
                                <span class="icon-[streamline--islam]" style="width: 24px; height: 24px;"></span>
                            </div>
                            <p class="text-2xl w-md">Transaksi 100% sesuai fatwa DSN-MUI (tanpa riba, gharar, maysir)
                            </p>
                        </li>
                        <li class="flex items-center gap-10">
                            <div class="bg-success-50 rounded-2xl p-4 shadow-lg text-success-700">
                                <span class="icon-[tabler--building-bank]" style="width: 24px; height: 24px;"></span>
                            </div>
                            <p class="text-2xl w-md">Pembiayaan cepat tanpa BI checking ketat & angsuran ringan</p>
                        </li>
                        <li class="flex items-center gap-10">
                            <div class="bg-success-50 rounded-2xl p-4 shadow-lg text-success-700">
                                <span class="icon-[icon-park-outline--weixin-market]" style="width: 24px; height: 24px;"></span>
                            </div>
                            <p class="text-2xl w-md">Mini market ritel halal dengan harga grosir khusus anggota</p>
                        </li>
                    </ul>
                </div>
                <h1 class="font-semibold w-lg text-right text-5xl text-dark-text leading-20 tracking-wide"><span
                        class="font-normal">Mengapa memilih</span> Koperasi Syariah Warga Polban?</h1>
            </section>
        </div>
    </Base>
</template>
