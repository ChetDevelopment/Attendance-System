  <script setup>
  import { reactive, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { User, Lock, Eye, EyeOff, ArrowRight, HelpCircle } from 'lucide-vue-next'
  import api from '../services/api'
  import {
    clearStudentSession,
    setToken,
    setUser,
    setUserRole,
    resolveUserRole,
  } from '../services/auth'

  const router = useRouter()
  const loading = ref(false)
  const showPassword = ref(false)
  const errorMessage = ref('')

  const form = reactive({
    email: '',
    password: '',
    remember: false,
  })

  const submit = async () => {
    loading.value = true
    errorMessage.value = ''

    try {
      const payload = {
        email: form.email,
        password: form.password,
      }

      const { data } = await api.post('/auth/login', payload)
      const role = resolveUserRole(data.user || data)

      clearStudentSession()
      setToken(data.token)
      setUser(data.user)
      setUserRole(role)

      router.push({
        name:
          role === 'teacher'
            ? 'teacher-dashboard'
            : role === 'education'
              ? 'education-dashboard'
              : role === 'student'
                ? 'student-dashboard'
                : 'dashboard',
      })
    } catch (error) {
      errorMessage.value = error.response?.data?.message || error.message || 'Login failed.'
    } finally {
      loading.value = false
    }
  }
  </script>

  <template>
    <div class="login-page flex min-h-[100dvh] w-full flex-col lg:flex-row bg-gradient-to-br from-blue-900 via-[#101922] to-slate-900 text-slate-100">
      <div
        class="relative hidden lg:flex lg:w-1/2 xl:w-3/5 bg-cover bg-center overflow-hidden lg:min-h-[100dvh]"
        style="background-image: url('/PictureUseInPageLogin.png')"
      >
        <div class="absolute inset-0 bg-gradient-to-t from-[#101922]/95 via-[#101922]/45 to-[#101922]/20"></div>

        <div class="relative z-10 flex flex-col justify-end p-8 xl:p-12 w-full h-full">
          <div class="max-w-xl">
            <div class="flex items-center gap-3 mb-6">
              <div class="h-10 w-auto bg-white p-1.5 rounded-lg flex items-center justify-center">
                <img
                  src="https://www.passerellesnumeriques.org/wp-content/uploads/2024/05/PN-Logo-English-Blue-Baseline.png"
                  alt="PNC Logo"
                  class="h-full w-auto object-contain"
                  referrerpolicy="no-referrer"
                />
              </div>
              <span class="text-2xl font-bold tracking-tight text-white">Passerelles Numeriques Cambodia</span>
            </div>
            <h1 class="text-4xl xl:text-5xl font-black text-white leading-tight mb-4">
              Attendance
              <br />
              <span class="text-primary">Management System</span>
            </h1>
            <p class="text-lg text-slate-300 font-medium">
              Streamlining session tracking and reporting for students, teachers, and education teams.
            </p>
          </div>
        </div>

        <div class="absolute top-8 left-8 flex items-center gap-2 bg-black/30 backdrop-blur-md px-4 py-2 rounded-full border border-white/10">
          <span class="size-2 bg-green-500 rounded-full animate-pulse"></span>
          <span class="text-xs font-semibold text-white uppercase tracking-wider">System Online</span>
        </div>
      </div>

      <div class="login-panel relative flex flex-1 flex-col items-center justify-center p-5 md:p-8 lg:p-8 xl:p-10 bg-gradient-to-br from-slate-800 via-[#101922] to-blue-950 overflow-hidden">
        <div class="absolute inset-0 lg:hidden bg-cover bg-center opacity-30" style="background-image: url('/PictureUseInPageLogin.png')"></div>
        <div class="absolute inset-0 lg:hidden bg-[#101922]/75"></div>

        <div class="login-card relative z-10 w-full max-w-[440px] space-y-6 py-4 lg:py-6">
          <div class="lg:hidden flex flex-col items-center text-center mb-6">
            <div class="h-16 w-auto bg-white/10 p-3 rounded-2xl mb-4 flex items-center justify-center">
              <img
                src="https://www.passerellesnumeriques.org/wp-content/uploads/2024/05/PN-Logo-English-Blue-Baseline.png"
                alt="PNC Logo"
                class="h-full w-auto object-contain"
                referrerpolicy="no-referrer"
              />
            </div>
            <h1 class="text-3xl font-black text-white">Attendance</h1>
            <p class="text-slate-400 mt-2">Management System</p>
          </div>

          <div class="login-head space-y-2">
            <h2 class="text-2xl font-bold text-white lg:block hidden">Login to your account</h2>
            <p class="text-slate-400">Enter your credentials to access the portal.</p>
          </div>

          <form @submit.prevent="submit" class="login-form space-y-5">
            <div class="space-y-2">
              <label class="text-sm font-semibold text-slate-300 ml-1" for="username">
                Email
              </label>
              <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-primary transition-colors">
                  <User :size="20" />
                </div>
                <input
                  id="username"
                  v-model="form.email"
                  type="email"
                  required
                  placeholder="you@example.com"
                  class="form-input block w-full pl-11 pr-4 py-3.5 bg-[#1c2127] border border-[#3b4754] rounded-xl text-white placeholder:text-[#9dabb9] focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none"
                />
              </div>
            </div>

            <div class="space-y-2">
              <div class="flex justify-between items-center px-1">
                <label class="text-sm font-semibold text-slate-300" for="password">Password</label>
              </div>
              <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-primary transition-colors">
                  <Lock :size="20" />
                </div>
                <input
                  id="password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  required
                  placeholder="********"
                  class="form-input block w-full pl-11 pr-12 py-3.5 bg-[#1c2127] border border-[#3b4754] rounded-xl text-white placeholder:text-[#9dabb9] focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none"
                />
                <button
                  type="button"
                  class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-200"
                  @click="showPassword = !showPassword"
                >
                  <EyeOff v-if="showPassword" :size="20" />
                  <Eye v-else :size="20" />
                </button>
              </div>
            </div>

            <div class="flex items-center space-x-3 px-1">
              <input
                id="remember"
                v-model="form.remember"
                type="checkbox"
                class="size-5 rounded border-slate-700 text-primary focus:ring-primary focus:ring-offset-[#101922] bg-[#1c2127]"
              />
              <label class="text-sm font-medium text-slate-400 cursor-pointer" for="remember">
                Keep me logged in for 30 days
              </label>
            </div>

            <p v-if="errorMessage" class="text-sm font-medium text-red-400">{{ errorMessage }}</p>

            <button
              type="submit"
              :disabled="loading"
              class="login-submit w-full flex items-center justify-center gap-2 py-4 px-6 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl shadow-lg shadow-primary/20 transition-all disabled:opacity-70 disabled:cursor-not-allowed"
            >
              <span>{{ loading ? 'Signing in...' : 'Log In to System' }}</span>
              <ArrowRight :size="18" />
            </button>
          </form>

          <div class="support-section relative py-4">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-slate-800"></div>
            </div>
            <div class="relative flex justify-center text-xs uppercase">
              <span class="bg-[#101922] px-4 text-slate-500 font-semibold tracking-widest">Help &amp; Support</span>
            </div>
          </div>

          <div class="flex flex-col gap-3">
            <a
              href="https://t.me/Sopheak_CHIM"
              target="_blank"
              rel="noopener noreferrer"
              class="flex items-center justify-center gap-3 w-full py-3 px-4 border border-slate-800 rounded-xl bg-[#1c2127] hover:bg-slate-800/50 transition-colors text-slate-300 font-medium no-underline"
              style="color: #cbd5e1; text-decoration: none;"
            >
              <svg
                viewBox="0 0 24 24"
                fill="currentColor"
                style="width: 20px; height: 20px; flex: none; display: block;"
              >
                <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.161c-.18 1.897-.962 6.502-1.359 8.627-.168.9-.5 1.201-.82 1.23-.696.064-1.225-.46-1.901-.903-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.751-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
              </svg>
              <span>Contact IT Support</span>
            </a>
          </div>

          <footer class="login-footer pt-4 pb-6 flex flex-col items-center gap-4 text-center">
            <p class="text-xs text-slate-500 font-medium">
              (c) {{ new Date().getFullYear() }} Passerelles Numeriques Cambodia. All rights reserved.
            </p>
            <div class="flex gap-4 text-xs font-bold text-slate-600 uppercase tracking-tighter">
              <a class="hover:text-primary transition-colors" href="#">Privacy Policy</a>
              <span>|</span>
              <a class="hover:text-primary transition-colors" href="#">Terms of Service</a>
            </div>
          </footer>
        </div>
      </div>
    </div>
  </template>

  <style scoped>
  @media (min-width: 1024px) and (max-height: 860px) {
    .login-card {
      max-width: 410px;
      gap: 0.85rem;
      padding-top: 0.5rem;
      padding-bottom: 0.5rem;
    }

    .login-head h2 {
      font-size: 1.35rem;
      line-height: 1.3;
    }

    .login-head p {
      font-size: 0.85rem;
    }

    .login-form {
      gap: 0.8rem;
    }

    .form-input {
      padding-top: 0.6rem;
      padding-bottom: 0.6rem;
    }

    .login-submit {
      padding-top: 0.75rem;
      padding-bottom: 0.75rem;
    }

    .support-section {
      padding-top: 0.35rem;
      padding-bottom: 0.35rem;
    }

    .login-footer {
      padding-top: 0.2rem;
      padding-bottom: 0.2rem;
      gap: 0.45rem;
    }

    .login-footer p,
    .login-footer a,
    .login-footer span {
      font-size: 0.65rem;
    }
  }
  </style>
