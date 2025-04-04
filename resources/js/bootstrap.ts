import axios from 'axios';
// import Echo from 'laravel-echo';
import { io } from 'socket.io-client';

// Pastikan tipe global untuk TypeScript
declare global {
    interface Window {
        axios: typeof axios;
        io: typeof io;
        // Echo: Echo<any>;
    }
}

// Konfigurasi Axios
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.io = io;
// window.Echo = new Echo({
//     broadcaster: 'socket.io',
//     host: window.location.hostname + ':6001',
//     transports: ['websocket', 'polling', 'flashsocket'],
//     reconnectionAttempts: 5,
//     reconnectionDelay: 3000,
// });
