@if (session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         x-transition:leave="transition-opacity duration-500" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="mb-4 px-4 py-3 bg-success-light border border-[#3d9970]/20 rounded-lg text-[13px] text-success-text flex items-center justify-between gap-3">
        <span>{{ session('success') }}</span>
        <button type="button" @click="show = false" class="text-[#3d9970] hover:opacity-60 flex-shrink-0 cursor-pointer">&times;</button>
    </div>
@endif
@if (session('error'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         x-transition:leave="transition-opacity duration-500" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-[13px] text-red-700 flex items-center justify-between gap-3">
        <span>{{ session('error') }}</span>
        <button type="button" @click="show = false" class="text-red-400 hover:opacity-60 flex-shrink-0 cursor-pointer">&times;</button>
    </div>
@endif
