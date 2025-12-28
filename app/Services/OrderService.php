<?php

namespace App\Services;

use App\DTOs\CreateOrderDto;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Exceptions\InsufficientAssetException;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\OrderNotCancellableException;
use App\Models\Order;
use App\Repositories\AssetRepositoryInterface;
use App\Repositories\OrderRepositoryInterface;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly AssetRepositoryInterface $assetRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly MatchingServiceInterface $matchingService,
    ) {
    }

    public function createOrder(CreateOrderDto $dto): Order
    {
        return DB::transaction(function () use ($dto) {
            if ($dto->side === Order::SIDE_BUY) {
                $this->validateAndLockBuyOrder($dto);
            } else {
                $this->validateAndLockSellOrder($dto);
            }

            $order = $this->orderRepository->create([
                'user_id' => $dto->userId,
                'symbol' => $dto->symbol,
                'side' => $dto->side,
                'price' => $dto->price,
                'amount' => $dto->amount,
                'status' => Order::STATUS_OPEN,
                'locked_usd' => $dto->side === Order::SIDE_BUY ? bcmul($dto->price, $dto->amount, 8) : '0',
            ]);

            $this->matchingService->matchOrder($order);
            $order->refresh();

            if ($order->status === Order::STATUS_OPEN) {
                event(new OrderCreated($order));
            }

            return $order;
        });
    }

    public function cancelOrder(int $orderId, int $userId): void
    {
        DB::transaction(function () use ($orderId, $userId) {
            $order = $this->orderRepository->findByIdOrFail($orderId);
            $order->lockForUpdate();
            $order->refresh();

            throw_if($order->user_id !== $userId, \RuntimeException::class, 'Order does not belong to user');

            throw_if(
                $order->status !== Order::STATUS_OPEN,
                OrderNotCancellableException::class
            );

            if ($order->isBuy()) {
                $this->unlockBuyOrderFunds($order);
            } else {
                $this->unlockSellOrderAssets($order);
            }

            $this->orderRepository->updateStatus($order, Order::STATUS_CANCELLED);
            
            // Refresh order to get updated status
            $order->refresh();
            event(new OrderCancelled($order));
        });
    }

    private function validateAndLockBuyOrder(CreateOrderDto $dto): void
    {
        $user = $this->userRepository->findById($dto->userId);
        throw_if($user === null, \RuntimeException::class, 'User not found');

        $requiredUsd = bcmul($dto->price, $dto->amount, 8);

        $user->lockForUpdate();
        $user->refresh();

        $userBalance = (string) $user->balance;

        throw_if(
            bccomp($userBalance, $requiredUsd, 8) < 0,
            InsufficientBalanceException::class
        );

        $this->userRepository->lockBalance($user, $requiredUsd);
    }

    private function validateAndLockSellOrder(CreateOrderDto $dto): void
    {
        $asset = $this->assetRepository->findOrCreateByUserAndSymbol($dto->userId, $dto->symbol);
        $asset->refresh();

        $availableAmount = bcsub((string) $asset->amount, (string) $asset->locked_amount, 8);

        throw_if(
            bccomp($availableAmount, $dto->amount, 8) < 0,
            InsufficientAssetException::class
        );

        $this->assetRepository->lockAmount($asset, $dto->amount);
    }

    private function unlockBuyOrderFunds(Order $order): void
    {
        $user = $this->userRepository->findById($order->user_id);
        if ($user !== null) {
            $user->lockForUpdate();
            $user->refresh();
            $this->userRepository->unlockBalance($user, $order->locked_usd);
        }
    }

    private function unlockSellOrderAssets(Order $order): void
    {
        $asset = $this->assetRepository->findByUserAndSymbol($order->user_id, $order->symbol);
        if ($asset !== null) {
            $asset->lockForUpdate();
            $asset->refresh();
            $this->assetRepository->unlockAmount($asset, $order->amount);
        }
    }
}

