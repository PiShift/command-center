<div
    x-data="{
        open: false,
        saving: false,
        form: {
            name: '',
            email: '',
            phone: '',
            company: ''
        },
        errors: {},
        resetForm() {
            this.saving = false;
            this.form = { name: '', email: '', phone: '', company: '' };
            this.errors = {};
        },
        closeModal() {
            this.open = false;
            this.resetForm();
        },
        async submit() {
            this.saving = true;
            this.errors = {};

            try {
                const response = await fetch('/customers', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || ''
                    },
                    body: JSON.stringify({
                        ...this.form,
                        status: 'active'
                    })
                });

                if (response.ok) {
                    this.closeModal();
                    window.dispatchEvent(new CustomEvent('customer-toast', { detail: 'Customer created successfully' }));
                } else if (response.status === 422) {
                    const data = await response.json();
                    this.errors = data.errors || {};
                } else {
                    this.errors = { name: ['Unable to create customer right now.'] };
                }
            } catch (error) {
                this.errors = { name: ['Network error. Please try again.'] };
            }

            this.saving = false;
        }
    }"
    x-show="open"
    style="display: none;"
    x-on:open-quick-customer-modal.window="open = true; resetForm(); $nextTick(() => $refs.nameInput?.focus())"
    x-on:keydown.escape.window.prevent="if (open) closeModal()"
    x-transition.opacity.duration.180ms
    class="fixed inset-0 z-[70] flex items-center justify-center"
>
        <div class="absolute inset-0 bg-black/60" x-on:click="closeModal()"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6" x-on:click.stop>
            <h2 class="text-[16px] font-bold text-ink mb-4">New Customer</h2>

            <form x-on:submit.prevent="submit()" class="space-y-4">
                <div>
                    <label class="block mb-1.5 text-[11px] font-bold uppercase tracking-[0.05em] text-muted">Name</label>
                    <input
                        x-ref="nameInput"
                        x-model="form.name"
                        type="text"
                        required
                        class="w-full bg-surface border border-line rounded-lg px-3 py-2.5 text-[14px] text-ink placeholder:text-muted placeholder:italic focus:bg-card focus:border-accent focus:outline-none transition-colors"
                        placeholder="Customer name"
                    >
                    <template x-if="errors.name">
                        <p class="mt-1 text-[12px] text-danger" x-text="errors.name[0]"></p>
                    </template>
                </div>

                <div>
                    <label class="block mb-1.5 text-[11px] font-bold uppercase tracking-[0.05em] text-muted">Email</label>
                    <input
                        x-model="form.email"
                        type="email"
                        class="w-full bg-surface border border-line rounded-lg px-3 py-2.5 text-[14px] text-ink placeholder:text-muted placeholder:italic focus:bg-card focus:border-accent focus:outline-none transition-colors"
                        placeholder="Optional"
                    >
                    <template x-if="errors.email">
                        <p class="mt-1 text-[12px] text-danger" x-text="errors.email[0]"></p>
                    </template>
                </div>

                <div>
                    <label class="block mb-1.5 text-[11px] font-bold uppercase tracking-[0.05em] text-muted">Phone</label>
                    <input
                        x-model="form.phone"
                        type="text"
                        class="w-full bg-surface border border-line rounded-lg px-3 py-2.5 text-[14px] text-ink placeholder:text-muted placeholder:italic focus:bg-card focus:border-accent focus:outline-none transition-colors"
                        placeholder="Optional"
                    >
                    <template x-if="errors.phone">
                        <p class="mt-1 text-[12px] text-danger" x-text="errors.phone[0]"></p>
                    </template>
                </div>

                <div>
                    <label class="block mb-1.5 text-[11px] font-bold uppercase tracking-[0.05em] text-muted">Company</label>
                    <input
                        x-model="form.company"
                        type="text"
                        class="w-full bg-surface border border-line rounded-lg px-3 py-2.5 text-[14px] text-ink placeholder:text-muted placeholder:italic focus:bg-card focus:border-accent focus:outline-none transition-colors"
                        placeholder="Optional"
                    >
                    <template x-if="errors.company">
                        <p class="mt-1 text-[12px] text-danger" x-text="errors.company[0]"></p>
                    </template>
                </div>

                <template x-if="errors.general">
                    <p class="text-[12px] text-danger" x-text="errors.general[0]"></p>
                </template>

                <div class="pt-2 flex items-center justify-end gap-2.5">
                    <button
                        type="button"
                        x-on:click="closeModal()"
                        class="px-4 py-2 text-[13px] font-medium text-ink bg-surface border border-line rounded-lg hover:bg-hairline hover:border-muted transition-colors cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        x-bind:disabled="saving"
                        x-bind:class="saving ? 'opacity-70 cursor-not-allowed' : ''"
                        class="px-4 py-2 text-[13px] font-medium text-white bg-accent hover:bg-accent-hover rounded-lg transition-colors cursor-pointer"
                    >
                        <span x-show="!saving">Create Customer</span>
                        <span x-show="saving">Saving...</span>
                    </button>
                </div>
            </form>
        </div>
</div>

{{-- Toast: standalone Alpine component, survives modal close --}}
<div
    x-data="{ open: false, message: '' }"
    x-show="open"
    style="display: none;"
    x-on:customer-toast.window="message = $event.detail; open = true; setTimeout(() => open = false, 2200)"
    x-transition.opacity.duration.150ms
    class="fixed top-5 right-5 z-[80] bg-ink text-white text-[13px] font-medium rounded-lg px-4 py-2.5 shadow-lg pointer-events-none"
    x-text="message"
></div>