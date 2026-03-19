<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import { 
  Camera, 
  QrCode, 
  ArrowRight, 
  Calendar, 
  Clock,
  Loader2, 
  FlipHorizontal, 
  RefreshCw,
  Info,
  CheckCircle2,
  AlertCircle
} from 'lucide-vue-next';
import { getStudentDashboardStats, submitStudentCheckIn, submitStudentManualRequest } from '../../services/studentPortalService';

const attendanceMode = ref<'webcam' | 'qr' | 'manual'>('webcam');
const videoRef = ref<HTMLVideoElement | null>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);
const isProcessing = ref(false);
const isMirrored = ref(true);
const showFlash = ref(false);
const videoDevices = ref<MediaDeviceInfo[]>([]);
const selectedDeviceId = ref<string>('');
const videoResolution = ref('0x0');
const notification = ref<{ message: string; type: 'success' | 'error' } | null>(null);

const manualCourse = ref('Web Development II');
const manualReason = ref('');
const currentSession = ref<any>(null);
const currentDateLabel = computed(() => new Date().toLocaleDateString('en-US', {
  weekday: 'long',
  month: 'short',
  day: 'numeric',
  year: 'numeric',
}));
const currentCourseLabel = computed(() => currentSession.value?.course_name || currentSession.value?.name || manualCourse.value);

const decodeQrCodeFromCanvas = async (imageData: ImageData) => {
  if (typeof window === 'undefined' || !('BarcodeDetector' in window)) {
    return null;
  }

  try {
    const detector = new window.BarcodeDetector({ formats: ['qr_code'] });
    const barcodes = await detector.detect(imageData);
    return barcodes[0]?.rawValue || null;
  } catch (err) {
    console.error('QR detection failed:', err);
    return null;
  }
};

const showNotification = (message: string, type: 'success' | 'error' = 'success') => {
  notification.value = { message, type };
  setTimeout(() => {
    notification.value = null;
  }, 3000);
};

const getDevices = async () => {
  try {
    const allDevices = await navigator.mediaDevices.enumerateDevices();
    videoDevices.value = allDevices.filter(d => d.kind === 'videoinput');
    if (videoDevices.value.length > 0 && !selectedDeviceId.value) {
      selectedDeviceId.value = videoDevices.value[0].deviceId;
    }
  } catch (err) {
    console.error("Error enumerating devices:", err);
  }
};

const updateResolution = () => {
  if (videoRef.value) {
    videoResolution.value = `${videoRef.value.videoWidth}x${videoRef.value.videoHeight}`;
  }
};

const startWebcam = async () => {
  stopWebcam();
  try {
    const constraints = {
      video: {
        deviceId: selectedDeviceId.value ? { exact: selectedDeviceId.value } : undefined,
        facingMode: selectedDeviceId.value ? undefined : 'user',
        width: { ideal: 1920, min: 1280 },
        height: { ideal: 1080, min: 720 },
        frameRate: { ideal: 30 }
      }
    };
    const stream = await navigator.mediaDevices.getUserMedia(constraints);
    if (videoRef.value) {
      videoRef.value.srcObject = stream;
    }
  } catch (err) {
    console.error("Webcam error:", err);
    showNotification("Could not access webcam. Please check permissions.", "error");
  }
};

const stopWebcam = () => {
  if (videoRef.value && videoRef.value.srcObject) {
    const stream = videoRef.value.srcObject as MediaStream;
    stream.getTracks().forEach(track => track.stop());
    videoRef.value.srcObject = null;
  }
};

const capturePhoto = () => {
  if (!videoRef.value || !canvasRef.value) return null;
  const canvas = canvasRef.value;
  const video = videoRef.value;
  canvas.width = video.videoWidth;
  canvas.height = video.videoHeight;
  const ctx = canvas.getContext('2d');
  if (ctx) {
    ctx.save();
    if (isMirrored.value) {
      ctx.translate(canvas.width, 0);
      ctx.scale(-1, 1);
    }
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    ctx.restore();
    return canvas.toDataURL('image/jpeg', 0.8);
  }
  return null;
};

