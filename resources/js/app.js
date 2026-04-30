import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    /* ─── Theme store ──────────────────────────────────────── */
    Alpine.store('theme', {
        value: document.documentElement.dataset.theme || 'light',
        toggle() {
            this.value = this.value === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.theme = this.value;
            localStorage.setItem('gfonts.theme', this.value);
        },
    });

    /* ─── Toast store ──────────────────────────────────────── */
    Alpine.store('toast', {
        items: [],
        push(message, opts = {}) {
            const id = Date.now() + Math.random();
            const tone = opts.tone || 'success';      // 'success' | 'error' | 'info'
            const duration = opts.duration ?? 3000;
            this.items.push({ id, message, tone });
            if (duration > 0) {
                setTimeout(() => this.dismiss(id), duration);
            }
        },
        success(msg, opts) { this.push(msg, { ...opts, tone: 'success' }); },
        error(msg, opts)   { this.push(msg, { ...opts, tone: 'error' }); },
        info(msg, opts)    { this.push(msg, { ...opts, tone: 'info' }); },
        dismiss(id) {
            this.items = this.items.filter(i => i.id !== id);
        },
    });

    /* ─── Keyboard store (registry of bindings + help modal) ── */
    Alpine.store('keys', {
        helpOpen: false,
        toggleHelp() { this.helpOpen = !this.helpOpen; },
    });
});

/* ─── Global keyboard shortcuts ───────────────────────────── */
document.addEventListener('keydown', (e) => {
    const tag = (e.target?.tagName || '').toLowerCase();
    const isTyping = tag === 'input' || tag === 'textarea' || e.target?.isContentEditable;

    // `?` → toggle help (work even when typing? no — only when not typing)
    if (!isTyping && e.key === '?' && !e.ctrlKey && !e.metaKey) {
        e.preventDefault();
        Alpine.store('keys').toggleHelp();
        return;
    }

    // `/` → focus search (when not typing)
    if (!isTyping && e.key === '/' && !e.ctrlKey && !e.metaKey) {
        const search = document.querySelector('header input[type="search"]');
        if (search) {
            e.preventDefault();
            search.focus();
            search.select();
        }
        return;
    }

    // Escape inside an input → blur it (so subsequent shortcuts work)
    if (isTyping && e.key === 'Escape') {
        e.target.blur();
    }
});

Alpine.start();
