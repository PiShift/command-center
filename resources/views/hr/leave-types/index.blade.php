<x-layouts.app :title="'Leave Types'">
    @php
        $palette = ['#3b82f6', '#f59e0b', '#6b7280', '#ec4899', '#8b5cf6', '#06b6d4', '#10b981', '#ef4444'];
        $defaultColor = old('color', $palette[array_rand($palette)]);
        $hasLeaveTypes = $leaveTypes->isNotEmpty();
    @endphp

    <div
        x-data="{
            createOpen: {{ $errors->any() && !old('editing_id') ? 'true' : 'false' }},
            activeEdit: null,
            flashList: {{ session('success') ? 'true' : 'false' }},
            createColor: @js($defaultColor),
            palette: @js($palette),
            openEdit(id) {
                this.activeEdit = this.activeEdit === id ? null : id;
            },
            setCreateColor(color) {
                this.createColor = color;
            }
        }"
        x-init="if (flashList) { setTimeout(() => flashList = false, 1200) }"
        class="space-y-6"
    >
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
                <p class="text-[12px] font-bold uppercase tracking-[0.08em] text-muted">Human Resources</p>
                <div>
                    <h1 class="text-ink" style="font-size:24px;font-weight:600;line-height:1.2">Leave Types</h1>
                    <p class="mt-1 text-[13px] text-dim">Manage leave categories for your employees.</p>
                </div>
            </div>

            <button
                type="button"
                @click="createOpen = true"
                class="rounded-lg bg-accent px-4 py-2 text-[13px] font-medium text-white transition-colors duration-150 hover:bg-accent-hover cursor-pointer"
            >
                + New Leave Type
            </button>
        </div>

        @if(session('success'))
            <div x-show="flashList" x-cloak x-transition:enter="transition-opacity duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="rounded-lg border border-[#c6e8d5] bg-[#edf7f2] px-4 py-3 text-[13px] font-medium text-[#2e7d55]">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-lg border border-[#ffd0d0] bg-[#fff0f0] px-4 py-3 text-[13px] font-medium text-[#b94040]">
                {{ session('error') }}
            </div>
        @endif

        @if(!$hasLeaveTypes)
            <div class="flex min-h-[52vh] items-center justify-center rounded-2xl border border-line bg-white shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                <div class="max-w-sm text-center space-y-3 px-6 py-10">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-surface text-muted">
                        @include('components.icon', ['name' => 'calendar'])
                    </div>
                    <div class="space-y-1">
                        <p class="text-[14px] font-medium text-ink">No leave types yet. Create your first one.</p>
                        <p class="text-[13px] text-muted">Set up the leave categories your team can request.</p>
                    </div>
                    <button type="button" @click="createOpen = true" class="rounded-lg bg-accent px-4 py-2 text-[13px] font-medium text-white transition-colors duration-150 hover:bg-accent-hover cursor-pointer">
                        + New Leave Type
                    </button>
                </div>
            </div>
        @else
            <div class="space-y-4">
                @foreach($leaveTypes as $leaveType)
                    @php
                        $isSystem = (bool) $leaveType->is_system;
                        $settings = collect([
                            $leaveType->is_paid ? 'Paid' : 'Unpaid',
                            $leaveType->requires_approval ? 'Approval required' : 'Auto-approved',
                            $leaveType->accrues_monthly ? 'Accrues monthly' : null,
                            filled($leaveType->default_days_per_year) ? number_format((float) $leaveType->default_days_per_year, 0) . ' days/year' : null,
                        ])->filter()->values();
                    @endphp

                    <div
                        class="overflow-hidden rounded-2xl border border-line bg-white shadow-[0_1px_3px_rgba(20,20,19,0.04)] transition-all duration-150 hover:border-line hover:shadow-[0_4px_14px_rgba(20,20,19,0.08)]"
                        :class="flashList ? 'bg-[#edf7f2] border-[#c6e8d5]' : 'bg-white'"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-4 px-4 py-4 md:px-5">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="mt-1 h-3 w-3 flex-shrink-0 rounded-full" style="background: {{ $leaveType->color }}"></span>
                                <div class="min-w-0 space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="truncate text-[14px] font-medium text-ink">{{ $leaveType->name }}</h2>
                                        @if($leaveType->is_system)
                                            <span class="inline-flex items-center rounded-[5px] bg-[#fef9ec] px-2 py-0.5 text-[11px] font-semibold text-[#9a7a1a]">System</span>
                                        @endif
                                    </div>
                                    <p class="text-[12px] text-muted">{{ $leaveType->code }}</p>
                                </div>
                            </div>

                            <div class="flex flex-1 flex-wrap items-center gap-2 md:justify-center">
                                @foreach($settings as $setting)
                                    <span class="inline-flex items-center rounded-[5px] bg-surface px-2 py-1 text-[11px] font-semibold text-dim">{{ $setting }}</span>
                                @endforeach
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                @if($leaveType->is_active)
                                    <span class="inline-flex items-center rounded-[5px] bg-[#edf7f2] px-2 py-1 text-[11px] font-semibold text-[#2e7d55]">Active</span>
                                @else
                                    <span class="inline-flex items-center rounded-[5px] bg-surface px-2 py-1 text-[11px] font-semibold text-muted">Inactive</span>
                                @endif

                                <button
                                    type="button"
                                    @click="openEdit({{ $leaveType->id }})"
                                    class="rounded-lg bg-surface px-3 py-2 text-[13px] font-medium text-ink transition-colors duration-150 hover:bg-[#eeeee9] cursor-pointer"
                                >
                                    Edit
                                </button>

                                @if(!$leaveType->is_system)
                                    <form method="POST" action="{{ route('leave-types.destroy', $leaveType) }}" onsubmit="return confirm('Delete this leave type?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg bg-[#fff0f0] px-3 py-2 text-[13px] font-medium text-[#b94040] transition-colors duration-150 hover:bg-[#ffe0e0] cursor-pointer">
                                            Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        <div x-show="activeEdit === {{ $leaveType->id }}" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="border-t border-hairline bg-canvas px-4 py-4 md:px-5">
                            <form method="POST" action="{{ route('leave-types.update', $leaveType) }}" class="space-y-4">
                                @csrf
                                @method('PUT')

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div class="space-y-1">
                                        <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted">Name</label>
                                        @if($leaveType->is_system)
                                            <input type="text" value="{{ $leaveType->name }}" disabled class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-[14px] text-ink">
                                            <input type="hidden" name="name" value="{{ $leaveType->name }}">
                                        @else
                                            <input type="text" name="name" value="{{ old('name', $leaveType->name) }}" required class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-[14px] text-ink transition-colors duration-150 focus:border-accent focus:bg-white">
                                        @endif
                                    </div>

                                    <div class="space-y-1">
                                        <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted">Code</label>
                                        @if($leaveType->is_system)
                                            <input type="text" value="{{ $leaveType->code }}" disabled class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-[14px] text-ink">
                                            <input type="hidden" name="code" value="{{ $leaveType->code }}">
                                        @else
                                            <input type="text" name="code" value="{{ old('code', $leaveType->code) }}" required class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-[14px] text-ink transition-colors duration-150 focus:border-accent focus:bg-white">
                                        @endif
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-[1fr_1.2fr]">
                                    <div class="space-y-1">
                                        <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted">Color</label>
                                        <div class="grid grid-cols-[auto_1fr] gap-3">
                                            <input type="color" name="color" value="{{ old('color', $leaveType->color) }}" class="h-11 w-14 rounded-lg border border-line bg-white p-1 cursor-pointer">
                                            <input type="text" name="color" value="{{ old('color', $leaveType->color) }}" class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-[14px] text-ink transition-colors duration-150 focus:border-accent focus:bg-white" placeholder="#D97757">
                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-line bg-white p-4 shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                                        <p class="text-[12px] font-medium text-dim">Use the controls below to adjust whether this leave category is paid, approval-based, active, or monthly-accruing.</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div class="space-y-3 rounded-xl border border-line bg-white p-4 shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                                        <label class="flex items-center justify-between gap-3 text-[13px] font-medium text-ink">
                                            <span>Paid</span>
                                            <input type="hidden" name="is_paid" value="0">
                                            <input type="checkbox" name="is_paid" value="1" @checked(old('is_paid', $leaveType->is_paid)) class="h-4 w-4 rounded border-line text-accent focus:ring-accent/30">
                                        </label>
                                        <label class="flex items-center justify-between gap-3 text-[13px] font-medium text-ink">
                                            <span>Requires Approval</span>
                                            <input type="hidden" name="requires_approval" value="0">
                                            <input type="checkbox" name="requires_approval" value="1" @checked(old('requires_approval', $leaveType->requires_approval)) class="h-4 w-4 rounded border-line text-accent focus:ring-accent/30">
                                        </label>
                                        <label class="flex items-center justify-between gap-3 text-[13px] font-medium text-ink">
                                            <span>Active</span>
                                            <input type="hidden" name="is_active" value="0">
                                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $leaveType->is_active)) class="h-4 w-4 rounded border-line text-accent focus:ring-accent/30">
                                        </label>
                                    </div>

                                    <div class="space-y-3 rounded-xl border border-line bg-white p-4 shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                                        <div x-data="{ monthly: {{ old('accrues_monthly', $leaveType->accrues_monthly) ? 'true' : 'false' }} }" class="space-y-3">
                                            <label class="flex items-center justify-between gap-3 text-[13px] font-medium text-ink">
                                                <span>Accrues Monthly</span>
                                                <input type="hidden" name="accrues_monthly" value="0">
                                                <input type="checkbox" name="accrues_monthly" value="1" x-model="monthly" class="h-4 w-4 rounded border-line text-accent focus:ring-accent/30">
                                            </label>

                                            <div x-show="monthly" x-cloak class="space-y-1">
                                                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted">Monthly Accrual Days</label>
                                                <input type="number" name="monthly_accrual_days" step="0.1" min="0" value="{{ old('monthly_accrual_days', $leaveType->monthly_accrual_days) }}" class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-[14px] text-ink transition-colors duration-150 focus:border-accent focus:bg-white" placeholder="0.0">
                                            </div>
                                        </div>

                                        <div class="space-y-1">
                                            <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted">Default Days / Year</label>
                                            <input type="number" name="default_days_per_year" step="1" min="0" value="{{ old('default_days_per_year', $leaveType->default_days_per_year) }}" class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-[14px] text-ink transition-colors duration-150 focus:border-accent focus:bg-white" placeholder="Unlimited">
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center justify-end gap-2 pt-1">
                                    <button type="button" @click="activeEdit = null" class="rounded-lg bg-surface px-4 py-2 text-[13px] font-medium text-ink transition-colors duration-150 hover:bg-[#eeeee9] cursor-pointer">
                                        Cancel
                                    </button>
                                    <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-[13px] font-medium text-white transition-colors duration-150 hover:bg-accent-hover cursor-pointer">
                                        Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <template x-teleport="body">
            <div
                x-show="createOpen"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
            >
                <div class="absolute inset-0 bg-black/45" @click="createOpen = false"></div>

                <div
                    class="relative w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-[0_20px_60px_rgba(0,0,0,0.18)]"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    @click.stop
                >
                    <div class="flex items-center justify-between border-b border-hairline px-5 py-4">
                        <h3 class="text-[16px] font-semibold text-ink">New Leave Type</h3>
                        <button type="button" @click="createOpen = false" class="flex h-7 w-7 items-center justify-center rounded-full bg-[rgba(0,0,0,0.07)] text-dim transition-colors duration-150 hover:bg-[rgba(0,0,0,0.13)] hover:text-ink cursor-pointer">
                            @include('components.icon', ['name' => 'x'])
                        </button>
                    </div>

                    <form method="POST" action="{{ route('leave-types.store') }}" class="space-y-5 px-5 py-5">
                        @csrf

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted">Name</label>
                                <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-[14px] text-ink transition-colors duration-150 focus:border-accent focus:bg-white" placeholder="Congé Annuel">
                            </div>

                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted">Code</label>
                                <input type="text" name="code" value="{{ old('code') }}" required class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-[14px] text-ink transition-colors duration-150 focus:border-accent focus:bg-white" placeholder="annual">
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-[0.05em] text-muted">Color Palette</p>
                                    <p class="mt-1 text-[12px] text-muted">Pick a swatch or use the color field.</p>
                                </div>
                                <div class="text-[12px] text-muted">Selected: <span class="font-medium text-ink" x-text="createColor"></span></div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <template x-for="color in palette" :key="color">
                                    <button type="button" @click="setCreateColor(color)" class="h-8 w-8 rounded-full border-2 border-white shadow-sm ring-1 ring-line transition-transform duration-150 hover:scale-105 cursor-pointer" :style="`background-color: ${color}`" aria-label="Select color"></button>
                                </template>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-[1fr_auto] md:items-end">
                                <div class="space-y-1">
                                    <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted">Color</label>
                                    <input type="text" name="color" x-model="createColor" required class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-[14px] text-ink transition-colors duration-150 focus:border-accent focus:bg-white" placeholder="#D97757">
                                </div>
                                <div class="space-y-1 md:w-36">
                                    <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted">Picker</label>
                                    <input type="color" x-model="createColor" class="h-11 w-full rounded-lg border border-line bg-white p-1 cursor-pointer">
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-3 rounded-xl border border-line bg-canvas p-4">
                                <label class="flex items-center justify-between gap-3 text-[13px] font-medium text-ink">
                                    <span>Paid</span>
                                    <input type="hidden" name="is_paid" value="0">
                                    <input type="checkbox" name="is_paid" value="1" checked class="h-4 w-4 rounded border-line text-accent focus:ring-accent/30">
                                </label>
                                <label class="flex items-center justify-between gap-3 text-[13px] font-medium text-ink">
                                    <span>Requires Approval</span>
                                    <input type="hidden" name="requires_approval" value="0">
                                    <input type="checkbox" name="requires_approval" value="1" checked class="h-4 w-4 rounded border-line text-accent focus:ring-accent/30">
                                </label>
                                <label class="flex items-center justify-between gap-3 text-[13px] font-medium text-ink">
                                    <span>Active</span>
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-line text-accent focus:ring-accent/30">
                                </label>
                            </div>

                            <div class="space-y-3 rounded-xl border border-line bg-canvas p-4" x-data="{ monthly: {{ old('accrues_monthly') ? 'true' : 'false' }} }">
                                <label class="flex items-center justify-between gap-3 text-[13px] font-medium text-ink">
                                    <span>Accrues Monthly</span>
                                    <input type="hidden" name="accrues_monthly" value="0">
                                    <input type="checkbox" name="accrues_monthly" value="1" x-model="monthly" class="h-4 w-4 rounded border-line text-accent focus:ring-accent/30">
                                </label>

                                <div x-show="monthly" x-cloak class="space-y-1">
                                    <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted">Monthly Accrual Days</label>
                                    <input type="number" name="monthly_accrual_days" step="0.1" min="0" value="{{ old('monthly_accrual_days', 0) }}" class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-[14px] text-ink transition-colors duration-150 focus:border-accent focus:bg-white" placeholder="0.0">
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-[11px] font-bold uppercase tracking-[0.05em] text-muted">Default Days / Year</label>
                                    <input type="number" name="default_days_per_year" step="1" min="0" value="{{ old('default_days_per_year') }}" class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-[14px] text-ink transition-colors duration-150 focus:border-accent focus:bg-white" placeholder="Unlimited">
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-end gap-2 pt-1">
                            <button type="button" @click="createOpen = false" class="rounded-lg bg-surface px-4 py-2 text-[13px] font-medium text-ink transition-colors duration-150 hover:bg-[#eeeee9] cursor-pointer">
                                Cancel
                            </button>
                            <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-[13px] font-medium text-white transition-colors duration-150 hover:bg-accent-hover cursor-pointer">
                                Create Leave Type
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>
</x-layouts.app>
