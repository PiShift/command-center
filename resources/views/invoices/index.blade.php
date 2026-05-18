<x-layouts.app title="Invoices">

@php
    $sortLink = fn(string $col) => request()->fullUrlWithQuery([
        'sort'      => $col,
        'direction' => ($sort === $col && $direction === 'desc') ? 'asc' : 'desc',
        'page'      => 1,
    ]);
    $statusStyles = [
        'draft'          => 'background:#F5F4EF; color:#8c8c8a',
        'published'      => 'background:#eef3fb; color:#3a6fba',
        'partially_paid' => 'background:#fef9ec; color:#9a7a1a',
        'paid'           => 'background:#edf7f2; color:#2e7d55',
        'cancelled'      => 'background:#fdf0f0; color:#b94040',
    ];
@endphp

{{-- ── Payment modal ──────────────────────────────────────────────────────── --}}
<div id="pay-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(20,20,19,.45);padding:40px 16px;overflow-y:auto"
     onclick="if(event.target===this)closePayModal()">
    <div style="background:#fff;border-radius:14px;max-width:460px;margin:0 auto;box-shadow:0 20px 60px rgba(0,0,0,.18);overflow:hidden">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 0">
            <div>
                <div style="font-size:15px;font-weight:600;color:#141413">Record Payment</div>
                <div id="pay-inv-label" style="font-size:12px;color:#8c8c8a;margin-top:2px"></div>
            </div>
            <button onclick="closePayModal()" style="background:none;border:none;cursor:pointer;color:#8c8c8a;padding:4px;line-height:1;font-size:20px">&times;</button>
        </div>
        <form id="pay-form" method="POST" enctype="multipart/form-data" style="padding:20px 22px 22px">
            @csrf
            <div style="display:grid;gap:14px">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div>
                        <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;display:block;margin-bottom:5px">Amount *</label>
                        <input type="number" name="amount" id="pay-amount" step="0.01" min="0.01" required
                               placeholder="0.00"
                               style="width:100%;padding:9px 11px;font-size:13px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;box-sizing:border-box"
                               onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'">
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;display:block;margin-bottom:5px">Date *</label>
                        <input type="date" name="payment_date" id="pay-date" required
                               style="width:100%;padding:9px 11px;font-size:13px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;box-sizing:border-box"
                               onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'">
                    </div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;display:block;margin-bottom:5px">Method *</label>
                    <div style="position:relative">
                        <select name="method" required
                                style="width:100%;padding:9px 32px 9px 11px;font-size:13px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;appearance:none;box-sizing:border-box">
                            <option value="">Select method…</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cash">Cash</option>
                            <option value="check">Check</option>
                            <option value="card">Card</option>
                            <option value="other">Other</option>
                        </select>
                        <svg style="position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#8c8c8a" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;display:block;margin-bottom:5px">Reference</label>
                    <input type="text" name="reference" placeholder="Transaction ID, check #, etc."
                           style="width:100%;padding:9px 11px;font-size:13px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;box-sizing:border-box"
                           onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'">
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;display:block;margin-bottom:5px">Notes</label>
                    <textarea name="notes" rows="2" placeholder="Optional notes…"
                              style="width:100%;padding:9px 11px;font-size:13px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;resize:vertical;box-sizing:border-box"
                              onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'"></textarea>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;display:block;margin-bottom:5px">Proof (jpg/png/pdf, max 5 MB)</label>
                    <input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" style="width:100%;font-size:13px;color:#5c5c5a">
                </div>
            </div>
            <div style="display:flex;gap:10px;margin-top:20px">
                <button type="button" onclick="closePayModal()"
                        style="flex:1;padding:10px;font-size:13px;font-weight:500;border:1px solid #e5e4df;border-radius:8px;background:#F5F4EF;color:#5c5c5a;cursor:pointer">
                    Cancel
                </button>
                <button type="submit"
                        style="flex:2;padding:10px;font-size:13px;font-weight:600;border:none;border-radius:8px;background:#D97757;color:#fff;cursor:pointer"
                        onmouseover="this.style.background='#c4684a'" onmouseout="this.style.background='#D97757'">
                    Record Payment
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Page header ─────────────────────────────────────────────────────────── --}}
<div class="flex items-center justify-between mb-5">
    <h1 style="font-size:24px;font-weight:600;color:#141413">Invoices</h1>
    <a href="{{ route('invoices.create') }}"
       style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:13px;font-weight:500;background:#D97757;color:#fff;border-radius:8px;text-decoration:none"
       onmouseover="this.style.background='#c4684a'" onmouseout="this.style.background='#D97757'">
        + New invoice
    </a>
</div>

@include('components.flash')

