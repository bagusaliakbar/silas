<script setup>
import { ref } from 'vue';

const props = defineProps({
    compact: {
        type: Boolean,
        default: false,
    },
});

const sceneRef = ref(null);
const rotateX = ref(12);
const rotateY = ref(-16);
const isHovered = ref(false);
const isSendingBurst = ref(false);

const handleMouseMove = (e) => {
    if (!sceneRef.value) return;
    const rect = sceneRef.value.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    const centerX = rect.width / 2;
    const centerY = rect.height / 2;

    // Smooth 3D tilt based on cursor position
    rotateY.value = ((x - centerX) / centerX) * 22;
    rotateX.value = -((y - centerY) / centerY) * 18;
};

const handleMouseLeave = () => {
    isHovered.value = false;
    rotateX.value = 12;
    rotateY.value = -16;
};

const triggerBurst = () => {
    if (isSendingBurst.value) return;
    isSendingBurst.value = true;
    setTimeout(() => {
        isSendingBurst.value = false;
    }, 1800);
};
</script>

<template>
    <div
        ref="sceneRef"
        @mousemove="handleMouseMove"
        @mouseenter="isHovered = true"
        @mouseleave="handleMouseLeave"
        @click="triggerBurst"
        class="mail-3d-wrapper select-none cursor-pointer relative flex items-center justify-center"
        :class="compact ? 'w-44 h-36 sm:w-52 sm:h-40' : 'w-56 h-44 sm:w-64 sm:h-48'"
        title="Klik untuk simulasi pengiriman surat TTE"
    >
        <!-- Ambient Hologram Base Pedestal -->
        <div class="pedestal-glow absolute bottom-2 w-36 h-8 rounded-full bg-cyan-400/20 blur-xl pointer-events-none"></div>
        <div class="pedestal-ring absolute bottom-3 w-32 h-7 rounded-full border border-cyan-400/40 pointer-events-none"></div>
        <div class="pedestal-ring-inner absolute bottom-4 w-24 h-5 rounded-full border border-dashed border-blue-300/40 pointer-events-none"></div>

        <!-- 3D Perspective Stage -->
        <div
            class="scene-3d relative w-full h-full flex items-center justify-center"
            :style="{
                transform: `perspective(900px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`,
            }"
        >
            <!-- Orbiting Digital Trajectory Ring -->
            <div class="orbit-ring absolute w-40 h-40 rounded-full border border-cyan-400/25 pointer-events-none">
                <span class="orbit-dot absolute -top-1 left-1/2 -translate-x-1/2 w-2 h-2 rounded-full bg-cyan-300 shadow-[0_0_10px_#22d3ee]"></span>
                <span class="orbit-dot-2 absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-indigo-400 shadow-[0_0_8px_#818cf8]"></span>
            </div>

            <!-- Floating 3D Assembly -->
            <div class="floating-rig relative w-32 h-24" :class="{ 'burst-send': isSendingBurst }">
                <!-- Back Shadow Plate -->
                <div class="envelope-shadow absolute inset-0 rounded-xl bg-blue-950/80 blur-md"></div>

                <!-- 3D Envelope Back Layer -->
                <div class="envelope-back absolute inset-0 rounded-xl bg-gradient-to-br from-blue-600 via-indigo-700 to-slate-900 border border-blue-400/40 shadow-lg"></div>

                <!-- Official Letter Sheet Rising Out of Envelope (3D Z-Translated) -->
                <div class="letter-sheet absolute left-2.5 right-2.5 -top-7 h-24 rounded-lg bg-gradient-to-b from-white via-slate-50 to-blue-50 p-2.5 shadow-xl border border-blue-200/80 flex flex-col justify-between">
                    <!-- Mini Header Kop Fasilkom -->
                    <div class="flex items-center justify-between border-b border-slate-200 pb-1">
                        <div class="flex items-center gap-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400 inline-block shadow-2xs"></span>
                            <div class="space-y-0.5">
                                <div class="w-10 h-1 rounded-full bg-slate-700"></div>
                                <div class="w-7 h-0.5 rounded-full bg-slate-400"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Mini Document Skeleton Lines -->
                    <div class="space-y-1 my-1">
                        <div class="w-full h-1 rounded-full bg-slate-300"></div>
                        <div class="w-5/6 h-1 rounded-full bg-slate-300"></div>
                        <div class="w-4/6 h-1 rounded-full bg-slate-200"></div>
                    </div>

                    <!-- Mini QR Code & Crypto Signature Stamp -->
                    <div class="flex items-center justify-between pt-0.5">
                        <div class="flex items-center gap-1">
                            <div class="w-3.5 h-3.5 rounded-xs bg-slate-800 p-0.5 grid grid-cols-2 gap-0.5">
                                <span class="bg-white rounded-[0.5px]"></span>
                                <span class="bg-cyan-400 rounded-[0.5px]"></span>
                                <span class="bg-cyan-400 rounded-[0.5px]"></span>
                                <span class="bg-white rounded-[0.5px]"></span>
                            </div>
                            <div class="w-6 h-0.5 rounded-full bg-emerald-500"></div>
                        </div>
                        <div class="w-4 h-4 rounded-full bg-gradient-to-tr from-amber-500 to-yellow-300 flex items-center justify-center shadow-xs">
                            <svg class="w-2.5 h-2.5 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>

                    <!-- Laser Scanning Bar -->
                    <div class="laser-scan absolute left-0 right-0 h-0.5 bg-gradient-to-r from-transparent via-cyan-400 to-transparent shadow-[0_0_8px_#22d3ee]"></div>
                </div>

                <!-- 3D Envelope Front Pocket (SVG Fold) -->
                <div class="envelope-front absolute inset-0 pointer-events-none overflow-hidden rounded-xl">
                    <svg viewBox="0 0 128 96" class="w-full h-full drop-shadow-md">
                        <defs>
                            <linearGradient id="envLeft" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#2563eb" />
                                <stop offset="100%" stop-color="#1d4ed8" />
                            </linearGradient>
                            <linearGradient id="envRight" x1="100%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#3b82f6" />
                                <stop offset="100%" stop-color="#1e40af" />
                            </linearGradient>
                            <linearGradient id="envBottom" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#1d4ed8" />
                                <stop offset="100%" stop-color="#172554" />
                            </linearGradient>
                        </defs>
                        <!-- Left Flap -->
                        <polygon points="0,18 64,56 0,96" fill="url(#envLeft)" opacity="0.95" />
                        <!-- Right Flap -->
                        <polygon points="128,18 64,56 128,96" fill="url(#envRight)" opacity="0.95" />
                        <!-- Bottom Pocket -->
                        <polygon points="0,96 64,50 128,96" fill="url(#envBottom)" />
                        <!-- Subtle Highlight Trim -->
                        <polyline points="0,96 64,50 128,96" fill="none" stroke="rgba(147, 197, 253, 0.45)" stroke-width="1.2" />
                    </svg>
                </div>

                <!-- 3DGlowing E-Sign Seal Badge on Front of Envelope -->
                <div class="seal-badge absolute left-1/2 top-[56%] -translate-x-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-gradient-to-br from-cyan-400 via-blue-500 to-indigo-600 p-0.5 shadow-[0_0_15px_rgba(34,211,238,0.6)] flex items-center justify-center border border-white/60">
                    <div class="w-full h-full rounded-full bg-slate-950/90 flex items-center justify-center">
                        <svg class="w-4 h-4 text-cyan-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </div>
                </div>

                <!-- 3D Paper Airplane Launching & Orbiting -->
                <div class="paper-plane-orbit absolute inset-0 pointer-events-none">
                    <div class="paper-plane absolute -right-7 -top-8 w-8 h-8 text-cyan-300 drop-shadow-[0_0_10px_rgba(34,211,238,0.8)]">
                        <svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
                        </svg>
                        <!-- Jet Trail -->
                        <span class="plane-trail absolute right-6 top-1/2 -translate-y-1/2 w-8 h-0.5 bg-gradient-to-l from-cyan-300 to-transparent rounded-full"></span>
                    </div>
                </div>

                <!-- Floating Sparkle Data Packets -->
                <span class="packet-node packet-1 absolute -left-5 -top-3 w-2 h-2 rounded-full bg-emerald-400 shadow-[0_0_8px_#34d399]"></span>
                <span class="packet-node packet-2 absolute -right-4 bottom-2 w-1.5 h-1.5 rounded-full bg-amber-300 shadow-[0_0_8px_#fcd34d]"></span>
                <span class="packet-node packet-3 absolute left-3 -bottom-4 w-1.5 h-1.5 rounded-full bg-cyan-300 shadow-[0_0_8px_#67e8f9]"></span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.scene-3d {
    transform-style: preserve-3d;
    transition: transform 0.25s cubic-bezier(0.22, 1, 0.36, 1);
}

