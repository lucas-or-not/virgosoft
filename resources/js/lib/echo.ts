import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo: Echo | null;
    }
}

window.Pusher = Pusher;

let echoInstance: Echo | null = null;

// Helper to get CSRF token from meta tag
function getCsrfToken(): string {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    return metaTag ? (metaTag as HTMLMetaElement).content : '';
}

function initializeEcho(): Echo {
    if (echoInstance) {
        return echoInstance;
    }

    const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY || 'app-key';
    const pusherCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER;
    const pusherHost = import.meta.env.VITE_PUSHER_HOST || 'localhost';
    const pusherPort = import.meta.env.VITE_PUSHER_PORT || '6001';
    const pusherScheme = import.meta.env.VITE_PUSHER_SCHEME || 'http';

    // Always initialize - use defaults for Soketi if not configured

    // Build Echo options conditionally
    // If using custom host (Soketi), don't use cluster
    // If using Pusher Cloud, cluster is required
    const echoOptions: any = {
        broadcaster: 'pusher',
        key: pusherKey,
        enabledTransports: ['ws', 'wss'],
        disableStats: true, // Disable stats for Soketi
    };

    // Determine if we're using custom host (Soketi) or Pusher Cloud
    const usingCustomHost = pusherHost && pusherHost !== 'api.pusher.com';
    
    if (usingCustomHost) {
        // Custom host (Soketi) - use wsHost/wsPort
        // Set cluster to empty string to avoid Pusher-js error
        echoOptions.cluster = '';
        echoOptions.wsHost = pusherHost;
        echoOptions.wsPort = parseInt(pusherPort);
        echoOptions.wssPort = parseInt(pusherPort);
        echoOptions.forceTLS = pusherScheme === 'https';
    } else if (pusherCluster) {
        // Pusher Cloud - cluster is required, use default Pusher endpoints
        echoOptions.cluster = pusherCluster;
        // Do NOT set wsHost/wsPort for Pusher Cloud
    } else {
        // Default fallback: assume Soketi on localhost
        echoOptions.cluster = '';
        echoOptions.wsHost = 'localhost';
        echoOptions.wsPort = 6001;
        echoOptions.wssPort = 6001;
        echoOptions.forceTLS = false;
    }

    // Add authentication for private channels
    // Laravel Echo will automatically use cookies for authentication
    echoOptions.authEndpoint = '/broadcasting/auth';
    echoOptions.auth = {
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
    };
    // Enable withCredentials to send cookies for authentication
    echoOptions.encrypted = pusherScheme === 'https';

    try {
        echoInstance = new Echo(echoOptions);
        window.Echo = echoInstance;

        // Add connection event listeners for debugging
        // Access Pusher connection through the connector
        const pusher = (echoInstance as any).connector?.pusher;
        if (pusher) {
            pusher.connection.bind('connected', () => {
                console.log('✅ Echo: Connected to WebSocket server');
            });

            pusher.connection.bind('disconnected', () => {
                console.warn('⚠️ Echo: Disconnected from WebSocket server');
            });

            pusher.connection.bind('error', (err: any) => {
                console.error('❌ Echo: Connection error:', err);
            });

            pusher.connection.bind('state_change', (states: any) => {
                console.log('🔄 Echo: Connection state:', states.previous, '->', states.current);
            });

            pusher.connection.bind('unavailable', () => {
                console.error('❌ Echo: WebSocket server unavailable');
            });
        }
    } catch (error) {
        console.error('Failed to initialize Echo:', error);
        // Return a mock instance that won't crash
        echoInstance = {
            private: () => ({
                listen: () => {},
                subscribed: () => {},
                error: () => {},
            }),
        } as any;
        window.Echo = echoInstance;
    }

    return echoInstance;
}

// Initialize on first import
const EchoInstance = initializeEcho();

export default EchoInstance;

