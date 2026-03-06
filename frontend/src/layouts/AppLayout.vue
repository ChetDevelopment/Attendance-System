<template>
  <div class="min-h-screen bg-slate-950 text-slate-100">
    <div class="flex min-h-screen">
      <aside class="w-60 border-r border-slate-800 bg-slate-900/40 p-4 space-y-2">
        <h1 class="text-lg font-semibold mb-4">Attendance System</h1>
        <router-link to="/dashboard" class="block px-3 py-2 rounded hover:bg-slate-800">Dashboard</router-link>
        <router-link to="/attendance" class="block px-3 py-2 rounded hover:bg-slate-800">Attendance</router-link>
        <router-link to="/history" class="block px-3 py-2 rounded hover:bg-slate-800">History</router-link>
        <router-link to="/settings" class="block px-3 py-2 rounded hover:bg-slate-800">Settings</router-link>
      </aside>
      <main class="flex-1">
        <router-view />
      </main>
    </div>
  </div>
</template>
