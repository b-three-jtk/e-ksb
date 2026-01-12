<script setup>
    import { Head } from '@inertiajs/vue3';

    const props = defineProps({
        title: {
            type: String,
            default: ''
        }
    });
</script>

<template>
    <Head>
        <title>{{ title }} - Koperasi Syariah Warga Polban</title>
    </Head>
  <div
    class="min-h-screen bg-no-repeat bg-center bg-cover"
    style="background-image: url('/images/auth/bg_auth.png')"
  >
    <div class="min-h-screen bg-blue-900/60 flex items-center justify-center">
      <slot />
    </div>
  </div>
</template>
