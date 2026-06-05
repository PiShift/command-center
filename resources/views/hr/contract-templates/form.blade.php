@php $isEdit = $template->exists; @endphp
<x-layouts.app :title="$isEdit ? 'Edit: '.$template->name : 'New Template'">

    <div class="flex items-center justify-between mb-4">
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('contract-templates.index') }}"
               style="color:#8c8c8a;text-decoration:none;font-size:13px"
               onmouseover="this.style.color='#141413'" onmouseout="this.style.color='#8c8c8a'">← Contract Templates</a>
            <h1 style="font-size:24px;font-weight:600;color:#141413">
                {{ $isEdit ? 'Edit: '.$template->name : 'New Template' }}
            </h1>
        </div>
        <div class="flex items-center gap-2">
            @if($isEdit)
            <form method="POST" action="{{ route('contract-templates.preview', $template) }}" target="_blank" id="preview-form-top">
                @csrf
            </form>
            <button type="submit" form="preview-form-top"
                    class="font-medium rounded-lg px-4 py-2 transition-colors cursor-pointer"
                    style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;font-size:13px">
                Preview PDF
            </button>
            @endif
            <a href="{{ route('contract-templates.index') }}"
               class="font-medium rounded-lg px-4 py-2 transition-colors"
               style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;font-size:13px">
                Cancel
            </a>
        </div>
    </div>

        {{-- Content --}}
        <div style="height:calc(100vh - 180px)">
            <form method="POST"
                  action="{{ $isEdit ? route('contract-templates.update', $template) : route('contract-templates.store') }}"
                  class="flex h-full">
                @csrf
                @if($isEdit) @method('PUT') @endif

                @if($errors->any())
                <div class="fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg" style="background:#fff8f8;border:1px solid #ffd0d0;color:#b94040;font-size:13px;max-width:320px">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
                @endif

                {{-- Left — TipTap editor (65%) --}}
                <div class="flex flex-col" style="width:65%;border-right:1px solid #e5e4df">

                    {{-- Toolbar row — onmousedown.prevent keeps editor focus while clicking any button --}}
                    <div class="flex items-center gap-1 px-3 py-2 border-b border-hairline bg-canvas flex-wrap" id="editor-toolbar"
                         onmousedown="event.preventDefault()">

                        {{-- Text format buttons --}}
                        <button type="button" data-action="bold"        title="Bold"          class="tt-btn"><b>B</b></button>
                        <button type="button" data-action="italic"      title="Italic"        class="tt-btn"><i>I</i></button>
                        <div class="tt-sep"></div>
                        <button type="button" data-action="h1"          title="Heading 1"     class="tt-btn tt-lbl">H1</button>
                        <button type="button" data-action="h2"          title="Heading 2"     class="tt-btn tt-lbl">H2</button>
                        <button type="button" data-action="h3"          title="Heading 3"     class="tt-btn tt-lbl">H3</button>
                        <button type="button" data-action="paragraph"   title="Paragraph"     class="tt-btn tt-lbl">¶</button>
                        <div class="tt-sep"></div>
                        <button type="button" data-action="bulletList"  title="Bullet list"   class="tt-btn">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="9" y1="6" x2="20" y2="6"/><line x1="9" y1="12" x2="20" y2="12"/><line x1="9" y1="18" x2="20" y2="18"/><circle cx="4" cy="6" r="1.5" fill="currentColor"/><circle cx="4" cy="12" r="1.5" fill="currentColor"/><circle cx="4" cy="18" r="1.5" fill="currentColor"/></svg>
                        </button>
                        <button type="button" data-action="orderedList" title="Ordered list"  class="tt-btn">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><path d="M4 6h1v4" stroke-linecap="round"/><path d="M4 10h2" stroke-linecap="round"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <button type="button" data-action="blockquote"  title="Blockquote"    class="tt-btn">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z"/></svg>
                        </button>
                        <button type="button" data-action="hr"          title="Horizontal rule" class="tt-btn tt-lbl">—</button>
                        <div class="tt-sep"></div>
                        <button type="button" data-action="undo"        title="Undo"          class="tt-btn">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg>
                        </button>
                        <button type="button" data-action="redo"        title="Redo"          class="tt-btn">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3L21 13"/></svg>
                        </button>

                        {{-- Spacer --}}
                        <div class="flex-1"></div>

                        {{-- Insert Placeholder dropdown (Alpine) --}}
                        <div x-data="{
                                open: false,
                                search: '',
                                placeholders: @js($placeholders),
                                filtered() {
                                    const q = this.search.toLowerCase();
                                    if (!q) return this.placeholders;
                                    const out = {};
                                    for (const [group, items] of Object.entries(this.placeholders)) {
                                        const filtered = Object.fromEntries(
                                            Object.entries(items).filter(([k,v]) =>
                                                k.toLowerCase().includes(q) || v.toLowerCase().includes(q)
                                            )
                                        );
                                        if (Object.keys(filtered).length) out[group] = filtered;
                                    }
                                    return out;
                                },
                                insert(key) {
                                    if (window.__tiptap) {
                                        window.__tiptap.chain().focus().insertContent({
                                            type: 'text',
                                            marks: [{ type: 'code' }],
                                            text: '{{' + key + '}}'
                                        }).run();
                                    }
                                    this.open = false;
                                    this.search = '';
                                }
                             }"
                             @keydown.escape.window="open = false"
                             class="relative">

                            <button type="button"
                                    @mousedown.prevent
                                    @click="open = !open; $nextTick(() => $refs.phSearch?.focus())"
                                    class="flex items-center gap-1.5 font-medium rounded-lg px-3 py-1.5 transition-colors cursor-pointer"
                                    style="background:#fff;border:1px solid #e5e4df;color:#5c5c5a;font-size:12px">
                                <span style="font-size:14px;font-family:monospace;color:#D97757">{}</span>
                                Insert placeholder
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                            </button>

                            {{-- Dropdown panel --}}
                            {{-- Outer: positions + shadow. Inner wrapper: clips to rounded corners with overflow-hidden + sets max-height for scroll --}}
                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                                 @click.away="open = false"
                                 onmousedown="event.preventDefault()"
                                 x-cloak
                                 class="absolute right-0 top-full mt-1 rounded-xl z-50"
                                 style="width:320px;border:1px solid #e5e4df;box-shadow:0 4px 20px rgba(20,20,19,0.10)">

                                {{-- Inner flex-column wrapper that actually clips + constrains height --}}
                                <div class="flex flex-col rounded-xl overflow-hidden bg-card" style="max-height:420px">

                                    {{-- Search (never scrolls away) --}}
                                    <div class="flex-shrink-0 px-3 py-2.5" style="border-bottom:1px solid #eeeee9">
                                        <input type="text" x-model="search" x-ref="phSearch"
                                               placeholder="Search placeholders..."
                                               class="w-full rounded-lg px-3 py-1.5 text-ink bg-surface border border-line focus:outline-none focus:border-accent transition-colors"
                                               style="font-size:13px">
                                    </div>

                                    {{-- Scrollable list --}}
                                    <div class="flex-1 overflow-y-auto">
                                        @php
                                            $groupMeta = [
                                                'company'  => ['icon' => '🏢', 'label' => 'Company',  'color' => '#c4684a', 'bg' => '#fdf3ee'],
                                                'employee' => ['icon' => '👤', 'label' => 'Employee', 'color' => '#3a6fba', 'bg' => '#eef3fb'],
                                                'contract' => ['icon' => '📄', 'label' => 'Contract', 'color' => '#2e7d55', 'bg' => '#edf7f2'],
                                            ];
                                        @endphp
                                        @foreach($placeholders as $group => $items)
                                        @php $meta = $groupMeta[$group] ?? ['icon' => '•', 'label' => ucfirst($group), 'color' => '#5c5c5a', 'bg' => '#F5F4EF']; @endphp
                                        <div x-show="Object.keys(filtered()['{{ $group }}'] ?? {}).length > 0">
                                            <div class="px-3 py-1.5 sticky top-0 flex items-center gap-1.5" style="background:{{ $meta['bg'] }};border-bottom:1px solid #eeeee9">
                                                <span style="font-size:13px">{{ $meta['icon'] }}</span>
                                                <span class="font-bold uppercase" style="font-size:10px;letter-spacing:0.06em;color:{{ $meta['color'] }}">{{ $meta['label'] }}</span>
                                            </div>
                                            @foreach($items as $key => $label)
                                            <div x-show="filtered()['{{ $group }}'] && filtered()['{{ $group }}']['{{ $key }}'] !== undefined"
                                                 @mousedown.prevent
                                                 @click="insert('{{ $key }}')"
                                                 class="flex items-center justify-between px-3 py-2 cursor-pointer transition-colors hover:bg-canvas"
                                                 style="border-bottom:1px solid #eeeee9">
                                                <code class="font-mono font-semibold flex-shrink-0" style="font-size:11.5px;color:{{ $meta['color'] }}">{{ $key }}</code>
                                                <span class="text-muted text-right ml-3 truncate" style="font-size:12px">{{ $label }}</span>
                                            </div>
                                            @endforeach
                                        </div>
                                        @endforeach
                                        <div x-show="!Object.keys(filtered()).length" class="px-4 py-6 text-center text-muted" style="font-size:13px">
                                            No placeholders match "<span x-text="search"></span>"
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Hidden input for form submission --}}
                    <input type="hidden" name="content" id="content-hidden">

                    {{-- TipTap editor surface --}}
                    <div class="flex-1 overflow-y-auto p-4">
                        <div id="editor"
                             class="bg-card border border-line rounded-lg focus-within:border-accent transition-colors"
                             style="min-height:500px;cursor:text">
                        </div>
                    </div>
                </div>

                {{-- Right — settings sidebar (35%) --}}
                <div class="flex flex-col" style="width:35%">
                    <div class="px-5 py-3 border-b border-hairline" style="background:#faf9f5">
                        <span class="font-bold uppercase text-muted" style="font-size:10px;letter-spacing:0.06em">Template Settings</span>
                    </div>
                    <div class="flex-1 overflow-y-auto px-5 py-5 space-y-5">

                        {{-- Name --}}
                        <div>
                            <label class="block font-bold uppercase text-muted mb-1.5" style="font-size:11px;letter-spacing:0.05em">Template Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $template->name) }}" required placeholder="e.g. CDI Standard"
                                   class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent focus:bg-card transition-colors"
                                   style="font-size:14px">
                        </div>

                        {{-- Employment type --}}
                        <div>
                            <label class="block font-bold uppercase text-muted mb-1.5" style="font-size:11px;letter-spacing:0.05em">Contract Type <span class="text-red-500">*</span></label>
                            <select name="employment_type" required
                                    class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent focus:bg-card transition-colors"
                                    style="font-size:14px">
                                @foreach(['CDI' => 'CDI', 'CDD' => 'CDD', 'freelance' => 'Freelance', 'internship' => 'Stage', 'all' => 'All Types'] as $val => $lbl)
                                <option value="{{ $val }}" {{ old('employment_type', $template->employment_type) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Language --}}
                        <div>
                            <label class="block font-bold uppercase text-muted mb-1.5" style="font-size:11px;letter-spacing:0.05em">Language <span class="text-red-500">*</span></label>
                            <select name="language" required
                                    class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent focus:bg-card transition-colors"
                                    style="font-size:14px">
                                <option value="fr" {{ old('language', $template->language ?? 'fr') === 'fr' ? 'selected' : '' }}>Français</option>
                                <option value="ar" {{ old('language', $template->language) === 'ar' ? 'selected' : '' }}>العربية</option>
                                <option value="en" {{ old('language', $template->language) === 'en' ? 'selected' : '' }}>English</option>
                            </select>
                        </div>

                        {{-- Version --}}
                        <div>
                            <label class="block font-bold uppercase text-muted mb-1.5" style="font-size:11px;letter-spacing:0.05em">Version</label>
                            <input type="text" name="version" value="{{ old('version', $template->version ?? 'v1.0') }}"
                                   class="w-full text-ink bg-surface border border-line rounded-lg px-3 py-2 focus:outline-none focus:border-accent focus:bg-card transition-colors"
                                   style="font-size:14px" placeholder="v1.0">
                        </div>

                        {{-- Is Default --}}
                        <div class="flex items-start justify-between gap-4 py-3" style="border-top:1px solid #eeeee9">
                            <div>
                                <p class="font-medium text-ink" style="font-size:13.5px">Default Template</p>
                                <p class="text-muted mt-0.5" style="font-size:12px">Used automatically for this contract type when no template is selected. Only one default per type is allowed.</p>
                            </div>
                            <label class="relative flex-shrink-0 cursor-pointer" style="width:44px;height:24px">
                                <input type="checkbox" name="is_default" value="1" class="sr-only"
                                       {{ old('is_default', $template->is_default) ? 'checked' : '' }}>
                                <div class="toggle-track absolute inset-0 rounded-full transition-colors" style="background:#e5e4df"></div>
                                <div class="toggle-thumb absolute top-1 left-1 w-4 h-4 rounded-full bg-white shadow transition-transform"></div>
                            </label>
                        </div>

                        {{-- Is Active --}}
                        <div class="flex items-start justify-between gap-4 py-3" style="border-top:1px solid #eeeee9">
                            <div>
                                <p class="font-medium text-ink" style="font-size:13.5px">Active</p>
                                <p class="text-muted mt-0.5" style="font-size:12px">Inactive templates are hidden from the contract creation form.</p>
                            </div>
                            <label class="relative flex-shrink-0 cursor-pointer" style="width:44px;height:24px">
                                <input type="checkbox" name="is_active" value="1" class="sr-only"
                                       {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }}>
                                <div class="toggle-track absolute inset-0 rounded-full transition-colors" style="background:#e5e4df"></div>
                                <div class="toggle-thumb absolute top-1 left-1 w-4 h-4 rounded-full bg-white shadow transition-transform"></div>
                            </label>
                        </div>

                    </div>

                    {{-- Save button pinned at bottom --}}
                    <div class="px-5 py-4" style="border-top:1px solid #e5e4df;background:#faf9f5">
                        <button type="submit"
                                class="w-full font-medium rounded-lg px-4 py-2.5 text-white transition-colors cursor-pointer bg-accent hover:bg-accent-hover"
                                style="font-size:14px">
                            {{ $isEdit ? 'Save Changes' : 'Create Template' }}
                        </button>
                    </div>
                </div>

            </form>
        </div>


    <style>
        /* Toggle switch */
        input[type="checkbox"].sr-only:checked ~ .toggle-track { background: #D97757; }
        input[type="checkbox"].sr-only:checked ~ .toggle-thumb { transform: translateX(20px); }

        /* Toolbar button base */
        .tt-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 28px; height: 28px; border-radius: 6px; cursor: pointer;
            color: #5c5c5a; background: transparent; border: none;
            font-size: 13px; font-family: inherit; font-weight: 500;
            transition: background 120ms ease, color 120ms ease;
            flex-shrink: 0;
        }
        .tt-btn:hover { background: #eeeee9; color: #141413; }
        .tt-btn.is-active { background: #fdf3ee; color: #D97757; }
        .tt-btn.tt-lbl { font-family: monospace; font-size: 12px; font-weight: 700; }
        .tt-sep { width: 1px; height: 18px; background: #eeeee9; margin: 0 2px; flex-shrink: 0; }

        /* TipTap editor prose */
        #editor .ProseMirror {
            outline: none;
            padding: 16px;
            min-height: 500px;
            font-size: 13.5px;
            line-height: 1.6;
            color: #141413;
            font-family: inherit;
        }
        #editor .ProseMirror > * + * { margin-top: 0.75em; }
        #editor .ProseMirror h1 { font-size: 20px; font-weight: 700; line-height: 1.25; margin: 1em 0 0.4em; }
        #editor .ProseMirror h2 { font-size: 16px; font-weight: 700; line-height: 1.25; margin: 0.9em 0 0.4em; }
        #editor .ProseMirror h3 { font-size: 14px; font-weight: 700; line-height: 1.25; margin: 0.8em 0 0.3em; }
        #editor .ProseMirror p { margin: 0; }
        #editor .ProseMirror ul { padding-left: 1.4em; list-style: disc; }
        #editor .ProseMirror ol { padding-left: 1.4em; list-style: decimal; }
        #editor .ProseMirror li { margin: 0.2em 0; }
        #editor .ProseMirror blockquote {
            border-left: 3px solid #e5e4df; margin: 0; padding-left: 1em;
            color: #5c5c5a; font-style: italic;
        }
        #editor .ProseMirror hr {
            border: none; border-top: 1px solid #e5e4df; margin: 1.2em 0;
        }
        #editor .ProseMirror strong { font-weight: 700; }
        #editor .ProseMirror em { font-style: italic; }
        #editor .ProseMirror code {
            background-color: #fef3c7;
            color: #92400e;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 0.85em;
            padding: 1px 5px;
            border-radius: 4px;
            border: 1px solid #fde68a;
            font-weight: normal;
        }
        #editor .ProseMirror p.is-editor-empty:first-child::before {
            content: attr(data-placeholder);
            color: #8c8c8a; font-style: italic; pointer-events: none;
            float: left; height: 0;
        }
    </style>

