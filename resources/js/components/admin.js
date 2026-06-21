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
    // New Quote modal
    showNew: false,
    nq: { phone: '', name: '', email: '', zip: '', error: '', loading: false },

    init() {
        // Land on the first workqueue bucket that has quotes: New → Reviewing/Quoted
        // → Service Performed, falling back to Scheduled when the others are empty.
        this.filter = this.countNew ? 'new'
            : this.countReviewingQuoted ? 'reviewing_quoted'
                : this.countServicePerformed ? 'service_performed'
                    : 'scheduled';
    },

    get filtered() {
        const cutoff30 = this.filter === 'completed30' ? this._cutoff30() : null;
        return this.inquiries.filter((i) => {
            if (this.filter === 'active') { if (['completed', 'cancelled'].includes(i.status)) return false; }
            else if (this.filter === 'reviewing_quoted') { if (!['reviewing', 'quoted'].includes(i.status)) return false; }
            else if (this.filter === 'completed30') { if (i.status !== 'completed' || !i.created_at || new Date(i.created_at) < cutoff30) return false; }
            else if (this.filter !== 'all') { if (i.status !== this.filter) return false; }
            return true;
        });
    },

    // Workqueue card counts (reactive over the loaded list).
    _cutoff30() { const d = new Date(); d.setDate(d.getDate() - 30); return d; },
    get countNew() { return this.inquiries.filter((i) => i.status === 'new').length; },
    get countReviewingQuoted() { return this.inquiries.filter((i) => i.status === 'reviewing' || i.status === 'quoted').length; },
    get countScheduled() { return this.inquiries.filter((i) => i.status === 'scheduled').length; },
    get countServicePerformed() { return this.inquiries.filter((i) => i.status === 'service_performed').length; },
    get countCompleted30() { const c = this._cutoff30(); return this.inquiries.filter((i) => i.status === 'completed' && i.created_at && new Date(i.created_at) >= c).length; },

    setFilter(f) { this.filter = f; },

    detailUrl(id) { return this.cfg.detailBase.replace('__ID__', id); },
    rentalLabel(i) { return (i.equipment_rental_duration && i.equipment_rental_unit) ? `${i.equipment_rental_duration} ${i.equipment_rental_unit}` : ''; },

    // New Quote modal: live matches by phone (last 10 digits).
    get phoneMatches() {
        const digits = this.nq.phone.replace(/\D/g, '').slice(-10);
        if (digits.length < 4) return [];
        return this.inquiries.filter((i) => (i.phone || '').replace(/\D/g, '').slice(-10) === digits).slice(0, 6);
    },
    // Clone a previous quote into a brand-new one (all details copied server-side), then open it.
    async cloneQuote(m) {
        this.nq.error = '';
        this.nq.loading = true;
        try {
            const res = await fetch(this.cfg.cloneUrl.replace('__ID__', m.id), { method: 'POST', headers: window.jsonHeaders(true) });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Failed to clone quote');
            window.location.href = this.detailUrl(data.inquiry.id);
        } catch (e) {
            this.nq.error = e.message || 'Failed to clone quote';
            this.nq.loading = false;
        }
    },

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
    serviceCatalog: cfg.services || [],
    allInquiries: cfg.allInquiries || [],
    employees: cfg.employees || [], // {id, username} — for resolving the assigned-employee name
    scheduleEvents: cfg.scheduleEvents || [], // confirmed visits, for the day-schedule panel
    history: cfg.history || [],
    saving: false,
    baseline: '', // JSON snapshot of the saved form, for dirty detection
    error: '',
    TIME_SLOTS: buildTimeSlots(),
    fmtTime12, datePart, timePart,

    // address autocomplete (OpenStreetMap, via admin backend proxy)
    addrSuggestions: [], addrOpen: false, addrLoading: false, _addrTimer: null, _addrSeq: 0,

    // editable fields
    jobType: 'service', // 'service' | 'equipment' (pill toggle in Job Details)
    adminNotes: '', status: 'new',
    assignedEmployeeId: '',
    address: '', confirmedDateTime: '',
    firstName: '', lastName: '',
    phone: '', email: '', preferredContactMethod: 'phone',
    isEditingCustomer: false,
    customerPulled: false, // brief confirmation after pulling a prior customer's info
    notifyCustomer: false, // tick to text/email the customer when scheduling the visit
    customerZip: '', customerPreferredDay: '', customerPreferredTime: '',
    serviceType: '', equipmentType: '',
    equipmentRentalDuration: '', equipmentRentalUnit: '',
    expectedDurationValue: '', expectedDurationUnit: 'hours', adminHoursPerDay: '8',
    expectedDurationMinutes: 120,
    quotedPrice: '',
    quoteCopied: false, _quoteCopiedTimer: null, // transient "copied to Quoted Price" flash
    paymentMethod: '', paymentMethodOther: '', paymentDate: '', paymentNotes: '',

    // status flow
    flow: ['new', 'reviewing', 'quoted', 'scheduled', 'service_performed', 'completed'],
    get flowIndex() { return this.flow.indexOf(this.status); },

    // modals
    showPhotoModal: false,
    showVoicemailModal: false, voicemailNote: '', showCancelConfirm: false,
    showCalendarModal: false,
    showStatusSheet: false, // mobile status picker
    statusChoices: ['new', 'reviewing', 'quoted', 'scheduled', 'service_performed', 'completed', 'left_voicemail', 'cancelled'],

    init() {
        this.hydrate(this.inquiry);
        // Keep expected_duration_minutes in sync with the hrs/days editor.
        this.$watch('expectedDurationValue', () => this.syncDuration());
        this.$watch('expectedDurationUnit', () => this.syncDuration());
        // Apply a visit placed in the day-calendar popup (iframe). Validate the
        // message shape (a YYYY-MM-DDTHH:MM datetime) rather than the exact origin
        // so host aliases (127.0.0.1 vs localhost) can't silently drop it.
        window.addEventListener('message', (e) => {
            const d = e.data;
            if (!d || d.type !== 'calendar-pick' || !/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(d.datetime || '')) return;
            this.confirmedDateTime = d.datetime;
            // If the card was resized in the calendar, apply the new duration too.
            const mins = Number(d.duration);
            if (mins > 0) { this.expectedDurationUnit = 'hours'; this.expectedDurationValue = Math.round((mins / 60) * 100) / 100; }
            this.showCalendarModal = false; // saving in the calendar closes the popup
        });
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
        this.assignedEmployeeId = inq.assigned_employee_id || '';
        this.address = inq.address || '';
        this.confirmedDateTime = inq.confirmed_date_time || '';
        // Single stored name → first word is the first name, the rest is the last name.
        const nameParts = (inq.name || '').trim().split(/\s+/).filter(Boolean);
        this.firstName = nameParts.shift() || '';
        this.lastName = nameParts.join(' ');
        this.phone = inq.phone || '';
        this.email = inq.email || '';
        this.preferredContactMethod = inq.preferred_contact_method || 'phone';
        this.customerZip = inq.zip_code || '';
        this.customerPreferredDay = inq.preferred_day || '';
        this.customerPreferredTime = inq.preferred_time || '';
        this.serviceType = inq.service_type || '';
        this.equipmentType = inq.equipment_type || '';
        // Equipment Rental is the legacy service_type === 'equipment' (or any saved
        // equipment_type); everything else is a Service.
        this.jobType = (inq.service_type === 'equipment' || inq.equipment_type) ? 'equipment' : 'service';
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

        // Snapshot the saved state so we can detect unsaved edits (dirty).
        this.baseline = JSON.stringify(this.buildBody());
    },

    // True when the form differs from the last saved/loaded state.
    get dirty() { return this.baseline !== '' && JSON.stringify(this.buildBody()) !== this.baseline; },

    get isEquipment() { return this.jobType === 'equipment'; },

    get fullName() { return [this.firstName.trim(), this.lastName.trim()].filter(Boolean).join(' '); },

    // Pill toggle. Switching to Service ensures a valid catalog selection and
    // seeds the visit duration from that service's default.
    setJobType(type) {
        if (this.jobType === type) return;
        this.jobType = type;
        if (type === 'service') {
            if (!this.serviceType || this.serviceType === 'equipment') {
                this.serviceType = this.serviceCatalog[0]?.key || '';
            }
            this.applyServiceDefaultDuration();
        }
    },

    onServiceChange() { this.applyServiceDefaultDuration(); },

    // Seed the Visit "Duration" field from the selected service's default.
    applyServiceDefaultDuration() {
        const svc = this.serviceCatalog.find((s) => s.key === this.serviceType);
        const mins = svc?.default_duration_minutes;
        if (!mins || mins <= 0) return;
        this.expectedDurationUnit = 'hours';
        this.expectedDurationValue = mins / 60; // syncDuration() recomputes minutes
    },

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

    // Most recent *other* order sharing this quote's phone or email — the source
    // for the "pull customer info" banner. Returns null when there's nothing useful
    // to pull (no name/email/address/zip on the matched record).
    get customerMatch() {
        const np = (this.phone || '').replace(/\D/g, '').slice(-10);
        const ne = (this.email || '').toLowerCase().trim();
        if (!np && !ne) return null;
        return this.allInquiries
            .filter((i) => i.id !== this.inquiry.id)
            .filter((i) => {
                const p = (i.phone || '').replace(/\D/g, '').slice(-10);
                const e = (i.email || '').toLowerCase().trim();
                return (np && p === np) || (ne && e === ne);
            })
            .filter((i) => (i.name && i.name.trim()) || (i.email && i.email.trim()) || (i.address && i.address.trim()) || (i.zip_code && String(i.zip_code).trim()))
            .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))[0] || null;
    },

    // Copy the matched customer's profile into this quote (only fields the source
    // actually has, so we never blank out existing data), then persist.
    pullCustomerInfo() {
        const m = this.customerMatch;
        if (!m) return;
        if (m.name && m.name.trim()) {
            const parts = m.name.trim().split(/\s+/).filter(Boolean);
            this.firstName = parts.shift() || '';
            this.lastName = parts.join(' ');
        }
        if (m.phone && m.phone.trim()) this.phone = m.phone.trim();
        if (m.email && m.email.trim()) this.email = m.email.trim();
        if (m.address && m.address.trim()) this.address = m.address.trim();
        if (m.zip_code && String(m.zip_code).trim()) this.customerZip = String(m.zip_code).trim();
        if (m.preferred_contact_method) this.preferredContactMethod = m.preferred_contact_method;
        if (m.preferred_day && m.preferred_day.trim()) this.customerPreferredDay = m.preferred_day.trim();
        if (m.preferred_time && m.preferred_time.trim()) this.customerPreferredTime = m.preferred_time.trim();
        this.customerPulled = true;
        this.save();
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

    // Catalog price of the selected service (service mode only) — shown as the
    // quote for that item, copyable into the Payment "Quoted Price" field.
    get selectedServicePrice() {
        if (this.isEquipment) return null;
        const svc = this.serviceCatalog.find((s) => s.key === this.serviceType);
        return svc && svc.default_price != null ? svc.default_price : null;
    },

    // Description (customer_instructions) of the selected service/equipment — the
    // admin can pull it into the customer-visible Service Notes.
    get currentCatalogDescription() {
        const item = this.isEquipment
            ? this.equipmentOptions.find((e) => e.name === this.equipmentType)
            : this.serviceCatalog.find((s) => s.key === this.serviceType);
        return (item && item.customer_instructions) ? String(item.customer_instructions).trim() : '';
    },
    pullCatalogDescription() {
        const desc = this.currentCatalogDescription;
        if (!desc) return;
        const cur = (this.adminNotes || '').trim();
        if (cur.includes(desc)) return;                 // already pulled — don't duplicate
        this.adminNotes = cur ? `${cur}\n\n${desc}` : desc;
    },

    // --- Schedule the visit + optionally notify the customer ----------------
    get hasConfirmedSlot() { return !!(this.datePart(this.confirmedDateTime) && this.timePart(this.confirmedDateTime)); },
    get canSchedule() { return this.hasConfirmedSlot && ['new', 'reviewing', 'quoted', 'left_voicemail'].includes(this.status); },
    get preferredMethodLabel() { return this.preferredContactMethod === 'email' ? 'email' : 'text message'; },
    get visitJobLabel() { return this.isEquipment ? (this.equipmentType || 'equipment rental') : (this.serviceLabel(this.serviceType) || 'service'); },
    get visitWhenLabel() {
        const d = new Date(this.confirmedDateTime);
        return isNaN(d.getTime()) ? '' : d.toLocaleString(undefined, { weekday: 'long', month: 'long', day: 'numeric', hour: 'numeric', minute: '2-digit' });
    },
    get scheduledSummary() {
        const dur = this.expectedDurationValue ? ` · ${this.expectedDurationValue} ${this.expectedDurationUnit}` : '';
        return this.visitWhenLabel + dur;
    },

    // Move the quote to Scheduled, persist, then notify if requested.
    async markScheduled() {
        this.status = 'scheduled';
        await this.save();
        if (this.notifyCustomer) this.notifyCustomerOfVisit();
        this.notifyCustomer = false;
    },

    // Open the admin's SMS/email client pre-filled, using the customer's preferred method.
    notifyCustomerOfVisit() {
        const when = this.visitWhenLabel;
        if (!when) { this.error = 'Set a date and time first.'; return; }
        const msg = `Hi ${this.firstName || 'there'}, this confirms your ${this.visitJobLabel} with Junk N All Hauling is scheduled for ${when}. Reply with any questions — thank you!`;
        if (this.preferredContactMethod === 'email') {
            if (!this.email) { this.error = 'No email address on file for this customer.'; return; }
            window.location.href = `mailto:${encodeURIComponent(this.email)}`
                + `?subject=${encodeURIComponent('Your Scheduled Visit — Junk N All Hauling')}&body=${encodeURIComponent(msg)}`;
        } else {
            if (!this.phone) { this.error = 'No phone number on file for this customer.'; return; }
            window.location.href = `sms:${this.phone.replace(/[^\d+]/g, '')}?body=${encodeURIComponent(msg)}`;
        }
    },

    // Copy a displayed quote into the Payment "Quoted Price" field (+ brief flash),
    // then auto-save so the quote is persisted and a payment link can be sent
    // straight away without a manual save.
    copyToQuotedPrice(value) {
        const n = Number(value);
        if (isNaN(n) || n < 0) return;
        this.quotedPrice = n;
        this.quoteCopied = true;
        clearTimeout(this._quoteCopiedTimer);
        this._quoteCopiedTimer = setTimeout(() => { this.quoteCopied = false; }, 2500);
        this.save();
    },

    // --- Day-schedule panel: other confirmed visits on the selected visit date ---
    detailUrl(id) { return (this.urls?.detailBase || '').replace('__ID__', id); },
    get calendarEmbedUrl() {
        const date = encodeURIComponent(this.datePart(this.confirmedDateTime) || '');
        const time = encodeURIComponent(this.timePart(this.confirmedDateTime) || '');
        const dur = encodeURIComponent(this.expectedDurationMinutes || 120);
        const label = encodeURIComponent(this.fullName || this.inquiry.name || 'This visit');
        // Exclude this quote's own saved visit — it's represented by the draggable pick card.
        const exclude = encodeURIComponent(this.inquiry.id || '');
        return `${this.urls.calendarEmbed}?date=${date}&time=${time}&duration=${dur}&label=${label}&exclude=${exclude}`;
    },

    get currentVisitWindow() {
        if (!this.confirmedDateTime) return null;
        const start = new Date(this.confirmedDateTime);
        if (isNaN(start.getTime())) return null;
        const mins = Number(this.expectedDurationMinutes) || 120;
        return { start, end: new Date(start.getTime() + mins * 60000) };
    },

    get daySchedule() {
        const key = this.datePart(this.confirmedDateTime);
        if (!key) return [];
        const cur = this.currentVisitWindow;
        const rows = this.scheduleEvents
            .filter((e) => e.id !== this.inquiry.id && e.confirmed_date_time && this.datePart(e.confirmed_date_time) === key)
            .map((e) => {
                const start = new Date(e.confirmed_date_time);
                const end = new Date(start.getTime() + (Number(e.expected_duration_minutes) || 120) * 60000);
                const conflict = !!(cur && start < cur.end && end > cur.start);
                return { id: e.id, ref: e.ref, name: e.name, status: e.status, service_type: e.service_type, address: e.address, assigned_employee: e.assigned_employee, start, end, isSelf: false, conflict };
            });
        // Add this inquiry's own (live, possibly-unsaved) visit, highlighted.
        if (cur) {
            rows.push({ id: this.inquiry.id, ref: this.inquiry.ref, name: this.inquiry.name, status: this.status, service_type: this.serviceType, address: this.address, assigned_employee: this.employeeName(this.assignedEmployeeId), start: cur.start, end: cur.end, isSelf: true, conflict: false });
        }
        return rows.sort((a, b) => a.start - b.start);
    },

    employeeName(id) { return id ? (this.employees.find((e) => e.id === id)?.username || '') : ''; },

    get dayOtherCount() { return this.daySchedule.filter((e) => !e.isSelf).length; },
    get dayConflictCount() { return this.daySchedule.filter((e) => e.conflict).length; },

    clock(d) { return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }); },
    dotClass(s) {
        return ({ new: 'bg-blue-400', left_voicemail: 'bg-violet-400', reviewing: 'bg-amber-400', quoted: 'bg-indigo-400', scheduled: 'bg-[#F8C820]', service_performed: 'bg-teal-400', completed: 'bg-emerald-400' })[s] || 'bg-gray-400';
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
        const isEq = this.jobType === 'equipment';
        return {
            status: this.status,
            name: this.fullName,
            assigned_employee_id: this.assignedEmployeeId || null,
            service_type: isEq ? 'equipment' : (this.serviceType || null),
            admin_notes: this.adminNotes,
            address: this.address || null,
            confirmed_date_time: this.confirmedDateTime || null,
            phone: this.phone || null,
            email: this.email || null,
            preferred_contact_method: this.preferredContactMethod,
            zip_code: this.customerZip || null,
            preferred_day: this.customerPreferredDay || null,
            preferred_time: this.customerPreferredTime || null,
            equipment_type: isEq ? ((this.equipmentType === '__other__' ? '' : this.equipmentType) || null) : null,
            equipment_rental_duration: isEq ? (this.equipmentRentalDuration === '' ? null : Number(this.equipmentRentalDuration)) : null,
            equipment_rental_unit: isEq ? (this.equipmentRentalUnit || null) : null,
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

    // Mobile status sheet: close it, then apply the chosen status (same flow as
    // the desktop timeline — saves immediately, with voicemail/cancel prompts).
    pickStatus(newStatus) {
        this.showStatusSheet = false;
        if (newStatus !== this.status) this.quickUpdateStatus(newStatus);
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

    // Debounced address autocomplete. Fires as the admin types; results come
    // from /admin/api/address-suggest (OpenStreetMap, cached + rate-limited).
    onAddressInput() {
        clearTimeout(this._addrTimer);
        const q = (this.address || '').trim();
        if (q.length < 3) { this.addrSuggestions = []; this.addrOpen = false; return; }
        this._addrTimer = setTimeout(() => this.fetchAddressSuggestions(q), 450);
    },

    async fetchAddressSuggestions(q) {
        const seq = ++this._addrSeq;
        this.addrLoading = true;
        try {
            const res = await fetch(`${this.urls.addressSuggest}?q=${encodeURIComponent(q)}`, { headers: window.jsonHeaders() });
            const data = res.ok ? await res.json() : [];
            if (seq !== this._addrSeq) return; // a newer keystroke superseded this one
            this.addrSuggestions = Array.isArray(data) ? data : [];
            this.addrOpen = this.addrSuggestions.length > 0;
        } catch {
            if (seq === this._addrSeq) { this.addrSuggestions = []; this.addrOpen = false; }
        } finally {
            if (seq === this._addrSeq) this.addrLoading = false;
        }
    },

    pickAddress(s) {
        this.address = s.value;
        this.addrSuggestions = [];
        this.addrOpen = false;
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
    category: 'services',  // drill-down: 'services' | 'equipment'
    timelineMode: 'week',  // revenue timeline grouping: 'week' | 'month'
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

    get conversionRate() {
        const leads = this.rangeFiltered.filter((i) => i.status !== 'cancelled').length;
        return leads ? Math.round((this.completedCount / leads) * 100) : 0;
    },

    // --- Services vs Equipment Rental --------------------------------------
    // Equipment rental = legacy service_type 'equipment' OR any saved equipment_type.
    isEquipment(i) { return i.service_type === 'equipment' || !!(i.equipment_type && String(i.equipment_type).trim()); },

    _categoryTotal(pred) {
        const list = this.rangeFiltered.filter(pred);
        return {
            jobs: list.length,
            revenue: list.filter((i) => i.status === 'completed').reduce((s, i) => s + (Number(i.quoted_price) || 0), 0),
        };
    },
    get servicesTotal() { return this._categoryTotal((i) => !this.isEquipment(i)); },
    get equipmentTotal() { return this._categoryTotal((i) => this.isEquipment(i)); },
    get categoryRevenueMax() { return Math.max(1, this.servicesTotal.revenue, this.equipmentTotal.revenue); },

    // Per-item breakdown: jobs in range + revenue collected (completed only).
    _breakdown(pred, keyFn, labelFn) {
        const groups = {};
        this.rangeFiltered.filter(pred).forEach((i) => {
            const k = keyFn(i) || 'other';
            if (!groups[k]) groups[k] = { key: k, label: labelFn(k), jobs: 0, completed: 0, revenue: 0 };
            groups[k].jobs++;
            if (i.status === 'completed') { groups[k].completed++; groups[k].revenue += Number(i.quoted_price) || 0; }
        });
        const rows = Object.values(groups);
        const max = Math.max(1, ...rows.map((r) => r.revenue));
        rows.forEach((r) => { r.pct = Math.round((r.revenue / max) * 100); r.avg = r.completed ? Math.round(r.revenue / r.completed) : 0; });
        return rows.sort((a, b) => b.revenue - a.revenue || b.jobs - a.jobs);
    },
    get serviceRows() {
        const labels = { 'junk-removal': 'Junk Removal', '10yd-dumpster': '10yd Dumpster', '20yd-dumpster': '20yd Dumpster', other: 'Other / Uncategorized' };
        return this._breakdown((i) => !this.isEquipment(i), (i) => i.service_type || 'other', (k) => labels[k] || k.replace(/-/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()));
    },
    get equipmentRows() {
        return this._breakdown((i) => this.isEquipment(i), (i) => (String(i.equipment_type || '').trim() || 'Unspecified Equipment'), (k) => k);
    },
    get activeRows() { return this.category === 'equipment' ? this.equipmentRows : this.serviceRows; },

    // --- Revenue collected timeline (trailing 12 weeks / months) -----------
    get timeline() {
        const now = new Date();
        const periods = [];
        if (this.timelineMode === 'week') {
            for (let w = 11; w >= 0; w--) {
                const start = new Date(now); start.setHours(0, 0, 0, 0); start.setDate(start.getDate() - start.getDay() - w * 7);
                const end = new Date(start); end.setDate(end.getDate() + 7);
                periods.push({ start, end, label: `${start.getMonth() + 1}/${start.getDate()}`, revenue: 0 });
            }
        } else {
            for (let m = 11; m >= 0; m--) {
                const start = new Date(now.getFullYear(), now.getMonth() - m, 1);
                const end = new Date(now.getFullYear(), now.getMonth() - m + 1, 1);
                periods.push({ start, end, label: start.toLocaleDateString(undefined, { month: 'short' }), revenue: 0 });
            }
        }
        this.inquiries.filter((i) => i.status === 'completed' && i.quoted_price).forEach((i) => {
            const d = new Date(i.confirmed_date_time || i.created_at);
            if (isNaN(d.getTime())) return;
            const p = periods.find((p) => d >= p.start && d < p.end);
            if (p) p.revenue += Number(i.quoted_price) || 0;
        });
        const max = Math.max(1, ...periods.map((p) => p.revenue));
        periods.forEach((p) => { p.pct = Math.round((p.revenue / max) * 100); });
        return periods;
    },
    get timelineTotal() { return this.timeline.reduce((s, p) => s + p.revenue, 0); },

    // --- Pipeline (status mix) + payment method ----------------------------
    get statusBreakdown() {
        const order = ['new', 'reviewing', 'quoted', 'scheduled', 'service_performed', 'completed'];
        const labels = { new: 'New', reviewing: 'Reviewing', quoted: 'Quoted', scheduled: 'Scheduled', service_performed: 'Service Performed', completed: 'Completed' };
        const counts = {};
        this.rangeFiltered.forEach((i) => { counts[i.status] = (counts[i.status] || 0) + 1; });
        const max = Math.max(1, ...order.map((s) => counts[s] || 0));
        return order.map((s) => ({ key: s, label: labels[s], count: counts[s] || 0, pct: Math.round(((counts[s] || 0) / max) * 100) }));
    },
    get paymentBreakdown() {
        const groups = {};
        this.rangeFiltered.filter((i) => i.status === 'completed' && i.quoted_price).forEach((i) => {
            const k = i.payment_method || 'Unrecorded';
            if (!groups[k]) groups[k] = { label: k, revenue: 0, count: 0 };
            groups[k].revenue += Number(i.quoted_price) || 0; groups[k].count++;
        });
        const rows = Object.values(groups);
        const max = Math.max(1, ...rows.map((r) => r.revenue));
        rows.forEach((r) => { r.pct = Math.round((r.revenue / max) * 100); });
        return rows.sort((a, b) => b.revenue - a.revenue);
    },

    setRange(r) { this.range = r; this.refreshMap(); },
    refreshMap() { if (this.mapApi) this.mapApi.setInquiries(this.rangeFiltered); },
    money(n) { return Number(n).toLocaleString(); },
    moneyShort(n) { n = Number(n) || 0; return n >= 1000 ? (n / 1000).toFixed(n >= 10000 ? 0 : 1) + 'k' : String(Math.round(n)); },
}));

// ---------------------------------------------------------------------------
// Customer directory — search by phone/email, then per-customer analytics +
// quotes (grouped client-side; one row per phone-last-10, else email).
// ---------------------------------------------------------------------------
Alpine.data('customerLookup', (cfg = {}) => ({
    ...window.adminHelpers,
    inquiries: cfg.inquiries || [],
    detailBase: cfg.detailBase || '',
    query: '',
    selectedKey: '',

    _key(i) {
        const p = (i.phone || '').replace(/\D/g, '').slice(-10);
        if (p) return 'p:' + p;
        const e = (i.email || '').toLowerCase().trim();
        return e ? 'e:' + e : '';
    },

    get customers() {
        const map = {};
        for (const i of this.inquiries) {           // inquiries arrive newest-first
            const key = this._key(i);
            if (!key) continue;
            if (!map[key]) map[key] = { key, name: '', phone: '', email: '', address: '', zip: '', preferred: '', items: [] };
            const c = map[key];
            c.items.push(i);
            if (!c.name && i.name) c.name = i.name;
            if (!c.phone && i.phone) c.phone = i.phone;
            if (!c.email && i.email) c.email = i.email;
            if (!c.address && i.address) c.address = i.address;
            if (!c.zip && i.zip_code) c.zip = i.zip_code;
            if (!c.preferred && i.preferred_contact_method) c.preferred = i.preferred_contact_method;
        }
        return Object.values(map).map((c) => this._enrich(c)).sort((a, b) => b.lastSeen - a.lastSeen);
    },

    _enrich(c) {
        const completed = c.items.filter((i) => i.status === 'completed');
        c.count = c.items.length;
        c.completedCount = completed.length;
        c.revenue = completed.reduce((s, i) => s + (Number(i.quoted_price) || 0), 0);
        c.avg = c.completedCount ? Math.round(c.revenue / c.completedCount) : 0;
        c.outstanding = c.items
            .filter((i) => ['quoted', 'scheduled', 'service_performed'].includes(i.status) && !i.payment_method)
            .reduce((s, i) => s + (Number(i.quoted_price) || 0), 0);
        c.openCount = c.items.filter((i) => !['completed', 'cancelled'].includes(i.status)).length;
        const times = c.items.map((i) => new Date(i.created_at).getTime()).filter((t) => !isNaN(t));
        c.lastSeen = times.length ? Math.max(...times) : 0;
        c.firstSeen = times.length ? Math.min(...times) : 0;
        if (!c.preferred) c.preferred = 'phone';
        return c;
    },

    get results() {
        const q = this.query.trim().toLowerCase();
        const qDigits = q.replace(/\D/g, '');
        const all = this.customers;
        if (q.length < 2) return all.slice(0, 8);   // recent customers when not searching
        return all.filter((c) => {
            const phone = (c.phone || '').replace(/\D/g, '');
            return (qDigits.length >= 3 && phone.includes(qDigits)) || (c.email || '').toLowerCase().includes(q) || (c.name || '').toLowerCase().includes(q);
        }).slice(0, 30);
    },

    select(key) { this.selectedKey = key; },
    get selected() { return this.customers.find((c) => c.key === this.selectedKey) || null; },
    get selectedQuotes() {
        return this.selected ? [...this.selected.items].sort((a, b) => new Date(b.created_at) - new Date(a.created_at)) : [];
    },
    get selectedByService() {
        if (!this.selected) return [];
        const groups = {};
        for (const i of this.selected.items) {
            const eq = i.equipment_type && String(i.equipment_type).trim();
            const label = eq || this.serviceLabel(i.service_type) || 'Other';
            if (!groups[label]) groups[label] = { label, count: 0, revenue: 0 };
            groups[label].count++;
            if (i.status === 'completed') groups[label].revenue += Number(i.quoted_price) || 0;
        }
        const rows = Object.values(groups);
        const max = Math.max(1, ...rows.map((r) => r.revenue));
        rows.forEach((r) => { r.pct = Math.round((r.revenue / max) * 100); });
        return rows.sort((a, b) => b.revenue - a.revenue || b.count - a.count);
    },
    get selectedByStatus() {
        if (!this.selected) return [];
        const order = ['new', 'reviewing', 'quoted', 'scheduled', 'service_performed', 'completed', 'cancelled', 'left_voicemail'];
        const counts = {};
        for (const i of this.selected.items) counts[i.status] = (counts[i.status] || 0) + 1;
        return order.filter((s) => counts[s]).map((s) => ({ key: s, label: this.statusLabel(s), count: counts[s] }));
    },

    rentalLabel(i) { return (i.equipment_rental_duration && i.equipment_rental_unit) ? `${i.equipment_rental_duration} ${i.equipment_rental_unit}` : ''; },
    detailUrl(id) { return (this.detailBase || '').replace('__ID__', id); },
    contact(method) {
        const c = this.selected; if (!c) return;
        if (method === 'email' && c.email) window.location.href = `mailto:${encodeURIComponent(c.email)}`;
        else if (method === 'sms' && c.phone) window.location.href = `sms:${c.phone.replace(/[^\d+]/g, '')}`;
        else if (method === 'tel' && c.phone) window.location.href = `tel:${c.phone.replace(/[^\d+]/g, '')}`;
    },
    print() { window.print(); },
}));

// ---------------------------------------------------------------------------
// Service catalog CRUD.
// ---------------------------------------------------------------------------
Alpine.data('servicesCatalog', (cfg = {}) => ({
    urls: cfg.urls,
    services: cfg.services || [],
    nw: { label: '', price: '', duration: '120', customerVisible: true, instructions: '' },
    error: '',
    editingId: null,
    ed: { label: '', price: '', duration: '', instructions: '' },

    async reload() { try { const r = await fetch(this.urls.index, { headers: window.jsonHeaders() }); if (r.ok) { const d = await r.json(); this.services = d.services || []; } } catch {} },

    async add() {
        this.error = '';
        const r = await fetch(this.urls.store, { method: 'POST', headers: window.jsonHeaders(true), body: JSON.stringify({ label: this.nw.label, default_price: this.nw.price === '' ? null : this.nw.price, default_duration_minutes: this.nw.duration || 120, customer_visible: this.nw.customerVisible, customer_instructions: this.nw.instructions }) });
        if (r.ok) { this.nw = { label: '', price: '', duration: '120', customerVisible: true, instructions: '' }; await this.reload(); }
        else { const d = await r.json().catch(() => ({})); this.error = d.error || 'Failed to add service'; }
    },
    startEdit(s) { this.editingId = s.id; this.ed = { label: s.label, price: s.default_price ?? '', duration: s.default_duration_minutes ?? 120, instructions: s.customer_instructions ?? '' }; },
    cancelEdit() { this.editingId = null; },
    async saveEdit(s) {
        const r = await fetch(this.urls.update.replace('__ID__', s.id), { method: 'PATCH', headers: window.jsonHeaders(true), body: JSON.stringify({ label: this.ed.label, default_price: this.ed.price === '' ? null : this.ed.price, default_duration_minutes: this.ed.duration, customer_instructions: this.ed.instructions }) });
        if (r.ok) { this.editingId = null; await this.reload(); }
    },
    async toggleActive(s) { await fetch(this.urls.update.replace('__ID__', s.id), { method: 'PATCH', headers: window.jsonHeaders(true), body: JSON.stringify({ active: !s.active }) }); await this.reload(); },
    async toggleCustomerVisible(s) { await fetch(this.urls.update.replace('__ID__', s.id), { method: 'PATCH', headers: window.jsonHeaders(true), body: JSON.stringify({ customer_visible: !s.customer_visible }) }); await this.reload(); },
    async remove(s) { if (!confirm(`Permanently delete the "${s.label}" service? This cannot be undone.`)) return; await fetch(this.urls.destroy.replace('__ID__', s.id), { method: 'DELETE', headers: window.jsonHeaders(true) }); await this.reload(); },
    money(n) { return n == null ? '—' : Number(n).toLocaleString(); },
}));

// ---------------------------------------------------------------------------
// Equipment catalog CRUD.
// ---------------------------------------------------------------------------
Alpine.data('equipmentCatalog', (cfg = {}) => ({
    urls: cfg.urls,
    equipment: cfg.equipment || [],
    nw: { name: '', cost: '', daily: '', instructions: '' },
    error: '',
    editingId: null,
    ed: { name: '', cost: '', daily: '', instructions: '' },

    async reload() { try { const r = await fetch(this.urls.index, { headers: window.jsonHeaders() }); if (r.ok) { const d = await r.json(); this.equipment = d.equipment || []; } } catch {} },

    async add() {
        this.error = '';
        const r = await fetch(this.urls.store, { method: 'POST', headers: window.jsonHeaders(true), body: JSON.stringify({ name: this.nw.name, avg_cost_per_hour: this.nw.cost === '' ? null : this.nw.cost, daily_rate: this.nw.daily === '' ? null : this.nw.daily, customer_instructions: this.nw.instructions }) });
        if (r.ok) { this.nw = { name: '', cost: '', daily: '', instructions: '' }; await this.reload(); }
        else { const d = await r.json().catch(() => ({})); this.error = d.error || 'Failed to add equipment'; }
    },
    startEdit(e) { this.editingId = e.id; this.ed = { name: e.name, cost: e.avg_cost_per_hour ?? '', daily: e.daily_rate ?? '', instructions: e.customer_instructions ?? '' }; },
    cancelEdit() { this.editingId = null; },
    async saveEdit(e) {
        const r = await fetch(this.urls.update.replace('__ID__', e.id), { method: 'PATCH', headers: window.jsonHeaders(true), body: JSON.stringify({ name: this.ed.name, avg_cost_per_hour: this.ed.cost === '' ? null : this.ed.cost, daily_rate: this.ed.daily === '' ? null : this.ed.daily, customer_instructions: this.ed.instructions }) });
        if (r.ok) { this.editingId = null; await this.reload(); }
    },
    async toggleActive(e) { await fetch(this.urls.update.replace('__ID__', e.id), { method: 'PATCH', headers: window.jsonHeaders(true), body: JSON.stringify({ active: !e.active }) }); await this.reload(); },
    async toggleCustomerVisible(e) { await fetch(this.urls.update.replace('__ID__', e.id), { method: 'PATCH', headers: window.jsonHeaders(true), body: JSON.stringify({ customer_visible: !e.customer_visible }) }); await this.reload(); },
    async remove(e) { if (!confirm(`Permanently delete the "${e.name}" equipment item? This cannot be undone.`)) return; await fetch(this.urls.destroy.replace('__ID__', e.id), { method: 'DELETE', headers: window.jsonHeaders(true) }); await this.reload(); },
    money(n) { return n == null ? '—' : Number(n).toLocaleString(); },
}));

// ---------------------------------------------------------------------------
// Admin accounts management.
// ---------------------------------------------------------------------------
Alpine.data('adminsManager', (cfg = {}) => ({
    urls: cfg.urls,
    admins: cfg.admins || [],
    nw: { username: '', password: '', role: 'admin' },
    error: '',

    async reload() { try { const r = await fetch(this.urls.index, { headers: window.jsonHeaders() }); if (r.ok) { const d = await r.json(); this.admins = d.admins || []; } } catch {} },

    // Pick a role; prefill the default employee password for convenience.
    setRole(role) {
        this.nw.role = role;
        if (role === 'employee' && !this.nw.password) this.nw.password = 'model123!';
    },

    async create() {
        this.error = '';
        const r = await fetch(this.urls.store, { method: 'POST', headers: window.jsonHeaders(true), body: JSON.stringify({ username: this.nw.username, password: this.nw.password, role: this.nw.role }) });
        if (r.ok) { this.nw = { username: '', password: '', role: 'admin' }; await this.reload(); }
        else { const d = await r.json().catch(() => ({})); this.error = d.error || 'Failed to create account'; }
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

    // Time-picker state (only used in the embedded popup).
    pickedDateKey: '',
    pickedMinutes: null,
    pickDuration: cfg.pickDuration || 120, // visit length (min) — sizes the preview card
    pickLabel: cfg.pickLabel || 'This visit',
    pickInquiryId: cfg.pickInquiryId || '', // hide this quote's saved event (the pick card stands in for it)
    // drag state
    dragMode: null, _grabOffsetMin: 0, _justDragged: false, _durationChanged: false,

    init() {
        if (cfg.initialView && ['month', 'week', 'day', '3day', '5day'].includes(cfg.initialView)) this.viewMode = cfg.initialView;
        if (cfg.initialDate) {
            const d = new Date(cfg.initialDate + 'T00:00');
            if (!isNaN(d.getTime())) this.cur = d.getTime();
        }
        // Pre-place the card if the quote is already scheduled (so it can be dragged).
        if (cfg.pickTime) {
            const [hh, mm] = String(cfg.pickTime).split(':').map(Number);
            if (!isNaN(hh)) {
                this.pickedMinutes = hh * 60 + (mm || 0);
                this.pickedDateKey = cfg.initialDate || this.localKey(this.currentDate);
            }
        }
    },

    // Local YYYY-MM-DD (matches the quote's confirmed_date_time, avoids UTC drift).
    localKey(d) { const p = (n) => String(n).padStart(2, '0'); return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`; },

    // Click the day timeline → snap to the nearest 30-min slot and place the card.
    pickTimeAt(event) {
        if (this.dragMode || this._justDragged) return; // ignore clicks ending a drag
        const m = this._yToMinutes(event.clientY);
        if (m == null) return;
        let total = Math.max(DAY_START_HOUR * 60, Math.min(DAY_END_HOUR * 60 - this.pickDuration, this._snap30(m)));
        this.pickedDateKey = this.localKey(this.currentDate);
        this.pickedMinutes = total;
    },

    // --- Drag to reschedule (move) / extend (resize) ---
    _yToMinutes(clientY) {
        const el = this.$refs.timeline;
        if (!el) return null;
        return DAY_START_HOUR * 60 + ((clientY - el.getBoundingClientRect().top) / HOUR_PX) * 60;
    },
    _snap30(m) { return Math.round(m / 30) * 30; },

    startMove(event) {
        if (this.pickedMinutes == null) return;
        const m = this._yToMinutes(event.clientY);
        this._grabOffsetMin = m != null ? (m - this.pickedMinutes) : 0;
        this.dragMode = 'move';
        if (event.pointerId != null && event.target.setPointerCapture) { try { event.target.setPointerCapture(event.pointerId); } catch (e) {} }
    },
    startResize(event) {
        if (this.pickedMinutes == null) return;
        this.dragMode = 'resize';
        if (event.pointerId != null && event.target.setPointerCapture) { try { event.target.setPointerCapture(event.pointerId); } catch (e) {} }
    },
    onDrag(event) {
        if (!this.dragMode) return;
        const m = this._yToMinutes(event.clientY);
        if (m == null) return;
        if (this.dragMode === 'move') {
            let start = this._snap30(m - this._grabOffsetMin);
            start = Math.max(DAY_START_HOUR * 60, Math.min(DAY_END_HOUR * 60 - this.pickDuration, start));
            this.pickedMinutes = start;
            this.pickedDateKey = this.localKey(this.currentDate);
        } else {
            let end = Math.max(this.pickedMinutes + 30, Math.min(DAY_END_HOUR * 60, this._snap30(m)));
            this.pickDuration = end - this.pickedMinutes;
            this._durationChanged = true;
        }
    },
    endDrag() {
        if (!this.dragMode) return;
        this.dragMode = null;
        this._justDragged = true;
        setTimeout(() => { this._justDragged = false; }, 60);
    },

    // Commit the placed visit to the quote (parent page applies it and closes
    // the popup). targetOrigin '*' avoids host-alias mismatches; the parent
    // validates the message shape.
    applyToQuote() {
        if (this.pickedMinutes == null) return;
        const pad = (n) => String(n).padStart(2, '0');
        const time = `${pad(Math.floor(this.pickedMinutes / 60))}:${pad(this.pickedMinutes % 60)}`;
        const msg = { type: 'calendar-pick', datetime: `${this.pickedDateKey}T${time}` };
        if (this._durationChanged) msg.duration = this.pickDuration; // only override duration if the card was resized
        try { window.parent.postMessage(msg, '*'); } catch (e) { /* not embedded */ }
    },

    // Whether the preview card belongs on the currently-viewed day.
    get pickOnThisDay() { return this.pickedMinutes != null && this.localKey(this.currentDate) === this.pickedDateKey; },

    // Preview card: positioned at the picked time, sized to the visit duration.
    get pickCardStyle() {
        if (this.pickedMinutes == null) return '';
        const top = (this.pickedMinutes - DAY_START_HOUR * 60) * HOUR_PX / 60;
        const height = Math.max(HOUR_PX * 0.5, (this.pickDuration / 60) * HOUR_PX);
        return `position:absolute;top:${top}px;height:${height}px;left:4px;right:4px`;
    },
    minutesToLabel(m) { const pad = (n) => String(n).padStart(2, '0'); return fmtTime12(`${pad(Math.floor(m / 60))}:${pad(m % 60)}`); },
    get pickedTimeLabel() { return this.pickedMinutes == null ? '' : this.minutesToLabel(this.pickedMinutes); },
    get pickedEndLabel() { return this.pickedMinutes == null ? '' : this.minutesToLabel(this.pickedMinutes + this.pickDuration); },
    get pickedLabelFull() {
        if (this.pickedMinutes == null) return '';
        const d = new Date(this.pickedDateKey + 'T00:00');
        return `${d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' })}, ${this.pickedTimeLabel} – ${this.pickedEndLabel}`;
    },

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
        return this.events.filter((e) => e.confirmed_date_time).filter((e) => e.id !== this.pickInquiryId).map((e) => {
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
        if (this.viewMode === '3day' || this.viewMode === '5day') {
            const days = this.rangeDays, a = days[0], b = days[days.length - 1];
            return `${a.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })} – ${b.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })}`;
        }
        const ws = this.weekStart;
        return `${ws.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })} – ${new Date(ws.getTime() + 6 * 86400000).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })}`;
    },
    get totalOnCalendar() { return this.calendarEvents.length; },

    // multi-day range (3-day / 5-day) — starts at the current date, runs forward
    get rangeSize() { return this.viewMode === '3day' ? 3 : this.viewMode === '5day' ? 5 : 1; },
    get rangeDays() {
        const start = new Date(this.cur); start.setHours(0, 0, 0, 0);
        return Array.from({ length: this.rangeSize }, (_, i) => { const d = new Date(start); d.setDate(d.getDate() + i); return d; });
    },

    // week
    get weekStart() { const d = new Date(this.cur); d.setDate(d.getDate() - d.getDay()); d.setHours(0, 0, 0, 0); return d; },
    get weekDays() { const ws = this.weekStart; return Array.from({ length: 7 }, (_, i) => { const d = new Date(ws); d.setDate(d.getDate() + i); return d; }); },

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

    _step(dir) {
        const d = new Date(this.cur);
        if (this.viewMode === 'month') d.setMonth(d.getMonth() + dir);
        else if (this.viewMode === 'week') d.setDate(d.getDate() + 7 * dir);
        else if (this.viewMode === '3day' || this.viewMode === '5day') d.setDate(d.getDate() + this.rangeSize * dir);
        else d.setDate(d.getDate() + dir);
        this.cur = d.getTime();
    },
    prev() { this._step(-1); },
    next() { this._step(1); },
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
                link_label: c.link_label || '',
                link_url: c.link_url || '',
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
            link_label: '',
            link_url: '',
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
            const type = el.getAttribute('data-cms-type');
            if (type === 'list') {
                content[key] = el.value.split(/\r?\n/).map((s) => s.trim()).filter(Boolean);
            } else if (type === 'boolean') {
                content[key] = el.checked;
            } else {
                content[key] = el.value; // Trix keeps this hidden input in sync
            }
        });

        for (const key of Object.keys(this.cardSets)) {
            content[key] = this.cardSets[key].map((c) => ({ icon: c.icon, image: c.image || '', title: c.title, subheader: c.subheader || '', body: c.body, link_label: c.link_label || '', link_url: c.link_url || '' }));
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

// ---------------------------------------------------------------------------
// Payment-link sender — generates (or reuses) a payment link for the current
// quoted price and lets the admin send it to the customer (mailto/sms).
// ---------------------------------------------------------------------------
Alpine.data('paymentSender', (cfg = {}) => ({
    cfg,
    links: cfg.links || [],
    preferred: cfg.preferred === 'email' ? 'email' : 'phone',
    phone: cfg.phone || '',
    email: cfg.email || '',
    name: cfg.name || '',
    sendToContact: false,
    link: '',
    amount: null,
    sending: false,
    copied: false,
    copiedId: '',
    error: '',

    get contactLabel() {
        return (this.preferred === 'email' ? 'Email' : 'Text') + ' payment link';
    },

    init() {
        const active = this.links.find((l) => l.usable);
        if (active) { this.link = active.url; this.amount = active.amount; }
    },

    async send() {
        this.sending = true;
        this.error = '';
        this.copied = false;
        try {
            const res = await fetch(this.cfg.createUrl, { method: 'POST', headers: window.jsonHeaders(true) });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Could not generate the payment link.');
            const l = data.payment_link;
            this.link = l.url;
            this.amount = l.amount;
            const i = this.links.findIndex((x) => x.id === l.id);
            if (i === -1) this.links.unshift(l);
            else this.links[i] = l;
            if (this.sendToContact) this.deliver();
        } catch (e) {
            this.error = e.message || 'Error generating the payment link.';
        } finally {
            this.sending = false;
        }
    },

    // Open the admin's email/SMS app prefilled with the payment link, using the
    // customer's preferred contact method.
    deliver() {
        if (!this.link) return;
        const amt = this.amount != null ? `$${this.money(this.amount)}` : 'your balance';
        const msg = `Hi ${this.name || 'there'}, you can securely pay ${amt} for your quote here: ${this.link}`;
        if (this.preferred === 'email') {
            if (!this.email) { this.error = 'No email address on file for this customer.'; return; }
            window.location.href = `mailto:${encodeURIComponent(this.email)}`
                + `?subject=${encodeURIComponent('Your Payment Link')}&body=${encodeURIComponent(msg)}`;
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

    async copyLink(l) {
        try {
            await navigator.clipboard.writeText(l.url);
            this.copiedId = l.id;
            setTimeout(() => { if (this.copiedId === l.id) this.copiedId = ''; }, 2000);
        } catch {
            this.error = 'Copy failed — open the link and copy manually.';
        }
    },

    async remove(l) {
        const msg = l.paid_at
            ? 'Delete this completed payment record? This cannot be undone.'
            : 'Delete this pending payment link?';
        if (!confirm(msg)) return;
        this.error = '';
        try {
            const res = await fetch(this.cfg.deleteUrl.replace('__ID__', l.id), { method: 'DELETE', headers: window.jsonHeaders(true) });
            if (!res.ok) throw new Error('Could not delete the payment link.');
            this.links = this.links.filter((x) => x.id !== l.id);
            if (this.link && this.link === l.url) this.link = '';
        } catch (e) {
            this.error = e.message || 'Delete failed.';
        }
    },

    money(n) { return Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
    fmt(d) { return d ? new Date(d).toLocaleDateString() : ''; },
}));

// ---------------------------------------------------------------------------
// serviceSignature — touch/mouse signature pad on the employee job sheet.
// Captures the customer's signature when a service is complete and POSTs it to
// the sign endpoint, then reloads to show the signed/completed state.
// ---------------------------------------------------------------------------
Alpine.data('serviceSignature', (cfg = {}) => ({
    signUrl: cfg.signUrl,
    isDrawing: false,
    hasSignature: false,
    submitting: false,
    error: '',
    _canvas: null,

    initPad() {
        const c = this.$refs.canvas;
        if (!c) return;
        // Match the backing store to the displayed size for crisp strokes.
        const r = c.getBoundingClientRect();
        c.width = Math.round(r.width);
        c.height = Math.round(r.height);
        c.getContext('2d').clearRect(0, 0, c.width, c.height);
    },

    _coords(c, e) {
        const rect = c.getBoundingClientRect();
        const sx = c.width / rect.width, sy = c.height / rect.height;
        const cx = e.touches ? e.touches[0].clientX : e.clientX;
        const cy = e.touches ? e.touches[0].clientY : e.clientY;
        return { x: (cx - rect.left) * sx, y: (cy - rect.top) * sy };
    },
    start(e) {
        const c = e.currentTarget;
        this._canvas = c;
        const ctx = c.getContext('2d', { willReadFrequently: true });
        if (!ctx) return;
        this.isDrawing = true;
        this.hasSignature = true;
        const { x, y } = this._coords(c, e);
        ctx.strokeStyle = '#1C1C1C';
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';
        ctx.beginPath();
        ctx.moveTo(x, y);
    },
    move(e) {
        if (!this.isDrawing || !this._canvas) return;
        const ctx = this._canvas.getContext('2d', { willReadFrequently: true });
        const { x, y } = this._coords(this._canvas, e);
        ctx.lineTo(x, y);
        ctx.stroke();
    },
    end() { this.isDrawing = false; },
    clear() {
        const c = this.$refs.canvas;
        if (c) c.getContext('2d').clearRect(0, 0, c.width, c.height);
        this.hasSignature = false;
        this.error = '';
    },
    async submit() {
        if (!this.hasSignature) { this.error = 'Please have the customer sign above.'; return; }
        this.submitting = true;
        this.error = '';
        try {
            const data = this.$refs.canvas.toDataURL('image/png');
            const res = await fetch(this.signUrl, { method: 'POST', headers: window.jsonHeaders(true), body: JSON.stringify({ signature: data }) });
            if (!res.ok) throw new Error('Could not save the signature.');
            window.location.reload();
        } catch (e) {
            this.error = e.message || 'Save failed.';
            this.submitting = false;
        }
    },
}));

// ---------------------------------------------------------------------------
// commentThread — internal/customer-visible notes on a quote. Shared by the
// employee job sheet and the admin detail page (just a different postUrl).
// ---------------------------------------------------------------------------
Alpine.data('commentThread', (cfg = {}) => ({
    postUrl: cfg.postUrl,
    comments: cfg.comments || [],
    body: '',
    customerVisible: false,
    submitting: false,
    error: '',

    async submit() {
        const body = this.body.trim();
        if (!body) { this.error = 'Write a note first.'; return; }
        this.submitting = true;
        this.error = '';
        try {
            const res = await fetch(this.postUrl, {
                method: 'POST',
                headers: window.jsonHeaders(true),
                body: JSON.stringify({ body, customer_visible: this.customerVisible }),
            });
            if (!res.ok) throw new Error('Could not post the note.');
            const d = await res.json();
            if (d.comment) this.comments.push(d.comment);
            this.body = '';
            this.customerVisible = false;
        } catch (e) {
            this.error = e.message || 'Failed to post.';
        } finally {
            this.submitting = false;
        }
    },

    fmt(d) { return d ? new Date(d).toLocaleString([], { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : ''; },
}));

export {};
