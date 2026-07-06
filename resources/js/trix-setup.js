// Trix rich-text editor setup for the admin Site Content page.
//
// Adds a left/center/right text-alignment control group to the Trix toolbar.
// Trix has no native alignment, so each alignment is registered as a block
// attribute that renders a custom tag (`<trix-align-*>`); resources/css/app.css
// styles those tags (in the editor *and* in the rendered public output).
//
// TIMING: importing 'trix' synchronously registers <trix-editor> and parses the
// existing content *before* our config below runs — its DOMPurify pass would
// strip the unknown alignment tags. So we snapshot each editor's original HTML
// first, then re-load it once the config (block attributes + DOMPurify allow-list)
// is in place, so previously-saved alignment survives a page reload.

const ALIGNMENTS = [
    { attr: 'alignLeft', tag: 'trix-align-left', title: 'Align left', icon: 'align-left' },
    { attr: 'alignCenter', tag: 'trix-align-center', title: 'Align center', icon: 'align-center' },
    { attr: 'alignRight', tag: 'trix-align-right', title: 'Align right', icon: 'align-right' },
];
const ALIGN_ATTRS = ALIGNMENTS.map((a) => a.attr);

// Snapshot each editor's original HTML the instant before Trix parses it — at
// that point the config below isn't applied yet, so Trix's DOMPurify pass strips
// our alignment tags. `trix-before-initialize` fires for every editor (including
// the Alpine-rendered card editors) right before it loads its content, so we
// capture the untouched HTML and re-load it once the config is ready.
// Registered before importing Trix so it's listening before any editor upgrades.
const originals = new Map();
document.addEventListener('trix-before-initialize', (event) => {
    const el = event.target;
    const inputId = el.getAttribute('input');
    const input = inputId ? document.getElementById(inputId) : null;
    originals.set(el, input ? input.value : el.innerHTML);
});

import('trix').then(({ default: Trix }) => {
    // Register alignment block attributes (parse enabled so they restore on load).
    ALIGNMENTS.forEach(({ attr, tag }) => {
        Trix.config.blockAttributes[attr] = { tagName: tag, nestable: false };
    });
    // Let the sanitizer keep our custom tags on parse.
    Trix.config.dompurify = {
        ...Trix.config.dompurify,
        ADD_TAGS: ALIGNMENTS.map((a) => a.tag),
    };

    // Re-parse the original content now that the config recognises alignment tags.
    // Only needed when alignment tags are present — the first (pre-config) parse
    // stripped them. Skipping otherwise avoids a spurious change/dirty on load.
    originals.forEach((html, el) => {
        if (!html || !/<trix-align-/i.test(html)) return;
        const reload = () => el.editor && el.editor.loadHTML(html);
        if (el.editor) reload();
        else el.addEventListener('trix-initialize', reload, { once: true });
    });
});

// Inject the alignment buttons + enforce single-alignment when each editor is ready.
document.addEventListener('trix-initialize', (event) => {
    const editorEl = event.target;
    const toolbar = editorEl.toolbarElement;
    const group = toolbar && toolbar.querySelector('.trix-button-group--block-tools');
    if (!group || group.querySelector('[data-trix-attribute="alignLeft"]')) return;

    ALIGNMENTS.forEach(({ attr, title, icon }) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = `trix-button trix-button--icon trix-button--icon-${icon}`;
        btn.dataset.trixAttribute = attr;
        btn.title = title;
        btn.tabIndex = -1;
        btn.textContent = title;
        group.appendChild(btn);
    });

    // Capture-phase click clears the other alignments before Trix toggles the
    // clicked one, so a block is only ever left- OR center- OR right-aligned.
    group.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-trix-attribute^="align"]');
        if (!btn || !editorEl.editor) return;
        ALIGN_ATTRS.forEach((a) => {
            if (a !== btn.dataset.trixAttribute) editorEl.editor.deactivateAttribute(a);
        });
    }, true);
});
