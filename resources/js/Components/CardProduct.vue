<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    productImage: String,
    productTitle: String,
    productLink: String,
    main: Boolean,
})
</script>

<template>
    <li v-if="props.main"
        class="group duration-300 inline-flex flex-col hover:bg-light-bg bg-secondary rounded-2xl overflow-hidden relative">
        <div class="relative z-2">
            <img :src="props.productImage" class="w-96 h-96 object-cover" alt="">
            <span class="absolute inset-0"
                style="background: linear-gradient(180deg, rgba(255, 255, 255, 0) 56.09%, #FFF 100%);"></span>
        </div>
        <!-- ornament normal -->
        <img src="/public/images/home/ornament_dark.svg"
            class="w-60 h-auto absolute z-1 bottom-14 right-0 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
            alt="">
        <!-- ornament hover -->
        <img src="/public/images/home/ornament_orange.svg"
            class="w-60 h-auto absolute z-1 bottom-14 right-0 transition-opacity duration-300 group-hover:opacity-0"
            alt="">

        <h1 class="group-hover:text-dark-text font-medium text-white text-xl px-6 py-4 w-xs z-2">
            {{ props.productTitle }}
        </h1>
        <Link :href="props.productLink"
            class="bg-light-accent rounded-full mr-auto p-2 group-hover:text-dark-text text-white ml-6 mb-6">
            <span class="icon-[tabler--arrow-right]" style="width: 32px; height: 32px;"></span>
        </Link>
    </li>
    <li v-else
        class="group duration-300 inline-flex flex-col bg-light-bg hover:bg-secondary rounded-2xl overflow-hidden relative">
        <div class="relative z-2">
            <img :src="props.productImage" class="w-96 h-96 object-cover" alt="">
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
            {{ props.productTitle }}
        </h1>
        <Link :href="props.productLink"
            class="bg-light-accent rounded-full mr-auto p-2 text-dark-text group-hover:text-white ml-6 mb-6">
            <span class="icon-[tabler--arrow-right]" style="width: 32px; height: 32px;"></span>
        </Link>
    </li>
</template>
