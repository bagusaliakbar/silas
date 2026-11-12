<script setup>
import { onMounted, ref } from 'vue';

defineProps({
    showLabel: {
        type: Boolean,
        default: false,
    },
});

const isDark = ref(false);

const updateThemeState = () => {
    isDark.value = document.documentElement.classList.contains('dark');
};

const toggleTheme = () => {
    if (document.documentElement.classList.contains('dark')) {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
        isDark.value = false;
    } else {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
        isDark.value = true;
    }
};

onMounted(() => {
    updateThemeState();
});
</script>

<template>
    <button
        type="button"
        @click="toggleTheme"
        :title="isDark ? 'Beralih ke Mode Terang (Light Mode)' : 'Beralih ke Mode Gelap (Dark Mode)'"
        :aria-label="isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'"
        :class="[
            'inline-flex items-center justify-center transition-all duration-200 select-none group',
            showLabel
                ? 'w-full gap-2.5 px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700'
                : 'h-11 w-11 rounded-2xl bg-slate-100/80 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/80 text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-slate-200/60 dark:hover:bg-slate-700/60 shadow-2xs'
        ]"
    >
        <!-- Sun icon (when currently dark mode -> click for light) -->
        <svg
            v-if="isDark"
            class="w-5 h-5 text-amber-400 transition-transform duration-300 group-hover:rotate-45"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
            />
        </svg>

        <!-- Moon icon (when currently light mode -> click for dark) -->
        <svg
            v-else
            class="w-5 h-5 text-slate-600 dark:text-slate-300 transition-transform duration-300 group-hover:-rotate-12 group-hover:text-blue-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
            />
        </svg>

        <span v-if="showLabel" class="text-xs">
            {{ isDark ? 'Mode Terang (Light)' : 'Mode Gelap (Dark)' }}
        </span>
    </button>
</template>

