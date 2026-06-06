<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { usePage, Link, router } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

const dropdownOpen = ref(false)
const dropdownRef = ref(null)

const page = usePage()

const notifications = computed(
    () => page.props.notification_dropdown || []
)

const count = computed(
    () => page.props.unread_notification_count ?? 0
)

const isMember = computed(
    () => page.props.auth?.role === 'Anggota'
)

const popupNotifications = computed(
    () => page.props.pending_notification_popups || []
)

const notifying = computed(() => count.value > 0)

const toggleDropdown = () => {
    dropdownOpen.value = !dropdownOpen.value
}

const closeDropdown = () => {
    dropdownOpen.value = false
}

const handleClickOutside = (event) => {
    if (
        dropdownRef.value &&
        !dropdownRef.value.contains(event.target)
    ) {
        closeDropdown()
    }
}

const markPopupDisplayed = () => {
    if (!popupNotifications.value.length) {
        return
    }

    const notificationIds = popupNotifications.value.map(
        item => item.id
    )

    router.post(
        '/user/notifications/mark-popup-displayed',
        {
            notification_ids: notificationIds,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    )
}

onMounted(async () => {
    document.addEventListener('click', handleClickOutside)

    if (popupNotifications.value.length > 0) {
        for (const notification of popupNotifications.value) {
            await Swal.fire({
                title: notification.title,
                text: notification.message,
                icon: 'info',
                confirmButtonText: 'Tutup',
            })
        }

        markPopupDisplayed()
    }
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
    <div
        v-if="isMember"
        class="relative"
        ref="dropdownRef"
    >
        <!-- Bell Button -->
        <button
            class="relative flex items-center justify-center text-dark-text transition-colors bg-transparent border border-gray-200 rounded-full hover:text-dark-900 h-11 w-11 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
            @click="toggleDropdown"
        >
            <!-- Ping Indicator -->
            <span
                v-if="notifying"
                class="absolute right-0 top-0.5 z-10 h-2 w-2 rounded-full bg-orange-400"
            >
                <span
                    class="absolute inline-flex w-full h-full rounded-full bg-orange-400 opacity-75 animate-ping"
                />
            </span>

            <!-- Bell Icon -->
            <span
                class="icon-[heroicons-outline--bell]"
                style="width: 20px; height: 20px;"
            />

            <!-- Count Badge -->
            <span
                v-if="count > 0"
                class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs text-white"
            >
                {{ count }}
            </span>
        </button>

        <!-- Dropdown -->
        <div
            v-if="dropdownOpen"
            class="absolute -right-60 mt-4 flex h-[480px] w-[360px] flex-col rounded-2xl border border-gray-200 bg-white p-3 shadow-lg dark:border-gray-800 dark:bg-gray-900 sm:w-[380px] lg:right-0 z-50"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between pb-3 mb-3 border-b border-gray-100 dark:border-gray-800"
            >
                <h5
                    class="text-lg font-semibold text-gray-800 dark:text-white"
                >
                    Notifikasi
                </h5>

                <button
                    @click="closeDropdown"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400"
                >
                    <svg
                        class="fill-current"
                        width="20"
                        height="20"
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M6.22 7.28a.75.75 0 011.06 0L12 12l4.72-4.72a.75.75 0 111.06 1.06L13.06 13.06l4.72 4.72a.75.75 0 11-1.06 1.06L12 14.12l-4.72 4.72a.75.75 0 11-1.06-1.06l4.72-4.72-4.72-4.72a.75.75 0 010-1.06z"
                        />
                    </svg>
                </button>
            </div>

            <!-- Notifications -->
            <ul
                class="flex flex-col flex-1 overflow-y-auto custom-scrollbar"
            >
                <li
                    v-if="notifications.length === 0"
                    class="flex items-center justify-center flex-1 text-sm text-gray-500 dark:text-gray-400"
                >
                    Tidak ada notifikasi terbaru.
                </li>

                <li
                    v-for="notification in notifications"
                    :key="notification.id"
                >
                    <Link
                        :href="notification.href"
                        @click="closeDropdown"
                        class="flex gap-3 rounded-lg border-b border-gray-100 p-4 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800"
                    >
                        <!-- Icon -->
                        <div
                            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-900/20 dark:text-green-400"
                        >
                            <span
                                class="icon-[heroicons-outline--bell]"
                                style="width:20px;height:20px"
                            />
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div
                                class="flex items-start justify-between gap-2"
                            >
                                <p
                                    class="font-medium text-sm text-gray-800 dark:text-white"
                                >
                                    {{ notification.title }}
                                </p>

                                <span
                                    v-if="!notification.is_read"
                                    class="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full bg-red-500"
                                />
                            </div>

                            <p
                                class="mt-1 text-sm text-gray-500 dark:text-gray-400 line-clamp-2"
                            >
                                {{ notification.message }}
                            </p>

                            <p
                                class="mt-2 text-xs text-gray-400"
                            >
                                {{ notification.scheduled_at }}
                            </p>
                        </div>
                    </Link>
                </li>
            </ul>

            <!-- Footer -->
            <Link
                href="/user/notifications"
                @click="closeDropdown"
                class="mt-3 flex justify-center rounded-lg border border-gray-300 bg-white p-3 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
            >
                Lihat Semua Notifikasi
            </Link>
        </div>
    </div>
</template>