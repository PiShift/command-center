<x-layouts.app :title="$team ? 'Edit ' . $team->name : 'New Team'">

@php
    $isEdit = isset($team) && $team !== null;
    $existingMemberIds = $members->pluck('id')->toArray();
@endphp

<div class="flex items-center gap-3 mb-6" style="font-size:13px">
    <a href="{{ route('teams.index') }}" style="color:#8c8c8a;text-decoration:none;transition:color 150ms ease" onmouseover="this.style.color='#141413'" onmouseout="this.style.color='#8c8c8a'">Teams</a>
    <span style="color:#8c8c8a">/</span>
    <span style="color:#141413">{{ $isEdit ? 'Edit ' . $team->name : 'New Team' }}</span>
</div>

<div class="max-w-2xl">
<div style="background:#fff;border:1px solid #e5e4df;border-radius:12px;padding:24px;box-shadow:0 1px 3px rgba(20,20,19,0.04)"
     x-data="{
        search: '',
        selected: {{ json_encode($members->map(fn($m) => ['id' => $m->id, 'name' => $m->name, 'initials' => $m->initials ?? strtoupper(substr($m->name,0,2)), 'color' => $m->color ?? '#D97757'])->values()->toArray()) }},
        allUsers: {{ json_encode($users->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'initials' => $u->initials ?? strtoupper(substr($u->name,0,2)), 'color' => $u->color ?? '#D97757'])->toArray()) }},
        get filtered() {
            const q = this.search.toLowerCase();
            const selectedIds = this.selected.map(s => s.id);
            return this.allUsers.filter(u => !selectedIds.includes(u.id) && u.name.toLowerCase().includes(q));
        },
        addMember(user) {
            if (!this.selected.find(s => s.id === user.id)) {
                this.selected.push(user);
            }
            this.search = '';
        },
        removeMember(id) {
            this.selected = this.selected.filter(s => s.id !== id);
        }
     }">

    <h1 style="font-size:16px;font-weight:600;color:#141413;margin-bottom:24px">
        {{ $isEdit ? 'Edit Team' : 'New Team' }}
    </h1>

    @if ($errors->any())
    <div style="margin-bottom:16px;padding:12px 14px;background:#fff8f8;border:1px solid #ffd0d0;border-radius:8px;font-size:13px;color:#b94040">
        <ul style="list-style:disc;padding-left:16px;margin:0;display:flex;flex-direction:column;gap:2px">
            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST"
          action="{{ $isEdit ? route('teams.update', $team) : route('teams.store') }}"
          style="display:flex;flex-direction:column;gap:16px">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Name --}}
        <div>
            <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;margin-bottom:5px">
                Team Name <span style="color:#b94040">*</span>
            </label>
            <input type="text" name="name" value="{{ old('name', $team->name ?? '') }}" required
                   style="width:100%;padding:9px 12px;font-size:14px;color:#141413;background:#F5F4EF;border:1px solid #e5e4df;border-radius:8px;outline:none;transition:border-color 150ms ease,background 150ms ease;box-sizing:border-box"
                   onfocus="this.style.borderColor='#D97757';this.style.background='#fff'" onblur="this.style.borderColor='#e5e4df';this.style.background='#F5F4EF'">
        </div>

        {{-- Description --}}
        <div>
            <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;margin-bottom:5px">Description</label>
            <textarea name="description" rows="3"
                      style="width:100%;padding:9px 12px;font-size:14px;color:#141413;background:#F5F4EF;border:1px solid #e5e4df;border-radius:8px;outline:none;resize:vertical;line-height:1.5;transition:border-color 150ms ease,background 150ms ease;box-sizing:border-box"
                      onfocus="this.style.borderColor='#D97757';this.style.background='#fff'" onblur="this.style.borderColor='#e5e4df';this.style.background='#F5F4EF'">{{ old('description', $team->description ?? '') }}</textarea>
        </div>

        {{-- Lead --}}
        <div>
            <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;margin-bottom:5px">Team Lead</label>
            <select name="lead_user_id"
                    style="width:100%;padding:9px 12px;font-size:14px;color:#141413;background:#F5F4EF;border:1px solid #e5e4df;border-radius:8px;outline:none;transition:border-color 150ms ease,background 150ms ease;box-sizing:border-box"
                    onfocus="this.style.borderColor='#D97757';this.style.background='#fff'" onblur="this.style.borderColor='#e5e4df';this.style.background='#F5F4EF'">
                <option value="">— No lead —</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ old('lead_user_id', $team->lead_user_id ?? '') == $u->id ? 'selected' : '' }}>
                        {{ $u->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Member selector --}}
        <div>
            <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;margin-bottom:5px">Members</label>

            {{-- Selected members list --}}
            <div x-show="selected.length > 0"
                 style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px">
                <template x-for="member in selected" :key="member.id">
                    <div style="display:inline-flex;align-items:center;gap:6px;padding:4px 8px 4px 6px;background:#F5F4EF;border:1px solid #e5e4df;border-radius:20px">
                        <span :style="`width:20px;height:20px;border-radius:50%;background:${member.color};display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#fff;flex-shrink:0`"
                              x-text="member.initials"></span>
                        <span style="font-size:12px;font-weight:500;color:#141413" x-text="member.name"></span>
                        <button type="button" @click="removeMember(member.id)"
                                style="width:16px;height:16px;border-radius:50%;background:none;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#8c8c8a;padding:0;transition:color 150ms ease"
                                onmouseover="this.style.color='#b94040'" onmouseout="this.style.color='#8c8c8a'">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                        <input type="hidden" name="members[]" :value="member.id">
                    </div>
                </template>
            </div>

            {{-- Search input --}}
            <div style="position:relative">
                <input type="text" x-model="search" placeholder="Search users to add…"
                       style="width:100%;padding:9px 12px;font-size:13px;color:#141413;background:#F5F4EF;border:1px solid #e5e4df;border-radius:8px;outline:none;transition:border-color 150ms ease,background 150ms ease;box-sizing:border-box"
                       onfocus="this.style.borderColor='#D97757';this.style.background='#fff'" onblur="this.style.borderColor='#e5e4df';this.style.background='#F5F4EF'">

                {{-- Dropdown results --}}
                <div x-show="search.length > 0 && filtered.length > 0"
                     style="position:absolute;top:calc(100% + 4px);left:0;right:0;background:#fff;border:1px solid #e5e4df;border-radius:10px;box-shadow:0 4px 20px rgba(20,20,19,0.10);z-index:50;padding:6px;max-height:200px;overflow-y:auto">
                    <template x-for="user in filtered" :key="user.id">
                        <button type="button" @click="addMember(user)"
                                style="width:100%;display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:6px;background:none;border:none;cursor:pointer;text-align:left;transition:background 100ms ease"
                                onmouseover="this.style.background='#faf9f5'" onmouseout="this.style.background='none'">
                            <span :style="`width:28px;height:28px;border-radius:50%;background:${user.color};display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0`"
                                  x-text="user.initials"></span>
                            <span style="font-size:13px;font-weight:500;color:#141413" x-text="user.name"></span>
                        </button>
                    </template>
                </div>
                <div x-show="search.length > 0 && filtered.length === 0"
                     style="position:absolute;top:calc(100% + 4px);left:0;right:0;background:#fff;border:1px solid #e5e4df;border-radius:10px;box-shadow:0 4px 20px rgba(20,20,19,0.10);z-index:50;padding:16px;text-align:center;font-size:13px;color:#8c8c8a">
                    No users found
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;padding-top:16px;border-top:1px solid #eeeee9">
            <a href="{{ $isEdit ? route('teams.show', $team) : route('teams.index') }}"
               style="padding:8px 16px;font-size:13px;font-weight:500;color:#5c5c5a;background:#F5F4EF;border:1px solid #e5e4df;border-radius:8px;text-decoration:none;transition:background 150ms ease,border-color 150ms ease"
               onmouseover="this.style.background='#eeeee9';this.style.borderColor='#8c8c8a'" onmouseout="this.style.background='#F5F4EF';this.style.borderColor='#e5e4df'">
                Cancel
            </a>
            <button type="submit"
                    style="padding:8px 16px;font-size:13px;font-weight:500;color:#fff;background:#D97757;border:none;border-radius:8px;cursor:pointer;transition:background 150ms ease"
                    onmouseover="this.style.background='#c4684a'" onmouseout="this.style.background='#D97757'">
                {{ $isEdit ? 'Save changes' : 'Create team' }}
            </button>
        </div>
    </form>
</div>
</div>

</x-layouts.app>
