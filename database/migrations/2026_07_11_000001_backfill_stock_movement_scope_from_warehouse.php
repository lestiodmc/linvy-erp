<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        $branchBackfilled = DB::table('stock_movements as movement')
            ->join('warehouses as warehouse', 'warehouse.id', '=', 'movement.warehouse_id')
            ->whereNull('movement.branch_id')
            ->whereNotNull('warehouse.branch_id')
            ->update(['movement.branch_id' => DB::raw('warehouse.branch_id')]);

        $companyBackfilled = DB::table('stock_movements as movement')
            ->join('warehouses as warehouse', 'warehouse.id', '=', 'movement.warehouse_id')
            ->leftJoin('branches as branch', 'branch.id', '=', 'warehouse.branch_id')
            ->whereNull('movement.company_id')
            ->where(function ($query): void {
                $query->whereNotNull('warehouse.company_id')
                    ->orWhereNotNull('branch.company_id');
            })
            ->update(['movement.company_id' => DB::raw('COALESCE(warehouse.company_id, branch.company_id)')]);

        $unresolved = DB::table('stock_movements as movement')
            ->leftJoin('warehouses as warehouse', 'warehouse.id', '=', 'movement.warehouse_id')
            ->leftJoin('branches as branch', 'branch.id', '=', 'warehouse.branch_id')
            ->where(function ($query): void {
                $query->whereNull('movement.branch_id')->orWhereNull('movement.company_id');
            })
            ->where(function ($query): void {
                $query->whereNull('movement.warehouse_id')
                    ->orWhereNull('warehouse.id')
                    ->orWhereNull('warehouse.branch_id')
                    ->orWhere(function ($company): void {
                        $company->whereNull('movement.company_id')
                            ->whereNull('warehouse.company_id')
                            ->whereNull('branch.company_id');
                    });
            })
            ->count();

        Log::info('Stock movement scope backfill completed.', [
            'branch_rows_backfilled' => $branchBackfilled,
            'company_rows_backfilled' => $companyBackfilled,
            'unresolved_rows' => $unresolved,
        ]);
    }

    public function down(): void
    {
        // Data backfills are intentionally irreversible: original NULL values
        // cannot be distinguished safely from valid populated scope values.
    }
};
