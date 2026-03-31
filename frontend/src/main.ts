import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import './style.css';

// Apply dark mode from localStorage on app initialization
const initDarkMode = () => {
  const darkMode = localStorage.getItem('teacher_dark_mode');
  if (darkMode === 'true') {
    document.documentElement.classList.add('dark');
  }
};

initDarkMode();

createApp(App).use(router).mount('#app');
