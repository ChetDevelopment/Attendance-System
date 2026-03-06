<script setup lang="ts">
import { ref, onUnmounted } from 'vue';
import { 
  Camera, 
  X, 
  FlipHorizontal, 
  BadgeCheck, 
  Lock, 
  Bell, 
  Loader2,
  CheckCircle2, 
  AlertCircle 
} from 'lucide-vue-next';
import { studentProfile, updateProfile } from '../../services/auth';

const editName = ref(studentProfile.value.name);
const editAvatar = ref(studentProfile.value.avatar);
const isProcessing = ref(false);
const isSettingsCameraOpen = ref(false);
const isMirrored = ref(true);
const showFlash = ref(false);
const videoRef = ref<HTMLVideoElement | null>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);
const videoDevices = ref<MediaDeviceInfo[]>([]);
const selectedDeviceId = ref<string>('');
const videoResolution = ref('0x0');
const notification = ref<{ message: string; type: 'success' | 'error' } | null>(null);

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
    showNotification("Could not access webcam.", "error");
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

const openSettingsCamera = async () => {
  isSettingsCameraOpen.value = true;
  await getDevices();
  await startWebcam();
};

const closeSettingsCamera = () => {
  isSettingsCameraOpen.value = false;
  stopWebcam();
};

const captureSettingsPhoto = () => {
  const photo = capturePhoto();
  if (photo) {
    editAvatar.value = photo;
    closeSettingsCamera();
    showNotification("Photo captured!");
  } else {
    showNotification("Failed to capture photo", "error");
  }
};

const saveSettings = () => {
  if (!editName.value.trim()) {
    showNotification("Name cannot be empty", "error");
    return;
  }
  isProcessing.value = true;
  setTimeout(() => {
    updateProfile(editName.value, editAvatar.value);
    isProcessing.value = false;
    showNotification("Profile updated successfully!");
  }, 800);
};

onUnmounted(() => {
  stopWebcam();
});
</script>

