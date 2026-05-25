import './bootstrap';

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
