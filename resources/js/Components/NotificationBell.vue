<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const isOpen = ref(false);
const dropdownRef = ref(null);

// Audio sound preference stored in localStorage
const isSoundEnabled = ref(localStorage.getItem('notification_sound_enabled') !== 'false');

// Local notifications state for real-time reactivity
const localNotifications = ref([...(page.props.notifications?.list || [])]);
const localUnreadCount = ref(page.props.notifications?.unread_count || 0);

// Watch props when Inertia navigates
watch(
    () => page.props.notifications,
    (newVal) => {
        if (newVal) {
            localNotifications.value = newVal.list || [];
            localUnreadCount.value = newVal.unread_count || 0;
        }
    },
    { deep: true }
);

const notifications = computed(() => localNotifications.value);
const unreadCount = computed(() => localUnreadCount.value);

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

const closeDropdown = () => {
    isOpen.value = false;
};

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        closeDropdown();
    }
};

const handleKeyDown = (event) => {
    if (event.key === 'Escape' && isOpen.value) {
        closeDropdown();
    }
};

const toggleSound = () => {
    isSoundEnabled.value = !isSoundEnabled.value;
    localStorage.setItem('notification_sound_enabled', isSoundEnabled.value ? 'true' : 'false');
    if (isSoundEnabled.value) {
        playNotificationSound();
    }
};

// Play a pleasant chime using Web Audio API (zero external asset dependency)
const playNotificationSound = () => {
    if (!isSoundEnabled.value) return;

    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;

        const ctx = new AudioCtx();
        if (ctx.state === 'suspended') {
            ctx.resume();
        }

        const now = ctx.currentTime;

        // Tone 1: High crisp chime (F6 ~ 1396.91 Hz)
        const osc1 = ctx.createOscillator();
        const gain1 = ctx.createGain();
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(1396.91, now);
        gain1.gain.setValueAtTime(0.15, now);
        gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.35);

        osc1.connect(gain1);
        gain1.connect(ctx.destination);
        osc1.start(now);
        osc1.stop(now + 0.35);

        // Tone 2: Harmonic resolving chime (A6 ~ 1760 Hz)
        const osc2 = ctx.createOscillator();
        const gain2 = ctx.createGain();
        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(1760, now + 0.12);
        gain2.gain.setValueAtTime(0.2, now + 0.12);
        gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.55);

        osc2.connect(gain2);
        gain2.connect(ctx.destination);
        osc2.start(now + 0.12);
        osc2.stop(now + 0.55);
    } catch (e) {
        // Audio playback failed or blocked by autoplay policy until user gesture
        console.debug('Notification sound playback error:', e);
    }
};

// Polling interval reference
let pollTimer = null;
let isPolling = false;

const pollNotifications = async () => {
    if (isPolling) return;
    isPolling = true;

    try {
        const response = await axios.get(route('notifications.index'), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.data) {
            const newUnread = response.data.unread_count ?? 0;
            const newNotifications = response.data.notifications ?? [];

            // If there's an increase in unread notifications, play audio
            if (newUnread > localUnreadCount.value) {
                playNotificationSound();
            }

            localUnreadCount.value = newUnread;
            localNotifications.value = newNotifications;
        }
    } catch (error) {
        // Silently catch polling errors (e.g., session timeout or network blip)
    } finally {
        isPolling = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);

    // Initial check and background polling every 8 seconds
    pollTimer = setInterval(pollNotifications, 8000);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);

    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
});

const handleNotificationClick = (notification) => {
    if (!notification.read_at) {
        router.post(
            route('notifications.read', notification.id),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    // Update locally
                    notification.read_at = new Date().toISOString();
                    if (localUnreadCount.value > 0) {
                        localUnreadCount.value -= 1;
                    }
                    if (notification.url) {
                        router.visit(notification.url);
                    }
                },
            }
        );
    } else if (notification.url) {
        router.visit(notification.url);
    }
    closeDropdown();
};

const markAllAsRead = () => {
    router.post(
        route('notifications.read-all'),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                localUnreadCount.value = 0;
                localNotifications.value.forEach((n) => {
                    n.read_at = new Date().toISOString();
                });
            },
        }
    );
};
</script>

