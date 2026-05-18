<x-layouts.app :title="$customer->name">
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-[18px] font-bold text-ink">{{ $customer->name }}</h1>
            @if($customer->company)<p class="text-[13px] text-dim">{{ $customer->company }}</p>@endif
        </div>
        <div class="flex items-center gap-2">
            @if($walletBalance > 0)
            <a href="{{ route('credits.index', $customer) }}"
               style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;font-size:12px;font-weight:600;background:#edf7f2;color:#2e7d55;border:1px solid #b8e0cb;border-radius:8px;text-decoration:none"
               title="Customer wallet balance">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                Wallet: MRU {{ number_format($walletBalance, 2) }}
            </a>
            @else
            <a href="{{ route('credits.index', $customer) }}"
               style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;font-size:12px;color:#8c8c8a;border:1px solid #e5e4df;border-radius:8px;text-decoration:none"
               title="Customer credits">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                Wallet
            </a>
            @endif
            @include('components.badge', ['type' => 'customer_status', 'value' => $customer->status])
            @if(auth()->user()->hasPermission('customers.edit'))
            <a href="{{ route('customers.edit', $customer) }}"
               class="inline-flex items-center gap-1 px-3 py-1.5 text-[12px] border border-line rounded-lg text-dim hover:bg-hairline">
                @include('components.icon', ['name' => 'pencil']) Edit
            </a>
            @endif
        </div>
    </div>

    @include('components.flash')

    <div class="bg-white border border-line rounded-xl p-6 grid grid-cols-2 gap-4 text-[13px]">
        @foreach([['Email', $customer->email],['Phone', $customer->phone],['Website', $customer->website],['Industry', $customer->industry]] as [$label, $val])
        <div>
            <p class="text-[11px] font-medium text-muted uppercase tracking-wider mb-0.5">{{ $label }}</p>
            <p class="text-ink">{{ $val ?? '—' }}</p>
        </div>
        @endforeach
        @if($customer->notes)
        <div class="col-span-2">
            <p class="text-[11px] font-medium text-muted uppercase tracking-wider mb-0.5">Notes</p>
            <p class="text-ink whitespace-pre-line">{{ $customer->notes }}</p>
        </div>
        @endif
    </div>

    {{-- Projects --}}
    <div class="bg-white border border-line rounded-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-line">
            <h2 class="text-[14px] font-semibold text-ink">Projects ({{ $customer->projects->count() }})</h2>
        </div>
        @if($customer->projects->isEmpty())
            <div class="px-6 py-8 text-center text-[13px] text-muted">No projects.</div>
        @else
            <table class="w-full text-[13px]">
                <thead>
                    <tr class="text-[11px] font-semibold uppercase tracking-wider text-muted border-b border-hairline bg-surface">
                        <th class="px-6 py-2 text-left">Project</th>
                        <th class="px-4 py-2 text-left">Status</th>
                        <th class="px-4 py-2 text-left">Health</th>
                        <th class="px-4 py-2 text-left">Deadline</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-hairline">
                    @foreach($customer->projects as $p)
                    <tr class="hover:bg-hairline">
                        <td class="px-6 py-3">
                            <a href="{{ route('projects.show', $p) }}" class="font-medium text-ink hover:text-accent">{{ $p->name }}</a>
                        </td>
                        <td class="px-4 py-3">@include('components.badge', ['type' => 'project_status', 'value' => $p->status])</td>
                        <td class="px-4 py-3">@include('components.badge', ['type' => 'health', 'value' => $p->health ?? 'on-track'])</td>
                        <td class="px-4 py-3 text-dim">{{ $p->deadline?->format('M d, Y') ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
</x-layouts.app>