.floating-rig {
    transform-style: preserve-3d;
    animation: floatRig 4.2s ease-in-out infinite;
}

.envelope-shadow {
    transform: translateZ(-18px) translateY(14px) scale(0.92);
}

.envelope-back {
    transform: translateZ(0px);
}

.letter-sheet {
    transform: translateZ(14px) translateY(0px) rotateX(-4deg);
    animation: dispatchLetter 4.2s ease-in-out infinite;
}

.envelope-front {
    transform: translateZ(24px);
}

.seal-badge {
    transform: translateZ(34px);
    animation: pulseSeal 2.8s ease-in-out infinite;
}

.paper-plane-orbit {
    transform: translateZ(42px);
}

.paper-plane {
    animation: flyPlane 4.2s ease-in-out infinite;
}

.orbit-ring {
    transform: rotateX(72deg) translateZ(-22px);
    animation: spinOrbit 10s linear infinite;
}

.pedestal-ring {
    transform: rotateX(70deg);
    animation: pulsePedestal 3s ease-in-out infinite;
}

.pedestal-ring-inner {
    transform: rotateX(70deg);
    animation: spinReverse 12s linear infinite;
}

.laser-scan {
    animation: scanDoc 2.6s ease-in-out infinite;
}

.packet-1 {
    transform: translateZ(30px);
    animation: floatPacket 3s ease-in-out infinite;
}
.packet-2 {
    transform: translateZ(28px);
    animation: floatPacket 3.6s ease-in-out infinite 0.7s;
}
.packet-3 {
    transform: translateZ(20px);
    animation: floatPacket 2.8s ease-in-out infinite 1.3s;
}

