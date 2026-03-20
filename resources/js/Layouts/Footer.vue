
<script setup>
import { Link } from '@inertiajs/vue3'
</script>

<template>
    <footer class="bg-light-bg dark:bg-dark-text dark:text-gray-300">
        <div class="flex flex-col md:flex-row p-14 justify-between">
            <div class="flex flex-col gap-4 mb-9">
                <img class="max-w-32 mx-auto" src="" alt="">
                <a href="" class="py-4 px-8 flex items-center gap-2 border-2 border-accent-500 mx-auto rounded-4xl">
                    <span class="icon-[tabler--message]" style="width: 24px; height: 24px;"></span>
                    Feedback
                </a>
            </div>
            <div class="flex flex-col text-center md:text-left md:flex-row gap-10 md:gap-20">
                <div class="flex flex-col gap-5">
                    <h1 class="text-sm font-bold">INFO</h1>
                    <ul>
                        <li class="hover:text-primary"><Link href="products">Produk Koperasi</Link></li>
                        <li class="hover:text-primary"><Link href="faq">FAQ</Link></li>
                    </ul>
                </div>
                <div class="flex flex-col gap-5">
                    <h1 class="text-sm font-bold">ABOUT</h1>
                    <h3><Link href="about" class="hover:text-primary">About Us</Link></h3>
                </div>
                <div class="md:max-w-52 flex flex-col gap-5">
                    <h1 class="text-sm font-bold">CONTACT US</h1>
                    <h3>Puri Cipageran Indah 2, Desa Tanimulya, Kecamatan Ngamprah, Kabupaten Bandung Barat</h3>
                </div>
            </div>
        </div>
        <div class="flex justify-between px-14 py-8">
            <h1 class="text-gray-400">© 2026 — Copyright KoTA203</h1>
            <div class="flex gap-4 text-dark-text dark:text-light-accent">
                <Link class="hover:text-primary" href="https://www.instagram.com/">
                    <span class="icon-[tabler--brand-instagram]" style="width: 32px; height: 32px;"></span>
                </Link>
                <Link class="hover:text-primary" href="https://www.facebook.com/">
                    <span class="icon-[tabler--brand-facebook]" style="width: 32px; height: 32px;"></span>
                </Link>
                <Link class="hover:text-primary" href="https://www.youtube.com/">
                    <span class="icon-[tabler--brand-youtube]" style="width: 32px; height: 32px;"></span>
                </Link>
            </div>
        </div>
    </footer>
</template>