const handleCheckIn = async () => {
  if (!currentSession.value?.id) {
    showNotification("No active session is available right now.", "error");
    return;
  }

  isProcessing.value = true;
  showFlash.value = true;
  setTimeout(() => { showFlash.value = false; }, 150);

  const photo = capturePhoto();
  if (!photo) {
    showNotification("Webcam capture failed. Please check your camera connection.", "error");
    isProcessing.value = false;
    return;
  }

  try {
    const result = await submitStudentCheckIn({
      sessionId: currentSession.value.id,
      method: 'photo',
      photo,
    });
    showNotification(result?.message || "Attendance marked successfully!");
  } catch (err: any) {
    showNotification(err?.message || err?.response?.data?.message || "Attendance check-in failed.", "error");
  } finally {
    isProcessing.value = false;
  }
};

const scanQRCode = async () => {
  if (!videoRef.value || !canvasRef.value || attendanceMode.value !== 'qr') return;
  
  const video = videoRef.value;
  const canvas = canvasRef.value;
  const ctx = canvas.getContext('2d');
  
  if (ctx && video.readyState === video.HAVE_ENOUGH_DATA) {
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const code = await decodeQrCodeFromCanvas(imageData);

    if (code) {
      handleQRCheckIn(code);
      return;
    }
  }
  
  if (attendanceMode.value === 'qr') {
    requestAnimationFrame(scanQRCode);
  }
};

const handleQRCheckIn = async (qrData: string) => {
  if (!currentSession.value?.id) {
    showNotification("No active session is available right now.", "error");
    return;
  }

  isProcessing.value = true;

  try {
    const result = await submitStudentCheckIn({
      sessionId: currentSession.value.id,
      method: 'qrcode',
      qrCode: qrData,
    });
    showNotification(result?.message || "QR attendance marked successfully!");
    attendanceMode.value = 'webcam';
  } catch (err: any) {
    showNotification(err?.message || err?.response?.data?.message || "QR attendance failed.", "error");
  } finally {
    isProcessing.value = false;
  }
};

const submitManualRequest = async () => {
  if (!currentSession.value?.id) {
    showNotification("No active session is available right now.", "error");
    return;
  }

  if (!manualReason.value) {
    showNotification("Please provide a reason for the manual request.", "error");
    return;
  }

  isProcessing.value = true;
  try {
    const result = await submitStudentManualRequest({
      sessionId: currentSession.value.id,
      reason: manualReason.value,
    });
    showNotification(result?.message || "Manual request submitted for approval");
    manualReason.value = '';
    attendanceMode.value = 'webcam';
  } catch (err: any) {
    showNotification(err?.message || err?.response?.data?.message || "Manual request failed.", "error");
  } finally {
    isProcessing.value = false;
  }
};

watch(attendanceMode, (newMode) => {
  if (newMode === 'webcam' || newMode === 'qr') {
    getDevices().then(() => startWebcam());
    if (newMode === 'qr') {
      setTimeout(scanQRCode, 1000);
    }
  } else {
    stopWebcam();
  }
});

watch(selectedDeviceId, () => {
  if (attendanceMode.value !== 'manual') {
    startWebcam();
  }
});

onMounted(() => {
  getStudentDashboardStats()
    .then((stats) => {
      currentSession.value = stats?.currentSession || null;
      manualCourse.value = stats?.currentSession?.course_name || stats?.currentSession?.name || 'No active session';
    })
    .catch((error) => {
      console.error('Failed to load active session:', error);
    });
  getDevices().then(() => startWebcam());
});

onUnmounted(() => {
  stopWebcam();
});
</script>

