import axios from 'axios';

// PrivateChannel original method reference
let originalPrivateChannel = null;

/**
 * Setup Echo authentication interceptor
 * This attaches to the existing Laravel Echo instance and
 * ensures authentication tokens are properly added
 */
export const setupEchoAuth = async () => {
    if (!window.Echo) {
        console.error('Echo is not initialized yet');
        return false;
    }

    try {
        // Store original method if not already done
        if (!originalPrivateChannel && window.Echo.private) {
            originalPrivateChannel = window.Echo.private;
        }

        // Override the private method to inject authentication token
        window.Echo.private = function(channel) {
            // Get current token from storage
            const token = sessionStorage.getItem('echo_auth_token');

            // Set auth header on Echo connector if token exists
            if (token && this.connector) {
                // Make sure auth object exists
                if (!this.connector.options.auth) {
                    this.connector.options.auth = {};
                }

                // Make sure headers object exists
                if (!this.connector.options.auth.headers) {
                    this.connector.options.auth.headers = {};
                }

                // Set authorization header
                this.connector.options.auth.headers.Authorization = `Bearer ${token}`;
            }

            // Call original method
            return originalPrivateChannel.call(this, channel);
        };

        // Initial token fetch
        await refreshAuthToken();

        // Setup reconnection handler to refresh token
        if (window.Echo.connector && window.Echo.connector.socket) {
            window.Echo.connector.socket.on('connecting', async () => {
                await refreshAuthToken();
            });
        }

        return true;
    } catch (error) {
        console.error('Failed to setup Echo auth:', error);
        return false;
    }
};

/**
 * Refresh authentication token
 * @returns {Promise<string|null>} The new token or null on failure
 */
export const refreshAuthToken = async () => {
    try {
        const response = await axios.post('/api/broadcast/token');

        if (response.data && response.data.token) {
            // Store token in session storage
            sessionStorage.setItem('echo_auth_token', response.data.token);

            // Update Echo connector if it exists
            if (window.Echo && window.Echo.connector) {
                // Ensure auth headers exist
                if (!window.Echo.connector.options.auth) {
                    window.Echo.connector.options.auth = {};
                }

                if (!window.Echo.connector.options.auth.headers) {
                    window.Echo.connector.options.auth.headers = {};
                }

                // Set the auth header
                window.Echo.connector.options.auth.headers.Authorization = `Bearer ${response.data.token}`;
            }

            return response.data.token;
        }

        return null;
    } catch (error) {
        console.error('Failed to refresh auth token:', error);
        return null;
    }
};

/**
 * Get the current authentication token or refresh if needed
 * @returns {Promise<string|null>} The current token or null on failure
 */
export const getAuthToken = async () => {
    // Check if token exists in storage
    const token = sessionStorage.getItem('echo_auth_token');

    if (token) {
        return token;
    }

    // Token doesn't exist, try to refresh
    return await refreshAuthToken();
};
