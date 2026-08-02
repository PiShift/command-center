<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskInvoiceOverride;
use Illuminate\Http\Request;

class TaskInvoiceOverrideController extends Controller
{
    public function store(Request $request, Task $task)
    {
        abort_unless(auth()->user()->hasPermission('invoices.manage'), 403);

        $data = $request->validate([
            'status' => 'required|in:invoiced,paid',
            'note' => 'nullable|string',
        ]);

        TaskInvoiceOverride::updateOrCreate(
            ['task_id' => $task->id],
            [
                'status' => $data['status'],
                'note' => $data['note'] ?: null,
                'marked_by' => auth()->id(),
                'marked_at' => now(),
            ],
        );

        return back()->with('success', 'Billing override saved.');
    }

    public function destroy(Task $task)
    {
        abort_unless(auth()->user()->hasPermission('invoices.manage'), 403);

        $task->invoiceOverride()->delete();

        return back()->with('success', 'Billing override removed.');
    }
}
