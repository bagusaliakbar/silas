<script setup>
import { ref } from 'vue';

const stageRef = ref(null);
const rotateX = ref(8);
const rotateY = ref(-12);
const isVerifiedPulse = ref(false);

const handleMouseMove = (e) => {
    if (!stageRef.value) return;
    const rect = stageRef.value.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    const centerX = rect.width / 2;
    const centerY = rect.height / 2;

    rotateY.value = ((x - centerX) / centerX) * 20;
    rotateX.value = -((y - centerY) / centerY) * 16;
};

const handleMouseLeave = () => {
    rotateX.value = 8;
    rotateY.value = -12;
};

const triggerVerifyPulse = () => {
    if (isVerifiedPulse.value) return;
    isVerifiedPulse.value = true;
    setTimeout(() => {
        isVerifiedPulse.value = false;
    }, 1800);
};
</script>

<template>
    <div
        ref="stageRef"
        @mousemove="handleMouseMove"
        @mouseleave="handleMouseLeave"
        @click="triggerVerifyPulse"
        class="vault-3d-container relative w-full h-56 sm:h-64 rounded-2xl bg-gradient-to-br from-slate-950 via-blue-950 to-indigo-950 overflow-hidden border border-blue-800/60 shadow-inner flex items-center justify-center select-none cursor-pointer group"
        title="Klik untuk simulasi verifikasi segel kriptografi X.509"
    >
        <!-- Background Cyber Grid Perspective -->
        <div class="cyber-grid absolute inset-0 opacity-25 pointer-events-none"></div>

        <!-- Ambient Glow Orbs -->
        <div class="absolute -top-12 -left-12 w-44 h-44 rounded-full bg-blue-500/20 blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-12 -right-12 w-44 h-44 rounded-full bg-emerald-500/20 blur-3xl pointer-events-none"></div>

        <!-- Top Left Live Telemetry Badge -->
        <div class="absolute top-3 left-3.5 z-20 flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-900/80 border border-cyan-500/30 backdrop-blur-md">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
            <span class="text-[9px] font-mono font-bold tracking-wider uppercase text-cyan-300">
                {{ isVerifiedPulse ? 'VERIFIKASI HASH VALID ✓' : 'PKI X.509 • RSA-2048 ACTIVE' }}
            </span>
        </div>

        <!-- Top Right Interactive Hint -->
        <div class="absolute top-3 right-3.5 z-20 hidden sm:flex items-center gap-1 px-2 py-0.5 rounded-md bg-white/5 border border-white/10 text-[9px] text-slate-300 font-mono">
            <span>3D INTERAKTIF</span>
        </div>

        <!-- 3D Perspective Stage -->
        <div
            class="vault-scene relative w-full h-full flex items-center justify-center"
            :style="{
                transform: `perspective(950px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`,
            }"
        >
            <!-- Outer Gyroscopic Security Ring 1 -->
            <div class="gyro-ring-1 absolute w-48 h-48 rounded-full border border-cyan-400/25 pointer-events-none">
                <span class="absolute -top-1 left-1/2 -translate-x-1/2 w-2 h-2 rounded-full bg-cyan-400 shadow-[0_0_10px_#22d3ee]"></span>
                <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-blue-400"></span>
            </div>

            <!-- Inner Gyroscopic Dashed Ring 2 -->
            <div class="gyro-ring-2 absolute w-40 h-40 rounded-full border border-dashed border-emerald-400/30 pointer-events-none"></div>

            <!-- Pedestal Base Hologram -->
            <div class="pedestal-base absolute bottom-4 w-36 h-7 rounded-full bg-cyan-400/15 blur-lg pointer-events-none"></div>
            <div class="pedestal-disc absolute bottom-5 w-32 h-6 rounded-full border border-cyan-400/40 pointer-events-none"></div>

            <!-- Floating 3D Rig -->
            <div class="vault-rig relative w-32 h-40 flex items-center justify-center" :class="{ 'pulse-verify': isVerifiedPulse }">
                <!-- Back Layer: Encrypted Shadow Card -->
                <div class="doc-back-card absolute w-28 h-36 rounded-xl bg-indigo-900/70 border border-indigo-400/30 backdrop-blur-xs"></div>

                <!-- Middle Layer: Main 3D Academic Document Sheet -->
                <div class="doc-main-card relative w-28 h-36 rounded-xl bg-gradient-to-b from-white via-slate-50 to-blue-50 p-3 shadow-2xl border border-blue-200 flex flex-col justify-between overflow-hidden">
                    <!-- University Header Bar -->
                    <div class="flex items-center justify-between border-b border-slate-200 pb-1.5">
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-full bg-gradient-to-tr from-amber-500 to-yellow-300 shadow-2xs flex items-center justify-center">
                                <span class="w-1 h-1 rounded-full bg-slate-900"></span>
                            </span>
                            <div class="space-y-0.5">
                                <div class="w-11 h-1 rounded-full bg-slate-800"></div>
                                <div class="w-7 h-0.5 rounded-full bg-slate-400"></div>
                            </div>
                        </div>
                        <span class="px-1 py-0.5 rounded text-[5px] font-black bg-blue-100 text-blue-800">
                            UNSUB
                        </span>
                    </div>

                    <!-- Document Body Lines -->
                    <div class="space-y-1.5 my-1">
                        <div class="w-3/4 h-1 rounded-full bg-slate-700 mx-auto"></div>
                        <div class="w-full h-1 rounded-full bg-slate-300"></div>
                        <div class="w-11/12 h-1 rounded-full bg-slate-300"></div>
                        <div class="w-4/5 h-1 rounded-full bg-slate-300"></div>
                        <div class="w-full h-1 rounded-full bg-slate-200"></div>
                    </div>

                    <!-- Bottom Signature & Mini QR Matrix -->
                    <div class="flex items-center justify-between pt-1 border-t border-slate-200/80">
                        <!-- Mini QR Code -->
                        <div class="w-5 h-5 rounded-xs bg-slate-900 p-0.5 grid grid-cols-3 gap-0.5">
                            <span class="bg-white"></span>
                            <span class="bg-cyan-400"></span>
                            <span class="bg-white"></span>
                            <span class="bg-cyan-400"></span>
                            <span class="bg-white"></span>
                            <span class="bg-emerald-400"></span>
                            <span class="bg-white"></span>
                            <span class="bg-emerald-400"></span>
                            <span class="bg-white"></span>
                        </div>
                        <div class="text-right space-y-0.5">
                            <div class="w-9 h-0.5 rounded-full bg-emerald-500 ml-auto"></div>
                            <div class="w-7 h-1 rounded-full bg-slate-700 ml-auto"></div>
                        </div>
                    </div>

                    <!-- Vertical Laser Scanner Beam -->
                    <div class="doc-laser absolute left-0 right-0 h-1 bg-gradient-to-r from-transparent via-emerald-400 to-transparent shadow-[0_0_12px_#10b981]"></div>
                </div>

                <!-- Front Layer (High Z-Index): 3D Floating Cryptographic Shield -->
                <div class="crypto-shield absolute -right-5 -bottom-2 w-12 h-14 flex items-center justify-center">
                    <svg viewBox="0 0 24 24" class="w-full h-full drop-shadow-[0_6px_16px_rgba(16,185,129,0.55)]">
                        <defs>
                            <linearGradient id="shieldGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#10b981" />
                                <stop offset="50%" stop-color="#059669" />
                                <stop offset="100%" stop-color="#047857" />
                            </linearGradient>
                        </defs>
                        <path
                            fill="url(#shieldGrad)"
                            stroke="#a7f3d0"
                            stroke-width="1"
                            d="M12 2L4 5v6.09c0 5.05 3.41 9.76 8 10.91 4.59-1.15 8-5.86 8-10.91V5l-8-3z"
                        />
                        <path
                            fill="none"
                            stroke="#ffffff"
                            stroke-width="2.2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 12l2 2 4-4"
                        />
                    </svg>
                </div>

                <!-- Floating Left Chip: SHA-256 Tag -->
                <div class="hash-chip absolute -left-8 top-4 px-2 py-1 rounded-lg bg-slate-900/95 border border-cyan-400/50 shadow-[0_0_15px_rgba(34,211,238,0.3)] flex items-center gap-1">
                    <svg class="w-3 h-3 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span class="text-[8px] font-mono font-bold text-white">SHA-256</span>
                </div>

                <!-- Floating Top Right Chip: QR Verified -->
                <div class="qr-chip absolute -right-7 top-2 px-2 py-0.5 rounded-md bg-emerald-950/90 border border-emerald-400/50 text-emerald-300 text-[8px] font-mono font-bold shadow-md">
                    PAdES VALID
                </div>
            </div>
        </div>

        <!-- Bottom Caption Bar -->
        <div class="absolute bottom-2.5 inset-x-4 flex items-center justify-between text-[10px] text-slate-300/90 font-mono pointer-events-none">
            <span>FAKULTAS ILMU KOMPUTER</span>
            <span class="text-cyan-300 font-semibold">SILAS-TTE v2.0</span>
        </div>
    </div>
