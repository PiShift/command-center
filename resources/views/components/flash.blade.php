@if (session('success'))
    <div class="mb-4 px-4 py-3 bg-success-light border border-[#3d9970]/20 rounded-lg text-[13px] text-success-text">
        {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-[13px] text-red-700">
        {{ session('error') }}
    </div>
@endif
