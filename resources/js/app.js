import './bootstrap';

window.searchableSelect = function searchableSelect(config) {
    return {
        componentId: config.componentId,
        open: false,
        query: '',
        highlightedIndex: 0,
        options: Array.isArray(config.options) ? config.options : [],
        selectedValues: Array.isArray(config.selected) ? [...config.selected] : [],
        multiple: !!config.multiple,
        placeholder: config.placeholder || 'Select...',
        searchable: !!config.searchable,
        livewireModel: config.livewireModel || null,
        syncTimeoutId: null,

        init() {
            if (!this.multiple && this.selectedValues.length > 1) {
                this.selectedValues = this.selectedValues.slice(0, 1);
            }

            this.$watch('selectedValues', () => {
                this.queueLivewireSync();
                this.dispatchChange();
            });
        },

        queueLivewireSync() {
            if (!this.livewireModel || !this.$wire) {
                return;
            }

            if (this.syncTimeoutId !== null) {
                clearTimeout(this.syncTimeoutId);
            }

            this.syncTimeoutId = setTimeout(() => {
                this.syncLivewire();
                this.syncTimeoutId = null;
            }, 120);
        },

        syncLivewire() {
            if (!this.livewireModel || !this.$wire) {
                return;
            }

            const castValue = (value) => {
                if (typeof value !== 'string') {
                    return value;
                }

                if (/^\d+$/.test(value)) {
                    return Number.parseInt(value, 10);
                }

                return value;
            };

            const payload = this.multiple
                ? this.selectedValues.map((value) => castValue(value))
                : (this.selectedValues[0] ? castValue(this.selectedValues[0]) : null);

            this.$wire.set(this.livewireModel, payload);
        },

        dispatchChange() {
            this.$dispatch('change', {
                id: this.componentId,
                multiple: this.multiple,
                value: this.multiple ? [...this.selectedValues] : (this.selectedValues[0] ?? null),
            });
        },

        toggle() {
            this.open ? this.close() : this.openPanel();
        },

        openAndHighlightFirst() {
            this.openPanel();
            this.highlightedIndex = 0;
        },

        openPanel() {
            this.open = true;
            this.query = '';
            this.highlightedIndex = 0;

            this.$nextTick(() => {
                if (this.searchable && this.$refs.search) {
                    this.$refs.search.focus();
                }
            });
        },

        close() {
            this.open = false;
            this.query = '';
        },

        filteredOptions() {
            const q = this.query.toLowerCase().trim();
            if (!q) {
                return this.options;
            }

            return this.options.filter((option) => {
                const label = String(option.label || '').toLowerCase();
                const meta = String(option.meta || '').toLowerCase();
                return label.includes(q) || meta.includes(q);
            });
        },

        move(step) {
            const items = this.filteredOptions();
            if (items.length === 0) {
                this.highlightedIndex = 0;
                return;
            }

            const next = this.highlightedIndex + step;
            if (next < 0) {
                this.highlightedIndex = items.length - 1;
                return;
            }
            if (next >= items.length) {
                this.highlightedIndex = 0;
                return;
            }

            this.highlightedIndex = next;
        },

        selectHighlighted() {
            const option = this.filteredOptions()[this.highlightedIndex];
            if (!option) {
                return;
            }

            this.select(option.id);
        },

        select(optionId) {
            const id = String(optionId);
            if (this.multiple) {
                if (this.isSelected(id)) {
                    this.selectedValues = this.selectedValues.filter((value) => value !== id);
                } else {
                    this.selectedValues = [...this.selectedValues, id];
                }
                return;
            }

            this.selectedValues = [id];
            this.close();
        },

        isSelected(optionId) {
            return this.selectedValues.includes(String(optionId));
        },

        clear() {
            this.selectedValues = [];
        },

        selectAllFiltered() {
            if (!this.multiple) {
                return;
            }

            const ids = this.filteredOptions().map((option) => String(option.id));
            this.selectedValues = Array.from(new Set([...this.selectedValues, ...ids]));
        },

        selectedBadgeColor() {
            if (this.multiple) {
                return null;
            }

            const selectedId = this.selectedValues[0] ?? null;
            const option = this.options.find((item) => String(item.id) === String(selectedId));

            return option?.color || null;
        },

        triggerLabel() {
            if (this.selectedValues.length === 0) {
                return this.placeholder;
            }

            if (this.multiple) {
                if (this.selectedValues.length === 1) {
                    const option = this.options.find((item) => String(item.id) === this.selectedValues[0]);
                    return option?.label || this.placeholder;
                }

                return `${this.selectedValues.length} selected`;
            }

            const option = this.options.find((item) => String(item.id) === this.selectedValues[0]);
            return option?.label || this.placeholder;
        },
    };
};

// Sidebar store — registered via alpine:init so Livewire's bundled Alpine
// picks it up (importing a second Alpine instance causes $store to be undefined).
document.addEventListener('alpine:init', () => {
    Alpine.store('sidebar', {
        isOpen: localStorage.getItem('sidebar_open') !== null
            ? localStorage.getItem('sidebar_open') !== 'false'
            : window.innerWidth >= 1024,
        toggle() {
            this.isOpen = !this.isOpen;
            localStorage.setItem('sidebar_open', this.isOpen);
        },
        open()  { this.isOpen = true;  localStorage.setItem('sidebar_open', true); },
        close() { this.isOpen = false; localStorage.setItem('sidebar_open', false); },
    });

});