<template>
    <div ref="dropdownRef" class="relative inline-block text-left">
        <!-- Bell Trigger Button -->
        <button
            type="button"
            @click="toggleDropdown"
            :title="unreadCount > 0 ? `${unreadCount} notifikasi baru` : 'Notifikasi'"
            aria-label="Buka notifikasi"
            :class="[
                'h-11 w-11 rounded-2xl bg-slate-100/80 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/80 text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-slate-200/60 dark:hover:bg-slate-700/60 shadow-2xs relative inline-flex items-center justify-center transition-all duration-200 select-none group',
                isOpen ? 'ring-2 ring-blue-600/30 dark:ring-blue-400/30 text-blue-600 dark:text-blue-400' : ''
            ]"
        >
            <svg
                class="w-5 h-5 transition-transform duration-200 group-hover:scale-110"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                />
            </svg>

            <!-- Unread Badge -->
            <span
                v-if="unreadCount > 0"
                class="absolute -top-1 -right-1 flex h-5 min-w-[20px] items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] font-bold text-white shadow-sm ring-2 ring-white dark:ring-slate-900 animate-in fade-in zoom-in duration-200"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown Menu -->
        <transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div
                v-if="isOpen"
                class="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-2xl overflow-hidden z-50 text-slate-800 dark:text-slate-100"
            >
                <!-- Header -->
                <div class="px-4 py-3 bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-sm text-slate-900 dark:text-white">Notifikasi</span>
                        <span
                            v-if="unreadCount > 0"
                            class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300"
                        >
                            {{ unreadCount }} Baru
                        </span>
                    </div>

                    <div class="flex items-center gap-3">
                        <!-- Sound On/Off Toggle Button -->
                        <button
                            type="button"
                            @click="toggleSound"
                            :title="isSoundEnabled ? 'Suara notifikasi: Aktif (klik untuk nonaktifkan)' : 'Suara notifikasi: Nonaktif (klik untuk aktifkan)'"
                            :class="[
                                'p-1.5 rounded-lg text-xs font-medium transition-colors flex items-center gap-1',
                                isSoundEnabled
                                    ? 'text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-950/40'
                                    : 'text-slate-400 hover:text-slate-600 hover:bg-slate-200/60 dark:hover:bg-slate-800 dark:text-slate-500'
                            ]"
                        >
                            <!-- Speaker Active Icon -->
                            <svg v-if="isSoundEnabled" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                            </svg>
                            <!-- Speaker Muted Icon -->
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                            </svg>
                            <span class="text-[11px] font-semibold">{{ isSoundEnabled ? 'Suara On' : 'Mute' }}</span>
                        </button>

                        <button
                            v-if="unreadCount > 0"
                            @click="markAllAsRead"
                            type="button"
                            class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 inline-flex items-center gap-1 transition"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Tandai semua dibaca
                        </button>
                    </div>
                </div>

                <!-- Notifications List -->
                <div class="max-h-96 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/80">
                    <div
                        v-for="item in notifications"
                        :key="item.id"
                        @click="handleNotificationClick(item)"
                        :class="[
                            'p-3.5 flex items-start gap-3 transition-colors cursor-pointer text-left',
                            !item.read_at
                                ? 'bg-blue-50/40 dark:bg-blue-950/20 hover:bg-blue-50/70 dark:hover:bg-blue-950/40'
                                : 'hover:bg-slate-50 dark:hover:bg-slate-800/60'
                        ]"
                    >
                        <!-- Category Icon Badge -->
                        <div
                            :class="[
                                'w-9 h-9 rounded-xl shrink-0 flex items-center justify-center',
                                item.category === 'letter_signed' || item.category === 'document_signed'
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/80 dark:text-emerald-300'
                                    : item.category === 'letter_rejected'
                                    ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/80 dark:text-rose-300'
                                    : item.category === 'letter_submitted'
                                    ? 'bg-sky-100 text-sky-700 dark:bg-sky-950/80 dark:text-sky-300'
                                    : item.category === 'certificate_created'
                                    ? 'bg-purple-100 text-purple-700 dark:bg-purple-950/80 dark:text-purple-300'
                                    : 'bg-blue-100 text-blue-700 dark:bg-blue-950/80 dark:text-blue-300'
                            ]"
                        >
                            <!-- Approved / Signed Icon -->
                            <svg
                                v-if="item.category === 'letter_signed' || item.category === 'document_signed'"
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>

                            <!-- Rejected Icon -->
                            <svg
                                v-else-if="item.category === 'letter_rejected'"
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>

                            <!-- Letter Submitted Icon -->
                            <svg
                                v-else-if="item.category === 'letter_submitted'"
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>

                            <!-- Certificate Icon -->
                            <svg
                                v-else-if="item.category === 'certificate_created'"
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>

                            <!-- Default / Info Icon -->
                            <svg
                                v-else
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-1 mb-0.5">
                                <h4 class="text-xs font-bold text-slate-900 dark:text-slate-100 truncate">
                                    {{ item.title }}
                                </h4>
                                <span v-if="!item.read_at" class="w-2 h-2 rounded-full bg-blue-600 dark:bg-blue-400 shrink-0"></span>
                            </div>
                            <p class="text-[11px] text-slate-600 dark:text-slate-300 line-clamp-2 leading-relaxed">
                                {{ item.message }}
                            </p>
                            <span class="text-[10px] font-medium text-slate-400 dark:text-slate-500 mt-1 block">
                                {{ item.time_ago }}
                            </span>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="notifications.length === 0" class="py-10 px-4 text-center">
                        <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Belum Ada Notifikasi</p>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Semua aktivitas pengajuan surat dan tanda tangan elektronik akan muncul di sini.</p>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

