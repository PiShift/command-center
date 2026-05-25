<x-layouts.app title="Roles">

<div class="flex items-center justify-between mb-6">
    <h1 class="text-[16px] font-semibold text-ink">Roles</h1>
    @if(auth()->user()->hasPermission('roles.create'))
    <a href="{{ route('roles.create') }}"
       class="inline-flex items-center gap-1.5 px-3 py-2 text-[12px] font-semibold bg-accent hover:bg-accent-hover text-white rounded-lg transition-colors">
        @include('components.icon', ['name' => 'plus'])
        New role
    </a>
    @endif
</div>

@include('components.flash')

<div class="bg-white border border-line rounded-xl">
    @if($roles->isEmpty())
        <div class="px-6 py-12 text-center text-[13px] text-muted">No roles yet.</div>
    @else
        <div class="overflow-x-auto">
        <table class="w-full text-[13px]" style="min-width:480px">
            <thead>
                <tr class="text-[11px] font-semibold uppercase tracking-wider text-muted border-b border-line bg-surface">
                    <th class="px-6 py-3 text-left">Role</th>
                    <th class="px-4 py-3 text-left hidden sm:table-cell">Slug</th>
                    <th class="px-4 py-3 text-left">Members</th>
                    <th class="px-4 py-3 text-left">Permissions</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-hairline">
                @foreach($roles as $role)
                <tr class="hover:bg-hairline">
                    <td class="px-6 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold text-white"
                              style="background: {{ $role->color ?? '#5c5c5a' }}">
                            {{ $role->name }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-muted font-mono text-[11px] hidden sm:table-cell">{{ $role->slug }}</td>
                    <td class="px-4 py-3 text-dim">{{ $role->users_count }}</td>
                    <td class="px-4 py-3 text-dim">{{ $role->permissions_count }}</td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            @if(auth()->user()->hasPermission('roles.edit'))
                            <a href="{{ route('roles.edit', $role) }}" class="text-muted hover:text-ink">
                                @include('components.icon', ['name' => 'pencil'])
                            </a>
                            @endif
                            @if(auth()->user()->hasPermission('roles.delete') && !$role->isSuperAdmin())
                            <form method="POST" action="{{ route('roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-muted hover:text-red-600">
                                    @include('components.icon', ['name' => 'trash'])
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @endif
</div>

</x-layouts.app>
