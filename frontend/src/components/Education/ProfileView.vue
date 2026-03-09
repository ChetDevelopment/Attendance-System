<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { UserCircle, Mail, Phone, Briefcase, Save, Loader2 } from 'lucide-vue-next';
import api from '../../services/api';

const user = ref<any>(null);
const isEditing = ref(false);
const formData = ref<any>(null);
const isSaving = ref(false);

const fetchProfile = async () => {
  const { data } = await api.get('/user/profile');
  user.value = data;
  formData.value = { ...data };
};

const handleSave = async () => {
  isSaving.value = true;
  try {
    const { status } = await api.post('/user/profile', formData.value);
    if (status >= 200 && status < 300) {
      user.value = { ...formData.value };
      isEditing.value = false;
      alert('Profile updated successfully!');
    }
  } catch (err) {
    console.error(err);
  } finally {
    isSaving.value = false;
  }
};

onMounted(fetchProfile);
</script>

<template>
  <div class="max-w-4xl mx-auto space-y-8">
    <div v-if="!user" class="flex items-center justify-center h-64">
      <Loader2 class="animate-spin text-[#135bec]" />
    </div>
    
    <div v-else class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="h-32 bg-gradient-to-r from-[#135bec] to-blue-400"></div>
      <div class="px-8 pb-8">
        <div class="relative -mt-16 mb-6">
          <div class="size-32 rounded-3xl bg-white p-1 shadow-xl group relative overflow-hidden">
            <img 
              v-if="formData?.avatar_url"
              :src="formData.avatar_url" 
              alt="Avatar" 
              class="size-full rounded-2xl object-cover"
              referrerPolicy="no-referrer"
            />
            <div v-else class="size-full rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400">
              <UserCircle :size="64" />
            </div>
            <div v-if="isEditing" class="absolute inset-0 bg-black/40 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
              <p class="text-[10px] font-bold text-white uppercase tracking-wider">Change Photo</p>
            </div>
          </div>
        </div>

        <div class="flex justify-between items-start">
          <div>
            <div v-if="isEditing" class="space-y-2">
              <input 
                type="text" 
                v-model="formData.name" 
                placeholder="Full Name"
                class="text-2xl font-bold text-slate-900 bg-slate-50 border border-slate-200 rounded-xl px-3 py-1 outline-none focus:ring-2 focus:ring-[#135bec]/20"
              />
              <input 
                type="text" 
                v-model="formData.role" 
                placeholder="Role"
                class="block text-slate-500 font-medium bg-slate-50 border border-slate-200 rounded-xl px-3 py-1 text-sm outline-none focus:ring-2 focus:ring-[#135bec]/20"
              />
            </div>
            <div v-else>
              <h2 class="text-2xl font-bold text-slate-900">{{ user.name }}</h2>
              <p class="text-slate-500 font-medium">{{ user.role }}</p>
            </div>
          </div>
          <button 
            @click="isEditing ? handleSave() : isEditing = true"
            class="px-6 py-2.5 bg-[#135bec] text-white text-sm font-bold rounded-xl hover:bg-[#135bec]/90 transition-all flex items-center gap-2"
            :disabled="isSaving"
          >
            <Loader2 v-if="isSaving" :size="18" class="animate-spin" />
            <Save v-else-if="isEditing" :size="18" />
            <span v-if="!isSaving">{{ isEditing ? 'Save Changes' : 'Edit Profile' }}</span>
          </button>
        </div>

        <div v-if="isEditing" class="mt-6 p-4 bg-blue-50 rounded-2xl border border-blue-100">
          <label class="block text-xs font-bold text-blue-600 uppercase tracking-wider mb-2">Avatar URL</label>
          <input 
            type="text" 
            v-model="formData.avatar_url" 
            placeholder="https://example.com/photo.jpg"
            class="w-full bg-white border border-blue-200 rounded-xl px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-[#135bec]/20"
          />
          <p class="text-[10px] text-blue-400 mt-2 italic">Paste a link to an image to update your profile picture.</p>
        </div>

        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-8">
          <div class="space-y-6">
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Full Name</label>
              <input 
                v-if="isEditing"
                type="text" 
                v-model="formData.name" 
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-[#135bec]/20"
              />
              <div v-else class="flex items-center gap-3 text-slate-700">
                <UserCircle :size="18" class="text-slate-400" />
                <span class="text-sm font-medium">{{ user.name }}</span>
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Email Address</label>
              <input 
                v-if="isEditing"
                type="email" 
                v-model="formData.email" 
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-[#135bec]/20"
              />
              <div v-else class="flex items-center gap-3 text-slate-700">
                <Mail :size="18" class="text-slate-400" />
                <span class="text-sm font-medium">{{ user.email }}</span>
              </div>
            </div>
          </div>

          <div class="space-y-6">
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Phone Number</label>
              <input 
                v-if="isEditing"
                type="text" 
                v-model="formData.phone" 
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-[#135bec]/20"
              />
              <div v-else class="flex items-center gap-3 text-slate-700">
                <Phone :size="18" class="text-slate-400" />
                <span class="text-sm font-medium">{{ user.phone }}</span>
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Bio</label>
              <textarea 
                v-if="isEditing"
                v-model="formData.bio" 
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-[#135bec]/20 h-24 resize-none"
              />
              <div v-else class="flex items-start gap-3 text-slate-700">
                <Briefcase :size="18" class="text-slate-400 mt-0.5" />
                <p class="text-sm font-medium leading-relaxed">{{ user.bio }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
