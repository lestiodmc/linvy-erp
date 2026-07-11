<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BatchAssignment;
use App\Services\Inventory\BatchAssignmentService;
use Illuminate\Http\RedirectResponse;

class BatchAssignmentController extends Controller
{
    public function __construct(private readonly BatchAssignmentService $service) {}

    public function post(BatchAssignment $batchAssignment): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin() || auth()->user()?->branches()->whereKey($batchAssignment->branch_id)->exists(), 403);
        $this->service->post($batchAssignment);

        return back()->with('status', 'Batch assignment posted.');
    }
}
