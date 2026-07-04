<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ItemCategory;
use Illuminate\View\View;

class AccountMappingController extends Controller
{
    public function __invoke(): View
    {
        return view('accounting.account_mapping.index', [
            'categories' => ItemCategory::with([
                'inventoryAccount',
                'cogsAccount',
                'salesAccount',
                'purchaseAccount',
                'wipAccount',
                'adjustmentAccount',
                'wasteAccount',
            ])->orderBy('code')->get(),
        ]);
    }
}
