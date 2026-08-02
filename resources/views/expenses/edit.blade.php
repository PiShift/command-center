<x-layouts.app title="Edit Expense">

<style>
    @media (max-width: 768px) {
        .expense-form-two-col,
        .expense-form-recurring-grid,
        .expense-form-actions {
            display: flex !important;
            flex-direction: column !important;
            align-items: stretch !important;
        }

        .expense-form-actions {
            gap: 8px !important;
        }

        .expense-form-actions a,
        .expense-form-actions button {
            width: 100%;
        }
    }
</style>

<div style="max-width:720px;margin:0 auto;padding:32px 0">

    <div style="margin-bottom:24px">
        <a href="{{ route('expenses.monthly-overview') }}"
           style="font-size:13px;color:#8c8c8a;text-decoration:none;display:inline-flex;align-items:center;gap:6px;margin-bottom:16px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Back to Expenses
        </a>
        <h1 style="font-size:22px;font-weight:700;color:#141413;margin:0">Edit Expense</h1>
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

    <form method="POST" action="{{ route('expenses.update', $expense) }}" enctype="multipart/form-data"
          x-data="{
              recurring: {{ (string) old('is_recurring', '0') === '1' ? 'true' : 'false' }},
              expenseDate: '{{ old('expense_date', $expense->expense_date->format('Y-m-d')) }}',
              selectedCompanyAccountId: '{{ old('company_account_id', $expense->company_account_id ?? $defaultCompanyAccountId) }}',
              accountCurrencyById: @js($companyAccounts->mapWithKeys(fn ($account) => [(string) $account->id => strtoupper((string) $account->currency)])),
              get selectedCurrency() {
                  return this.accountCurrencyById[String(this.selectedCompanyAccountId)] || 'MRU';
              },
              get amountLabel() {
                  return this.selectedCurrency === 'USD' ? 'Amount (USD) *' : 'Amount (MRU) *';
              }
          }"
          style="background:#fff;border:1px solid #e5e4df;border-radius:12px;padding:28px">
        @csrf @method('PUT')

        <div style="display:grid;gap:20px">

            {{-- Title --}}
            <div>
                <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">Title *</label>
                <input type="text" name="title" value="{{ old('title', $expense->title) }}" required
                       style="width:100%;padding:10px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;box-sizing:border-box"
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
                                <option value="{{ $cat->id }}" @selected(old('category_id', $expense->category_id) == $cat->id)>{{ $cat->name }}</option>
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
                                <option value="{{ $project->id }}" @selected(old('project_id', $expense->project_id) == $project->id)>{{ $project->name }}</option>
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
                                @selected((string) old('company_account_id', $expense->company_account_id ?? $defaultCompanyAccountId) === (string) $account->id)>
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
                    <input type="number" name="amount" value="{{ old('amount', $expense->amount) }}" min="0" step="0.01" required
                           style="width:100%;padding:10px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;box-sizing:border-box"
                           onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'">
                </div>

                {{-- Date --}}
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">Date *</label>
                    <input type="date" name="expense_date" value="{{ old('expense_date', $expense->expense_date->format('Y-m-d')) }}" required
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
                        <input type="radio" name="status" value="draft" @checked(old('status', $expense->status) === 'draft')>
                        Draft
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;font-size:14px;color:#141413;cursor:pointer">
                        <input type="radio" name="status" value="confirmed" @checked(old('status', $expense->status) === 'confirmed')>
                        Confirmed
                    </label>
                </div>
            </div>

            {{-- Notes --}}
            <div>
                <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">Notes</label>
                <textarea name="notes" rows="3"
                          style="width:100%;padding:10px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;resize:vertical;box-sizing:border-box"
                          onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'">{{ old('notes', $expense->notes) }}</textarea>
            </div>

            {{-- Current receipt --}}
            @php $receipt = $expense->getFirstMedia('receipt'); @endphp
            @if($receipt)
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">Current Receipt</label>
                    <a href="{{ $receipt->getUrl() }}" target="_blank"
                       style="font-size:13px;color:#D97757;text-decoration:none">
                        {{ $receipt->file_name }}
                    </a>
                </div>
            @endif

            {{-- New receipt upload --}}
            <div>
                <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">
                    {{ $receipt ? 'Replace Receipt' : 'Receipt' }} (jpg/jpeg/png/pdf — max 10 MB)
                </label>
                <x-file-upload name="receipt" accept=".jpg,.jpeg,.png,.pdf"
                               :current-file="$receipt ? $receipt->file_name : null" />
            </div>

            {{-- Recurring toggle --}}
            <div style="border-top:1px solid #eeeee9;padding-top:20px;margin-top:4px">
                <input type="hidden" name="is_recurring" value="0">
                <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;user-select:none">
                    <input type="checkbox" name="is_recurring" value="1" x-model="recurring" style="margin-top:2px;width:16px;height:16px;flex-shrink:0;accent-color:#D97757;appearance:auto;-webkit-appearance:checkbox">
                    <span>
                        <span style="font-size:14px;font-weight:500;color:#141413;display:block">Make Recurring</span>
                        <span style="font-size:12px;color:#8c8c8a;display:block">Automatically schedule this expense on a repeating basis</span>
                    </span>
                </label>

                <div x-show.important="recurring" x-cloak class="expense-form-recurring-grid" style="margin-top:16px;display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">Frequency *</label>
                        <div style="position:relative">
                            <select name="rec_frequency"
                                    style="width:100%;padding:10px 32px 10px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;appearance:none;box-sizing:border-box">
                                <option value="monthly"   @selected(old('rec_frequency', $expense->recurringCharge?->frequency) === 'monthly')>Monthly</option>
                                <option value="quarterly" @selected(old('rec_frequency', $expense->recurringCharge?->frequency) === 'quarterly')>Quarterly</option>
                                <option value="annual"    @selected(old('rec_frequency', $expense->recurringCharge?->frequency) === 'annual')>Annual</option>
                            </select>
                            <svg style="position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#8c8c8a" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">Start Date *</label>
                        <input type="date" name="rec_start_date"
                               value="{{ old('rec_start_date', $expense->recurringCharge?->start_date?->format('Y-m-d') ?? $expense->expense_date->format('Y-m-d')) }}"
                               style="width:100%;padding:10px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;box-sizing:border-box"
                               onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'">
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">End Date</label>
                        <input type="date" name="rec_end_date"
                               value="{{ old('rec_end_date', $expense->recurringCharge?->end_date?->format('Y-m-d')) }}"
                               style="width:100%;padding:10px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;box-sizing:border-box"
                               onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'">
                        <p style="font-size:11px;color:#8c8c8a;margin:4px 0 0">Stop after this date (optional).</p>
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#8c8c8a;margin-bottom:6px">Max Occurrences</label>
                        <input type="number" name="rec_max_occurrences" min="1" step="1"
                               value="{{ old('rec_max_occurrences', $expense->recurringCharge?->max_occurrences) }}" placeholder="Unlimited"
                               style="width:100%;padding:10px 12px;font-size:14px;border:1px solid #e5e4df;border-radius:8px;background:#faf9f5;color:#141413;outline:none;box-sizing:border-box"
                               onfocus="this.style.borderColor='#D97757'" onblur="this.style.borderColor='#e5e4df'">
                        @if($expense->recurringCharge?->max_occurrences)
                            <p style="font-size:11px;color:#8c8c8a;margin:4px 0 0">{{ $expense->recurringCharge->occurrences_count }} of {{ $expense->recurringCharge->max_occurrences }} generated.</p>
                        @else
                            <p style="font-size:11px;color:#8c8c8a;margin:4px 0 0">Stop after N drafts (optional).</p>
                        @endif
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
                Save Changes
            </button>
        </div>
    </form>
</div>
</x-layouts.app>
