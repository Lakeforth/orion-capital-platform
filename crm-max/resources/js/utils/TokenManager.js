import axios from 'axios';

class TokenManager {
    constructor() {
        this.token = null;
        this.expiresAt = null;
        this.tokenKey = 'admin_broadcast_token';
        this.expiresKey = 'admin_broadcast_expires';

        // Try to load token from storage on initialization
        this.loadFromStorage();
    }

    /**
     * Load token from session storage if available
     */
    loadFromStorage() {
        try {
            const token = sessionStorage.getItem(this.tokenKey);
            const expiresAt = sessionStorage.getItem(this.expiresKey);

            if (token && expiresAt) {
                this.token = token;
                this.expiresAt = parseInt(expiresAt, 10);

                // If token is expired, clear it
                if (this.isExpired()) {
                    this.clearToken();
                }
            }
        } catch (error) {
            console.error('Error loading token from storage', error);
            this.clearToken();
        }
    }

    /**
     * Save token to session storage
     */
    saveToStorage() {
        try {
            sessionStorage.setItem(this.tokenKey, this.token);
            sessionStorage.setItem(this.expiresKey, this.expiresAt.toString());
        } catch (error) {
            console.error('Error saving token to storage', error);
        }
    }

    /**
     * Clear token from memory and storage
     */
    clearToken() {
        this.token = null;
        this.expiresAt = null;

        try {
            sessionStorage.removeItem(this.tokenKey);
            sessionStorage.removeItem(this.expiresKey);
        } catch (error) {
            console.error('Error clearing token from storage', error);
        }
    }

    /**
     * Check if token is expired
     * @returns {boolean} True if token is expired or missing
     */
    isExpired() {
        if (!this.token || !this.expiresAt) {
            return true;
        }

        // Add 5 minute buffer to ensure we don't use a token that's about to expire
        const fiveMinutesInMs = 5 * 60 * 1000;
        return Date.now() > (this.expiresAt - fiveMinutesInMs);
    }

    /**
     * Get a valid token, refreshing if necessary
     * @returns {Promise<string>} Valid broadcast token
     */
    async getToken() {
        if (!this.isExpired()) {
            return this.token;
        }

        try {
            // Request new token from the server
            const response = await axios.post('/api/v1/broadcast/token');

            if (response.data && response.data.token) {
                this.token = response.data.token;

                // Calculate expiration time (default to 24 hours if not provided)
                const expiresInMs = (response.data.expires_in || 24 * 60 * 60) * 1000;
                this.expiresAt = Date.now() + expiresInMs;

                this.saveToStorage();
                return this.token;
            } else {
                throw new Error('Invalid token response');
            }
        } catch (error) {
            console.error('Failed to refresh broadcast token', error);
            this.clearToken();
            throw error;
        }
    }
}

// Create and export a singleton instance
export const tokenManager = new TokenManager();

/**
 * Echo token provider function
 * Used to configure Laravel Echo
 * @returns {Promise<string>} Valid broadcast token
 */
export async function getEchoToken() {
    return tokenManager.getToken();
}
