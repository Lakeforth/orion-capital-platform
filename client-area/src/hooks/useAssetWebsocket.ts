// NOTE: the market-data host and key are withheld from this bundle.
// The feed is a separate service and is not part of this handover.
import { useEffect, useRef, useCallback } from 'react';
import { io, Socket } from 'socket.io-client';
import useAssetStore from '@/store/assetStore';
import useSiteSettingsStore from '@/store/siteSettingStore';

// Safety-net REST poll: this socket has no fallback of its own (it gives up
// for good after reconnectionAttempts is exhausted), so market-watch/header
// prices would otherwise freeze silently if it ever drops -- same failure
// mode already found and fixed for the Pusher trade-sync channel.
const PRICE_FALLBACK_POLL_MS = 30000;

// Types for WebSocket data
export interface AssetUpdate {
    id: string;
    symbol: string;
    name: string;
    category: string;
    price: number;
    lastUpdated: string;
    tradingViewSymbol: string;
    priceLow24h?: number;
    priceHigh24h?: number;
    change24h?: number;
    changePercent24h?: number;
    volume24h?: number | null;
}

export interface WebSocketResponse {
    success: boolean;
    count: number;
    data: AssetUpdate[];
}

type AssetSnapshot = WebSocketResponse | AssetUpdate[];

interface WebSocketOptions {
    onConnected?: () => void;
    onDisconnected?: () => void;
    onError?: (error: Error) => void;
}

/**
 * Custom hook for managing asset WebSocket connections
 */
export function useAssetWebSocket(options: WebSocketOptions = {}) {
    const socketRef = useRef<Socket | null>(null);
    const lastMarketDataAtRef = useRef<number>(0);
    const optionsRef = useRef<WebSocketOptions>(options);
    const updateAssetFromWebsocket = useAssetStore(state => state.updateAssetFromWebsocket);
    const livetraderStatus = useSiteSettingsStore(
        (state) => state.settings?.livetrader_status ?? true
    );

    useEffect(() => {
        optionsRef.current = options;
    }, [options.onConnected, options.onDisconnected, options.onError]);

    // Connect to WebSocket
    const connect = useCallback(() => {
        // Don't try to reconnect if already connected
        if (socketRef.current?.connected) {
            return;
        }

        // If there's an existing socket that's not connected, clean it up first
        if (socketRef.current) {
            socketRef.current.removeAllListeners();
            socketRef.current.close();
            socketRef.current = null;
        }

        // Create a new socket
        socketRef.current = io("<MARKET_DATA_HOST>", {
            auth: {
                apiKey: "<MARKET_DATA_API_KEY>"
            },
            transports: ['websocket'],
            reconnectionAttempts: 5,
            timeout: 10000
        });

        // Handle connection events
        socketRef.current.on('connect', () => {
            lastMarketDataAtRef.current = Date.now();
            optionsRef.current.onConnected?.();

            // Subscribe to all assets immediately when connected
            if (socketRef.current) {
                socketRef.current.emit('subscribe:all');
            }
        });

        socketRef.current.on('disconnect', () => {
            optionsRef.current.onDisconnected?.();
        });

        socketRef.current.on('connect_error', (error: Error) => {
            optionsRef.current.onError?.(error);
        });

        // Handle data events
        socketRef.current.on('data:all', (response: AssetSnapshot) => {
            const assets = Array.isArray(response) ? response : response.data;
            if (assets?.length) {
                lastMarketDataAtRef.current = Date.now();
                updateAssetFromWebsocket(assets);
            }
        });

        socketRef.current.on('data:update', (assets: AssetUpdate | AssetUpdate[]) => {
            lastMarketDataAtRef.current = Date.now();
            updateAssetFromWebsocket(assets);
        });

    }, [updateAssetFromWebsocket]);

    // Subscribe to all assets
    const subscribeToAll = useCallback(() => {
        if (socketRef.current) {
            socketRef.current.emit('subscribe:all');
        }
    }, []);

    // Subscribe to specific assets by symbol
    const subscribeToAssets = useCallback((symbols: string[]) => {
        if (socketRef.current && symbols.length > 0) {
            socketRef.current.emit('subscribe:assets', { symbols });
        }
    }, []);

    // Subscribe to a single asset by symbol
    const subscribeToAsset = useCallback((symbol: string) => {
        if (socketRef.current && symbol) {
            socketRef.current.emit('subscribe:asset', { symbol });
        }
    }, []);

    // Unsubscribe from specific assets
    const unsubscribeFromAssets = useCallback((symbols: string[]) => {
        if (socketRef.current && symbols.length > 0) {
            socketRef.current.emit('unsubscribe:assets', { symbols });
        }
    }, []);

    // Unsubscribe from a single asset
    const unsubscribeFromAsset = useCallback((symbol: string) => {
        if (socketRef.current && symbol) {
            socketRef.current.emit('unsubscribe:asset', { symbol });
        }
    }, []);

    // Disconnect and cleanup
    const disconnect = useCallback(() => {
        if (socketRef.current) {
            socketRef.current.removeAllListeners();
            socketRef.current.disconnect();
            socketRef.current = null;
        }
    }, []);

    // Setup connection and cleanup
    useEffect(() => {
        // Only connect if livetrader_status is true
        if (livetraderStatus) {
            connect();
        } else {
            // Disconnect if livetrader_status is false
            disconnect();
        }

        // Clean up WebSocket connection on unmount
        return () => {
            disconnect();
        };
    }, [connect, disconnect, livetraderStatus]);

    // Safety-net REST poll. A socket can remain technically connected while
    // no market-data events are arriving, so connection state alone is not
    // enough to detect a frozen price/PnL display.
    useEffect(() => {
        if (!livetraderStatus) return;

        const interval = window.setInterval(() => {
            const streamIsFresh =
                socketRef.current?.connected &&
                Date.now() - lastMarketDataAtRef.current < PRICE_FALLBACK_POLL_MS;

            if (streamIsFresh) return;

            useAssetStore.getState().fetchAssets().catch((err) => {
                console.error("Error polling asset price fallback:", err);
            });
        }, PRICE_FALLBACK_POLL_MS);

        return () => {
            window.clearInterval(interval);
        };
    }, [livetraderStatus]);

    return {
        subscribeToAll,
        subscribeToAssets,
        subscribeToAsset,
        unsubscribeFromAssets,
        unsubscribeFromAsset,
        disconnect,
        isConnected: !!socketRef.current?.connected
    };
}
