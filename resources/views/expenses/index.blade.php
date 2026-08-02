<x-layouts.app title="Expenses">

@php
    $statusStyles = [
        'draft'     => 'background:#fff8ee; color:#9a5a1a',
        'confirmed' => 'background:#edf7f2; color:#2e7d55',
    ];
@endphp

<div style="max-width:1200px;margin:0 auto;padding:32px 24px">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
        <div>
            <h1 style="font-size:22px;font-weight:700;color:#141413;margin:0">Expenses</h1>
            <p style="font-size:13px;color:#8c8c8a;margin:4px 0 0">Track and confirm your business expenses.</p>
        </div>
        <div style="display:flex;gap:10px;align-items:center">
            {{-- Generate drafts --}}
            <form method="POST" action="{{ route('expenses.generate-drafts') }}">
                @csrf
                <button type="submit"
                        style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:#F5F4EF;border:1px solid #e5e4df;border-radius:8px;font-size:13px;font-weight:500;color:#141413;cursor:pointer">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                    Generate Drafts
                </button>
            </form>
            <a href="{{ route('expenses.create') }}"
               style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:#D97757;border:none;border-radius:8px;font-size:13px;font-weight:500;color:#fff;text-decoration:none">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New Expense
            </a>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div style="background:#edf7f2;border:1px solid #b5deca;border-radius:8px;padding:11px 16px;color:#2e7d55;font-size:13px;margin-bottom:16px">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#fdf0f0;border:1px solid #f5c6c6;border-radius:8px;padding:11px 16px;color:#b94040;font-size:13px;margin-bottom:16px">
            {{ session('error') }}
        </div>
    @endif

    {{-- Filter bar --}}
    <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px;align-items:center">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search expenses…"
               style="padding:8px 12px;font-size:13px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;min-width:200px;outline:none">
        <select name="category_id"
                style="padding:8px 12px;font-size:13px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </select>
        <select name="status"
                style="padding:8px 12px;font-size:13px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none">
            <option value="">All Statuses</option>
            <option value="draft"     @selected(request('status') === 'draft')>Draft</option>
            <option value="confirmed" @selected(request('status') === 'confirmed')>Confirmed</option>
        </select>
        <input type="month" name="month" value="{{ request('month') }}"
               style="padding:8px 12px;font-size:13px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none">
        <button type="submit"
                style="padding:8px 16px;background:#D97757;border:none;border-radius:8px;font-size:13px;font-weight:500;color:#fff;cursor:pointer">
            Filter
        </button>
        @if(request()->hasAny(['search','category_id','status','month']))
            <a href="{{ route('expenses.index') }}"
               style="padding:8px 14px;background:#F5F4EF;border:1px solid #e5e4df;border-radius:8px;font-size:13px;color:#8c8c8a;text-decoration:none">
                Clear
            </a>
        @endif
    </form>

    {{-- Draft section (amber bg) --}}
    @php $pendingDrafts = $expenses->getCollection()->where('status','draft'); @endphp
    @if($pendingDrafts->count())
        <div style="background:#fffbf2;border:1px solid #f5e0a8;border-radius:12px;padding:20px;margin-bottom:24px">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
                <div style="display:flex;align-items:center;gap:10px">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9a5a1a" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span style="font-size:14px;font-weight:600;color:#9a5a1a">{{ $pendingDrafts->count() }} Draft Expense(s) Pending Confirmation</span>
                </div>
                <form method="POST" action="{{ route('expenses.bulk-confirm') }}" id="bulk-confirm-form">
                    @csrf
                    @foreach($pendingDrafts as $d)
                        <input type="hidden" name="ids[]" value="{{ $d->id }}">
                    @endforeach
                    <button type="submit"
                            style="padding:7px 14px;background:#9a5a1a;border:none;border-radius:7px;font-size:12px;font-weight:600;color:#fff;cursor:pointer">
                        Confirm All Drafts
                    </button>
                </form>
            </div>
            <div style="display:flex;flex-direction:column;gap:8px">
                @foreach($pendingDrafts as $expense)
                    <div style="display:flex;align-items:center;justify-content:space-between;background:#fff;border:1px solid #f5e0a8;border-radius:8px;padding:12px 16px">
                        <div style="display:flex;align-items:center;gap:12px">
                            @if($expense->category)
                                <span style="width:8px;height:8px;border-radius:50%;background:{{ $expense->category->color }};display:inline-block"></span>
                                <span style="font-size:12px;color:#8c8c8a">{{ $expense->category->name }}</span>
                            @endif
                            <span style="font-size:14px;font-weight:500;color:#141413">{{ $expense->title }}</span>
                            @if($expense->recurringCharge)
                                <span style="font-size:11px;background:#f0f0f0;color:#8c8c8a;padding:2px 8px;border-radius:4px">Recurring</span>
                            @endif
                        </div>
                        <div style="display:flex;align-items:center;gap:14px">
                            <span style="font-size:13px;color:#8c8c8a">{{ $expense->expense_date->format('d M Y') }}</span>
                            <span style="font-size:14px;font-weight:600;color:#141413">{{ number_format($expense->amount, 2) }} MRU</span>
                            <form method="POST" action="{{ route('expenses.confirm', $expense) }}">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        style="padding:5px 12px;background:#edf7f2;border:1px solid #b5deca;border-radius:6px;font-size:12px;font-weight:500;color:#2e7d55;cursor:pointer">
                                    Confirm
                                </button>
                            </form>
                            <a href="{{ route('expenses.edit', $expense) }}"
                               style="font-size:12px;color:#8c8c8a;text-decoration:none;padding:5px 10px;border:1px solid #e5e4df;border-radius:6px">Edit</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Expenses table --}}
    <div style="background:#fff;border:1px solid #e5e4df;border-radius:12px">
        <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;min-width:640px">
            <thead>
                <tr style="border-bottom:1px solid #eeeee9">
                    <th style="text-align:left;padding:12px 16px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a">Title</th>
                    <th class="hidden sm:table-cell" style="text-align:left;padding:12px 16px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a">Category</th>
                    <th class="hidden sm:table-cell" style="text-align:left;padding:12px 16px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a">Date</th>
                    <th style="text-align:right;padding:12px 16px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a">Amount</th>
                    <th style="text-align:center;padding:12px 16px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a">Status</th>
                    <th style="text-align:right;padding:12px 16px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $expense)
                    <tr style="border-bottom:1px solid #eeeee9;{{ $expense->status === 'draft' ? 'background:#fffbf2;' : '' }}"
                        onmouseover="this.style.background='{{ $expense->status === 'draft' ? '#fff8e6' : '#faf9f5' }}'"
                        onmouseout="this.style.background='{{ $expense->status === 'draft' ? '#fffbf2' : '' }}'">
                        <td style="padding:13px 16px">
                            <div style="font-size:14px;font-weight:500;color:#141413">{{ $expense->title }}</div>
                            @if($expense->recurringCharge)
                                <div style="font-size:11px;color:#8c8c8a;margin-top:2px">↻ {{ $expense->recurringCharge->name }}</div>
                            @endif
                        </td>
                        <td class="hidden sm:table-cell" style="padding:13px 16px">
                            @if($expense->category)
                                <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#5c5c5a">
                                    <span style="width:8px;height:8px;border-radius:50%;background:{{ $expense->category->color }};display:inline-block;flex-shrink:0"></span>
                                    {{ $expense->category->name }}
                                </span>
                            @else
                                <span style="font-size:12px;color:#c0bfba">—</span>
                            @endif
                        </td>
                        <td class="hidden sm:table-cell" style="padding:13px 16px;font-size:13px;color:#5c5c5a">
                            {{ $expense->expense_date->format('d M Y') }}
                        </td>
                        <td style="padding:13px 16px;text-align:right;font-size:14px;font-weight:600;color:#141413">
                            {{ number_format($expense->amount, 2) }} <span style="font-size:11px;color:#8c8c8a">MRU</span>
                        </td>
                        <td style="padding:13px 16px;text-align:center">
                            <span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;{{ $statusStyles[$expense->status] ?? '' }}">
                                {{ ucfirst($expense->status) }}
                            </span>
                        </td>
                        <td style="padding:13px 16px;text-align:right">
                            <div style="display:flex;gap:8px;justify-content:flex-end;align-items:center">
                                @if($expense->status === 'draft')
                                    <form method="POST" action="{{ route('expenses.confirm', $expense) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                                style="padding:5px 12px;background:#edf7f2;border:1px solid #b5deca;border-radius:6px;font-size:12px;font-weight:500;color:#2e7d55;cursor:pointer">
                                            Confirm
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('expenses.edit', $expense) }}"
                                   style="font-size:12px;color:#8c8c8a;text-decoration:none;padding:5px 10px;border:1px solid #e5e4df;border-radius:6px">Edit</a>
                                <form method="POST" action="{{ route('expenses.destroy', $expense) }}"
                                      onsubmit="return confirm('Delete this expense?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            style="font-size:12px;color:#b94040;background:none;border:1px solid #f5c6c6;border-radius:6px;padding:5px 10px;cursor:pointer">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding:48px;text-align:center;color:#8c8c8a;font-size:14px">
                            No expenses found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($expenses->count())
                <tfoot>
                    <tr style="background:#F5F4EF;border-top:1px solid #e5e4df">
                        <td colspan="3" style="padding:12px 16px;font-size:13px;font-weight:600;color:#5c5c5a">
                            {{ $expenses->total() }} expense(s)
                        </td>
                        <td style="padding:12px 16px;text-align:right;font-size:14px;font-weight:700;color:#141413">
                            {{ number_format($expenses->getCollection()->sum('amount'), 2) }} <span style="font-size:11px;color:#8c8c8a">MRU</span>
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($expenses->hasPages())
        <div style="margin-top:20px">{{ $expenses->links() }}</div>
    @endif

</div>
</x-layouts.app>
