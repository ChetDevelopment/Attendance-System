<script setup>
import { onMounted, ref } from 'vue'
import api from '../../services/api'

const attendances = ref([])
const loading = ref(true)

const fetchAttendances = async () => {
  try {
    const { data } = await api.get('/attendances')
    attendances.value = data
  } finally {
    loading.value = false
  }
}

onMounted(fetchAttendances)
</script>

<template>
  <section>
    <h1 class="page-title">Dashboard</h1>
    <div class="card">
      <h2 class="section-title">Recent Attendance Records</h2>
      <p v-if="loading">Loading attendance...</p>
      <ul v-else-if="attendances.length">
        <li v-for="item in attendances" :key="item.id" class="attendance-item">
          <span>{{ item.date }}</span>
          <span class="badge">{{ item.status }}</span>
        </li>
      </ul>
      <p v-else>No attendance records yet.</p>
    </div>
  </section>
</template>
