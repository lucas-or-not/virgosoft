<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { useTrading } from '@/composables/useTrading';
import type { BreadcrumbItem } from '@/types';
import Echo from '@/lib/echo';
import type { OrderMatchedEvent } from '@/types/trading';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const page = usePage();
const user = page.props.auth?.user as { id: number } | undefined;

const { profile, orders, orderbook, loading, getProfile, getOrders, getOrderbook, cancelOrder, subscribeToOrderUpdates, subscribeToOrderbookUpdates } = useTrading();

// Separate filters for different sections
const myOrdersSymbol = ref<string>('all');
const selectedSide = ref<'buy' | 'sell' | 'all'>('all');
const selectedStatus = ref<'all' | 'open' | 'filled' | 'cancelled'>('all');
const buyOrdersSymbol = ref<string>('all');
const sellOrdersSymbol = ref<string>('all');

// Store subscription cleanup function
let orderbookSubscriptionCleanup: (() => void) | null = null;

const filteredOrders = computed(() => {
    let filtered = orders.value;

    if (myOrdersSymbol.value !== 'all') {
        filtered = filtered.filter(order => order.symbol === myOrdersSymbol.value);
    }

    if (selectedSide.value !== 'all') {
        filtered = filtered.filter(order => order.side === selectedSide.value);
    }

    if (selectedStatus.value !== 'all') {
        const statusMap = { open: 1, filled: 2, cancelled: 3 };
        filtered = filtered.filter(order => order.status === statusMap[selectedStatus.value]);
    }

    return filtered;
});

const statusLabel = (status: number): string => {
    const labels: Record<number, string> = {
        1: 'Open',
        2: 'Filled',
        3: 'Cancelled',
    };
    return labels[status] || 'Unknown';
};

const statusColor = (status: number): string => {
    const colors: Record<number, string> = {
        1: 'text-blue-600 dark:text-blue-400',
        2: 'text-green-600 dark:text-green-400',
        3: 'text-gray-600 dark:text-gray-400',
    };
    return colors[status] || '';
};

const handleCancelOrder = async (orderId: number) => {
    if (confirm('Are you sure you want to cancel this order?')) {
        await cancelOrder(orderId);
        await getOrders();
    }
};

const loadData = async () => {
    // Load profile and orders (orders don't depend on symbol filter)
    await Promise.all([
        getProfile(),
        getOrders(),
    ]);
    
    // Load orderbook with all symbols by default
    await getOrderbook(null);
};

onMounted(async () => {
    await loadData();

    if (user?.id) {
        // Subscribe to private channel for user's own order updates
        subscribeToOrderUpdates(user.id, (event: OrderMatchedEvent) => {
            // Refresh all data when order is matched
            loadData();
        });
    }

    // Subscribe to public orderbook channel for real-time orderbook updates
    // Subscribe to all symbols since widgets can have different filters
    orderbookSubscriptionCleanup = subscribeToOrderbookUpdates(null, () => {
        // Orderbook is already refreshed in the subscription handler
        console.log('Orderbook updated');
    });
});

// Filter orderbook data based on symbol (client-side filtering)
const filteredBuyOrders = computed(() => {
    if (!orderbook.value?.buy) return [];
    if (buyOrdersSymbol.value === 'all') return orderbook.value.buy;
    return orderbook.value.buy.filter(entry => entry.symbol === buyOrdersSymbol.value);
});

const filteredSellOrders = computed(() => {
    if (!orderbook.value?.sell) return [];
    if (sellOrdersSymbol.value === 'all') return orderbook.value.sell;
    return orderbook.value.sell.filter(entry => entry.symbol === sellOrdersSymbol.value);
});

const handleBuyOrdersSymbolChange = (symbol: string) => {
    buyOrdersSymbol.value = symbol;
    // No need to reload orderbook - we filter client-side
};

const handleSellOrdersSymbolChange = (symbol: string) => {
    sellOrdersSymbol.value = symbol;
    // No need to reload orderbook - we filter client-side
};

