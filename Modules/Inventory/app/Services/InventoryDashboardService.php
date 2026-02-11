<?php

namespace Modules\Inventory\Services;

use App\Repositories\Division\DivisionRepository;
use Modules\Inventory\Enums\InventoryPermission;
use Modules\Inventory\Repositories\Item\ItemRepository;
use Modules\Inventory\Repositories\ItemTransaction\ItemTransactionRepository;
use Modules\Inventory\Repositories\StockOpname\StockOpnameRepository;
use Modules\Inventory\Repositories\WarehouseOrder\WarehouseOrderRepository;

class InventoryDashboardService
{
    public function __construct(
        private ItemRepository $itemRepository,
        private ItemTransactionRepository $transactionRepository,
        private WarehouseOrderRepository $orderRepository,
        private StockOpnameRepository $opnameRepository,
        private DivisionRepository $divisionRepository
    ) {}

    /**
     * Get inventory dashboard tabs for the authenticated user
     */
    public function getDashboardTabs(): array
    {
        $user = auth()->user();
        $tabs = [];

        // Tab: Gudang Divisi
        if ($user->can(InventoryPermission::ViewDivisionWarehouseDashboard->value) && $user->division_id) {
            $tabs[] = $this->getDivisionWarehouseTab($user);
        }

        // Tab: Gudang Utama
        if ($user->can(InventoryPermission::ViewMainWarehouseDashboard->value)) {
            $tabs[] = $this->getMainWarehouseTab();
        }

        // Tab: Gudang Keseluruhan
        if ($user->can(InventoryPermission::ViewAllWarehouseDashboard->value)) {
            $tabs[] = $this->getAllWarehouseTab();
        }

        return $tabs;
    }

    /**
     * Get division warehouse data
     */
    public function getDivisionWarehouseData($user): array
    {
        $divisionId = $user->division_id;

        return [
            'most_stock_items' => $this->itemRepository->getMostStocked(5, $divisionId),
            'least_stock_items' => $this->itemRepository->getLeastStocked(5, $divisionId),
            'has_stock_opname_this_month' => $this->opnameRepository->hasOpnameThisMonth($divisionId),
            'active_orders' => $this->orderRepository->getActiveOrders($divisionId, 5),
            'recent_transactions' => $this->transactionRepository->getLatestTransactions($divisionId, 5),
            'division_name' => $user->division?->name,
        ];
    }

    /**
     * Get main warehouse data
     */
    public function getMainWarehouseData(): array
    {
        $statistics = $this->orderRepository->getStatusStatistics()
            ->mapWithKeys(function ($item) {
                return [$item->status->value => $item->total];
            });

        return [
            'statistics' => $statistics,
            'pendingOrders' => $this->orderRepository->getPendingOrders(10),
            'confirmedOrders' => $this->orderRepository->getConfirmedOrders(10),
        ];
    }

    /**
     * Get generic dashboard data
     */
    public function getGenericDashboardData(): array
    {
        $statistics = $this->orderRepository->getStatusStatistics()
            ->mapWithKeys(function ($item) {
                return [$item->status->value => $item->total];
            });

        return [
            'statistics' => $statistics,
            'pendingOrders' => $this->orderRepository->getPendingOrders(10),
            'confirmedOrders' => $this->orderRepository->getConfirmedOrders(10),
            'deliveredOrders' => $this->orderRepository->getActiveOrders(null, 10), // Simplification
        ];
    }

    /**
     * Get division warehouse tab data
     */
    private function getDivisionWarehouseTab($user): array
    {
        return [
            'id' => 'division',
            'label' => 'Gudang ' . ($user->division?->name ?? 'Divisi'),
            'icon' => 'building',
            'type' => 'warehouse',
            'stock_opname_link' => '/inventory/stock-opname/division',
            'data' => $this->getDivisionWarehouseData($user),
        ];
    }

    /**
     * Get main warehouse tab data
     */
    private function getMainWarehouseTab(): array
    {
        return [
            'id' => 'main',
            'label' => 'Gudang Utama',
            'icon' => 'warehouse',
            'type' => 'warehouse',
            'stock_opname_link' => '/inventory/stock-opname/warehouse',
            'data' => [
                'most_stock_items' => $this->itemRepository->getMostStocked(5),
                'least_stock_items' => $this->itemRepository->getLeastStocked(5),
                'has_stock_opname_this_month' => $this->opnameRepository->hasOpnameThisMonth(),
                'active_orders' => $this->orderRepository->getActiveOrders(null, 5),
                'recent_transactions' => $this->transactionRepository->getLatestTransactions(null, 5),
            ],
        ];
    }

    /**
     * Get all warehouse tab data (keseluruhan)
     */
    private function getAllWarehouseTab(): array
    {
        // Get all divisions with their stock opname status
        $divisions = $this->divisionRepository->all();
        $stockOpnameStatus = [];

        foreach ($divisions as $division) {
            $stockOpnameStatus[] = [
                'division_id' => $division->id,
                'division_name' => $division->name,
                'has_stock_opname' => $this->opnameRepository->hasOpnameThisMonth($division->id),
            ];
        }

        // Add main warehouse status
        array_unshift($stockOpnameStatus, [
            'division_id' => null,
            'division_name' => 'Gudang Utama',
            'has_stock_opname' => $this->opnameRepository->hasOpnameThisMonth(),
        ]);

        return [
            'id' => 'all',
            'label' => 'Keseluruhan',
            'icon' => 'globe',
            'type' => 'overview',
            'data' => [
                'recent_transactions' => $this->transactionRepository->getLatestTransactions(null, 10),
                'recent_orders' => $this->orderRepository->getActiveOrders(null, 5),
                'stock_opname_status' => $stockOpnameStatus,
            ],
        ];
    }
}