<template>
  <div class="p-8 max-w-4xl mx-auto">
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

    <div class="mb-8">
      <h1 class="text-3xl font-bold dark:text-white">Account Settings</h1>
      <p class="text-slate-500 mt-2">Update your personal information and preferences.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <div class="md:col-span-1">
        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm text-center">
          <div class="relative inline-block mb-4">
            <img 
              :src="editAvatar" 
              alt="Profile Preview" 
              class="w-32 h-32 rounded-full object-cover ring-4 ring-primary/10 mx-auto"
            />
            <button 
              @click="openSettingsCamera"
              class="absolute bottom-0 right-0 p-2 bg-primary text-white rounded-full shadow-lg hover:scale-110 transition-transform"
            >
              <Camera :size="16" />
            </button>
          </div>
          <h3 class="font-bold dark:text-white">{{ studentProfile.name }}</h3>
          <p class="text-xs text-slate-500 font-mono mt-1">ID: {{ studentProfile.id }}</p>
        </div>

        <!-- Settings Camera Modal -->
        <div v-if="isSettingsCameraOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
          <div class="bg-white dark:bg-slate-900 w-full max-w-xl rounded-3xl overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-800">
            <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
              <h3 class="font-bold dark:text-white">Take Profile Photo</h3>
              <button @click="closeSettingsCamera" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors">
                <X :size="20" class="text-slate-500" />
              </button>
            </div>
            <div class="p-6">
              <div class="relative aspect-square bg-slate-900 rounded-2xl overflow-hidden mb-6">
                <video 
                  ref="videoRef"
                  autoplay 
                  playsinline
                  class="w-full h-full object-cover"
                  :class="{ '-scale-x-100': isMirrored }"
                ></video>
                <canvas ref="canvasRef" class="hidden"></canvas>
                <div class="absolute inset-0 border-2 border-primary/20 rounded-2xl pointer-events-none"></div>
                
                <!-- Flash Effect -->
                <transition name="fade">
                  <div v-if="showFlash" class="absolute inset-0 bg-white z-50"></div>
                </transition>

                <!-- Resolution HUD -->
                <div class="absolute bottom-4 right-4 bg-black/60 backdrop-blur-md text-white text-[8px] px-2 py-1 rounded font-mono font-bold uppercase tracking-widest border border-white/10 pointer-events-none">
                  {{ videoResolution }}
                </div>

                <div class="absolute top-4 right-4 flex flex-col gap-2">
                  <button 
                    @click="isMirrored = !isMirrored"
                    class="p-2 bg-black/40 backdrop-blur-md text-white rounded-lg hover:bg-black/60 transition-all"
                  >
                    <FlipHorizontal :size="16" />
                  </button>
                </div>
              </div>
              
              <div class="flex gap-3">
                <button 
                  @click="closeSettingsCamera"
                  class="flex-1 px-6 py-3.5 border border-slate-200 dark:border-slate-700 rounded-2xl font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all"
                >
                  Cancel
                </button>
                <button 
                  @click="captureSettingsPhoto"
                  class="flex-[2] bg-primary hover:bg-blue-600 text-white px-6 py-3.5 rounded-2xl font-bold transition-all shadow-lg shadow-primary/20 flex items-center justify-center gap-2"
                >
                  <Camera :size="20" />
                  Capture & Use
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="md:col-span-2 space-y-6">
        <div class="bg-white dark:bg-slate-900 p-8 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm">
          <h3 class="text-lg font-bold mb-6 dark:text-white flex items-center gap-2">
            <BadgeCheck class="text-primary" :size="20" />
            Personal Information
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 uppercase tracking-wider">Full Name</label>
              <input 
                v-model="editName"
                type="text" 
                class="w-full px-4 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-primary outline-none dark:text-white"
              />
            </div>
            <div class="md:col-span-2">
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 uppercase tracking-wider">Avatar URL</label>
              <input 
                v-model="editAvatar"
                type="text" 
                class="w-full px-4 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-primary outline-none dark:text-white"
              />
            </div>
            <div>
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 uppercase tracking-wider">Student ID</label>
              <input 
                :value="studentProfile.id"
                disabled
                type="text" 
                class="w-full px-4 py-3.5 bg-slate-100 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl text-slate-500 cursor-not-allowed"
              />
            </div>
            <div>
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 uppercase tracking-wider">Email Address</label>
              <input 
                value="mary.sao@student.passerellesnumeriques.org"
                disabled
                type="email" 
                class="w-full px-4 py-3.5 bg-slate-100 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl text-slate-500 cursor-not-allowed"
              />
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 p-8 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm">
          <h3 class="text-lg font-bold mb-6 dark:text-white flex items-center gap-2">
            <Lock class="text-primary" :size="20" />
            Security & Privacy
          </h3>
          <div class="space-y-4">
            <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700">
              <div>
                <p class="text-sm font-bold dark:text-white">Two-Factor Authentication</p>
                <p class="text-xs text-slate-500">Add an extra layer of security to your account.</p>
              </div>
              <div class="w-12 h-6 bg-slate-200 dark:bg-slate-700 rounded-full relative cursor-pointer">
                <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full shadow-sm"></div>
              </div>
            </div>
            <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700">
              <div>
                <p class="text-sm font-bold dark:text-white">Face ID Login</p>
                <p class="text-xs text-slate-500">Use your camera to log in automatically.</p>
              </div>
              <div class="w-12 h-6 bg-primary rounded-full relative cursor-pointer">
                <div class="absolute right-1 top-1 w-4 h-4 bg-white rounded-full shadow-sm"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 p-8 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm">
          <h3 class="text-lg font-bold mb-6 dark:text-white flex items-center gap-2">
            <Bell class="text-primary" :size="20" />
            Notification Preferences
          </h3>
          <div class="space-y-4">
            <label class="flex items-center gap-3 cursor-pointer group">
              <input type="checkbox" checked class="w-5 h-5 rounded border-slate-300 text-primary focus:ring-primary" />
              <span class="text-sm text-slate-600 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-slate-200">Email me when attendance is marked</span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer group">
              <input type="checkbox" checked class="w-5 h-5 rounded border-slate-300 text-primary focus:ring-primary" />
              <span class="text-sm text-slate-600 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-slate-200">Notify me about upcoming session deadlines</span>
            </label>
          </div>
        </div>

        <div class="flex justify-end gap-4">
          <button class="px-8 py-4 border border-slate-200 dark:border-slate-700 rounded-2xl font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
            Cancel Changes
          </button>
          <button 
            @click="saveSettings"
            :disabled="isProcessing"
            class="px-8 py-4 bg-primary hover:bg-blue-600 disabled:bg-slate-400 text-white rounded-2xl font-bold shadow-lg shadow-primary/20 transition-all active:scale-95 flex items-center gap-2"
          >
            <Loader2 v-if="isProcessing" class="animate-spin" :size="20" />
            {{ isProcessing ? 'Saving...' : 'Save Profile Changes' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
