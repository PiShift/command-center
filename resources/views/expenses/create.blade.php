<x-layouts.app title="New Expense">

<style>
    @media (max-width: 768px) {
        .expense-form-two-col,
        .expense-form-recurring-grid,
        .expense-form-actions,
        .expense-category-inline {
            display: flex !important;
            flex-direction: column !important;
            align-items: stretch !important;
        }

        .expense-form-actions {
            gap: 8px !important;
        }

        .expense-form-actions a,
        .expense-form-actions button,
        .expense-category-inline > * {
            width: 100%;
        }

        .expense-category-inline button {
            justify-content: center;
        }
    }
</style>

<div style="max-width:720px;margin:0 auto;padding:32px 24px">

    <div style="margin-bottom:24px">
        <a href="{{ route('expenses.monthly-overview') }}"
           style="font-size:13px;color:#8c8c8a;text-decoration:none;display:inline-flex;align-items:center;gap:6px;margin-bottom:16px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Back to Expenses
        </a>
        <h1 style="font-size:22px;font-weight:700;color:#141413;margin:0">New Expense</h1>
    </div>

    @if($errors->any())
        <div style="background:#fdf0f0;border:1px solid #f5c6c6;border-radius:8px;padding:11px 16px;color:#b94040;font-size:13px;margin-bottom:20px">
            <ul style="margin:0;padding-left:18px">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data"
          x-data="{
              recurring: false,
              expenseDate: '{{ date('Y-m-d') }}',
              showNewCat: false,
              newCatName: '',
              newCatColor: '#D97757',
              addingCat: false,
              selectedCompanyAccountId: '{{ old('company_account_id', $defaultCompanyAccountId) }}',
              accountCurrencyById: @js($companyAccounts->mapWithKeys(fn ($account) => [(string) $account->id => strtoupper((string) $account->currency)])),
              get selectedCurrency() {
                  return this.accountCurrencyById[String(this.selectedCompanyAccountId)] || 'MRU';
              },
              get amountLabel() {
                  return this.selectedCurrency === 'USD' ? 'Amount (USD) *' : 'Amount (MRU) *';
              }
          }"
          style="background:#fff;border:1px solid #e5e4df;border-radius:12px;padding:28px">
        @csrf

        <div style="display:grid;gap:20px">

            {{-- Title --}}
            <div>
                <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">Title *</label>
                <input type="text" name="title" value="{{ old('title') }}" required
                       style="width:100%;padding:10px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;box-sizing:border-box"
                       placeholder="e.g. Monthly server bill"
                       onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'">
            </div>

            <div class="expense-form-two-col" style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                {{-- Category --}}
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">Category</label>
                    <div style="position:relative">
                        <select name="category_id"
                                style="width:100%;padding:10px 32px 10px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;appearance:none;box-sizing:border-box">
                            <option value="">No category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <svg style="position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#8c8c8a" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                    {{-- Quick add category --}}
                    <button type="button" @click="showNewCat = !showNewCat"
                            style="margin-top:6px;font-size:12px;color:#D97757;background:none;border:none;padding:0;cursor:pointer;display:inline-flex;align-items:center;gap:4px">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        <span x-text="showNewCat ? 'Cancel' : 'New category'"></span>
                    </button>
                    <div x-show="showNewCat" x-cloak class="expense-category-inline"
                         style="margin-top:8px;display:flex;align-items:center;gap:8px;padding:10px 12px;background:#faf9f5;border:1px solid #e5e4df;border-radius:8px">
                        <input type="text" x-model="newCatName" placeholder="Category name" maxlength="80"
                               style="flex:1;padding:7px 10px;font-size:13px;border:1px solid #e5e4df;border-radius:7px;background:#fff;color:#141413;outline:none"
                               onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'">
                        <input type="color" x-model="newCatColor" title="Color"
                               style="width:34px;height:34px;border:1px solid #e5e4df;border-radius:7px;cursor:pointer;padding:2px;flex-shrink:0">
                        <button type="button" :disabled="addingCat || !newCatName.trim()"
                                @click="
                                    addingCat = true;
                                    fetch('{{ route('expense-categories.store') }}', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                        body: JSON.stringify({ name: newCatName.trim(), color: newCatColor })
                                    })
                                    .then(r => r.json())
                                    .then(data => {
                                        const sel = $el.closest('[x-data]').querySelector('select[name=category_id]');
                                        const opt = new Option(data.name, data.id, true, true);
                                        sel.add(opt);
                                        sel.value = data.id;
                                        newCatName = ''; newCatColor = '#D97757'; showNewCat = false;
                                    })
                                    .catch(() => alert('Could not create category.'))
                                    .finally(() => addingCat = false)
                                "
                                style="padding:7px 14px;background:#D97757;border:none;border-radius:7px;font-size:12px;font-weight:500;color:#fff;cursor:pointer;white-space:nowrap;flex-shrink:0">
                            <span x-text="addingCat ? 'Adding…' : 'Add'"></span>
                        </button>
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
                                <option value="{{ $project->id }}" @selected(old('project_id') == $project->id)>{{ $project->name }}</option>
                            @endforeach
                        </select>
                        <svg style="position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#8c8c8a" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                </div>
            </div>

            <div>
                <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">Company Account *</label>
                <div style="position:relative">
                    <select name="company_account_id" required x-model="selectedCompanyAccountId"
                            style="width:100%;padding:10px 32px 10px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;appearance:none;box-sizing:border-box">
                        @foreach($companyAccounts as $account)
                            <option value="{{ $account->id }}"
                                @selected((string) old('company_account_id', $defaultCompanyAccountId) === (string) $account->id)>
                                {{ $account->name }}
                                @if($account->bank_name)
                                    — {{ $account->bank_name }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <svg style="position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#8c8c8a" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
            </div>

            <div class="expense-form-two-col" style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                {{-- Amount --}}
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px" x-text="amountLabel"></label>
                    <input type="number" name="amount" value="{{ old('amount') }}" min="0" step="0.01" required
                           style="width:100%;padding:10px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;box-sizing:border-box"
                           placeholder="0.00"
                           onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'">
                </div>

                {{-- Date --}}
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">Date *</label>
                    <input type="date" name="expense_date" value="{{ old('expense_date', date('Y-m-d')) }}" required
                           x-model="expenseDate"
                           style="width:100%;padding:10px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;box-sizing:border-box"
                           onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'">
                </div>
            </div>

            {{-- Status --}}
            <div>
                <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">Status</label>
                <div style="display:flex;gap:16px">
                    <label style="display:flex;align-items:center;gap:8px;font-size:14px;color:#141413;cursor:pointer">
                        <input type="radio" name="status" value="draft" @checked(old('status','draft') === 'draft')>
                        Draft
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;font-size:14px;color:#141413;cursor:pointer">
                        <input type="radio" name="status" value="confirmed" @checked(old('status') === 'confirmed')>
                        Confirmed
                    </label>
                </div>
            </div>

            {{-- Notes --}}
            <div>
                <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">Notes</label>
                <textarea name="notes" rows="3"
                          style="width:100%;padding:10px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;resize:vertical;box-sizing:border-box"
                          placeholder="Optional notes…"
                          onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'">{{ old('notes') }}</textarea>
            </div>

            {{-- Receipt --}}
            <div>
                <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">Receipt (jpg/jpeg/png/pdf — max 10 MB)</label>
                <x-file-upload name="receipt" accept=".jpg,.jpeg,.png,.pdf" />
            </div>

            {{-- Recurring toggle --}}
            <div style="border-top:1px solid #eeeee9;padding-top:20px;margin-top:4px">
                <input type="hidden" name="is_recurring" :value="recurring ? '1' : '0'">
                <div @click="recurring = !recurring"
                     style="display:flex;align-items:center;gap:10px;cursor:pointer;user-select:none">
                    <div :style="recurring ? 'background:#D97757' : 'background:#e5e4df'"
                         style="width:40px;height:22px;border-radius:11px;transition:background 150ms ease;position:relative;flex-shrink:0">
                        <div :style="recurring ? 'left:20px' : 'left:2px'"
                             style="position:absolute;top:2px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.2);transition:left 150ms ease"></div>
                    </div>
                    <div>
                        <div style="font-size:14px;font-weight:500;color:#141413">Make Recurring</div>
                        <div style="font-size:12px;color:#8c8c8a">Automatically schedule this expense on a repeating basis</div>
                    </div>
                </div>

                <div x-show="recurring" x-cloak class="expense-form-recurring-grid" style="margin-top:16px;display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">Frequency *</label>
                        <div style="position:relative">
                            <select name="rec_frequency"
                                    style="width:100%;padding:10px 32px 10px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;appearance:none;box-sizing:border-box">
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="annual">Annual</option>
                            </select>
                            <svg style="position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#8c8c8a" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">Start Date *</label>
                        <input type="date" name="rec_start_date" :value="expenseDate"
                               style="width:100%;padding:10px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;box-sizing:border-box"
                               onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'">
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">End Date</label>
                        <input type="date" name="rec_end_date" value="{{ old('rec_end_date') }}"
                               style="width:100%;padding:10px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;box-sizing:border-box"
                               onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'">
                        <p style="font-size:11px;color:#8c8c8a;margin:4px 0 0">Stop after this date (optional).</p>
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">Max Occurrences</label>
                        <input type="number" name="rec_max_occurrences" min="1" step="1" value="{{ old('rec_max_occurrences') }}" placeholder="Unlimited"
                               style="width:100%;padding:10px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;box-sizing:border-box"
                               onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'">
                        <p style="font-size:11px;color:#8c8c8a;margin:4px 0 0">Stop after N drafts (optional).</p>
                    </div>
                </div>
            </div>

        </div>

        <div class="expense-form-actions" style="display:flex;justify-content:flex-end;gap:12px;margin-top:28px;padding-top:20px;border-top:1px solid #eeeee9">
            <a href="{{ route('expenses.monthly-overview') }}"
               style="padding:10px 20px;background:#F5F4EF;border:1px solid #e5e4df;border-radius:8px;font-size:14px;font-weight:500;color:#141413;text-decoration:none">
                Cancel
            </a>
            <button type="submit"
                    style="padding:10px 24px;background:#D97757;border:none;border-radius:8px;font-size:14px;font-weight:500;color:#fff;cursor:pointer">
                Create Expense
            </button>
        </div>
    </form>
</div>
</x-layouts.app>
