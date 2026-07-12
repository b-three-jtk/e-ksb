<script setup>
import { Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const props = defineProps({
    log_aktivitas: Array,
});

// Helper to determine icon and color based on event type
const getEventIcon = (event) => {
    switch (event) {
        case 'created':
            return { icon: 'mdi:plus-circle', color: 'text-green-500', bg: 'bg-green-100 dark:bg-green-900/30' };
        case 'updated':
            return { icon: 'mdi:pencil-circle', color: 'text-blue-500', bg: 'bg-blue-100 dark:bg-blue-900/30' };
        case 'deleted':
            return { icon: 'mdi:minus-circle', color: 'text-red-500', bg: 'bg-red-100 dark:bg-red-900/30' };
        case 'restored':
            return { icon: 'mdi:refresh-circle', color: 'text-yellow-500', bg: 'bg-yellow-100 dark:bg-yellow-900/30' };
        default:
            return { icon: 'mdi:information', color: 'text-gray-500', bg: 'bg-gray-100 dark:bg-gray-800' };
    }
};

const getEventLabel = (event) => {
    switch (event) {
        case 'created': return 'Membuat';
        case 'updated': return 'Memperbarui';
        case 'deleted': return 'Menghapus';
        case 'restored': return 'Memulihkan';
        default: return event;
    }
}
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="card-layout lg:col-span-1 flex flex-col gap-5">
            <div>
                <h1 class="card-title">Akses Cepat</h1>
                <p class="text-sm text-gray-400">Jalan pintas menu sistem</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <Link href="/admin/users"
                    class="flex flex-col items-center justify-center gap-2 p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 hover:bg-indigo-50 hover:border-indigo-200 dark:bg-gray-800/50 dark:hover:bg-indigo-900/20 dark:hover:border-indigo-800 transition-colors">
                    <Icon icon="mdi:account-group" class="w-8 h-8 text-indigo-500" />
                    <span class="text-xs font-semibold text-center text-gray-700 dark:text-gray-300">Pengguna</span>
                </Link>

                <Link href="/admin/roles"
                    class="flex flex-col items-center justify-center gap-2 p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 hover:bg-purple-50 hover:border-purple-200 dark:bg-gray-800/50 dark:hover:bg-purple-900/20 dark:hover:border-purple-800 transition-colors">
                    <Icon icon="mdi:shield-account" class="w-8 h-8 text-purple-500" />
                    <span class="text-xs font-semibold text-center text-gray-700 dark:text-gray-300">Peran & Hak</span>
                </Link>

                <Link href="/admin/settings"
                    class="flex flex-col items-center justify-center gap-2 p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 hover:bg-orange-50 hover:border-orange-200 dark:bg-gray-800/50 dark:hover:bg-orange-900/20 dark:hover:border-orange-800 transition-colors">
                    <Icon icon="mdi:cog" class="w-8 h-8 text-orange-500" />
                    <span class="text-xs font-semibold text-center text-gray-700 dark:text-gray-300">Pengaturan</span>
                </Link>

                <Link href="/admin/logs"
                    class="flex flex-col items-center justify-center gap-2 p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 hover:bg-teal-50 hover:border-teal-200 dark:bg-gray-800/50 dark:hover:bg-teal-900/20 dark:hover:border-teal-800 transition-colors">
                    <Icon icon="mdi:clipboard-text-clock" class="w-8 h-8 text-teal-500" />
                    <span class="text-xs font-semibold text-center text-gray-700 dark:text-gray-300">Semua Log</span>
                </Link>
            </div>
        </div>

        <div class="card-layout lg:col-span-2 flex flex-col gap-5">
            <div class="flex items-center gap-3">
                <div>
                    <h1 class="card-title">Log Aktivitas Terbaru</h1>
                    <p class="text-sm text-gray-400">Jejak aktivitas yang terekam di sistem</p>
                </div>
            </div>

            <div v-if="!log_aktivitas" class="flex flex-col gap-3 mt-2">
                <div v-for="i in 5" :key="i" class="h-16 bg-gray-100 dark:bg-gray-800 rounded-xl animate-pulse"></div>
            </div>

            <div v-else-if="log_aktivitas.length === 0"
                class="flex flex-col items-center justify-center py-10 text-center">
                <Icon icon="mdi:text-box-search-outline" class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-3" />
                <p class="text-sm text-gray-400">Belum ada log aktivitas yang terekam.</p>
            </div>

            <div v-else class="flex flex-col gap-3 max-h-144 overflow-y-auto pr-2 custom-scrollbar">
                <div v-for="log in log_aktivitas" :key="log.id"
                    class="flex items-start gap-4 p-4 rounded-xl border border-gray-100 hover:border-gray-300 dark:border-gray-800 dark:hover:border-gray-700 bg-white dark:bg-gray-800/50 transition-colors">
                    <div
                        :class="`w-10 h-10 rounded-full flex items-center justify-center shrink-0 ${getEventIcon(log.event).bg}`">
                        <Icon :icon="getEventIcon(log.event).icon"
                            :class="`w-6 h-6 ${getEventIcon(log.event).color}`" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ log.user }} <span class="font-normal text-gray-500">telah {{
                                getEventLabel(log.event).toLowerCase() }} data</span> {{ log.tipe }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1" :title="log.waktu_lengkap">
                            {{ log.waktu }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>
