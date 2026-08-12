<div class="mb-5 rounded-xl border border-line bg-white shadow-card p-1 inline-flex gap-1">
    <a href="{{ route('teams.index') }}"
       class="px-3.5 py-1.5 rounded-lg text-[13px] font-medium transition-colors {{ ($activeTab ?? 'overview') === 'overview' ? 'bg-accent-light text-accent' : 'text-dim hover:bg-hairline hover:text-ink' }}">
        Team Overview
    </a>

    @if(in_array(auth()->user()?->roleModel?->slug, ['manager', 'super-admin'], true))
        <a href="{{ route('teams.index', ['tab' => 'accountability']) }}"
           class="px-3.5 py-1.5 rounded-lg text-[13px] font-medium transition-colors {{ ($activeTab ?? null) === 'accountability' ? 'bg-accent-light text-accent' : 'text-dim hover:bg-hairline hover:text-ink' }}">
            Accountability
        </a>
    @endif
</div>
