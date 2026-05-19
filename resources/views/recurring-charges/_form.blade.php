{{-- Shared form fields for recurring charges --}}
@php
    $val = fn(string $field, $default = null) => old($field, $charge?->{$field} ?? $default);
@endphp

<div style="display:grid;gap:20px">

    {{-- Name --}}
    <div>
        <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">Name *</label>
        <input type="text" name="name" value="{{ $val('name') }}" required
               style="width:100%;padding:10px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;box-sizing:border-box"
               placeholder="e.g. AWS EC2 monthly"
               onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'">
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        {{-- Category --}}
        <div>
            <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">Category</label>
            <div style="position:relative">
                <select name="category_id"
                        style="width:100%;padding:10px 32px 10px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;appearance:none;box-sizing:border-box">
                    <option value="">No category</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected($val('category_id') == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
                <svg style="position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#8c8c8a" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            </div>
        </div>

        {{-- Project --}}
        <div>
            <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">Project</label>
            <div style="position:relative">
                <select name="project_id"
                        style="width:100%;padding:10px 32px 10px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;appearance:none;box-sizing:border-box">
                    <option value="">No project</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" @selected($val('project_id') == $project->id)>{{ $project->name }}</option>
                    @endforeach
                </select>
                <svg style="position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#8c8c8a" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        {{-- Amount --}}
        <div>
            <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">Amount (MRU) *</label>
            <input type="number" name="amount" value="{{ $val('amount') }}" min="0" step="0.01" required
                   style="width:100%;padding:10px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;box-sizing:border-box"
                   onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'">
            <p style="font-size:11px;color:#8c8c8a;margin:4px 0 0">Currency is always MRU.</p>
        </div>

        {{-- Frequency --}}
        <div>
            <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">Frequency *</label>
            <div style="position:relative">
                <select name="frequency" required
                        style="width:100%;padding:10px 32px 10px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;appearance:none;box-sizing:border-box">
                    <option value="">Choose…</option>
                    <option value="monthly"   @selected($val('frequency') === 'monthly')>Monthly</option>
                    <option value="quarterly" @selected($val('frequency') === 'quarterly')>Quarterly</option>
                    <option value="annual"    @selected($val('frequency') === 'annual')>Annual</option>
                </select>
                <svg style="position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#8c8c8a" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        {{-- Start date --}}
        <div>
            <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">Start Date *</label>
            <input type="date" name="start_date" value="{{ $val('start_date') ? \Carbon\Carbon::parse($val('start_date'))->format('Y-m-d') : '' }}" required
                   style="width:100%;padding:10px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;box-sizing:border-box"
                   onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'">
        </div>

        {{-- Next due date --}}
        <div>
            <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">Next Due Date *</label>
            <input type="date" name="next_due_date" value="{{ $val('next_due_date') ? \Carbon\Carbon::parse($val('next_due_date'))->format('Y-m-d') : '' }}" required
                   style="width:100%;padding:10px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;box-sizing:border-box"
                   onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'">
        </div>
    </div>

    {{-- Active toggle --}}
    <div>
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
            <input type="checkbox" name="is_active" value="1" @checked($val('is_active', true))>
            <span style="font-size:14px;color:#141413;font-weight:500">Active (generate drafts monthly)</span>
        </label>
    </div>

    {{-- Notes --}}
    <div>
        <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">Notes</label>
        <textarea name="notes" rows="3"
                  style="width:100%;padding:10px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;resize:vertical;box-sizing:border-box"
                  onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'">{{ $val('notes') }}</textarea>
    </div>

</div>
