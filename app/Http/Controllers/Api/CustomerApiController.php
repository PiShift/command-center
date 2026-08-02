<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerApiController extends Controller
{
    public function invoices(Request $request, ?string $identifier = null): JsonResponse
    {
        $rawIdentifier = $identifier
            ?? $request->input('identifier')
            ?? $request->input('phone')
            ?? $request->input('email')
            ?? $request->input('id');

        $rawIdentifier = is_string($rawIdentifier) ? trim($rawIdentifier) : (string) $rawIdentifier;

        if ($rawIdentifier === '') {
            return response()->json(['error' => 'Customer not found'], 404);
        }

        $lookupType = $this->detectIdentifierType($rawIdentifier);

        $customerQuery = Customer::query();

        if ($lookupType === 'phone') {
            $customerQuery->where('phone', $rawIdentifier);
        } elseif ($lookupType === 'email') {
            $customerQuery->where('email', $rawIdentifier);
        } else {
            $customerQuery->where('id', $rawIdentifier);
        }

        $customer = $customerQuery->first();

        if (! $customer) {
            return response()->json(['error' => 'Customer not found'], 404);
        }

        $paymentStatus = $request->query('payment_status', $request->input('payment_status'));
        $limitInput    = (int) $request->query('limit', $request->input('limit', 10));
        $limit         = max(1, min(50, $limitInput > 0 ? $limitInput : 10));

        $baseQuery = Invoice::query()
            ->where('customer_id', $customer->id)
            ->where('status', 'published');

        if (in_array($paymentStatus, ['paid', 'unpaid', 'partially_paid'], true)) {
            $baseQuery->where('payment_status', $paymentStatus);
        }

        $invoices = (clone $baseQuery)
            ->orderByDesc('issue_date')
            ->limit($limit)
            ->get([
                'invoice_number',
                'issue_date',
                'due_date',
                'total',
                'amount_paid',
                'payment_status',
                'currency',
            ]);

        $summaryRows = (clone $baseQuery)
            ->get(['total', 'amount_paid', 'due_date', 'payment_status', 'currency']);

        $totalBilled      = (float) $summaryRows->sum('total');
        $totalPaid        = (float) $summaryRows->sum('amount_paid');
        $totalOutstanding = (float) max(0, $totalBilled - $totalPaid);
        $today            = Carbon::today();

        $overdueCount = $summaryRows->filter(function ($inv) use ($today) {
            return $inv->payment_status !== 'paid'
                && $inv->due_date
                && Carbon::parse($inv->due_date)->lt($today);
        })->count();

        $currency = (string) ($summaryRows->first()->currency ?? 'MRU');

        return response()->json([
            'customer' => [
                'name'  => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
            ],
            'summary' => [
                'total_invoices'    => $summaryRows->count(),
                'total_billed'      => round($totalBilled, 2),
                'total_paid'        => round($totalPaid, 2),
                'total_outstanding' => round($totalOutstanding, 2),
                'overdue_count'     => $overdueCount,
                'currency'          => $currency,
            ],
            'invoices' => $invoices->map(function ($inv) use ($today) {
                $total       = (float) $inv->total;
                $paid        = (float) $inv->amount_paid;
                $outstanding = (float) max(0, $total - $paid);
                $isOverdue   = $inv->payment_status !== 'paid'
                    && $inv->due_date
                    && Carbon::parse($inv->due_date)->lt($today);

                return [
                    'number'         => $inv->invoice_number,
                    'date'           => optional($inv->issue_date)->format('M d, Y'),
                    'due'            => optional($inv->due_date)->format('M d, Y'),
                    'total'          => round($total, 2),
                    'paid'           => round($paid, 2),
                    'outstanding'    => round($outstanding, 2),
                    'payment_status' => $inv->payment_status,
                    'overdue'        => (bool) $isOverdue,
                ];
            })->values(),
        ]);
    }

    private function detectIdentifierType(string $identifier): string
    {
        if (str_contains($identifier, '@')) {
            return 'email';
        }

        $digitsOnly = preg_replace('/\D+/', '', $identifier) ?? '';

        if (str_starts_with($identifier, '+') || (strlen($digitsOnly) > 8 && $digitsOnly === $identifier)) {
            return 'phone';
        }

        return 'id';
    }
}