@push('scripts')
<script type="module">
import { Editor } from 'https://esm.sh/@tiptap/core@2';
import StarterKit from 'https://esm.sh/@tiptap/starter-kit@2';

const existingContent = @json(old('content', $template->content ?? ''));

const editor = new Editor({
    element: document.getElementById('editor'),
    extensions: [
        StarterKit.configure({
            heading: { levels: [1, 2, 3] },
        }),
    ],
    content: existingContent || '',
    editorProps: {
        attributes: { spellcheck: 'false' },
    },
    onUpdate() {
        document.getElementById('content-hidden').value = editor.getHTML();
    },
});

// Seed the hidden input immediately
document.getElementById('content-hidden').value = editor.getHTML();

// Expose globally so Alpine placeholder insertion can reach it
window.__tiptap = editor;

// Toolbar button wiring
document.getElementById('editor-toolbar').addEventListener('click', e => {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    const action = btn.dataset.action;
    const chain = editor.chain().focus();
    switch (action) {
        case 'bold':        chain.toggleBold().run(); break;
        case 'italic':      chain.toggleItalic().run(); break;
        case 'h1':          chain.toggleHeading({ level: 1 }).run(); break;
        case 'h2':          chain.toggleHeading({ level: 2 }).run(); break;
        case 'h3':          chain.toggleHeading({ level: 3 }).run(); break;
        case 'paragraph':   chain.setParagraph().run(); break;
        case 'bulletList':  chain.toggleBulletList().run(); break;
        case 'orderedList': chain.toggleOrderedList().run(); break;
        case 'blockquote':  chain.toggleBlockquote().run(); break;
        case 'hr':          chain.setHorizontalRule().run(); break;
        case 'undo':        chain.undo().run(); break;
        case 'redo':        chain.redo().run(); break;
    }
    updateToolbar();
});

// Keep toolbar active states in sync
function updateToolbar() {
    const map = {
        bold:        () => editor.isActive('bold'),
        italic:      () => editor.isActive('italic'),
        h1:          () => editor.isActive('heading', { level: 1 }),
        h2:          () => editor.isActive('heading', { level: 2 }),
        h3:          () => editor.isActive('heading', { level: 3 }),
        paragraph:   () => editor.isActive('paragraph'),
        bulletList:  () => editor.isActive('bulletList'),
        orderedList: () => editor.isActive('orderedList'),
        blockquote:  () => editor.isActive('blockquote'),
    };
    for (const [action, check] of Object.entries(map)) {
        const btn = document.querySelector(`[data-action="${action}"]`);
        if (btn) btn.classList.toggle('is-active', check());
    }
}
editor.on('selectionUpdate', updateToolbar);
editor.on('transaction', updateToolbar);

// Validate — require non-empty content on form submit
document.querySelector('form').addEventListener('submit', e => {
    const html = editor.getHTML();
    document.getElementById('content-hidden').value = html;
    if (editor.isEmpty) {
        e.preventDefault();
        document.getElementById('editor').style.borderColor = '#e05555';
        document.getElementById('editor').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
});
</script>
@endpush
</x-layouts.app>
