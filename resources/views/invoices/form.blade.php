{{-- Usage: @include('invoices.form', ['invoice' => $invoice, 'customers' => $customers, 'projects' => $projects]) --}}

<div class="grid grid-cols-3 gap-6">

    {{-- Left: main form --}}
    <div class="col-span-2 flex flex-col gap-5">

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
            <div class="grid grid-cols-2 gap-4">
                {{-- Customer --}}
                <div class="col-span-2">
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
                <div class="col-span-2">
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;display:block;margin-bottom:4px">Project (optional)</label>
                    <div class="relative">
                        <select name="project_id" class="w-full appearance-none rounded-lg text-[13px] pl-3 pr-8 py-2"
                                :style="'background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none;' + (!selectedCustomer ? 'opacity:0.5;cursor:not-allowed' : '')"
                                x-model="selectedProject"
                                :disabled="!selectedCustomer"
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
                @foreach([['issue_date','Issue Date'],['due_date','Due Date']] as [$name,$label])
                <div>
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;display:block;margin-bottom:4px">{{ $label }} *</label>
                    <input type="date" name="{{ $name }}" value="{{ old($name, isset($invoice) ? $invoice->$name?->format('Y-m-d') : '') }}" required
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
        <div class="rounded-xl overflow-hidden" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)" x-data="itemsManager()">
            <div class="px-6 py-4 flex items-center justify-between" style="border-bottom:1px solid #eeeee9">
                <p style="font-size:13px;font-weight:600;color:#141413">Line Items</p>
                <button type="button" @click="addItem()"
                        style="font-size:12px;font-weight:500;color:#D97757;background:none;border:none;cursor:pointer">+ Add line</button>
            </div>
            <table class="w-full" style="font-size:12.5px">
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
                            <input type="text" :name="`items[${idx}][description]`" x-model="item.description" placeholder="Description"
                                   class="w-full rounded text-[12.5px] px-2 py-1.5"
                                   style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none"
                                   onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'">
                            <input type="hidden" :name="`items[${idx}][type]`" :value="item.type">
                            <input type="hidden" :name="`items[${idx}][task_id]`" :value="item.task_id">
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
    <div>
        <div class="rounded-xl p-5" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
            <p style="font-size:13px;font-weight:600;color:#141413;margin-bottom:14px">Discount & Tax</p>
            <div class="flex flex-col gap-4">
                <div>
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;display:block;margin-bottom:4px">Discount Type</label>
                    <div class="relative">
                        <select name="discount_type" class="w-full appearance-none rounded-lg text-[13px] pl-3 pr-8 py-2"
                                style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none">
                            <option value="">None</option>
                            <option value="percent" {{ old('discount_type', $invoice->discount_type ?? '') === 'percent' ? 'selected' : '' }}>Percentage (%)</option>
                            <option value="fixed" {{ old('discount_type', $invoice->discount_type ?? '') === 'fixed' ? 'selected' : '' }}>Fixed amount</option>
                        </select>
                        <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2" style="color:#8c8c8a">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </span>
                    </div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;display:block;margin-bottom:4px">Discount Value</label>
                    <input type="number" name="discount_value" value="{{ old('discount_value', $invoice->discount_value ?? '') }}" step="0.01" min="0"
                           class="w-full rounded-lg text-[13px] px-3 py-2"
                           style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none"
                           onfocus="this.style.borderColor='#D97757';this.style.background='#fff'"
                           onblur="this.style.borderColor='#e5e4df';this.style.background='#F5F4EF'">
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;display:block;margin-bottom:4px">Tax Rate (%)</label>
                    <input type="number" name="tax_rate" value="{{ old('tax_rate', $invoice->tax_rate ?? '') }}" step="0.01" min="0" max="100"
                           class="w-full rounded-lg text-[13px] px-3 py-2"
                           style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none"
                           onfocus="this.style.borderColor='#D97757';this.style.background='#fff'"
                           onblur="this.style.borderColor='#e5e4df';this.style.background='#F5F4EF'">
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $existingItems = (isset($invoice) && $invoice->relationLoaded('items') && $invoice->items->isNotEmpty())
        ? $invoice->items->map(fn($i) => [
            'type'           => $i->type,
            'task_id'        => $i->task_id,
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
function itemsManager() {
    return {
        items: @json($existingItems),
        addItem() {
            this.items.push({ type:'manual', task_id:null, description:'', quantity:1, unit:'units', unit_price:0, discount_value:null, discount_type:'', subtotal:0 });
        },
        recalc(item) {
            const base = (item.quantity || 0) * (item.unit_price || 0);
            const disc = item.discount_value > 0 ? base * item.discount_value / 100 : 0;
            item.subtotal = Math.max(0, base - disc);
        }
    }
}
</script>
