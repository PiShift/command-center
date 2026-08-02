{{-- Usage: @include('invoices.form', ['invoice' => $invoice, 'customers' => $customers, 'projects' => $projects]) --}}

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="itemsManager()">

    {{-- Left: main form --}}
    <div class="lg:col-span-2 flex flex-col gap-5">

        {{-- Header / customer / project --}}
        <div class="rounded-xl p-6" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)"
             x-data="{
                 allProjects: {{ Js::from($projects->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'customer_id' => $p->customer_id])) }},
                 selectedCustomer: '{{ old('customer_id', $invoice->customer_id ?? '') }}',
                 selectedProject: '{{ old('project_id', $invoice->project_id ?? '') }}',
                 get filteredProjects() {
                     if (!this.selectedCustomer) return [];
                     return this.allProjects.filter(p => String(p.customer_id) === String(this.selectedCustomer));
                 },
                 onCustomerChange() {
                     if (!this.filteredProjects.find(p => String(p.id) === String(this.selectedProject))) {
                         this.selectedProject = '';
                     }
                 }
             }">
            <p style="font-size:13px;font-weight:600;color:#141413;margin-bottom:16px">Invoice Details</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Customer --}}
                <div class="sm:col-span-2">
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;display:block;margin-bottom:4px">Customer *</label>
                    <div class="relative">
                        <select name="customer_id" required class="w-full appearance-none rounded-lg text-[13px] pl-3 pr-8 py-2"
                                style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none"
                                onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'"
                                x-model="selectedCustomer" @change="onCustomerChange()">
                            <option value="">Select customer</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2" style="color:#8c8c8a">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </span>
                    </div>
                </div>
                {{-- Project --}}
                <div class="sm:col-span-2">
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;display:block;margin-bottom:4px">Project (optional)</label>
                    <div class="relative">
                        <select name="project_id" class="w-full appearance-none rounded-lg text-[13px] pl-3 pr-8 py-2"
                                :style="'background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none;' + (!selectedCustomer ? 'opacity:0.5;cursor:not-allowed' : '')"
                                x-model="selectedProject"
                                :disabled="!selectedCustomer"
                                @change="$dispatch('project-changed', { id: selectedProject })"
                                onfocus="if(!this.disabled){this.style.borderColor='#D97757'}" onblur="this.style.borderColor='#e5e4df'">
                            <option value="">No project</option>
                            <template x-for="p in filteredProjects" :key="p.id">
                                <option :value="p.id" x-text="p.name"></option>
                            </template>
                        </select>
                        <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2" style="color:#8c8c8a">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </span>
                    </div>
                </div>
                {{-- Dates --}}
                @php
                    $defaultIssue = now()->format('Y-m-d');
                    $defaultDue   = now()->addDays(7)->format('Y-m-d');
                    $dateDefaults = ['issue_date' => $defaultIssue, 'due_date' => $defaultDue];
                @endphp
                @foreach([['issue_date','Issue Date'],['due_date','Due Date']] as [$name,$label])
                <div>
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;display:block;margin-bottom:4px">{{ $label }} *</label>
                    <input type="date" name="{{ $name }}" value="{{ old($name, isset($invoice) && $invoice->exists ? $invoice->$name?->format('Y-m-d') : $dateDefaults[$name]) }}" required
                           class="w-full rounded-lg text-[13px] px-3 py-2"
                           style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none"
                           onfocus="this.style.borderColor='#D97757';this.style.background='#fff'"
                           onblur="this.style.borderColor='#e5e4df';this.style.background='#F5F4EF'">
                </div>
                @endforeach
                {{-- Currency (locked to MRU) --}}
                <div>
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;display:block;margin-bottom:4px">Currency</label>
                    <input type="text" name="currency" value="MRU" maxlength="10" disabled
                           class="w-full rounded-lg text-[13px] px-3 py-2"
                           style="background:#F5F4EF;border:1px solid #e5e4df;color:#8c8c8a;outline:none;cursor:not-allowed">
                    {{-- Always submit MRU even though field is disabled --}}
                    <input type="hidden" name="currency" value="MRU">
                </div>
            </div>
        </div>

        {{-- Line items --}}
        @php
            $projectIds = $projects->pluck('id')->toArray();
            $allProjectTasks = \App\Models\Task::where('status', 'done')
                ->whereIn('project_id', $projectIds)
                ->with('sprint:id,name')
                ->get(['id', 'project_id', 'sprint_id', 'title', 'estimated_hours'])
                ->groupBy('project_id')
                ->map(fn($ts) => $ts->map(fn($t) => [
                    'id'              => $t->id,
                    'title'           => $t->title,
                    'sprint_name'     => $t->sprint?->name,
                    'estimated_hours' => $t->estimated_hours,
                ])->values()->all())
                ->all();
            $allProjectSprints = \App\Models\Sprint::whereIn('project_id', $projectIds)
                ->whereIn('status', ['active', 'completed'])
                ->with('tasks:id,sprint_id,title')
                ->get(['id', 'project_id', 'name', 'status'])
                ->groupBy('project_id')
                ->map(fn($ss) => $ss->map(fn($s) => [
                    'id'     => $s->id,
                    'name'   => $s->name,
                    'status' => $s->status,
                    'tasks'  => $s->tasks->pluck('title')->values()->all(),
                ])->values()->all())
                ->all();
        @endphp
        <div class="rounded-xl overflow-hidden" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
            <div class="px-6 py-4 flex items-center justify-between gap-3 flex-wrap" style="border-bottom:1px solid #eeeee9">
                <p style="font-size:13px;font-weight:600;color:#141413">Line Items</p>
                <div class="flex items-center gap-2 flex-wrap">
                    <button type="button" @click="addItem()"
                            style="font-size:12px;font-weight:500;color:#D97757;background:none;border:none;cursor:pointer">+ Add line</button>

                    <template x-if="currentProjectId">
                        <button type="button"
                                @click="showTaskPanel = !showTaskPanel; showSprintSelect = false"
                                :style="showTaskPanel
                                    ? 'font-size:12px;font-weight:500;color:#fff;background:#D97757;border:1px solid #D97757;cursor:pointer;border-radius:6px;padding:3px 10px'
                                    : 'font-size:12px;font-weight:500;color:#5c5c5a;background:none;border:1px solid #e5e4df;cursor:pointer;border-radius:6px;padding:3px 10px'"
                                >+ From Tasks</button>
                    </template>

                    <template x-if="currentProjectId">
                        <div class="flex items-center gap-1.5">
                            <button type="button"
                                    @click="showSprintSelect = !showSprintSelect; showTaskPanel = false"
                                    :style="showSprintSelect
                                        ? 'font-size:12px;font-weight:500;color:#fff;background:#D97757;border:1px solid #D97757;cursor:pointer;border-radius:6px;padding:3px 10px'
                                        : 'font-size:12px;font-weight:500;color:#5c5c5a;background:none;border:1px solid #e5e4df;cursor:pointer;border-radius:6px;padding:3px 10px'"
                                    >+ From Sprint</button>
                            <template x-if="showSprintSelect">
                                <div class="flex items-center gap-1.5">
                                    <div class="relative">
                                        <select x-model="selectedSprintId"
                                                style="font-size:12px;background:#F5F4EF;border:1px solid #e5e4df;color:#141413;border-radius:6px;padding:3px 22px 3px 8px;outline:none;appearance:none">
                                            <option value="">Pick sprint…</option>
                                            <template x-for="s in projectSprints" :key="s.id">
                                                <option :value="s.id" x-text="s.name + (s.status === 'active' ? ' ●' : '')"></option>
                                            </template>
                                        </select>
                                        <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2" style="color:#8c8c8a">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                                        </span>
                                    </div>
                                    <button type="button" @click="addFromSprint()"
                                            :disabled="!selectedSprintId"
                                            style="font-size:12px;font-weight:500;color:#fff;background:#141413;border:none;cursor:pointer;border-radius:6px;padding:3px 10px">Add</button>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
            <div class="overflow-x-auto">
            <table class="w-full min-w-[760px]" style="font-size:12.5px">
                <thead>
                    <tr style="background:#faf9f5;border-bottom:1px solid #e5e4df">
                        <th class="px-4 py-2.5 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;width:35%">Description</th>
                        <th class="px-3 py-2.5 text-left" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;width:10%">Unit</th>
                        <th class="px-3 py-2.5 text-right" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;width:10%">Qty</th>
                        <th class="px-3 py-2.5 text-right" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;width:15%">Unit Price</th>
                        <th class="px-3 py-2.5 text-right" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;width:10%">Disc</th>
                        <th class="px-3 py-2.5 text-right" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8c8a;width:12%">Subtotal</th>
                        <th class="px-2 py-2.5" style="width:5%"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, idx) in items" :key="idx">
                    <tr style="border-bottom:1px solid #eeeee9">
                        <td class="px-4 py-2">
                            <textarea :name="`items[${idx}][description]`" x-model="item.description" placeholder="Description"
                                      rows="1"
                                      class="w-full rounded text-[12.5px] px-2 py-1.5"
                                      style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none;resize:none;line-height:1.5;overflow:hidden"
                                      onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'"
                                      @input="$el.style.height='auto'; $el.style.height=$el.scrollHeight+'px'"
                                      x-init="$nextTick(() => { $el.style.height='auto'; $el.style.height=$el.scrollHeight+'px' })"></textarea>
                        </td>
                        <td class="px-3 py-2">
                            <input type="text" :name="`items[${idx}][unit]`" x-model="item.unit" placeholder="e.g. hours"
                                   class="w-full rounded text-[12px] px-2 py-1.5"
                                   style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none"
                                   onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'">
                        </td>
                        <td class="px-3 py-2">
                            <input type="number" :name="`items[${idx}][quantity]`" x-model.number="item.quantity" step="0.01" min="0"
                                   @input="recalc(item)" class="w-full rounded text-[12.5px] px-2 py-1.5 text-right"
                                   style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none">
                        </td>
                        <td class="px-3 py-2">
                            <input type="number" :name="`items[${idx}][unit_price]`" x-model.number="item.unit_price" step="0.01" min="0"
                                   @input="recalc(item)" class="w-full rounded text-[12.5px] px-2 py-1.5 text-right"
                                   style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none">
                        </td>
                        <td class="px-3 py-2">
                            <input type="number" :name="`items[${idx}][discount_value]`" x-model.number="item.discount_value" step="0.01" min="0"
                                   @input="recalc(item)" placeholder="%" class="w-full rounded text-[12.5px] px-2 py-1.5 text-right"
                                   style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none">
                            <input type="hidden" :name="`items[${idx}][discount_type]`" :value="item.discount_value > 0 ? 'percent' : ''">
                        </td>
                        <td class="px-3 py-2 text-right" style="color:#141413;font-weight:500" x-text="item.subtotal.toFixed(2)"></td>
                        <td class="px-2 py-2 text-center">
                            <button type="button" @click="items.splice(idx,1)" style="color:#b94040;background:none;border:none;cursor:pointer;font-size:14px">&times;</button>
                        </td>
                    </tr>
                    </template>
                </tbody>
            </table>
            </div>

            {{-- From Tasks panel --}}
            <div x-show="showTaskPanel" style="border-top:1px solid #eeeee9;background:#faf9f5">
                <div class="px-6 py-4">
                    <p style="font-size:12px;font-weight:600;color:#141413;margin-bottom:10px">Select done tasks from this project:</p>
                    <template x-if="projectTasks.length === 0">
                        <p style="font-size:12px;color:#8c8c8a">No completed tasks found for this project.</p>
                    </template>
                    <template x-if="projectTasks.length > 0">
                        <div>
                            <div class="flex flex-col gap-1.5 mb-4" style="max-height:260px;overflow-y:auto">
                                <template x-for="task in projectTasks" :key="task.id">
                                    <label class="flex items-center gap-3 px-3 py-2 rounded-lg cursor-pointer"
                                           style="background:#fff;border:1px solid #e5e4df">
                                        <input type="checkbox" :value="task.id" x-model="selectedTaskIds"
                                               style="accent-color:#D97757;width:14px;height:14px;flex-shrink:0;cursor:pointer">
                                        <div style="flex:1;min-width:0">
                                            <p style="font-size:12.5px;color:#141413;font-weight:500" x-text="task.title"></p>
                                            <p style="font-size:11px;color:#8c8c8a">
                                                <span x-text="task.sprint_name || 'No sprint'"></span>
                                                <template x-if="task.estimated_hours">
                                                    <span> &middot; <span x-text="task.estimated_hours"></span>h estimated</span>
                                                </template>
                                            </p>
                                        </div>
                                    </label>
                                </template>
                            </div>
                            <button type="button" @click="addFromTasks()"
                                    :disabled="selectedTaskIds.length === 0"
                                    :style="selectedTaskIds.length > 0
                                        ? 'font-size:12px;font-weight:500;color:#fff;background:#D97757;border:none;cursor:pointer;border-radius:6px;padding:5px 14px'
                                        : 'font-size:12px;font-weight:500;color:#8c8c8a;background:#e5e4df;border:none;cursor:not-allowed;border-radius:6px;padding:5px 14px'"
                                    x-text="selectedTaskIds.length > 0 ? 'Add ' + selectedTaskIds.length + ' task(s) as line items' : 'Select tasks above'"
                                    ></button>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Notes --}}
        <div class="rounded-xl p-6" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
            <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;display:block;margin-bottom:6px">Payment Info / Notes</label>
            <textarea name="notes" rows="4" placeholder="Bank account details, payment instructions..."
                      class="w-full rounded-lg text-[13px] px-3 py-2"
                      style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none;resize:vertical;line-height:1.5"
                      onfocus="this.style.borderColor='#D97757';this.style.background='#fff'"
                      onblur="this.style.borderColor='#e5e4df';this.style.background='#F5F4EF'">{{ old('notes', $invoice->notes ?? '') }}</textarea>
        </div>

    </div>

    {{-- Right: discount & tax --}}
    <div class="lg:sticky lg:top-6 self-start">
        <div class="rounded-xl p-5" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
            <p style="font-size:13px;font-weight:600;color:#141413;margin-bottom:14px">Discount & Tax</p>
            <div class="flex flex-col gap-4">
                <div>
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;display:block;margin-bottom:4px">Discount Type</label>
                    <div class="relative">
                        <select name="discount_type" x-model="discountType" class="w-full appearance-none rounded-lg text-[13px] pl-3 pr-8 py-2"
                                style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none">
                            <option value="">None</option>
                            <option value="percent">Percentage (%)</option>
                            <option value="fixed">Fixed amount</option>
                        </select>
                        <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2" style="color:#8c8c8a">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </span>
                    </div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;display:block;margin-bottom:4px">Discount Value</label>
                    <input type="number" name="discount_value" x-model.number="discountValue" step="0.01" min="0"
                           class="w-full rounded-lg text-[13px] px-3 py-2"
                           style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none"
                           onfocus="this.style.borderColor='#D97757';this.style.background='#fff'"
                           onblur="this.style.borderColor='#e5e4df';this.style.background='#F5F4EF'">
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;display:block;margin-bottom:4px">Tax Rate (%)</label>
                    <input type="number" name="tax_rate" x-model.number="taxRate" step="0.01" min="0" max="100"
                           class="w-full rounded-lg text-[13px] px-3 py-2"
                           style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none"
                           onfocus="this.style.borderColor='#D97757';this.style.background='#fff'"
                           onblur="this.style.borderColor='#e5e4df';this.style.background='#F5F4EF'">
                </div>

                {{-- Live totals --}}
                <div style="border-top:1px solid #eeeee9;padding-top:14px;margin-top:2px">
                    <div class="flex justify-between" style="font-size:12px;color:#8c8c8a;margin-bottom:6px">
                        <span>Items subtotal</span>
                        <span x-text="'MRU ' + itemsSubtotal.toFixed(2)"></span>
                    </div>
                    <template x-if="discountAmount > 0">
                        <div class="flex justify-between" style="font-size:12px;color:#b94040;margin-bottom:6px">
                            <span x-text="discountType === 'percent' ? 'Discount (' + discountValue + '%)' : 'Discount'"></span>
                            <span x-text="'- MRU ' + discountAmount.toFixed(2)"></span>
                        </div>
                    </template>
                    <template x-if="taxAmount > 0">
                        <div class="flex justify-between" style="font-size:12px;color:#5c5c5a;margin-bottom:6px">
                            <span x-text="'Tax (' + taxRate + '%)'" ></span>
                            <span x-text="'MRU ' + taxAmount.toFixed(2)"></span>
                        </div>
                    </template>
                    <div class="flex justify-between" style="font-size:14px;font-weight:700;color:#141413;padding-top:8px;border-top:1px solid #eeeee9;margin-top:4px">
                        <span>Total</span>
                        <span x-text="'MRU ' + grandTotal.toFixed(2)"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $existingItems = (isset($invoice) && $invoice->relationLoaded('items') && $invoice->items->isNotEmpty())
        ? $invoice->items->map(fn($i) => [
            'description'    => $i->description,
            'quantity'       => $i->quantity,
            'unit'           => $i->unit,
            'unit_price'     => $i->unit_price,
            'discount_value' => $i->discount_value,
            'discount_type'  => $i->discount_type,
            'subtotal'       => $i->subtotal,
        ])->values()->all()
        : [];
