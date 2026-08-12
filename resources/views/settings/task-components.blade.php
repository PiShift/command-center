<x-layouts.settings title="Global Task Components">

<div class="space-y-5">
    <h1 style="font-size:22px;font-weight:700;color:#141413;margin-bottom:4px">Global Task Components</h1>
    <p style="font-size:13px;color:#5c5c5a">One shared list used across all projects, task forms, and board filters.</p>

    @include('components.flash')

    <div class="rounded-xl p-6" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
        <div class="flex items-center justify-between gap-3 mb-4">
            <div>
                <h2 style="font-size:15px;font-weight:600;color:#141413;margin:0">Allowed Components</h2>
                <p style="font-size:12px;color:#8c8c8a;margin-top:4px">Editing this list updates options everywhere. Existing task values are never changed automatically.</p>
            </div>
        </div>

        @if($components->isEmpty())
            <p style="font-size:13px;color:#5c5c5a;margin-bottom:12px">No global components configured yet.</p>
        @endif

        <div class="space-y-2 mb-4">
            @foreach($components as $component)
                <div class="flex items-center gap-2 rounded-lg px-3 py-2" style="background:#F5F4EF">
                    <form method="POST" action="{{ route('settings.task-components.update', $component) }}" class="flex items-center gap-2 flex-1 min-w-0">
                        @csrf
                        @method('PATCH')
                        <input type="text" name="name" value="{{ $component->name }}"
                               class="flex-1 min-w-0 px-3 py-1.5 text-[13px] border border-line rounded-lg bg-white"
                               style="outline:none">
                        <span style="font-size:11px;color:#8c8c8a;white-space:nowrap">{{ (int) ($usageCounts[$component->name] ?? 0) }} tasks</span>
                        <button type="submit"
                                style="padding:6px 10px;font-size:12px;font-weight:500;background:#fff;border:1px solid #e5e4df;color:#141413;border-radius:8px;cursor:pointer">Save</button>
                    </form>
                    <form method="POST" action="{{ route('settings.task-components.destroy', $component) }}" onsubmit="return confirm('Remove this global component option? Existing tasks keep their current value.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                style="padding:6px 10px;font-size:12px;font-weight:500;background:#fff0f0;border:1px solid #ffd0d0;color:#b94040;border-radius:8px;cursor:pointer">Remove</button>
                    </form>
                </div>
            @endforeach
        </div>

        <form method="POST" action="{{ route('settings.task-components.store') }}" class="flex items-center gap-2">
            @csrf
            <input type="text" name="name" placeholder="Add component (e.g. Mobile)"
                   class="flex-1 min-w-0 px-3 py-2 text-[13px] border border-line rounded-lg bg-white"
                   style="outline:none">
            <button type="submit"
                    style="padding:8px 14px;font-size:13px;font-weight:600;background:#F5F4EF;border:1px solid #e5e4df;color:#141413;border-radius:8px;cursor:pointer">Add</button>
        </form>
        @error('name')
            <p style="font-size:12px;color:#b94040;margin-top:8px">{{ $message }}</p>
        @enderror
    </div>

    <div class="rounded-xl p-6" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
        <h2 style="font-size:15px;font-weight:600;color:#141413;margin:0 0 4px">Manual Reassignment Queue</h2>
        <p style="font-size:12px;color:#8c8c8a;margin-bottom:12px">Values below exist on tasks but are not part of the global list. Reassign manually when ready.</p>

        @if($legacyValues->isEmpty())
            <p style="font-size:13px;color:#2e7d55">All in-use task component values are covered by the global list.</p>
        @else
            <div class="space-y-2">
                @foreach($legacyValues as $legacy)
                    <form method="POST" action="{{ route('settings.task-components.bulk-reassign') }}"
                          class="flex items-center gap-2 rounded-lg px-3 py-2"
                          style="background:#fff8ef;border:1px solid #ffe0b8">
                        @csrf
                        <input type="hidden" name="from_component" value="{{ $legacy->component }}">
                        <span style="font-size:13px;color:#141413;min-width:160px">{{ $legacy->component }}</span>
                        <span style="font-size:11px;color:#8c8c8a;white-space:nowrap">{{ $legacy->task_count }} tasks</span>
                        <span style="font-size:12px;color:#5c5c5a">→</span>
                        <select name="to_component_id" class="px-2 py-1.5 text-[13px] border border-line rounded-lg bg-white" style="outline:none">
                            @foreach($components as $component)
                                <option value="{{ $component->id }}">{{ $component->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit"
                                style="padding:6px 10px;font-size:12px;font-weight:500;background:#fff;border:1px solid #e5e4df;color:#141413;border-radius:8px;cursor:pointer">Reassign</button>
                    </form>
                @endforeach
            </div>
        @endif
    </div>
</div>
</x-layouts.settings>
