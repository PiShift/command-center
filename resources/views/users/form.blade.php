<x-layouts.app :title="isset($user) ? 'Edit ' . $user->name : 'Add team member'">

<div class="max-w-xl mx-auto">
    <div class="flex items-center gap-3 mb-6 text-[13px]">
        <a href="{{ route('users.index') }}" class="text-muted hover:text-ink">Users</a>
        <span class="text-muted">/</span>
        <span class="text-ink">{{ isset($user) ? 'Edit' : 'Add member' }}</span>
    </div>

    <div class="bg-white border border-line rounded-xl p-6">
        <h1 class="text-[15px] font-semibold text-ink mb-6">{{ isset($user) ? 'Edit ' . $user->name : 'Add Team Member' }}</h1>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-[13px] text-red-700">
                <ul class="list-disc pl-4 space-y-1">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST"
              action="{{ isset($user) ? route('users.update', $user) : route('users.store') }}"
              class="space-y-4">
            @csrf
            @if(isset($user)) @method('PUT') @endif

            <div>
                <label class="block text-[12px] font-medium text-dim mb-1">Name *</label>
                <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required
                       class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
            </div>
            <div>
                <label class="block text-[12px] font-medium text-dim mb-1">Email *</label>
                <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required
                       class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
            </div>
            <div>
                <label class="block text-[12px] font-medium text-dim mb-1">Password {{ isset($user) ? '(leave blank to keep current)' : '*' }}</label>
                <input type="password" name="password" {{ isset($user) ? '' : 'required' }}
                       class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
            </div>
            @if(isset($user))
            <div>
                <label class="block text-[12px] font-medium text-dim mb-1">Confirm Password</label>
                <input type="password" name="password_confirmation"
                       class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
            </div>
            @else
            <div>
                <label class="block text-[12px] font-medium text-dim mb-1">Confirm Password *</label>
                <input type="password" name="password_confirmation" required
                       class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
            </div>
            @endif
            <div>
                <label class="block text-[12px] font-medium text-dim mb-1">Role</label>
                <select name="role_id" class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                    <option value="">— No role —</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ old('role_id', $user->role_id ?? '') == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[12px] font-medium text-dim mb-1">Initials</label>
                    <input type="text" name="initials" maxlength="5" value="{{ old('initials', $user->initials ?? '') }}"
                           class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                </div>
                <div>
                    <label class="block text-[12px] font-medium text-dim mb-1">Color</label>
                    <input type="color" name="color" value="{{ old('color', $user->color ?? '#D97757') }}"
                           class="w-full h-10 border border-line rounded-lg cursor-pointer">
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-hairline">
                <a href="{{ route('users.index') }}" class="px-4 py-2 text-[13px] text-dim hover:text-ink">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-accent hover:bg-accent-hover text-white text-[13px] font-semibold rounded-lg transition-colors">
                    {{ isset($user) ? 'Save changes' : 'Add member' }}
                </button>
            </div>
        </form>
    </div>
</div>

</x-layouts.app>
