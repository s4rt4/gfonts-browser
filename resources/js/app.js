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

    /* ─── Notes & tags per family ─────────────────────────── */
    Alpine.store('notes', {
        data: JSON.parse(localStorage.getItem('gfonts.notes') || '{}'),

        getNote(family)  { return this.data[family]?.note || ''; },
        getTags(family)  { return this.data[family]?.tags || []; },
        hasAnything(family) {
            const e = this.data[family];
            return !!(e && (e.note?.trim() || e.tags?.length));
        },

        setNote(family, text) {
            this._ensure(family);
            this.data[family].note = text;
            this._cleanup(family);
            this._save();
        },

        addTag(family, tag) {
            tag = String(tag || '').trim().toLowerCase().replace(/\s+/g, '-');
            if (!tag) return;
            this._ensure(family);
            if (!this.data[family].tags.includes(tag)) {
                this.data[family].tags.push(tag);
            }
            this._save();
        },

        removeTag(family, tag) {
            if (!this.data[family]?.tags) return;
            this.data[family].tags = this.data[family].tags.filter(t => t !== tag);
            this._cleanup(family);
            this._save();
        },

        allTags() {
            const tags = new Map();
            for (const entry of Object.values(this.data)) {
                for (const t of (entry.tags || [])) {
                    tags.set(t, (tags.get(t) || 0) + 1);
                }
            }
            return [...tags.entries()].sort((a, b) => b[1] - a[1] || a[0].localeCompare(b[0]));
        },

        familiesWithTag(tag) {
            return Object.entries(this.data)
                .filter(([_, d]) => (d.tags || []).includes(tag))
                .map(([family]) => family);
        },

        _ensure(family) {
            if (!this.data[family]) this.data[family] = { note: '', tags: [] };
            if (!this.data[family].tags) this.data[family].tags = [];
        },
        _cleanup(family) {
            const e = this.data[family];
            if (!e) return;
            if (!e.note && (!e.tags || !e.tags.length)) delete this.data[family];
        },
        _save() {
            localStorage.setItem('gfonts.notes', JSON.stringify(this.data));
        },
    });

    /* ─── Recently viewed (last N families) ───────────────── */
    Alpine.store('recent', {
        items: JSON.parse(localStorage.getItem('gfonts.recent') || '[]'),
        max: 10,

        push(family) {
            this.items = [family, ...this.items.filter(f => f !== family)].slice(0, this.max);
            localStorage.setItem('gfonts.recent', JSON.stringify(this.items));
        },
        clear() {
            this.items = [];
            localStorage.removeItem('gfonts.recent');
        },
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
