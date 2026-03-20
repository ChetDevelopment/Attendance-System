<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { 
  Camera, 
  X, 
  FlipHorizontal, 
  BadgeCheck, 
  Bell, 
  Loader2,
  CheckCircle2, 
  AlertCircle 
} from 'lucide-vue-next';
import { getUser, setUser, studentProfile } from '../../services/auth';
import { profileService } from '../../services/profileService';

const editName = ref(studentProfile.value.name);
const editAvatar = ref(studentProfile.value.avatar);
const selectedAvatarFile = ref<File | null>(null);
const isProcessing = ref(false);
const notificationEmail = ref(true);
const notificationPush = ref(true);
const originalState = ref({
  name: studentProfile.value.name,
  avatar: studentProfile.value.avatar,
  notificationEmail: true,
  notificationPush: true,
  email: studentProfile.value.email,
});
const isSettingsCameraOpen = ref(false);
const isMirrored = ref(true);
const showFlash = ref(false);
const videoRef = ref<HTMLVideoElement | null>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);
const videoDevices = ref<MediaDeviceInfo[]>([]);
const selectedDeviceId = ref<string>('');
const videoResolution = ref('0x0');
const notification = ref<{ message: string; type: 'success' | 'error' } | null>(null);
const emailAddress = computed(() => originalState.value.email || getUser()?.email || studentProfile.value.email || '');
const profileCardName = computed(() => editName.value || originalState.value.name || studentProfile.value.name);
const profileCardId = computed(() => studentProfile.value.id || getUser()?.student_id || getUser()?.id || 'N/A');
const profileClassName = computed(() => getUser()?.student?.class_name || studentProfile.value.className || '');

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
    selectedAvatarFile.value = null;
    closeSettingsCamera();
    showNotification("Photo captured!");
  } else {
    showNotification("Failed to capture photo", "error");
  }
};

const handleAvatarFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0] || null;

  if (!file) {
    return;
  }

  if (!file.type.startsWith('image/')) {
    showNotification("Please choose an image file.", "error");
    target.value = '';
    return;
  }

  selectedAvatarFile.value = file;
  editAvatar.value = URL.createObjectURL(file);
};

const dataUrlToFile = async (dataUrl: string, filename: string) => {
  const response = await fetch(dataUrl);
  const blob = await response.blob();
  return new File([blob], filename, { type: blob.type || 'image/jpeg' });
};

const saveSettings = async () => {
  if (!editName.value.trim()) {
    showNotification("Name cannot be empty", "error");
    return;
  }
  isProcessing.value = true;

  try {
    let avatarUrl = editAvatar.value;

    if (selectedAvatarFile.value) {
      const uploadResponse = await profileService.uploadAvatar(selectedAvatarFile.value);
      avatarUrl = uploadResponse?.avatar_url || avatarUrl;
    } else if (avatarUrl.startsWith('data:image/')) {
      // Camera captures are base64 data URLs; upload them as files instead of
      // trying to store the entire string in avatar_url.
      const avatarFile = await dataUrlToFile(avatarUrl, `student-avatar-${Date.now()}.jpg`);
      const uploadResponse = await profileService.uploadAvatar(avatarFile);
      avatarUrl = uploadResponse?.avatar_url || avatarUrl;
    }

    const [profileResponse, settingsResponse] = await Promise.all([
      profileService.updateProfile({
        name: editName.value.trim(),
        avatar_url: avatarUrl,
      }),
      profileService.updateSettings({
        notification_email: notificationEmail.value,
        notification_push: notificationPush.value,
      }),
    ]);

    const currentUser = getUser() || {};
    const updatedUser = {
      ...currentUser,
      name: profileResponse?.name || editName.value.trim(),
      fullname: profileResponse?.name || editName.value.trim(),
      email: profileResponse?.email || currentUser.email,
      avatar_url: profileResponse?.avatar_url || avatarUrl,
      avatar: profileResponse?.avatar_url || avatarUrl,
      student: profileResponse?.student || currentUser.student,
      notification_email: settingsResponse?.user?.notification_email ?? notificationEmail.value,
      notification_push: settingsResponse?.user?.notification_push ?? notificationPush.value,
    };

    setUser(updatedUser);
    originalState.value = {
      name: updatedUser.fullname || updatedUser.name || editName.value.trim(),
      avatar: updatedUser.avatar || updatedUser.avatar_url || avatarUrl,
      notificationEmail: updatedUser.notification_email ?? notificationEmail.value,
      notificationPush: updatedUser.notification_push ?? notificationPush.value,
      email: updatedUser.email || emailAddress.value,
    };
    editAvatar.value = originalState.value.avatar;
    selectedAvatarFile.value = null;
    showNotification("Profile updated successfully!");
  } catch (error: any) {
    showNotification(error?.message || "Failed to save settings.", "error");
  } finally {
    isProcessing.value = false;
  }
};

