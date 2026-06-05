<x-layouts.app :title="$employee->display_name">

<div x-data="{ tab: window.location.hash.replace('#','') || 'profile' }">

    {{-- Page header --}}
    <div class="flex items-center justify-between mb-4">
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('employees.index') }}"
               style="color:#8c8c8a;text-decoration:none;font-size:13px"
               onmouseover="this.style.color='#141413'" onmouseout="this.style.color='#8c8c8a'">← Employees</a>
            <h1 style="font-size:24px;font-weight:600;color:#141413">{{ $employee->display_name }}</h1>
            <span class="font-medium" style="font-size:12px;color:#8c8c8a">{{ $employee->employee_number }}</span>
        </div>
        @if(auth()->user()->hasPermission('hr.manage'))
        <a href="{{ route('employees.edit', $employee) }}"
           class="font-medium rounded-lg px-3 py-1.5 transition-colors"
           style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;font-size:13px">
            Edit Profile
        </a>
        @endif
    </div>

    {{-- Tab bar --}}
    <div class="flex items-center gap-1 mb-5" style="border-bottom:1px solid #e5e4df;padding-bottom:0">
        @foreach(['profile' => 'Profile', 'contracts' => 'Contracts', 'documents' => 'Documents'] as $key => $label)
        <button @click="tab='{{ $key }}'; window.location.hash='{{ $key }}'"
                :class="tab === '{{ $key }}' ? 'border-b-2 text-ink' : 'text-muted hover:text-dim'"
                class="px-4 py-2 font-medium transition-all cursor-pointer"
                style="font-size:13px;border-color:#D97757;margin-bottom:-1px">
            {{ $label }}
            @if($key === 'contracts')
            <span class="ml-1 text-xs font-semibold px-1.5 rounded-full" style="background:#F5F4EF;color:#8c8c8a">{{ $employee->contracts->count() }}</span>
            @elseif($key === 'documents')
            <span class="ml-1 text-xs font-semibold px-1.5 rounded-full" style="background:#F5F4EF;color:#8c8c8a">{{ $employee->documents->count() }}</span>
            @endif
        </button>
        @endforeach
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         x-transition:leave="transition-opacity duration-500" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="mb-4 px-4 py-3 rounded-lg text-sm font-medium flex items-center justify-between gap-3" style="background:#edf7f2;color:#2e7d55;border:1px solid #c6e8d5">
        <span>{{ session('success') }}</span>
        <button type="button" @click="show = false" class="hover:opacity-60 flex-shrink-0 cursor-pointer" style="color:#2e7d55">&times;</button>
    </div>
    @endif
    @if($errors->has('activate'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
         x-transition:leave="transition-opacity duration-500" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="mb-4 px-4 py-3 rounded-lg text-sm font-medium flex items-center justify-between gap-3" style="background:#fff8f8;border:1px solid #ffd0d0;color:#b94040">
        <span>{{ $errors->first('activate') }}</span>
        <button type="button" @click="show = false" class="hover:opacity-60 flex-shrink-0 cursor-pointer" style="color:#b94040">&times;</button>
    </div>
    @endif

    {{-- ─── Profile Tab ─────────────────────────────────────────────── --}}
    <div x-show="tab === 'profile'" x-cloak>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    {{-- Left: employee details --}}
                    <div class="lg:col-span-2 space-y-4">
                        @php $avatarUrl = $employee->getFirstMediaUrl('avatar'); @endphp
                        <div class="rounded-xl overflow-hidden" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
                            {{-- Header: avatar updates live via window event dispatched when a file is picked --}}
                            <div class="px-5 py-4 flex items-center gap-4"
                                 x-data="{ src: '{{ $avatarUrl }}' }"
                                 @avatar-preview.window="src = $event.detail.url"
                                 style="border-bottom:1px solid #eeeee9">
                                @if($avatarUrl)
                                <img :src="src" class="w-14 h-14 rounded-full object-cover flex-shrink-0" alt="{{ $employee->display_name }}">
                                @else
                                @php $initials = collect(explode(' ', $employee->display_name))->map(fn($w) => strtoupper($w[0] ?? ''))->take(2)->join(''); @endphp
                                <img x-show="src" :src="src" x-cloak class="w-14 h-14 rounded-full object-cover flex-shrink-0">
                                <div x-show="!src" class="w-14 h-14 rounded-full flex items-center justify-center flex-shrink-0 text-white font-bold" style="font-size:18px;background:#D97757">{{ $initials }}</div>
                                @endif
                                <div>
                                    <div class="font-semibold text-ink" style="font-size:17px">{{ $employee->display_name }}</div>
                                    <div class="text-muted" style="font-size:12px">{{ $employee->employee_number }} &middot; {{ $employee->user?->email }}</div>
                                </div>
                            </div>
                            {{-- Avatar upload — collapsed by default --}}
                            @if(auth()->user()->hasPermission('hr.manage'))
                            <div x-data="{ open: false }" style="border-bottom:1px solid #eeeee9">
                                <button type="button" @click="open = !open"
                                        class="w-full px-5 py-2.5 flex items-center justify-between text-left hover:bg-surface transition-colors cursor-pointer"
                                        style="background:#faf9f5">
                                    <span class="text-muted font-bold uppercase tracking-wider" style="font-size:10px;letter-spacing:0.06em">Change Avatar</span>
                                    <svg class="w-3.5 h-3.5 text-muted transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div x-show="open" x-collapse
                                     style="background:#faf9f5"
                                     onchange="if(event.target.type==='file'&&event.target.files[0]){const r=new FileReader();r.onload=e=>window.dispatchEvent(new CustomEvent('avatar-preview',{detail:{url:e.target.result}}));r.readAsDataURL(event.target.files[0])}">
                                    <div class="px-5 pb-4 pt-1">
                                        <form method="POST" action="{{ route('employees.avatar', $employee) }}" enctype="multipart/form-data">
                                            @csrf
                                            <x-file-upload name="avatar" accept=".jpg,.jpeg,.png,.webp" :max-size-mb="5" label="Drop photo or click to browse" />
                                            <div class="mt-2 flex justify-end">
                                                <button type="submit" class="text-xs font-medium px-3 py-1.5 rounded-lg bg-surface border border-line text-dim hover:text-ink transition-colors cursor-pointer">Upload</button>
                                            </div>
                                        </form>
                                        @error('avatar')
                                        <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            @endif
                            {{-- Fields --}}
                            <div class="px-5 py-4 grid grid-cols-2 gap-x-8 gap-y-4">
                                @foreach([
                                    ['Job Title', $employee->job_title],
                                    ['Department', $employee->department],
                                    ['Employment Type', $employee->employment_type],
                                    ['Status', str_replace('_', ' ', ucfirst($employee->status))],
                                    ['Start Date', $employee->start_date->format('d M Y')],
                                    ['End Date', $employee->end_date?->format('d M Y')],
                                    ['Personal Phone', $employee->personal_phone],
                                    ['Personal Email', $employee->personal_email],
                                    ['NNI', $employee->nni],
                                    ['Date of Birth', $employee->date_of_birth?->format('d M Y')],
                                    ['Nationality', $employee->nationality],
                                    ['Work Location', $employee->work_location],
                                    ['Category / Grade', $employee->category],
                                    ['Probation Period', $employee->probation_period_months ? $employee->probation_period_months . ' months' : null],
                                ] as [$label, $value])
                                <div>
                                    <p class="text-muted font-bold uppercase tracking-wider mb-1" style="font-size:10px;letter-spacing:0.06em">{{ $label }}</p>
                                    <p class="text-ink" style="font-size:13.5px">{{ $value ?: '—' }}</p>
                                </div>
                                @endforeach
                            </div>
                            @if($employee->address || $employee->emergency_contact_name)
                            <div class="px-5 py-4 border-t border-hairline grid grid-cols-2 gap-x-8 gap-y-4">
                                @if($employee->address)
                                <div class="col-span-2">
                                    <p class="text-muted font-bold uppercase tracking-wider mb-1" style="font-size:10px;letter-spacing:0.06em">Address</p>
                                    <p class="text-ink" style="font-size:13px;line-height:1.5">{{ $employee->address }}</p>
                                </div>
                                @endif
                                @if($employee->emergency_contact_name)
                                <div>
                                    <p class="text-muted font-bold uppercase tracking-wider mb-1" style="font-size:10px;letter-spacing:0.06em">Emergency Contact</p>
                                    <p class="text-ink" style="font-size:13.5px">{{ $employee->emergency_contact_name }}</p>
                                </div>
                                <div>
                                    <p class="text-muted font-bold uppercase tracking-wider mb-1" style="font-size:10px;letter-spacing:0.06em">Emergency Phone</p>
                                    <p class="text-ink" style="font-size:13.5px">{{ $employee->emergency_contact_phone ?: '—' }}</p>
                                </div>
                                @endif
                            </div>
                            @endif
                            @if($employee->notes)
                            <div class="px-5 py-4 border-t border-hairline">
                                <p class="text-muted font-bold uppercase tracking-wider mb-1" style="font-size:10px;letter-spacing:0.06em">Notes</p>
                                <p class="text-dim" style="font-size:13px;line-height:1.6">{{ $employee->notes }}</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Right: current contract card --}}
                    <div class="space-y-4">
                        @php $contract = $employee->current_contract; @endphp
                        <div class="rounded-xl overflow-hidden" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
                            <div class="px-5 py-4 flex items-center justify-between" style="border-bottom:1px solid #eeeee9">
                                <p class="font-semibold text-ink" style="font-size:14px">Current Contract</p>
                                @if($contract)
                                @php $cBg = $contract->status === 'active' ? '#edf7f2' : ($contract->status === 'draft' ? '#fef9ec' : '#F5F4EF'); $cTxt = $contract->status === 'active' ? '#2e7d55' : ($contract->status === 'draft' ? '#9a7a1a' : '#8c8c8a'); @endphp
                                <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:5px;background:{{ $cBg }};color:{{ $cTxt }}">{{ ucfirst($contract->status) }}</span>
                                @endif
                            </div>
                            @if($contract)
                            <div class="px-5 py-4 space-y-3">
                                <div>
                                    <p class="text-muted font-bold uppercase tracking-wider mb-0.5" style="font-size:10px;letter-spacing:0.06em">Base Salary</p>
                                    <p class="text-ink font-bold" style="font-size:20px">{{ $contract->currency }} {{ number_format($contract->base_salary, 2) }}</p>
                                    <p class="text-muted" style="font-size:11px">per month</p>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <p class="text-muted font-bold uppercase tracking-wider mb-0.5" style="font-size:10px;letter-spacing:0.06em">Hours/Day</p>
                                        <p class="text-ink" style="font-size:13px">{{ $contract->working_hours_per_day }}h</p>
                                    </div>
                                    <div>
                                        <p class="text-muted font-bold uppercase tracking-wider mb-0.5" style="font-size:10px;letter-spacing:0.06em">Days/Week</p>
                                        <p class="text-ink" style="font-size:13px">{{ $contract->working_days_per_week }} days</p>
                                    </div>
                                    <div>
                                        <p class="text-muted font-bold uppercase tracking-wider mb-0.5" style="font-size:10px;letter-spacing:0.06em">Type</p>
                                        <p class="text-ink" style="font-size:13px">{{ $contract->employment_type }}</p>
                                    </div>
                                    <div>
                                        <p class="text-muted font-bold uppercase tracking-wider mb-0.5" style="font-size:10px;letter-spacing:0.06em">Notice</p>
                                        <p class="text-ink" style="font-size:13px">{{ $contract->notice_period_days }}d</p>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-muted font-bold uppercase tracking-wider mb-0.5" style="font-size:10px;letter-spacing:0.06em">Effective</p>
                                    <p class="text-ink" style="font-size:13px">
                                        {{ $contract->effective_from->format('d M Y') }}
                                        @if($contract->effective_to) → {{ $contract->effective_to->format('d M Y') }} @else → present @endif
                                    </p>
                                </div>
                            </div>
                            <div class="px-5 py-3 flex flex-col gap-2" style="border-top:1px solid #eeeee9;background:#faf9f5">
                                <a href="{{ route('employees.contracts.download', [$employee, $contract]) }}"
                                   target="_blank"
                                   class="text-center font-medium rounded-lg px-3 py-2 transition-colors"
                                   style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;font-size:13px">
                                    Download Contract PDF
                                </a>
                                @if(auth()->user()->hasPermission('hr.manage'))
                                <div x-data="{ uploadOpen: false }">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-muted font-bold uppercase tracking-wider" style="font-size:10px;letter-spacing:0.06em">Signed Copy:</span>
                                        @if($contract->getFirstMedia('signed_contract'))
                                        <a href="{{ $contract->getFirstMedia('signed_contract')->getUrl() }}"
                                           class="text-accent hover:text-accent-hover transition-colors" style="font-size:12px" target="_blank">Download signed ↗</a>
                                        <button type="button" @click="uploadOpen = !uploadOpen" class="text-xs text-dim hover:text-ink transition-colors cursor-pointer">Replace</button>
                                        @else
                                        <button type="button" @click="uploadOpen = !uploadOpen" class="text-xs text-accent hover:text-accent-hover transition-colors cursor-pointer">+ Upload signed copy</button>
                                        @endif
                                    </div>
                                    <div x-show="uploadOpen" x-cloak x-transition class="mt-2">
                                        <form method="POST" action="{{ route('employees.contracts.upload-signed', [$employee, $contract]) }}" enctype="multipart/form-data">
                                            @csrf
                                            <x-file-upload name="signed_contract" accept=".pdf" label="Drop signed PDF here" />
                                            <div class="mt-2 flex items-center justify-end gap-2">
                                                <button type="button" @click="uploadOpen = false" class="text-xs font-medium px-3 py-1.5 rounded-lg text-dim hover:text-ink transition-colors cursor-pointer">Cancel</button>
                                                <button type="submit" class="text-xs font-medium px-3 py-1.5 rounded-lg bg-surface border border-line text-dim hover:text-ink transition-colors cursor-pointer">Upload</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                @endif
                            </div>
                            @else
                            <div class="px-5 py-8 text-center">
                                <p class="text-muted" style="font-size:13px">No contract on file.</p>
                            </div>
                            @endif
                            @if(auth()->user()->hasPermission('hr.manage'))
                            <div class="px-5 py-3" style="border-top:1px solid #eeeee9">
                                <a href="{{ route('employees.contracts.create', $employee) }}"
                                   class="block text-center font-medium rounded-lg px-3 py-2 text-white transition-colors bg-accent hover:bg-accent-hover"
                                   style="font-size:13px">
                                    + {{ $contract && $contract->status === 'active' ? 'New Contract Version' : 'Create Contract' }}
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>

            {{-- ─── Contracts Tab ──────────────────────────────────────────── --}}
            <div x-show="tab === 'contracts'" x-cloak>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-ink" style="font-size:15px">Contract History</h2>
                    @if(auth()->user()->hasPermission('hr.manage'))
                    <a href="{{ route('employees.contracts.create', $employee) }}"
                       class="font-medium rounded-lg px-4 py-2 text-white transition-colors bg-accent hover:bg-accent-hover"
                       style="font-size:13px">
                        + New Contract
                    </a>
                    @endif
                </div>

                @forelse($employee->contracts as $c)
                @php $cBg2 = $c->status === 'active' ? '#edf7f2' : ($c->status === 'draft' ? '#fef9ec' : '#F5F4EF'); $cTxt2 = $c->status === 'active' ? '#2e7d55' : ($c->status === 'draft' ? '#9a7a1a' : '#8c8c8a'); @endphp
                <div class="rounded-xl overflow-hidden mb-4" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
                    <div class="px-5 py-4 flex items-center justify-between" style="border-bottom:1px solid #eeeee9">
                        <div class="flex items-center gap-3">
                            <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:5px;background:{{ $cBg2 }};color:{{ $cTxt2 }}">{{ ucfirst($c->status) }}</span>
                            <span class="text-dim font-medium" style="font-size:13px">{{ $c->employment_type }}</span>
                            @if($c->contract_reference)
                            <span class="text-muted font-medium" style="font-size:11px;background:#F5F4EF;padding:1px 6px;border-radius:4px">{{ $c->contract_reference }}</span>
                            @endif
                            <span class="text-muted" style="font-size:12px">
                                {{ $c->effective_from->format('d M Y') }} → {{ $c->effective_to?->format('d M Y') ?? 'present' }}
                            </span>
                            @if($c->template)
                            <span class="text-muted" style="font-size:11px">· {{ $c->template->name }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            @if($c->status === 'draft' && auth()->user()->hasPermission('hr.manage'))
                            <a href="{{ route('employees.contracts.edit', [$employee, $c]) }}"
                               class="text-xs font-medium px-3 py-1.5 rounded-lg transition-colors"
                               style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413">
                                Edit
                            </a>
                            <form method="POST" action="{{ route('employees.contracts.activate', [$employee, $c]) }}">
                                @csrf
                                <button type="submit" class="text-xs font-medium px-3 py-1.5 rounded-lg transition-colors cursor-pointer" style="background:#edf7f2;color:#2e7d55;border:1px solid #c6e8d5">Activate</button>
                            </form>
                            @endif
                            <a href="{{ route('employees.contracts.download', [$employee, $c]) }}"
                               target="_blank"
                               class="text-xs font-medium px-3 py-1.5 rounded-lg transition-colors"
                               style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413">
                                Download PDF
                            </a>
                        </div>
                    </div>
                    <div class="px-5 py-4 grid grid-cols-4 gap-4">
                        <div>
                            <p class="text-muted font-bold uppercase tracking-wider mb-0.5" style="font-size:10px;letter-spacing:0.06em">Salary</p>
                            <p class="text-ink font-semibold" style="font-size:14px">{{ $c->currency }} {{ number_format($c->base_salary, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-muted font-bold uppercase tracking-wider mb-0.5" style="font-size:10px;letter-spacing:0.06em">Hours/Day</p>
                            <p class="text-ink" style="font-size:13px">{{ $c->working_hours_per_day }}h</p>
                        </div>
                        <div>
                            <p class="text-muted font-bold uppercase tracking-wider mb-0.5" style="font-size:10px;letter-spacing:0.06em">Days/Week</p>
                            <p class="text-ink" style="font-size:13px">{{ $c->working_days_per_week }}</p>
                        </div>
                        <div>
                            <p class="text-muted font-bold uppercase tracking-wider mb-0.5" style="font-size:10px;letter-spacing:0.06em">Notice</p>
                            <p class="text-ink" style="font-size:13px">{{ $c->notice_period_days }} days</p>
                        </div>
                    </div>
                    @if($c->additional_clauses)
                    <div class="px-5 py-3 border-t border-hairline">
                        <p class="text-muted font-bold uppercase tracking-wider mb-1" style="font-size:10px;letter-spacing:0.06em">Additional Clauses</p>
                        <p class="text-dim" style="font-size:13px;line-height:1.5">{{ $c->additional_clauses }}</p>
                    </div>
                    @endif
                    @if($c->status === 'active' && auth()->user()->hasPermission('hr.manage'))
                    <div class="px-5 py-3 border-t border-hairline" style="background:#faf9f5">
                        <div x-data="{ uploadOpen: false }">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-muted font-bold uppercase tracking-wider" style="font-size:10px;letter-spacing:0.06em">Signed Copy:</span>
                                @if($c->getFirstMedia('signed_contract'))
                                <a href="{{ $c->getFirstMedia('signed_contract')->getUrl() }}" class="text-accent hover:text-accent-hover transition-colors" style="font-size:12px" target="_blank">Download signed ↗</a>
                                <button type="button" @click="uploadOpen = !uploadOpen" class="text-xs text-dim hover:text-ink transition-colors cursor-pointer">Replace</button>
                                @else
                                <button type="button" @click="uploadOpen = !uploadOpen" class="text-xs text-accent hover:text-accent-hover transition-colors cursor-pointer">+ Upload signed copy</button>
                                @endif
                            </div>
                            <div x-show="uploadOpen" x-cloak x-transition class="mt-2">
                                <form method="POST" action="{{ route('employees.contracts.upload-signed', [$employee, $c]) }}" enctype="multipart/form-data">
                                    @csrf
                                    <x-file-upload name="signed_contract" accept=".pdf" label="Drop signed PDF here" />
                                    <div class="mt-2 flex items-center justify-end gap-2">
                                        <button type="button" @click="uploadOpen = false" class="text-xs font-medium px-3 py-1.5 rounded-lg text-dim hover:text-ink transition-colors cursor-pointer">Cancel</button>
                                        <button type="submit" class="text-xs font-medium px-3 py-1.5 rounded-lg bg-surface border border-line text-dim hover:text-ink transition-colors cursor-pointer">Upload</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                @empty
                <div class="rounded-xl py-16 text-center" style="background:#fff;border:1px solid #e5e4df">
                    <p class="text-muted" style="font-size:14px;font-weight:500;margin-bottom:4px">No contracts yet</p>
                    @if(auth()->user()->hasPermission('hr.manage'))
                    <a href="{{ route('employees.contracts.create', $employee) }}" class="inline-block mt-3 font-medium rounded-lg px-4 py-2 text-white transition-colors bg-accent hover:bg-accent-hover" style="font-size:13px">+ Create First Contract</a>
                    @endif
                </div>
                @endforelse
            </div>

            {{-- ─── Documents Tab ──────────────────────────────────────────── --}}
            <div x-show="tab === 'documents'" x-cloak>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-ink" style="font-size:15px">Documents</h2>
                    @if(auth()->user()->hasPermission('hr.manage'))
                    <button x-data @click="$dispatch('open-doc-form')"
                            class="font-medium rounded-lg px-4 py-2 text-white transition-colors bg-accent hover:bg-accent-hover cursor-pointer"
                            style="font-size:13px">
                        + Add Document
                    </button>
                    @endif
                </div>

                {{-- Add document form (collapsible) --}}
                @if(auth()->user()->hasPermission('hr.manage'))
                <div x-data="{ open: false }" @open-doc-form.window="open = true">
                    <div x-show="open" x-cloak class="rounded-xl overflow-hidden mb-4" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
                        <div class="px-5 py-4 flex items-center justify-between" style="border-bottom:1px solid #eeeee9">
                            <p class="font-semibold text-ink" style="font-size:14px">Upload Document</p>
                            <button @click="open = false" class="text-muted hover:text-ink transition-colors cursor-pointer" style="font-size:18px;line-height:1">&times;</button>
                        </div>
                        <form method="POST" action="{{ route('employees.documents.store', $employee) }}" enctype="multipart/form-data" class="px-5 py-4 space-y-3">
                            @csrf
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Title <span class="text-red-500">*</span></label>
                                    <input type="text" name="title" required placeholder="e.g. National ID"
                                           class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                           style="font-size:14px">
                                </div>
                                <div>
                                    <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Type <span class="text-red-500">*</span></label>
                                    <select name="type" required
                                            class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                            style="font-size:14px">
                                        <option value="contract">Contract</option>
                                        <option value="id_card">ID Card</option>
                                        <option value="diploma">Diploma</option>
                                        <option value="certificate">Certificate</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">File <span class="text-red-500">*</span></label>
                                <x-file-upload name="file" accept=".pdf,.jpg,.jpeg,.png" />
                            </div>
                            <div>
                                <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Notes</label>
                                <input type="text" name="notes" placeholder="Optional note..."
                                       class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                       style="font-size:14px">
                            </div>
                            <div class="flex justify-end gap-2 pt-1">
                                <button type="button" @click="open = false" class="font-medium rounded-lg px-3 py-2 transition-colors cursor-pointer" style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;font-size:13px">Cancel</button>
                                <button type="submit" class="font-medium rounded-lg px-4 py-2 text-white transition-colors cursor-pointer bg-accent hover:bg-accent-hover" style="font-size:13px">Upload</button>
                            </div>
                        </form>
                    </div>
                </div>
                @endif

                {{-- Documents grid --}}
                @if($employee->documents->isEmpty())
                <div class="rounded-xl py-16 text-center" style="background:#fff;border:1px solid #e5e4df">
                    <p class="text-muted" style="font-size:14px;font-weight:500">No documents uploaded yet.</p>
                </div>
                @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($employee->documents->load('media') as $doc)
                    @php
                        $typeMap = ['contract'=>['#eef3fb','#3a6fba'],'id_card'=>['#fdf3ee','#b55a2f'],'diploma'=>['#edf7f2','#2e7d55'],'certificate'=>['#fef9ec','#9a7a1a'],'other'=>['#F5F4EF','#5c5c5a']];
                        [$dBg,$dTxt] = $typeMap[$doc->type] ?? ['#F5F4EF','#5c5c5a'];
                        $fileUrl = $doc->getFirstMediaUrl('file');
                        $mime = $doc->getFirstMedia('file')?->mime_type ?? '';
                    @endphp
                    <div class="rounded-xl overflow-hidden" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
                        <div class="px-4 py-4">
                            <div class="flex items-start justify-between gap-2 mb-3">
                                <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:5px;background:{{ $dBg }};color:{{ $dTxt }}">{{ str_replace('_', ' ', ucfirst($doc->type)) }}</span>
                                <span class="text-muted" style="font-size:11px">{{ $doc->created_at->format('d M Y') }}</span>
                            </div>
                            <div class="flex items-center gap-2 mb-2">
                                {{-- File icon --}}
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#8c8c8a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                <span class="font-medium text-ink" style="font-size:13.5px">{{ $doc->title }}</span>
                            </div>
                            @if($doc->notes)
                            <p class="text-muted" style="font-size:12px;margin-bottom:8px">{{ $doc->notes }}</p>
                            @endif
                        </div>
                        <div class="px-4 py-3 flex items-center justify-between" style="border-top:1px solid #eeeee9;background:#faf9f5">
                            @if($fileUrl)
                            <a href="{{ $fileUrl }}" target="_blank"
                               class="text-accent hover:text-accent-hover transition-colors font-medium"
                               style="font-size:12px">
                                Download ↗
                            </a>
                            @else
                            <span class="text-muted" style="font-size:12px">No file</span>
                            @endif
                            @if(auth()->user()->hasPermission('hr.manage'))
                            <form method="POST" action="{{ route('employees.documents.destroy', [$employee, $doc]) }}"
                                  onsubmit="return confirm('Delete this document?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-muted hover:text-red-600 transition-colors cursor-pointer">Delete</button>
                            </form>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

</div>
</x-layouts.app>
