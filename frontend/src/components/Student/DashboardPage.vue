<<<<<<< HEAD:frontend/src/components/Student/DashboardPage.vue
<script setup>
import { onMounted, ref } from 'vue'
import api from '../../services/api'
=======
<script setup lang="ts">
import AppLayout from '../layouts/AppLayout.vue';
import { CheckCircle2 as CheckCircle2Icon, AlertCircle as AlertCircleIcon } from 'lucide-vue-next';
>>>>>>> feature/login:frontend/src/pages/DashboardPage.vue

const user = JSON.parse(localStorage.getItem('user_data') || '{}');
const activities = [
  { date: 'Today', session: 'Advanced Web Dev', status: 'Present', time: '08:00 AM' },
  { date: 'Yesterday', session: 'Database Systems', status: 'Present', time: '01:30 PM' },
  { date: 'Yesterday', session: 'English for IT', status: 'Late', time: '08:05 AM' },
];
</script>

<template>
  <AppLayout title="Student Overview" :user="user">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl">
        <p class="text-slate-500 text-sm font-medium mb-1">Attendance Rate</p>
        <h4 class="text-3xl font-bold text-white">98.5%</h4>
        <div class="mt-4 h-2 bg-slate-800 rounded-full overflow-hidden">
          <div class="h-full bg-emerald-500" style="width: 98.5%" />
        </div>
      </div>
      <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl">
        <p class="text-slate-500 text-sm font-medium mb-1">Late Sessions</p>
        <h4 class="text-3xl font-bold text-white">2</h4>
        <p class="text-xs text-slate-600 mt-2">This semester</p>
      </div>
      <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl">
        <p class="text-slate-500 text-sm font-medium mb-1">Excused Absences</p>
        <h4 class="text-3xl font-bold text-white">0</h4>
        <p class="text-xs text-slate-600 mt-2">Perfect record!</p>
      </div>
    </div>

    <div class="mt-8 bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
      <div class="p-6 border-b border-slate-800 flex justify-between items-center">
        <h3 class="text-lg font-bold text-white">Recent Activity</h3>
        <button class="text-sm text-pnc-blue font-medium">View All</button>
      </div>
      <div class="divide-y divide-slate-800">
        <div v-for="(item, i) in activities" :key="i" class="p-6 flex items-center justify-between hover:bg-slate-800/30 transition-colors">
          <div class="flex items-center gap-4">
            <div :class="['p-2 rounded-lg', item.status === 'Present' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-amber-500/10 text-amber-500']">
              <CheckCircle2Icon v-if="item.status === 'Present'" class="h-5 w-5" />
              <AlertCircleIcon v-else class="h-5 w-5" />
            </div>
            <div>
              <p class="text-white font-bold">{{ item.session }}</p>
              <p class="text-xs text-slate-500">{{ item.date }} • {{ item.time }}</p>
            </div>
          </div>
          <span :class="['text-xs font-bold px-3 py-1 rounded-full', item.status === 'Present' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-amber-500/10 text-amber-500']">
            {{ item.status }}
          </span>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
