<script setup lang="ts">
import { login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="Welcome" />
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-[#FDFDFC] p-6 text-[#1b1b18] dark:bg-[#0a0a0a]"
    >
        <div class="w-full max-w-md space-y-8 text-center">
            <div>
                <h1 class="text-4xl font-bold mb-4">Welcome</h1>
                <p class="text-lg text-[#706f6c] dark:text-[#A1A09A]">
                    Get started with your trading account
                </p>
            </div>
            
            <div class="flex flex-col gap-4">
                <Link
                    :href="login()"
                    class="inline-block rounded-lg border border-[#19140035] bg-white px-8 py-3 text-base font-medium text-[#1b1b18] hover:border-[#1915014a] hover:bg-gray-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                >
                    Log In
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="register()"
                    class="inline-block rounded-lg border border-[#19140035] bg-[#1b1b18] px-8 py-3 text-base font-medium text-white hover:bg-black dark:border-[#3E3E3A] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-white"
                >
                    Sign Up
                </Link>
                </div>
        </div>
    </div>
</template>
