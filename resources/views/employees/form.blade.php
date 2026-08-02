<x-layouts.app :title="$employee ? 'Edit '.$employee->display_name : 'New Employee'">

<div class="flex items-center justify-between mb-6">
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('employees.index') }}"
           style="color:#8c8c8a;text-decoration:none;font-size:13px"
           onmouseover="this.style.color='#141413'" onmouseout="this.style.color='#8c8c8a'">← Employees</a>
        <h1 style="font-size:24px;font-weight:600;color:#141413">
            {{ $employee ? 'Edit ' . $employee->display_name : 'New Employee' }}
        </h1>
    </div>
    @if($employee && auth()->user()->hasPermission('hr.manage'))
    <form method="POST" action="{{ route('employees.destroy', $employee) }}"
          onsubmit="return confirm('Remove this employee profile?')">
        @csrf @method('DELETE')
        <button type="submit"
                class="text-sm font-medium px-3 py-1.5 rounded-lg transition-colors cursor-pointer"
                style="background:#fff0f0;border:1px solid #ffd0d0;color:#b94040;font-size:13px">
            Remove
        </button>
    </form>
    @endif
</div>

@include('components.flash')

            <form method="POST"
                  action="{{ $employee ? route('employees.update', $employee) : route('employees.store') }}"
                  x-data="{ employmentType: '{{ old('employment_type', $employee?->employment_type ?? 'CDI') }}' }"
                  class="max-w-2xl mx-auto">
                @csrf
                @if($employee) @method('PATCH') @endif

                @if($errors->any())
                <div class="mb-4 px-4 py-3 rounded-lg" style="background:#fff8f8;border:1px solid #ffd0d0;color:#b94040;font-size:13px">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- Section: Personal Info --}}
                <div class="rounded-xl overflow-hidden mb-4" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
                    <div class="px-5 py-4" style="border-bottom:1px solid #eeeee9">
                        <p class="font-semibold text-ink" style="font-size:14px">Personal Information</p>
                    </div>
                    <div class="px-5 py-5 space-y-4">

                        {{-- User selector --}}
                        <div>
                            <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">
                                Linked User <span class="text-red-500">*</span>
                            </label>
                            <select name="user_id" required
                                    class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                    style="font-size:14px">
                                <option value="">— Select a user —</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(old('user_id', $employee?->user_id) == $user->id)>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-muted" style="font-size:11px">Only users without an existing employee profile are shown.</p>
                        </div>

                        {{-- Job title + Department --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Job Title</label>
                                <input type="text" name="job_title" value="{{ old('job_title', $employee?->job_title) }}"
                                       placeholder="e.g. Senior Developer"
                                       class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                       style="font-size:14px">
                            </div>
                            <div>
                                <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Department</label>
                                <input type="text" name="department" value="{{ old('department', $employee?->department) }}"
                                       placeholder="e.g. Engineering"
                                       class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                       style="font-size:14px">
                            </div>
                        </div>

                        {{-- Employment type + Status --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Employment Type <span class="text-red-500">*</span></label>
                                <select name="employment_type" required
                                        x-model="employmentType"
                                        class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                        style="font-size:14px">
                                    <option value="CDI" @selected(old('employment_type', $employee?->employment_type) === 'CDI')>CDI</option>
                                    <option value="CDD" @selected(old('employment_type', $employee?->employment_type) === 'CDD')>CDD</option>
                                    <option value="freelance" @selected(old('employment_type', $employee?->employment_type) === 'freelance')>Freelance</option>
                                    <option value="internship" @selected(old('employment_type', $employee?->employment_type) === 'internship')>Internship</option>
                                    <option value="part_time" @selected(old('employment_type', $employee?->employment_type) === 'part_time')>Part-time</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Status</label>
                                <select name="status"
                                        class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                        style="font-size:14px">
                                    <option value="active" @selected(old('status', $employee?->status ?? 'active') === 'active')>Active</option>
                                    <option value="on_leave" @selected(old('status', $employee?->status) === 'on_leave')>On Leave</option>
                                    <option value="terminated" @selected(old('status', $employee?->status) === 'terminated')>Terminated</option>
                                </select>
                            </div>
                        </div>

                        {{-- Start date + End date --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Start Date <span class="text-red-500">*</span></label>
                                <input type="date" name="start_date" value="{{ old('start_date', $employee?->start_date?->format('Y-m-d')) }}" required
                                       class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                       style="font-size:14px">
                            </div>
                            <div x-show="['CDD','freelance','internship'].includes(employmentType)">
                                <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">End Date</label>
                                <input type="date" name="end_date" value="{{ old('end_date', $employee?->end_date?->format('Y-m-d')) }}"
                                       class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                       style="font-size:14px">
                            </div>
                        </div>

                        {{-- Phone + Email --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Personal Phone</label>
                                <input type="text" name="personal_phone" value="{{ old('personal_phone', $employee?->personal_phone) }}"
                                       placeholder="+222 00 00 00 00"
                                       class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                       style="font-size:14px">
                            </div>
                            <div>
                                <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Personal Email</label>
                                <input type="email" name="personal_email" value="{{ old('personal_email', $employee?->personal_email) }}"
                                       placeholder="personal@example.com"
                                       class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                       style="font-size:14px">
                            </div>
                        </div>

                        {{-- Address --}}
                        <div>
                            <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Address</label>
                            <textarea name="address" rows="2" placeholder="Full address..."
                                      class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors resize-none"
                                      style="font-size:14px;line-height:1.5">{{ old('address', $employee?->address) }}</textarea>
                        </div>

                        {{-- Emergency contact --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Emergency Contact Name</label>
                                <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $employee?->emergency_contact_name) }}"
                                       class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                       style="font-size:14px">
                            </div>
                            <div>
                                <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Emergency Contact Phone</label>
                                <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $employee?->emergency_contact_phone) }}"
                                       class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                       style="font-size:14px">
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Section: Identity & Details --}}
                <div class="rounded-xl overflow-hidden mb-4" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
                    <div class="px-5 py-4" style="border-bottom:1px solid #eeeee9">
                        <p class="font-semibold text-ink" style="font-size:14px">Identity &amp; Details</p>
                    </div>
                    <div class="px-5 py-5 space-y-4">

                        {{-- NNI + Date of Birth --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">NNI</label>
                                <input type="text" name="nni" value="{{ old('nni', $employee?->nni) }}"
                                       placeholder="e.g. 8399461346"
                                       class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                       style="font-size:14px">
                            </div>
                            <div>
                                <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Date of Birth</label>
                                <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $employee?->date_of_birth?->format('Y-m-d')) }}"
                                       class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                       style="font-size:14px">
                            </div>
                        </div>

                        {{-- Nationality + Category --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Nationality</label>
                                <input type="text" name="nationality" value="{{ old('nationality', $employee?->nationality ?? 'Mauritanienne') }}"
                                       class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                       style="font-size:14px">
                            </div>
                            <div>
                                <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Category / Grade</label>
                                <input type="text" name="category" value="{{ old('category', $employee?->category) }}"
                                       placeholder="e.g. M5"
                                       class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                       style="font-size:14px">
                            </div>
                        </div>

                        {{-- Work Location + Probation Period --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Work Location</label>
                                <input type="text" name="work_location" value="{{ old('work_location', $employee?->work_location ?? 'Nouakchott') }}"
                                       class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                       style="font-size:14px">
                            </div>
                            <div>
                                <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Probation Period (months)</label>
                                <input type="number" name="probation_period_months" min="0" max="24"
                                       value="{{ old('probation_period_months', $employee?->probation_period_months ?? 2) }}"
                                       class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                       style="font-size:14px">
                            </div>
                        </div>

                        {{-- Supervisor Name (internship only) --}}
                        <div x-show="employmentType === 'internship'">
                            <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Supervisor Name</label>
                            <input type="text" name="supervisor_name" value="{{ old('supervisor_name', $employee?->supervisor_name) }}"
                                   placeholder="Internship supervisor"
                                   class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                   style="font-size:14px">
                        </div>

                    </div>
                </div>

                {{-- Section: Notes --}}
                <div class="rounded-xl overflow-hidden mb-6" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
                    <div class="px-5 py-4" style="border-bottom:1px solid #eeeee9">
                        <p class="font-semibold text-ink" style="font-size:14px">Notes</p>
                    </div>
                    <div class="px-5 py-5">
                        <textarea name="notes" rows="3" placeholder="Any internal notes about this employee..."
                                  class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors resize-none"
                                  style="font-size:14px;line-height:1.5">{{ old('notes', $employee?->notes) }}</textarea>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ $employee ? route('employees.show', $employee) : route('employees.index') }}"
                       class="font-medium rounded-lg px-4 py-2 transition-colors"
                       style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;font-size:13px">
                        Cancel
                    </a>
                    <button type="submit"
                            class="font-medium rounded-lg px-4 py-2 text-white transition-colors cursor-pointer bg-accent hover:bg-accent-hover"
                            style="font-size:13px">
                        {{ $employee ? 'Save Changes' : 'Create Employee' }}
                    </button>
                </div>
            </form>

</x-layouts.app>