// Cleanup on unmount
onUnmounted(() => {
    if (orderbookSubscriptionCleanup) {
        orderbookSubscriptionCleanup();
    }
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Trading Dashboard</h1>
                    <p class="text-muted-foreground mt-1">
                        Manage your orders and view the orderbook
                    </p>
                </div>
                <div class="flex gap-3">
                    <Button
                        variant="outline"
                        @click="router.visit('/trading/deposit')"
                    >
                        Deposit
                    </Button>
                    <Button @click="router.visit('/trading/order')">
                        Place Order
                    </Button>
                </div>
            </div>

            <!-- Balance Section -->
            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-lg border p-4">
                    <h2 class="text-lg font-semibold mb-4">USD Balance</h2>
                    <p class="text-3xl font-bold">
                        ${{ profile?.balance || '0.00' }}
                    </p>
                </div>
                <div class="rounded-lg border p-4">
                    <h2 class="text-lg font-semibold mb-4">Assets</h2>
                    <div v-if="profile?.assets && profile.assets.length > 0" class="space-y-2">
                        <div
                            v-for="asset in profile.assets"
                            :key="asset.symbol"
                            class="flex justify-between items-center"
                        >
                            <span class="font-medium">{{ asset.symbol }}</span>
                            <div class="text-right">
                                <div>{{ asset.available_amount }} available</div>
                                <div class="text-sm text-muted-foreground">
                                    {{ asset.locked_amount }} locked
                                </div>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-muted-foreground">No assets</p>
                </div>
            </div>

            <!-- Orders Section -->
            <div class="rounded-lg border p-4">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold">My Orders</h2>
                    <div class="flex gap-2">
                        <select
                            v-model="myOrdersSymbol"
                            @change="getOrders(myOrdersSymbol === 'all' ? undefined : myOrdersSymbol)"
                            class="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="all">All Symbols</option>
                            <option value="BTC">BTC</option>
                            <option value="ETH">ETH</option>
                        </select>
                        <select
                            v-model="selectedSide"
                            class="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="all">All Sides</option>
                            <option value="buy">Buy</option>
                            <option value="sell">Sell</option>
                        </select>
                        <select
                            v-model="selectedStatus"
                            class="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="all">All Status</option>
                            <option value="open">Open</option>
                            <option value="filled">Filled</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <div v-if="loading" class="text-center py-8 text-muted-foreground">
                    Loading...
                </div>

                <div v-else-if="filteredOrders.length === 0" class="text-center py-8 text-muted-foreground">
                    No orders found
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left p-2">Symbol</th>
                                <th class="text-left p-2">Side</th>
                                <th class="text-left p-2">Price</th>
                                <th class="text-left p-2">Amount</th>
                                <th class="text-left p-2">Status</th>
                                <th class="text-left p-2">Created</th>
                                <th class="text-left p-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="order in filteredOrders"
                                :key="order.id"
                                class="border-b"
                            >
                                <td class="p-2">{{ order.symbol }}</td>
                                <td class="p-2">
                                    <span
                                        :class="order.side === 'buy' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                                    >
                                        {{ order.side.toUpperCase() }}
                                    </span>
                                </td>
                                <td class="p-2">${{ order.price }}</td>
                                <td class="p-2">{{ order.amount }}</td>
                                <td class="p-2">
                                    <span :class="statusColor(order.status)">
                                        {{ statusLabel(order.status) }}
                                    </span>
                                </td>
                                <td class="p-2 text-sm text-muted-foreground">
                                    {{ new Date(order.created_at).toLocaleString() }}
                                </td>
                                <td class="p-2">
                                    <Button
                                        v-if="order.status === 1"
                                        variant="destructive"
                                        size="sm"
                                        @click="handleCancelOrder(order.id)"
                                    >
                                        Cancel
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Orderbook Section -->
            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-lg border p-4">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold">Buy Orders</h2>
                        <select
                            v-model="buyOrdersSymbol"
                            @change="handleBuyOrdersSymbolChange(($event.target as HTMLSelectElement).value)"
                            class="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="all">All Symbols</option>
                            <option value="BTC">BTC</option>
                            <option value="ETH">ETH</option>
                        </select>
                    </div>
                    <div v-if="loading" class="text-center py-4 text-muted-foreground">
                        Loading...
                    </div>
                    <div v-else-if="!filteredBuyOrders || filteredBuyOrders.length === 0" class="text-center py-4 text-muted-foreground">
                        No buy orders
                    </div>
                    <div v-else class="space-y-1">
                        <div
                            v-for="(entry, index) in filteredBuyOrders"
                            :key="index"
                            class="flex justify-between text-sm"
                        >
                            <div class="flex items-center gap-2">
                                <span class="text-green-600 dark:text-green-400">${{ entry.price }}</span>
                                <span v-if="entry.symbol" class="text-xs text-muted-foreground">({{ entry.symbol }})</span>
                            </div>
                            <span>{{ entry.amount }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border p-4">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold">Sell Orders</h2>
                        <select
                            v-model="sellOrdersSymbol"
                            @change="handleSellOrdersSymbolChange(($event.target as HTMLSelectElement).value)"
                            class="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="all">All Symbols</option>
                            <option value="BTC">BTC</option>
                            <option value="ETH">ETH</option>
                        </select>
                    </div>
                    <div v-if="loading" class="text-center py-4 text-muted-foreground">
                        Loading...
                    </div>
                    <div v-else-if="!filteredSellOrders || filteredSellOrders.length === 0" class="text-center py-4 text-muted-foreground">
                        No sell orders
                    </div>
                    <div v-else class="space-y-1">
                        <div
                            v-for="(entry, index) in filteredSellOrders"
                            :key="index"
                            class="flex justify-between text-sm"
                        >
                            <div class="flex items-center gap-2">
                                <span class="text-red-600 dark:text-red-400">${{ entry.price }}</span>
                                <span v-if="entry.symbol" class="text-xs text-muted-foreground">({{ entry.symbol }})</span>
                            </div>
                            <span>{{ entry.amount }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
