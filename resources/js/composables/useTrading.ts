import { router, usePage } from '@inertiajs/vue3';
import { ref, type Ref } from 'vue';
import Echo from '../lib/echo';
import type {
    Asset,
    CreateOrderData,
    Order,
    Orderbook,
    OrderMatchedEvent,
    Profile,
} from '../types/trading';

// Helper to get CSRF token from meta tag
function getCsrfToken(): string {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    return metaTag ? (metaTag as HTMLMetaElement).content : '';
}

export function useTrading() {
    const profile: Ref<Profile | null> = ref(null);
    const orders: Ref<Order[]> = ref([]);
    const orderbook: Ref<Orderbook | null> = ref(null);
    const loading = ref(false);
    const error = ref<string | null>(null);

    const getProfile = async (): Promise<Profile> => {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch('/api/profile', {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'include',
            });

            if (!response.ok) {
                throw new Error('Failed to fetch profile');
            }

            const data = await response.json();
            profile.value = data.data;
            return data.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const getOrders = async (symbol?: string): Promise<Order[]> => {
        loading.value = true;
        error.value = null;
        try {
            const url = symbol ? `/api/my-orders?symbol=${symbol}` : '/api/my-orders';
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'include',
            });

            if (!response.ok) {
                throw new Error('Failed to fetch orders');
            }

            const data = await response.json();
            orders.value = data.data;
            return data.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const getOrderbook = async (symbol: string | null): Promise<Orderbook> => {
        loading.value = true;
        error.value = null;
        try {
            // If symbol is null or 'all', don't include symbol parameter
            const url = symbol && symbol !== 'all' ? `/api/orders?symbol=${symbol}` : '/api/orders';
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'include',
            });

            if (!response.ok) {
                throw new Error('Failed to fetch orderbook');
            }

            const data = await response.json();
            orderbook.value = data.data;
            return data.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const createOrder = async (orderData: CreateOrderData): Promise<Order> => {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch('/api/orders', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'include',
                body: JSON.stringify(orderData),
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to create order');
            }

            const data = await response.json();
            // Refresh orders and orderbook
            await Promise.all([
                getOrders(orderData.symbol),
                getOrderbook(orderData.symbol),
            ]);
            return data.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const cancelOrder = async (orderId: number): Promise<void> => {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch(`/api/orders/${orderId}/cancel`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'include',
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to cancel order');
            }

            // Refresh orders
            await getOrders();
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const subscribeToOrderUpdates = (userId: number, onUpdate: (event: OrderMatchedEvent) => void): void => {
        try {
            if (!Echo) {
                console.error('Echo is not initialized');
                return;
            }

            console.log(`Attempting to subscribe to private channel: user.${userId}`);
            
            const channel = Echo.private(`user.${userId}`);

            // Add subscription event listeners
            channel.subscribed(() => {
                console.log(`✅ Echo: Successfully subscribed to private channel user.${userId}`);
            });

            channel.error((error: any) => {
                console.error(`❌ Echo: Error subscribing to user.${userId}:`, error);
            });

            // Note: subscription_error might not be available in all Echo versions
            // Errors will be caught by channel.error() above

            channel.listen('.order.matched', (event: OrderMatchedEvent) => {
                console.log('📢 Order matched event received:', event);
                onUpdate(event);

            // Always refresh orders list to get latest status
            // This ensures the UI is updated even if the event data is slightly stale
            getOrders().then(() => {
                // Also update specific orders in the list if they exist (optimistic update)
                if (event.buy_order?.id) {
                    const buyOrderIndex = orders.value.findIndex(o => o.id === event.buy_order.id);
                    if (buyOrderIndex !== -1) {
                        orders.value[buyOrderIndex] = {
                            ...orders.value[buyOrderIndex],
                            status: event.buy_order.status,
                        };
                    }
                }

                if (event.sell_order?.id) {
                    const sellOrderIndex = orders.value.findIndex(o => o.id === event.sell_order.id);
                    if (sellOrderIndex !== -1) {
                        orders.value[sellOrderIndex] = {
                            ...orders.value[sellOrderIndex],
                            status: event.sell_order.status,
                        };
                    }
                }
            }).catch(err => {
                console.error('Error refreshing orders after match:', err);
            });

            // Refresh profile and orderbook
            Promise.all([
                getProfile(),
                event.buy_order?.symbol ? getOrderbook(event.buy_order.symbol) : Promise.resolve(),
            ]).catch(err => {
                console.error('Error refreshing data after order match:', err);
            });
        });
        } catch (error) {
            console.error('Failed to subscribe to order updates:', error);
        }
    };

    const subscribeToOrderbookUpdates = (symbol: string | null, onUpdate: () => void): (() => void) => {
        try {
            if (!Echo) {
                console.error('Echo is not initialized');
                return () => {};
            }

            // If symbol is null or 'all', subscribe to both BTC and ETH channels
            if (!symbol || symbol === 'all') {
                const symbols = ['BTC', 'ETH'];
                const cleanupFunctions: (() => void)[] = [];

                symbols.forEach(sym => {
                    console.log(`Subscribing to orderbook updates for: ${sym}`);
                    const channel = Echo.channel(`orderbook.${sym}`);
                    
                    channel.subscribed(() => {
                        console.log(`✅ Echo: Subscribed to orderbook channel: orderbook.${sym}`);
                    });

                    channel.error((error: any) => {
                        console.error(`❌ Echo: Error subscribing to orderbook.${sym}:`, error);
                    });

                    // Listen for order matched events
                    channel.listen('.order.matched', () => {
                        console.log(`📢 Orderbook update received (order matched) for ${sym}`);
                        getOrderbook(null).then(() => {
                            onUpdate();
                        }).catch(err => {
                            console.error('Error refreshing orderbook:', err);
                        });
                    });

                    // Listen for new order created events
                    channel.listen('.order.created', () => {
                        console.log(`📢 Orderbook update received (new order created) for ${sym}`);
                        getOrderbook(null).then(() => {
                            onUpdate();
                        }).catch(err => {
                            console.error('Error refreshing orderbook:', err);
                        });
                    });

                    // Listen for order cancelled events
                    channel.listen('.order.cancelled', () => {
                        console.log(`📢 Orderbook update received (order cancelled) for ${sym}`);
                        getOrderbook(null).then(() => {
                            onUpdate();
                        }).catch(err => {
                            console.error('Error refreshing orderbook:', err);
                        });
                    });

                    cleanupFunctions.push(() => {
                        try {
                            Echo.leave(`orderbook.${sym}`);
                            console.log(`Unsubscribed from orderbook channel: orderbook.${sym}`);
                        } catch (err) {
                            console.error('Error unsubscribing from orderbook channel:', err);
                        }
                    });
                });

                return () => {
                    cleanupFunctions.forEach(cleanup => cleanup());
                };
            }

            console.log(`Subscribing to orderbook updates for: ${symbol}`);
            
            // Subscribe to public channel for orderbook updates
            const channel = Echo.channel(`orderbook.${symbol}`);

            channel.subscribed(() => {
                console.log(`✅ Echo: Subscribed to orderbook channel: orderbook.${symbol}`);
            });

            channel.error((error: any) => {
                console.error(`❌ Echo: Error subscribing to orderbook.${symbol}:`, error);
            });

            // Listen for order matched events on the orderbook channel
            channel.listen('.order.matched', () => {
                console.log(`📢 Orderbook update received (order matched) for ${symbol}`);
                // Refresh the orderbook when an order is matched
                getOrderbook(symbol).then(() => {
                    onUpdate();
                }).catch(err => {
                    console.error('Error refreshing orderbook:', err);
                });
            });

            // Listen for new order created events on the orderbook channel
            channel.listen('.order.created', () => {
                console.log(`📢 Orderbook update received (new order created) for ${symbol}`);
                // Refresh the orderbook when a new order is created
                getOrderbook(symbol).then(() => {
                    onUpdate();
                }).catch(err => {
                    console.error('Error refreshing orderbook:', err);
                });
            });

            // Listen for order cancelled events on the orderbook channel
            channel.listen('.order.cancelled', () => {
                console.log(`📢 Orderbook update received (order cancelled) for ${symbol}`);
                // Refresh the orderbook when an order is cancelled
                getOrderbook(symbol).then(() => {
                    onUpdate();
                }).catch(err => {
                    console.error('Error refreshing orderbook:', err);
                });
            });

            // Return cleanup function
            return () => {
                try {
                    Echo.leave(`orderbook.${symbol}`);
                    console.log(`Unsubscribed from orderbook channel: orderbook.${symbol}`);
                } catch (err) {
                    console.error('Error unsubscribing from orderbook channel:', err);
                }
            };
        } catch (error) {
            console.error('Failed to subscribe to orderbook updates:', error);
            return () => {};
        }
    };

    return {
        profile,
        orders,
        orderbook,
        loading,
        error,
        getProfile,
        getOrders,
        getOrderbook,
        createOrder,
        cancelOrder,
        subscribeToOrderUpdates,
        subscribeToOrderbookUpdates,
    };
}

