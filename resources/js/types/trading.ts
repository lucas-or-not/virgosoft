export interface Order {
    id: number;
    symbol: string;
    side: 'buy' | 'sell';
    price: string;
    amount: string;
    status: number; // 1=open, 2=filled, 3=cancelled
    created_at: string;
    updated_at: string;
}

export interface Asset {
    symbol: string;
    amount: string;
    locked_amount: string;
    available_amount: string;
}

export interface Profile {
    balance: string;
    assets: Asset[];
}

export interface OrderbookEntry {
    price: string;
    amount: string;
    side: 'buy' | 'sell';
    symbol?: string;
}

export interface Orderbook {
    buy: OrderbookEntry[];
    sell: OrderbookEntry[];
}

export interface CreateOrderData {
    symbol: string;
    side: 'buy' | 'sell';
    price: string;
    amount: string;
}

export interface OrderMatchedEvent {
    buy_order: {
        id: number;
        symbol: string;
        side: string;
        price: string;
        amount: string;
        status: number;
    };
    sell_order: {
        id: number;
        symbol: string;
        side: string;
        price: string;
        amount: string;
        status: number;
    };
    trade: {
        id: number;
        symbol: string;
        price: string;
        amount: string;
        commission: string;
    } | null;
}

