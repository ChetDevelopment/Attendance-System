<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'

const router = useRouter()
const name = ref('')
const email = ref('')
const password = ref('')
const errorMessage = ref('')
const isSubmitting = ref(false)

const submit = async () => {
  if (!name.value || !email.value || !password.value) return
  errorMessage.value = ''
  isSubmitting.value = true

  try {
    await api.post('/auth/register', {
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: password.value,
      role_id: 1,
    })
    router.push('/login')
  } catch (error) {
    errorMessage.value = error.message || 'Registration failed.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <section class="min-h-screen flex items-center justify-center p-6">
    <form class="w-full max-w-sm space-y-4 bg-slate-900 p-6 rounded-xl border border-slate-800" @submit.prevent="submit">
      <h1 class="text-2xl font-bold">Register</h1>
      <input v-model="name" type="text" placeholder="Name" class="w-full px-3 py-2 rounded bg-slate-800 border border-slate-700" />
      <input v-model="email" type="email" placeholder="Email" class="w-full px-3 py-2 rounded bg-slate-800 border border-slate-700" />
      <input v-model="password" type="password" placeholder="Password" class="w-full px-3 py-2 rounded bg-slate-800 border border-slate-700" />
      <p v-if="errorMessage" class="text-sm text-red-400">{{ errorMessage }}</p>
      <button :disabled="isSubmitting" class="w-full py-2 rounded bg-blue-600 text-white font-semibold disabled:opacity-60">
        {{ isSubmitting ? 'Creating account...' : 'Create account' }}
      </button>
      <router-link to="/login" class="block text-sm text-slate-300">Back to login</router-link>
    </form>
  </section>
</template>
