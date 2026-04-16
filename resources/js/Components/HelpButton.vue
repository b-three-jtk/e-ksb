<script setup>
const whatsappNumber = '62345678910'; // TODO: Ganti dengan nomor WhatsApp resmi koperasi

const openWhatsapp = () => {
    const message = 'Halo, saya ingin mendapatkan bantuan mengenai layanan Koperasi Syariah Berkah.';
    const encodedMessage = encodeURIComponent(message);
    const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${encodedMessage}`;
    window.open(whatsappUrl, '_blank');
}
</script>

<template>
    <button
        @click="openWhatsapp"
        class="fixed bottom-8 right-8 z-40 flex items-center justify-center w-16 h-16 rounded-full bg-green-500 hover:bg-green-600 shadow-lg transition-all duration-300 hover:scale-110 focus:outline-none focus:ring-4 focus:ring-orange-300 dark:focus:ring-orange-700"
        aria-label="Hubungi kami melalui WhatsApp"
        title="Hubungi kami"
    >
        <span class="icon-[mdi--message-text]" style="width: 28px; height: 28px; color: white;"></span>
    </button>
</template>

<style scoped>
button:active {
    transform: scale(0.95);
}
</style>
