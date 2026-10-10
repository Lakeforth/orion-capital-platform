
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

export {}; // make this file a module

declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo: Echo;
    }

    interface ImportMetaEnv {
        readonly VITE_PUSHER_APP_KEY: string;
        readonly VITE_PUSHER_APP_CLUSTER: string;
    }
    interface ImportMeta {
        readonly env: ImportMetaEnv;
    }
}
