# Frontend Tests

This directory contains frontend tests for the Vue.js application using Vitest and Vue Test Utils.

## Setup

Install dependencies:

```bash
yarn install
```

## Running Tests

Run all tests:
```bash
yarn test
```

Run tests in watch mode:
```bash
yarn test --watch
```

Run tests with UI:
```bash
yarn test:ui
```

Run tests with coverage:
```bash
yarn test:coverage
```

## Test Structure

- `composables/` - Tests for Vue composables (e.g., `useTrading`)
- `pages/` - Tests for page components (e.g., `OrderForm`, `Deposit`, `Dashboard`)
- `setup.ts` - Test setup file with mocks and global configuration

## Writing Tests

### Example: Testing a Composable

```typescript
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { useTrading } from '@/composables/useTrading';

describe('useTrading', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        global.fetch = vi.fn();
    });

    it('should fetch profile', async () => {
        // Test implementation
    });
});
```

### Example: Testing a Component

```typescript
import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import MyComponent from '@/components/MyComponent.vue';

describe('MyComponent', () => {
    it('should render', () => {
        const wrapper = mount(MyComponent);
        expect(wrapper.find('h1').exists()).toBe(true);
    });
});
```

## Mocks

The test setup (`setup.ts`) includes mocks for:
- Inertia.js router and components
- Laravel Echo
- Global fetch API
- CSRF token

## Coverage

Coverage reports are generated in the `coverage/` directory when running `yarn test:coverage`.

