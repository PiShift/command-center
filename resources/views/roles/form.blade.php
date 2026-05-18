<x-layouts.app :title="isset($role) ? 'Edit ' . $role->name : 'New Role'">

<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6 text-[13px]">
        <a href="{{ route('roles.index') }}" class="text-muted hover:text-ink">Roles</a>
        <span class="text-muted">/</span>
        <span class="text-ink">{{ isset($role) ? 'Edit' : 'New role' }}</span>
    </div>

    <div class="bg-white border border-line rounded-xl p-6">
        <h1 class="text-[15px] font-semibold text-ink mb-6">{{ isset($role) ? 'Edit ' . $role->name : 'New Role' }}</h1>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-[13px] text-red-700">
                <ul class="list-disc pl-4 space-y-1">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST"
              action="{{ isset($role) ? route('roles.update', $role) : route('roles.store') }}"
              class="space-y-5">
            @csrf
            @if(isset($role)) @method('PUT') @endif

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[12px] font-medium text-dim mb-1">Role Name *</label>
                    <input type="text" name="name" value="{{ old('name', $role->name ?? '') }}" required
                           class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                </div>
                <div>
                    <label class="block text-[12px] font-medium text-dim mb-1">Color</label>
                    <input type="color" name="color" value="{{ old('color', $role->color ?? '#5c5c5a') }}"
                           class="w-full h-10 border border-line rounded-lg cursor-pointer">
                </div>
                <div class="col-span-2">
                    <label class="block text-[12px] font-medium text-dim mb-1">Description</label>
                    <input type="text" name="description" value="{{ old('description', $role->description ?? '') }}"
                           class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                </div>
            </div>

            {{-- Permissions --}}
            @if($permissions->isNotEmpty())
            <div>
                <label class="block text-[12px] font-medium text-dim mb-3">Permissions</label>
                @php
                    $grouped = $permissions->groupBy(fn($p) => explode('.', $p->slug)[0]);
                    $currentPermissions = isset($role) ? $role->permissions->pluck('id')->toArray() : [];
                @endphp
                <div class="space-y-3">
                    @foreach($grouped as $resource => $perms)
                    <div class="border border-hairline rounded-lg p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-muted mb-3">{{ ucfirst($resource) }}</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($perms as $perm)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                                       {{ in_array($perm->id, old('permissions', $currentPermissions)) ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-line accent-accent">
                                <span class="text-[12px] text-ink">{{ $perm->slug }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-hairline">
                <a href="{{ route('roles.index') }}" class="px-4 py-2 text-[13px] text-dim hover:text-ink">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-accent hover:bg-accent-hover text-white text-[13px] font-semibold rounded-lg transition-colors">
                    {{ isset($role) ? 'Save changes' : 'Create role' }}
                </button>
            </div>
        </form>
    </div>
</div>

</x-layouts.app>