{{-- ── Filters ──────────────────────────────────────────────────────────────── --}}
<form method="GET" class="flex flex-wrap gap-2 mb-4">
    <input type="hidden" name="sort" value="{{ $sort }}">
    <input type="hidden" name="direction" value="{{ $direction }}">

    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search invoices…"
           class="text-[13px] pl-3 pr-3 py-2 rounded-lg"
           style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none;width:200px">

    <div class="relative">
        <select name="status" onchange="this.form.submit()"
                class="appearance-none text-[13px] pl-3 pr-8 py-2 rounded-lg cursor-pointer"
                style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none">
            <option value="">All Statuses</option>
            @foreach(['draft'=>'Draft','published'=>'Published','partially_paid'=>'Partial','paid'=>'Paid','cancelled'=>'Cancelled'] as $val=>$lab)
                <option value="{{ $val }}" {{ request('status')==$val?'selected':'' }}>{{ $lab }}</option>
            @endforeach
        </select>
        <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2" style="color:#8c8c8a">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
        </span>
    </div>

    <label class="flex items-center gap-1.5 px-3 py-2 text-[13px] rounded-lg cursor-pointer"
           style="background:#F5F4EF;border:1px solid #e5e4df;color:#5c5c5a">
        <input type="checkbox" name="overdue" value="1" {{ request('overdue')?'checked':'' }} onchange="this.form.submit()">
        Overdue only
    </label>

    @if(request()->hasAny(['search','status','overdue']))
        <a href="{{ route('invoices.index') }}" style="display:flex;align-items:center;padding:8px 12px;font-size:13px;color:#8c8c8a;text-decoration:none"
           onmouseover="this.style.color='#141413'" onmouseout="this.style.color='#8c8c8a'">✕ Clear</a>
    @endif

    <button type="submit" style="padding:8px 14px;font-size:13px;font-weight:500;background:#F5F4EF;border:1px solid #e5e4df;border-radius:8px;cursor:pointer;color:#141413">
        Search
    </button>
</form>

{{-- ── Bulk action bar ──────────────────────────────────────────────────────── --}}
<div id="bulk-bar" style="display:none;align-items:center;gap:10px;padding:10px 16px;margin-bottom:8px;background:#fef9ec;border:1px solid #f0d980;border-radius:10px">
    <span id="bulk-count" style="font-size:13px;font-weight:500;color:#7a6010"></span>
    <form id="bulk-form" method="POST" action="{{ route('invoices.bulk-action') }}" style="display:flex;gap:8px;align-items:center">
        @csrf
        <input type="hidden" name="ids" id="bulk-ids">
        <div style="position:relative">
            <select name="action" style="font-size:13px;padding:6px 28px 6px 10px;border:1px solid #e5e4df;border-radius:7px;background:#fff;color:#141413;appearance:none;cursor:pointer;outline:none">
                <option value="">Choose action…</option>
                <option value="cancel">Cancel</option>
                <option value="reset_to_draft">Reset to Draft</option>
                <option value="delete">Delete</option>
            </select>
            <svg style="position:absolute;right:8px;top:50%;transform:translateY(-50%);pointer-events:none;color:#8c8c8a" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <button type="button" onclick="submitBulk()" style="padding:6px 14px;font-size:13px;font-weight:500;background:#D97757;color:#fff;border:none;border-radius:7px;cursor:pointer">Apply</button>
        <button type="button" onclick="clearSelection()" style="font-size:13px;color:#8c8c8a;background:none;border:none;cursor:pointer;padding:4px 6px">Deselect all</button>
    </form>
</div>

