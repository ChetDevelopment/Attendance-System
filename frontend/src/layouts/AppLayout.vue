<script setup lang="ts">
import { ref } from "vue";
import Sidebar from "./Sidebar.vue";
import Navbar from "./Navbar.vue";

// Reactive state for sidebar toggle (optional)
const isSidebarOpen = ref(true);

// Function to toggle sidebar
const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value;
};
</script>

<template>
  <div class="flex h-screen overflow-hidden bg-background-light">
    <!-- Sidebar -->
    <Sidebar :is-open="isSidebarOpen" @toggle="toggleSidebar" />

    <!-- Main content -->
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <!-- Navbar -->
      <Navbar @toggle-sidebar="toggleSidebar" />

      <!-- Page content rendered via router -->
      <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
        <router-view />
      </div>
    </main>
  </div>
</template>

<style scoped>
/* Optional: smooth fade transition for routes */
</style>