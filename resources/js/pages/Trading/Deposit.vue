<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import InputError from '@/components/InputError.vue';
import type { BreadcrumbItem } from '@/types';
import { Wallet, DollarSign, Coins } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Deposit',
        href: '/trading/deposit',
    },
];

const depositType = ref<'usd' | 'asset'>('usd');
const symbol = ref<string>('BTC');
const amount = ref<string>('');
const loading = ref(false);
const error = ref<string | null>(null);
const success = ref(false);
const validationErrors = ref<Record<string, string>>({});

function getCsrfToken(): string {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    return metaTag ? (metaTag as HTMLMetaElement).content : '';
}

const handleSubmit = async () => {
    validationErrors.value = {};
    error.value = null;
    success.value = false;

    if (!amount.value || parseFloat(amount.value) <= 0) {
        validationErrors.value.amount = 'Amount must be greater than 0';
        return;
    }

    loading.value = true;

    try {
        const payload: any = {
            type: depositType.value,
            amount: amount.value,
        };

        if (depositType.value === 'asset') {
            payload.symbol = symbol.value;
        }

        const response = await fetch('/api/deposit', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'include',
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Failed to deposit');
        }

        success.value = true;
        amount.value = '';

        // Redirect to dashboard after 1 second
        setTimeout(() => {
            router.visit('/dashboard');
        }, 1000);
    } catch (err) {
        error.value = err instanceof Error ? err.message : 'Unknown error';
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <Head title="Deposit Funds" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-3xl mx-auto p-6 space-y-6">
            <div>
                <h1 class="text-3xl font-bold">Deposit Funds</h1>
                <p class="text-muted-foreground mt-2">
                    Add USD balance or cryptocurrency assets to your trading wallet
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Wallet class="h-5 w-5" />
                        Deposit Information
                    </CardTitle>
                    <CardDescription>
                        Select deposit type and enter the amount
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="handleSubmit" class="space-y-6">
                        <div class="grid gap-2">
                            <Label for="type" class="text-sm font-medium flex items-center gap-2">
                                <Coins class="h-4 w-4" />
                                Deposit Type
                            </Label>
                            <select
                                id="type"
                                v-model="depositType"
                                class="h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="usd">USD Balance</option>
                                <option value="asset">Cryptocurrency Asset</option>
                            </select>
                            <InputError :message="validationErrors.type" />
                        </div>

                        <div v-if="depositType === 'asset'" class="grid gap-2">
                            <Label for="symbol" class="text-sm font-medium">Asset Symbol</Label>
                            <select
                                id="symbol"
                                v-model="symbol"
                                class="h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="BTC">Bitcoin (BTC)</option>
                                <option value="ETH">Ethereum (ETH)</option>
                            </select>
                            <InputError :message="validationErrors.symbol" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="amount" class="text-sm font-medium flex items-center gap-2">
                                <DollarSign class="h-4 w-4" />
                                {{ depositType === 'usd' ? 'Amount (USD)' : `Amount (${symbol})` }}
                            </Label>
                            <Input
                                id="amount"
                                v-model="amount"
                                type="number"
                                step="0.00000001"
                                min="0.00000001"
                                placeholder="0.00"
                                required
                                class="h-10"
                            />
                            <InputError :message="validationErrors.amount" />
                            <p class="text-xs text-muted-foreground">
                                Enter the amount you want to deposit to your wallet
                            </p>
                        </div>

                        <div v-if="error" class="p-4 rounded-lg bg-destructive/10 border border-destructive/20 text-destructive text-sm">
                            {{ error }}
                        </div>

                        <div v-if="success" class="p-4 rounded-lg bg-green-500/10 border border-green-500/20 text-green-600 dark:text-green-400 text-sm">
                            ✓ Deposit successful! Redirecting to dashboard...
                        </div>

                        <div class="flex items-center gap-4 pt-4 border-t">
                            <Button 
                                type="submit" 
                                :disabled="loading"
                                class="flex-1"
                                size="lg"
                            >
                                {{ loading ? 'Processing...' : 'Deposit' }}
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

