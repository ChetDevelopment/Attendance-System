<script setup>
import { reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import api from '../services/api'
import { setToken } from '../services/auth'

const router = useRouter()
const loading = ref(false)
const errorMessage = ref('')

const form = reactive({
  email: '',
  password: '',
})

const submit = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const { data } = await api.post('/auth/login', form)
    setToken(data.token)
    router.push({ name: 'dashboard' })
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Login failed.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-page">
    <form class="card form" @submit.prevent="submit">
      <h1>Sign In</h1>

      <label>Email</label>
      <input v-model="form.email" type="email" required />

      <label>Password</label>
      <input v-model="form.password" type="password" required />

      <p v-if="errorMessage" class="error">{{ errorMessage }}</p>

      <button class="btn" :disabled="loading">{{ loading ? 'Signing in...' : 'Login' }}</button>

      <p class="switch-text">
        No account?
        <RouterLink to="/register">Register</RouterLink>
      </p>
    </form>
  </div>
</template>
