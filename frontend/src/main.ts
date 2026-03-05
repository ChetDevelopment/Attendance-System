import { createApp } from 'vue';
import App from './App.vue';
<<<<<<< HEAD
import './index.css';

const app = createApp(App);
app.mount('#root');
=======
import router from './router';
import './style.css';

createApp(App).use(router).mount('#root');
>>>>>>> feature/login
