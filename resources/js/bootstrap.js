import Echo from 'laravel-echo';
import axios from 'axios';
window.axios = axios;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    wsHost: import.meta.env.VITE_PUSHER_HOST,
    wsPort: import.meta.env.VITE_PUSHER_PORT,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
});

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
