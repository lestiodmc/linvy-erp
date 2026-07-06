<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class BackfillPurchaseBranchCommand extends Command
{
    protected $signature = 'linvy:backfill-purchase-branch';

    protected $description = 'Backfill legacy purchase company, branch, and warehouse data.';

    private int $warnings = 0;

    public function handle(): int
    {
        $this->info('Backfill Purchase Branch Started');
        $this->newLine();

        $counts = DB::transaction(function (): array {
            $defaultCompanyId = $this->getDefaultCompanyId();
            $defaultBranchId = $this->getDefaultBranchId($defaultCompanyId);

            return [
                'purchase_requests' => $this->backfillPurchaseRequests($defaultCompanyId, $defaultBranchId),
                'purchase_request_lines' => $this->backfillPurchaseRequestLines(),
                'purchase_orders' => $this->backfillPurchaseOrders($defaultCompanyId, $defaultBranchId),
                'purchase_order_lines' => $this->backfillPurchaseOrderLines(),
                'receivings' => $this->backfillReceivings($defaultCompanyId, $defaultBranchId),
                'receiving_lines' => $this->backfillReceivingLines(),
            ];
        });

        $this->line('Purchase Requests fixed: '.$counts['purchase_requests']);
        $this->line('Purchase Request Lines fixed: '.$counts['purchase_request_lines']);
        $this->line('Purchase Orders fixed: '.$counts['purchase_orders']);
        $this->line('Purchase Order Lines fixed: '.$counts['purchase_order_lines']);
        $this->line('Receivings fixed: '.$counts['receivings']);
        $this->line('Receiving Lines fixed: '.$counts['receiving_lines']);
        $this->line('Skipped / warnings: '.$this->warnings);
        $this->newLine();
        $this->info('Backfill completed successfully.');

        return self::SUCCESS;
    }

    private function backfillPurchaseRequests(int $defaultCompanyId, int $defaultBranchId): int
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

                if ($record->company_id === null) {
                    $updates['company_id'] = $defaultCompanyId;
                }

                if ($record->branch_id === null) {
                    $updates['branch_id'] = $defaultBranchId;
                }

                if ($updates !== []) {
                    $this->updateById('purchase_requests', $record->id, $updates);
                    $fixed++;
                }
            });

        return $fixed;
    }

    private function backfillPurchaseRequestLines(): int
    {
        if (! Schema::hasTable('purchase_request_lines')) {
            return 0;
        }

        $fixed = 0;
        $hasCompany = Schema::hasColumn('purchase_request_lines', 'company_id');
        $hasBranch = Schema::hasColumn('purchase_request_lines', 'branch_id');
        $hasWarehouse = Schema::hasColumn('purchase_request_lines', 'warehouse_id');

        if (! $hasCompany && ! $hasBranch && ! $hasWarehouse) {
            return 0;
        }

        DB::table('purchase_request_lines')
            ->join('purchase_requests', 'purchase_request_lines.purchase_request_id', '=', 'purchase_requests.id')
            ->select('purchase_request_lines.*', 'purchase_requests.company_id as header_company_id', 'purchase_requests.branch_id as header_branch_id')
            ->orderBy('purchase_request_lines.id')
            ->each(function (object $line) use ($hasCompany, $hasBranch, $hasWarehouse, &$fixed): void {
                $updates = [];

                if ($hasCompany && $line->company_id === null) {
                    $updates['company_id'] = $line->header_company_id;
                }

                if ($hasBranch && $line->branch_id === null) {
                    $updates['branch_id'] = $line->header_branch_id;
                }

                if ($hasWarehouse && $line->warehouse_id === null) {
                    $updates['warehouse_id'] = $this->resolveWarehouseId($line->header_branch_id, $line->item_id);
                }

                if ($updates !== []) {
                    $this->updateById('purchase_request_lines', $line->id, $updates);
                    $fixed++;
                }
            });

        return $fixed;
    }

    private function backfillPurchaseOrders(int $defaultCompanyId, int $defaultBranchId): int
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
                    $updates['company_id'] = $record->pr_company_id ?: $defaultCompanyId;
                }

                if ($record->branch_id === null) {
                    $updates['branch_id'] = $record->pr_branch_id ?: $defaultBranchId;
                }

                if ($updates !== []) {
                    $this->updateById('purchase_orders', $record->id, $updates);
                    $fixed++;
                }
            });

        return $fixed;
    }

    private function backfillPurchaseOrderLines(): int
    {
        if (! Schema::hasTable('purchase_order_lines')) {
            return 0;
        }

        $fixed = 0;
        $hasCompany = Schema::hasColumn('purchase_order_lines', 'company_id');
        $hasBranch = Schema::hasColumn('purchase_order_lines', 'branch_id');
        $hasWarehouse = Schema::hasColumn('purchase_order_lines', 'warehouse_id');

        if (! $hasCompany && ! $hasBranch && ! $hasWarehouse) {
            return 0;
        }

        DB::table('purchase_order_lines')
            ->join('purchase_orders', 'purchase_order_lines.purchase_order_id', '=', 'purchase_orders.id')
            ->select('purchase_order_lines.*', 'purchase_orders.company_id as header_company_id', 'purchase_orders.branch_id as header_branch_id')
            ->orderBy('purchase_order_lines.id')
            ->each(function (object $line) use ($hasCompany, $hasBranch, $hasWarehouse, &$fixed): void {
                $updates = [];

                if ($hasCompany && $line->company_id === null) {
                    $updates['company_id'] = $line->header_company_id;
                }

                if ($hasBranch && $line->branch_id === null) {
                    $updates['branch_id'] = $line->header_branch_id;
                }

                if ($hasWarehouse && $line->warehouse_id === null) {
                    $updates['warehouse_id'] = $this->resolveWarehouseId($line->header_branch_id, $line->item_id);
                }

                if ($updates !== []) {
                    $this->updateById('purchase_order_lines', $line->id, $updates);
                    $fixed++;
                }
            });

        return $fixed;
    }

    private function backfillReceivings(int $defaultCompanyId, int $defaultBranchId): int
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
                    $updates['company_id'] = $record->po_company_id ?: $defaultCompanyId;
                }

                if ($record->branch_id === null) {
                    $updates['branch_id'] = $record->po_branch_id ?: $defaultBranchId;
                }

                if ($updates !== []) {
                    $this->updateById('receivings', $record->id, $updates);
                    $fixed++;
                }
            });

        return $fixed;
    }

    private function backfillReceivingLines(): int
    {
        if (! Schema::hasTable('receiving_lines')) {
            return 0;
        }

        $fixed = 0;
        $hasCompany = Schema::hasColumn('receiving_lines', 'company_id');
        $hasBranch = Schema::hasColumn('receiving_lines', 'branch_id');
        $hasWarehouse = Schema::hasColumn('receiving_lines', 'warehouse_id');
        $poLineHasWarehouse = Schema::hasColumn('purchase_order_lines', 'warehouse_id');

        if (! $hasCompany && ! $hasBranch && ! $hasWarehouse) {
            return 0;
        }

        $query = DB::table('receiving_lines')
            ->join('receivings', 'receiving_lines.receiving_id', '=', 'receivings.id')
            ->leftJoin('purchase_order_lines', 'receiving_lines.purchase_order_line_id', '=', 'purchase_order_lines.id')
            ->select(
                'receiving_lines.*',
                'receivings.company_id as header_company_id',
                'receivings.branch_id as header_branch_id',
                'receivings.warehouse_id as header_warehouse_id'
            )
            ->orderBy('receiving_lines.id');

        if ($poLineHasWarehouse) {
            $query->addSelect('purchase_order_lines.warehouse_id as po_line_warehouse_id');
        }

        $query->each(function (object $line) use ($hasCompany, $hasBranch, $hasWarehouse, $poLineHasWarehouse, &$fixed): void {
            $updates = [];

            if ($hasCompany && $line->company_id === null) {
                $updates['company_id'] = $line->header_company_id;
            }

            if ($hasBranch && $line->branch_id === null) {
                $updates['branch_id'] = $line->header_branch_id;
            }

            if ($hasWarehouse && $line->warehouse_id === null) {
                $poLineWarehouseId = $poLineHasWarehouse ? ($line->po_line_warehouse_id ?? null) : null;
                $fallbackWarehouseId = $poLineWarehouseId ?: $line->header_warehouse_id;
                $updates['warehouse_id'] = $this->resolveWarehouseId($line->header_branch_id, $line->item_id, $fallbackWarehouseId);
            }

            if ($updates !== []) {
                $this->updateById('receiving_lines', $line->id, $updates);
                $fixed++;
            }
        });

        return $fixed;
    }

    private function getDefaultCompanyId(): int
    {
        $companyId = DB::table('companies')->orderBy('id')->value('id');

        if (! $companyId) {
            throw new RuntimeException('No company found for purchase branch backfill.');
        }

        return (int) $companyId;
    }

    private function getDefaultBranchId(?int $companyId = null): int
    {
        $query = DB::table('branches')->orderBy('id');

        if ($companyId && Schema::hasColumn('branches', 'company_id')) {
            $branchId = (clone $query)->where('company_id', $companyId)->value('id');

            if ($branchId) {
                return (int) $branchId;
            }
        }

        $branchId = $query->value('id');

        if (! $branchId) {
            throw new RuntimeException('No branch found for purchase branch backfill.');
        }

        return (int) $branchId;
    }

    private function resolveWarehouseId(?int $branchId, ?int $itemId, ?int $fallbackWarehouseId = null): ?int
    {
        if ($fallbackWarehouseId) {
            return $fallbackWarehouseId;
        }

        if (! $branchId) {
            $this->warnOnce('Cannot resolve warehouse because branch is missing.');

            return null;
        }

        $item = $itemId ? DB::table('items')->where('id', $itemId)->first() : null;

        if ($item?->default_warehouse_type_id) {
            $warehouseId = DB::table('warehouses')
                ->where('branch_id', $branchId)
                ->where('warehouse_type_id', $item->default_warehouse_type_id)
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

        $this->warnOnce('No warehouse found for branch ID '.$branchId.'.');

        return null;
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

    private function updateById(string $table, int $id, array $updates): void
    {
        $updates = array_filter($updates, fn ($value): bool => $value !== null);

        if ($updates === []) {
            return;
        }

        if (Schema::hasColumn($table, 'updated_at')) {
            $updates['updated_at'] = now();
        }

        DB::table($table)->where('id', $id)->update($updates);
    }

    private function warnOnce(string $message): void
    {
        $this->warnings++;
        $this->warn($message);
    }
}
