<?php

namespace App\Services;

use App\Events\OrderMatched;
use App\Models\Order;
use App\Models\Trade;
use App\Repositories\AssetRepositoryInterface;
use App\Repositories\OrderRepositoryInterface;
use App\Repositories\TradeRepositoryInterface;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class MatchingService implements MatchingServiceInterface
{
    private const COMMISSION_RATE = 0.015;

    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly AssetRepositoryInterface $assetRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly TradeRepositoryInterface $tradeRepository,
    ) {
    }

    public function matchOrder(Order $order): ?Trade
    {
        return DB::transaction(function () use ($order) {
            $matchingOrder = $this->findMatchingOrder($order);

            if ($matchingOrder === null) {
                return null;
            }

            $trade = $this->executeTrade($order, $matchingOrder);
            $order->refresh();
            
            return $trade;
        });
    }

    private function findMatchingOrder(Order $order): ?Order
    {
        if ($order->isBuy()) {
            return $this->orderRepository->findMatchingSellOrder($order->symbol, $order->price);
        }

        return $this->orderRepository->findMatchingBuyOrder($order->symbol, $order->price);
    }

    private function executeTrade(Order $buyOrder, Order $sellOrder): Trade
    {
        if ($buyOrder->isSell()) {
            [$buyOrder, $sellOrder] = [$sellOrder, $buyOrder];
        }

        $buyOrder->lockForUpdate();
        $sellOrder->lockForUpdate();
        $buyOrder->refresh();
        $sellOrder->refresh();

        if ($buyOrder->status !== Order::STATUS_OPEN || $sellOrder->status !== Order::STATUS_OPEN) {
            throw new \RuntimeException('One or both orders are no longer open');
        }

        $sellerAsset = $this->assetRepository->findByUserAndSymbol($sellOrder->user_id, $sellOrder->symbol);
        if ($sellerAsset !== null) {
            $sellerAsset->lockForUpdate();
            $sellerAsset->refresh();
            $requiredAmount = $buyOrder->amount;
            
            throw_if(
                bccomp((string) $sellerAsset->locked_amount, $requiredAmount, 8) < 0,
                \RuntimeException::class,
                'Seller does not have sufficient locked asset for trade'
            );
            
            throw_if(
                bccomp((string) $sellerAsset->locked_amount, (string) $sellerAsset->amount, 8) > 0,
                \RuntimeException::class,
                'Invalid asset state: locked_amount exceeds total amount'
            );
            
            $newLockedAmount = bcsub((string) $sellerAsset->locked_amount, $requiredAmount, 8);
            throw_if(
                bccomp($newLockedAmount, '0', 8) < 0,
                \RuntimeException::class,
                'Invalid asset state: locked_amount would become negative'
            );
        }

        $price = $sellOrder->price;
        $amount = $buyOrder->amount;

        $usdValue = bcmul($price, $amount, 8);
        $commission = bcmul($usdValue, (string) self::COMMISSION_RATE, 8);
        $sellerUsdProceeds = bcsub($usdValue, $commission, 8);

        $buyer = $this->userRepository->findById($buyOrder->user_id);
        if ($buyer !== null) {
            $buyerAsset = $this->assetRepository->findOrCreateByUserAndSymbol($buyOrder->user_id, $buyOrder->symbol);
            $this->assetRepository->addAmount($buyerAsset, $amount);
        }

        $seller = $this->userRepository->findById($sellOrder->user_id);
        if ($seller !== null) {
            $seller->lockForUpdate();
            $seller->refresh();
            
            if ($sellerAsset !== null) {
                $this->assetRepository->unlockAmount($sellerAsset, $amount);
                $this->assetRepository->subtractAmount($sellerAsset, $amount);
            }
            $this->userRepository->addBalance($seller, $sellerUsdProceeds);
        }

        $this->orderRepository->updateStatus($buyOrder, Order::STATUS_FILLED);
        $this->orderRepository->updateStatus($sellOrder, Order::STATUS_FILLED);
        $buyOrder->refresh();
        $sellOrder->refresh();

        $trade = $this->tradeRepository->create([
            'buy_order_id' => $buyOrder->id,
            'sell_order_id' => $sellOrder->id,
            'symbol' => $buyOrder->symbol,
            'price' => $price,
            'amount' => $amount,
            'commission' => $commission,
            'buyer_id' => $buyOrder->user_id,
            'seller_id' => $sellOrder->user_id,
        ]);

        event(new OrderMatched($buyOrder, $sellOrder, $trade));

        return $trade;
    }
}

