<script setup>
    import { Head } from '@inertiajs/vue3';
    import ThemeProvider from '@/Layouts/Admin/ThemeProvider.vue';
    import ThemeToggler from '@/Components/ThemeToggler.vue';
    import { ref, onMounted } from 'vue';

    const props = defineProps({
        title: {
            type: String,
            default: ''
        }
    });

    const imageLoaded = ref(false);

    onMounted(() => {
        const img = new Image();
        img.src = '/images/home/al-hikmah.png';
        
        img.onload = () => {
            imageLoaded.value = true;
        };
        
        img.onerror = () => {
            imageLoaded.value = true;
        };
        
        // Jika sudah di-cache, langsung set true
        if (img.complete) {
            imageLoaded.value = true;
        }
        
        // Fallback timeout to ensure content displays after 3 seconds
        setTimeout(() => {
            imageLoaded.value = true;
        }, 3000);
    });
</script>

<template>
    <Head>
        <title>{{ title }} - Koperasi Syariah Berkah</title>
        <link rel="preload" as="image" href="/images/home/al-hikmah.png" />
    </Head>
    <ThemeProvider>
      <div
        class="min-h-screen bg-no-repeat bg-bottom bg-cover bg-gray-100 dark:bg-gray-900 transition-opacity duration-300"
        :class="{ 'opacity-100': imageLoaded, 'opacity-0': !imageLoaded }"
        :style="imageLoaded ? 'background-image: url(\'/images/home/al-hikmah.png\')' : ''"
      >
        <div class="min-h-screen bg-linear-to-b from-transparent to-black flex items-center justify-center relative">
          <div class="absolute top-6 right-6">
            <ThemeToggler />
          </div>
          <slot v-if="imageLoaded" />
        </div>
      </div>
    </ThemeProvider>
</template>
