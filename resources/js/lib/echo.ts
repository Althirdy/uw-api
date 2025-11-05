import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Make Pusher available globally
(window as any).Pusher = Pusher;

<<<<<<< HEAD
// Determine broadcaster based on environment
const broadcaster = import.meta.env.VITE_PUSHER_APP_KEY ? 'pusher' : 'reverb';

// Initialize Laravel Echo
const echo = new Echo(
    broadcaster === 'pusher'
        ? {
              broadcaster: 'pusher',
              key: import.meta.env.VITE_PUSHER_APP_KEY,
              cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'ap1',
              forceTLS: true,
          }
        : {
              broadcaster: 'reverb',
              key: import.meta.env.VITE_REVERB_APP_KEY,
              wsHost: import.meta.env.VITE_REVERB_HOST,
              wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
              wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
              forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
              enabledTransports: ['ws', 'wss'],
          }
);
=======
// Initialize Laravel Echo with Reverb
const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
>>>>>>> ed05b14 (feat: Add Laravel Reverb real-time updates for YOLO accident detection)

export default echo;
