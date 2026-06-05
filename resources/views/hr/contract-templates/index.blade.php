<x-layouts.app title="Contract Templates">

<div class="flex items-center justify-between mb-5">
    <h1 style="font-size:24px;font-weight:600;color:#141413">
        Contract Templates
        <span class="ml-2 text-muted font-normal" style="font-size:14px">{{ $templates->count() }}</span>
    </h1>
    <a href="{{ route('contract-templates.create') }}"
       style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:13px;font-weight:500;background:#D97757;color:#fff;border-radius:8px;transition:background 150ms ease;text-decoration:none"
       onmouseover="this.style.background='#c4684a'" onmouseout="this.style.background='#D97757'">
        + New Template
    </a>
</div>

@include('components.flash')

@if($templates->isEmpty())
            <div class="rounded-xl py-16 text-center" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
                <svg class="mx-auto mb-3" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#8c8c8a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                <p class="font-medium text-dim" style="font-size:14px">No contract templates yet</p>
                <p class="text-muted mt-1" style="font-size:13px">Create templates with placeholders to generate contracts automatically.</p>
                <a href="{{ route('contract-templates.create') }}"
                   class="inline-block mt-4 font-medium rounded-lg px-4 py-2 text-white transition-colors bg-accent hover:bg-accent-hover"
                   style="font-size:13px">
                    + Create First Template
                </a>
            </div>
            @else
            <div class="rounded-xl overflow-hidden" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
                <table class="w-full" style="border-collapse:collapse">
                    <thead>
                        <tr style="background:#faf9f5;border-bottom:1px solid #e5e4df">
                            <th class="px-5 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Name</th>
                            <th class="px-4 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;width:100px">Type</th>
                            <th class="px-4 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;width:60px">Lang</th>
                            <th class="px-4 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;width:80px">Version</th>
                            <th class="px-4 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;width:80px">Default</th>
                            <th class="px-4 py-3 text-right" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;width:200px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($templates as $tmpl)
                    @php
                        $typeColors = ['CDI'=>['#edf7f2','#2e7d55'],'CDD'=>['#fef9ec','#9a7a1a'],'freelance'=>['#fdf3ee','#b55a2f'],'internship'=>['#eef3fb','#3a6fba'],'all'=>['#F5F4EF','#5c5c5a']];
                        [$tBg,$tTxt] = $typeColors[$tmpl->employment_type] ?? ['#F5F4EF','#5c5c5a'];
                    @endphp
                    <tr class="border-b border-hairline last:border-0 hover:bg-canvas transition-colors">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2">
                                <div class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background:{{ $tmpl->is_active ? '#2e7d55' : '#d0d0cc' }}"></div>
                                <span class="font-medium text-ink" style="font-size:13.5px">{{ $tmpl->name }}</span>
                                @if(! $tmpl->is_active)<span class="text-xs text-muted ml-1">(inactive)</span>@endif
                            </div>
                        </td>
                        <td class="px-4 py-3.5">
                            <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:5px;background:{{ $tBg }};color:{{ $tTxt }}">{{ $tmpl->employment_type }}</span>
                        </td>
                        <td class="px-4 py-3.5 text-dim uppercase font-medium" style="font-size:12px">{{ $tmpl->language }}</td>
                        <td class="px-4 py-3.5 text-muted" style="font-size:12px">{{ $tmpl->version }}</td>
                        <td class="px-4 py-3.5">
                            @if($tmpl->is_default)
                            <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:5px;background:#edf7f2;color:#2e7d55">Default</span>
                            @else
                            <span class="text-muted" style="font-size:12px">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5">
                            <div class="flex items-center justify-end gap-2">
                                <form method="POST" action="{{ route('contract-templates.preview', $tmpl) }}" target="_blank">
                                    @csrf
                                    <button type="submit" class="text-xs font-medium px-2.5 py-1 rounded-lg transition-colors cursor-pointer" style="background:#F5F4EF;border:1px solid #e5e4df;color:#5c5c5a">Preview</button>
                                </form>
                                <a href="{{ route('contract-templates.edit', $tmpl) }}"
                                   class="text-xs font-medium px-2.5 py-1 rounded-lg transition-colors"
                                   style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413">Edit</a>
                                <form method="POST" action="{{ route('contract-templates.destroy', $tmpl) }}"
                                      onsubmit="return confirm('Delete template {{ addslashes($tmpl->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs font-medium px-2.5 py-1 rounded-lg transition-colors cursor-pointer"
                                            style="background:#fff0f0;border:1px solid #ffd0d0;color:#b94040">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
</x-layouts.app>
