<script setup>
import { reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import api from '../services/api'
import { setToken } from '../services/auth'

const router = useRouter()
const loading = ref(false)
const errorMessage = ref('')

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const submit = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const { data } = await api.post('/auth/register', form)
    setToken(data.token)
    router.push({ name: 'dashboard' })
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Registration failed.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-page">
    <form class="card form" @submit.prevent="submit">
      <h1>Create Account</h1>

      <label>Name</label>
      <input v-model="form.name" type="text" required />

      <label>Email</label>
      <input v-model="form.email" type="email" required />

      <label>Password</label>
      <input v-model="form.password" type="password" minlength="8" required />

      <label>Confirm Password</label>
      <input v-model="form.password_confirmation" type="password" minlength="8" required />

      <p v-if="errorMessage" class="error">{{ errorMessage }}</p>

      <button class="btn" :disabled="loading">
        {{ loading ? 'Creating account...' : 'Register' }}
      </button>

      <p class="switch-text">
        Already have an account?
        <RouterLink to="/login">Login</RouterLink>
      </p>
    </form>
  </div>
</template>
