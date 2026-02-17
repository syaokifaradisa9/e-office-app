<?php

namespace Modules\Inventory\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\DataTransferObjects\WarehouseOrderDTO;
use Modules\Inventory\Enums\InventoryPermission;
use Modules\Inventory\Enums\ItemTransactionType;
use Modules\Inventory\Enums\WarehouseOrderStatus;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\WarehouseOrder;
use Modules\Inventory\Models\WarehouseOrderReject;
use Modules\Inventory\Repositories\Item\ItemRepository;
use Modules\Inventory\Repositories\ItemTransaction\ItemTransactionRepository;
use Modules\Inventory\Repositories\WarehouseOrder\WarehouseOrderRepository;

class WarehouseOrderService
{
    public function __construct(
        private WarehouseOrderRepository $orderRepository,
        private ItemRepository $itemRepository,
        private ItemTransactionRepository $transactionRepository
    ) {}

    public function store(WarehouseOrderDTO $dto, User $user): WarehouseOrder
    {
        return DB::transaction(function () use ($dto, $user) {
            $order = $this->orderRepository->create([
                'user_id' => $user->id,
                'division_id' => $dto->division_id,
                'order_number' => $this->orderRepository->generateOrderNumber(),
                'description' => $dto->description,
                'notes' => $dto->notes,
                'status' => WarehouseOrderStatus::Pending,
            ]);

            foreach ($dto->items as $itemData) {
                $order->carts()->create([
                    'item_id' => $itemData['item_id'],
                    'quantity' => $itemData['quantity'],
                ]);

                // Decrement stock immediately
                $masterItem = $this->itemRepository->findById($itemData['item_id']);
                if ($masterItem) {
                    $this->itemRepository->update($masterItem, [
                        'stock' => $masterItem->stock - $itemData['quantity']
                    ]);
                }
            }

            return $order;
        });
    }

    public function update(WarehouseOrder $order, array $data): WarehouseOrder
    {
        return DB::transaction(function () use ($order, $data) {
            // Restore stock for old items (only for Pending/Revision, NOT Rejected)
            if (in_array($order->status, [WarehouseOrderStatus::Pending, WarehouseOrderStatus::Revision])) {
                foreach ($order->carts as $cart) {
                    $item = $cart->item;
                    if ($item) {
                        $this->itemRepository->update($item, [
                            'stock' => $item->stock + $cart->quantity
                        ]);
                    }
                }
            }

            // If order was rejected, change status to Revision
            $updateData = [
                'description' => $data['description'] ?? null,
                'notes' => $data['notes'] ?? null,
            ];

            if ($order->status === WarehouseOrderStatus::Rejected) {
                $updateData['status'] = WarehouseOrderStatus::Revision;
            }

            $order = $this->orderRepository->update($order, $updateData);

            $order->carts()->delete();
            foreach ($data['items'] as $itemData) {
                $order->carts()->create([
                    'item_id' => $itemData['item_id'],
                    'quantity' => $itemData['quantity'],
                ]);

                // Decrement stock for new items
                $masterItem = $this->itemRepository->findById($itemData['item_id']);
                if ($masterItem) {
                    $this->itemRepository->update($masterItem, [
                        'stock' => $masterItem->stock - $itemData['quantity']
                    ]);
                }
            }

            return $order->fresh();
        });
    }

    public function delete(WarehouseOrder $order): bool
    {
        return DB::transaction(function () use ($order) {
            // Restore stock before deleting
            foreach ($order->carts as $cart) {
                $item = $cart->item;
                if ($item) {
                    $this->itemRepository->update($item, [
                        'stock' => $item->stock + $cart->quantity
                    ]);
                }
            }

            $order->carts()->delete();

            return $order->delete();
        });
    }

    public function confirm(WarehouseOrder $order): WarehouseOrder
    {
        return $this->orderRepository->update($order, [
            'status' => WarehouseOrderStatus::Confirmed,
            'accepted_date' => now(),
        ]);
    }

    public function reject(WarehouseOrder $order, string $reason, User $user): WarehouseOrder
    {
        return DB::transaction(function () use ($order, $reason, $user) {
            WarehouseOrderReject::create([
                'warehouse_order_id' => $order->id,
                'user_id' => $user->id,
                'reason' => $reason,
            ]);

            $order = $this->orderRepository->update($order, [
                'status' => WarehouseOrderStatus::Rejected,
            ]);

            // Restore stock
            foreach ($order->carts as $cart) {
                $item = $cart->item;
                if ($item) {
                    $this->itemRepository->update($item, [
                        'stock' => $item->stock + $cart->quantity
                    ]);
                }
            }

            return $order;
        });
    }

    public function deliver(WarehouseOrder $order, array $data, User $user): WarehouseOrder
    {
        return DB::transaction(function () use ($order, $data, $user) {
            $order->loadMissing('carts.item');

            $imagePaths = [];

            foreach ($data['delivery_images'] as $image) {
                $path = $image->store('WarehouseOrder/'.($order->order_number ?? 'unknown').'/Delivered', 'public');
                $imagePaths[] = '/storage/'.$path;
            }

            $order = $this->orderRepository->update($order, [
                'status' => WarehouseOrderStatus::Delivered,
                'delivery_date' => $data['delivery_date'],
                'delivered_by' => $user->id,
                'delivery_images' => $imagePaths,
            ]);

            foreach ($order->carts as $cart) {
                $cart->update(['delivered_quantity' => $cart->quantity]);

                // Create transaction for delivered items
                $this->transactionRepository->create([
                    'date' => $data['delivery_date'],
                    'type' => ItemTransactionType::Out,
                    'item_id' => $cart->item_id,
                    'quantity' => $cart->quantity,
                    'user_id' => $user->id,
                    'description' => 'Penyerahan barang - Order #'.$order->order_number,
                ]);
            }

            return $order;
        });
    }

