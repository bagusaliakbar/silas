<script setup>
import { ref, watch, onMounted, nextTick } from 'vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    signerName: {
        type: String,
        default: '',
    },
    defaultSignatureUrl: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['close', 'save']);

const activeTab = ref('draw'); // 'draw', 'type', 'upload'
const canvasRef = ref(null);
const isDrawing = ref(false);
const hasDrawn = ref(false);
const strokeColor = ref('#0f172a');
const strokeWidth = ref(2.5);

// Type tab state
const typedName = ref('');
const selectedFont = ref('font-cursive-1');
const typeFonts = [
    { id: 'font-cursive-1', name: 'Gaya Formal', style: 'font-serif italic tracking-wide text-2xl font-bold' },
    { id: 'font-cursive-2', name: 'Gaya Bersambung', style: 'italic tracking-wider text-2xl font-light' },
    { id: 'font-cursive-3', name: 'Gaya Tegas', style: 'tracking-tight text-xl font-bold uppercase' },
];

// Upload tab state
const uploadedImageData = ref(null);
const fileInputRef = ref(null);

// Consent
const consentChecked = ref(true);

// Stamp Options
const includeStamp = ref(false);
const defaultStampUrl = '/images/stamps/stempel-fasilkom.png';

watch(() => props.show, (newVal) => {
    if (newVal) {
        typedName.value = props.signerName || '';
        includeStamp.value = false;
        if (props.defaultSignatureUrl) {
            uploadedImageData.value = props.defaultSignatureUrl;
            activeTab.value = 'upload';
        } else {
            activeTab.value = 'draw';
        }
        nextTick(() => {
            initCanvas();
        });
    }
});

const initCanvas = () => {
    const canvas = canvasRef.value;
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const rect = canvas.getBoundingClientRect();
    canvas.width = rect.width * 2; // high-dpi
    canvas.height = rect.height * 2;
    ctx.scale(2, 2);
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.strokeStyle = strokeColor.value;
    ctx.lineWidth = strokeWidth.value;
    clearCanvas();
};

const clearCanvas = () => {
    const canvas = canvasRef.value;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    hasDrawn.value = false;
};

const getCanvasCoordinates = (event) => {
    const canvas = canvasRef.value;
    const rect = canvas.getBoundingClientRect();
    const clientX = event.touches ? event.touches[0].clientX : event.clientX;
    const clientY = event.touches ? event.touches[0].clientY : event.clientY;
    return {
        x: clientX - rect.left,
        y: clientY - rect.top,
    };
};

const startDrawing = (event) => {
    isDrawing.value = true;
    hasDrawn.value = true;
    const canvas = canvasRef.value;
    const ctx = canvas.getContext('2d');
    const { x, y } = getCanvasCoordinates(event);
    ctx.beginPath();
    ctx.moveTo(x, y);
    ctx.strokeStyle = strokeColor.value;
    ctx.lineWidth = strokeWidth.value;
};

const draw = (event) => {
    if (!isDrawing.value) return;
    event.preventDefault();
    const canvas = canvasRef.value;
    const ctx = canvas.getContext('2d');
    const { x, y } = getCanvasCoordinates(event);
    ctx.lineTo(x, y);
    ctx.stroke();
};

const stopDrawing = () => {
    isDrawing.value = false;
};

const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) {
        alert('Silakan pilih file gambar (PNG / JPG).');
        return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
        uploadedImageData.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

const generateTypeSignatureImage = () => {
    const canvas = document.createElement('canvas');
    canvas.width = 500;
    canvas.height = 180;
    const ctx = canvas.getContext('2d');

    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = strokeColor.value;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';

    if (selectedFont.value === 'font-cursive-1') {
        ctx.font = 'italic bold 44px Georgia, serif';
    } else if (selectedFont.value === 'font-cursive-2') {
        ctx.font = 'italic 40px "Brush Script MT", cursive, sans-serif';
    } else {
        ctx.font = 'bold 36px "Segoe UI", sans-serif';
    }

    ctx.fillText(typedName.value || 'Tanda Tangan', canvas.width / 2, canvas.height / 2);
    return canvas.toDataURL('image/png');
};

// Merge signature image with stamp image
const mergeSignatureWithStamp = async (baseSigData) => {
    return new Promise((resolve) => {
        const sigImg = new Image();
        sigImg.crossOrigin = 'anonymous';
        sigImg.onload = () => {
            const stampImg = new Image();
            stampImg.crossOrigin = 'anonymous';
            stampImg.onload = () => {
                const canvas = document.createElement('canvas');
                // Target wide canvas: stamp on left slightly overlapping with signature on right
                const stampSize = 260;
                const sigW = sigImg.width || 300;
                const sigH = sigImg.height || 200;

                // Scale signature to balance with stamp
                const scale = stampSize / Math.max(sigH, 180);
                const finalSigW = sigW * scale;
                const finalSigH = sigH * scale;

                // Overlap: stamp slightly under the signature on the left
                const overlap = stampSize * 0.28;
                const totalW = stampSize + finalSigW - overlap;
                const totalH = Math.max(stampSize, finalSigH) + 20;

                canvas.width = totalW;
                canvas.height = totalH;
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, totalW, totalH);

                // 1. Draw Stamp on the left
                const stampY = (totalH - stampSize) / 2;
                ctx.globalAlpha = 0.92;
                ctx.drawImage(stampImg, 0, stampY, stampSize, stampSize);

                // 2. Draw Signature on the right overlapping with stamp
                ctx.globalAlpha = 1.0;
                const sigX = stampSize - overlap;
                const sigY = (totalH - finalSigH) / 2;
                ctx.drawImage(sigImg, sigX, sigY, finalSigW, finalSigH);

                resolve(canvas.toDataURL('image/png'));
            };
            stampImg.onerror = () => resolve(baseSigData);
            stampImg.src = defaultStampUrl;
        };
        sigImg.onerror = () => resolve(baseSigData);
        sigImg.src = baseSigData;
    });
};