{{-- ── Table ────────────────────────────────────────────────────────────────── --}}
<div class="rounded-xl overflow-hidden" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,.04)">
    @if($invoices->isEmpty())
        <div class="py-16 text-center" style="color:#8c8c8a;font-size:13px">No invoices found.</div>
    @else
    <table class="w-full" style="font-size:13.5px">
        <thead>
            <tr style="background:#faf9f5;border-bottom:1px solid #e5e4df">
                <th class="px-4 py-3" style="width:36px">
                    <input type="checkbox" id="select-all" onchange="toggleAll(this)"
                           style="width:15px;height:15px;cursor:pointer;accent-color:#D97757">
                </th>
                @php $cols = [
                    ['col'=>'invoice_number','label'=>'Invoice', 'cls'=>'px-4 py-3 text-left'],
                    ['col'=>null,            'label'=>'Customer','cls'=>'px-4 py-3 text-left'],
                    ['col'=>'status',        'label'=>'Status',  'cls'=>'px-4 py-3 text-left'],
                    ['col'=>'issue_date',    'label'=>'Issued',  'cls'=>'px-4 py-3 text-left'],
                    ['col'=>'due_date',      'label'=>'Due',     'cls'=>'px-4 py-3 text-left'],
                    ['col'=>'total',         'label'=>'Total',   'cls'=>'px-4 py-3 text-right'],
                    ['col'=>null,            'label'=>'Due Amt', 'cls'=>'px-4 py-3 text-right'],
                    ['col'=>null,            'label'=>'',        'cls'=>'px-4 py-3'],
                ]; @endphp
                @foreach($cols as $th)
                <th class="{{ $th['cls'] }}" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#8c8c8a;white-space:nowrap">
                    @if($th['col'])
                        <a href="{{ $sortLink($th['col']) }}" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:4px">
                            {{ $th['label'] }}
                            <span style="color:{{ $sort===$th['col']?'#D97757':'#d8d7d2' }}">{!! $sort===$th['col']?($direction==='asc'?'↑':'↓'):'↕' !!}</span>
                        </a>
                    @else
                        {{ $th['label'] }}
                    @endif
                </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($invoices as $invoice)
            @php $overdue = $invoice->is_overdue; $bg = $overdue ? '#fff8f8' : '#fff'; @endphp
            <tr class="inv-row" data-bg="{{ $bg }}"
                style="background:{{ $bg }};border-bottom:1px solid #eeeee9;transition:background 100ms ease"
                onmouseover="this.style.background='#faf9f5'" onmouseout="this.style.background=this.dataset.bg">

                <td class="px-4 py-3" style="width:36px">
                    <input type="checkbox" class="row-check" value="{{ $invoice->id }}" onchange="onRowCheck()"
                           style="width:15px;height:15px;cursor:pointer;accent-color:#D97757">
                </td>

                <td class="px-4 py-3">
                    <a href="{{ route('invoices.show', $invoice) }}" style="font-weight:500;color:#141413;text-decoration:none"
                       onmouseover="this.style.color='#D97757'" onmouseout="this.style.color='#141413'">{{ $invoice->invoice_number }}</a>
                    @if($invoice->project)<p style="font-size:11px;color:#8c8c8a;margin-top:2px">{{ $invoice->project->name }}</p>@endif
                </td>

                <td class="px-4 py-3" style="color:#5c5c5a">{{ $invoice->customer->name }}</td>

                <td class="px-4 py-3">
                    <span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:5px;font-size:11px;font-weight:600;{{ $statusStyles[$invoice->status]??'' }}">
                        {{ str_replace('_',' ',ucfirst($invoice->status)) }}
                    </span>
                </td>

                <td class="px-4 py-3" style="color:#5c5c5a;font-size:12px">{{ $invoice->issue_date->format('M d, Y') }}</td>
                <td class="px-4 py-3" style="color:{{ $overdue?'#b94040':'#5c5c5a' }};font-weight:{{ $overdue?'500':'400' }};font-size:12px">
                    {{ $invoice->due_date->format('M d, Y') }}
                </td>

                <td class="px-4 py-3 text-right" style="font-weight:500;color:#141413">{{ $invoice->currency }} {{ number_format($invoice->total,2) }}</td>
                <td class="px-4 py-3 text-right" style="color:{{ $invoice->amount_due>0?'#b94040':'#2e7d55' }};font-weight:500">
                    {{ $invoice->amount_due>0 ? $invoice->currency.' '.number_format($invoice->amount_due,2) : '—' }}
                </td>

                {{-- ⋮ dropdown --}}
                <td class="px-3 py-3" style="text-align:right;white-space:nowrap">
                    <div style="position:relative;display:inline-block">
                        <button onclick="toggleMenu(this)" title="Actions"
                                style="display:flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:7px;border:1px solid #e5e4df;background:#fff;cursor:pointer;color:#5c5c5a"
                                onmouseover="this.style.background='#F5F4EF'" onmouseout="this.style.background='#fff'">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="12" cy="19" r="1.8"/></svg>
                        </button>
                        <div class="inv-menu" style="display:none;position:absolute;right:0;top:36px;background:#fff;border:1px solid #e5e4df;border-radius:10px;box-shadow:0 8px 30px rgba(0,0,0,.12);min-width:185px;z-index:200;padding:5px 0">

                            <a href="{{ route('invoices.show', $invoice) }}" class="menu-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                View
                            </a>

                            @if($invoice->status === 'draft')
                            <a href="{{ route('invoices.edit', $invoice) }}" class="menu-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                Edit
                            </a>
                            @endif

                            @if($invoice->status !== 'draft')
                            <form method="POST" action="{{ route('invoices.reset-draft', $invoice) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="menu-item" style="width:100%;text-align:left">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4"/></svg>
                                    Reset to Draft
                                </button>
                            </form>
                            @endif

                            <div style="border-top:1px solid #f0efeb;margin:4px 0"></div>

                            <a href="{{ route('invoices.preview', $invoice) }}" target="_blank" class="menu-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                Preview PDF
                            </a>

                            <a href="{{ route('invoices.download', $invoice) }}" class="menu-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                Download PDF
                            </a>

                            @if(in_array($invoice->status, ['published','partially_paid']))
                            <div style="border-top:1px solid #f0efeb;margin:4px 0"></div>
                            <button type="button" class="menu-item" style="width:100%;text-align:left"
                                    onclick="openPayModal({{ $invoice->id }},'{{ $invoice->invoice_number }}',{{ (float)$invoice->amount_due }},'{{ route('invoices.payments.store', $invoice) }}')">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                Record Payment
                            </button>
                            @endif

                            <div style="border-top:1px solid #f0efeb;margin:4px 0"></div>

                            <form method="POST" action="{{ route('invoices.destroy', $invoice) }}"
                                  onsubmit="return confirm('Delete {{ addslashes($invoice->invoice_number) }}? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="menu-item menu-danger" style="width:100%;text-align:left">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                    Delete
                                </button>
                            </form>

                        </div>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="px-6 py-4" style="border-top:1px solid #eeeee9">{{ $invoices->links() }}</div>
    @endif
