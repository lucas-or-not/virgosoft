import { vi } from 'vitest';
import { config } from '@vue/test-utils';

// Mock Inertia.js
vi.mock('@inertiajs/vue3', () => ({
    router: {
        get: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        patch: vi.fn(),
        delete: vi.fn(),
        visit: vi.fn(),
    },
    usePage: vi.fn(() => ({
        props: {
            auth: {
                user: {
                    id: 1,
                    name: 'Test User',
                    email: 'test@example.com',
                },
            },
        },
    })),
    Head: {
        name: 'Head',
        setup() {
            return () => null;
        },
    },
}));

// Mock Laravel Echo
vi.mock('@/lib/echo', () => ({
    default: {
        private: vi.fn(() => ({
            subscribed: vi.fn(),
            error: vi.fn(),
            listen: vi.fn(),
        })),
        channel: vi.fn(() => ({
            subscribed: vi.fn(),
            error: vi.fn(),
            listen: vi.fn(),
        })),
        leave: vi.fn(),
    },
}));

// Mock fetch globally
global.fetch = vi.fn();

// Mock CSRF token
Object.defineProperty(document, 'querySelector', {
    value: vi.fn(() => ({
        content: 'test-csrf-token',
    })),
    writable: true,
});

// Configure Vue Test Utils
config.global.stubs = {
    Head: true,
    RouterLink: true,
};

