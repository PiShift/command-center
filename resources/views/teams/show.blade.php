<x-layouts.app :title="$team->name">

<div class="flex items-center gap-3 mb-6" style="font-size:13px">
    <a href="{{ route('teams.index') }}" style="color:#8c8c8a;text-decoration:none;transition:color 150ms ease" onmouseover="this.style.color='#141413'" onmouseout="this.style.color='#8c8c8a'">Teams</a>
    <span style="color:#8c8c8a">/</span>
    <span style="color:#141413">{{ $team->name }}</span>
</div>

@include('components.flash')

{{-- Header card --}}
<div style="background:#fff;border:1px solid #e5e4df;border-radius:12px;padding:24px;box-shadow:0 1px 3px rgba(20,20,19,0.04);margin-bottom:20px">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
        <div>
            <h1 style="font-size:24px;font-weight:600;color:#141413;margin-bottom:6px">{{ $team->name }}</h1>
            @if($team->description)
            <p style="font-size:13px;color:#5c5c5a;max-width:600px;line-height:1.5">{{ $team->description }}</p>
            @endif
            @if($team->lead)
            <div style="display:flex;align-items:center;gap:6px;margin-top:10px">
                <span style="width:26px;height:26px;border-radius:50%;background:{{ $team->lead->color ?? '#D97757' }};display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff">
                    {{ $team->lead->initials ?? strtoupper(substr($team->lead->name, 0, 2)) }}
                </span>
                <span style="font-size:13px;color:#5c5c5a">{{ $team->lead->name }}</span>
                <span style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;background:#F5F4EF;padding:1px 7px;border-radius:4px">Lead</span>
            </div>
            @endif
        </div>
        @if(auth()->user()->hasPermission('teams.manage'))
        <div style="display:flex;align-items:center;gap:8px">
            <a href="{{ route('teams.edit', $team) }}"
               style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;font-size:13px;font-weight:500;background:#F5F4EF;border:1px solid #e5e4df;border-radius:8px;text-decoration:none;color:#141413;transition:background 150ms ease"
               onmouseover="this.style.background='#eeeee9'" onmouseout="this.style.background='#F5F4EF'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Edit Team
            </a>
        </div>
        @endif
    </div>
</div>

