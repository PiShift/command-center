<x-layouts.app :title="isset($customer) ? 'Edit ' . $customer->name : 'New Customer'">

<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6 text-[13px]">
        <a href="{{ route('customers.index') }}" class="text-muted hover:text-ink">Customers</a>
        <span class="text-muted">/</span>
        <span class="text-ink">{{ isset($customer) ? 'Edit' : 'New customer' }}</span>
    </div>

    <div class="bg-white border border-line rounded-xl p-6">
        <h1 class="text-[15px] font-semibold text-ink mb-6">{{ isset($customer) ? 'Edit customer' : 'New Customer' }}</h1>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-[13px] text-red-700">
                <ul class="list-disc pl-4 space-y-1">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST"
              action="{{ isset($customer) ? route('customers.update', $customer) : route('customers.store') }}"
              class="grid grid-cols-2 gap-4">
            @csrf
            @if(isset($customer)) @method('PUT') @endif

            <div class="col-span-2">
                <label class="block text-[12px] font-medium text-dim mb-1">Name *</label>
                <input type="text" name="name" value="{{ old('name', $customer->name ?? '') }}" required
                       class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
            </div>
            <div>
                <label class="block text-[12px] font-medium text-dim mb-1">Company</label>
                <input type="text" name="company" value="{{ old('company', $customer->company ?? '') }}"
                       class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
            </div>
            <div>
                <label class="block text-[12px] font-medium text-dim mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $customer->email ?? '') }}"
                       class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
            </div>
            <div>
                <label class="block text-[12px] font-medium text-dim mb-1">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $customer->phone ?? '') }}"
                       class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
            </div>
            <div>
                <label class="block text-[12px] font-medium text-dim mb-1">Website</label>
                <input type="url" name="website" value="{{ old('website', $customer->website ?? '') }}"
                       class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
            </div>
            <div>
                <label class="block text-[12px] font-medium text-dim mb-1">Status *</label>
                <select name="status" required class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
                    @foreach(['active' => 'Active', 'prospect' => 'Prospect', 'churned' => 'Churned'] as $v => $l)
                        <option value="{{ $v }}" {{ old('status', $customer->status ?? 'prospect') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[12px] font-medium text-dim mb-1">Industry</label>
                <input type="text" name="industry" value="{{ old('industry', $customer->industry ?? '') }}"
                       class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">
            </div>
            <div class="col-span-2">
                <label class="block text-[12px] font-medium text-dim mb-1">Notes</label>
                <textarea name="notes" rows="4"
                          class="w-full px-3 py-2 text-[13px] border border-line rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-accent">{{ old('notes', $customer->notes ?? '') }}</textarea>
            </div>

            <div class="col-span-2 flex items-center justify-end gap-3 pt-4 border-t border-hairline">
                <a href="{{ route('customers.index') }}" class="px-4 py-2 text-[13px] text-dim hover:text-ink">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-accent hover:bg-accent-hover text-white text-[13px] font-semibold rounded-lg transition-colors">
                    {{ isset($customer) ? 'Save changes' : 'Create customer' }}
                </button>
            </div>
        </form>
    </div>
</div>

</x-layouts.app>
