<x-layouts.app title="Checklist Templates">

<div class="flex items-center justify-between mb-5">
    <h1 style="font-size:24px;font-weight:600;color:#141413">
        Checklist Templates
        <span class="ml-2 text-muted font-normal" style="font-size:14px">{{ $templates->count() }}</span>
    </h1>
    <a href="{{ route('checklist-templates.create') }}"
       style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:13px;font-weight:500;background:#D97757;color:#fff;border-radius:8px;transition:background 150ms ease;text-decoration:none"
       onmouseover="this.style.background='#c4684a'" onmouseout="this.style.background='#D97757'">
        + New Template
    </a>
</div>

<p class="text-muted mb-5" style="font-size:13px;max-width:640px">
    Templates define a baseline checklist (Definition of Done) that is automatically attached to every new task
    matching their project and type rules. Template items can be checked off but not deleted from tasks.
</p>

@include('components.flash')

@if($templates->isEmpty())
    <div class="rounded-xl py-16 text-center" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
        <svg class="mx-auto mb-3" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#8c8c8a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><polyline points="9 13 11 15 15 11"/></svg>
        <p class="font-medium text-dim" style="font-size:14px">No checklist templates yet</p>
        <p class="text-muted mt-1" style="font-size:13px">Create a template to give every new task a guaranteed baseline checklist.</p>
        <a href="{{ route('checklist-templates.create') }}"
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
                    <th class="px-4 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;width:180px">Project</th>
                    <th class="px-4 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;width:120px">Task Type</th>
                    <th class="px-4 py-3 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;width:80px">Items</th>
                    <th class="px-4 py-3 text-right" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;width:160px">Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach($templates as $template)
            <tr class="border-b border-hairline last:border-0 hover:bg-canvas transition-colors">
                <td class="px-5 py-3.5">
                    <span class="font-medium text-ink" style="font-size:13.5px">{{ $template->name }}</span>
                    <p class="text-muted mt-0.5 truncate" style="font-size:12px;max-width:360px">
                        {{ $template->items->pluck('label')->take(3)->join(' · ') }}{{ $template->items_count > 3 ? ' …' : '' }}
                    </p>
                </td>
                <td class="px-4 py-3.5">
                    @if($template->project)
                        <span class="text-dim" style="font-size:12.5px">{{ $template->project->name }}</span>
                    @else
                        <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:5px;background:#F5F4EF;color:#5c5c5a">All projects</span>
                    @endif
                </td>
                <td class="px-4 py-3.5">
                    @if($template->type)
                        <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:5px;background:#fdf3ee;color:#b55a2f">{{ ucfirst($template->type) }}</span>
                    @else
                        <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:5px;background:#F5F4EF;color:#5c5c5a">All types</span>
                    @endif
                </td>
                <td class="px-4 py-3.5 text-muted" style="font-size:12px">{{ $template->items_count }}</td>
                <td class="px-4 py-3.5">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('checklist-templates.edit', $template) }}"
                           class="text-xs font-medium px-2.5 py-1 rounded-lg transition-colors"
                           style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413">Edit</a>
                        <form method="POST" action="{{ route('checklist-templates.destroy', $template) }}"
                              onsubmit="return confirm('Delete template {{ addslashes($template->name) }}? Items already attached to tasks are kept.')">
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
