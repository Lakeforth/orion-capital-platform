
import Echo from 'laravel-echo';

// Only declare what we actually use to avoid conflicts
declare module 'laravel-echo' {
    interface Channel {
        listen<T>(event: string, callback: (data: T) => void): Channel;
    }
}
