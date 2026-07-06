<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanupLegacyPurchaseDataCommand extends Command
{
    protected $signature = 'linvy:cleanup-legacy-purchase-data';

    protected $description = 'Clean up legacy purchase documents so company, branch, and receiving warehouse data are usable.';

    private int $skipped = 0;

    private int $warnings = 0;

    public function handle(): int
    {
        $this->info('Legacy Purchase Data Cleanup Started');
        $this->newLine();

        $this->line('Purchase Requests missing company/branch: '.$this->countMissingCompanyBranch('purchase_requests'));
        $this->line('Purchase Orders missing company/branch: '.$this->countMissingCompanyBranch('purchase_orders'));
        $this->line('Receivings missing company/branch: '.$this->countMissingCompanyBranch('receivings'));
        $this->line('Receiving Lines missing warehouse: '.$this->countMissingWarehouse('receiving_lines'));
        $this->newLine();

        $counts = DB::transaction(function (): array {
            $defaultCompanyId = $this->getDefaultCompanyId();
            $defaultBranchId = $this->getDefaultBranchId($defaultCompanyId);

            return [
                'purchase_requests' => $this->cleanupPurchaseRequests($defaultCompanyId, $defaultBranchId),
                'purchase_orders' => $this->cleanupPurchaseOrders($defaultCompanyId, $defaultBranchId),
                'receivings' => $this->cleanupReceivings($defaultCompanyId, $defaultBranchId),
                'receiving_lines' => $this->cleanupReceivingLines(),
            ];
        });

        $this->line('Purchase Requests fixed: '.$counts['purchase_requests']);
        $this->line('Purchase Orders fixed: '.$counts['purchase_orders']);
        $this->line('Receivings fixed: '.$counts['receivings']);
        $this->line('Receiving Lines fixed: '.$counts['receiving_lines']);
        $this->line('Skipped: '.$this->skipped);
        $this->line('Warnings: '.$this->warnings);
        $this->newLine();
        $this->info('Legacy Purchase Data Cleanup Completed');

        return self::SUCCESS;
    }

    private function cleanupPurchaseRequests(?int $defaultCompanyId, ?int $defaultBranchId): int
    {
        if (! $this->hasColumns('purchase_requests', ['company_id', 'branch_id'])) {
            return 0;
        }

        $fixed = 0;

        DB::table('purchase_requests')
            ->whereNull('company_id')
            ->orWhereNull('branch_id')
            ->orderBy('id')
            ->each(function (object $record) use ($defaultCompanyId, $defaultBranchId, &$fixed): void {
                $updates = [];

                if ($record->company_id === null && $defaultCompanyId) {
                    $updates['company_id'] = $defaultCompanyId;
                }

                if ($record->branch_id === null && $defaultBranchId) {
                    $updates['branch_id'] = $defaultBranchId;
                }

                $fixed += $this->updateOrSkip('purchase_requests', $record->id, $updates);
            });

        return $fixed;
    }

    private function cleanupPurchaseOrders(?int $defaultCompanyId, ?int $defaultBranchId): int
    {
        if (! $this->hasColumns('purchase_orders', ['company_id', 'branch_id'])) {
            return 0;
        }

        $fixed = 0;

        DB::table('purchase_orders')
            ->leftJoin('purchase_requests', 'purchase_orders.purchase_request_id', '=', 'purchase_requests.id')
            ->select('purchase_orders.*', 'purchase_requests.company_id as pr_company_id', 'purchase_requests.branch_id as pr_branch_id')
            ->where(function ($query): void {
                $query->whereNull('purchase_orders.company_id')
                    ->orWhereNull('purchase_orders.branch_id');
            })
            ->orderBy('purchase_orders.id')
            ->each(function (object $record) use ($defaultCompanyId, $defaultBranchId, &$fixed): void {
                $updates = [];

                if ($record->company_id === null) {
                    $companyId = $record->pr_company_id ?: $defaultCompanyId;
                    if ($companyId) {
                        $updates['company_id'] = $companyId;
                    }
                }

                if ($record->branch_id === null) {
                    $branchId = $record->pr_branch_id ?: $defaultBranchId;
                    if ($branchId) {
                        $updates['branch_id'] = $branchId;
                    }
                }

                $fixed += $this->updateOrSkip('purchase_orders', $record->id, $updates);
            });

        return $fixed;
    }

    private function cleanupReceivings(?int $defaultCompanyId, ?int $defaultBranchId): int
    {
        if (! $this->hasColumns('receivings', ['company_id', 'branch_id'])) {
            return 0;
        }

        $fixed = 0;

        DB::table('receivings')
            ->leftJoin('purchase_orders', 'receivings.purchase_order_id', '=', 'purchase_orders.id')
            ->select('receivings.*', 'purchase_orders.company_id as po_company_id', 'purchase_orders.branch_id as po_branch_id')
            ->where(function ($query): void {
                $query->whereNull('receivings.company_id')
                    ->orWhereNull('receivings.branch_id');
            })
            ->orderBy('receivings.id')
            ->each(function (object $record) use ($defaultCompanyId, $defaultBranchId, &$fixed): void {
                $updates = [];

                if ($record->company_id === null) {
                    $companyId = $record->po_company_id ?: $defaultCompanyId;
                    if ($companyId) {
                        $updates['company_id'] = $companyId;
                    }
                }

                if ($record->branch_id === null) {
                    $branchId = $record->po_branch_id ?: $defaultBranchId;
                    if ($branchId) {
                        $updates['branch_id'] = $branchId;
                    }
                }

                $fixed += $this->updateOrSkip('receivings', $record->id, $updates);
            });

        return $fixed;
    }

    private function cleanupReceivingLines(): int
    {
        if (! $this->hasColumns('receiving_lines', ['warehouse_id'])) {
            return 0;
        }

        $fixed = 0;
        $receivingsHasWarehouse = Schema::hasColumn('receivings', 'warehouse_id');

        $query = DB::table('receiving_lines')
            ->join('receivings', 'receiving_lines.receiving_id', '=', 'receivings.id')
            ->leftJoin('items', 'receiving_lines.item_id', '=', 'items.id')
            ->select(
                'receiving_lines.id',
                'receiving_lines.item_id',
                'receivings.branch_id as receiving_branch_id',
                'items.default_warehouse_type_id'
            )
            ->whereNull('receiving_lines.warehouse_id')
            ->orderBy('receiving_lines.id');

        if ($receivingsHasWarehouse) {
            $query->addSelect('receivings.warehouse_id as receiving_warehouse_id');
        }

        $query->each(function (object $line) use ($receivingsHasWarehouse, &$fixed): void {
            $fallbackWarehouseId = $receivingsHasWarehouse ? ($line->receiving_warehouse_id ?? null) : null;
            $warehouseId = $this->resolveReceivingLineWarehouseId(
                $line->receiving_branch_id,
                $line->default_warehouse_type_id,
                $fallbackWarehouseId
            );

            if (! $warehouseId) {
                $this->skipped++;

                return;
            }

            $fixed += $this->updateOrSkip('receiving_lines', $line->id, [
                'warehouse_id' => $warehouseId,
            ]);
        });

        return $fixed;
    }

    private function getDefaultCompanyId(): ?int
    {
        if (! Schema::hasTable('companies')) {
            $this->warning('Table companies does not exist. Company cleanup will be skipped.');

            return null;
        }

        $companyId = DB::table('companies')->orderBy('id')->value('id');

        if (! $companyId) {
            $this->warning('No company found. Company cleanup will be skipped.');

            return null;
        }

        return (int) $companyId;
    }

    private function getDefaultBranchId(?int $companyId = null): ?int
    {
        if (! Schema::hasTable('branches')) {
            $this->warning('Table branches does not exist. Branch cleanup will be skipped.');

            return null;
        }

        if ($companyId && Schema::hasColumn('branches', 'company_id')) {
            $branchId = DB::table('branches')
                ->where('company_id', $companyId)
                ->orderBy('id')
                ->value('id');

            if ($branchId) {
                return (int) $branchId;
            }
        }

        $branchId = DB::table('branches')->orderBy('id')->value('id');

        if (! $branchId) {
            $this->warning('No branch found. Branch cleanup will be skipped.');

            return null;
        }

        return (int) $branchId;
    }

    private function resolveReceivingLineWarehouseId(?int $branchId, ?int $warehouseTypeId = null, ?int $fallbackWarehouseId = null): ?int
    {
        if ($fallbackWarehouseId) {
            return $fallbackWarehouseId;
        }

        if (! $branchId) {
            $this->warning('Receiving line warehouse skipped because receiving branch is missing.');

            return null;
        }

        if ($warehouseTypeId) {
            $warehouseId = DB::table('warehouses')
                ->where('branch_id', $branchId)
                ->where('warehouse_type_id', $warehouseTypeId)
                ->orderBy('id')
                ->value('id');

            if ($warehouseId) {
                return (int) $warehouseId;
            }
        }

        $warehouseId = DB::table('warehouses')
            ->where('branch_id', $branchId)
            ->orderBy('id')
            ->value('id');

        if ($warehouseId) {
            return (int) $warehouseId;
        }

        $this->warning('Receiving line warehouse skipped because no warehouse exists for branch ID '.$branchId.'.');

        return null;
    }

    private function countMissingCompanyBranch(string $table): int
    {
        if (! $this->hasColumns($table, ['company_id', 'branch_id'])) {
            return 0;
        }

        return DB::table($table)
            ->whereNull('company_id')
            ->orWhereNull('branch_id')
            ->count();
    }

    private function countMissingWarehouse(string $table): int
    {
        if (! $this->hasColumns($table, ['warehouse_id'])) {
            return 0;
        }

        return DB::table($table)->whereNull('warehouse_id')->count();
    }

    private function hasColumns(string $table, array $columns): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    private function updateOrSkip(string $table, int $id, array $updates): int
    {
        $updates = array_filter($updates, fn ($value): bool => $value !== null);

        if ($updates === []) {
            $this->skipped++;

            return 0;
        }

        if (Schema::hasColumn($table, 'updated_at')) {
            $updates['updated_at'] = now();
        }

        DB::table($table)->where('id', $id)->update($updates);

        return 1;
    }

    private function warning(string $message): void
    {
        $this->warnings++;
        $this->warn($message);
    }
}
