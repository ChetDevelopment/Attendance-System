<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'
import { setToken } from '../services/auth'

const router = useRouter()
const email = ref('')
const password = ref('')
const errorMessage = ref('')
const isSubmitting = ref(false)

const submit = async () => {
  if (!email.value || !password.value) return
  errorMessage.value = ''
  isSubmitting.value = true

  try {
    const { data } = await api.post('/auth/login', {
      email: email.value,
      password: password.value,
    })

    setToken(data.token)
    localStorage.setItem('user_data', JSON.stringify(data.user || {}))
    router.push('/attendance')
  } catch (error) {
    errorMessage.value = error.message || 'Login failed.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <section class="min-h-screen flex items-center justify-center p-6">
    <form class="w-full max-w-sm space-y-4 bg-slate-900 p-6 rounded-xl border border-slate-800" @submit.prevent="submit">
      <h1 class="text-2xl font-bold">Login</h1>
      <input v-model="email" type="email" placeholder="Email" class="w-full px-3 py-2 rounded bg-slate-800 border border-slate-700" />
      <input v-model="password" type="password" placeholder="Password" class="w-full px-3 py-2 rounded bg-slate-800 border border-slate-700" />
      <p v-if="errorMessage" class="text-sm text-red-400">{{ errorMessage }}</p>
      <button :disabled="isSubmitting" class="w-full py-2 rounded bg-blue-600 text-white font-semibold disabled:opacity-60">
        {{ isSubmitting ? 'Signing in...' : 'Sign in' }}
      </button>
      <router-link to="/register" class="block text-sm text-slate-300">Create account</router-link>
    </form>
  </section>
</template>
