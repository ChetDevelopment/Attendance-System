<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'

const attendances = ref([])
const loading = ref(true)
const user = ref(null)
const error = ref(null)

const fetchAttendances = async () => {
  try {
    const { data } = await api.get('/attendances')
    attendances.value = Array.isArray(data) ? data : []
  } catch (err) {
    console.error('Failed to fetch attendances:', err)
    error.value = 'Failed to load attendance records'
  } finally {
    loading.value = false
  }
}

const fetchUser = async () => {
  try {
    const { data } = await api.get('/user/profile')
    user.value = data
  } catch (err) {
    console.error('Failed to fetch user profile:', err)
    // Don't set error for user fetch as it's not critical
  }
}

onMounted(() => {
  fetchAttendances()
  fetchUser()
})
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-8">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-gray-600 mt-1">Welcome to your attendance management system</p>
          </div>
          <div v-if="user" class="flex items-center gap-3 bg-gray-50 rounded-lg p-3">
            <div class="w-12 h-12 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold">
              {{ user.name?.charAt(0) || 'U' }}
            </div>
            <div>
              <p class="font-semibold text-gray-900">{{ user.name || 'User' }}</p>
              <p class="text-sm text-gray-500">{{ user.email }}</p>
            </div>
          </div>
        </div>
        
        <div class="border-t border-gray-200 pt-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Attendance Records</h2>
          
          <div v-if="loading" class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="ml-3 text-gray-600">Loading attendance...</span>
          </div>
          
          <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
            <p class="text-red-600">{{ error }}</p>
            <button @click="fetchAttendances" class="mt-2 text-sm text-red-600 hover:text-red-700 underline">
              Try again
            </button>
          </div>
          
          <div v-else-if="attendances.length === 0" class="text-center py-8 text-gray-500">
            <div class="text-4xl mb-2">📊</div>
            <p>No attendance records found</p>
            <p class="text-sm mt-1">Records will appear here once attendance is taken</p>
          </div>
          
          <div v-else class="space-y-3">
            <div 
              v-for="item in attendances" 
              :key="item.id" 
              class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
            >
              <div>
                <p class="font-medium text-gray-900">{{ item.date || 'Unknown date' }}</p>
                <p class="text-sm text-gray-500">{{ item.student?.name || 'Unknown student' }}</p>
              </div>
              <span 
                class="px-3 py-1 rounded-full text-sm font-medium"
                :class="{
                  'bg-green-100 text-green-800': item.status === 'present',
                  'bg-red-100 text-red-800': item.status === 'absent',
                  'bg-yellow-100 text-yellow-800': item.status === 'late',
                  'bg-gray-100 text-gray-800': !['present', 'absent', 'late'].includes(item.status)
                }"
              >
                {{ item.status || 'Unknown' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
