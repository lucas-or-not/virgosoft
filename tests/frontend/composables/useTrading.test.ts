import { describe, it, expect, beforeEach, vi } from 'vitest';
import { useTrading } from '@/composables/useTrading';

describe('useTrading', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        global.fetch = vi.fn();
    });

    describe('getProfile', () => {
        it('should fetch and set profile data', async () => {
            const mockProfile = {
                id: 1,
                name: 'Test User',
                email: 'test@example.com',
                balance: '100000.00000000',
                assets: [
                    { symbol: 'BTC', amount: '1.00000000', locked_amount: '0.00000000' },
                ],
            };

            (global.fetch as any).mockResolvedValueOnce({
                ok: true,
                json: async () => ({ data: mockProfile }),
            });

            const { profile, getProfile, loading } = useTrading();

            expect(loading.value).toBe(false);
            const result = await getProfile();

            expect(global.fetch).toHaveBeenCalledWith('/api/profile', {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': 'test-csrf-token',
                },
                credentials: 'include',
            });

            expect(result).toEqual(mockProfile);
            expect(profile.value).toEqual(mockProfile);
            expect(loading.value).toBe(false);
        });

        it('should handle fetch errors', async () => {
            (global.fetch as any).mockResolvedValueOnce({
                ok: false,
            });

            const { error, getProfile } = useTrading();

            await expect(getProfile()).rejects.toThrow();
            expect(error.value).toBe('Failed to fetch profile');
        });
    });

    describe('getOrders', () => {
        it('should fetch orders without symbol filter', async () => {
            const mockOrders = [
                {
                    id: 1,
                    symbol: 'BTC',
                    side: 'buy',
                    price: '50000.00',
                    amount: '1.00000000',
                    status: 1,
                },
            ];

            (global.fetch as any).mockResolvedValueOnce({
                ok: true,
                json: async () => ({ data: mockOrders }),
            });

            const { orders, getOrders } = useTrading();

            await getOrders();

            expect(global.fetch).toHaveBeenCalledWith('/api/my-orders', {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': 'test-csrf-token',
                },
                credentials: 'include',
            });

            expect(orders.value).toEqual(mockOrders);
        });

        it('should fetch orders with symbol filter', async () => {
            const mockOrders = [
                {
                    id: 1,
                    symbol: 'BTC',
                    side: 'buy',
                    price: '50000.00',
                    amount: '1.00000000',
                    status: 1,
                },
            ];

            (global.fetch as any).mockResolvedValueOnce({
                ok: true,
                json: async () => ({ data: mockOrders }),
            });

            const { getOrders } = useTrading();

            await getOrders('BTC');

            expect(global.fetch).toHaveBeenCalledWith('/api/my-orders?symbol=BTC', {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': 'test-csrf-token',
                },
                credentials: 'include',
            });
        });
    });

    describe('getOrderbook', () => {
        it('should fetch orderbook for specific symbol', async () => {
            const mockOrderbook = {
                buy: [
                    { price: '50000.00', amount: '1.00000000', side: 'buy' },
                ],
                sell: [
                    { price: '51000.00', amount: '1.00000000', side: 'sell' },
                ],
            };

            (global.fetch as any).mockResolvedValueOnce({
                ok: true,
                json: async () => ({ data: mockOrderbook }),
            });

            const { orderbook, getOrderbook } = useTrading();

            await getOrderbook('BTC');

            expect(global.fetch).toHaveBeenCalledWith('/api/orders?symbol=BTC', {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': 'test-csrf-token',
                },
                credentials: 'include',
            });

            expect(orderbook.value).toEqual(mockOrderbook);
        });

        it('should fetch orderbook for all symbols when symbol is null', async () => {
            const mockOrderbook = {
                buy: [],
                sell: [],
            };

            (global.fetch as any).mockResolvedValueOnce({
                ok: true,
                json: async () => ({ data: mockOrderbook }),
            });

            const { getOrderbook } = useTrading();

            await getOrderbook(null);

            expect(global.fetch).toHaveBeenCalledWith('/api/orders', {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': 'test-csrf-token',
                },
                credentials: 'include',
            });
        });

        it('should fetch orderbook for all symbols when symbol is "all"', async () => {
            const mockOrderbook = {
                buy: [],
                sell: [],
            };

            (global.fetch as any).mockResolvedValueOnce({
                ok: true,
                json: async () => ({ data: mockOrderbook }),
            });

            const { getOrderbook } = useTrading();

            await getOrderbook('all');

            expect(global.fetch).toHaveBeenCalledWith('/api/orders', {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': 'test-csrf-token',
                },
                credentials: 'include',
            });
        });
    });

    describe('createOrder', () => {
        it('should create an order and refresh data', async () => {
            const orderData = {
                symbol: 'BTC',
                side: 'buy' as const,
                price: '50000.00',
                amount: '1.00000000',
            };

            const mockOrder = {
                id: 1,
                ...orderData,
                status: 1,
            };

            const mockOrders = [mockOrder];
            const mockOrderbook = {
                buy: [{ price: '50000.00', amount: '1.00000000', side: 'buy' }],
                sell: [],
            };

            (global.fetch as any)
                .mockResolvedValueOnce({
                    ok: true,
                    json: async () => ({ data: mockOrder }),
                })
                .mockResolvedValueOnce({
                    ok: true,
                    json: async () => ({ data: mockOrders }),
                })
                .mockResolvedValueOnce({
                    ok: true,
                    json: async () => ({ data: mockOrderbook }),
                });

            const { createOrder, orders, orderbook } = useTrading();

            const result = await createOrder(orderData);

            expect(global.fetch).toHaveBeenCalledWith('/api/orders', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': 'test-csrf-token',
                },
                credentials: 'include',
                body: JSON.stringify(orderData),
            });

            expect(result).toEqual(mockOrder);
            expect(orders.value).toEqual(mockOrders);
            expect(orderbook.value).toEqual(mockOrderbook);
        });

        it('should handle creation errors', async () => {
            const orderData = {
                symbol: 'BTC',
                side: 'buy' as const,
                price: '50000.00',
                amount: '1.00000000',
            };

            (global.fetch as any).mockResolvedValueOnce({
                ok: false,
                json: async () => ({ message: 'Insufficient balance' }),
            });

            const { error, createOrder } = useTrading();

            await expect(createOrder(orderData)).rejects.toThrow();
            expect(error.value).toBe('Insufficient balance');
        });
    });

    describe('cancelOrder', () => {
        it('should cancel an order and refresh orders', async () => {
            const mockOrders = [];

            (global.fetch as any)
                .mockResolvedValueOnce({
                    ok: true,
                    json: async () => ({ data: null }),
                })
                .mockResolvedValueOnce({
                    ok: true,
                    json: async () => ({ data: mockOrders }),
                });

            const { cancelOrder, orders } = useTrading();

            await cancelOrder(1);

            expect(global.fetch).toHaveBeenCalledWith('/api/orders/1/cancel', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': 'test-csrf-token',
                },
                credentials: 'include',
            });

            expect(orders.value).toEqual(mockOrders);
        });

        it('should handle cancellation errors', async () => {
            (global.fetch as any).mockResolvedValueOnce({
                ok: false,
                json: async () => ({ message: 'Order not found' }),
            });

            const { error, cancelOrder } = useTrading();

            await expect(cancelOrder(999)).rejects.toThrow();
            expect(error.value).toBe('Order not found');
        });
    });
});