<template>
  <div class="p-8 overflow-y-auto">
    <!-- Notification Toast -->
    <transition name="fade">
      <div v-if="notification" :class="[
        'fixed top-6 left-1/2 -translate-x-1/2 z-[100] px-6 py-3 rounded-2xl shadow-2xl flex items-center gap-3 border backdrop-blur-md',
        notification.type === 'success' ? 'bg-green-500/90 border-green-400 text-white' : 'bg-red-500/90 border-red-400 text-white'
      ]">
        <CheckCircle2 v-if="notification.type === 'success'" :size="20" />
        <AlertCircle v-else :size="20" />
        <span class="font-bold text-sm">{{ notification.message }}</span>
      </div>
    </transition>

    <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold mb-1 dark:text-white">Self Attendance Check-In</h1>
        <p class="text-slate-500 dark:text-slate-400">Complete your attendance for <span class="font-semibold text-slate-700 dark:text-slate-200">{{ currentCourseLabel }}</span></p>
      </div>
      <div class="flex items-center space-x-2 bg-white dark:bg-slate-900 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <Calendar class="text-primary" :size="18" />
        <span class="text-sm font-medium dark:text-white">{{ currentDateLabel }}</span>
      </div>
    </div>

    <div class="flex space-x-1 p-1 bg-slate-100 dark:bg-slate-800 rounded-2xl w-fit mb-8">
      <button 
        @click="attendanceMode = 'webcam'"
        :class="[
          'px-6 py-2.5 rounded-xl text-sm font-semibold flex items-center space-x-2 transition-all',
          attendanceMode === 'webcam' ? 'bg-white dark:bg-slate-700 shadow-sm text-primary' : 'text-slate-500 hover:text-slate-700'
        ]"
      >
        <Camera :size="16" />
        <span>Webcam Check-In</span>
      </button>
      <button 
        @click="attendanceMode = 'qr'"
        :class="[
          'px-6 py-2.5 rounded-xl text-sm font-semibold flex items-center space-x-2 transition-all',
          attendanceMode === 'qr' ? 'bg-white dark:bg-slate-700 shadow-sm text-primary' : 'text-slate-500 hover:text-slate-700'
        ]"
      >
        <QrCode :size="16" />
        <span>QR Scan</span>
      </button>
      <button 
        @click="attendanceMode = 'manual'"
        :class="[
          'px-6 py-2.5 rounded-xl text-sm font-semibold flex items-center space-x-2 transition-all',
          attendanceMode === 'manual' ? 'bg-white dark:bg-slate-700 shadow-sm text-primary' : 'text-slate-500 hover:text-slate-700'
        ]"
      >
        <ArrowRight :size="16" />
        <span>Manual Request</span>
      </button>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
      <div class="xl:col-span-2 space-y-6">
        <!-- Webcam/QR View -->
        <div v-if="attendanceMode !== 'manual'" class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden relative">
          <div class="relative aspect-video bg-slate-900 rounded-2xl overflow-hidden group border-4 border-slate-800 dark:border-slate-700 shadow-inner">
            <!-- Camera Feed -->
            <video 
              ref="videoRef"
              autoplay 
              playsinline
              @loadedmetadata="updateResolution"
              class="w-full h-full object-cover opacity-80 transition-transform duration-300"
              :class="{ 
                'grayscale contrast-125': attendanceMode === 'webcam',
                '-scale-x-100': isMirrored
              }"
            ></video>
            
            <canvas ref="canvasRef" class="hidden"></canvas>

            <!-- Flash Effect -->
            <transition name="fade">
              <div v-if="showFlash" class="absolute inset-0 bg-white z-50"></div>
            </transition>

            <!-- Camera Controls Overlay -->
            <div class="absolute top-4 right-4 flex flex-col gap-2 z-20">
              <button 
                @click="isMirrored = !isMirrored"
                class="p-2.5 bg-black/40 backdrop-blur-md text-white rounded-xl hover:bg-black/60 transition-all border border-white/10"
                title="Mirror Camera"
              >
                <FlipHorizontal :size="18" />
              </button>
              <button 
                v-if="videoDevices.length > 1"
                @click="selectedDeviceId = videoDevices[(videoDevices.findIndex(d => d.deviceId === selectedDeviceId) + 1) % videoDevices.length].deviceId"
                class="p-2.5 bg-black/40 backdrop-blur-md text-white rounded-xl hover:bg-black/60 transition-all border border-white/10"
                title="Switch Camera"
              >
                <RefreshCw :size="18" />
              </button>
            </div>

            <!-- Scanning Line (Webcam Mode) -->
            <div v-if="attendanceMode === 'webcam'" class="absolute inset-0 pointer-events-none">
              <div class="absolute w-full h-1 bg-primary/40 shadow-[0_0_15px_rgba(66,133,244,0.8)] animate-scan"></div>
            </div>

            <!-- QR Overlay (QR Mode) -->
            <div v-if="attendanceMode === 'qr'" class="absolute inset-0 flex items-center justify-center pointer-events-none">
              <div class="w-64 h-64 border-2 border-primary/60 rounded-3xl relative">
                <div class="absolute -top-1 -left-1 w-8 h-8 border-t-4 border-l-4 border-primary"></div>
                <div class="absolute -top-1 -right-1 w-8 h-8 border-t-4 border-r-4 border-primary"></div>
                <div class="absolute -bottom-1 -left-1 w-8 h-8 border-b-4 border-l-4 border-primary"></div>
                <div class="absolute -bottom-1 -right-1 w-8 h-8 border-b-4 border-r-4 border-primary"></div>
                <div class="absolute inset-0 bg-primary/5 animate-pulse"></div>
              </div>
            </div>

            <!-- HUD Corners (Webcam Mode) -->
            <div v-if="attendanceMode === 'webcam'" class="absolute inset-4 pointer-events-none border border-white/10 rounded-xl">
              <div class="absolute top-0 left-0 w-8 h-8 border-t-2 border-l-2 border-primary/40 rounded-tl-lg"></div>
              <div class="absolute top-0 right-0 w-8 h-8 border-t-2 border-r-2 border-primary/40 rounded-tr-lg"></div>
              <div class="absolute bottom-0 left-0 w-8 h-8 border-b-2 border-l-2 border-primary/40 rounded-bl-lg"></div>
              <div class="absolute bottom-0 right-0 w-8 h-8 border-b-2 border-r-2 border-primary/40 rounded-br-lg"></div>
            </div>

            <!-- Face Target Frame (Webcam Mode) -->
            <div v-if="attendanceMode === 'webcam'" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-56 h-72 pointer-events-none">
              <div class="absolute inset-0 border-2 border-dashed border-primary/30 rounded-[3rem]"></div>
              <div class="absolute inset-0 border-2 border-primary/60 rounded-[3rem] animate-pulse"></div>
              
              <!-- Target Corners -->
              <div class="absolute -top-2 -left-2 w-10 h-10 border-t-4 border-l-4 border-primary rounded-tl-2xl"></div>
              <div class="absolute -top-2 -right-2 w-10 h-10 border-t-4 border-r-4 border-primary rounded-tr-2xl"></div>
              <div class="absolute -bottom-2 -left-2 w-10 h-10 border-b-4 border-l-4 border-primary rounded-bl-2xl"></div>
              <div class="absolute -bottom-2 -right-2 w-10 h-10 border-b-4 border-r-4 border-primary rounded-br-2xl"></div>

              <!-- Scanning Crosshair -->
              <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full h-px bg-primary/20"></div>
              <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-full w-px bg-primary/20"></div>
            </div>

            <!-- HUD Data -->
            <div v-if="attendanceMode === 'webcam'" class="absolute top-6 right-16 text-right space-y-1 pointer-events-none">
              <div class="flex items-center justify-end gap-2">
                <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                <p class="text-[8px] font-mono text-primary/80 uppercase tracking-tighter">FACE_DETECT: TRUE</p>
              </div>
              <p class="text-[8px] font-mono text-primary/80 uppercase tracking-tighter">CONFIDENCE: 98.4%</p>
              <p class="text-[8px] font-mono text-primary/80 uppercase tracking-tighter">LIVENESS: VERIFIED</p>
              <div class="flex items-center justify-end gap-1.5">
                <span v-if="parseInt(videoResolution.split('x')[1]) >= 720" class="text-[7px] bg-primary/20 text-primary px-1 rounded font-bold">HD</span>
                <span v-if="parseInt(videoResolution.split('x')[1]) >= 1080" class="text-[7px] bg-primary/20 text-primary px-1 rounded font-bold">FHD</span>
                <p class="text-[8px] font-mono text-primary/80 uppercase tracking-tighter">RES: {{ videoResolution }}</p>
              </div>
            </div>

            <div class="absolute top-6 left-6 bg-black/60 backdrop-blur-md text-white text-[9px] px-3 py-1.5 rounded-lg flex items-center space-x-2 font-bold uppercase tracking-widest border border-white/10">
              <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
              <span>{{ attendanceMode === 'qr' ? 'QR_SCANNER // ACTIVE' : 'REC // LIVE_FEED' }}</span>
            </div>

            <div v-if="attendanceMode === 'webcam'" class="absolute bottom-6 left-1/2 -translate-x-1/2 w-full px-8">
              <button 
                @click="handleCheckIn"
                :disabled="isProcessing"
                class="w-full bg-primary hover:bg-blue-600 disabled:bg-slate-400 text-white py-4 rounded-2xl font-bold flex items-center justify-center space-x-3 shadow-2xl shadow-blue-500/40 transition-all active:scale-95 group/btn"
              >
                <Loader2 v-if="isProcessing" class="animate-spin" :size="20" />
                <Camera v-else :size="20" class="group-hover/btn:rotate-12 transition-transform" />
                <span class="tracking-tight">{{ isProcessing ? 'PROCESSING...' : 'AUTHENTICATE & CHECK-IN' }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Manual Request View -->
        <div v-else class="bg-white dark:bg-slate-900 p-8 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm">
          <div class="mb-8">
            <h3 class="text-xl font-bold dark:text-white mb-2">Manual Attendance Request</h3>
            <p class="text-sm text-slate-500">If you're having technical issues with the camera, please submit a manual request for instructor approval.</p>
          </div>

          <div class="space-y-6">
            <div>
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 uppercase tracking-wider">Course Session</label>
	              <select v-model="manualCourse" class="w-full px-4 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-primary outline-none dark:text-white">
	                <option :value="manualCourse">{{ manualCourse }}</option>
	              </select>
	            </div>

            <div>
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 uppercase tracking-wider">Reason for Manual Request</label>
              <textarea 
                v-model="manualReason"
                rows="4" 
                class="w-full px-4 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-primary outline-none dark:text-white"
                placeholder="e.g. Camera hardware failure, browser permission issue..."
              ></textarea>
            </div>

            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-2xl flex gap-3">
              <Info class="text-primary shrink-0" :size="20" />
              <p class="text-xs text-slate-600 dark:text-slate-400">Manual requests are reviewed by the course instructor. You will be notified once approved.</p>
            </div>

            <button 
              @click="submitManualRequest"
              :disabled="isProcessing"
              class="w-full bg-primary hover:bg-blue-600 disabled:bg-slate-400 text-white py-4 rounded-2xl font-bold flex items-center justify-center space-x-3 transition-all active:scale-95"
            >
              <Loader2 v-if="isProcessing" class="animate-spin" :size="20" />
              <span>{{ isProcessing ? 'SUBMITTING...' : 'SUBMIT REQUEST' }}</span>
            </button>
          </div>
        </div>
      </div>

      <div class="space-y-6">
        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm">
          <h3 class="font-bold dark:text-white mb-4 flex items-center gap-2">
            <Info class="text-primary" :size="18" />
            Instructions
          </h3>
          <ul class="space-y-3 text-sm text-slate-500 dark:text-slate-400">
            <li class="flex gap-2">
              <span class="w-5 h-5 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-[10px] font-bold shrink-0">1</span>
              Ensure your face is clearly visible within the target frame.
            </li>
            <li class="flex gap-2">
              <span class="w-5 h-5 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-[10px] font-bold shrink-0">2</span>
              Avoid strong backlighting or wearing masks/sunglasses.
            </li>
            <li class="flex gap-2">
              <span class="w-5 h-5 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-[10px] font-bold shrink-0">3</span>
              Click "Authenticate" to capture your photo and mark attendance.
            </li>
          </ul>
        </div>

        <div class="bg-amber-50 dark:bg-amber-900/10 p-6 rounded-3xl border border-amber-100 dark:border-amber-900/20">
          <div class="flex gap-3 mb-3">
            <Clock class="text-amber-600" :size="20" />
            <h4 class="font-bold text-amber-900 dark:text-amber-400 text-sm">Session Deadline</h4>
          </div>
          <p class="text-xs text-amber-700 dark:text-amber-500/80 leading-relaxed">
            Attendance check-in for <span class="font-bold">{{ currentCourseLabel }}</span> is only available during the active session window.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes scan {
  0% { top: 0%; }
  50% { top: 100%; }
  100% { top: 0%; }
}
.animate-scan {
  animation: scan 3s linear infinite;
}
</style>
