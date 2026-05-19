<x-layouts.app title="Monthly Budgets">

<div style="max-width:1000px;margin:0 auto;padding:32px 24px">

    {{-- Header --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:14px">
        <div>
            <h1 style="font-size:22px;font-weight:700;color:#141413;margin:0">Monthly Budgets</h1>
            <p style="font-size:13px;color:#8c8c8a;margin:4px 0 0">{{ $month->format('F Y') }}</p>
        </div>
        <div style="display:flex;align-items:center;gap:10px">
            {{-- Month picker --}}
            <form method="GET" style="display:flex;align-items:center;gap:8px">
                <input type="month" name="month" value="{{ $month->format('Y-m') }}"
                       style="padding:8px 12px;font-size:13px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none"
                       onchange="this.form.submit()">
            </form>
            <a href="{{ route('expenses.monthly-overview', ['month' => $month->format('Y-m')]) }}"
               style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:#F5F4EF;border:1px solid #e5e4df;border-radius:8px;font-size:13px;font-weight:500;color:#141413;text-decoration:none">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                Back to Overview
            </a>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div style="background:#edf7f2;border:1px solid #b5deca;border-radius:8px;padding:11px 16px;color:#2e7d55;font-size:13px;margin-bottom:20px">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#fff0f0;border:1px solid #ffd0d0;border-radius:8px;padding:11px 16px;color:#b94040;font-size:13px;margin-bottom:20px">
            {{ session('error') }}
        </div>
    @endif

    {{-- Budget table --}}
    <div style="background:#fff;border:1px solid #eeeee9;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(20,20,19,.04);margin-bottom:32px">
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="background:#faf9f5">
                    <th style="text-align:left;padding:11px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#8c8c8a;border-bottom:1px solid #e5e4df">Category</th>
                    <th style="text-align:right;padding:11px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#8c8c8a;border-bottom:1px solid #e5e4df">Budget (MRU)</th>
                    <th style="text-align:left;padding:11px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#8c8c8a;border-bottom:1px solid #e5e4df">Notes</th>
                    <th style="width:60px;border-bottom:1px solid #e5e4df"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    @php $budget = $budgets->get($category->id); @endphp
                    <tr style="border-bottom:1px solid #eeeee9" x-data="{ editing: false }">
                        <td style="padding:13px 16px;font-size:13.5px;color:#141413;vertical-align:middle">
                            <div style="display:flex;align-items:center;gap:8px">
                                @if($category->icon)
                                    <span>{{ $category->icon }}</span>
                                @endif
                                <span>{{ $category->name }}</span>
                                @if($category->is_system)
                                    <span style="font-size:10px;font-weight:600;background:#F5F4EF;color:#8c8c8a;border-radius:4px;padding:1px 6px">system</span>
                                @endif
                            </div>
                        </td>

                        {{-- View mode --}}
                        <td style="padding:13px 16px;text-align:right;vertical-align:middle" x-show="!editing">
                            @if($budget)
                                <span style="font-size:14px;font-weight:600;color:#141413">{{ number_format($budget->amount, 2) }}</span>
                            @else
                                <span style="font-size:13px;color:#8c8c8a">—</span>
                            @endif
                        </td>
                        <td style="padding:13px 16px;vertical-align:middle" x-show="!editing">
                            <span style="font-size:13px;color:#5c5c5a">{{ $budget?->notes ?? '' }}</span>
                        </td>
                        <td style="padding:13px 16px;vertical-align:middle;text-align:right" x-show="!editing">
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px">
                                <button @click="editing = true" type="button"
                                        style="padding:5px 10px;font-size:12px;font-weight:500;background:#F5F4EF;border:1px solid #e5e4df;border-radius:7px;color:#141413;cursor:pointer">
                                    {{ $budget ? 'Edit' : 'Set' }}
                                </button>
                                @if($budget)
                                    <form method="POST" action="{{ route('monthly-budgets.destroy', $budget->id) }}" onsubmit="return confirm('Remove this budget?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                style="padding:5px 10px;font-size:12px;font-weight:500;background:#fff0f0;border:1px solid #ffd0d0;border-radius:7px;color:#b94040;cursor:pointer">
                                            Remove
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>

                        {{-- Edit mode (inline form) --}}
                        <td colspan="3" style="padding:12px 16px;vertical-align:middle" x-show="editing" x-cloak>
                            <form method="POST" action="{{ route('monthly-budgets.store') }}" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                                @csrf
                                <input type="hidden" name="category_id" value="{{ $category->id }}">
                                <input type="hidden" name="month" value="{{ $month->toDateString() }}">
                                <input type="number" name="amount" step="0.01" min="0"
                                       value="{{ $budget?->amount ?? '' }}"
                                       placeholder="Amount (MRU)"
                                       style="width:160px;padding:8px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#F5F4EF;color:#141413;outline:none"
                                       required>
                                <input type="text" name="notes"
                                       value="{{ $budget?->notes ?? '' }}"
                                       placeholder="Notes (optional)"
                                       style="flex:1;min-width:160px;padding:8px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#F5F4EF;color:#141413;outline:none">
                                <button type="submit"
                                        style="padding:8px 16px;background:#D97757;border:none;border-radius:8px;font-size:13px;font-weight:500;color:#fff;cursor:pointer">
                                    Save
                                </button>
                                <button type="button" @click="editing = false"
                                        style="padding:8px 14px;background:#F5F4EF;border:1px solid #e5e4df;border-radius:8px;font-size:13px;font-weight:500;color:#141413;cursor:pointer">
                                    Cancel
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="padding:40px;text-align:center;color:#8c8c8a;font-size:13px">
                            No categories found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
</x-layouts.app>
