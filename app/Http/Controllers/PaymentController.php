<?php

namespace App\Http\Controllers;

use App\Models\InvoicePayment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('payments.view'), 403);
        $sortable  = ['payment_date', 'amount', 'method'];
        $sort      = in_array($request->sort, $sortable) ? $request->sort : 'payment_date';
        $direction = $request->direction === 'asc' ? 'asc' : 'desc';

        $query = InvoicePayment::with(['invoice', 'customer'])
            ->orderBy($sort, $direction);

        if ($s = $request->search) {
            $query->where(function ($q) use ($s) {
                $q->where('reference', 'like', "%$s%")
                  ->orWhereHas('invoice', fn($q2) => $q2->where('invoice_number', 'like', "%$s%"))
                  ->orWhereHas('customer', fn($q2) => $q2->where('name', 'like', "%$s%"));
            });
        }

        if ($method = $request->method) {
            $query->where('method', $method);
        }

        $payments = $query->paginate(25)->withQueryString();

        $totalAmount = InvoicePayment::sum('amount');

        return view('payments.index', compact('payments', 'sort', 'direction', 'totalAmount'));
    }
}
