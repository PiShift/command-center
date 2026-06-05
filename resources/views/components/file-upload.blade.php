@props([
    'name',
    'accept'               => '.pdf,.jpg,.jpeg,.png',
    'maxSizeMb'            => 10,
    'label'                => 'Drop file here or click to browse',
    'multiple'             => false,
    'currentFile'          => null,
])

<div
    x-data="{
        dragging: false,
        files: [],
        errors: [],
        currentFile: @js($currentFile),

        handleDrop(e) {
            this.dragging = false;
            const dt = new DataTransfer();
            Array.from(e.dataTransfer.files).forEach(f => dt.items.add(f));
            this.$refs.fileInput.files = dt.files;
            this.processFiles(e.dataTransfer.files);
        },

        handleSelect(e) {
            this.processFiles(e.target.files);
        },

        processFiles(fileList) {
            this.errors = [];
            this.files = [];
            const maxBytes = {{ $maxSizeMb }} * 1024 * 1024;
            Array.from(fileList).forEach(file => {
                if (file.size > maxBytes) {
                    this.errors.push(file.name + ' exceeds {{ $maxSizeMb }}MB limit');
                    return;
                }
                this.files.push({
                    name: file.name,
                    size: this.formatSize(file.size),
                    type: file.type,
                    preview: file.type.startsWith('image/') ? URL.createObjectURL(file) : null
                });
            });
        },

        formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1048576).toFixed(1) + ' MB';
        },

        removeFile(index) {
            this.files.splice(index, 1);
            this.$refs.fileInput.value = '';
        }
    }"
    class="w-full">

    {{-- Drop zone --}}
    <div
        @dragover.prevent="dragging = true"
        @dragleave.prevent="dragging = false"
        @drop.prevent="handleDrop($event)"
        @click="$refs.fileInput.click()"
        :class="dragging
            ? 'border-[#3b82f6] bg-[#eff6ff] scale-[1.01]'
            : 'border-line bg-canvas hover:border-muted hover:bg-hairline'"
        class="border-2 border-dashed rounded-xl p-5 text-center cursor-pointer transition-all duration-200 select-none">

        {{-- Empty state --}}
        <div x-show="!files.length && !currentFile" class="space-y-2">
            <div class="mx-auto w-9 h-9 text-muted flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
            </div>
            <p class="text-dim" style="font-size:13px">
                {{ $label }} or <span class="text-accent font-medium underline">browse</span>
            </p>
            <p class="text-muted" style="font-size:11px">
                {{ strtoupper(str_replace(['.', ', .', ',.'], ['', ' ', ' '], $accept)) }} — max {{ $maxSizeMb }} MB
            </p>
        </div>

        {{-- Current file (edit mode) --}}
        <div x-show="!files.length && currentFile" class="flex items-center justify-between px-2">
            <div class="flex items-center gap-2 text-dim" style="font-size:13px">
                <svg class="w-5 h-5 text-muted flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                <span x-text="currentFile" class="truncate" style="max-width:240px"></span>
            </div>
            <span class="text-muted" style="font-size:11px">Click to replace</span>
        </div>

        {{-- Selected file previews --}}
        <template x-for="(file, index) in files" :key="index">
            <div class="flex items-center gap-3 bg-card rounded-lg p-3 border border-hairline text-left mt-2"
                 @click.stop>
                {{-- Image preview --}}
                <template x-if="file.preview">
                    <img :src="file.preview" class="w-10 h-10 rounded object-cover flex-shrink-0">
                </template>
                {{-- File icon for non-images --}}
                <template x-if="!file.preview">
                    <div class="w-10 h-10 bg-surface rounded flex items-center justify-center text-muted flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                    </div>
                </template>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-ink truncate" style="font-size:13px" x-text="file.name"></p>
                    <p class="text-muted" style="font-size:11px" x-text="file.size"></p>
                </div>
                <button type="button" @click.stop="removeFile(index)"
                        class="text-muted hover:text-red-500 transition-colors cursor-pointer flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </template>
    </div>

    {{-- Errors --}}
    <template x-for="error in errors" :key="error">
        <p class="mt-1" style="font-size:12px;color:#b94040" x-text="error"></p>
    </template>

    {{-- Hidden file input --}}
    <input
        x-ref="fileInput"
        type="file"
        name="{{ $name }}"
        accept="{{ $accept }}"
        @if($multiple) multiple @endif
        @change="handleSelect($event)"
        class="hidden">
</div>