const cancelChanges = () => {
  editName.value = originalState.value.name || 'Student';
  editAvatar.value = originalState.value.avatar || studentProfile.value.avatar;
  notificationEmail.value = originalState.value.notificationEmail;
  notificationPush.value = originalState.value.notificationPush;
  selectedAvatarFile.value = null;
  stopWebcam();
  isSettingsCameraOpen.value = false;
};

onMounted(async () => {
  try {
    const profile = await profileService.getProfile();
    editName.value = profile?.name || editName.value;
    editAvatar.value = profile?.avatar_url || editAvatar.value;
    notificationEmail.value = profile?.notification_email ?? true;
    notificationPush.value = profile?.notification_push ?? true;

    const currentUser = getUser() || {};
    setUser({
      ...currentUser,
      name: profile?.name || currentUser.name,
      fullname: profile?.name || currentUser.fullname,
      email: profile?.email || currentUser.email,
      avatar_url: profile?.avatar_url || currentUser.avatar_url,
      avatar: profile?.avatar_url || currentUser.avatar,
      student: profile?.student || currentUser.student,
      notification_email: profile?.notification_email ?? currentUser.notification_email,
      notification_push: profile?.notification_push ?? currentUser.notification_push,
    });
    originalState.value = {
      name: profile?.name || editName.value,
      avatar: profile?.avatar_url || editAvatar.value,
      notificationEmail: profile?.notification_email ?? true,
      notificationPush: profile?.notification_push ?? true,
      email: profile?.email || currentUser.email || studentProfile.value.email,
    };
  } catch (error: any) {
    showNotification(error?.message || "Unable to load profile settings.", "error");
  }
});

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
          <h3 class="font-bold dark:text-white">{{ profileCardName }}</h3>
          <p class="text-xs text-slate-500 font-mono mt-1">ID: {{ profileCardId }}</p>
          <p v-if="profileClassName" class="text-xs text-slate-500 mt-1">{{ profileClassName }}</p>
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
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 uppercase tracking-wider">Profile Picture</label>
              <div class="space-y-3">
                <input
                  type="file"
                  accept="image/*"
                  @change="handleAvatarFileChange"
                  class="w-full px-4 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-primary outline-none dark:text-white file:mr-4 file:rounded-xl file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-blue-600"
                />
                <p class="text-xs text-slate-500">
                  Choose an image from your device, or use the camera button on the profile card.
                </p>
              </div>
            </div>
            <div>
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 uppercase tracking-wider">Student ID</label>
              <input 
                :value="profileCardId"
                disabled
                type="text" 
                class="w-full px-4 py-3.5 bg-slate-100 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl text-slate-500 cursor-not-allowed"
              />
            </div>
            <div>
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 uppercase tracking-wider">Email Address</label>
              <input 
                :value="emailAddress"
                disabled
                type="email" 
                class="w-full px-4 py-3.5 bg-slate-100 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl text-slate-500 cursor-not-allowed"
              />
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
              <input v-model="notificationEmail" type="checkbox" class="w-5 h-5 rounded border-slate-300 text-primary focus:ring-primary" />
              <span class="text-sm text-slate-600 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-slate-200">Email me when attendance is marked</span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer group">
              <input v-model="notificationPush" type="checkbox" class="w-5 h-5 rounded border-slate-300 text-primary focus:ring-primary" />
              <span class="text-sm text-slate-600 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-slate-200">Notify me about upcoming session deadlines</span>
            </label>
          </div>
        </div>

        <div class="flex justify-end gap-4">
          <button 
            @click="cancelChanges"
            class="px-8 py-4 border border-slate-200 dark:border-slate-700 rounded-2xl font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
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
