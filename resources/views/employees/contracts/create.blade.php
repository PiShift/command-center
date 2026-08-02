<x-layouts.app :title="'New Contract'">

<div class="flex flex-wrap items-center gap-2 mb-6" style="font-size:13px">
    <a href="{{ route('employees.index') }}"
       style="color:#8c8c8a;text-decoration:none"
       onmouseover="this.style.color='#141413'" onmouseout="this.style.color='#8c8c8a'">Employees</a>
    <span style="color:#c0bfbb">/</span>
    <a href="{{ route('employees.show', $employee) }}"
       style="color:#8c8c8a;text-decoration:none"
       onmouseover="this.style.color='#141413'" onmouseout="this.style.color='#8c8c8a'">{{ $employee->display_name }}</a>
    <span style="color:#c0bfbb">/</span>
    <h1 style="font-size:24px;font-weight:600;color:#141413;line-height:1">New Contract</h1>
</div>

@include('components.flash')

            <form method="POST" action="{{ route('employees.contracts.store', $employee) }}"
                  class="max-w-xl mx-auto"
                  x-data="{
                      type: '{{ old('employment_type', $employee->employment_type) }}',
                      templateId: '{{ old('template_id', '') }}',
                      allTemplates: @js($templates->map(fn($t) => ['id' => (string) $t->id, 'name' => $t->name, 'employment_type' => $t->employment_type, 'is_default' => (bool) $t->is_default])->values()),
                      previewLoading: false,

                      get filteredTemplates() {
                          return this.allTemplates.filter(t =>
                              t.employment_type === this.type || t.employment_type === 'all'
                          );
                      },

                      get hasTemplates() {
                          return this.filteredTemplates.length > 0;
                      },

                      init() {
                          // When type changes, reset templateId if the chosen template no longer applies
                          this.$watch('type', () => {
                              const current = this.allTemplates.find(t => t.id === this.templateId);
                              const stillValid = current && (current.employment_type === this.type || current.employment_type === 'all');
                              if (!stillValid) {
                                  const def   = this.filteredTemplates.find(t => t.is_default);
                                  const first = this.filteredTemplates[0];
                                  this.templateId = (def ?? first)?.id ?? '';
                              }
                          });
                          // Auto-select default/only template on load
                          if (!this.templateId && this.hasTemplates) {
                              const def   = this.filteredTemplates.find(t => t.is_default);
                              const first = this.filteredTemplates[0];
                              if (def || this.filteredTemplates.length === 1) {
                                  this.templateId = (def ?? first).id;
                              }
                          }
                      },

                      previewTemplate() {
                          if (!this.templateId) return;
                          const form  = document.createElement('form');
                          form.method = 'POST';
                          form.action = '/hr/contract-templates/' + this.templateId + '/preview';
                          form.target = '_blank';
                          const csrf  = document.createElement('input');
                          csrf.type   = 'hidden';
                          csrf.name   = '_token';
                          csrf.value  = document.querySelector('input[name=\'_token\']').value;
                          form.appendChild(csrf);
                          document.body.appendChild(form);
                          form.submit();
                          document.body.removeChild(form);
                      },

                      async preview() {
                          this.previewLoading = true;
                          const form = document.getElementById('contract-form');
                          const fd   = new FormData(form);
                          const res  = await fetch(form.action, { method: 'POST', body: fd });
                          if (res.ok) { window.open(URL.createObjectURL(await res.blob()), '_blank'); }
                          this.previewLoading = false;
                      }
                  }"
                  id="contract-form">
                @csrf
                @if($errors->any())
                <div class="mb-4 px-4 py-3 rounded-lg" style="background:#fff8f8;border:1px solid #ffd0d0;color:#b94040;font-size:13px">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
                @endif

                <div class="rounded-xl overflow-hidden mb-4" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
                    <div class="px-5 py-4" style="border-bottom:1px solid #eeeee9">
                        <p class="font-semibold text-ink" style="font-size:14px">Contract Details</p>
                        <p class="text-muted mt-0.5" style="font-size:12px">For {{ $employee->display_name }} ({{ $employee->employee_number }})</p>
                    </div>
                    <div class="px-5 py-5 space-y-4">

                        {{-- 1. Contract type first — drives template cascade --}}
                        <div>
                            <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Contract Type <span class="text-red-500">*</span></label>
                            <select name="employment_type" required x-model="type"
                                    class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                    style="font-size:14px">
                                <option value="CDI">CDI — Contrat à Durée Indéterminée</option>
                                <option value="CDD">CDD — Contrat à Durée Déterminée</option>
                                <option value="freelance">Freelance / Prestation de Services</option>
                                <option value="internship">Stage</option>
                                <option value="part_time">Temps Partiel</option>
                            </select>
                        </div>

                        {{-- 2. Template selector — cascades from type above --}}
                        <div>
                            <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Contract Template</label>

                            {{-- Templates exist for this type --}}
                            <template x-if="hasTemplates">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <select name="template_id" x-model="templateId"
                                                class="flex-1 text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                                style="font-size:14px">
                                            <option value="">— Use default for type —</option>
                                            <template x-for="tmpl in filteredTemplates" :key="tmpl.id">
                                                <option :value="tmpl.id"
                                                        x-text="tmpl.name + (tmpl.employment_type === 'all' ? ' (all types)' : '') + (tmpl.is_default ? ' ✓ Default' : '')">
                                                </option>
                                            </template>
                                        </select>
                                        <button type="button" x-show="templateId" x-cloak
                                                @click="previewTemplate()"
                                                class="text-xs font-medium px-3 py-2 rounded-lg bg-surface border border-line text-dim hover:text-ink transition-colors cursor-pointer"
                                                style="white-space:nowrap">
                                            Preview ↗
                                        </button>
                                    </div>
                                    <p class="mt-1 text-muted" style="font-size:11px">If none selected, the default template for this contract type will be used.</p>
                                </div>
                            </template>

                            {{-- No templates for selected type --}}
                            <template x-if="!hasTemplates">
                                <div class="flex items-center justify-between px-4 py-3 rounded-lg" style="background:#fff8f8;border:1px solid #ffd0d0">
                                    <p style="font-size:13px;color:#b94040">No template found for this contract type.</p>
                                    <a href="{{ route('contract-templates.create') }}" target="_blank"
                                       class="text-xs font-medium px-3 py-1.5 rounded-lg transition-colors"
                                       style="background:#fff;border:1px solid #ffd0d0;color:#b94040;white-space:nowrap">
                                        + Create template ↗
                                    </a>
                                </div>
                            </template>
                        </div>

                        {{-- Salary --}}
                        <div>
                            <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Base Salary (MRU/month) <span class="text-red-500">*</span></label>
                            <input type="number" name="base_salary" value="{{ old('base_salary') }}" required min="0" step="0.01"
                                   placeholder="0.00"
                                   class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                   style="font-size:14px">
                        </div>

                        {{-- Working hours + days --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Hours / Day <span class="text-red-500">*</span></label>
                                <input type="number" name="working_hours_per_day" value="{{ old('working_hours_per_day', 8) }}" required min="1" max="24" step="0.5"
                                       class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                       style="font-size:14px">
                            </div>
                            <div>
                                <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Days / Week <span class="text-red-500">*</span></label>
                                <input type="number" name="working_days_per_week" value="{{ old('working_days_per_week', 5) }}" required min="1" max="7"
                                       class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                       style="font-size:14px">
                            </div>
                        </div>

                        {{-- Notice period --}}
                        <div>
                            <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Notice Period (days) <span class="text-red-500">*</span></label>
                            <input type="number" name="notice_period_days" value="{{ old('notice_period_days', 30) }}" required min="0"
                                   class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                   style="font-size:14px">
                        </div>

                        {{-- Effective from + to --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Effective From <span class="text-red-500">*</span></label>
                                <input type="date" name="effective_from" value="{{ old('effective_from', now()->format('Y-m-d')) }}" required
                                       class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                       style="font-size:14px">
                            </div>
                            <div x-show="['CDD','internship'].includes(type)">
                                <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Effective To</label>
                                <input type="date" name="effective_to" value="{{ old('effective_to') }}"
                                       class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors"
                                       style="font-size:14px">
                            </div>
                        </div>

                        {{-- Additional clauses --}}
                        <div>
                            <label class="block text-muted font-bold uppercase tracking-wider mb-1.5" style="font-size:11px;letter-spacing:0.05em">Additional Clauses</label>
                            <textarea name="additional_clauses" rows="4" placeholder="Any additional terms or conditions..."
                                      class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent transition-colors resize-none"
                                      style="font-size:14px;line-height:1.5">{{ old('additional_clauses') }}</textarea>
                        </div>

                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-between">
                    <button type="button" @click="preview()" :disabled="previewLoading"
                            class="font-medium rounded-lg px-4 py-2 transition-colors cursor-pointer"
                            style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;font-size:13px">
                        <span x-text="previewLoading ? 'Generating…' : 'Preview PDF'"></span>
                    </button>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('employees.show', $employee) }}"
                           class="font-medium rounded-lg px-4 py-2 transition-colors"
                           style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;font-size:13px">
                            Cancel
                        </a>
                        <button type="submit" name="action" value="draft"
                                formnovalidate
                                class="font-medium rounded-lg px-4 py-2 transition-colors cursor-pointer"
                                style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;font-size:13px">
                            Save as Draft
                        </button>
                        <button type="submit" name="action" value="activate"
                                :disabled="!hasTemplates"
                                :class="!hasTemplates ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer'"
                                class="font-medium rounded-lg px-4 py-2 text-white transition-colors bg-accent hover:bg-accent-hover"
                                style="font-size:13px">
                            Save &amp; Activate
                        </button>
                    </div>
                </div>
            </form>

</x-layouts.app>