/* Click Burst Animation */
.burst-send {
    animation: burstLaunch 1.6s cubic-bezier(0.22, 1, 0.36, 1) forwards !important;
}

@keyframes floatRig {
    0%, 100% {
        transform: translateY(0px) rotateZ(0deg);
    }
    50% {
        transform: translateY(-8px) rotateZ(1.5deg);
    }
}

@keyframes dispatchLetter {
    0%, 100% {
        transform: translateZ(14px) translateY(4px) scale(0.96);
    }
    45%, 65% {
        transform: translateZ(18px) translateY(-10px) scale(1);
    }
}

@keyframes flyPlane {
    0% {
        transform: translate(-18px, 14px) scale(0.7) rotate(-25deg);
        opacity: 0;
    }
    25% {
        opacity: 1;
    }
    65% {
        transform: translate(12px, -14px) scale(1.08) rotate(-18deg);
        opacity: 1;
    }
    100% {
        transform: translate(28px, -26px) scale(0.6) rotate(-10deg);
        opacity: 0;
    }
}

@keyframes scanDoc {
    0%, 100% {
        top: 12%;
        opacity: 0.2;
    }
    50% {
        top: 86%;
        opacity: 0.95;
    }
}

@keyframes pulseSeal {
    0%, 100% {
        transform: translate(-50%, -50%) translateZ(34px) scale(1);
    }
    50% {
        transform: translate(-50%, -50%) translateZ(40px) scale(1.1);
    }
}

@keyframes spinOrbit {
    0% {
        transform: rotateX(72deg) translateZ(-22px) rotateZ(0deg);
    }
    100% {
        transform: rotateX(72deg) translateZ(-22px) rotateZ(360deg);
    }
}

@keyframes spinReverse {
    0% {
        transform: rotateX(70deg) rotateZ(360deg);
    }
    100% {
        transform: rotateX(70deg) rotateZ(0deg);
    }
}

@keyframes pulsePedestal {
    0%, 100% {
        transform: rotateX(70deg) scale(0.95);
        opacity: 0.45;
    }
    50% {
        transform: rotateX(70deg) scale(1.06);
        opacity: 0.85;
    }
}

@keyframes floatPacket {
    0%, 100% {
        transform: translateZ(30px) translateY(0px) scale(0.9);
        opacity: 0.5;
    }
    50% {
        transform: translateZ(38px) translateY(-9px) scale(1.25);
        opacity: 1;
    }
}

@keyframes burstLaunch {
    0% {
        transform: scale(1) translateZ(0px) rotateY(0deg);
    }
    30% {
        transform: scale(0.9) translateZ(-20px) rotateX(12deg);
    }
    65% {
        transform: scale(1.12) translateZ(45px) translateY(-14px) rotateY(360deg);
    }
    100% {
        transform: scale(1) translateZ(0px) rotateY(360deg);
    }
}
</style>

