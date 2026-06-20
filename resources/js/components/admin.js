// Admin-area Alpine components: sidebar shell, dashboard, inquiry detail,
// status timeline, calendar, catalogs.
import Alpine from 'alpinejs';

// ---------------------------------------------------------------------------
// Admin shell — sidebar collapse state + self-service password / account modals.
// Wraps the whole admin layout so the sidebar can drive the modals.
// ---------------------------------------------------------------------------
Alpine.data('adminShell', (cfg = {}) => ({
    cfg,
    mobileOpen: false,
    settingsOpen: !!cfg.settingsOpenInit,
    userMenuOpen: false,
    showPwd: false,
    pwd: { current: '', next: '', confirm: '', error: '', success: false, loading: false },
    showAccount: false,
    account: { username: cfg.currentUsername || '', error: '', success: false, loading: false },

    openPwd() {
        this.userMenuOpen = false;
        this.pwd = { current: '', next: '', confirm: '', error: '', success: false, loading: false };
        this.showPwd = true;
    },
    async submitPwd() {
        this.pwd.error = ''; this.pwd.success = false;
        if (!this.pwd.current || !this.pwd.next || !this.pwd.confirm) { this.pwd.error = 'Please fill in all fields'; return; }
        if (this.pwd.next.length < 6) { this.pwd.error = 'New password must be at least 6 characters'; return; }
        if (this.pwd.next !== this.pwd.confirm) { this.pwd.error = 'New passwords do not match'; return; }
        this.pwd.loading = true;
        try {
            const res = await fetch(this.cfg.changePwdUrl, {
                method: 'POST', headers: window.jsonHeaders(true),
                body: JSON.stringify({ currentPassword: this.pwd.current, newPassword: this.pwd.next }),
            });
            if (res.ok) { this.pwd.success = true; setTimeout(() => { this.showPwd = false; this.pwd.success = false; }, 1600); }
            else { const d = await res.json().catch(() => ({})); this.pwd.error = d.error || 'Failed to change password'; }
        } catch { this.pwd.error = 'Something went wrong. Please try again.'; }
        finally { this.pwd.loading = false; }
    },

    openAccount() {
        this.userMenuOpen = false;
        this.account = { username: this.cfg.currentUsername || '', error: '', success: false, loading: false };
        this.showAccount = true;
    },
    async submitAccount() {
        this.account.error = ''; this.account.success = false;
        if (!this.account.username.trim()) { this.account.error = 'Username is required'; return; }
        this.account.loading = true;
        try {
            const res = await fetch(this.cfg.meUrl, {
                method: 'PATCH', headers: window.jsonHeaders(true),
                body: JSON.stringify({ newUsername: this.account.username.trim() }),
            });
            if (res.ok) { this.account.success = true; setTimeout(() => { this.showAccount = false; window.location.reload(); }, 1200); }
            else { const d = await res.json().catch(() => ({})); this.account.error = d.error || 'Failed to save changes'; }
        } catch { this.account.error = 'Something went wrong. Please try again.'; }
        finally { this.account.loading = false; }
    },

    async signOut() {
        await fetch(this.cfg.logoutUrl, { method: 'POST', headers: window.jsonHeaders(true) });
        window.location.href = this.cfg.loginUrl;
    },
}));

// ---------------------------------------------------------------------------
// Shared status/format helpers used across admin components.
// ---------------------------------------------------------------------------
const STATUS_LABELS = {
    new: 'New', left_voicemail: 'Left Voicemail', reviewing: 'Reviewing', quoted: 'Quoted',
    scheduled: 'Scheduled', service_performed: 'Service Performed', completed: 'Completed', cancelled: 'Cancelled',
};
const STATUS_CLASSES = {
    new: 'status-new', left_voicemail: 'status-reviewing', reviewing: 'status-reviewing', quoted: 'status-quoted',
    scheduled: 'status-scheduled', service_performed: 'status-service_performed', completed: 'status-completed', cancelled: 'status-cancelled',
};
const SERVICE_LABELS = {
    'junk-removal': 'Junk Removal', '10yd-dumpster': '10 Yard Dumpster Rental', '20yd-dumpster': '20 Yard Dumpster Rental',
    equipment: 'Equipment Rental', other: 'Other / Not Sure',
};
window.adminHelpers = {
    statusLabel: (s) => STATUS_LABELS[s] || 'New',
    statusClass: (s) => STATUS_CLASSES[s] || 'status-new',
    serviceLabel: (s) => SERVICE_LABELS[s] || (s || '').replace(/-/g, ' '),
    money: (n) => Number(n).toLocaleString(),
    date: (d) => (d ? new Date(d).toLocaleDateString() : ''),
    dateTime: (d) => (d ? new Date(d).toLocaleString() : ''),
};

