<script setup>
import Base from '@/Layouts/Base.vue'
import { usePage, Link } from '@inertiajs/vue3'

const props = defineProps({
    notification: Object,
})
</script>

<template>
    <Base title="Detail Notifikasi">
        <div class="min-h-screen flex items-center justify-center px-6 py-8">
            <div class="w-full max-w-4xl bg-white dark:bg-gray-800 rounded-2xl shadow p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ notification.title }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ notification.notification_type === 'mandatory_saving' ? 'Simpanan Wajib' : 'Angsuran Pembiayaan' }}</p>
                    </div>
                    <Link href="/user/notifications" class="text-primary hover:underline">Kembali</Link>
                </div>

                <div class="space-y-4 text-sm text-gray-700 dark:text-gray-300">
                    <div>
                        <h2 class="font-semibold">Pesan</h2>
                        <p>{{ notification.message }}</p>
                    </div>
                    <div>
                        <span class="font-semibold">Reminder:</span> {{ notification.reminder_type }}
                    </div>
                    <div>
                        <span class="font-semibold">Status Pengiriman:</span> {{ notification.status }}
                    </div>
                    <div>
                        <span class="font-semibold">Status Dibaca:</span> {{ notification.is_read ? 'Sudah' : 'Belum' }}
                    </div>
                    <div>
                        <span class="font-semibold">Dikirim Pada:</span> {{ notification.sent_at }}
                    </div>
                    <div>
                        <span class="font-semibold">Dibaca Pada:</span> {{ notification.read_at }}
                    </div>
                </div>
            </div>
        </div>
    </Base>
</template>
