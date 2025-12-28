import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import Deposit from '@/pages/Trading/Deposit.vue';
import { router } from '@inertiajs/vue3';

vi.mock('@/layouts/AppLayout.vue', () => ({
    default: {
        name: 'AppLayout',
        template: '<div><slot /></div>',
    },
}));

describe('Deposit', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        global.fetch = vi.fn();
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('should render the deposit form', () => {
        const wrapper = mount(Deposit);

        expect(wrapper.find('h1').text()).toBe('Deposit Funds');
        expect(wrapper.find('form').exists()).toBe(true);
    });

    it('should display form fields with default values', () => {
        const wrapper = mount(Deposit);

        const typeSelect = wrapper.find('#type');
        const amountInput = wrapper.find('#amount');

        expect(typeSelect.element.value).toBe('usd');
        expect(amountInput.element.value).toBe('');
    });

    it('should show symbol selector when asset type is selected', async () => {
        const wrapper = mount(Deposit);

        const typeSelect = wrapper.find('#type');
        await typeSelect.setValue('asset');
        await nextTick();

        const symbolSelect = wrapper.find('#symbol');
        expect(symbolSelect.exists()).toBe(true);
        expect(symbolSelect.element.value).toBe('BTC');
    });

    it('should hide symbol selector when USD type is selected', async () => {
        const wrapper = mount(Deposit);

        const typeSelect = wrapper.find('#type');
        await typeSelect.setValue('asset');
        await nextTick();

        await typeSelect.setValue('usd');
        await nextTick();

        const symbolSelect = wrapper.find('#symbol');
        expect(symbolSelect.exists()).toBe(false);
    });

    it('should validate amount is required', async () => {
        const wrapper = mount(Deposit);
        const form = wrapper.find('form');

        await form.trigger('submit');
        await nextTick();

        expect(global.fetch).not.toHaveBeenCalled();
        expect(wrapper.text()).toContain('Amount must be greater than 0');
    });

    it('should submit USD deposit', async () => {
        (global.fetch as any).mockResolvedValueOnce({
            ok: true,
            json: async () => ({ data: null, message: 'USD balance added successfully' }),
        });

        const wrapper = mount(Deposit);
        const form = wrapper.find('form');

        const amountInput = wrapper.find('#amount');
        await amountInput.setValue('1000');

        await form.trigger('submit');
        await nextTick();

        expect(global.fetch).toHaveBeenCalled();
        const call = (global.fetch as any).mock.calls[0];
        expect(call[0]).toBe('/api/deposit');
        expect(call[1].method).toBe('POST');
        const body = JSON.parse(call[1].body);
        expect(body.type).toBe('usd');
        // Amount can be string or number depending on input type
        expect(['1000', 1000]).toContain(body.amount);
    });

    it('should submit asset deposit with symbol', async () => {
        (global.fetch as any).mockResolvedValueOnce({
            ok: true,
            json: async () => ({ data: null, message: 'BTC added successfully' }),
        });

        const wrapper = mount(Deposit);
        const form = wrapper.find('form');

        const typeSelect = wrapper.find('#type');
        await typeSelect.setValue('asset');
        await nextTick();

        const amountInput = wrapper.find('#amount');
        await amountInput.setValue('1');

        await form.trigger('submit');
        await nextTick();

        expect(global.fetch).toHaveBeenCalled();
        const call = (global.fetch as any).mock.calls[0];
        expect(call[0]).toBe('/api/deposit');
        expect(call[1].method).toBe('POST');
        const body = JSON.parse(call[1].body);
        expect(body.type).toBe('asset');
        expect(body.symbol).toBe('BTC');
        // Amount might be number or string depending on input type
        expect(['1', 1]).toContain(body.amount);
    });

    it('should display error message on failure', async () => {
        (global.fetch as any).mockResolvedValueOnce({
            ok: false,
            json: async () => ({ message: 'Deposit failed' }),
        });

        const wrapper = mount(Deposit);
        const form = wrapper.find('form');

        const amountInput = wrapper.find('#amount');
        await amountInput.setValue('1000');

        await form.trigger('submit');
        await nextTick();

        expect(wrapper.text()).toContain('Deposit failed');
    });

    it('should display success message and redirect', async () => {
        (global.fetch as any).mockResolvedValueOnce({
            ok: true,
            json: async () => ({ data: null, message: 'Deposit successful' }),
        });

        const visitSpy = vi.spyOn(router, 'visit');

        const wrapper = mount(Deposit);
        const form = wrapper.find('form');

        const amountInput = wrapper.find('#amount');
        await amountInput.setValue('1000');

        await form.trigger('submit');
        await nextTick();

        expect(wrapper.text()).toContain('Deposit successful');

        vi.advanceTimersByTime(1000);
        await nextTick();

        expect(visitSpy).toHaveBeenCalledWith('/dashboard');
    });

    it('should disable submit button when loading', async () => {
        (global.fetch as any).mockImplementation(() => new Promise(() => {}));

        const wrapper = mount(Deposit);
        const form = wrapper.find('form');

        const amountInput = wrapper.find('#amount');
        await amountInput.setValue('1000');

        await form.trigger('submit');
        await nextTick();

        const submitButton = wrapper.find('button[type="submit"]');
        expect(submitButton.attributes('disabled')).toBeDefined();
        expect(submitButton.text()).toContain('Processing...');
    });
});

