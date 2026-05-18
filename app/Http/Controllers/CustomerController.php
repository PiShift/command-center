<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('customers.view'), 403);

        $sortable  = ['name', 'company', 'status', 'industry'];
        $sort      = in_array($request->sort, $sortable) ? $request->sort : 'name';
        $direction = $request->direction === 'desc' ? 'desc' : 'asc';

        $query = Customer::withCount('projects')->orderBy($sort, $direction);

        if ($request->filled('search'))  $query->where('name', 'like', '%' . $request->search . '%');
        if ($request->filled('status'))  $query->where('status', $request->status);
        if ($request->filled('industry')) $query->where('industry', $request->industry);

        $customers = $query->paginate(25)->withQueryString();

        return view('customers.index', compact('customers', 'sort', 'direction'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('customers.create'), 403);
        return view('customers.form', ['customer' => null]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('customers.create'), 403);
        $customer = Customer::create($this->validated($request));
        return redirect()->route('customers.show', $customer)->with('success', 'Customer created.');
    }

    public function show(Customer $customer)
    {
        abort_unless(auth()->user()->hasPermission('customers.view'), 403);
        $customer->load('projects');
        $walletBalance = \App\Models\CustomerCredit::getBalanceForCustomer($customer->id, 'MRU');
        return view('customers.show', compact('customer', 'walletBalance'));
    }

    public function edit(Customer $customer)
    {
        abort_unless(auth()->user()->hasPermission('customers.edit'), 403);
        return view('customers.form', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        abort_unless(auth()->user()->hasPermission('customers.edit'), 403);
        $customer->update($this->validated($request));
        return redirect()->route('customers.show', $customer)->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer)
    {
        abort_unless(auth()->user()->hasPermission('customers.delete'), 403);
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'nullable|email|max:255',
            'company'    => 'nullable|string|max:255',
            'phone'      => 'nullable|string|max:50',
            'website'    => 'nullable|url|max:255',
            'status'     => 'required|in:active,prospect,churned',
            'industry'   => 'nullable|string|max:255',
            'avatar_url' => 'nullable|url|max:255',
            'notes'      => 'nullable|string',
        ]);
    }
}
