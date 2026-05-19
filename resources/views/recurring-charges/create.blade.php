<x-layouts.app title="New Recurring Charge">

<div style="max-width:680px;margin:0 auto;padding:32px 24px">

    <div style="margin-bottom:24px">
        <a href="{{ route('recurring-charges.index') }}"
           style="font-size:13px;color:#8c8c8a;text-decoration:none;display:inline-flex;align-items:center;gap:6px;margin-bottom:16px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Back to Recurring Charges
        </a>
        <h1 style="font-size:22px;font-weight:700;color:#141413;margin:0">New Recurring Charge</h1>
    </div>

    @if($errors->any())
        <div style="background:#fdf0f0;border:1px solid #f5c6c6;border-radius:8px;padding:11px 16px;color:#b94040;font-size:13px;margin-bottom:20px">
            <ul style="margin:0;padding-left:18px">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('recurring-charges.store') }}"
          style="background:#fff;border:1px solid #e5e4df;border-radius:12px;padding:28px">
        @csrf

        @include('recurring-charges._form', ['charge' => null])

        <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:28px;padding-top:20px;border-top:1px solid #eeeee9">
            <a href="{{ route('recurring-charges.index') }}"
               style="padding:10px 20px;background:#F5F4EF;border:1px solid #e5e4df;border-radius:8px;font-size:14px;font-weight:500;color:#141413;text-decoration:none">
                Cancel
            </a>
            <button type="submit"
                    style="padding:10px 24px;background:#D97757;border:none;border-radius:8px;font-size:14px;font-weight:500;color:#fff;cursor:pointer">
                Create Charge
            </button>
        </div>
    </form>
</div>
</x-layouts.app>