</template>

<style scoped>
.cyber-grid {
    background-image:
        linear-gradient(to right, rgba(56, 189, 248, 0.14) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(56, 189, 248, 0.14) 1px, transparent 1px);
    background-size: 22px 22px;
    transform: perspective(400px) rotateX(55deg) scale(1.4);
    transform-origin: center bottom;
}

.vault-scene {
    transform-style: preserve-3d;
    transition: transform 0.22s cubic-bezier(0.22, 1, 0.36, 1);
}

.vault-rig {
    transform-style: preserve-3d;
    animation: floatVault 4.5s ease-in-out infinite;
}

.doc-back-card {
    transform: translateZ(-16px) translateX(-8px) translateY(6px) rotateZ(-6deg);
}

.doc-main-card {
    transform: translateZ(12px);
}

.crypto-shield {
    transform: translateZ(38px);
    animation: shieldFloat 3.2s ease-in-out infinite;
}

.hash-chip {
    transform: translateZ(32px);
    animation: chipFloat 3.6s ease-in-out infinite 0.4s;
}

.qr-chip {
    transform: translateZ(28px);
    animation: chipFloat 3.8s ease-in-out infinite 1s;
}

.gyro-ring-1 {
    transform: rotateX(68deg) rotateY(-12deg) translateZ(-20px);
    animation: spinGyro1 11s linear infinite;
}

