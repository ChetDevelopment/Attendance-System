<script setup lang="ts">
import DashboardStudent from './DashboardStudent.vue';
<script setup>
import { onMounted, ref } from 'vue'
import api from '../../services/api'

const user = JSON.parse(localStorage.getItem('user_data') || '{}');
const activities = [
  { date: 'Today', session: 'Advanced Web Dev', status: 'Present', time: '08:00 AM' },
  { date: 'Yesterday', session: 'Database Systems', status: 'Present', time: '01:30 PM' },
  { date: 'Yesterday', session: 'English for IT', status: 'Late', time: '08:05 AM' },
];
</script>

<template>
  <DashboardStudent />
</template>
