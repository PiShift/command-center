<?php

namespace App\Http\Controllers;

use App\Models\EmployeeDocument;
use App\Models\EmployeeProfile;
use Illuminate\Http\Request;

class EmployeeDocumentController extends Controller
{
    public function store(Request $request, EmployeeProfile $employee)
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type'  => 'required|in:contract,id_card,diploma,certificate,other',
            'notes' => 'nullable|string',
            'file'  => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $document = $employee->documents()->create([
            'title' => $validated['title'],
            'type'  => $validated['type'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $document->addMediaFromRequest('file')
            ->toMediaCollection('file');

        return back()->with('success', 'Document uploaded.');
    }

    public function destroy(EmployeeProfile $employee, EmployeeDocument $document)
    {
        abort_unless(auth()->user()->hasPermission('hr.manage'), 403);
        abort_unless($document->employee_id === $employee->id, 404);

        $document->clearMediaCollection('file');
        $document->delete();

        return back()->with('success', 'Document deleted.');
    }
}
