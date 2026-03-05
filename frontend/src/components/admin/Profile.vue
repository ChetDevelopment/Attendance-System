<script setup lang="ts">
import { ref } from 'vue';
import { User, Camera, Shield, Lock, Save } from 'lucide-vue-next';

const adminData = ref({
  name: 'Admin (Principal)',
  email: 'principal@eduattend.com',
  role: 'Administrator',
  phone: '+1 (555) 000-1234',
  bio: 'Dedicated educator with 15+ years of experience.'
});

const isEditing = ref(false);
</script>

<template>
  <div 
    class="max-w-4xl mx-auto space-y-8"
  >
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Admin Profile</h2>
        <p class="text-sm text-slate-500 font-medium">Manage your personal information and security settings</p>
      </div>
      <button 
        @click="isEditing = !isEditing"
        class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-bold text-slate-600 hover:bg-slate-50 transition-all shadow-sm"
      >
        {{ isEditing ? 'Cancel' : 'Edit Profile' }}
      </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Profile Card -->
      <div class="lg:col-span-1 space-y-6">
        <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm text-center space-y-4">
          <div class="relative inline-block">
            <div class="size-32 bg-indigo-100 rounded-full flex items-center justify-center border-4 border-white shadow-md">
              <User class="size-16 text-indigo-600" />
            </div>
            <button class="absolute bottom-0 right-0 p-2 bg-primary text-white rounded-full shadow-lg hover:scale-110 transition-transform">
              <Camera class="size-4" />
            </button>
          </div>
          <div>
            <h3 class="text-xl font-black text-slate-900">{{ adminData.name }}</h3>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ adminData.role }}</p>
          </div>
          <div class="pt-4 flex justify-center gap-2">
            <span class="px-3 py-1 bg-green-100 text-green-600 text-[10px] font-black rounded-full uppercase">Active</span>
            <span class="px-3 py-1 bg-indigo-100 text-indigo-600 text-[10px] font-black rounded-full uppercase">Verified</span>
          </div>
        </div>

        <div class="bg-slate-900 p-6 rounded-2xl text-white space-y-4 shadow-xl">
          <div class="flex items-center gap-3">
            <Shield class="size-5 text-primary" />
            <h4 class="font-bold">Security Status</h4>
          </div>
          <div class="space-y-3">
            <div class="flex justify-between text-[10px] font-bold">
              <span class="text-slate-400 uppercase">2FA</span>
              <span class="text-green-400">ENABLED</span>
            </div>
            <div class="flex justify-between text-[10px] font-bold">
              <span class="text-slate-400 uppercase">Last Login</span>
              <span>Today, 08:02 AM</span>
            </div>
          </div>
          <button class="w-full py-2 bg-white/10 hover:bg-white/20 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-2">
            <Lock class="size-3" />
            Change Password
          </button>
        </div>
      </div>

      <!-- Details Form -->
      <div class="lg:col-span-2 space-y-6">
        <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm space-y-6">
          <h3 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-4">Personal Information</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-1">
              <label class="text-[10px] font-bold text-slate-500 uppercase">Full Name</label>
              <input 
                type="text" 
                :disabled="!isEditing"
                v-model="adminData.name"
                class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary/20 disabled:opacity-60" 
              />
            </div>
            <div class="space-y-1">
              <label class="text-[10px] font-bold text-slate-500 uppercase">Email Address</label>
              <input 
                type="email" 
                :disabled="!isEditing"
                v-model="adminData.email"
                class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary/20 disabled:opacity-60" 
              />
            </div>
            <div class="space-y-1">
              <label class="text-[10px] font-bold text-slate-500 uppercase">Phone Number</label>
              <input 
                type="text" 
                :disabled="!isEditing"
                v-model="adminData.phone"
                class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary/20 disabled:opacity-60" 
              />
            </div>
            <div class="space-y-1">
              <label class="text-[10px] font-bold text-slate-500 uppercase">Role</label>
              <input 
                type="text" 
                disabled
                v-model="adminData.role"
                class="w-full px-4 py-2 bg-slate-100 border border-slate-200 rounded-xl text-sm outline-none opacity-60" 
              />
            </div>
          </div>

          <div class="space-y-1">
            <label class="text-[10px] font-bold text-slate-500 uppercase">Bio / Description</label>
            <textarea 
              :disabled="!isEditing"
              v-model="adminData.bio"
              rows="4"
              class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary/20 disabled:opacity-60 resize-none"
            />
          </div>

          <div v-if="isEditing" class="pt-4 flex justify-end">
            <button 
              @click="isEditing = false"
              class="flex items-center gap-2 px-8 py-3 bg-primary text-white rounded-xl font-bold text-sm shadow-xl shadow-primary/20 hover:scale-105 transition-all"
            >
              <Save class="size-4" />
              Save Changes
            </button>
          </div>
        </div>

        <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm space-y-6">
          <h3 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-4">Activity Summary</h3>
          <div class="grid grid-cols-3 gap-4">
            <div class="p-4 bg-slate-50 rounded-xl text-center">
              <div class="text-2xl font-black text-slate-900">124</div>
              <div class="text-[9px] font-bold text-slate-400 uppercase">Logins</div>
            </div>
            <div class="p-4 bg-slate-50 rounded-xl text-center">
              <div class="text-2xl font-black text-slate-900">45</div>
              <div class="text-[9px] font-bold text-slate-400 uppercase">Reports</div>
            </div>
            <div class="p-4 bg-slate-50 rounded-xl text-center">
              <div class="text-2xl font-black text-slate-900">12</div>
              <div class="text-[9px] font-bold text-slate-400 uppercase">Alerts</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