.gyro-ring-2 {
    transform: rotateX(68deg) rotateY(12deg) translateZ(-10px);
    animation: spinGyro2 14s linear infinite reverse;
}

.pedestal-disc {
    transform: rotateX(72deg);
    animation: pulseDisc 3s ease-in-out infinite;
}

.doc-laser {
    animation: laserSweep 2.8s ease-in-out infinite;
}

.pulse-verify {
    animation: verifyFlip 1.5s cubic-bezier(0.22, 1, 0.36, 1) forwards !important;
}

@keyframes floatVault {
    0%, 100% {
        transform: translateY(0px) rotateZ(0deg);
    }
    50% {
        transform: translateY(-8px) rotateZ(1deg);
    }
}

@keyframes shieldFloat {
    0%, 100% {
        transform: translateZ(38px) translateY(0px) scale(1);
    }
    50% {
        transform: translateZ(46px) translateY(-5px) scale(1.08);
    }
}

@keyframes chipFloat {
    0%, 100% {
        transform: translateZ(32px) translateY(0px);
    }
    50% {
        transform: translateZ(38px) translateY(-4px);
    }
}

@keyframes laserSweep {
    0%, 100% {
        top: 8%;
        opacity: 0.25;
    }
    50% {
        top: 88%;
        opacity: 0.95;
    }
}

@keyframes spinGyro1 {
    0% {
        transform: rotateX(68deg) rotateY(-12deg) translateZ(-20px) rotateZ(0deg);
    }
    100% {
        transform: rotateX(68deg) rotateY(-12deg) translateZ(-20px) rotateZ(360deg);
    }
}

@keyframes spinGyro2 {
    0% {
        transform: rotateX(68deg) rotateY(12deg) translateZ(-10px) rotateZ(0deg);
    }
    100% {
        transform: rotateX(68deg) rotateY(12deg) translateZ(-10px) rotateZ(360deg);
    }
}

@keyframes pulseDisc {
    0%, 100% {
        transform: rotateX(72deg) scale(0.95);
        opacity: 0.4;
    }
    50% {
        transform: rotateX(72deg) scale(1.08);
        opacity: 0.85;
    }
}

@keyframes verifyFlip {
    0% {
        transform: scale(1) rotateY(0deg);
    }
    40% {
        transform: scale(1.1) translateZ(30px) rotateY(180deg);
    }
    100% {
        transform: scale(1) rotateY(360deg);
    }
}
</style>

