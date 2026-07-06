import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

Alpine.plugin(persist);
window.Alpine = Alpine;
window.L = L;

// ---------------------------------------------------------------------------
// Small shared helpers
// ---------------------------------------------------------------------------
window.csrfToken = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

window.jsonHeaders = (withCsrf = false) => {
    const h = { 'Content-Type': 'application/json', Accept: 'application/json' };
    if (withCsrf) h['X-CSRF-TOKEN'] = window.csrfToken();
    return h;
};

// Base URL of the app, so fetch() calls work when installed in a subfolder
// (e.g. /junknallhauling). Read from the <meta name="app-base-url"> tag.
window.appBaseUrl = (
    document.querySelector('meta[name="app-base-url"]')?.getAttribute('content') ?? ''
).replace(/\/$/, '');

window.apiUrl = (path) =>
    window.appBaseUrl + (String(path).startsWith('/') ? path : '/' + path);

// Alpine.data() component registrations are appended below as features are built.
// @see resources/js/components/*

import './components/forms.js';
import './components/admin.js';
import './components/map.js';
import './components/reveal.js';

// Trix rich-text editor JS — only pulled in on the admin Site Content page so
// it never weighs down the public bundle. Its CSS is bundled into app.css.
// The setup module imports Trix and adds the text-alignment toolbar controls.
if (document.querySelector('trix-editor')) {
    import('./trix-setup.js');
}

Alpine.start();
