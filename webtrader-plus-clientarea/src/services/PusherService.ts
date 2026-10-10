import Pusher from 'pusher-js';
import Echo from 'laravel-echo';
import axiosInstance from '@/lib/axios';

// Connection status types
export type ConnectionStatus = 'connected' | 'connecting' | 'disconnected' | 'failed';
export type ConnectionChangeListener = (status: ConnectionStatus, error?: string) => void;

const isPusherConfigured = (): boolean => {
    const key = import.meta.env.VITE_PUSHER_APP_KEY;
    const cluster = import.meta.env.VITE_PUSHER_APP_CLUSTER;

    if (!key || !cluster) {
        return false;
    }

    const placeholderPattern = /your_|placeholder|example/i;

    return !placeholderPattern.test(key) && !placeholderPattern.test(cluster);
};

// Global Pusher
window.Pusher = Pusher;

/**
 * Simple singleton service to maintain a Laravel Echo connection
 */
class PusherService {
    private static instance: PusherService;
    // Narrowed to 'pusher' (not the broader BroadcastDriver union) because
    // connect() always constructs with broadcaster: 'pusher' -- needed so
    // TypeScript knows connector.pusher (the real connection state) exists.
    private echo: Echo<'pusher'> | null = null;
    private status: ConnectionStatus = 'disconnected';
    private error: string | null = null;
    private listeners: ConnectionChangeListener[] = [];

    private constructor() {}

    public static getInstance(): PusherService {
        if (!PusherService.instance) {
            PusherService.instance = new PusherService();
        }
        return PusherService.instance;
    }

    public connect(): void {
        if (!isPusherConfigured()) {
            this.setStatus('disconnected');

            return;
        }

        if (this.echo || this.status === 'connecting') return;
        this.setStatus('connecting');

        try {
            // Create Echo instance
            this.echo = new Echo({
                broadcaster: 'pusher',
                key: import.meta.env.VITE_PUSHER_APP_KEY,
                cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
                forceTLS: true,
                authorizer: (channel: { name: string }) => {
                    return {
                        authorize: (socketId: string, callback: (error: boolean, response: unknown) => void) => {
                            axiosInstance
                                .post('/broadcasting/auth', {
                                    socket_id: socketId,
                                    channel_name: channel.name,
                                })
                                .then((response) => {
                                    callback(false, response.data);
                                })
                                .catch((error) => {
                                    callback(true, error);
                                });
                        },
                    };
                },
            });

            // Constructing Echo only opens the socket -- it does not mean the
            // handshake succeeded. This used to call setStatus('connected')
            // right here, which reported "connected" even when the real
            // connection was still pending, failed auth, or never came up at
            // all -- so consumers gating a polling fallback on
            // connectionStatus !== 'connected' (NotificationListener,
            // WebtraderTradeSync) never fell back to polling, and private
            // channel subscriptions silently never fired. Bind to the
            // underlying Pusher connection's own state instead.
            const pusherConnection = this.echo.connector.pusher.connection;
            pusherConnection.bind('connected', () => this.setStatus('connected'));
            pusherConnection.bind('connecting', () => this.setStatus('connecting'));
            pusherConnection.bind('unavailable', () => this.setStatus('disconnected'));
            pusherConnection.bind('disconnected', () => this.setStatus('disconnected'));
            pusherConnection.bind('failed', () => this.setStatus('failed', 'Pusher connection failed'));
            pusherConnection.bind('error', (err: { error?: { data?: { message?: string } } }) => {
                this.setStatus('failed', err?.error?.data?.message || 'Pusher connection error');
            });
        } catch (err) {
            this.setStatus('failed', err instanceof Error ? err.message : 'Connection failed');
        }
    }

    public disconnect(): void {
        if (!this.echo) return;
        this.echo.disconnect();
        this.echo = null;
        this.setStatus('disconnected');
    }

    // Subscribe to a private channel
    public subscribeToPrivateChannel<T>(
        channelName: string,
        events: Record<string, (data: T) => void>
    ): void {
        if (!this.echo) this.connect();
        if (!this.echo) return;

        const normalizedName = channelName.replace(/^private-/, '');
        const channel = this.echo.private(normalizedName);

        Object.entries(events).forEach(([event, callback]) => {
            channel.listen(event, callback);
        });
    }

    // Subscribe to a public channel
    public subscribeToChannel<T>(
        channelName: string,
        events: Record<string, (data: T) => void>
    ): void {
        if (!this.echo) this.connect();
        if (!this.echo) return;

        const channel = this.echo.channel(channelName);

        Object.entries(events).forEach(([event, callback]) => {
            channel.listen(event, callback);
        });
    }

    // Unsubscribe from a channel
    public unsubscribeFromChannel(channelName: string): void {
        if (!this.echo) return;
        this.echo.leave(channelName);
    }

    // Register for connection status changes
    public onConnectionChange(listener: ConnectionChangeListener): () => void {
        this.listeners.push(listener);
        listener(this.status, this.error || undefined);
        return () => {
            this.listeners = this.listeners.filter(l => l !== listener);
        };
    }

    // Get current status
    public getStatus(): ConnectionStatus {
        return this.status;
    }

    // Get current error if any
    public getError(): string | null {
        return this.error;
    }

    // Update status and notify listeners
    private setStatus(status: ConnectionStatus, error?: string): void {
        this.status = status;
        this.error = error || null;
        this.listeners.forEach(listener => listener(status, error));
    }
}

// Type declaration for global Pusher
declare global {
    interface Window {
        Pusher: typeof Pusher;
    }
}

export default PusherService;
