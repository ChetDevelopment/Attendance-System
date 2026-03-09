<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { 
  User as UserIcon, 
  Lock as LockIcon, 
  Mail as MailIcon,
  ArrowRight as ArrowRightIcon,
  Loader2 as Loader2Icon
} from 'lucide-vue-next';

const router = useRouter();
const name = ref('');
const email = ref('');
const password = ref('');
const isLoading = ref(false);

const handleRegister = async () => {
  isLoading.value = true;
  // Mock registration
  await new Promise(resolve => setTimeout(resolve, 1500));
  isLoading.value = false;
  router.push('/login');
};
</script>

<template>
  <div class="min-h-screen bg-pnc-dark flex items-center justify-center px-6">
    <div class="max-w-md w-full">
      <div class="text-center mb-10">
        <h1 class="text-4xl font-bold text-white mb-2">Create Account</h1>
        <p class="text-slate-400">Join the PNC Attendance System</p>
      </div>

      <form @submit.prevent="handleRegister" class="space-y-6">
        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-300">Full Name</label>
          <div class="relative">
            <UserIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
            <input 
              v-model="name"
              type="text" 
              required
              class="w-full pl-12 pr-4 py-3.5 bg-slate-900/50 border border-slate-800 rounded-xl text-white focus:ring-2 focus:ring-pnc-blue/50 focus:border-pnc-blue outline-none transition-all"
              placeholder="John Doe"
            />
          </div>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-300">Email Address</label>
          <div class="relative">
            <MailIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
            <input 
              v-model="email"
              type="email" 
              required
              class="w-full pl-12 pr-4 py-3.5 bg-slate-900/50 border border-slate-800 rounded-xl text-white focus:ring-2 focus:ring-pnc-blue/50 focus:border-pnc-blue outline-none transition-all"
              placeholder="pnc.student@pnc.org"
            />
          </div>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-300">Password</label>
          <div class="relative">
            <LockIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
            <input 
              v-model="password"
              type="password" 
              required
              class="w-full pl-12 pr-4 py-3.5 bg-slate-900/50 border border-slate-800 rounded-xl text-white focus:ring-2 focus:ring-pnc-blue/50 focus:border-pnc-blue outline-none transition-all"
              placeholder="••••••••"
            />
          </div>
        </div>

        <button 
          type="submit"
          :disabled="isLoading"
          class="w-full flex items-center justify-center gap-2 py-4 bg-pnc-blue hover:bg-blue-600 disabled:bg-slate-800 disabled:text-slate-500 text-white font-bold rounded-xl shadow-lg transition-all"
        >
          <Loader2Icon v-if="isLoading" class="h-5 w-5 animate-spin" />
          <template v-else>
            Register Now
            <ArrowRightIcon class="h-5 w-5" />
          </template>
        </button>
      </form>

      <p class="mt-8 text-center text-slate-400">
        Already have an account? 
        <router-link to="/login" class="text-pnc-blue font-bold hover:underline">Log In</router-link>
      </p>
    </div>
  </div>
</template>