// ---------------------------------------------------------------------------
// Dashboard inquiries section — workqueue filtering, search, New Quote modal.
// ---------------------------------------------------------------------------
Alpine.data('inquiryDashboard', (cfg = {}) => ({
    ...window.adminHelpers,
    cfg,
    inquiries: cfg.inquiries || [],
    filter: 'active',
    search: '',
    dateFilter: '',
    // New Quote modal
    showNew: false,
    nq: { phone: '', name: '', email: '', zip: '', error: '', loading: false },

    get filtered() {
        const q = this.search.trim().toLowerCase();
        return this.inquiries.filter((i) => {
            if (this.filter === 'active') { if (['completed', 'cancelled'].includes(i.status)) return false; }
            else if (this.filter !== 'all') { if (i.status !== this.filter) return false; }
            if (this.dateFilter) { if (!i.created_at || i.created_at.slice(0, 10) !== this.dateFilter) return false; }
            if (q) {
                const hay = `${i.name} ${i.phone} ${i.email} ${i.ref} ${this.serviceLabel(i.service_type)}`.toLowerCase();
                if (!hay.includes(q)) return false;
            }
            return true;
        });
    },

    setFilter(f) { this.filter = f; },

    detailUrl(id) { return this.cfg.detailBase.replace('__ID__', id); },

    // New Quote modal: live matches by phone (last 10 digits).
    get phoneMatches() {
        const digits = this.nq.phone.replace(/\D/g, '').slice(-10);
        if (digits.length < 4) return [];
        return this.inquiries.filter((i) => (i.phone || '').replace(/\D/g, '').slice(-10) === digits).slice(0, 6);
    },
    cloneFrom(m) { this.nq.name = m.name || ''; this.nq.email = m.email || ''; this.nq.zip = m.zip_code || ''; },

    async createQuote() {
        this.nq.error = '';
        if (!this.nq.phone.trim()) { this.nq.error = 'Phone number is required.'; return; }
        this.nq.loading = true;
        try {
            const res = await fetch(this.cfg.createUrl, {
                method: 'POST', headers: window.jsonHeaders(true),
                body: JSON.stringify({ phone: this.nq.phone, name: this.nq.name, email: this.nq.email, zip_code: this.nq.zip }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Failed to create quote');
            window.location.href = this.detailUrl(data.inquiry.id);
        } catch (e) {
            this.nq.error = e.message || 'Failed to create quote';
        } finally {
            this.nq.loading = false;
        }
    },
}));

// ---------------------------------------------------------------------------
// Time slots (5:00 AM – 10:00 PM, 30-min steps) + helpers shared by detail page.
// ---------------------------------------------------------------------------
function buildTimeSlots() {
    const out = [];
    for (let h = 5; h <= 22; h++) {
        for (let m = 0; m < 60; m += 30) {
            if (h === 22 && m > 0) break;
            out.push(`${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`);
        }
    }
    return out;
}
function fmtTime12(t) {
    if (!t) return '';
    const [h, m] = t.split(':').map(Number);
    const period = h >= 12 ? 'PM' : 'AM';
    return `${h % 12 || 12}:${String(m).padStart(2, '0')} ${period}`;
}
function datePart(c) { return c && c.includes('T') ? (c.split('T')[0] || '') : ''; }
function timePart(c) {
    if (!c) return '';
    if (c.includes('T')) return (c.split('T')[1] || '').substring(0, 5);
    if (/^\d{2}:\d{2}$/.test(c)) return c;
    return '';
}

// ---------------------------------------------------------------------------
// Inquiry detail page — ports app/admin/inquiries/[id]/page.tsx.
// ---------------------------------------------------------------------------
Alpine.data('inquiryDetail', (cfg = {}) => ({
    ...window.adminHelpers,
    cfg,
    urls: cfg.urls,
    inquiry: cfg.inquiry,
    equipmentOptions: cfg.equipment || [],
    allInquiries: cfg.allInquiries || [],
    history: cfg.history || [],
    saving: false,
    error: '',
    TIME_SLOTS: buildTimeSlots(),
    fmtTime12, datePart, timePart,

    // editable fields
    adminNotes: '', status: 'new',
    address: '', confirmedDateTime: '',
    phone: '', email: '', preferredContactMethod: 'phone',
    isEditingCustomer: false,
    customerZip: '', customerPreferredDay: '', customerPreferredTime: '',
    serviceType: '', equipmentType: '',
    equipmentRentalDuration: '', equipmentRentalUnit: '',
    expectedDurationValue: '', expectedDurationUnit: 'hours', adminHoursPerDay: '8',
    expectedDurationMinutes: 120,
    quotedPrice: '',
    paymentMethod: '', paymentMethodOther: '', paymentDate: '', paymentNotes: '',

    // status flow
    flow: ['new', 'reviewing', 'quoted', 'scheduled', 'service_performed', 'completed'],
    get flowIndex() { return this.flow.indexOf(this.status); },

    // modals
    showPhotoModal: false,
    showVoicemailModal: false, voicemailNote: '', showCancelConfirm: false,

    init() {
        this.hydrate(this.inquiry);
        // Keep expected_duration_minutes in sync with the hrs/days editor.
        this.$watch('expectedDurationValue', () => this.syncDuration());
        this.$watch('expectedDurationUnit', () => this.syncDuration());
    },

    syncDuration() {
        const qty = Number(this.expectedDurationValue);
        if (!qty || qty <= 0) return;
        this.expectedDurationMinutes = this.expectedDurationUnit === 'days'
            ? Math.round(qty * 8 * 60) : Math.round(qty * 60);
    },

    hydrate(inq) {
        this.inquiry = inq;
        this.adminNotes = inq.admin_notes || '';
        this.status = inq.status;
        this.address = inq.address || '';
        this.confirmedDateTime = inq.confirmed_date_time || '';
        this.phone = inq.phone || '';
        this.email = inq.email || '';
        this.preferredContactMethod = inq.preferred_contact_method || 'phone';
        this.customerZip = inq.zip_code || '';
        this.customerPreferredDay = inq.preferred_day || '';
        this.customerPreferredTime = inq.preferred_time || '';
        this.serviceType = inq.service_type || '';
        this.equipmentType = inq.equipment_type || '';
        this.quotedPrice = inq.quoted_price ?? '';
        this.equipmentRentalDuration = inq.equipment_rental_duration ?? '';
        this.equipmentRentalUnit = inq.equipment_rental_unit ?? '';

        const known = ['Cash', 'Check', 'Credit/Debit Card', 'Venmo', 'Zelle', 'PayPal', 'Invoice'];
        const pm = inq.payment_method || '';
        if (pm && !known.includes(pm)) { this.paymentMethod = 'Other'; this.paymentMethodOther = pm; }
        else { this.paymentMethod = pm; this.paymentMethodOther = ''; }

        if (inq.payment_date) { this.paymentDate = inq.payment_date; }
        else {
            const n = new Date(); const p = (x) => String(x).padStart(2, '0');
            this.paymentDate = `${n.getFullYear()}-${p(n.getMonth() + 1)}-${p(n.getDate())}T${p(n.getHours())}:${p(n.getMinutes())}`;
        }
        this.paymentNotes = inq.payment_notes || '';
        this.expectedDurationMinutes = inq.expected_duration_minutes ?? 120;

        if (inq.equipment_rental_duration != null && inq.equipment_rental_unit) {
            this.expectedDurationValue = inq.equipment_rental_duration;
            this.expectedDurationUnit = inq.equipment_rental_unit;
        } else {
            this.expectedDurationValue = (inq.expected_duration_minutes ?? 120) / 60;
            this.expectedDurationUnit = 'hours';
        }
    },

    get isEquipment() { return this.serviceType === 'equipment'; },

    get previousCustomerAddresses() {
        const np = (this.phone || '').replace(/\D/g, '').slice(-10);
        const ne = (this.email || '').toLowerCase().trim();
        if (!np && !ne) return [];
        const matches = this.allInquiries
            .filter((i) => i.id !== this.inquiry.id)
            .filter((i) => {
                const p = (i.phone || '').replace(/\D/g, '').slice(-10);
                const e = (i.email || '').toLowerCase().trim();
                return (np && p === np) || (ne && e === ne);
            })
            .filter((i) => i.address && i.address.trim().length > 3)
            .sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
        const seen = new Set(); const out = [];
        for (const i of matches) { const a = i.address.trim().toLowerCase(); if (!seen.has(a)) { seen.add(a); out.push(i); } }
        return out;
    },

    get currentCatalogInitialQuote() {
        if (!this.equipmentType || !this.equipmentRentalDuration) return null;
        const equip = this.equipmentOptions.find((e) => e.name === this.equipmentType);
        if (!equip) return null;
        const qty = parseFloat(this.equipmentRentalDuration);
        if (!qty || qty <= 0) return null;
        const unit = this.equipmentRentalUnit || 'hours';
        if (unit === 'days') {
            if (equip.daily_rate != null) return Math.round(equip.daily_rate * qty);
            if (equip.avg_cost_per_hour != null) return Math.round(equip.avg_cost_per_hour * qty * (parseFloat(this.adminHoursPerDay) || 8));
            return null;
        }
        return equip.avg_cost_per_hour != null ? Math.round(equip.avg_cost_per_hour * qty) : null;
    },

    getServiceLabel(key) { return this.serviceLabel(key) || 'Not specified'; },

    getNextTwoOccurrences(dayName) {
        const map = { sunday: 0, monday: 1, tuesday: 2, wednesday: 3, thursday: 4, friday: 5, saturday: 6 };
        const target = map[(dayName || '').toLowerCase()];
        if (target === undefined) return [];
        const out = []; const d = new Date(); d.setHours(0, 0, 0, 0);
        let add = (target - d.getDay() + 7) % 7; if (add === 0) add = 7;
        d.setDate(d.getDate() + add);
        for (let i = 0; i < 2; i++) { out.push(d.toISOString().split('T')[0]); d.setDate(d.getDate() + 7); }
        return out;
    },
    dayLabel(dateStr) {
        return new Date(dateStr + 'T00:00').toLocaleDateString(undefined, { weekday: 'short', month: 'numeric', day: 'numeric' });
    },

    buildBody(overrides = {}) {
        return {
            status: this.status,
            service_type: this.serviceType || null,
            admin_notes: this.adminNotes,
            address: this.address || null,
            confirmed_date_time: this.confirmedDateTime || null,
            phone: this.phone || null,
            email: this.email || null,
            preferred_contact_method: this.preferredContactMethod,
            zip_code: this.customerZip || null,
            preferred_day: this.customerPreferredDay || null,
            preferred_time: this.customerPreferredTime || null,
            equipment_type: (this.equipmentType === '__other__' ? '' : this.equipmentType) || null,
            equipment_rental_duration: this.equipmentRentalDuration === '' ? null : Number(this.equipmentRentalDuration),
            equipment_rental_unit: this.equipmentRentalUnit || null,
            quoted_price: this.quotedPrice === '' ? null : Number(this.quotedPrice),
            payment_method: this.paymentMethod === 'Other' ? (this.paymentMethodOther.trim() || null) : (this.paymentMethod || null),
            payment_date: this.paymentDate || null,
            payment_notes: this.paymentNotes.trim() || null,
            expected_duration_minutes: this.expectedDurationMinutes,
            ...overrides,
        };
    },

    async save(overrides = {}) {
        this.saving = true;
        try {
            const res = await fetch(this.urls.update, { method: 'PATCH', headers: window.jsonHeaders(true), body: JSON.stringify(this.buildBody(overrides)) });
            if (res.ok) { const d = await res.json(); if (d.inquiry) this.hydrate(d.inquiry); await this.reloadHistory(); }
        } catch (e) { this.error = 'Failed to save'; }
        finally { this.saving = false; }
    },

    async reloadHistory() {
        try { const r = await fetch(this.urls.history); if (r.ok) { const d = await r.json(); this.history = d.history || []; } } catch {}
    },

    async quickUpdateStatus(newStatus) {
        if (newStatus === 'left_voicemail') { this.voicemailNote = ''; this.showVoicemailModal = true; return; }
        if (newStatus === 'cancelled') { this.showCancelConfirm = true; return; }
        this.status = newStatus;
        await this.save({ status: newStatus });
    },

    async handleSaveVoicemail() {
        const ts = new Date().toLocaleString();
        const note = this.voicemailNote.trim();
        let notes = this.inquiry.admin_notes || '';
        if (note) { const entry = `Voicemail left on ${ts}:\n${note}`; notes = notes ? `${entry}\n\n${notes}` : entry; }
        this.status = 'left_voicemail';
        await this.save({ status: 'left_voicemail', admin_notes: notes });
        await this.logAudit(note ? 'Left Voicemail (with note)' : 'Left Voicemail');
        this.showVoicemailModal = false; this.voicemailNote = '';
    },

    async handleConfirmCancel() {
        this.status = 'cancelled';
        await this.save({ status: 'cancelled' });
        await this.logAudit('Cancelled quote');
        this.showCancelConfirm = false;
    },

    async togglePreferredContact() {
        this.preferredContactMethod = this.preferredContactMethod === 'phone' ? 'email' : 'phone';
        await this.save({ preferred_contact_method: this.preferredContactMethod });
    },

    async logAudit(action) {
        try { await fetch(this.urls.audit, { method: 'POST', headers: window.jsonHeaders(true), body: JSON.stringify({ action }) }); } catch {}
    },

    openInGoogleMaps() { if (this.address?.trim()) window.open(`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(this.address)}`, '_blank'); },
    openAddressInMaps(addr) { if (addr?.trim()) window.open(`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(addr)}`, '_blank'); },

    stepDuration(dir) {
        const step = this.expectedDurationUnit === 'days' ? 1 : 0.5;
        const minVal = this.expectedDurationUnit === 'days' ? 1 : 0.5;
        const cur = Number(this.expectedDurationValue) || 0;
        this.expectedDurationValue = Math.max(minVal, Math.round((cur + dir * step) * 10) / 10);
    },

    // confirmed date/time split-field setters
    setConfirmedDate(v) { const t = this.timePart(this.confirmedDateTime) || '00:00'; this.confirmedDateTime = v ? `${v}T${t}` : ''; },
    setConfirmedTime(v) { const d = this.datePart(this.confirmedDateTime); if (!v) { this.confirmedDateTime = d ? `${d}T` : ''; return; } this.confirmedDateTime = d ? `${d}T${v}` : v; },
    setPaymentDate(v) { const t = this.timePart(this.paymentDate) || '00:00'; this.paymentDate = v ? `${v}T${t}` : ''; },
    setPaymentTime(v) { const d = this.datePart(this.paymentDate); if (!v) { this.paymentDate = d ? `${d}T` : ''; return; } this.paymentDate = d ? `${d}T${v}` : v; },
    pickPreferredDate(dateStr) { const t = this.timePart(this.confirmedDateTime) || '00:00'; this.confirmedDateTime = `${dateStr}T${t}`; },
}));

// ---------------------------------------------------------------------------
// Analytics — KPIs + 30/MTD/YTD range toggle + service breakdown + Leaflet map.
// ---------------------------------------------------------------------------
Alpine.data('analytics', (cfg = {}) => ({
    inquiries: cfg.inquiries || [],
    range: '30',
    mapApi: null,

    init() {
        this.$nextTick(() => {
            if (this.$refs.map && window.HaulMap) { this.mapApi = window.HaulMap.init(this.$refs.map); this.refreshMap(); }
        });
    },

    get cutoff() {
        const now = new Date();
        if (this.range === 'mtd') return new Date(now.getFullYear(), now.getMonth(), 1);
        if (this.range === 'ytd') return new Date(now.getFullYear(), 0, 1);
        const d = new Date(now); d.setDate(d.getDate() - 30); return d;
    },
    get rangeFiltered() { const c = this.cutoff; return this.inquiries.filter((i) => i.created_at && new Date(i.created_at) >= c); },

    get scheduledCount() { return this.rangeFiltered.filter((i) => i.status === 'scheduled').length; },
    get completedCount() { return this.rangeFiltered.filter((i) => i.status === 'completed').length; },
    get revenue() { return this.rangeFiltered.filter((i) => i.status === 'completed').reduce((s, i) => s + (Number(i.quoted_price) || 0), 0); },
    get avgJobValue() { return this.completedCount ? Math.round(this.revenue / this.completedCount) : 0; },
    get quotedUnpaid() {
        return this.rangeFiltered
            .filter((i) => ['quoted', 'scheduled', 'service_performed'].includes(i.status) && !i.payment_method)
            .reduce((s, i) => s + (Number(i.quoted_price) || 0), 0);
    },

    get serviceBreakdown() {
        const labels = { 'junk-removal': 'Junk Removal', '10yd-dumpster': '10yd Dumpster', '20yd-dumpster': '20yd Dumpster', equipment: 'Equipment', other: 'Other' };
        const groups = {};
        this.rangeFiltered.forEach((i) => {
            const k = i.service_type || 'other';
            if (!groups[k]) groups[k] = { sum: 0, n: 0 };
            if (i.quoted_price != null) { groups[k].sum += Number(i.quoted_price); groups[k].n++; }
        });
        const rows = Object.entries(groups).map(([k, v]) => ({ key: k, label: labels[k] || k, avg: v.n ? Math.round(v.sum / v.n) : 0, count: this.rangeFiltered.filter((i) => (i.service_type || 'other') === k).length }));
        const max = Math.max(1, ...rows.map((r) => r.avg));
        rows.forEach((r) => { r.pct = Math.round((r.avg / max) * 100); });
        return rows.sort((a, b) => b.avg - a.avg);
    },

    setRange(r) { this.range = r; this.refreshMap(); },
    refreshMap() { if (this.mapApi) this.mapApi.setInquiries(this.rangeFiltered); },
    money(n) { return Number(n).toLocaleString(); },
}));

// ---------------------------------------------------------------------------
// Service catalog CRUD.
// ---------------------------------------------------------------------------
Alpine.data('servicesCatalog', (cfg = {}) => ({
    urls: cfg.urls,
    services: cfg.services || [],
    nw: { key: 'junk-removal', label: '', price: '', duration: '120' },
    error: '',
    editingId: null,
    ed: { label: '', price: '', duration: '' },

    async reload() { try { const r = await fetch(this.urls.index, { headers: window.jsonHeaders() }); if (r.ok) { const d = await r.json(); this.services = d.services || []; } } catch {} },

    async add() {
        this.error = '';
        const r = await fetch(this.urls.store, { method: 'POST', headers: window.jsonHeaders(true), body: JSON.stringify({ key: this.nw.key, label: this.nw.label, default_price: this.nw.price === '' ? null : this.nw.price, default_duration_minutes: this.nw.duration || 120 }) });
        if (r.ok) { this.nw = { key: 'junk-removal', label: '', price: '', duration: '120' }; await this.reload(); }
        else { const d = await r.json().catch(() => ({})); this.error = d.error || 'Failed to add service'; }
    },
    startEdit(s) { this.editingId = s.id; this.ed = { label: s.label, price: s.default_price ?? '', duration: s.default_duration_minutes ?? 120 }; },
    cancelEdit() { this.editingId = null; },
    async saveEdit(s) {
        const r = await fetch(this.urls.update.replace('__ID__', s.id), { method: 'PATCH', headers: window.jsonHeaders(true), body: JSON.stringify({ label: this.ed.label, default_price: this.ed.price === '' ? null : this.ed.price, default_duration_minutes: this.ed.duration }) });
        if (r.ok) { this.editingId = null; await this.reload(); }
    },
    async toggleActive(s) { await fetch(this.urls.update.replace('__ID__', s.id), { method: 'PATCH', headers: window.jsonHeaders(true), body: JSON.stringify({ active: !s.active }) }); await this.reload(); },
    async remove(s) { if (!confirm(`Permanently delete the "${s.label}" service? This cannot be undone.`)) return; await fetch(this.urls.destroy.replace('__ID__', s.id), { method: 'DELETE', headers: window.jsonHeaders(true) }); await this.reload(); },
    money(n) { return n == null ? '—' : Number(n).toLocaleString(); },
}));

// ---------------------------------------------------------------------------
// Equipment catalog CRUD.
// ---------------------------------------------------------------------------
Alpine.data('equipmentCatalog', (cfg = {}) => ({
    urls: cfg.urls,
    equipment: cfg.equipment || [],
    nw: { name: '', cost: '', daily: '' },
    error: '',
    editingId: null,
    ed: { name: '', cost: '', daily: '' },

    async reload() { try { const r = await fetch(this.urls.index, { headers: window.jsonHeaders() }); if (r.ok) { const d = await r.json(); this.equipment = d.equipment || []; } } catch {} },

    async add() {
        this.error = '';
        const r = await fetch(this.urls.store, { method: 'POST', headers: window.jsonHeaders(true), body: JSON.stringify({ name: this.nw.name, avg_cost_per_hour: this.nw.cost === '' ? null : this.nw.cost, daily_rate: this.nw.daily === '' ? null : this.nw.daily }) });
        if (r.ok) { this.nw = { name: '', cost: '', daily: '' }; await this.reload(); }
        else { const d = await r.json().catch(() => ({})); this.error = d.error || 'Failed to add equipment'; }
    },
    startEdit(e) { this.editingId = e.id; this.ed = { name: e.name, cost: e.avg_cost_per_hour ?? '', daily: e.daily_rate ?? '' }; },
    cancelEdit() { this.editingId = null; },
    async saveEdit(e) {
        const r = await fetch(this.urls.update.replace('__ID__', e.id), { method: 'PATCH', headers: window.jsonHeaders(true), body: JSON.stringify({ name: this.ed.name, avg_cost_per_hour: this.ed.cost === '' ? null : this.ed.cost, daily_rate: this.ed.daily === '' ? null : this.ed.daily }) });
        if (r.ok) { this.editingId = null; await this.reload(); }
    },
    async toggleActive(e) { await fetch(this.urls.update.replace('__ID__', e.id), { method: 'PATCH', headers: window.jsonHeaders(true), body: JSON.stringify({ active: !e.active }) }); await this.reload(); },
    async remove(e) { if (!confirm('Hide this equipment item?')) return; await fetch(this.urls.destroy.replace('__ID__', e.id), { method: 'DELETE', headers: window.jsonHeaders(true) }); await this.reload(); },
    money(n) { return n == null ? '—' : Number(n).toLocaleString(); },
}));

// ---------------------------------------------------------------------------
// Admin accounts management.
// ---------------------------------------------------------------------------
Alpine.data('adminsManager', (cfg = {}) => ({
    urls: cfg.urls,
    admins: cfg.admins || [],
    nw: { username: '', password: '' },
    error: '',

    async reload() { try { const r = await fetch(this.urls.index, { headers: window.jsonHeaders() }); if (r.ok) { const d = await r.json(); this.admins = d.admins || []; } } catch {} },

    async create() {
        this.error = '';
        const r = await fetch(this.urls.store, { method: 'POST', headers: window.jsonHeaders(true), body: JSON.stringify({ username: this.nw.username, password: this.nw.password }) });
        if (r.ok) { this.nw = { username: '', password: '' }; await this.reload(); }
        else { const d = await r.json().catch(() => ({})); this.error = d.error || 'Failed to create admin'; }
    },
    async resetPassword(a) {
        const pw = prompt(`New temporary password for ${a.username} (min 6 chars):`);
        if (!pw) return;
        const r = await fetch(this.urls.update.replace('__ID__', a.id), { method: 'PATCH', headers: window.jsonHeaders(true), body: JSON.stringify({ action: 'reset_password', newPassword: pw }) });
        if (r.ok) alert('Password reset. The admin must change it on next login.');
        else { const d = await r.json().catch(() => ({})); alert(d.error || 'Failed to reset password'); }
    },
    async remove(a) {
        if (!confirm(`Delete admin ${a.username}?`)) return;
        const r = await fetch(this.urls.destroy.replace('__ID__', a.id), { method: 'DELETE', headers: window.jsonHeaders(true) });
        if (r.ok) await this.reload();
        else { const d = await r.json().catch(() => ({})); alert(d.error || 'Failed to delete admin'); }
    },
    date(d) { return d ? new Date(d).toLocaleDateString() : ''; },
}));

// ---------------------------------------------------------------------------
// Pickup calendar — month / week / day. Ported from app/admin/calendar/page.tsx.
// ---------------------------------------------------------------------------
const DAY_START_HOUR = 5, DAY_END_HOUR = 22, HOUR_PX = 64;
Alpine.data('calendar', (cfg = {}) => ({
    events: cfg.events || [],
    detailBase: cfg.detailBase,
    viewMode: 'week',
    // store currentDate as a timestamp for clean reactivity
    cur: Date.now(),
    HOURS: Array.from({ length: DAY_END_HOUR - DAY_START_HOUR }, (_, i) => i + DAY_START_HOUR),

    get currentDate() { return new Date(this.cur); },

    formatHour(h) { if (h === 0) return '12 AM'; if (h < 12) return `${h} AM`; if (h === 12) return '12 PM'; return `${h - 12} PM`; },
    detailUrl(id) { return this.detailBase.replace('__ID__', id); },

    eventClasses(s) {
        return ({
            new: 'bg-blue-500/10 hover:bg-blue-500/20 border-blue-500/40 hover:border-blue-500/70',
            left_voicemail: 'bg-violet-500/10 hover:bg-violet-500/20 border-violet-500/40 hover:border-violet-500/70',
            reviewing: 'bg-amber-500/10 hover:bg-amber-500/20 border-amber-500/40 hover:border-amber-500/70',
            quoted: 'bg-indigo-500/10 hover:bg-indigo-500/20 border-indigo-500/40 hover:border-indigo-500/70',
            scheduled: 'bg-[#F8C820]/10 hover:bg-[#F8C820]/20 border-[#F8C820]/40 hover:border-[#F8C820]/70',
            service_performed: 'bg-teal-500/10 hover:bg-teal-500/20 border-teal-500/40 hover:border-teal-500/70',
            completed: 'bg-emerald-500/10 hover:bg-emerald-500/20 border-emerald-500/40 hover:border-emerald-500/70',
        })[s] || 'bg-gray-100 hover:bg-gray-200 border-gray-300 hover:border-gray-400';
    },
    dotClass(s) {
        return ({ new: 'bg-blue-400', left_voicemail: 'bg-violet-400', reviewing: 'bg-amber-400', quoted: 'bg-indigo-400', scheduled: 'bg-[#F8C820]', service_performed: 'bg-teal-400', completed: 'bg-emerald-400' })[s] || 'bg-gray-400';
    },
    statusLabel(s) { return ({ new: 'New', left_voicemail: 'Voicemail', reviewing: 'Reviewing', quoted: 'Quoted', scheduled: 'Scheduled', service_performed: 'Service Performed', completed: 'Completed' })[s] || s; },
    serviceLabel(s) { return (s || '').replace(/-/g, ' '); },
    fmtClock(d) { return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }); },

    get calendarEvents() {
        return this.events.filter((e) => e.confirmed_date_time).map((e) => {
            const start = new Date(e.confirmed_date_time);
            const end = new Date(start.getTime() + (e.expected_duration_minutes || 120) * 60000);
            return { inquiry: e, start, end, key: start.toISOString().split('T')[0] };
        });
    },
    eventsForKey(key) { return this.calendarEvents.filter((e) => e.key === key).sort((a, b) => a.start - b.start); },

    // header
    get headerLabel() {
        if (this.viewMode === 'month') return this.currentDate.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
        if (this.viewMode === 'day') return this.currentDate.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
        const ws = this.weekStart;
        return `${ws.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })} – ${new Date(ws.getTime() + 6 * 86400000).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })}`;
    },
    get totalOnCalendar() { return this.calendarEvents.length; },

    // week
    get weekStart() { const d = new Date(this.cur); d.setDate(d.getDate() - d.getDay()); d.setHours(0, 0, 0, 0); return d; },
    get weekDays() { const ws = this.weekStart; return Array.from({ length: 7 }, (_, i) => { const d = new Date(ws); d.setDate(d.getDate() + i); return d; }); },

    // month
    get monthGrid() {
        const y = this.currentDate.getFullYear(), m = this.currentDate.getMonth();
        const firstDay = new Date(y, m, 1).getDay(), days = new Date(y, m + 1, 0).getDate();
        const out = [];
        for (let i = 0; i < firstDay; i++) out.push(null);
        for (let d = 1; d <= days; d++) out.push(new Date(y, m, d));
        return out;
    },

    // day layout
    get dayLayout() {
        const key = this.currentDate.toISOString().split('T')[0];
        const evs = this.eventsForKey(key);
        const laneEnds = [];
        const withLanes = evs.map((e) => {
            let lane = laneEnds.findIndex((end) => end <= e.start);
            if (lane === -1) { lane = laneEnds.length; laneEnds.push(e.end); } else { laneEnds[lane] = e.end; }
            return { ...e, lane };
        });
        return withLanes.map((e) => {
            const concurrent = withLanes.filter((o) => o.start < e.end && o.end > e.start).length;
            const startMin = e.start.getHours() * 60 + e.start.getMinutes();
            const endMin = e.end.getHours() * 60 + e.end.getMinutes();
            const dayStart = DAY_START_HOUR * 60, dayEnd = DAY_END_HOUR * 60;
            const top = Math.max(0, (startMin - dayStart) * HOUR_PX / 60);
            const rawH = (Math.min(endMin, dayEnd) - Math.max(startMin, dayStart)) * HOUR_PX / 60;
            const height = Math.max(HOUR_PX * 0.45, rawH);
            const left = (e.lane / concurrent) * 100, width = 100 / concurrent;
            return { ...e, style: `position:absolute;top:${top}px;height:${height}px;left:calc(${left}% + 4px);width:calc(${width}% - 8px)`, big: height >= HOUR_PX };
        });
    },

    isToday(d) { return d && d.toDateString() === new Date().toDateString(); },
    dayKey(d) { return d.toISOString().split('T')[0]; },

    prev() { const d = new Date(this.cur); if (this.viewMode === 'month') d.setMonth(d.getMonth() - 1); else if (this.viewMode === 'week') d.setDate(d.getDate() - 7); else d.setDate(d.getDate() - 1); this.cur = d.getTime(); },
    next() { const d = new Date(this.cur); if (this.viewMode === 'month') d.setMonth(d.getMonth() + 1); else if (this.viewMode === 'week') d.setDate(d.getDate() + 7); else d.setDate(d.getDate() + 1); this.cur = d.getTime(); },
    today() { this.cur = Date.now(); },
    goToDay(d) { this.cur = d.getTime(); this.viewMode = 'day'; },
}));

// ---------------------------------------------------------------------------
// Site content editor — collects every Trix hidden input + serving-areas
// textarea on the page and PATCHes them to the admin content endpoint.
// ---------------------------------------------------------------------------
Alpine.data('siteContent', (cfg = {}) => ({
    cfg,
    icons: cfg.icons || [],
    cardMax: cfg.cardMax || {},
    cardSets: {}, // { fieldKey: [cards] } — populated in init()
    saving: false,
    saved: false,
    dirty: false,
    ready: false, // gates dirty-tracking until after Trix finishes loading content
    error: '',

    init() {
        const sets = this.cfg.cardSets || {};
        const built = {};
        for (const key of Object.keys(sets)) {
            built[key] = (sets[key] || []).map((c, i) => ({
                icon: c.icon || 'truck',
                image: c.image || '',
                title: c.title || '',
                subheader: c.subheader || '',
                body: c.body || '',
                uid: c.uid || (key + i + Math.random().toString(36).slice(2)),
            }));
        }
        this.cardSets = built;
        setTimeout(() => { this.ready = true; }, 700);
    },

    maxFor(key) {
        return this.cardMax[key] || 4;
    },

    addCard(key) {
        if ((this.cardSets[key]?.length || 0) >= this.maxFor(key)) return;
        this.cardSets[key].push({
            icon: this.icons[0] || 'truck',
            image: '',
            title: '',
            subheader: '',
            body: '',
            uid: key + Date.now().toString(36) + Math.random().toString(36).slice(2),
        });
        this.dirty = true;
    },

    removeCard(key, i) {
        this.cardSets[key].splice(i, 1);
        this.dirty = true;
    },

    // Read an uploaded image, downscale it to <=128px, store as a base64 data URL.
    uploadIcon(card, event) {
        const file = event.target.files && event.target.files[0];
        event.target.value = ''; // allow re-selecting the same file later
        if (!file) return;
        if (!file.type.startsWith('image/')) { this.error = 'Please choose an image file.'; return; }
        if (file.size > 10 * 1024 * 1024) { this.error = 'Image is too large (max 10MB).'; return; }
        this.error = '';

        const reader = new FileReader();
        reader.onload = (e) => {
            const img = new Image();
            img.onload = () => {
                const max = 128;
                let w = img.width, h = img.height;
                if (w > max || h > max) {
                    const scale = max / Math.max(w, h);
                    w = Math.round(w * scale);
                    h = Math.round(h * scale);
                }
                const canvas = document.createElement('canvas');
                canvas.width = w;
                canvas.height = h;
                canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                card.image = canvas.toDataURL('image/png'); // PNG preserves transparency
                this.dirty = true;
            };
            img.onerror = () => { this.error = 'That image could not be read.'; };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    },

    clearIcon(card) {
        card.image = '';
        this.dirty = true;
    },

    async save() {
        this.saving = true;
        this.saved = false;
        this.error = '';

        const content = {};
        document.querySelectorAll('[data-cms-key]').forEach((el) => {
            const key = el.getAttribute('data-cms-key');
            if (el.getAttribute('data-cms-type') === 'list') {
                content[key] = el.value.split(/\r?\n/).map((s) => s.trim()).filter(Boolean);
            } else {
                content[key] = el.value; // Trix keeps this hidden input in sync
            }
        });

        for (const key of Object.keys(this.cardSets)) {
            content[key] = this.cardSets[key].map((c) => ({ icon: c.icon, image: c.image || '', title: c.title, subheader: c.subheader || '', body: c.body }));
        }

        try {
            const res = await fetch(this.cfg.updateUrl, {
                method: 'PATCH',
                headers: window.jsonHeaders(true),
                body: JSON.stringify({ content }),
            });
            if (!res.ok) throw new Error('Save failed. Please try again.');
            this.saved = true;
            this.dirty = false;
            setTimeout(() => { this.saved = false; }, 3000);
        } catch (e) {
            this.error = e.message || 'Could not save.';
        } finally {
            this.saving = false;
        }
    },
}));

// ---------------------------------------------------------------------------
// Rental agreement sender — generates (or reuses) a signing link for the
// current inquiry and lets the admin copy it to send to the customer.
// ---------------------------------------------------------------------------
Alpine.data('agreementSender', (cfg = {}) => ({
    cfg,
    agreements: cfg.agreements || [],
    preferred: cfg.preferred === 'email' ? 'email' : 'phone',
    phone: cfg.phone || '',
    email: cfg.email || '',
    name: cfg.name || '',
    sendToContact: false,
    link: '',
    sending: false,
    copied: false,
    copiedId: '',
    error: '',

    get contactLabel() {
        return (this.preferred === 'email' ? 'Email' : 'Text') + ' agreement link';
    },

    init() {
        const active = this.agreements.find((a) => a.usable);
        if (active) this.link = active.url;
    },

    async send() {
        this.sending = true;
        this.error = '';
        this.copied = false;
        try {
            const res = await fetch(this.cfg.createUrl, { method: 'POST', headers: window.jsonHeaders(true) });
            if (!res.ok) throw new Error('Could not generate the agreement.');
            const data = await res.json();
            const a = data.agreement;
            this.link = a.url;
            if (!this.agreements.some((x) => x.id === a.id)) {
                this.agreements.unshift(a);
            }
            if (this.sendToContact) this.deliver();
        } catch (e) {
            this.error = e.message || 'Error generating the agreement.';
        } finally {
            this.sending = false;
        }
    },

    // Open the admin's email/SMS app prefilled with the signing link, using the
    // customer's preferred contact method.
    deliver() {
        if (!this.link) return;
        const msg = `Hi ${this.name || 'there'}, please review and sign your rental agreement here: ${this.link}`;
        if (this.preferred === 'email') {
            if (!this.email) { this.error = 'No email address on file for this customer.'; return; }
            window.location.href = `mailto:${encodeURIComponent(this.email)}`
                + `?subject=${encodeURIComponent('Your Rental Agreement')}&body=${encodeURIComponent(msg)}`;
        } else {
            if (!this.phone) { this.error = 'No phone number on file for this customer.'; return; }
            window.location.href = `sms:${this.phone.replace(/[^\d+]/g, '')}?body=${encodeURIComponent(msg)}`;
        }
    },

    async copy() {
        try {
            await navigator.clipboard.writeText(this.link);
            this.copied = true;
            setTimeout(() => { this.copied = false; }, 2000);
        } catch {
            this.error = 'Copy failed — select the link and copy manually.';
        }
    },

    async copyAgreement(a) {
        try {
            await navigator.clipboard.writeText(a.url);
            this.copiedId = a.id;
            setTimeout(() => { if (this.copiedId === a.id) this.copiedId = ''; }, 2000);
        } catch {
            this.error = 'Copy failed — open the agreement and copy the link manually.';
        }
    },

    async remove(a) {
        const msg = a.signed_at
            ? 'Delete this signed agreement and its collected signature? This cannot be undone.'
            : 'Delete this pending agreement link?';
        if (!confirm(msg)) return;
        this.error = '';
        try {
            const res = await fetch(this.cfg.deleteUrl.replace('__ID__', a.id), {
                method: 'DELETE',
                headers: window.jsonHeaders(true),
            });
            if (!res.ok) throw new Error('Could not delete the agreement.');
            this.agreements = this.agreements.filter((x) => x.id !== a.id);
            if (this.link && this.link === a.url) this.link = '';
        } catch (e) {
            this.error = e.message || 'Delete failed.';
        }
    },

    fmt(d) {
        return d ? new Date(d).toLocaleDateString() : '';
    },
}));

export {};
