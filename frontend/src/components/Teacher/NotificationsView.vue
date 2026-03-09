<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Bell, CheckCircle2, Clock, MoreHorizontal } from 'lucide-vue-next'
import { teacherService } from '../../services/teacherService'

const notifications = ref<any[]>([])
const loading = ref(false)
const errorMessage = ref('')

const loadNotifications = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const data = await teacherService.getNotifications()
    notifications.value = Array.isArray(data) ? data : []
  } catch (error: any) {
    errorMessage.value = error.message || 'Failed to load notifications.'
  } finally {
    loading.value = false
  }
}

const markAsRead = (id: number) => {
  const n = notifications.value.find((notif) => notif.id === id)
  if (n) n.unread = false
}

const deleteNotification = (id: number) => {
  notifications.value = notifications.value.filter((n) => n.id !== id)
}

onMounted(loadNotifications)
</script>

<template>
  <div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-3xl font-black tracking-tight text-slate-900">Notifications</h2>
        <p class="text-slate-500 font-medium">Teacher activity updates from backend</p>
      </div>
      <button class="text-xs font-bold text-primary hover:underline" @click="loadNotifications">Refresh</button>
    </div>

    <p v-if="errorMessage" class="p-3 rounded-lg bg-rose-50 text-rose-700 text-sm">{{ errorMessage }}</p>
    <div v-if="loading" class="text-sm text-slate-500">Loading notifications...</div>

    <div class="space-y-3">
      <div
        v-for="n in notifications"
        :key="n.id"
        :class="`bg-white p-5 rounded-2xl border transition-all group relative ${
          n.unread ? 'border-primary/20 bg-primary/[0.01] shadow-md shadow-primary/5' : 'border-slate-200 opacity-80'
        }`"
      >
        <div v-if="n.unread" class="absolute left-0 top-0 bottom-0 w-1 bg-primary rounded-l-2xl" />

        <div class="flex gap-4">
          <div class="size-12 rounded-xl flex items-center justify-center shrink-0 bg-slate-100 text-slate-600">
            <Clock v-if="n.type === 'reminder'" :size="20" />
            <CheckCircle2 v-else-if="n.type === 'success'" :size="20" />
            <Bell v-else :size="20" />
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex justify-between items-start mb-1">
              <h4 class="font-bold text-slate-900">{{ n.title }}</h4>
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ n.time }}</span>
            </div>
            <p class="text-sm text-slate-600 leading-relaxed mb-3">{{ n.message }}</p>

            <div class="flex items-center gap-3">
              <button
                v-if="n.unread"
                @click="markAsRead(n.id)"
                class="text-[10px] font-black text-primary uppercase tracking-widest hover:underline"
              >
                Mark as read
              </button>
              <button
                @click="deleteNotification(n.id)"
                class="text-[10px] font-black text-red-500 uppercase tracking-widest hover:underline opacity-0 group-hover:opacity-100 transition-opacity"
              >
                Delete
              </button>
            </div>
          </div>

          <button class="p-1 text-slate-300 hover:text-slate-600">
            <MoreHorizontal :size="18" />
          </button>
        </div>
      </div>

      <div v-if="!loading && notifications.length === 0" class="py-20 text-center space-y-4">
        <div class="size-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto text-slate-200">
          <Bell :size="40" />
        </div>
        <p class="text-slate-400 font-medium">You're all caught up!</p>
      </div>
    </div>
  </div>
</template>
