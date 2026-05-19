<div class="flex flex-col h-full">

    {{-- ── Header row: tabs left · filters right ────────────────────────────── --}}
    <div class="flex items-center justify-between gap-4 mb-6 flex-wrap">

        {{-- Tab switcher --}}
        <div class="flex items-center gap-0.5 rounded-lg p-1" style="background:#F5F4EF">
            @foreach([['board','Board'],['projects', $scopedToUser ? 'My Projects' : 'Projects'],['team', $scopedToUser ? 'My Teams' : 'Team']] as [$tab,$label])
            <button wire:click="$set('activeTab', '{{ $tab }}')"
                    class="px-4 py-1.5 text-[13px] rounded-md transition-all duration-150 cursor-pointer"
                    style="{{ $activeTab === $tab
                        ? 'background:#faf9f5; color:#141413; font-weight:500; box-shadow:0 1px 3px rgba(20,20,19,0.08)'
                        : 'background:transparent; color:#8c8c8a; font-weight:400' }}"
                    onmouseover="{{ $activeTab !== $tab ? 'this.style.color=\'#5c5c5a\'' : '' }}"
                    onmouseout="{{ $activeTab !== $tab ? 'this.style.color=\'#8c8c8a\'' : '' }}">
                {{ $label }}
            </button>
            @endforeach
        </div>

        {{-- Filters + primary action (board only) --}}
        @if($activeTab === 'board')
        <div class="flex items-center gap-2">
            <div class="relative">
                <select wire:model.live="filterProject"
                        class="appearance-none text-[13px] pl-3 pr-8 py-2 rounded-lg cursor-pointer transition-colors"
                        style="background: #F5F4EF; border: 1px solid #e5e4df; color: #141413; outline: none">
                    <option value="">All Projects</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
                <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2" style="color: #8c8c8a">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </span>
            </div>
            <div class="relative">
                <select wire:model.live="filterAssignee"
                        class="appearance-none text-[13px] pl-3 pr-8 py-2 rounded-lg cursor-pointer transition-colors"
                        style="background: #F5F4EF; border: 1px solid #e5e4df; color: #141413; outline: none">
                    <option value="">All Members</option>
                    @foreach($teamMembers as $m)
                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                    @endforeach
                </select>
                <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2" style="color: #8c8c8a">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </span>
            </div>
            <div class="relative">
                <select wire:model.live="filterPriority"
                        class="appearance-none text-[13px] pl-3 pr-8 py-2 rounded-lg cursor-pointer transition-colors"
                        style="background: #F5F4EF; border: 1px solid #e5e4df; color: #141413; outline: none">
                    <option value="">All Priorities</option>
                    <option value="critical">Critical</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>
                <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2" style="color: #8c8c8a">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </span>
            </div>
            <button type="button"
                    x-on:click="$dispatch('new-task', {})"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-[13px] font-medium bg-accent hover:bg-accent-hover text-white rounded-lg transition-colors whitespace-nowrap cursor-pointer">
                + Add Task
            </button>
        </div>
        @endif
    </div>

    {{-- ── Board Tab ────────────────────────────────────────────────────────── --}}
    @if($activeTab === 'board')
    <div class="flex gap-5 overflow-x-auto pb-4 flex-1 min-h-0">
        @foreach($columns as $col)
        @php
            $accentHex = $col->color_hex;
        @endphp
        <div class="shrink-0 w-[300px] h-full flex flex-col rounded-xl overflow-hidden"
             wire:key="col-{{ $col->id }}"
             style="background: #F5F4EF">

            {{-- Column header — no border, Surface bg, colored dot accent --}}
            <div class="flex items-center gap-2 px-3 pt-3 pb-2"
                 style="background: #F5F4EF; border-bottom: 1px solid #eeeee9">
                <span class="w-2 h-2 rounded-full shrink-0" style="background: {{ $accentHex }}"></span>
                <span class="text-[11px] font-bold uppercase tracking-widest flex-1" style="color: #5c5c5a; letter-spacing: 0.07em">{{ $col->name }}</span>
                <span class="text-[11px] font-semibold rounded-[5px] px-2 py-0.5 leading-none" style="background: #ffffff; color: #8c8c8a">{{ $col->tasks->count() }}</span>
            </div>

            {{-- Cards drop zone --}}
            <div class="flex flex-col gap-2 flex-1 min-h-0 overflow-y-auto px-2.5 pt-2 pb-1 transition-colors"
                 style="background: #F5F4EF"
                 x-on:dragover.prevent="$el.style.background='rgba(217,119,87,0.05)'; $el.style.transition='background 150ms ease'"
                 x-on:dragleave="$el.style.background='#F5F4EF'"
                 x-on:drop.prevent="$el.style.background='#F5F4EF'; $wire.moveTask(parseInt($event.dataTransfer.getData('taskId')), '{{ $col->slug }}')">

                @forelse($col->tasks as $task)
                {{-- Task card --}}
                <div class="bg-white rounded-[10px] cursor-pointer transition-all shadow-[0_1px_3px_rgba(20,20,19,0.04)] hover:shadow-[0_4px_14px_rgba(20,20,19,0.08)]"
                     style="border: 1px solid #eeeee9"
                     draggable="true"
                     wire:key="task-{{ $task->id }}"
                     x-on:dragstart.stop="$event.dataTransfer.setData('taskId', '{{ $task->id }}'); $event.dataTransfer.effectAllowed='move'"
                     x-on:mouseenter="$el.style.borderColor='#e5e4df'"
                     x-on:mouseleave="$el.style.borderColor='#eeeee9'"
                     x-on:click="$dispatch('open-task', { id: {{ $task->id }} })">
                    <div class="p-3.5">
                        {{-- Title --}}
                        <p class="text-[13.5px] font-medium text-ink leading-snug mb-1 line-clamp-2">{{ $task->title }}</p>

                        {{-- "View & Claim" button — unassigned tasks only; opens modal so dev reads before claiming --}}
                        @if(! $task->assigned_to && \Illuminate\Support\Facades\Gate::allows('claim', $task))
                        <div x-on:click.stop>
                            <button x-on:click="$dispatch('open-task', { id: {{ $task->id }} })"
                                    class="inline-flex items-center gap-1 mb-2 px-2 py-0.5 text-[11px] font-semibold rounded-[5px] border cursor-pointer transition-colors duration-150"
                                    style="color:#2e7d55;background:#edf7f2;border-color:#b7e0ca"
                                    onmouseover="this.style.background='#d6f0e4'"
                                    onmouseout="this.style.background='#edf7f2'"
                                    title="View task details and claim">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672 13.684 16.6m0 0-2.51 2.225.569-9.47 5.227 7.917-3.286-.672ZM12 2.25V4.5m5.834.166-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243-1.59-1.59"/>
                                </svg>
                                View & Claim
                            </button>
                        </div>
                        @endif

                        {{-- Notes preview --}}
                        @if($task->description)
                        <p class="text-[12px] text-muted leading-relaxed mb-3 truncate">{{ $task->description }}</p>
                        @endif

                        {{-- Tag row: project · assignee · priority (in that order, max 3) --}}
                        <div class="flex items-center gap-1.5 flex-wrap">
                            {{-- Project tag --}}
                            @if($task->project)
                            <span class="inline-flex items-center text-[11px] font-semibold rounded-[5px] px-2 py-0.5"
                                  style="background: #eef3fb; color: #3a6fba; border-left: 3px solid {{ $task->project->color ?? '#D97757' }}; padding-left: 5px;">
                                {{ $task->project->name }}
                            </span>
                            @endif

                            {{-- Assignee tag --}}
                            @if($task->assignee)
                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold rounded-[5px] px-2 py-0.5"
                                  style="background: #fdf3ee; color: #b55a2f;">
                                <span class="w-3.5 h-3.5 rounded-full text-[8px] font-bold text-white flex items-center justify-center shrink-0"
                                      style="background: {{ $task->assignee->color ?? '#D97757' }}">{{ strtoupper(substr($task->assignee->name, 0, 1)) }}</span>
                                {{ $task->assignee->name }}
                            </span>
                            @endif

                            {{-- Right: comment count + priority badge --}}
                            @php
                                $pBadge = match($task->priority) {
                                    'critical' => ['↑↑ Critical', '#b94040', '#fdf0f0'],
                                    'high'     => ['↑ High',     '#b94040', '#fdf0f0'],
                                    'medium'   => ['→ Medium',   '#9a7a1a', '#fef9ec'],
                                    default    => ['↓ Low',      '#2e7d55', '#edf7f2'],
                                };
                            @endphp
                            <span class="ml-auto flex items-center gap-1.5">
                                @if(($task->comments_count ?? 0) > 0)
                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-muted">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.963 9.963 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                    {{ $task->comments_count }}
                                </span>
                                @endif
                                @if($task->relationLoaded('checklists') && $task->checklists->count() > 0)
                                @php
                                    $clTotal   = $task->checklists->count();
                                    $clDone    = $task->checklists->where('is_checked', true)->count();
                                    $clAllDone = $clDone === $clTotal;
                                @endphp
                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold"
                                      style="color: {{ $clAllDone ? '#2e7d55' : '#8c8c8a' }}">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ $clDone }}/{{ $clTotal }}
                                </span>
                                @endif
                                <span class="inline-flex items-center text-[11px] font-semibold rounded-[5px] px-2 py-0.5"
                                      style="background: {{ $pBadge[2] }}; color: {{ $pBadge[1] }}">{{ $pBadge[0] }}</span>
                            </span>
                        </div>
                    </div>
                </div>
                @empty
                <p class="text-[13px] text-muted text-center py-8 select-none">No tasks</p>
                @endforelse

            </div>

            {{-- Add task — always visible at column bottom --}}
            <div class="px-2.5 pb-2.5 pt-1 shrink-0" style="background:#F5F4EF">
                <button type="button"
                        x-on:click="$dispatch('new-task', {})"
                        class="block w-full text-center text-[13px] font-medium text-muted hover:text-accent border border-dashed border-line hover:border-accent py-2 rounded-lg transition-colors cursor-pointer">
                    + Add task
                </button>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ── Projects Tab ─────────────────────────────────────────────────────── --}}
    @if($activeTab === 'projects')
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($projects as $project)
        <div class="bg-white border border-hairline rounded-xl p-4"
             style="box-shadow: 0 1px 3px rgba(20,20,19,0.04)">
            <div class="flex items-start gap-3 mb-3">
                <span class="w-3 h-3 rounded-full mt-0.5 shrink-0" style="background: {{ $project->color ?? '#D97757' }}"></span>
                <div class="flex-1 min-w-0">
                    <a href="{{ route('projects.show', $project) }}"
                       class="text-[14px] font-medium text-ink hover:underline truncate block">{{ $project->name }}</a>
                    @if($project->customer)
                    <p class="text-[12px] text-muted mt-0.5">{{ $project->customer->name }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    @if($scopedToUser && ($project->claimable_count ?? 0) > 0)
                    <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-semibold rounded-full"
                          style="background:#edf7f2;color:#2e7d55">
                        {{ $project->claimable_count }} to claim
                    </span>
                    @endif
                    @include('components.badge', ['type' => 'project_status', 'value' => $project->status])
                </div>
            </div>
            @php
                // For team projects: show all project tasks. For direct: show user's assigned tasks.
                $displayTasks = ($scopedToUser && $project->is_team_project)
                    ? $project->tasks
                    : ($scopedToUser ? $project->my_tasks : $project->tasks);
                $open  = $displayTasks->whereNotIn('status', ['done'])->count();
                $total = $displayTasks->count();
                $pct   = $total > 0 ? round(($total - $open) / $total * 100) : 0;
            @endphp
            <div class="flex items-center justify-between text-[12px] text-muted mb-1.5">
                <span>{{ $open }} open
                    @if($scopedToUser)
                        {{ $project->is_team_project ? '— team project' : '— assigned to you' }}
                    @endif
                </span>
                <span>{{ $pct }}%</span>
            </div>
            <div class="w-full bg-hairline rounded-full h-1.5 mb-3">
                <div class="h-1.5 rounded-full bg-accent" style="width: {{ $pct }}%"></div>
            </div>
            @if($scopedToUser && $displayTasks->isNotEmpty())
            <div class="space-y-1.5 border-t border-hairline pt-3">
                @foreach($displayTasks->sortBy(fn($t) => ['critical'=>0,'high'=>1,'medium'=>2,'low'=>3][$t->priority] ?? 9)->take(5) as $t)
                <div class="flex items-center gap-2">
                    @php
                        $dotColors = ['critical'=>'#b94040','high'=>'#D97757','medium'=>'#4a90d9','low'=>'#8c8c8a'];
                    @endphp
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background:{{ $dotColors[$t->priority] ?? '#8c8c8a' }}"></span>
                    <a href="{{ route('tasks.show', $t) }}" class="text-[12px] text-ink hover:underline truncate flex-1">{{ $t->title }}</a>
                    @if($project->is_team_project && $t->assignee)
                    <span class="text-[11px] text-muted shrink-0">{{ $t->assignee->name }}</span>
                    @endif
                    @include('components.badge', ['type' => 'status', 'value' => $t->status])
                </div>
                @endforeach
                @if($displayTasks->count() > 5)
                <p class="text-[11px] text-muted pt-0.5">+ {{ $displayTasks->count() - 5 }} more tasks</p>
                @endif
            </div>
            @elseif($scopedToUser)
            <p class="text-[12px] text-muted border-t border-hairline pt-3">No tasks in this project yet</p>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- ── Team Tab ─────────────────────────────────────────────────────────── --}}
    @if($activeTab === 'team')

    {{-- Developer: My Teams view --}}
    @if($scopedToUser)
        @if($userTeams->isEmpty())
        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:80px 0;color:#8c8c8a">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:12px;opacity:0.5"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
            <p style="font-size:14px;font-weight:500;color:#5c5c5a">You're not in any team yet</p>
            <p style="font-size:13px;margin-top:4px">Ask your manager to add you to a team.</p>
        </div>
        @else
        <div style="display:flex;flex-direction:column;gap:16px">
            @foreach($userTeams as $team)
            <div style="background:#fff;border:1px solid #e5e4df;border-radius:12px;box-shadow:0 1px 3px rgba(20,20,19,0.04);overflow:hidden">
                {{-- Team header --}}
                <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #eeeee9">
                    <div>
                        <a href="{{ route('teams.show', $team) }}"
                           style="font-size:15px;font-weight:600;color:#141413;text-decoration:none;transition:color 150ms ease"
                           onmouseover="this.style.color='#D97757'" onmouseout="this.style.color='#141413'">
                            {{ $team->name }}
                        </a>
                        @if($team->description)
                        <p style="font-size:12px;color:#5c5c5a;margin-top:2px">{{ $team->description }}</p>
                        @endif
                    </div>
                    <div style="display:flex;align-items:center;gap:10px">
                        {{-- Lead badge --}}
                        @if($team->lead)
                        <div style="display:flex;align-items:center;gap:5px">
                            <span style="width:22px;height:22px;border-radius:50%;background:{{ $team->lead->color ?? '#D97757' }};display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#fff">
                                {{ $team->lead->initials ?? strtoupper(substr($team->lead->name, 0, 2)) }}
                            </span>
                            <span style="font-size:12px;color:#5c5c5a">{{ $team->lead->name }}</span>
                            <span style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;background:#F5F4EF;padding:1px 6px;border-radius:4px">Lead</span>
                        </div>
                        @endif
                        {{-- Member count --}}
                        <span style="font-size:12px;font-weight:600;color:#8c8c8a;background:#F5F4EF;padding:2px 8px;border-radius:10px">
                            {{ $team->members_count }} {{ $team->members_count === 1 ? 'member' : 'members' }}
                        </span>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0">
                    {{-- Members list --}}
                    <div style="padding:14px 20px;border-right:1px solid #eeeee9">
                        <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;margin-bottom:10px">Members</p>
                        <div style="display:flex;flex-direction:column;gap:8px">
                            @foreach($team->members as $member)
                            <div style="display:flex;align-items:center;gap:8px">
                                <span style="width:28px;height:28px;border-radius:50%;background:{{ $member->color ?? '#D97757' }};display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;flex-shrink:0">
                                    {{ $member->initials ?? strtoupper(substr($member->name, 0, 2)) }}
                                </span>
                                <div>
                                    <span style="font-size:13px;font-weight:{{ $member->id === auth()->id() ? '600' : '400' }};color:#141413">
                                        {{ $member->name }}{{ $member->id === auth()->id() ? ' (you)' : '' }}
                                    </span>
                                    @if($team->lead_user_id === $member->id)
                                    <span style="font-size:10px;font-weight:600;color:#D97757;margin-left:5px">Lead</span>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Assigned projects --}}
                    <div style="padding:14px 20px">
                        <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;margin-bottom:10px">Projects</p>
                        @if($team->projects->isEmpty())
                        <p style="font-size:12px;color:#8c8c8a">No projects assigned to this team.</p>
                        @else
                        <div style="display:flex;flex-direction:column;gap:7px">
                            @foreach($team->projects as $proj)
                            @php
                                $statusColors = ['active'=>['#fdf3ee','#D97757'],'paused'=>['#F5F4EF','#8c8c8a'],'complete'=>['#edf7f2','#2e7d55'],'cancelled'=>['#fdf0f0','#b94040']];
                                [$sBg, $sText] = $statusColors[$proj->status] ?? ['#F5F4EF','#8c8c8a'];
                            @endphp
                            <div style="display:flex;align-items:center;gap:8px">
                                <span style="width:8px;height:8px;border-radius:50%;background:{{ $proj->color ?? '#4a90d9' }};flex-shrink:0"></span>
                                <a href="{{ route('projects.show', $proj) }}"
                                   style="font-size:13px;color:#141413;text-decoration:none;flex:1;transition:color 150ms ease"
                                   onmouseover="this.style.color='#D97757'" onmouseout="this.style.color='#141413'">
                                    {{ $proj->name }}
                                </a>
                                <span style="font-size:11px;font-weight:600;padding:1px 7px;border-radius:5px;background:{{ $sBg }};color:{{ $sText }}">
                                    {{ ucfirst($proj->status) }}
                                </span>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

    {{-- Non-developer: original team member cards --}}
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($teamMembers as $member)
        @php
            $myTasks = $member->tasks()->whereNotIn('status', ['done'])->with('project')->orderBy('due_date')->get();
        @endphp
        <div class="bg-white border border-hairline rounded-xl p-4"
             style="box-shadow: 0 1px 3px rgba(20,20,19,0.04)">
            <div class="flex items-center gap-3 mb-3">
                <span class="w-9 h-9 rounded-full flex items-center justify-center text-[13px] font-bold text-white shrink-0"
                      style="background: {{ $member->color ?? '#D97757' }}">
                    {{ $member->initials ?? strtoupper(substr($member->name, 0, 2)) }}
                </span>
                <div>
                    <p class="text-[14px] font-semibold text-ink">{{ $member->name }}</p>
                    <p class="text-[12px] text-muted">{{ $myTasks->count() }} open tasks</p>
                </div>
            </div>
            <div class="space-y-1.5">
                @forelse($myTasks->take(5) as $t)
                <div class="flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background: {{ $t->project->color ?? '#e5e4df' }}"></span>
                    <a href="{{ route('tasks.show', $t) }}" class="text-[13px] text-ink hover:underline truncate flex-1">{{ $t->title }}</a>
                    @include('components.badge', ['type' => 'priority', 'value' => $t->priority])
                </div>
                @empty
                <p class="text-[12px] text-muted">No open tasks</p>
                @endforelse
                @if($myTasks->count() > 5)
                <p class="text-[12px] text-muted pt-1">+ {{ $myTasks->count() - 5 }} more</p>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    @endif

</div>
