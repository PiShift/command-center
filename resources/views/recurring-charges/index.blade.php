<x-layouts.app title="Recurring Charges">

<div style="max-width:1100px;margin:0 auto;padding:32px 24px">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
        <div>
            <h1 style="font-size:22px;font-weight:700;color:#141413;margin:0">Recurring Charges</h1>
            <p style="font-size:13px;color:#8c8c8a;margin:4px 0 0">Manage subscriptions and recurring expenses.</p>
        </div>
        <a href="{{ route('recurring-charges.create') }}"
           style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:#D97757;border:none;border-radius:8px;font-size:13px;font-weight:500;color:#fff;text-decoration:none">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Charge
        </a>
    </div>

    @if(session('success'))
        <div style="background:#edf7f2;border:1px solid #b5deca;border-radius:8px;padding:11px 16px;color:#2e7d55;font-size:13px;margin-bottom:16px">
            {{ session('success') }}
        </div>
    @endif

    <div style="background:#fff;border:1px solid #e5e4df;border-radius:12px;overflow:hidden">
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="border-bottom:1px solid #eeeee9">
                    <th style="text-align:left;padding:12px 16px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a">Name</th>
                    <th style="text-align:left;padding:12px 16px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a">Category</th>
                    <th style="text-align:right;padding:12px 16px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a">Amount</th>
                    <th style="text-align:center;padding:12px 16px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a">Frequency</th>
                    <th style="text-align:center;padding:12px 16px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a">Next Due</th>
                    <th style="text-align:center;padding:12px 16px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a">Active</th>
                    <th style="text-align:right;padding:12px 16px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($charges as $charge)
                    <tr style="border-bottom:1px solid #eeeee9"
                        onmouseover="this.style.background='#faf9f5'" onmouseout="this.style.background=''">
                        <td style="padding:13px 16px">
                            <div style="font-size:14px;font-weight:500;color:#141413">{{ $charge->name }}</div>
                            @if($charge->project)
                                <div style="font-size:11px;color:#8c8c8a;margin-top:2px">{{ $charge->project->name }}</div>
                            @endif
                        </td>
                        <td style="padding:13px 16px">
                            @if($charge->category)
                                <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#5c5c5a">
                                    <span style="width:8px;height:8px;border-radius:50%;background:{{ $charge->category->color }};display:inline-block"></span>
                                    {{ $charge->category->name }}
                                </span>
                            @else
                                <span style="color:#c0bfba;font-size:12px">—</span>
                            @endif
                        </td>
                        <td style="padding:13px 16px;text-align:right;font-size:14px;font-weight:600;color:#141413">
                            {{ number_format($charge->amount, 2) }} <span style="font-size:11px;color:#8c8c8a">MRU</span>
                        </td>
                        <td style="padding:13px 16px;text-align:center">
                            <span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;background:#F5F4EF;color:#5c5c5a;text-transform:capitalize">
                                {{ $charge->frequency }}
                            </span>
                        </td>
                        <td style="padding:13px 16px;text-align:center;font-size:13px;color:#5c5c5a">
                            {{ $charge->next_due_date->format('d M Y') }}
                        </td>
                        <td style="padding:13px 16px;text-align:center">
                            <span style="font-size:12px;padding:3px 10px;border-radius:20px;{{ $charge->is_active ? 'background:#edf7f2;color:#2e7d55' : 'background:#F5F4EF;color:#8c8c8a' }}">
                                {{ $charge->is_active ? 'Active' : 'Paused' }}
                            </span>
                        </td>
                        <td style="padding:13px 16px;text-align:right">
                            <div style="display:flex;gap:8px;justify-content:flex-end">
                                <a href="{{ route('recurring-charges.edit', $charge) }}"
                                   style="font-size:12px;color:#8c8c8a;text-decoration:none;padding:5px 10px;border:1px solid #e5e4df;border-radius:6px">Edit</a>
                                <form method="POST" action="{{ route('recurring-charges.destroy', $charge) }}"
                                      onsubmit="return confirm('Delete this recurring charge?')">
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
                        <td colspan="7" style="padding:48px;text-align:center;color:#8c8c8a;font-size:14px">
                            No recurring charges yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
</x-layouts.app>