    public function receive(WarehouseOrder $order, string $receiptDate, array $receiptImages, User $user): WarehouseOrder
    {
        return DB::transaction(function () use ($order, $receiptDate, $receiptImages, $user) {
            $order->loadMissing('carts.item');

            $imagePaths = [];

            foreach ($receiptImages as $image) {
                $path = $image->store('WarehouseOrder/'.($order->order_number ?? 'unknown').'/Received', 'public');
                $imagePaths[] = '/storage/'.$path;
            }

            $order = $this->orderRepository->update($order, [
                'status' => WarehouseOrderStatus::Finished,
                'receipt_date' => $receiptDate,
                'received_by' => $user->id,
                'receipt_images' => $imagePaths,
            ]);

            foreach ($order->carts as $cart) {
                $receivedQuantity = $cart->delivered_quantity ?? $cart->quantity;
                $cart->update(['received_quantity' => $receivedQuantity]);

                // Find or create division item
                $masterItem = $cart->item;
                if ($masterItem) {
                    $divisionItem = Item::where('division_id', $order->division_id)
                        ->where('main_reference_item_id', $masterItem->id)
                        ->first();

                    if ($divisionItem) {
                        $this->itemRepository->update($divisionItem, [
                            'stock' => $divisionItem->stock + $receivedQuantity
                        ]);
                    } else {
                        $divisionItem = $this->itemRepository->create([
                            'division_id' => $order->division_id,
                            'category_id' => $masterItem->category_id,
                            'name' => $masterItem->name,
                            'unit_of_measure' => $masterItem->unit_of_measure,
                            'stock' => $receivedQuantity,
                            'description' => $masterItem->description,
                            'multiplier' => $masterItem->multiplier ?? 1,
                            'main_reference_item_id' => $masterItem->id,
                        ]);
                    }

                    // Create transaction for received items
                    $this->transactionRepository->create([
                        'date' => $receiptDate,
                        'type' => ItemTransactionType::In,
                        'item_id' => $divisionItem->id,
                        'quantity' => $receivedQuantity,
                        'user_id' => $user->id,
                        'description' => 'Penerimaan barang - Order #'.$order->order_number,
                    ]);

                    // --- HIERARCHY SYNCHRONIZATION ---
                    $this->syncHierarchy($order->division_id, $masterItem, $divisionItem);
                }
            }

            return $order;
        });
    }

    private function syncHierarchy(int $divisionId, Item $masterItem, Item $divisionItem): void
    {
        // case A: Box to Pcs
        if ($masterItem->multiplier > 1 && $masterItem->reference_item_id && ! $divisionItem->reference_item_id) {
            $divisionPcsItem = Item::where('division_id', $divisionId)
                ->where('main_reference_item_id', $masterItem->reference_item_id)
                ->first();

            if ($divisionPcsItem) {
                $this->itemRepository->update($divisionItem, ['reference_item_id' => $divisionPcsItem->id]);
            }
        }

        // case B: Pcs to Box
        if ($masterItem->multiplier == 1) {
            $masterBoxItems = Item::where('reference_item_id', $masterItem->id)->get();

            foreach ($masterBoxItems as $masterBox) {
                $divisionBoxItem = Item::where('division_id', $divisionId)
                    ->where('main_reference_item_id', $masterBox->id)
                    ->first();

                if ($divisionBoxItem) {
                    $this->itemRepository->update($divisionBoxItem, ['reference_item_id' => $divisionItem->id]);
                }
            }
        }
    }

    public function getType(WarehouseOrder $order): string
    {
        return $order->division_id ? 'division' : 'warehouse';
    }

    public function canEdit(WarehouseOrder $order): bool
    {
        return in_array($order->status, [
            WarehouseOrderStatus::Pending,
            WarehouseOrderStatus::Revision,
            WarehouseOrderStatus::Rejected,
        ]);
    }

    public function canConfirm(WarehouseOrder $order): bool
    {
        return in_array($order->status, [
            WarehouseOrderStatus::Pending,
            WarehouseOrderStatus::Revision,
        ]);
    }

    public function canDeliver(WarehouseOrder $order): bool
    {
        return $order->status === WarehouseOrderStatus::Confirmed;
    }

    public function canReceive(WarehouseOrder $order, User $user): bool
    {
        if ($order->status !== WarehouseOrderStatus::Delivered) {
            return false;
        }

        return $order->user_id === $user->id || $order->division_id === $user->division_id;
    }

    public function canView(WarehouseOrder $order, User $user): bool
    {
        if ($user->can(InventoryPermission::ViewAllWarehouseOrder->value)) {
            return true;
        }

        if ($user->can(InventoryPermission::ViewWarehouseOrderDivisi->value)) {
            return $order->division_id === $user->division_id;
        }

        return $order->user_id === $user->id;
    }
}
