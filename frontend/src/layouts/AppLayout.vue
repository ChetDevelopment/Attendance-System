<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'
import { clearToken } from '../services/auth'

const router = useRouter()
const user = ref(null)

const fetchUser = async () => {
  try {
    const { data } = await api.get('/auth/me')
    user.value = data.user
  } catch (error) {
    clearToken()
    router.push({ name: 'login' })
  }
}

const logout = async () => {
  try {
    await api.post('/auth/logout')
  } finally {
    clearToken()
    router.push({ name: 'login' })
  }
}

onMounted(fetchUser)
</script>

<template>
  <div class="app-shell">
    <aside class="sidebar">
      <div class="brand">Attendance</div>
      <nav class="menu">
        <router-link to="/dashboard">Dashboard</router-link>
      </nav>
    </aside>

    <div class="content-wrap">
      <header class="navbar">
        <div>Welcome, {{ user?.name || 'User' }}</div>
        <button class="btn btn-outline" @click="logout">Logout</button>
      </header>

      <main class="content">
        <router-view />
      </main>
    </div>
  </div>
</template>