{{-- Members section --}}
<div style="background:#fff;border:1px solid #e5e4df;border-radius:12px;box-shadow:0 1px 3px rgba(20,20,19,0.04);overflow:hidden;margin-bottom:20px">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #eeeee9">
        <span style="font-size:13px;font-weight:600;color:#141413">Members
            <span style="font-size:12px;font-weight:600;color:#8c8c8a;background:#F5F4EF;padding:2px 8px;border-radius:10px;margin-left:6px">{{ $team->members->count() }}</span>
        </span>
    </div>

    @if($team->members->isEmpty())
    <div style="padding:32px;text-align:center;color:#8c8c8a;font-size:13px">No members yet. Add someone below.</div>
    @else
    <table style="width:100%;font-size:13.5px;border-collapse:collapse">
        <thead>
            <tr style="background:#faf9f5;border-bottom:1px solid #e5e4df">
                <th style="padding:10px 20px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Member</th>
                <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Email</th>
                <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Joined</th>
                @if(auth()->user()->hasPermission('teams.manage'))
                <th style="padding:10px 16px;text-align:right;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a"></th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($team->members as $member)
            <tr style="border-bottom:1px solid #eeeee9;transition:background 120ms ease"
                x-data="{ confirmRemove: false }"
                onmouseover="this.style.background='#faf9f5'" onmouseout="this.style.background='#fff'">
                <td style="padding:12px 20px">
                    <div style="display:flex;align-items:center;gap:10px">
                        <span style="width:34px;height:34px;border-radius:50%;background:{{ $member->color ?? '#D97757' }};display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0">
                            {{ $member->initials ?? strtoupper(substr($member->name, 0, 2)) }}
                        </span>
                        <div>
                            <div style="font-weight:500;color:#141413">{{ $member->name }}</div>
                            @if($team->lead_user_id === $member->id)
                            <span style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#D97757">Lead</span>
                            @endif
                        </div>
                    </div>
                </td>
                <td style="padding:12px 16px;color:#5c5c5a">{{ $member->email }}</td>
                <td style="padding:12px 16px;color:#8c8c8a;font-size:12px">
                    {{ $member->pivot->created_at?->format('M j, Y') ?? '—' }}
                </td>
                @if(auth()->user()->hasPermission('teams.manage'))
                <td style="padding:12px 16px;text-align:right">
                    <div x-show="!confirmRemove">
                        <button type="button" @click="confirmRemove = true"
                                style="padding:4px 10px;font-size:12px;font-weight:500;background:none;border:1px solid #e5e4df;border-radius:6px;cursor:pointer;color:#8c8c8a;transition:all 150ms ease"
                                onmouseover="this.style.background='#fff0f0';this.style.borderColor='#ffd0d0';this.style.color='#b94040'" onmouseout="this.style.background='none';this.style.borderColor='#e5e4df';this.style.color='#8c8c8a'">
                            Remove
                        </button>
                    </div>
                    <div x-show="confirmRemove" style="display:flex;align-items:center;justify-content:flex-end;gap:6px">
                        <button type="button" @click="confirmRemove = false"
                                style="padding:4px 10px;font-size:12px;font-weight:500;background:#F5F4EF;border:1px solid #e5e4df;border-radius:6px;cursor:pointer;color:#141413;transition:background 150ms ease"
                                onmouseover="this.style.background='#eeeee9'" onmouseout="this.style.background='#F5F4EF'">
                            Cancel
                        </button>
                        <form method="POST" action="{{ route('teams.members.destroy', [$team, $member]) }}" style="margin:0">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    style="padding:4px 10px;font-size:12px;font-weight:500;background:#fff0f0;border:1px solid #ffd0d0;border-radius:6px;cursor:pointer;color:#b94040;transition:background 150ms ease"
                                    onmouseover="this.style.background='#ffe0e0'" onmouseout="this.style.background='#fff0f0'">
                                Confirm
                            </button>
                        </form>
                    </div>
                </td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Add member form --}}
    @if(auth()->user()->hasPermission('teams.manage') && $availableUsers->isNotEmpty())
    <div style="padding:14px 20px;border-top:1px solid #eeeee9;background:#faf9f5">
        <form method="POST" action="{{ route('teams.members.store', $team) }}"
              style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            @csrf
            <select name="user_id" required
                    style="flex:1;min-width:180px;padding:7px 10px;font-size:13px;color:#141413;background:#fff;border:1px solid #e5e4df;border-radius:8px;outline:none;transition:border-color 150ms ease"
                    onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'">
                <option value="">— Select a member to add —</option>
                @foreach($availableUsers as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
            <button type="submit"
                    style="padding:7px 14px;font-size:13px;font-weight:500;color:#fff;background:#D97757;border:none;border-radius:8px;cursor:pointer;transition:background 150ms ease;white-space:nowrap"
                    onmouseover="this.style.background='#c4684a'" onmouseout="this.style.background='#D97757'">
                Add Member
            </button>
        </form>
    </div>
    @endif
</div>

{{-- Projects section --}}
<div style="background:#fff;border:1px solid #e5e4df;border-radius:12px;box-shadow:0 1px 3px rgba(20,20,19,0.04);overflow:hidden">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #eeeee9">
        <span style="font-size:13px;font-weight:600;color:#141413">Projects
            <span style="font-size:12px;font-weight:600;color:#8c8c8a;background:#F5F4EF;padding:2px 8px;border-radius:10px;margin-left:6px">{{ $team->projects->count() }}</span>
        </span>
    </div>

    @if($team->projects->isEmpty())
    <div style="padding:32px;text-align:center;color:#8c8c8a;font-size:13px">No projects assigned to this team yet.</div>
    @else
    <table style="width:100%;font-size:13.5px;border-collapse:collapse">
        <thead>
            <tr style="background:#faf9f5;border-bottom:1px solid #e5e4df">
                <th style="padding:10px 20px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Project</th>
                <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a">Status</th>
                @if(auth()->user()->hasPermission('teams.manage'))
                <th style="padding:10px 16px;text-align:right;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a"></th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($team->projects as $project)
            @php
                $statusMap = [
                    'active'    => ['bg' => '#fdf3ee', 'text' => '#D97757', 'label' => 'Active'],
                    'paused'    => ['bg' => '#F5F4EF', 'text' => '#8c8c8a', 'label' => 'Paused'],
                    'complete'  => ['bg' => '#edf7f2', 'text' => '#2e7d55', 'label' => 'Complete'],
                    'cancelled' => ['bg' => '#fdf0f0', 'text' => '#b94040', 'label' => 'Cancelled'],
                ];
                $badge = $statusMap[$project->status] ?? ['bg' => '#F5F4EF', 'text' => '#8c8c8a', 'label' => ucfirst($project->status)];
            @endphp
            <tr style="border-bottom:1px solid #eeeee9;transition:background 120ms ease"
                x-data="{ confirmDetach: false }"
                onmouseover="this.style.background='#faf9f5'" onmouseout="this.style.background='#fff'">
                <td style="padding:12px 20px">
                    <div style="display:flex;align-items:center;gap:8px">
                        <span style="width:10px;height:10px;border-radius:50%;background:{{ $project->color ?? '#4a90d9' }};flex-shrink:0"></span>
                        <a href="{{ route('projects.show', $project) }}"
                           style="font-weight:500;color:#141413;text-decoration:none;transition:color 150ms ease"
                           onmouseover="this.style.color='#D97757'" onmouseout="this.style.color='#141413'">
                            {{ $project->name }}
                        </a>
                    </div>
                </td>
                <td style="padding:12px 16px">
                    <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:5px;background:{{ $badge['bg'] }};color:{{ $badge['text'] }}">
                        {{ $badge['label'] }}
                    </span>
                </td>
                @if(auth()->user()->hasPermission('teams.manage'))
                <td style="padding:12px 16px;text-align:right">
                    <div x-show="!confirmDetach">
                        <button type="button" @click="confirmDetach = true"
                                style="padding:4px 10px;font-size:12px;font-weight:500;background:none;border:1px solid #e5e4df;border-radius:6px;cursor:pointer;color:#8c8c8a;transition:all 150ms ease"
                                onmouseover="this.style.background='#fff0f0';this.style.borderColor='#ffd0d0';this.style.color='#b94040'" onmouseout="this.style.background='none';this.style.borderColor='#e5e4df';this.style.color='#8c8c8a'">
                            Detach
                        </button>
                    </div>
                    <div x-show="confirmDetach" style="display:flex;align-items:center;justify-content:flex-end;gap:6px">
                        <button type="button" @click="confirmDetach = false"
                                style="padding:4px 10px;font-size:12px;font-weight:500;background:#F5F4EF;border:1px solid #e5e4df;border-radius:6px;cursor:pointer;color:#141413;transition:background 150ms ease"
                                onmouseover="this.style.background='#eeeee9'" onmouseout="this.style.background='#F5F4EF'">
                            Cancel
                        </button>
                        <form method="POST" action="{{ route('projects.teams.destroy', [$project, $team]) }}" style="margin:0">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    style="padding:4px 10px;font-size:12px;font-weight:500;background:#fff0f0;border:1px solid #ffd0d0;border-radius:6px;cursor:pointer;color:#b94040;transition:background 150ms ease"
                                    onmouseover="this.style.background='#ffe0e0'" onmouseout="this.style.background='#fff0f0'">
                                Confirm
                            </button>
                        </form>
                    </div>
                </td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Assign to project form --}}
    @if(auth()->user()->hasPermission('teams.manage') && $availableProjects->isNotEmpty())
    <div style="padding:14px 20px;border-top:1px solid #eeeee9;background:#faf9f5"
         x-data="{ selectedProject: '' }">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <select x-model="selectedProject" required
                    style="flex:1;min-width:200px;padding:7px 10px;font-size:13px;color:#141413;background:#fff;border:1px solid #e5e4df;border-radius:8px;outline:none;transition:border-color 150ms ease"
                    onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'">
                <option value="">— Assign to a project —</option>
                @foreach($availableProjects as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>
            <template x-if="selectedProject">
                <form :action="`/projects/${selectedProject}/teams`" method="POST" style="margin:0">
                    @csrf
                    <input type="hidden" name="team_id" value="{{ $team->id }}">
                    <button type="submit"
                            style="padding:7px 14px;font-size:13px;font-weight:500;color:#fff;background:#D97757;border:none;border-radius:8px;cursor:pointer;transition:background 150ms ease;white-space:nowrap"
                            onmouseover="this.style.background='#c4684a'" onmouseout="this.style.background='#D97757'">
                        Assign
                    </button>
                </form>
            </template>
            <template x-if="!selectedProject">
                <button type="button" disabled
                        style="padding:7px 14px;font-size:13px;font-weight:500;color:#fff;background:#D97757;border:none;border-radius:8px;cursor:not-allowed;opacity:0.4;white-space:nowrap">
                    Assign
                </button>
            </template>
        </div>
    </div>
    @endif
</div>

</x-layouts.app>
