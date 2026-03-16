<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { Save, Upload } from 'lucide-vue-next'
import { profileService } from '../../services/profileService'
import { dashboardService } from '../../services/dashboardService'
import { setUser } from '../../services/auth'

type ProfileData = {
  id: number
  name: string
  email: string
  role: string
  avatar_url: string | null
  phone: string | null
  bio: string | null
}

const loading = ref(false)
const saving = ref(false)
const uploadingAvatar = ref(false)
const isEditing = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const validationErrors = ref<Record<string, string[]>>({})

const profile = ref<ProfileData | null>(null)
const summary = ref({
  total_present_today: 0,
  total_absent_today: 0,
  total_late_today: 0,
})

const form = ref({
  name: '',
  phone: '',
  bio: '',
  avatar_url: '',
})

const loadData = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const [profileData, overviewData] = await Promise.all([
      profileService.getProfile(),
      dashboardService.getOverview(),
    ])

    profile.value = profileData
    const summaryData = overviewData.summary || {}
    summary.value = {
      total_present_today: Number(summaryData.total_present_today || 0),
      total_absent_today: Number(summaryData.total_absent_today || 0),
      total_late_today: Number(summaryData.total_late_today || 0),
    }

    form.value = {
      name: profileData?.name || '',
      phone: profileData?.phone || '',
      bio: profileData?.bio || '',
      avatar_url: profileData?.avatar_url || '',
    }
  } catch (error: any) {
    errorMessage.value = error.message || 'Failed to load profile.'
  } finally {
    loading.value = false
  }
}

const saveProfile = async () => {
  if (saving.value) return
  saving.value = true
  errorMessage.value = ''
  successMessage.value = ''
  validationErrors.value = {}

  try {
    // Only include avatar_url if it was uploaded via file (not base64)
    const payload: any = {
      name: form.value.name,
      phone: form.value.phone || null,
      bio: form.value.bio || null,
    }
    
    // Only include avatar_url if it's not a base64 string (was uploaded via file)
    if (form.value.avatar_url && !form.value.avatar_url.startsWith('data:')) {
      payload.avatar_url = form.value.avatar_url
    }
    
    const updated = await profileService.updateProfile(payload)
    profile.value = updated
    setUser(updated)
    isEditing.value = false
    successMessage.value = 'Profile updated successfully.'
  } catch (error: any) {
    errorMessage.value = error.message || 'Failed to save profile.'
    validationErrors.value = error.errors || {}
  } finally {
    saving.value = false
  }
}

const handleAvatarUpload = async (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  uploadingAvatar.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await profileService.uploadAvatar(file)
    const avatarUrl = String(response?.avatar_url || response?.user?.avatar_url || '')

    if (avatarUrl) {
      form.value.avatar_url = avatarUrl
      if (profile.value) {
        profile.value = { ...profile.value, avatar_url: avatarUrl }
        setUser(profile.value)
      }
    }

    successMessage.value = 'Avatar uploaded successfully.'
  } catch (error: any) {
    errorMessage.value = error.message || 'Failed to upload avatar.'
  } finally {
    uploadingAvatar.value = false
    input.value = ''
  }
}

onMounted(loadData)
</script>

<template>
  <div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Admin Profile</h2>
        <p class="text-sm text-slate-500 font-medium">Profile values are loaded and saved via backend API</p>
      </div>
      <button class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-bold text-slate-600" @click="isEditing = !isEditing">
        {{ isEditing ? 'Cancel' : 'Edit Profile' }}
      </button>
    </div>

    <p v-if="errorMessage" class="p-3 rounded-lg bg-rose-50 text-rose-700 text-sm">{{ errorMessage }}</p>
    <p v-if="successMessage" class="p-3 rounded-lg bg-emerald-50 text-emerald-700 text-sm">{{ successMessage }}</p>

    <div v-if="loading" class="bg-white p-8 rounded-xl border border-slate-200 shadow-sm text-slate-500">
      Loading profile...
    </div>

    <div v-else-if="profile" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
        <div class="flex items-center gap-3">
          <img
            v-if="profile.avatar_url"
            :src="profile.avatar_url"
            alt="avatar"
            class="size-14 rounded-full object-cover border border-slate-200"
          />
          <div v-else class="size-14 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-lg font-bold text-slate-600">
            {{ profile.name?.charAt(0) || 'U' }}
          </div>
          <div>
            <h3 class="text-lg font-black text-slate-900">{{ profile.name }}</h3>
            <p class="text-xs font-bold text-slate-400 uppercase">{{ profile.role || 'admin' }}</p>
          </div>
        </div>
        <div class="space-y-2 text-sm">
          <p><span class="font-bold text-slate-600">Email:</span> {{ profile.email }}</p>
          <p><span class="font-bold text-slate-600">Phone:</span> {{ profile.phone || '-' }}</p>
        </div>
      </div>

      <div class="lg:col-span-2 bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
        <h3 class="text-lg font-bold text-slate-900">Personal Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-[10px] font-bold text-slate-500 uppercase">Name</label>
            <input v-model="form.name" :disabled="!isEditing" class="mt-1 w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded disabled:opacity-70" />
            <p v-if="validationErrors.name" class="text-xs text-red-500 mt-1">{{ validationErrors.name[0] }}</p>
          </div>
          <div>
            <label class="text-[10px] font-bold text-slate-500 uppercase">Email</label>
            <input :value="profile.email" disabled class="mt-1 w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded opacity-70" />
          </div>
          <div>
            <label class="text-[10px] font-bold text-slate-500 uppercase">Phone</label>
            <input v-model="form.phone" :disabled="!isEditing" class="mt-1 w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded disabled:opacity-70" />
            <p v-if="validationErrors.phone" class="text-xs text-red-500 mt-1">{{ validationErrors.phone[0] }}</p>
          </div>
          <div v-if="isEditing">
            <label class="text-[10px] font-bold text-slate-500 uppercase">Avatar</label>
            <div class="mt-2">
              <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-100 text-slate-700 text-xs font-bold cursor-pointer hover:bg-slate-200">
                <Upload class="size-4" />
                {{ uploadingAvatar ? 'Uploading...' : 'Upload Avatar' }}
                <input type="file" class="hidden" accept="image/*" :disabled="uploadingAvatar" @change="handleAvatarUpload" />
              </label>
            </div>
          </div>
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Bio</label>
          <textarea v-model="form.bio" rows="4" :disabled="!isEditing" class="mt-1 w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded resize-none disabled:opacity-70"></textarea>
          <p v-if="validationErrors.bio" class="text-xs text-red-500 mt-1">{{ validationErrors.bio[0] }}</p>
        </div>
        <div v-if="isEditing" class="flex justify-end">
          <button class="flex items-center gap-2 px-6 py-2 bg-primary text-white rounded-lg text-sm font-bold disabled:opacity-60" :disabled="saving" @click="saveProfile">
            <Save class="size-4" />
            {{ saving ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm text-center">
        <div class="text-2xl font-black text-slate-900">{{ summary.total_present_today }}</div>
        <div class="text-[10px] font-bold text-slate-400 uppercase">Present Today</div>
      </div>
      <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm text-center">
        <div class="text-2xl font-black text-slate-900">{{ summary.total_absent_today }}</div>
        <div class="text-[10px] font-bold text-slate-400 uppercase">Absent Today</div>
      </div>
      <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm text-center">
        <div class="text-2xl font-black text-slate-900">{{ summary.total_late_today }}</div>
        <div class="text-[10px] font-bold text-slate-400 uppercase">Late Today</div>
      </div>
    </div>
  </div>
</template>