</div>

<style>
.menu-item {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 8px 14px;
    font-size: 13px;
    color: #141413;
    text-decoration: none;
    background: none;
    border: none;
    cursor: pointer;
    box-sizing: border-box;
    white-space: nowrap;
    line-height: 1.4;
}
.menu-item:hover { background: #F5F4EF; }
.menu-item.menu-danger { color: #b94040; }
.menu-item.menu-danger:hover { background: #fdf0f0; }
</style>

<script>
// ── Dropdown ──────────────────────────────────────────────────────────────
function toggleMenu(btn) {
    const menu = btn.nextElementSibling;
    const open  = menu.style.display === 'block';
    closeAllMenus();
    if (!open) {
        menu.style.display = 'block';
        // position check: flip upward if near bottom of viewport
        const rect = menu.getBoundingClientRect();
        if (rect.bottom > window.innerHeight - 20) {
            menu.style.top  = 'auto';
            menu.style.bottom = '36px';
        }
        setTimeout(() => document.addEventListener('click', outsideClick, {once:true}), 0);
    }
}
function closeAllMenus() {
    document.querySelectorAll('.inv-menu').forEach(m => { m.style.display='none'; m.style.bottom=''; m.style.top='36px'; });
}
function outsideClick(e) {
    if (!e.target.closest('.inv-menu') && !e.target.closest('button[onclick^="toggleMenu"]')) {
        closeAllMenus();
    } else {
        document.addEventListener('click', outsideClick, {once:true});
    }
}

// ── Payment modal ─────────────────────────────────────────────────────────
function openPayModal(id, number, amountDue, actionUrl) {
    closeAllMenus();
    document.getElementById('pay-inv-label').textContent = number + '  ·  Due: ' + amountDue.toFixed(2);
    document.getElementById('pay-amount').value = amountDue > 0 ? amountDue.toFixed(2) : '';
    document.getElementById('pay-date').value   = new Date().toISOString().slice(0,10);
    document.getElementById('pay-form').action  = actionUrl;
    document.getElementById('pay-modal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    document.getElementById('pay-amount').focus();
}
function closePayModal() {
    document.getElementById('pay-modal').style.display = 'none';
    document.body.style.overflow = '';
    document.getElementById('pay-form').reset();
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closePayModal(); });

// ── Bulk select ───────────────────────────────────────────────────────────
function toggleAll(master) {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = master.checked);
    onRowCheck();
}
function onRowCheck() {
    const checked = [...document.querySelectorAll('.row-check:checked')];
    const all     = document.querySelectorAll('.row-check');
    const master  = document.getElementById('select-all');
    master.indeterminate = checked.length > 0 && checked.length < all.length;
    master.checked = all.length > 0 && checked.length === all.length;

    const bar = document.getElementById('bulk-bar');
    if (checked.length > 0) {
        document.getElementById('bulk-count').textContent = checked.length + ' invoice' + (checked.length > 1 ? 's' : '') + ' selected';
        document.getElementById('bulk-ids').value = checked.map(cb => cb.value).join(',');
        bar.style.display = 'flex';
    } else {
        bar.style.display = 'none';
    }
}
function clearSelection() {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = false);
    const master = document.getElementById('select-all');
    master.checked = false;
    master.indeterminate = false;
    document.getElementById('bulk-bar').style.display = 'none';
}
function submitBulk() {
    const form   = document.getElementById('bulk-form');
    const action = form.querySelector('[name="action"]').value;
    if (!action) { alert('Please choose an action first.'); return; }
    if (action === 'delete' && !confirm('Permanently delete the selected invoices?')) return;
    form.submit();
}
</script>

</x-layouts.app>