const saveSignature = async () => {
    if (!consentChecked.value) {
        alert('Anda harus menyetujui ketentuan tanda tangan elektronik terlebih dahulu.');
        return;
    }

    let signatureData = null;
    let signatureType = activeTab.value;

    if (activeTab.value === 'draw') {
        if (!hasDrawn.value) {
            alert('Silakan bubuhkan tanda tangan di atas kanvas terlebih dahulu.');
            return;
        }
        signatureData = canvasRef.value.toDataURL('image/png');
        signatureType = 'canvas_drawing';
    } else if (activeTab.value === 'type') {
        if (!typedName.value.trim()) {
            alert('Silakan masukkan nama tanda tangan.');
            return;
        }
        signatureData = generateTypeSignatureImage();
        signatureType = 'typed_name';
    } else if (activeTab.value === 'upload') {
        if (!uploadedImageData.value) {
            alert('Silakan unggah gambar tanda tangan.');
            return;
        }
        signatureData = uploadedImageData.value;
        signatureType = 'image_upload';
    }

    if (includeStamp.value && signatureData) {
        signatureData = await mergeSignatureWithStamp(signatureData);
    }

    emit('save', { signatureData, signatureType });
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="emit('close')" max-width="lg">
        <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl">
            <!-- Header -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Bubuhkan Tanda Tangan</h3>
                    <p class="text-xs text-slate-500">Pilih metode pembuatan tanda tangan elektronik Anda</p>
                </div>
                <button
                    @click="emit('close')"
                    class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Tabs -->
            <div class="flex gap-2 p-1 my-4 bg-slate-100 dark:bg-slate-800 rounded-xl">
                <button
                    @click="activeTab = 'draw'"
                    :class="[
                        'flex-1 py-2 text-xs font-semibold rounded-lg transition-all',
                        activeTab === 'draw'
                            ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-300 shadow-sm'
                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                    ]"
                >
                    Goresan (Draw)
                </button>
                <button
                    @click="activeTab = 'type'"
                    :class="[
                        'flex-1 py-2 text-xs font-semibold rounded-lg transition-all',
                        activeTab === 'type'
                            ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-300 shadow-sm'
                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                    ]"
                >
                    Ketik Nama (Type)
                </button>
                <button
                    @click="activeTab = 'upload'"
                    :class="[
                        'flex-1 py-2 text-xs font-semibold rounded-lg transition-all',
                        activeTab === 'upload'
                            ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-300 shadow-sm'
                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                    ]"
                >
                    Unggah Gambar (Upload)
                </button>
            </div>

            <!-- TAB 1: DRAW -->
            <div v-show="activeTab === 'draw'" class="space-y-3">
                <div class="relative border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-950 overflow-hidden">
                    <canvas
                        ref="canvasRef"
                        class="w-full h-48 cursor-crosshair touch-none"
                        @mousedown="startDrawing"
                        @mousemove="draw"
                        @mouseup="stopDrawing"
                        @mouseleave="stopDrawing"
                        @touchstart="startDrawing"
                        @touchmove="draw"
                        @touchend="stopDrawing"
                    ></canvas>
                    <div class="absolute bottom-2 right-2 flex items-center gap-2">
                        <button
                            type="button"
                            @click="clearCanvas"
                            class="px-2.5 py-1 text-xs font-medium rounded-md bg-white dark:bg-slate-800 text-rose-600 border border-slate-200 dark:border-slate-700 shadow-xs hover:bg-rose-50 dark:hover:bg-rose-950 transition"
                        >
                            Hapus Coretan
                        </button>
                    </div>
                    <div v-if="!hasDrawn" class="absolute inset-0 flex items-center justify-center pointer-events-none text-slate-400 text-xs">
                        Tanda tangani di sini dengan mouse atau layar sentuh
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-slate-500">
                    <div class="flex items-center gap-2">
                        <span>Warna Tinta:</span>
                        <button
                            type="button"
                            @click="strokeColor = '#0f172a'"
                            :class="['w-5 h-5 rounded-full bg-slate-900 border-2', strokeColor === '#0f172a' ? 'ring-2 ring-indigo-500' : '']"
                        ></button>
                        <button
                            type="button"
                            @click="strokeColor = '#1e3a8a'"
                            :class="['w-5 h-5 rounded-full bg-blue-900 border-2', strokeColor === '#1e3a8a' ? 'ring-2 ring-indigo-500' : '']"
                        ></button>
                    </div>
                    <span class="text-[11px] text-slate-400">Tekan mouse dan geser untuk menggambar</span>
                </div>
            </div>

            <!-- TAB 2: TYPE -->
            <div v-show="activeTab === 'type'" class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Tanda Tangan</label>
                    <input
                        v-model="typedName"
                        type="text"
                        placeholder="Bagus Ali Akbar"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                    />
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">Pilih Gaya Font Tulisan:</label>
                    <div class="grid grid-cols-1 gap-2">
                        <div
                            v-for="font in typeFonts"
                            :key="font.id"
                            @click="selectedFont = font.id"
                            :class="[
                                'p-3 rounded-xl border cursor-pointer transition flex items-center justify-between bg-slate-50 dark:bg-slate-950',
                                selectedFont === font.id
                                    ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-50/20'
                                    : 'border-slate-200 dark:border-slate-800 hover:border-slate-300'
                            ]"
                        >
                            <span :class="[font.style, 'text-slate-800 dark:text-slate-100']">
                                {{ typedName || 'Pratinjau Tanda Tangan' }}
                            </span>
                            <span class="text-[11px] text-slate-400 font-sans">{{ font.name }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: UPLOAD -->
            <div v-show="activeTab === 'upload'" class="space-y-3">
                <div v-if="defaultSignatureUrl && uploadedImageData === defaultSignatureUrl" class="flex items-center gap-2 p-2.5 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-xl text-xs text-emerald-800 dark:text-emerald-300">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Tanda tangan visual resmi pejabat penandatangan berhasil terdeteksi otomatis.</span>
                </div>

                <div
                    @click="fileInputRef.click()"
                    class="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-6 text-center cursor-pointer hover:border-indigo-400 bg-slate-50 dark:bg-slate-950 transition"
                >
                    <input
                        ref="fileInputRef"
                        type="file"
                        accept="image/png, image/jpeg"
                        class="hidden"
                        @change="handleFileUpload"
                    />
                    <div v-if="uploadedImageData" class="flex flex-col items-center">
                        <img :src="uploadedImageData" alt="Tanda Tangan" class="max-h-32 object-contain rounded-lg" />
                        <span class="text-xs text-indigo-600 mt-2">Klik untuk mengganti gambar</span>
                    </div>
                    <div v-else class="flex flex-col items-center text-slate-400">
                        <svg class="w-8 h-8 mb-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="text-xs font-medium text-slate-600 dark:text-slate-300">Pilih berkas tanda tangan (PNG / JPG)</span>
                        <span class="text-[11px] text-slate-400 mt-1">Disarankan latar belakang transparan / putih bersih</span>
                    </div>
                </div>
            </div>

            <!-- Legal Consent Checkbox -->
            <div class="mt-5 p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                <label class="flex items-start gap-2.5 cursor-pointer">
                    <input
                        v-model="consentChecked"
                        type="checkbox"
                        class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <span class="text-[11px] leading-relaxed text-slate-600 dark:text-slate-400">
                        Saya menyetujui pembubuhan tanda tangan elektronik ini secara sadar dan mengikat secara hukum sesuai dengan <strong>UU ITE No. 11/2008 & PP 71/2019</strong>.
                    </span>
                </label>
            </div>

            <!-- Footer Actions -->
            <div class="flex items-center justify-end gap-2.5 mt-5">
                <button
                    type="button"
                    @click="emit('close')"
                    class="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition"
                >
                    Batal
                </button>
                <button
                    type="button"
                    @click="saveSignature"
                    class="px-5 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm shadow-indigo-600/30 transition hover:scale-[1.02] active:scale-[0.98]"
                >
                    Gunakan Tanda Tangan Ini
                </button>
            </div>
        </div>
    </Modal>
</template>
