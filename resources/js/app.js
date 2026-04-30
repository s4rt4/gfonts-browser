import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.store('theme', {
        value: document.documentElement.dataset.theme || 'light',
        toggle() {
            this.value = this.value === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.theme = this.value;
            localStorage.setItem('gfonts.theme', this.value);
        },
    });
});

Alpine.start();
