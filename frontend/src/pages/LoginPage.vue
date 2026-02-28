<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { User, Lock, Eye, EyeOff, ArrowRight, HelpCircle } from 'lucide-vue-next';

const showPassword = ref(false);
const isLoading = ref(false);
const email = ref('');
const password = ref('');

const handleLogin = () => {
  isLoading.value = true;
  // Simulate login
  setTimeout(() => {
    isLoading.value = false;
  }, 1500);
};

const isMounted = ref(false);
onMounted(() => {
  isMounted.value = true;
});
</script>

<template>
  <div class="min-h-screen flex flex-col md:flex-row font-sans">
    <!-- Left Side: Hero Section -->
    <div class="relative w-full md:w-3/5 h-64 md:h-screen overflow-hidden">
      <!-- Background Image -->
      <img
        src="https://images.unsplash.com/photo-1562774053-701939374585?q=80&w=1986&auto=format&fit=crop"
        alt="PNC Campus"
        class="absolute inset-0 w-full h-full object-cover"
        referrerPolicy="no-referrer"
      />
      <!-- Overlay Gradient -->
      <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent" />
      
      <!-- Top Left Badge -->
      <div class="absolute top-6 left-6">
        <div class="flex items-center gap-2 bg-black/40 backdrop-blur-md border border-white/20 px-3 py-1 rounded-full">
          <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
          <span class="text-[10px] font-bold tracking-widest uppercase text-white">System Online</span>
        </div>
      </div>

      <!-- Content Overlay -->
      <div class="absolute bottom-12 left-12 right-12 text-white">
        <Transition
          appear
          enter-active-class="transition duration-600 ease-out"
          enter-from-class="transform translate-y-5 opacity-0"
          enter-to-class="transform translate-y-0 opacity-100"
        >
          <div v-if="isMounted" class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center shadow-xl">
              <span class="text-blue-600 font-bold text-xl">PN</span>
            </div>
            <div class="h-8 w-[1px] bg-white/30" />
            <span class="text-lg font-medium tracking-tight">Passerelles Numériques Cambodia</span>
          </div>
        </Transition>

        <Transition
          appear
          enter-active-class="transition duration-600 delay-100 ease-out"
          enter-from-class="transform translate-y-5 opacity-0"
          enter-to-class="transform translate-y-0 opacity-100"
        >
          <div v-if="isMounted">
            <h2 class="font-khmer text-3xl md:text-4xl font-bold mb-1 text-white/90">វត្តមាន</h2>
            <h1 class="text-5xl md:text-7xl font-bold mb-6 tracking-tight text-blue-500">
              Attendance <br /> Management
            </h1>
            <p class="text-lg md:text-xl text-slate-300 max-w-xl leading-relaxed font-light">
              Streamlining student session tracking and reporting for the next generation of IT professionals in Cambodia.
            </p>
          </div>
        </Transition>
      </div>
    </div>

    <!-- Right Side: Login Form -->
    <div class="w-full md:w-2/5 bg-[#0d1117] flex flex-col justify-center items-center p-8 md:p-16">
      <Transition
        appear
        enter-active-class="transition duration-600 ease-out"
        enter-from-class="transform translate-x-5 opacity-0"
        enter-to-class="transform translate-x-0 opacity-100"
      >
        <div v-if="isMounted" class="w-full max-w-md">
          <div class="mb-10">
            <h2 class="text-3xl font-bold text-white mb-2">Login to your account</h2>
            <p class="text-slate-400">Welcome back! Please enter your credentials to access the portal.</p>
          </div>

          <form @submit.prevent="handleLogin" class="space-y-6">
            <div class="space-y-2">
              <label class="text-sm font-medium text-slate-300 block">Email or Username</label>
              <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500 group-focus-within:text-blue-500 transition-colors">
                  <User :size="18" />
                </div>
                <input 
                  v-model="email"
                  type="text" 
                  class="input-field pl-10" 
                  placeholder="pnc.student@pnc.org"
                  required
                />
              </div>
            </div>

            <div class="space-y-2">
              <div class="flex justify-between items-center">
                <label class="text-sm font-medium text-slate-300">Password</label>
                <button type="button" class="text-xs font-semibold text-blue-500 hover:text-blue-400 transition-colors">
                  Forgot password?
                </button>
              </div>
              <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500 group-focus-within:text-blue-500 transition-colors">
                  <Lock :size="18" />
                </div>
                <input 
                  v-model="password"
                  :type="showPassword ? 'text' : 'password'" 
                  class="input-field pl-10 pr-10" 
                  placeholder="••••••••"
                  required
                />
                <button 
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-500 hover:text-slate-300 transition-colors"
                >
                  <EyeOff v-if="showPassword" :size="18" />
                  <Eye v-else :size="18" />
                </button>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <input 
                type="checkbox" 
                id="remember" 
                class="w-4 h-4 rounded border-[#30363d] bg-[#161b22] text-blue-600 focus:ring-blue-500 focus:ring-offset-[#0d1117]" 
              />
              <label htmlFor="remember" class="text-sm text-slate-400 cursor-pointer select-none">
                Keep me logged in for 30 days
              </label>
            </div>

            <button 
              type="submit" 
              :disabled="isLoading"
              class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3.5 rounded-lg flex items-center justify-center gap-2 transition-all shadow-lg shadow-blue-900/20 disabled:opacity-70 disabled:cursor-not-allowed group"
            >
              <div v-if="isLoading" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
              <template v-else>
                Log In to System
                <ArrowRight :size="18" class="group-hover:translate-x-1 transition-transform" />
              </template>
            </button>
          </form>

          <div class="mt-12 pt-8 border-t border-[#30363d]">
            <p class="text-center text-[10px] font-bold tracking-widest uppercase text-slate-500 mb-6">Help & Support</p>
            <button class="w-full bg-[#21262d] hover:bg-[#30363d] text-slate-200 font-medium py-3 rounded-lg flex items-center justify-center gap-2 transition-all border border-[#30363d]">
              <HelpCircle :size="18" />
              Contact IT Support
            </button>
          </div>

          <div class="mt-16 text-center">
            <p class="text-xs text-slate-500 mb-4">
              © 2026 Passerelles Numériques Cambodia. All rights reserved.
            </p>
            <!-- <div class="flex justify-center gap-4 text-[10px] font-bold tracking-wider text-slate-600">
              <a href="#" class="hover:text-slate-400 transition-colors uppercase">Privacy Policy</a>
              <span class="text-slate-800">•</span>
              <a href="#" class="hover:text-slate-400 transition-colors uppercase">Terms of Service</a>
            </div> -->
          </div>
        </div>
      </Transition>
    </div>
  </div>
</template>