@endphp
<script>
const _allProjectTasks   = @json($allProjectTasks);
const _allProjectSprints = @json($allProjectSprints);

function itemsManager() {
    return {
        items: @json($existingItems).map(i => ({ isMultiline: false, ...i })),
        currentProjectId: '{{ old('project_id', $invoice->project_id ?? '') }}',
        showTaskPanel:    false,
        showSprintSelect: false,
        selectedTaskIds:  [],
        selectedSprintId: '',
        discountType:  '{{ old('discount_type', $invoice->discount_type ?? '') }}',
        discountValue: {{ (float)(old('discount_value', $invoice->discount_value ?? 0)) }},
        taxRate:       {{ (float)(old('tax_rate', $invoice->tax_rate ?? 0)) }},

        get itemsSubtotal() {
            return this.items.reduce((s, i) => s + (i.subtotal || 0), 0);
        },
        get discountAmount() {
            const sub = this.itemsSubtotal;
            if (this.discountType === 'percent' && this.discountValue > 0) return sub * this.discountValue / 100;
            if (this.discountType === 'fixed' && this.discountValue > 0) return Math.min(sub, this.discountValue);
            return 0;
        },
        get taxAmount() {
            return Math.max(0, this.itemsSubtotal - this.discountAmount) * (this.taxRate || 0) / 100;
        },
        get grandTotal() {
            return Math.max(0, this.itemsSubtotal - this.discountAmount) + this.taxAmount;
        },

        init() {
            window.addEventListener('project-changed', (e) => {
                this.currentProjectId = e.detail.id ? String(e.detail.id) : '';
                this.showTaskPanel    = false;
                this.showSprintSelect = false;
                this.selectedTaskIds  = [];
                this.selectedSprintId = '';
            });
        },

        get projectTasks() {
            if (!this.currentProjectId) return [];
            return _allProjectTasks[this.currentProjectId] ?? [];
        },

        get projectSprints() {
            if (!this.currentProjectId) return [];
            return _allProjectSprints[this.currentProjectId] ?? [];
        },

        addItem() {
            this.items.push({
                isMultiline: false, type: 'manual', task_id: null,
                description: '', quantity: 1, unit: 'units',
                unit_price: 0, discount_value: null, discount_type: '', subtotal: 0,
            });
        },

        addFromTasks() {
            const ids = this.selectedTaskIds.map(String);
            this.projectTasks
                .filter(t => ids.includes(String(t.id)))
                .forEach(t => {
                    const qty  = t.estimated_hours ? parseFloat(t.estimated_hours) : 1;
                    const unit = t.estimated_hours ? 'hours' : 'fixed';
                    this.items.push({
                        isMultiline: false,
                        type: 'manual', task_id: null,
                        description: t.title,
                        quantity: qty, unit: unit,
                        unit_price: 0, discount_value: null, discount_type: '', subtotal: 0,
                    });
                });
            this.selectedTaskIds = [];
            this.showTaskPanel   = false;
        },

        addFromSprint() {
            if (!this.selectedSprintId) return;
            const sprint = this.projectSprints.find(s => String(s.id) === String(this.selectedSprintId));
            if (!sprint) return;
            const taskLines  = sprint.tasks.map(t => '\u00b7 ' + t).join('\n');
            const desc       = sprint.name + '\n\n' + taskLines;
            this.items.push({
                isMultiline: true,
                type: 'manual', task_id: null,
                description: desc,
                quantity: 1, unit: 'fixed',
                unit_price: 0, discount_value: null, discount_type: '', subtotal: 0,
            });
            this.selectedSprintId = '';
            this.showSprintSelect = false;
        },

        recalc(item) {
            const base = (item.quantity || 0) * (item.unit_price || 0);
            const disc = item.discount_value > 0 ? base * item.discount_value / 100 : 0;
            item.subtotal = Math.max(0, base - disc);
        },
    };
}
</script>
