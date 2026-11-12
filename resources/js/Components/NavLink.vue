<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'inline-flex items-center gap-2 px-3.5 py-1.5 h-9 rounded-xl text-xs sm:text-sm font-semibold transition-all duration-200 bg-blue-600 text-white shadow-xs shadow-blue-900/20'
        : 'inline-flex items-center gap-2 px-3.5 py-1.5 h-9 rounded-xl text-xs sm:text-sm font-medium transition-all duration-200 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/80 dark:hover:bg-slate-700/60',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
