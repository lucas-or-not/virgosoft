<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import InputError from '@/components/InputError.vue';
import { useTrading } from '@/composables/useTrading';
import type { CreateOrderData } from '@/types/trading';
import type { BreadcrumbItem } from '@/types';
import { ArrowUpCircle, ArrowDownCircle, TrendingUp, DollarSign } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Place Order',
        href: '/trading/order',
    },
];

const { createOrder, loading, error, profile, getProfile } = useTrading();

const form = ref<CreateOrderData>({
    symbol: 'BTC',
    side: 'buy',
    price: '',
    amount: '',
});

const validationErrors = ref<Record<string, string>>({});
const success = ref(false);

// Calculate total cost/value
const totalCost = computed(() => {
    if (!form.value.price || !form.value.amount) {
        return '0.00';
    }
    const price = parseFloat(form.value.price);
    const amount = parseFloat(form.value.amount);
    if (isNaN(price) || isNaN(amount)) {
        return '0.00';
    }
    return (price * amount).toFixed(2);
});

// Check if user has sufficient balance (for buy orders)
const hasSufficientBalance = computed(() => {
    if (form.value.side !== 'buy' || !profile.value) {
        return true;
    }
    const balance = parseFloat(profile.value.balance || '0');
    const cost = parseFloat(totalCost.value);
    return balance >= cost;
});

const handleSubmit = async () => {
    validationErrors.value = {};
    success.value = false;

    if (!form.value.price || parseFloat(form.value.price) <= 0) {
        validationErrors.value.price = 'Price must be greater than 0';
        return;
    }

    if (!form.value.amount || parseFloat(form.value.amount) <= 0) {
        validationErrors.value.amount = 'Amount must be greater than 0';
        return;
    }

    // Check balance for buy orders
    if (form.value.side === 'buy' && !hasSufficientBalance.value) {
        validationErrors.value.amount = 'Insufficient balance to place this order';
        return;
    }

    try {
        await createOrder(form.value);
        success.value = true;
        // Reset form
        form.value.price = '';
        form.value.amount = '';
        // Redirect to dashboard after 1 second
        setTimeout(() => {
            router.visit('/dashboard');
        }, 1000);
    } catch (err) {
        // Error is handled by composable
    }
};

// Load profile when needed for balance checking
onMounted(async () => {
    if (form.value.side === 'buy') {
        await getProfile();
    }
});

// Watch for side changes to load profile for buy orders
watch(() => form.value.side, async (newSide) => {
    if (newSide === 'buy') {
        await getProfile();
    }
});
</script>

<template>
    <Head title="Place Order" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-3xl mx-auto p-6 space-y-6">
            <div>
                <h1 class="text-3xl font-bold">Place Limit Order</h1>
                <p class="text-muted-foreground mt-2">
                    Create a new buy or sell order for cryptocurrency trading
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <TrendingUp class="h-5 w-5" />
                        Order Details
                    </CardTitle>
                    <CardDescription>
                        Fill in the order information below
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="handleSubmit" class="space-y-6">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="symbol" class="text-sm font-medium">Symbol</Label>
                                <select
                                    id="symbol"
                                    v-model="form.symbol"
                                    class="h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="BTC">Bitcoin (BTC)</option>
                                    <option value="ETH">Ethereum (ETH)</option>
                                </select>
                                <InputError :message="validationErrors.symbol" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="side" class="text-sm font-medium">Order Type</Label>
                                <div class="relative">
                                    <select
                                        id="side"
                                        v-model="form.side"
                                        class="h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        :class="form.side === 'buy' ? 'border-green-500/50 focus-visible:ring-green-500/20' : 'border-red-500/50 focus-visible:ring-red-500/20'"
                                    >
                                        <option value="buy">Buy</option>
                                        <option value="sell">Sell</option>
                                    </select>
                                    <component
                                        :is="form.side === 'buy' ? ArrowUpCircle : ArrowDownCircle"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 pointer-events-none"
                                        :class="form.side === 'buy' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                                    />
                                </div>
                                <InputError :message="validationErrors.side" />
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="price" class="text-sm font-medium flex items-center gap-2">
                                    <DollarSign class="h-4 w-4" />
                                    Price (USD)
                                </Label>
                                <Input
                                    id="price"
                                    v-model="form.price"
                                    type="number"
                                    step="0.00000001"
                                    min="0.00000001"
                                    placeholder="0.00"
                                    required
                                    class="h-10"
                                />
                                <InputError :message="validationErrors.price" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="amount" class="text-sm font-medium">
                                    Amount ({{ form.symbol }})
                                </Label>
                                <Input
                                    id="amount"
                                    v-model="form.amount"
                                    type="number"
                                    step="0.00000001"
                                    min="0.00000001"
                                    placeholder="0.00"
                                    required
                                    class="h-10"
                                />
                                <InputError :message="validationErrors.amount" />
                                <p class="text-xs text-muted-foreground">
                                    Enter the amount of {{ form.symbol }} you want to {{ form.side === 'buy' ? 'buy' : 'sell' }}
                                </p>
                            </div>
                        </div>

                        <!-- Total Cost/Value Display -->
                        <div
                            v-if="form.price && form.amount && parseFloat(form.price) > 0 && parseFloat(form.amount) > 0"
                            class="rounded-lg border-2 p-4 transition-colors"
                            :class="form.side === 'buy' && !hasSufficientBalance 
                                ? 'border-destructive bg-destructive/5' 
                                : form.side === 'buy' 
                                    ? 'border-green-500/30 bg-green-500/5' 
                                    : 'border-red-500/30 bg-red-500/5'"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-muted-foreground">
                                    {{ form.side === 'buy' ? 'Total Cost' : 'Total Value' }}:
                                </span>
                                <span
                                    class="text-2xl font-bold"
                                    :class="form.side === 'buy' && !hasSufficientBalance 
                                        ? 'text-destructive' 
                                        : form.side === 'buy'
                                            ? 'text-green-600 dark:text-green-400'
                                            : 'text-red-600 dark:text-red-400'"
                                >
                                    ${{ totalCost }}
                                </span>
                            </div>
                            <div
                                v-if="form.side === 'buy' && profile && !hasSufficientBalance"
                                class="text-sm text-destructive font-medium"
                            >
                                ⚠️ Insufficient balance. You have ${{ profile.balance || '0.00' }}, need ${{ totalCost }}
                            </div>
                            <div
                                v-else-if="form.side === 'buy' && profile"
                                class="text-sm text-muted-foreground"
                            >
                                Available balance: ${{ profile.balance || '0.00' }}
                            </div>
                        </div>

                        <div v-if="error" class="p-4 rounded-lg bg-destructive/10 border border-destructive/20 text-destructive text-sm">
                            {{ error }}
                        </div>

                        <div v-if="success" class="p-4 rounded-lg bg-green-500/10 border border-green-500/20 text-green-600 dark:text-green-400 text-sm">
                            ✓ Order placed successfully! Redirecting to dashboard...
                        </div>

                        <div class="flex items-center gap-4 pt-4 border-t">
                            <Button
                                type="submit"
                                :disabled="loading || (form.side === 'buy' && !hasSufficientBalance)"
                                class="flex-1"
                                size="lg"
                                :class="form.side === 'buy' 
                                    ? 'bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600' 
                                    : 'bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600'"
                            >
                                {{ loading ? 'Placing Order...' : `Place ${form.side === 'buy' ? 'Buy' : 'Sell'} Order` }}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="lg"
                                @click="router.visit('/dashboard')"
                            >
                                Cancel
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

