<script setup lang="ts">
import { ref, onMounted } from 'vue';
import DashboardStudent from './DashboardStudent.vue';
import api from '../../services/api';

const todayStats = ref({ present_today: 0, absent_today: 0, late_today: 0 });

const fetchTodayStats = async () => {
  try {
    const { data } = await api.get('/dashboard/today-attendance');
    todayStats.value = data;
  } catch (err) {
    console.error('Failed to fetch today attendance:', err);
  }
};

onMounted(() => {
  fetchTodayStats();
});

// Function to refresh from child
const refreshStats = () => {
  fetchTodayStats();
};
</script>

<template>
  <!-- Pass the refresh function to child components -->
  <DashboardStudent :today-stats="todayStats" :refresh-stats="refreshStats" />
</template>