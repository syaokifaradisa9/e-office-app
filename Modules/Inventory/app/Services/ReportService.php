<?php

namespace Modules\Inventory\Services;

use App\Models\Division;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Enums\WarehouseOrderStatus;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\WarehouseOrder;
use Modules\Inventory\Models\WarehouseOrderCart;
use Modules\Inventory\Models\StockOpnameItem; // Added for opname variance
use Carbon\Carbon;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class ReportService
{
    public function getDivisionReportData(User $user): array
    {
        $divisionId = $user->division_id;

        return [
            'overview_stats' => $this->getOrderStatusStats($divisionId),
            'request_trend' => $this->getMonthlyRequestTrend($divisionId),
            'opname_variance_trend' => $this->getOpnameVarianceTrend($divisionId),
            'item_rankings' => [
                'most_requested' => $this->getItemRequestRankings($divisionId, 'most', 10),
                'least_requested' => $this->getItemRequestRankings($divisionId, 'least', 10),
                'most_outbound' => $this->getItemOutboundRankings($divisionId, 10),
                'opname_variance_minus' => $this->getOpnameVarianceRankings($divisionId, 10),
                'most_stock' => $this->getStockRankings($divisionId, 'most', 10),
                'least_stock' => $this->getStockRankings($divisionId, 'least', 10),
            ],
            'category_rankings' => [
                'most_requested' => $this->getCategoryRankings($divisionId, 'most', 5),
                'least_requested' => $this->getCategoryRankings($divisionId, 'least', 5),
                'most_outbound' => $this->getCategoryOutboundRankings($divisionId, 5),
            ],
            'stock_analysis' => [
                'stagnant_stock' => $this->getStagnantStockAnalysis($divisionId, 3),
            ],
            'alerts' => $this->getAlertsData($divisionId),
        ];
    }

    public function getAllReportData(): array
    {
        $divisions = Division::all();

        return [
            // Laporan Barang (Global / Gudang Utama)
            'global' => [
                'overview_stats' => $this->getOrderStatusStats(null),
                'request_trend' => $this->getMonthlyRequestTrend(null),
                'opname_variance_trend' => $this->getOpnameVarianceTrend(null),
                'item_rankings' => [
                    'most_requested' => $this->getItemRequestRankings(null, 'most', 10),
                    'least_requested' => $this->getItemRequestRankings(null, 'least', 10),
                    'most_outbound' => $this->getItemOutboundRankings(null, 10),
                    'opname_variance_minus' => $this->getOpnameVarianceRankings(null, 10),
                    'most_stock' => $this->getStockRankings(null, 'most', 10),
                    'least_stock' => $this->getStockRankings(null, 'least', 10),
                ],
                'category_rankings' => [
                    'most_requested' => $this->getCategoryRankings(null, 'most', 5),
                    'least_requested' => $this->getCategoryRankings(null, 'least', 5),
                    'most_outbound' => $this->getCategoryOutboundRankings(null, 5),
                ],
                'stock_analysis' => [
                    'stagnant_stock' => $this->getStagnantStockAnalysis(null, 3),
                ],
                'alerts' => $this->getAlertsData(null),
            ],
            // Laporan Barang Divisi
            'per_division' => $divisions->map(function ($division) {
                return [
                    'division_id' => $division->id,
                    'division_name' => $division->name,
                    'request_trend' => $this->getMonthlyRequestTrend($division->id),
                    'opname_variance_trend' => $this->getOpnameVarianceTrend($division->id),
                    'item_rankings' => [
                        'most_requested' => $this->getItemRequestRankings($division->id, 'most', 10),
                        'least_requested' => $this->getItemRequestRankings($division->id, 'least', 10),
                        'most_outbound' => $this->getItemOutboundRankings($division->id, 10),
                        'opname_variance_minus' => $this->getOpnameVarianceRankings($division->id, 10),
                        'most_stock' => $this->getStockRankings($division->id, 'most', 10),
                        'least_stock' => $this->getStockRankings($division->id, 'least', 10),
                    ],
                    'category_rankings' => [
                        'most_requested' => $this->getCategoryRankings($division->id, 'most', 5),
                        'least_requested' => $this->getCategoryRankings($division->id, 'least', 5),
                        'most_outbound' => $this->getCategoryOutboundRankings($division->id, 5),
                    ],
                    'stock_analysis' => [
                        'stagnant_stock' => $this->getStagnantStockAnalysis($division->id, 3),
                    ],
                    'alerts' => $this->getAlertsData($division->id),
                ];
            })->toArray(),
        ];
    }

    private function getOrderStatusStats(?string $divisionId = null)
    {
        $query = WarehouseOrder::query();
        if ($divisionId) {
            $query->where('division_id', $divisionId);
        }

        $stats = $query->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->status->value => $item->total]);

        // Ensure all statuses exist even with 0
        $allStatuses = [
            WarehouseOrderStatus::Pending->value => 0,
            WarehouseOrderStatus::Confirmed->value => 0,
            WarehouseOrderStatus::Accepted->value => 0,
            WarehouseOrderStatus::Delivery->value => 0,
            WarehouseOrderStatus::Delivered->value => 0,
            WarehouseOrderStatus::Finished->value => 0,
            WarehouseOrderStatus::Rejected->value => 0,
            WarehouseOrderStatus::Revision->value => 0,
        ];

        return array_merge($allStatuses, $stats->toArray());
    }

    private function getMonthlyRequestTrend(?string $divisionId)
    {
        $query = WarehouseOrder::query()
            ->leftJoin('warehouse_order_carts', 'warehouse_orders.id', '=', 'warehouse_order_carts.warehouse_order_id');

        if ($divisionId) {
            $query->where('warehouse_orders.division_id', $divisionId);
        }

        $driver = config('database.default');
        $format = $driver === 'sqlite' ? 'strftime("%Y-%m", warehouse_orders.created_at)' : 'DATE_FORMAT(warehouse_orders.created_at, "%Y-%m")';

        return $query->select(
            DB::raw("$format as month"),
            DB::raw('COUNT(DISTINCT warehouse_orders.id) as total_orders'),
            DB::raw('COALESCE(SUM(warehouse_order_carts.quantity), 0) as total_items')
        )
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    private function getItemRequestRankings(?string $divisionId, string $type = 'most', int $limit = 10)
    {
        $query = WarehouseOrderCart::query()
            ->join('warehouse_orders', 'warehouse_order_carts.warehouse_order_id', '=', 'warehouse_orders.id')
            ->join('items', 'warehouse_order_carts.item_id', '=', 'items.id')
            ->select('items.name', DB::raw('SUM(warehouse_order_carts.quantity) as total'))
            ->when($divisionId, fn ($q) => $q->where('warehouse_orders.division_id', $divisionId))
            ->groupBy('items.id', 'items.name');

        if ($type === 'most') {
            $query->orderByDesc('total');
        } else {
            $query->orderBy('total');
        }

        return $query->limit($limit)->get();
    }

    private function getItemOutboundRankings(?string $divisionId, int $limit = 10)
    {
        return WarehouseOrderCart::query()
            ->join('warehouse_orders', 'warehouse_order_carts.warehouse_order_id', '=', 'warehouse_orders.id')
            ->join('items', 'warehouse_order_carts.item_id', '=', 'items.id')
            ->select('items.name', DB::raw('SUM(warehouse_order_carts.quantity) as total'))
            ->where('warehouse_orders.status', WarehouseOrderStatus::Finished)
            ->when($divisionId, fn ($q) => $q->where('warehouse_orders.division_id', $divisionId))
            ->groupBy('items.id', 'items.name')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    private function getOpnameVarianceRankings(?string $divisionId, int $limit = 10)
    {
        return StockOpnameItem::query()
            ->join('stock_opnames', 'stock_opname_items.stock_opname_id', '=', 'stock_opnames.id')
            ->join('items', 'stock_opname_items.item_id', '=', 'items.id')
            ->select('items.name', DB::raw('SUM(physical_stock - system_stock) as total_difference'))
            ->whereRaw('(physical_stock - system_stock) < 0')
            ->when($divisionId, fn ($q) => $q->where('stock_opnames.division_id', $divisionId))
            ->groupBy('items.id', 'items.name')
            ->orderBy('total_difference') // Large negative values first
            ->limit($limit)
            ->get();
    }

    private function getOpnameVarianceTrend(?string $divisionId)
    {
        $driver = config('database.default');
        $format = $driver === 'sqlite' ? 'strftime("%Y-%m", stock_opnames.opname_date)' : 'DATE_FORMAT(stock_opnames.opname_date, "%Y-%m")';

        return StockOpnameItem::query()
            ->join('stock_opnames', 'stock_opname_items.stock_opname_id', '=', 'stock_opnames.id')
            ->select(
                DB::raw("$format as month"),
                DB::raw('ABS(SUM(CASE WHEN (physical_stock - system_stock) < 0 THEN (physical_stock - system_stock) ELSE 0 END)) as total_minus')
            )
            ->when($divisionId, fn ($q) => $q->where('stock_opnames.division_id', $divisionId), fn ($q) => $q->whereNull('stock_opnames.division_id'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    private function getStockRankings(?string $divisionId, string $type = 'most', int $limit = 10)
    {
        $query = Item::query()
            ->select('name', 'stock', 'unit_of_measure')
            ->when($divisionId, fn ($q) => $q->where('division_id', $divisionId), fn ($q) => $q->whereNull('division_id'));

        if ($type === 'most') {
            $query->orderByDesc('stock');
        } else {
            $query->orderBy('stock');
        }

        return $query->limit($limit)->get();
    }

    private function getCategoryRankings(?string $divisionId, string $type = 'most', int $limit = 5)
    {
        $query = WarehouseOrderCart::query()
            ->join('warehouse_orders', 'warehouse_order_carts.warehouse_order_id', '=', 'warehouse_orders.id')
            ->join('items', 'warehouse_order_carts.item_id', '=', 'items.id')
            ->join('category_items', 'items.category_id', '=', 'category_items.id')
            ->select('category_items.name', DB::raw('COUNT(DISTINCT warehouse_orders.id) as total_requests'))
            ->when($divisionId, fn ($q) => $q->where('warehouse_orders.division_id', $divisionId))
            ->groupBy('category_items.id', 'category_items.name');

        if ($type === 'most') {
            $query->orderByDesc('total_requests');
        } else {
            $query->orderBy('total_requests');
        }

        return $query->limit($limit)->get();
    }

    private function getCategoryOutboundRankings(?string $divisionId, int $limit = 5)
    {
        return WarehouseOrderCart::query()
            ->join('warehouse_orders', 'warehouse_order_carts.warehouse_order_id', '=', 'warehouse_orders.id')
            ->join('items', 'warehouse_order_carts.item_id', '=', 'items.id')
            ->join('category_items', 'items.category_id', '=', 'category_items.id')
            ->select('category_items.name', DB::raw('SUM(warehouse_order_carts.quantity) as total_quantity'))
            ->where('warehouse_orders.status', WarehouseOrderStatus::Finished)
            ->when($divisionId, fn ($q) => $q->where('warehouse_orders.division_id', $divisionId))
            ->groupBy('category_items.id', 'category_items.name')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();
    }

    private function getStagnantStockAnalysis(?string $divisionId, int $months = 3)
    {
        $dateLimit = now()->subMonths($months);

        // For global (Gudang Utama): Not REQUESTED in 3 months
        // For division: Not RELEASED/OUTBOUND (Finished) in 3 months
        $isGlobal = is_null($divisionId);

        return Item::query()
            ->select(
                'items.id',
                'items.name', 
                'items.stock', 
                'items.unit_of_measure',
                $isGlobal 
                    // For global: last request date (any order status)
                    ? DB::raw('(SELECT MAX(warehouse_orders.created_at) 
                        FROM warehouse_order_carts 
                        JOIN warehouse_orders ON warehouse_order_carts.warehouse_order_id = warehouse_orders.id 
                        WHERE warehouse_order_carts.item_id = items.id 
                    ) as last_activity_date')
                    // For division: last outbound date (Finished orders only)
                    : DB::raw('(SELECT MAX(warehouse_orders.created_at) 
                        FROM warehouse_order_carts 
                        JOIN warehouse_orders ON warehouse_order_carts.warehouse_order_id = warehouse_orders.id 
                        WHERE warehouse_order_carts.item_id = items.id 
                        AND warehouse_orders.status = "' . WarehouseOrderStatus::Finished->value . '"
                    ) as last_activity_date')
            )
            ->where('items.stock', '>', 0)
            ->when($divisionId, fn ($q) => $q->where('items.division_id', $divisionId), fn ($q) => $q->whereNull('items.division_id'))
            // Exclude items created less than 3 months ago (give new items time before considering stagnant)
            ->where('items.created_at', '<', $dateLimit)
            ->whereNotExists(function ($query) use ($dateLimit, $isGlobal) {
                $query->select(DB::raw(1))
                    ->from('warehouse_order_carts')
                    ->join('warehouse_orders', 'warehouse_order_carts.warehouse_order_id', '=', 'warehouse_orders.id')
                    ->whereRaw('warehouse_order_carts.item_id = items.id')
                    ->where('warehouse_orders.created_at', '>=', $dateLimit);
                
                // For division only: filter by Finished status (outbound)
                if (!$isGlobal) {
                    $query->where('warehouse_orders.status', WarehouseOrderStatus::Finished);
                }
            })
            ->limit(10)
            ->get();
    }

    private function getAlertsData(?string $divisionId = null): array
    {
        return [
            'critical_stock' => $this->getCriticalStock($divisionId),
            'fulfillment_rate' => $this->getFulfillmentRate($divisionId),
        ];
    }

    private function getCriticalStock(?string $divisionId)
    {
        $query = Item::where('stock', '>', 0)
            ->where('stock', '<=', 10);

        if ($divisionId) {
            $query->where('division_id', $divisionId);
        } else {
            $query->whereNull('division_id');
        }

        return $query->with('category')->limit(10)->get();
    }

    private function getFulfillmentRate(?string $divisionId): array
    {
        $query = WarehouseOrder::query();

        if ($divisionId) {
            $query->where('division_id', $divisionId);
        }

        $totalOrders = (clone $query)->count();
        $finishedOrders = (clone $query)->where('status', WarehouseOrderStatus::Finished)->count();
        $pendingOrders = (clone $query)->where('status', WarehouseOrderStatus::Pending)->count();
        $deliveredOrders = (clone $query)->whereIn('status', [
            WarehouseOrderStatus::Delivered,
            WarehouseOrderStatus::Delivery,
        ])->count();

        $fulfillmentRate = $totalOrders > 0 ? round(($finishedOrders / $totalOrders) * 100, 2) : 0;

        return [
            'total_orders' => $totalOrders,
            'finished_orders' => $finishedOrders,
            'pending_orders' => $pendingOrders,
            'delivered_orders' => $deliveredOrders,
            'fulfillment_rate' => $fulfillmentRate,
        ];
    }

    public function printExcel(User $user)
    {
        $data = $this->getAllReportData();
        $global = $data['global'];
        $perDivision = $data['per_division'];

        return response()->streamDownload(function () use ($global, $perDivision) {
            $writer = new Writer();
            $writer->openToFile('php://output');

            // 1. Overview Sheet
            $writer->getCurrentSheet()->setName('Ringkasan Global');
            
            $writer->addRow(Row::fromValues(['LAPORAN INVENTORY GLOBAL - ' . Carbon::now()->format('d F Y')]));
            $writer->addRow(Row::fromValues(['']));
            
            $writer->addRow(Row::fromValues(['Statistik Permintaan Barang (Seluruh Sistem)']));
            $writer->addRow(Row::fromValues([
                'Status',
                'Jumlah Order'
            ]));
            
            $statusLabels = [
                'Pending' => 'Menunggu',
                'Confirmed' => 'Dikonfirmasi',
                'Accepted' => 'Diproses',
                'Delivery' => 'Dikirim',
                'Delivered' => 'Sampai',
                'Finished' => 'Selesai',
                'Rejected' => 'Ditolak',
                'Revision' => 'Revisi',
            ];

            foreach ($global['overview_stats'] as $status => $count) {
                $writer->addRow(Row::fromValues([
                    $statusLabels[$status] ?? $status,
                    $count
                ]));
            }

            $writer->addRow(Row::fromValues(['']));
            $writer->addRow(Row::fromValues(['Tren Permintaan (12 Bulan Terakhir)']));
            $writer->addRow(Row::fromValues(['Bulan', 'Total Order', 'Total Barang']));
            foreach ($global['request_trend'] as $trend) {
                $writer->addRow(Row::fromValues([
                    $trend['month'],
                    $trend['total_orders'],
                    $trend['total_items']
                ]));
            }

            // 2. Rankings Sheet
            $newSheet = $writer->addNewSheetAndMakeItCurrent();
            $newSheet->setName('Peringkat & Analisis');

            $writer->addRow(Row::fromValues(['ANALISIS PERINGKAT BARANG & KATEGORI']));
            $writer->addRow(Row::fromValues(['']));

            $writer->addRow(Row::fromValues(['10 Barang Paling Banyak Diminta']));
            $writer->addRow(Row::fromValues(['Nama Barang', 'Total Permintaan']));
            foreach ($global['item_rankings']['most_requested'] as $item) {
                $writer->addRow(Row::fromValues([$item->name, $item->total]));
            }

            $writer->addRow(Row::fromValues(['']));
            $writer->addRow(Row::fromValues(['10 Barang Paling Banyak Keluar (Outbound)']));
            $writer->addRow(Row::fromValues(['Nama Barang', 'Total Keluar']));
            foreach ($global['item_rankings']['most_outbound'] as $item) {
                $writer->addRow(Row::fromValues([$item->name, $item->total]));
            }

            $writer->addRow(Row::fromValues(['']));
            $writer->addRow(Row::fromValues(['Barang Terancam Habis (Stok \u003c Critical)']));
            $writer->addRow(Row::fromValues(['Nama Barang', 'Stok Saat Ini', 'Critical Level']));
            foreach ($global['alerts']['critical_stock'] as $item) {
                $writer->addRow(Row::fromValues([$item->name, $item->stock, $item->critical_stock ?? '-']));
            }

            $writer->addRow(Row::fromValues(['']));
            $writer->addRow(Row::fromValues(['Stok Tertimbun (Tidak Ada Aktivitas \u003e 3 Bulan)']));
            $writer->addRow(Row::fromValues(['Nama Barang', 'Stok Tersisa']));
            foreach ($global['stock_analysis']['stagnant_stock'] as $item) {
                $writer->addRow(Row::fromValues([$item->name, $item->stock]));
            }

            // 3. Per Division Summary
            $newSheet = $writer->addNewSheetAndMakeItCurrent();
            $newSheet->setName('Ringkasan Per Divisi');

            $writer->addRow(Row::fromValues(['RINGKASAN PERMINTAAN PER DIVISI']));
            $writer->addRow(Row::fromValues(['']));
            $writer->addRow(Row::fromValues([
                'Nama Divisi',
                'Total Order Selesai',
                'Total Barang Keluar',
                'Stok Kritis',
                'Fulfillment Rate (%)'
            ]));

            foreach ($perDivision as $div) {
                $writer->addRow(Row::fromValues([
                    $div['division_name'],
                    $div['overview_stats']['Finished'] ?? 0,
                    collect($div['item_rankings']['most_outbound'])->sum('total'),
                    count($div['alerts']['critical_stock']),
                    $div['alerts']['fulfillment_rate']['fulfillment_rate'] . '%'
                ]));
            }

            $writer->close();
        }, 'Laporan_Inventory_Komprehensif_' . date('Ymd_His') . '.xlsx');
    }
}
