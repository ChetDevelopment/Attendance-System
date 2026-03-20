<template>
  <StudentLayout v-if="route.meta?.layout === 'StudentLayout'">
    <router-view />
  </StudentLayout>
  <router-view v-else />
</template>

<script setup>
import { useRoute } from 'vue-router'
import StudentLayout from './components/Student/StudentLayout.vue'

const route = useRoute()
</script>
