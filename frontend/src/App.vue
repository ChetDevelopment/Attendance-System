<template>
  <component :is="layout">
    <router-view />
  </component>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppLayout from './layouts/AppLayout.vue'
import StudentLayout from './components/Student/StudentLayout.vue'

const route = useRoute()

const layout = computed(() => {
  const layoutName = route.meta?.layout
  if (layoutName === 'StudentLayout') {
    return StudentLayout
  }
  return AppLayout
})
</script>
