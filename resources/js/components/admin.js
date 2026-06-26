// Admin-area Alpine components: sidebar shell, dashboard, inquiry detail,
// status timeline, calendar, catalogs.
import Alpine from 'alpinejs';
import qrcode from 'qrcode-generator';

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
    new: 'New', left_voicemail: 'Left Voicemail', reviewing: 'Reviewing', quoted: 'Quoted', finalize_scheduling: 'Finalize Scheduling',
    scheduled: 'Scheduled', equipment_delivered: 'Equipment Delivered', equipment_picked_up: 'Equipment Picked Up', service_performed: 'Service Performed', completed: 'Completed', cancelled: 'Cancelled',
};
const STATUS_CLASSES = {
    new: 'status-new', left_voicemail: 'status-reviewing', reviewing: 'status-reviewing', quoted: 'status-quoted', finalize_scheduling: 'status-finalize_scheduling',
    scheduled: 'status-scheduled', equipment_delivered: 'status-equipment_delivered', equipment_picked_up: 'status-equipment_picked_up', service_performed: 'status-service_performed', completed: 'status-completed', cancelled: 'status-cancelled',
};
const SERVICE_LABELS = {
    'junk-removal': 'Junk Removal', '10yd-dumpster': '10 Yard Dumpster Rental', '20yd-dumpster': '20 Yard Dumpster Rental',
    equipment: 'Rentals', other: 'Other / Not Sure',
};
// Full US state name → 2-letter code (for parsing a pasted/auto-filled full address).
const US_STATE_ABBR = {
    alabama: 'AL', alaska: 'AK', arizona: 'AZ', arkansas: 'AR', california: 'CA', colorado: 'CO',
    connecticut: 'CT', delaware: 'DE', 'district of columbia': 'DC', florida: 'FL', georgia: 'GA',
    hawaii: 'HI', idaho: 'ID', illinois: 'IL', indiana: 'IN', iowa: 'IA', kansas: 'KS', kentucky: 'KY',
    louisiana: 'LA', maine: 'ME', maryland: 'MD', massachusetts: 'MA', michigan: 'MI', minnesota: 'MN',
    mississippi: 'MS', missouri: 'MO', montana: 'MT', nebraska: 'NE', nevada: 'NV', 'new hampshire': 'NH',
    'new jersey': 'NJ', 'new mexico': 'NM', 'new york': 'NY', 'north carolina': 'NC', 'north dakota': 'ND',
    ohio: 'OH', oklahoma: 'OK', oregon: 'OR', pennsylvania: 'PA', 'rhode island': 'RI', 'south carolina': 'SC',
    'south dakota': 'SD', tennessee: 'TN', texas: 'TX', utah: 'UT', vermont: 'VT', virginia: 'VA',
    washington: 'WA', 'west virginia': 'WV', wisconsin: 'WI', wyoming: 'WY',
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
                : this.countFinalizeScheduling ? 'finalize_scheduling'
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
    get countFinalizeScheduling() { return this.inquiries.filter((i) => i.status === 'finalize_scheduling').length; },
    get countScheduled() { return this.inquiries.filter((i) => i.status === 'scheduled').length; },
    get countServicePerformed() { return this.inquiries.filter((i) => i.status === 'service_performed').length; },
    get countFollowUp() { return this.inquiries.filter((i) => i.status === 'left_voicemail').length; },
    get countEquipmentOut() { return this.inquiries.filter((i) => i.status === 'equipment_delivered').length; },
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
                body: JSON.stringify({ phone: this.nq.phone }),
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
    customerPickup: cfg.customerPickup || null, // pickup the customer requested on a signed agreement
    scheduleEvents: cfg.scheduleEvents || [], // confirmed visits, for the day-schedule panel
    businessName: cfg.businessName || '',     // for the request-details text/email message
    agreementTitle: cfg.agreementTitle || '', // title of the agreement attached to this item (or '')
    agreements: cfg.agreements || [],         // signed/pending agreement links for this quote
    get signedAgreement() { return this.agreements.find((a) => a.signed_at) || null; },
    detailRequests: cfg.detailRequests || [], // customer "request details" links
    detailReq: { url: '', loading: false, error: '', copied: false }, // active link + ui state
    detailSubmission: null, // the customer's submitted details (signature + confirm flags), for review
    lightboxPhoto: '',      // full-screen photo viewer (customer photos)
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
    jobError: false,    // flagged when saving without a service/equipment chosen
    saveError: '',      // why a save was blocked (missing required fields)
    adminNotes: '', status: 'new',
    assignedEmployeeIds: [], pickupAssignedEmployeeIds: [],
    addressStreet: '', addressCity: '', addressState: 'CA',
    confirmedDateTime: '', pickupDateTime: '',
    firstName: '', lastName: '',
    phone: '', email: '', preferredContactMethod: 'phone',
    urgency: 'routine',
    isEditingCustomer: true,   // customer fields are always editable (no read-only toggle)
    quickSchedule: cfg.quickSchedule ?? true,   // Quick Schedule card on/off (Site Content setting)
    isMobile: false,
    // Per-section collapse (mobile only). Completed sections start collapsed; the
    // header stays tappable to reopen. Desktop always shows everything.
    collapsed: { customer: false, job: false, visit: false, payment: false },
    customerPulled: false, // brief confirmation after pulling a prior customer's info
    notifyCustomer: false, // tick to text/email the customer when scheduling the visit
    customerZip: '', customerPreferredDay: '', customerPreferredTime: '',
    serviceType: '', equipmentType: '',
    equipmentRentalDuration: '', equipmentRentalUnit: '',
    expectedDurationValue: '', expectedDurationUnit: 'hours', adminHoursPerDay: '8',
    expectedDurationMinutes: 120,
    pickupDurationValue: 1, pickupDurationUnit: 'hours', pickupDurationMinutes: 60,
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
    showPickupCalendarModal: false,
    showStatusSheet: false, // mobile status picker
    showQuickNav: false,    // mobile jump-to-section menu
    showOtherActions: false, // mobile other-actions menu (delivered / voicemail / cancel)
    statusChoices: ['new', 'reviewing', 'quoted', 'finalize_scheduling', 'scheduled', 'service_performed', 'completed', 'left_voicemail', 'cancelled'],

    init() {
        this.hydrate(this.inquiry);
        // Surface an existing "request details" link + the customer's submission (if any).
        const usable = this.detailRequests.find((d) => d.usable);
        if (usable) this.detailReq.url = usable.url;
        this.detailSubmission = this.detailRequests.find((d) => d.signed_at) || null;
        // On mobile, collapse sections that are already complete (re-openable). The
        // collapse only takes effect on mobile (see sectionOpen); desktop shows all.
        const mq = window.matchMedia('(max-width: 639px)');
        this.isMobile = mq.matches;
        mq.addEventListener('change', (e) => { this.isMobile = e.matches; });
        const done = this.sectionDone;
        for (const s of ['customer', 'job', 'visit', 'payment']) this.collapsed[s] = !!done[s];
        // Keep expected_duration_minutes in sync with the hrs/days editor.
        this.$watch('expectedDurationValue', () => this.syncDuration());
        this.$watch('expectedDurationUnit', () => this.syncDuration());
        this.$watch('pickupDurationValue', () => this.syncPickupDuration());
        this.$watch('pickupDurationUnit', () => this.syncPickupDuration());
        // Apply a visit placed in the day-calendar popup (iframe). Validate the
        // message shape (a YYYY-MM-DDTHH:MM datetime) rather than the exact origin
        // so host aliases (127.0.0.1 vs localhost) can't silently drop it.
        window.addEventListener('message', (e) => {
            const d = e.data;
            if (!d || d.type !== 'calendar-pick' || !/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(d.datetime || '')) return;
            const mins = Number(d.duration);
            // Assignees chosen in the popup also set the assignment (when any are selected;
            // clearing to "Everyone" leaves the current assignment untouched).
            const assignees = Array.isArray(d.assignees) ? d.assignees.filter(Boolean) : null;
            if (d.target === 'pickup') {
                this.pickupDateTime = d.datetime;
                if (mins > 0) { this.pickupDurationUnit = 'hours'; this.pickupDurationValue = Math.round((mins / 60) * 100) / 100; }
                if (assignees && assignees.length) this.pickupAssignedEmployeeIds = assignees;
                this.showPickupCalendarModal = false;
            } else {
                this.confirmedDateTime = d.datetime;
                if (mins > 0) { this.expectedDurationUnit = 'hours'; this.expectedDurationValue = Math.round((mins / 60) * 100) / 100; }
                if (assignees && assignees.length) this.assignedEmployeeIds = assignees;
                this.showCalendarModal = false; // saving in the calendar closes the popup
            }
        });
    },

    syncDuration() {
        const qty = Number(this.expectedDurationValue);
        if (!qty || qty <= 0) return;
        this.expectedDurationMinutes = this.expectedDurationUnit === 'days'
            ? Math.round(qty * 8 * 60) : Math.round(qty * 60);
    },
    syncPickupDuration() {
        const qty = Number(this.pickupDurationValue);
        if (!qty || qty <= 0) return;
        this.pickupDurationMinutes = this.pickupDurationUnit === 'days'
            ? Math.round(qty * 8 * 60) : Math.round(qty * 60);
    },

    hydrate(inq) {
        this.inquiry = inq;
        this.adminNotes = inq.admin_notes || '';
        this.status = inq.status;
        this.assignedEmployeeIds = (inq.assigned_employee_ids && inq.assigned_employee_ids.length) ? [...inq.assigned_employee_ids] : (inq.assigned_employee_id ? [inq.assigned_employee_id] : []);
        this.pickupAssignedEmployeeIds = (inq.pickup_assigned_employee_ids && inq.pickup_assigned_employee_ids.length) ? [...inq.pickup_assigned_employee_ids] : (inq.pickup_assigned_employee_id ? [inq.pickup_assigned_employee_id] : []);
        // Structured address parts. Back-compat: an old record with only a combined
        // `address` (no parts) prefills Street so it's preserved and re-saves cleanly.
        this.addressStreet = inq.address_street || '';
        this.addressCity = inq.address_city || '';
        this.addressState = inq.address_state || 'CA';
        if (!this.addressStreet && !this.addressCity && inq.address) this.addressStreet = inq.address;
        this.confirmedDateTime = inq.confirmed_date_time || '';
        // No scheduled visit yet → pre-fill the date with the customer's next preferred
        // day (or tomorrow if none). Date only — no time, so it isn't treated/saved as a
        // confirmed slot until the admin actually picks a time (see buildBody).
        if (!this.confirmedDateTime) {
            const day = this.getNextTwoOccurrences(inq.preferred_day || '')[0] || this._tomorrowKey();
            this.confirmedDateTime = `${day}T`;
        }
        this.pickupDateTime = inq.pickup_date_time || '';
        this.pickupDurationMinutes = inq.pickup_duration_minutes ?? 60;
        this.pickupDurationUnit = 'hours';
        this.pickupDurationValue = Math.round((this.pickupDurationMinutes / 60) * 100) / 100;
        // Single stored name → first word is the first name, the rest is the last name.
        const nameParts = (inq.name || '').trim().split(/\s+/).filter(Boolean);
        this.firstName = nameParts.shift() || '';
        this.lastName = nameParts.join(' ');
        this.phone = inq.phone || '';
        this.email = inq.email || '';
        this.preferredContactMethod = inq.preferred_contact_method || 'phone';
        this.urgency = inq.urgency || 'routine';
        this.customerZip = inq.zip_code || '';
        this.customerPreferredDay = inq.preferred_day || '';
        this.customerPreferredTime = inq.preferred_time || '';
        this.serviceType = inq.service_type || '';
        this.equipmentType = inq.equipment_type || '';
        // Rentals = the legacy service_type === 'equipment' (or any saved equipment_type);
        // 'help-me-decide' = the customer asked us to decide; everything else is a Service.
        this.jobType = inq.service_type === 'help-me-decide' ? 'help'
            : ((inq.service_type === 'equipment' || inq.equipment_type) ? 'equipment' : 'service');
        this.quotedPrice = inq.quoted_price ?? '';
        this.equipmentRentalDuration = inq.equipment_rental_duration ?? '';
        this.equipmentRentalUnit = inq.equipment_rental_unit || 'hours';   // Hours is the default

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
        // Visit duration = the employee's time on site. Kept INDEPENDENT of the
        // equipment rental length (equipment_rental_duration) — they're unrelated.
        this.expectedDurationMinutes = inq.expected_duration_minutes ?? 120;
        this.expectedDurationValue = (inq.expected_duration_minutes ?? 120) / 60;
        this.expectedDurationUnit = 'hours';

        // Snapshot the saved state so we can detect unsaved edits (dirty).
        this.baseline = JSON.stringify(this.buildBody());
    },

    // True when the form differs from the last saved/loaded state.
    get dirty() { return this.baseline !== '' && JSON.stringify(this.buildBody()) !== this.baseline; },

    get isEquipment() { return this.jobType === 'equipment'; },
    get isHelp() { return this.jobType === 'help'; },

    get fullName() { return [this.firstName.trim(), this.lastName.trim()].filter(Boolean).join(' '); },

    // Composed address from the structured parts — every `this.address` reader uses this.
    get address() {
        const tail = [this.addressState, this.customerZip].map((s) => (s || '').trim()).filter(Boolean).join(' ');
        return [this.addressStreet, this.addressCity, tail].map((s) => (s || '').trim()).filter(Boolean).join(', ');
    },

    // Pill toggle. Switching to Service ensures a valid catalog selection and
    // seeds the visit duration from that service's default.
    setJobType(type) {
        this.jobError = false;
        if (this.jobType === type) return;
        this.jobType = type;
        if (type === 'service') {
            if (!this.serviceType || this.serviceType === 'equipment' || this.serviceType === 'help-me-decide') {
                this.serviceType = this.serviceCatalog[0]?.key || '';
            }
            this.applyServiceDefaultDuration();
        } else if (type === 'help') {
            this.serviceType = 'help-me-decide';
            this.equipmentType = '';
        }
    },

    // A service (or a real equipment type) must be chosen for the quote to save.
    get jobSelected() {
        return this.isEquipment ? !!(this.equipmentType && this.equipmentType !== '__other__') : !!this.serviceType;
    },

    onServiceChange() { this.jobError = false; this.saveError = ''; this.applyServiceDefaultDuration(); },

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
        // Prefer the prior order's structured parts; fall back to its combined address.
        if (m.address_street || m.address_city) {
            this.addressStreet = (m.address_street || '').trim();
            this.addressCity = (m.address_city || '').trim();
            if (m.address_state) this.addressState = m.address_state;
        } else if (m.address && m.address.trim()) {
            this.addressStreet = m.address.trim();
        }
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

    // --- Mobile quick-nav: section completeness + smooth scroll -------------
    get sectionDone() {
        return {
            customer: !!(this.fullName && this.phone && this.addressStreet && this.addressCity && this.customerZip),
            job: !!(this.addressStreet && this.addressCity && this.customerZip) && (this.isEquipment
                ? !!(this.equipmentType && this.equipmentType !== '__other__' && this.equipmentRentalDuration)
                : !!this.serviceType),
            visit: this.hasConfirmedSlot && this.assignedEmployeeIds.length > 0,   // unassigned ⇒ not complete
            payment: !!(this.quotedPrice !== '' && this.quotedPrice != null && (this.paymentMethod && (this.paymentMethod !== 'Other' || this.paymentMethodOther.trim()))),
        };
    },
    // Condensed one-line summaries shown in a section header while it's collapsed.
    get sectionSummary() {
        const join = (parts) => parts.map((p) => (p == null ? '' : String(p).trim())).filter(Boolean).join(' · ');
        const price = (this.quotedPrice !== '' && this.quotedPrice != null) ? '$' + Number(this.quotedPrice).toLocaleString() : '';
        const duration = this.expectedDurationValue ? `${this.expectedDurationValue} ${this.expectedDurationUnit}` : '';
        // Equipment rentals: include the scheduled pickup date/time when set.
        let pickup = '';
        if (this.isEquipment && this.datePart(this.pickupDateTime) && this.timePart(this.pickupDateTime)) {
            const pd = new Date(this.pickupDateTime);
            if (!isNaN(pd.getTime())) pickup = 'Pickup: ' + pd.toLocaleString(undefined, { weekday: 'short', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
        }
        return {
            customer: join([this.fullName || '(no name)', this.phone, this.email]),
            job: join([this.isEquipment ? (this.equipmentType || 'Equipment rental') : this.getServiceLabel(this.serviceType), this.address]),
            visit: join([this.visitWhenLabel || 'Not scheduled', duration, this.employeeNames(this.assignedEmployeeIds), pickup]),
            payment: join([price || 'No price set', this.paymentMethod === 'Other' ? this.paymentMethodOther : this.paymentMethod]),
        };
    },
    scrollToSection(id) {
        this.showQuickNav = false;
        const el = document.getElementById(id);
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },
    // Has the visit duration been changed from what's saved (Final Review)?
    get durationChanged() {
        const saved = Number(this.inquiry.expected_duration_minutes) || 120;
        return Number(this.expectedDurationMinutes) !== saved;
    },

    // Payment received + arrival/departure documented (and not already closed) → ready to complete.
    get readyToComplete() {
        return !!(this.inquiry.arrived_at && this.inquiry.departed_at && this.paymentMethod && !['completed', 'cancelled'].includes(this.status));
    },

    // A section's body is visible on desktop always, and on mobile unless collapsed.
    sectionOpen(s) { return !this.isMobile || !this.collapsed[s]; },
    toggleSection(s) { if (this.isMobile) this.collapsed[s] = !this.collapsed[s]; },

    // --- Schedule the visit + optionally notify the customer ----------------
    get hasConfirmedSlot() { return !!(this.datePart(this.confirmedDateTime) && this.timePart(this.confirmedDateTime)); },
    get canSchedule() { return this.hasConfirmedSlot && ['new', 'reviewing', 'quoted', 'left_voicemail'].includes(this.status); },

    // --- Quick Schedule (expedited new-quote card on mobile) ----------------
    get showQuickScheduleCard() { return this.quickSchedule && this.isMobile && ['new', 'reviewing', 'quoted', 'left_voicemail'].includes(this.status); },
    get quickScheduleReady() {
        const jobOk = this.isEquipment ? (!!this.equipmentType && this.equipmentType !== '__other__') : !!this.serviceType;
        return !!this.phone && this.canRequestDetails && jobOk;
    },

    // --- "Request details" link (customer self-service confirmation form) ----
    // Needs a concrete slot + price so there's something for the customer to confirm.
    get canRequestDetails() { return this.hasConfirmedSlot && this.quotedPrice !== '' && this.quotedPrice != null && Number(this.quotedPrice) > 0; },
    async requestDetails() {
        this.detailReq.error = '';
        if (!this.canRequestDetails) {
            this.detailReq.error = 'Set a visit date/time and a quoted price first.';
            this.scrollToSection('sec-visit');
            return;
        }
        if (this.dirty) { const ok = await this.save(); if (!ok) return; }   // server validates saved values
        this.detailReq.loading = true;
        try {
            const res = await fetch(this.urls.detailRequest, { method: 'POST', headers: window.jsonHeaders(true) });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Could not create the link.');
            this.detailReq.url = data.detail_request.url;
            // Same one-tap flow as "Send ETA": open the messaging app with the link.
            this.sendDetailLink();
        } catch (e) {
            this.detailReq.error = e.message || 'Could not create the link.';
        } finally {
            this.detailReq.loading = false;
        }
    },
    async copyDetailLink() {
        if (!this.detailReq.url) return;
        try {
            await navigator.clipboard.writeText(this.detailReq.url);
            this.detailReq.copied = true;
            setTimeout(() => { this.detailReq.copied = false; }, 2000);
        } catch { this.detailReq.error = 'Copy failed — open the link and copy manually.'; }
    },
    // Opens the device's messaging app with a pre-filled message (same workflow as Send ETA).
    sendDetailLink() {
        if (!this.detailReq.url) return;
        const msg = `Hi ${this.firstName || 'there'}, this is ${this.businessName}. Please confirm your service details and sign here: ${this.detailReq.url}`;
        if (this.preferredContactMethod === 'email') {
            if (!this.email) { this.detailReq.error = 'No email on file for this customer.'; return; }
            window.location.href = `mailto:${encodeURIComponent(this.email)}?subject=${encodeURIComponent('Confirm your service details')}&body=${encodeURIComponent(msg)}`;
        } else {
            if (!this.phone) { this.detailReq.error = 'No phone on file for this customer.'; return; }
            window.location.href = `sms:${this.phone.replace(/[^\d+]/g, '')}?body=${encodeURIComponent(msg)}`;
        }
    },
    detailFmt(d) { return d ? new Date(d).toLocaleString() : ''; },
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
        const assignee = encodeURIComponent(this.assignedEmployeeIds.join(','));
        const aName = encodeURIComponent(this.employeeNames(this.assignedEmployeeIds));
        return `${this.urls.calendarEmbed}?date=${date}&time=${time}&duration=${dur}&label=${label}&exclude=${exclude}&target=visit&assignee=${assignee}&assignee_name=${aName}`;
    },
    get pickupCalendarEmbedUrl() {
        const date = encodeURIComponent(this.datePart(this.pickupDateTime) || '');
        const time = encodeURIComponent(this.timePart(this.pickupDateTime) || '');
        const dur = encodeURIComponent(this.pickupDurationMinutes || 60);
        const label = encodeURIComponent((this.fullName || this.inquiry.name || 'Pickup') + ' — pickup');
        const exclude = encodeURIComponent(this.inquiry.id || '');
        const assignee = encodeURIComponent(this.pickupAssignedEmployeeIds.join(','));
        const aName = encodeURIComponent(this.employeeNames(this.pickupAssignedEmployeeIds));
        return `${this.urls.calendarEmbed}?date=${date}&time=${time}&duration=${dur}&label=${label}&exclude=${exclude}&target=pickup&assignee=${assignee}&assignee_name=${aName}`;
    },

    get currentVisitWindow() {
        if (!this.confirmedDateTime) return null;
        const start = new Date(this.confirmedDateTime);
        if (isNaN(start.getTime())) return null;
        const mins = Number(this.expectedDurationMinutes) || 120;
        return { start, end: new Date(start.getTime() + mins * 60000) };
    },
    get pickupBeforeVisit() {
        if (!this.isEquipment) return false;
        const v = new Date(this.confirmedDateTime), p = new Date(this.pickupDateTime);
        return !isNaN(v.getTime()) && !isNaN(p.getTime()) && p < v;
    },

    // Estimate the pickup from the delivery time + requested rental duration. Hours
    // are counted within a 7am–5pm window — past 5pm rolls to 7am the next day.
    _addRentalTime(start, qty, unit) {
        const WORK_START = 7, WORK_END = 17;
        const cursor = new Date(start);
        if (unit === 'days') { cursor.setDate(cursor.getDate() + qty); return cursor; }
        let remaining = qty * 60; // minutes
        let guard = 0;
        while (remaining > 0 && guard++ < 1000) {
            const endOfDay = new Date(cursor); endOfDay.setHours(WORK_END, 0, 0, 0);
            const minsUntilEnd = (endOfDay - cursor) / 60000;
            if (minsUntilEnd <= 0) { cursor.setDate(cursor.getDate() + 1); cursor.setHours(WORK_START, 0, 0, 0); continue; }
            if (remaining <= minsUntilEnd) { cursor.setTime(cursor.getTime() + remaining * 60000); remaining = 0; }
            else { remaining -= minsUntilEnd; cursor.setDate(cursor.getDate() + 1); cursor.setHours(WORK_START, 0, 0, 0); }
        }
        return cursor;
    },
    get estimatedPickup() {
        if (!this.isEquipment || this.datePart(this.pickupDateTime)) return null;       // only when not set
        if (!this.datePart(this.confirmedDateTime) || !this.timePart(this.confirmedDateTime)) return null; // need a delivery date+time
        const start = new Date(this.confirmedDateTime);
        const qty = Number(this.equipmentRentalDuration);
        if (isNaN(start.getTime()) || !qty || qty <= 0) return null;
        const end = this._addRentalTime(start, qty, this.equipmentRentalUnit || 'hours');
        const p = (n) => String(n).padStart(2, '0');
        return {
            date: `${end.getFullYear()}-${p(end.getMonth() + 1)}-${p(end.getDate())}`,
            time24: `${p(end.getHours())}:${p(end.getMinutes())}`,
            label: end.toLocaleString(undefined, { weekday: 'short', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }),
        };
    },
    // Show the visit-derived recommendation whenever it's computable — even alongside
    // a customer-requested pickup — so it stays in sync as the visit date/time changes.
    get showEstimatedPickup() { return !!this.estimatedPickup; },
    applyEstimatedPickup() {
        const e = this.estimatedPickup;
        if (e) this.pickupDateTime = `${e.date}T${e.time24}`;
    },
    get currentPickupWindow() {
        if (!this.pickupDateTime) return null;
        const start = new Date(this.pickupDateTime);
        if (isNaN(start.getTime())) return null;
        const mins = Number(this.pickupDurationMinutes) || 60;
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
                // Conflict only if a shared assignee is double-booked at the same time.
                const otherIds = (e.assigned_employee_ids && e.assigned_employee_ids.length) ? e.assigned_employee_ids : (e.assigned_employee_id ? [e.assigned_employee_id] : []);
                const samePerson = otherIds.some((id) => this.assignedEmployeeIds.includes(id));
                const conflict = !!(cur && samePerson && start < cur.end && end > cur.start);
                return { id: e.id, ref: e.ref, name: e.name, status: e.status, service_type: e.service_type, address: e.address, assigned_employee: e.assigned_employee, assignee_ids: otherIds, start, end, isSelf: false, conflict };
            });
        // Add this inquiry's own (live, possibly-unsaved) visit, highlighted.
        if (cur) {
            rows.push({ id: this.inquiry.id, ref: this.inquiry.ref, name: this.inquiry.name, status: this.status, service_type: this.serviceType, address: this.address, assigned_employee: this.employeeNames(this.assignedEmployeeIds), assignee_ids: [...this.assignedEmployeeIds], start: cur.start, end: cur.end, isSelf: true, conflict: false });
        }
        return rows.sort((a, b) => a.start - b.start);
    },

    employeeName(id) { return id ? (this.employees.find((e) => e.id === id)?.username || '') : ''; },
    employeeNames(ids) { return (ids || []).map((id) => this.employeeName(id)).filter(Boolean).join(', '); },
    toggleAssignee(field, id) { const a = this[field]; const i = a.indexOf(id); if (i === -1) a.push(id); else a.splice(i, 1); },
    assigneeChecked(field, id) { return this[field].includes(id); },

    get dayOtherCount() { return this.daySchedule.filter((e) => !e.isSelf).length; },
    get dayConflictCount() { return this.daySchedule.filter((e) => e.conflict).length; },

    get pickupDaySchedule() {
        const key = this.datePart(this.pickupDateTime);
        if (!key) return [];
        const cur = this.currentPickupWindow;
        const rows = this.scheduleEvents
            .filter((e) => e.id !== this.inquiry.id && e.confirmed_date_time && this.datePart(e.confirmed_date_time) === key)
            .map((e) => {
                const start = new Date(e.confirmed_date_time);
                const end = new Date(start.getTime() + (Number(e.expected_duration_minutes) || 120) * 60000);
                // Only a conflict if the same person is double-booked at the same time.
                const otherIds = (e.assigned_employee_ids && e.assigned_employee_ids.length) ? e.assigned_employee_ids : (e.assigned_employee_id ? [e.assigned_employee_id] : []);
                const samePerson = otherIds.some((id) => this.pickupAssignedEmployeeIds.includes(id));
                const conflict = !!(cur && samePerson && start < cur.end && end > cur.start);
                return { id: e.id, ref: e.ref, name: e.name, status: e.status, service_type: e.service_type, address: e.address, assigned_employee: e.assigned_employee, assignee_ids: otherIds, start, end, isSelf: false, conflict };
            });
        if (cur) {
            rows.push({ id: this.inquiry.id, ref: this.inquiry.ref, name: this.inquiry.name, status: this.status, service_type: this.serviceType, address: this.address, assigned_employee: this.employeeNames(this.pickupAssignedEmployeeIds), assignee_ids: [...this.pickupAssignedEmployeeIds], start: cur.start, end: cur.end, isSelf: true, conflict: false });
        }
        // Surface this rental's own drop-off (delivery) visit when it lands on the same
        // day, so the gap between drop-off and pickup is visible. It rides in the pickup's
        // column(s) as a reference marker — not draggable, not a link.
        const vis = this.currentVisitWindow;
        if (vis && this.datePart(this.confirmedDateTime) === key) {
            rows.push({ id: this.inquiry.id + '-delivery', ref: this.inquiry.ref, name: this.inquiry.name, status: this.status, service_type: this.serviceType, address: this.address, assigned_employee: this.employeeNames(this.assignedEmployeeIds), assignee_ids: [...this.pickupAssignedEmployeeIds], start: vis.start, end: vis.end, isSelf: false, isDelivery: true, conflict: false });
        }
        return rows.sort((a, b) => a.start - b.start);
    },
    get pickupDayOtherCount() { return this.pickupDaySchedule.filter((e) => !e.isSelf && !e.isDelivery).length; },
    get pickupDayConflictCount() { return this.pickupDaySchedule.filter((e) => e.conflict).length; },

    // Group a day-schedule list into one column per assignee (the job's assignees
    // first, Unassigned last) — a row with multiple assignees appears in each of
    // their columns. When the job has assignees (filterIds), narrow to those columns.
    _assigneeColumns(rows, filterIds) {
        const map = new Map();
        for (const r of rows) {
            const keys = (r.assignee_ids && r.assignee_ids.length) ? r.assignee_ids : ['__unassigned__'];
            for (const key of keys) {
                if (!map.has(key)) {
                    map.set(key, { id: key, name: key === '__unassigned__' ? 'Unassigned' : (this.employeeName(key) || 'Employee'), isUnassigned: key === '__unassigned__', hasSelf: false, rows: [] });
                }
                const col = map.get(key);
                col.rows.push(r);
                if (r.isSelf) col.hasSelf = true;
            }
        }
        let cols = [...map.values()].sort((a, b) => {
            if (a.hasSelf !== b.hasSelf) return a.hasSelf ? -1 : 1;
            if (a.isUnassigned !== b.isUnassigned) return a.isUnassigned ? 1 : -1;
            return a.name.localeCompare(b.name);
        });
        if (filterIds && filterIds.length) cols = cols.filter((c) => filterIds.includes(c.id));
        // Always show the timeline even on an empty day: seed empty lane(s) for the
        // assigned employee(s), or a single Unassigned lane when none are assigned.
        if (cols.length === 0) {
            cols = (filterIds && filterIds.length)
                ? filterIds.map((id) => ({ id, name: this.employeeName(id) || 'Employee', isUnassigned: false, hasSelf: false, rows: [] }))
                : [{ id: '__unassigned__', name: 'Unassigned', isUnassigned: true, hasSelf: false, rows: [] }];
        }
        return cols;
    },
    get dayScheduleColumns() { return this._assigneeColumns(this.daySchedule, this.assignedEmployeeIds); },
    get pickupDayScheduleColumns() { return this._assigneeColumns(this.pickupDaySchedule, this.pickupAssignedEmployeeIds); },

    clock(d) { return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }); },

    // Day-schedule preview timeline (a compact 6am–5pm window so gaps are visible).
    panelStartHour: 6,
    panelEndHour: 17,
    panelHourPx: 30,
    get panelHours() { const h = []; for (let x = this.panelStartHour; x <= this.panelEndHour; x++) h.push(x); return h; },
    panelHourLabel(h) { const hr = h % 12 === 0 ? 12 : h % 12; return hr + (h >= 12 ? 'p' : 'a'); },
    panelTop(ev) {
        const m = ev.start.getHours() * 60 + ev.start.getMinutes();
        return Math.max(0, (m - this.panelStartHour * 60) * this.panelHourPx / 60);
    },
    panelHeight(ev) {
        const s = ev.start.getHours() * 60 + ev.start.getMinutes();
        const e = ev.end.getHours() * 60 + ev.end.getMinutes();
        const h = (Math.min(e, this.panelEndHour * 60) - Math.max(s, this.panelStartHour * 60)) * this.panelHourPx / 60;
        return Math.max(14, h);
    },

    // Click an empty slot on the preview timeline to drop the visit/pickup there
    // (sets the time; assigns the clicked employee if none is set yet).
    minuteToTime(min) {
        const m = Math.max(this.panelStartHour * 60, Math.min(this.panelEndHour * 60 - 15, min));
        return `${String(Math.floor(m / 60)).padStart(2, '0')}:${String(m % 60).padStart(2, '0')}`;
    },
    placeVisitAt(e, col, kind) {
        // Ignore the drag-release "click" and clicks that land on an existing event card.
        if (this.panelDragged || e.target.closest('a')) return;
        const rect = e.currentTarget.getBoundingClientRect();
        const min = this.panelStartHour * 60 + Math.round((e.clientY - rect.top) / this.panelHourPx * 60 / 15) * 15;
        const field = kind === 'pickup' ? 'pickupAssignedEmployeeIds' : 'assignedEmployeeIds';
        if (!col.isUnassigned && this[field].length === 0) this[field] = [col.id];
        if (kind === 'pickup') this.setPickupTime(this.minuteToTime(min)); else this.setConfirmedTime(this.minuteToTime(min));
    },

    // Drag "This visit"/"This pickup" up or down the preview timeline to reschedule
    // its start time (snaps to 15-min steps; the date and duration are unchanged).
    panelDrag: null,
    panelDragged: false,
    startPanelDrag(ev, kind, e) {
        if (!ev.isSelf || (e.button != null && e.button !== 0)) return;
        const startMin = ev.start.getHours() * 60 + ev.start.getMinutes();
        this.panelDrag = { kind, pointerId: e.pointerId, startClientY: e.clientY, origStartMin: startMin, moved: false };
        document.body.style.userSelect = 'none';
    },
    movePanelDrag(e) {
        const d = this.panelDrag;
        if (!d || e.pointerId !== d.pointerId) return;
        const deltaMin = Math.round((e.clientY - d.startClientY) / this.panelHourPx * 60 / 15) * 15;
        if (deltaMin !== 0) d.moved = true;
        const v = this.minuteToTime(d.origStartMin + deltaMin);
        if (d.kind === 'pickup') this.setPickupTime(v); else this.setConfirmedTime(v);
    },
    endPanelDrag() {
        if (!this.panelDrag) return;
        // Suppress the click the browser fires after a real drag so it doesn't re-place the visit.
        if (this.panelDrag.moved) { this.panelDragged = true; setTimeout(() => { this.panelDragged = false; }, 0); }
        this.panelDrag = null;
        document.body.style.userSelect = '';
    },
    dotClass(s) {
        return ({ new: 'bg-blue-400', left_voicemail: 'bg-violet-400', reviewing: 'bg-amber-400', quoted: 'bg-indigo-400', finalize_scheduling: 'bg-pink-500', scheduled: 'bg-[#F8C820]', equipment_delivered: 'bg-cyan-500', equipment_picked_up: 'bg-sky-500', service_performed: 'bg-teal-400', completed: 'bg-emerald-400' })[s] || 'bg-gray-400';
    },

    getServiceLabel(key) { return this.serviceLabel(key) || 'Not specified'; },

    // Accepts one or more comma-separated day names ("Monday" / "Monday, Wednesday")
    // and returns the upcoming dates for each, de-duplicated and sorted ascending.
    getNextTwoOccurrences(dayName) {
        const map = { sunday: 0, monday: 1, tuesday: 2, wednesday: 3, thursday: 4, friday: 5, saturday: 6 };
        const names = String(dayName || '').split(',').map((s) => s.trim().toLowerCase()).filter(Boolean);
        const out = [];
        for (const name of names) {
            const target = map[name];
            if (target === undefined) continue;
            const d = new Date(); d.setHours(0, 0, 0, 0);
            let add = (target - d.getDay() + 7) % 7; if (add === 0) add = 7;
            d.setDate(d.getDate() + add);
            for (let i = 0; i < 2; i++) { out.push(d.toISOString().split('T')[0]); d.setDate(d.getDate() + 7); }
        }
        return [...new Set(out)].sort();
    },
    // Next `count` upcoming dates (from tomorrow) that fall on one of the customer's
    // preferred days (comma-separated). Empty when no preferred day is set.
    recommendedDates(count = 3) {
        const map = { sunday: 0, monday: 1, tuesday: 2, wednesday: 3, thursday: 4, friday: 5, saturday: 6 };
        const targets = String(this.customerPreferredDay || '').split(',')
            .map((s) => map[s.trim().toLowerCase()]).filter((n) => n !== undefined);
        if (!targets.length) return [];
        const out = []; const base = new Date(); base.setHours(0, 0, 0, 0);
        const p = (x) => String(x).padStart(2, '0');
        for (let i = 1; out.length < count && i <= 90; i++) {
            const d = new Date(base); d.setDate(base.getDate() + i);
            if (targets.includes(d.getDay())) out.push(`${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`);
        }
        return out;
    },
    dayLabel(dateStr) {
        return new Date(dateStr + 'T00:00').toLocaleDateString(undefined, { weekday: 'short', month: 'numeric', day: 'numeric' });
    },

    // Multi-select chips: toggle a value within a comma-separated string field,
    // re-ordered to the canonical day/time order (unknown legacy values kept).
    togglePref(field, value) {
        const set = new Set(String(this[field] || '').split(',').map((s) => s.trim()).filter(Boolean));
        set.has(value) ? set.delete(value) : set.add(value);
        const order = field.toLowerCase().includes('day')
            ? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']
            : ['Morning (8am - 12pm)', 'Afternoon (12pm - 5pm)', 'Evening (5pm - 8pm)'];
        const ordered = order.filter((v) => set.has(v));
        const extras = [...set].filter((v) => !order.includes(v));
        this[field] = [...ordered, ...extras].join(', ');
    },
    prefHas(field, value) {
        return String(this[field] || '').split(',').map((s) => s.trim()).includes(value);
    },
    _tomorrowKey() { const d = new Date(); d.setDate(d.getDate() + 1); const p = (x) => String(x).padStart(2, '0'); return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`; },

    buildBody(overrides = {}) {
        const isEq = this.jobType === 'equipment';
        return {
            status: this.status,
            name: this.fullName,
            assigned_employee_ids: this.assignedEmployeeIds,
            service_type: isEq ? 'equipment' : (this.serviceType || null),
            admin_notes: this.adminNotes,
            address: this.address || null,   // composed from the parts below
            address_street: this.addressStreet || null,
            address_city: this.addressCity || null,
            address_state: this.addressState || null,
            // Only a date+time counts as a scheduled visit — a pre-filled date alone is not saved.
            confirmed_date_time: (this.datePart(this.confirmedDateTime) && this.timePart(this.confirmedDateTime)) ? this.confirmedDateTime : null,
            // Equipment pickup (date+time + on-site duration) — only for equipment rentals.
            pickup_date_time: (isEq && this.datePart(this.pickupDateTime) && this.timePart(this.pickupDateTime)) ? this.pickupDateTime : null,
            pickup_duration_minutes: (isEq && this.datePart(this.pickupDateTime) && this.timePart(this.pickupDateTime)) ? this.pickupDurationMinutes : null,
            pickup_assigned_employee_ids: isEq ? this.pickupAssignedEmployeeIds : [],
            phone: this.phone || null,
            email: this.email || null,
            preferred_contact_method: this.preferredContactMethod,
            urgency: this.urgency,
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

    // Fields required to save a quote (email is intentionally NOT required).
    get missingToSave() {
        const m = [];
        if (!String(this.phone || '').trim()) m.push('a phone number');
        if (!this.jobSelected) m.push(this.isEquipment ? 'an equipment type' : 'a service');
        return m;
    },
    _joinAnd(arr) {
        if (arr.length <= 1) return arr[0] || '';
        return arr.slice(0, -1).join(', ') + ' and ' + arr[arr.length - 1];
    },

    // The "Save Changes" button: enforce required fields (starred), explain what's
    // missing, then persist. Quick status actions (cancel, voicemail, etc.) call
    // save() directly and are never blocked by completeness.
    saveChanges() {
        const missing = this.missingToSave;
        if (missing.length) {
            this.jobError = !this.jobSelected;
            this.saveError = 'Can’t save yet — please add ' + this._joinAnd(missing) + '.';
            this.scrollToSection(!this.jobSelected ? 'sec-job' : 'sec-customer');
            return;
        }
        this.saveError = '';
        return this.save();
    },

    async save(overrides = {}) {
        this.saving = true;
        let ok = false;
        try {
            const res = await fetch(this.urls.update, { method: 'PATCH', headers: window.jsonHeaders(true), body: JSON.stringify(this.buildBody(overrides)) });
            if (res.ok) { const d = await res.json(); if (d.inquiry) this.hydrate(d.inquiry); await this.reloadHistory(); ok = true; }
            else { this.error = 'Failed to save'; }
        } catch (e) { this.error = 'Failed to save'; }
        finally { this.saving = false; }
        return ok;
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
        const ok = await this.save({ status: 'cancelled' });
        if (!ok) { this.showCancelConfirm = false; return; }   // surface the error
        await this.logAudit('Cancelled quote');
        window.location.href = this.urls.dashboard;             // back to the quote list
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
        const q = (this.addressStreet || '').trim();
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
        // Parse the suggestion into the right fields (don't blank ones it didn't include).
        this.addressStreet = s.street || s.value || '';
        if (s.city) this.addressCity = s.city;
        if (s.state) this.addressState = s.state;
        if (s.zip) this.customerZip = s.zip;
        this.addrSuggestions = [];
        this.addrOpen = false;
    },

    _stateAbbr(s) {
        s = (s || '').trim();
        if (!s) return '';
        if (s.length === 2) return s.toUpperCase();
        return US_STATE_ABBR[s.toLowerCase()] || s;
    },
    _looksLikeState(text) {
        const t = (text || '').replace(/\b\d{5}(?:-\d{4})?\b/, '').trim().toLowerCase();
        if (!t) return false;
        if (t.length === 2) return Object.values(US_STATE_ABBR).includes(t.toUpperCase());
        return !!US_STATE_ABBR[t];
    },
    // Split a full address held in the Street field into the right fields. Handles
    // comma-separated ("123 Main St, Yucaipa, CA 92399") and space/period-separated
    // ("31610 Florida St. Redlands Ca. 92373"). A plain street is left untouched.
    parseStreetAddress() {
        let raw = (this.addressStreet || '').trim();
        if (!raw) return;
        raw = raw.replace(/,?\s*(United States|USA)\.?\s*$/i, '').trim();

        // Peel a trailing ZIP.
        let zip = '';
        const zm = raw.match(/(\d{5})(?:-\d{4})?\s*$/);
        if (zm) { zip = zm[1]; raw = raw.slice(0, zm.index); }
        raw = raw.replace(/[,\s]+$/, '').trim();

        if (raw.includes(',')) {
            // Comma-separated: street, city[, …], state.
            const segs = raw.split(',').map((s) => s.trim()).filter(Boolean);
            let state = '';
            if (segs.length && this._looksLikeState(segs[segs.length - 1])) state = this._stateAbbr(segs.pop().replace(/\.$/, ''));
            const street = segs.shift() || '';
            const city = segs.join(', ');
            if (street) this.addressStreet = street;
            if (city) this.addressCity = city;
            if (state) this.addressState = state;
            if (zip) this.customerZip = zip;
            return;
        }

        // No commas: peel a trailing state, then treat the last word as the city —
        // but only when there's a ZIP or state (so a plain street isn't mis-split).
        const tokens = raw.split(/\s+/).filter(Boolean);
        let state = '';
        if (tokens.length >= 2 && this._looksLikeState((tokens[tokens.length - 2] + ' ' + tokens[tokens.length - 1]).replace(/\.$/, ''))) {
            state = this._stateAbbr((tokens.splice(-2, 2).join(' ')).replace(/\.$/, ''));
        } else if (tokens.length && this._looksLikeState(tokens[tokens.length - 1].replace(/\.$/, ''))) {
            state = this._stateAbbr(tokens.pop().replace(/\.$/, ''));
        }
        let city = '';
        if ((zip || state) && tokens.length >= 2) city = tokens.pop().replace(/\.$/, '');
        const street = tokens.join(' ');
        if (street) this.addressStreet = street;
        if (city) this.addressCity = city;
        if (state) this.addressState = state;
        if (zip) this.customerZip = zip;
    },
    // Apply a previous order's address (structured parts when available, else parse the combined string).
    usePreviousAddress(inq) {
        if (inq.address_street || inq.address_city) {
            this.addressStreet = inq.address_street || '';
            this.addressCity = inq.address_city || '';
            this.addressState = inq.address_state || 'CA';
            if (inq.zip_code) this.customerZip = String(inq.zip_code);
        } else {
            this.addressStreet = inq.address || '';
            this.parseStreetAddress();   // splits it if it's a comma-separated full address
            if (inq.zip_code && !this.customerZip) this.customerZip = String(inq.zip_code);
        }
    },

    openInGoogleMaps() { if (this.address?.trim()) window.open(`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(this.address)}`, '_blank'); },
    openAddressInMaps(addr) { if (addr?.trim()) window.open(`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(addr)}`, '_blank'); },

    stepDuration(dir) {
        const step = this.expectedDurationUnit === 'days' ? 1 : 0.5;
        const minVal = this.expectedDurationUnit === 'days' ? 1 : 0.5;
        const cur = Number(this.expectedDurationValue) || 0;
        this.expectedDurationValue = Math.max(minVal, Math.round((cur + dir * step) * 10) / 10);
    },
    stepPickupDuration(dir) {
        const step = this.pickupDurationUnit === 'days' ? 1 : 0.5;
        const minVal = this.pickupDurationUnit === 'days' ? 1 : 0.5;
        const cur = Number(this.pickupDurationValue) || 0;
        this.pickupDurationValue = Math.max(minVal, Math.round((cur + dir * step) * 10) / 10);
    },

    // confirmed date/time split-field setters
    setConfirmedDate(v) { const t = this.timePart(this.confirmedDateTime); this.confirmedDateTime = v ? `${v}T${t}` : ''; },
    // Nudge the visit date a day at a time (defaults to today when unset).
    stepConfirmedDate(dir) {
        const cur = this.datePart(this.confirmedDateTime);
        const d = cur ? new Date(cur + 'T00:00') : new Date();
        d.setDate(d.getDate() + dir);
        const p = (n) => String(n).padStart(2, '0');
        this.setConfirmedDate(`${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`);
    },
    setConfirmedTime(v) { const d = this.datePart(this.confirmedDateTime); if (!v) { this.confirmedDateTime = d ? `${d}T` : ''; return; } this.confirmedDateTime = d ? `${d}T${v}` : v; },

    // equipment pickup date/time split-field setters
    setPickupDate(v) { const t = this.timePart(this.pickupDateTime); this.pickupDateTime = v ? `${v}T${t}` : ''; },
    // Nudge the pickup date a day at a time (defaults to today when unset).
    stepPickupDate(dir) {
        const cur = this.datePart(this.pickupDateTime);
        const d = cur ? new Date(cur + 'T00:00') : new Date();
        d.setDate(d.getDate() + dir);
        const p = (n) => String(n).padStart(2, '0');
        this.setPickupDate(`${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`);
    },
    setPickupTime(v) { const d = this.datePart(this.pickupDateTime); if (!v) { this.pickupDateTime = d ? `${d}T` : ''; return; } this.pickupDateTime = d ? `${d}T${v}` : v; },

    // Pickup the customer requested on the agreement, only worth surfacing when we haven't scheduled one.
    get showCustomerPickup() { return !!(this.isEquipment && this.customerPickup && (this.customerPickup.date || this.customerPickup.time) && !this.datePart(this.pickupDateTime)); },
    get customerPickupLabel() {
        const p = this.customerPickup; if (!p) return '';
        const d = p.date ? new Date(p.date + 'T00:00').toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' }) : '';
        return [d, p.time].filter(Boolean).join(' at ');
    },
    applyCustomerPickup() {
        const p = this.customerPickup; if (!p || !p.date) return;
        this.pickupDateTime = `${p.date}T${p.time24 || '08:00'}`;
    },
    setPaymentDate(v) { const t = this.timePart(this.paymentDate) || '00:00'; this.paymentDate = v ? `${v}T${t}` : ''; },
    setPaymentTime(v) { const d = this.datePart(this.paymentDate); if (!v) { this.paymentDate = d ? `${d}T` : ''; return; } this.paymentDate = d ? `${d}T${v}` : v; },
    pickPreferredDate(dateStr) { const t = this.timePart(this.confirmedDateTime); this.confirmedDateTime = `${dateStr}T${t}`; },
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
    listExpanded: true,   // collapses once a customer is picked; re-openable to browse again

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

    select(key) { this.selectedKey = key; this.query = ''; this.listExpanded = false; },
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
    agreements: cfg.agreements || [],
    nw: { label: '', price: '', duration: '120', customerVisible: true, instructions: '', agreement_id: '' },
    error: '',
    editingId: null,
    ed: { label: '', price: '', duration: '', instructions: '', agreement_id: '' },

    async reload() { try { const r = await fetch(this.urls.index, { headers: window.jsonHeaders() }); if (r.ok) { const d = await r.json(); this.services = d.services || []; } } catch {} },

    agreementName(item) { const a = this.agreements.find((x) => x.id === item.agreement_id); return a ? a.title : ''; },

    async add() {
        this.error = '';
        const r = await fetch(this.urls.store, { method: 'POST', headers: window.jsonHeaders(true), body: JSON.stringify({ label: this.nw.label, default_price: this.nw.price === '' ? null : this.nw.price, default_duration_minutes: this.nw.duration || 120, customer_visible: this.nw.customerVisible, customer_instructions: this.nw.instructions, agreement_id: this.nw.agreement_id || null }) });
        if (r.ok) { this.nw = { label: '', price: '', duration: '120', customerVisible: true, instructions: '', agreement_id: '' }; await this.reload(); }
        else { const d = await r.json().catch(() => ({})); this.error = d.error || 'Failed to add service'; }
    },
    startEdit(s) { this.editingId = s.id; this.ed = { label: s.label, price: s.default_price ?? '', duration: s.default_duration_minutes ?? 120, instructions: s.customer_instructions ?? '', agreement_id: s.agreement_id ?? '' }; },
    cancelEdit() { this.editingId = null; },
    async saveEdit(s) {
        const r = await fetch(this.urls.update.replace('__ID__', s.id), { method: 'PATCH', headers: window.jsonHeaders(true), body: JSON.stringify({ label: this.ed.label, default_price: this.ed.price === '' ? null : this.ed.price, default_duration_minutes: this.ed.duration, customer_instructions: this.ed.instructions, agreement_id: this.ed.agreement_id || null }) });
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
    agreements: cfg.agreements || [],
    blank: { name: '', pricingType: 'machinery', cost: '', daily: '', flat: '', incDays: '', incTons: '', addTon: '', addDay: '', instructions: '', agreement_id: '' },
    f: { name: '', pricingType: 'machinery', cost: '', daily: '', flat: '', incDays: '', incTons: '', addTon: '', addDay: '', instructions: '', agreement_id: '' },   // single buffer for add + edit
    formOpen: false,
    editingId: null,   // null while creating
    error: '',

    async reload() { try { const r = await fetch(this.urls.index, { headers: window.jsonHeaders() }); if (r.ok) { const d = await r.json(); this.equipment = d.equipment || []; } } catch {} },

    agreementName(item) { const a = this.agreements.find((x) => x.id === item.agreement_id); return a ? a.title : ''; },

    // Short summary of an item's pricing for the list.
    pricingLabel(e) {
        if (e.flat_price) {
            const inc = [];
            if (e.included_days) inc.push(`${e.included_days}d`);
            if (e.included_tons) inc.push(`${this.money(e.included_tons)}t`);
            let s = `$${this.money(e.flat_price)} flat`;
            if (inc.length) s += ` (incl ${inc.join(' / ')})`;
            return s;
        }
        const r = [];
        if (e.avg_cost_per_hour) r.push(`$${this.money(e.avg_cost_per_hour)}/hr`);
        if (e.daily_rate) r.push(`$${this.money(e.daily_rate)}/day`);
        return r.length ? r.join(' · ') : '—';
    },

    // Build the request body from a form-state object (nw or ed). Only the fields
    // for the chosen pricing type are sent; the other type's fields are cleared.
    _payload(s) {
        const n = (v) => (v === '' || v == null ? null : v);
        const flat = s.pricingType === 'flat';
        return {
            name: s.name,
            avg_cost_per_hour: flat ? null : n(s.cost),
            daily_rate: flat ? null : n(s.daily),
            flat_price: flat ? n(s.flat) : null,
            included_days: flat ? n(s.incDays) : null,
            included_tons: flat ? n(s.incTons) : null,
            price_per_additional_ton: flat ? n(s.addTon) : null,
            price_per_additional_day: flat ? n(s.addDay) : null,
            customer_instructions: s.instructions, agreement_id: s.agreement_id || null,
        };
    },

    // Open the shared modal in create mode.
    openCreate() { this.error = ''; this.editingId = null; this.f = { ...this.blank }; this.formOpen = true; },
    // Open the shared modal in edit mode, prefilled from the item.
    startEdit(e) {
        this.error = '';
        this.editingId = e.id;
        this.f = {
            name: e.name, pricingType: e.flat_price ? 'flat' : 'machinery',
            cost: e.avg_cost_per_hour ?? '', daily: e.daily_rate ?? '',
            flat: e.flat_price ?? '', incDays: e.included_days ?? '', incTons: e.included_tons ?? '',
            addTon: e.price_per_additional_ton ?? '', addDay: e.price_per_additional_day ?? '',
            instructions: e.customer_instructions ?? '', agreement_id: e.agreement_id ?? '',
        };
        this.formOpen = true;
    },
    closeForm() { this.formOpen = false; this.editingId = null; this.error = ''; },
    async save() {
        this.error = '';
        const editing = this.editingId !== null;
        const url = editing ? this.urls.update.replace('__ID__', this.editingId) : this.urls.store;
        const r = await fetch(url, { method: editing ? 'PATCH' : 'POST', headers: window.jsonHeaders(true), body: JSON.stringify(this._payload(this.f)) });
        if (r.ok) { this.closeForm(); await this.reload(); }
        else { const d = await r.json().catch(() => ({})); this.error = d.error || 'Failed to save'; }
    },
    async toggleActive(e) { await fetch(this.urls.update.replace('__ID__', e.id), { method: 'PATCH', headers: window.jsonHeaders(true), body: JSON.stringify({ active: !e.active }) }); await this.reload(); },
    async toggleCustomerVisible(e) { await fetch(this.urls.update.replace('__ID__', e.id), { method: 'PATCH', headers: window.jsonHeaders(true), body: JSON.stringify({ customer_visible: !e.customer_visible }) }); await this.reload(); },
    async remove(e) { if (!confirm(`Permanently delete the "${e.name}" equipment item? This cannot be undone.`)) return; await fetch(this.urls.destroy.replace('__ID__', e.id), { method: 'DELETE', headers: window.jsonHeaders(true) }); await this.reload(); },
    money(n) { return n == null ? '—' : Number(n).toLocaleString(); },
}));

// ---------------------------------------------------------------------------
// Agreements catalog — editable templates (title + acknowledgment items +
// instructions) attachable to services/equipment. Acknowledgments are edited as
// one-per-line text; the server parses them into an array.
// ---------------------------------------------------------------------------
Alpine.data('agreementCatalog', (cfg = {}) => ({
    urls: cfg.urls,
    agreements: cfg.agreements || [],
    nw: { title: '', acknowledgments: '', instructions: '' },
    error: '',
    editingId: null,
    ed: { title: '', acknowledgments: '', instructions: '' },

    async reload() { try { const r = await fetch(this.urls.index, { headers: window.jsonHeaders() }); if (r.ok) { const d = await r.json(); this.agreements = d.agreements || []; } } catch {} },

    ackCount(a) { return (a.acknowledgments || []).length; },

    async add() {
        this.error = '';
        const r = await fetch(this.urls.store, { method: 'POST', headers: window.jsonHeaders(true), body: JSON.stringify({ title: this.nw.title, acknowledgments: this.nw.acknowledgments, instructions: this.nw.instructions }) });
        if (r.ok) { this.nw = { title: '', acknowledgments: '', instructions: '' }; await this.reload(); }
        else { const d = await r.json().catch(() => ({})); this.error = d.error || 'Failed to add agreement'; }
    },
    startEdit(a) { this.editingId = a.id; this.ed = { title: a.title, acknowledgments: (a.acknowledgments || []).join('\n'), instructions: a.instructions ?? '' }; },
    cancelEdit() { this.editingId = null; },
    async saveEdit(a) {
        const r = await fetch(this.urls.update.replace('__ID__', a.id), { method: 'PATCH', headers: window.jsonHeaders(true), body: JSON.stringify({ title: this.ed.title, acknowledgments: this.ed.acknowledgments, instructions: this.ed.instructions }) });
        if (r.ok) { this.editingId = null; await this.reload(); }
    },
    async toggleActive(a) { await fetch(this.urls.update.replace('__ID__', a.id), { method: 'PATCH', headers: window.jsonHeaders(true), body: JSON.stringify({ active: !a.active }) }); await this.reload(); },
    async remove(a) { if (!confirm(`Permanently delete the "${a.title}" agreement? It will be detached from any services/equipment. This cannot be undone.`)) return; await fetch(this.urls.destroy.replace('__ID__', a.id), { method: 'DELETE', headers: window.jsonHeaders(true) }); await this.reload(); },
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
        if (!confirm(`Delete ${a.role === 'employee' ? 'employee' : 'admin'} ${a.username}?`)) return;
        const r = await fetch(this.urls.destroy.replace('__ID__', a.id), { method: 'DELETE', headers: window.jsonHeaders(true) });
        if (r.ok) await this.reload();
        else { const d = await r.json().catch(() => ({})); alert(d.error || 'Failed to delete account'); }
    },
    async toggleActive(a) {
        const next = !a.active;
        if (!next && !confirm(`Deactivate ${a.username}? They won't be able to log in.`)) return;
        const r = await fetch(this.urls.update.replace('__ID__', a.id), { method: 'PATCH', headers: window.jsonHeaders(true), body: JSON.stringify({ action: 'set_active', active: next }) });
        if (r.ok) await this.reload();
        else { const d = await r.json().catch(() => ({})); alert(d.error || 'Failed to update account'); }
    },

    // At least one active admin-role account must always remain.
    get activeAdminCount() { return this.admins.filter((a) => (a.role || 'admin') === 'admin' && a.active).length; },
    isLastActiveAdmin(a) { return (a.role || 'admin') === 'admin' && a.active && this.activeAdminCount <= 1; },

    date(d) { return d ? new Date(d).toLocaleDateString() : ''; },
}));

// ---------------------------------------------------------------------------
// Notification settings — the current admin's per-event channel choices.
// Settings only: this persists preferences; nothing sends yet.
// ---------------------------------------------------------------------------
Alpine.data('notificationSettings', (cfg = {}) => ({
    events: cfg.events || [],
    updateUrl: cfg.updateUrl,
    email: cfg.prefs?.email || '',
    phone: cfg.prefs?.phone || '',
    channels: {},          // { [eventKey]: { email: bool, sms: bool } }
    saving: false,
    saved: false,
    error: '',

    // Global customer-facing channel switches.
    customerUrl: cfg.customerUrl,
    customerEmail: !!cfg.customer?.email,
    customerSms: !!cfg.customer?.sms,
    customerSaving: false,
    customerSaved: false,
    customerError: '',

    // Twilio test send.
    testSmsUrl: cfg.testSmsUrl,
    testing: false,
    testResult: '',
    testOk: false,

    async sendTest() {
        this.testResult = '';
        this.testing = true;
        try {
            const res = await fetch(this.testSmsUrl, {
                method: 'POST', headers: window.jsonHeaders(true),
                body: JSON.stringify({ phone: this.phone }),
            });
            const json = await res.json().catch(() => ({}));
            this.testOk = res.ok;
            this.testResult = res.ok ? 'Sent — check your phone.' : (json.error || 'Couldn’t send.');
        } catch (e) {
            this.testOk = false;
            this.testResult = 'Couldn’t send.';
        } finally {
            this.testing = false;
        }
    },

    // Test email send.
    testEmailUrl: cfg.testEmailUrl,
    emailTesting: false,
    emailTestResult: '',
    emailTestOk: false,

    async sendTestEmail() {
        this.emailTestResult = '';
        this.emailTesting = true;
        try {
            const res = await fetch(this.testEmailUrl, {
                method: 'POST', headers: window.jsonHeaders(true),
                body: JSON.stringify({ email: this.email }),
            });
            const json = await res.json().catch(() => ({}));
            this.emailTestOk = res.ok;
            this.emailTestResult = res.ok ? 'Sent — check your inbox.' : (json.error || 'Couldn’t send.');
        } catch (e) {
            this.emailTestOk = false;
            this.emailTestResult = 'Couldn’t send.';
        } finally {
            this.emailTesting = false;
        }
    },

    init() {
        const saved = cfg.prefs?.events || {};
        // Seed a channel entry for every known event from saved prefs (default off).
        for (const ev of this.events) {
            const s = saved[ev.key] || {};
            this.channels[ev.key] = { email: !!s.email, sms: !!s.sms };
        }
    },

    async saveCustomer() {
        this.customerError = '';
        this.customerSaved = false;
        this.customerSaving = true;
        try {
            const res = await fetch(this.customerUrl, {
                method: 'PATCH', headers: window.jsonHeaders(true),
                body: JSON.stringify({ email: this.customerEmail, sms: this.customerSms }),
            });
            const json = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(json.error || 'Couldn’t save these settings.');
            this.customerSaved = true;
            setTimeout(() => { this.customerSaved = false; }, 2500);
        } catch (e) {
            this.customerError = e.message || 'Couldn’t save these settings.';
        } finally {
            this.customerSaving = false;
        }
    },

    async save() {
        this.error = '';
        this.saved = false;
        this.saving = true;
        try {
            const res = await fetch(this.updateUrl, {
                method: 'PATCH', headers: window.jsonHeaders(true),
                body: JSON.stringify({ email: this.email, phone: this.phone, events: this.channels }),
            });
            const json = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(json.error || 'Couldn’t save your settings.');
            this.saved = true;
            setTimeout(() => { this.saved = false; }, 2500);
        } catch (e) {
            this.error = e.message || 'Couldn’t save your settings.';
        } finally {
            this.saving = false;
        }
    },
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
    pickTarget: cfg.pickTarget || 'visit', // 'visit' | 'pickup' — which field the parent applies the pick to
    pickInquiryId: cfg.pickInquiryId || '', // hide this quote's saved event (the pick card stands in for it)
    assigneeFilter: cfg.assignee || '',     // legacy single-filter (kept for compatibility)
    assigneeName: cfg.assigneeName || '',
    viewAll: false,
    employees: cfg.employees || [],          // {id, label} — for the quick-filter buttons
    selectedAssignees: [],                   // multi-select filter; >1 → per-employee columns
    embed: !!cfg.embed,                      // scheduling popup — hides the Unassigned column
    // Click-to-create (main calendar): clicking an empty slot opens a new-quote prompt.
    showNewQuote: false,
    newQuote: { date: '', minutes: 0, employeeId: '', employeeName: '', phone: '', error: '', loading: false },
    // drag state
    dragMode: null, _grabOffsetMin: 0, _justDragged: false, _durationChanged: false,

    init() {
        if (cfg.initialView && ['month', 'week', 'day', '3day', '5day'].includes(cfg.initialView)) this.viewMode = cfg.initialView;
        // Embed opens pre-filtered to the quote's assignee(s) (comma-joined; toggleable).
        if (cfg.assignee) this.selectedAssignees = String(cfg.assignee).split(',').filter(Boolean);
        // Main calendar defaults to showing just the current admin's own jobs.
        else if (cfg.selfId && this.employees.some((e) => e.id === cfg.selfId)) this.selectedAssignees = [cfg.selfId];
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
        const msg = { type: 'calendar-pick', target: this.pickTarget, datetime: `${this.pickedDateKey}T${time}`, assignees: [...this.selectedAssignees] };
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
        // Share a column with any events it overlaps so the preview doesn't cover them.
        const pick = this._dayColumns().find((it) => it.isPick);
        const cols = pick ? pick.cols : 1, lane = pick ? pick.lane : 0;
        const left = (lane / cols) * 100, width = 100 / cols;
        return `position:absolute;top:${top}px;height:${height}px;left:calc(${left}% + 4px);width:calc(${width}% - 8px)`;
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

    // Click an empty calendar slot → prompt to create a quote at that time (and
    // employee, when clicked inside a person's column).
    startNewQuoteAt(event, employeeId) {
        const rect = event.currentTarget.getBoundingClientRect();
        let m = DAY_START_HOUR * 60 + ((event.clientY - rect.top) / HOUR_PX) * 60;
        m = Math.round(m / 30) * 30;
        m = Math.max(DAY_START_HOUR * 60, Math.min(DAY_END_HOUR * 60 - 60, m));
        this.newQuote = {
            date: this.localKey(this.currentDate),
            minutes: m,
            employeeId: employeeId || '',
            employeeName: employeeId ? (this.employees.find((e) => e.id === employeeId)?.label || 'Employee') : '',
            phone: '', error: '', loading: false,
        };
        this.showNewQuote = true;
        this.$nextTick(() => { try { this.$refs.newQuotePhone.focus(); } catch { /* not rendered */ } });
    },
    get newQuoteTimeLabel() {
        const m = this.newQuote.minutes, h = Math.floor(m / 60), mm = m % 60;
        return `${h % 12 === 0 ? 12 : h % 12}:${String(mm).padStart(2, '0')} ${h >= 12 ? 'PM' : 'AM'}`;
    },
    get newQuoteDateLabel() {
        return new Date(this.newQuote.date + 'T00:00').toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' });
    },
    async submitNewQuote() {
        if (!this.newQuote.phone.trim()) { this.newQuote.error = 'A phone number is required.'; return; }
        this.newQuote.loading = true;
        this.newQuote.error = '';
        const pad = (n) => String(n).padStart(2, '0');
        const time = `${pad(Math.floor(this.newQuote.minutes / 60))}:${pad(this.newQuote.minutes % 60)}`;
        try {
            const res = await fetch(cfg.quickQuoteUrl, {
                method: 'POST', headers: window.jsonHeaders(true),
                body: JSON.stringify({ phone: this.newQuote.phone, datetime: `${this.newQuote.date}T${time}`, employee_id: this.newQuote.employeeId }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Could not create the quote.');
            // A brand-new quote opens the full editor (fill in service/customer), not the field sheet.
            window.location.href = (cfg.editBase || this.detailBase).replace('__ID__', data.inquiry.id);
        } catch (e) {
            this.newQuote.error = e.message || 'Could not create the quote.';
            this.newQuote.loading = false;
        }
    },

    eventClasses(s) {
        return ({
            new: 'bg-blue-500/10 hover:bg-blue-500/20 border-blue-500/40 hover:border-blue-500/70',
            left_voicemail: 'bg-violet-500/10 hover:bg-violet-500/20 border-violet-500/40 hover:border-violet-500/70',
            reviewing: 'bg-amber-500/10 hover:bg-amber-500/20 border-amber-500/40 hover:border-amber-500/70',
            quoted: 'bg-indigo-500/10 hover:bg-indigo-500/20 border-indigo-500/40 hover:border-indigo-500/70',
            scheduled: 'bg-[#F8C820]/10 hover:bg-[#F8C820]/20 border-[#F8C820]/40 hover:border-[#F8C820]/70',
            equipment_delivered: 'bg-cyan-500/10 hover:bg-cyan-500/20 border-cyan-500/40 hover:border-cyan-500/70',
            equipment_picked_up: 'bg-sky-500/10 hover:bg-sky-500/20 border-sky-500/40 hover:border-sky-500/70',
            service_performed: 'bg-teal-500/10 hover:bg-teal-500/20 border-teal-500/40 hover:border-teal-500/70',
            completed: 'bg-emerald-500/10 hover:bg-emerald-500/20 border-emerald-500/40 hover:border-emerald-500/70',
        })[s] || 'bg-gray-100 hover:bg-gray-200 border-gray-300 hover:border-gray-400';
    },
    dotClass(s) {
        return ({ new: 'bg-blue-400', left_voicemail: 'bg-violet-400', reviewing: 'bg-amber-400', quoted: 'bg-indigo-400', finalize_scheduling: 'bg-pink-500', scheduled: 'bg-[#F8C820]', equipment_delivered: 'bg-cyan-500', equipment_picked_up: 'bg-sky-500', service_performed: 'bg-teal-400', completed: 'bg-emerald-400' })[s] || 'bg-gray-400';
    },
    statusLabel(s) { return ({ new: 'New', left_voicemail: 'Voicemail', reviewing: 'Reviewing', quoted: 'Quoted', scheduled: 'Scheduled', service_performed: 'Service Performed', completed: 'Completed' })[s] || s; },
    serviceLabel(s) { return (s || '').replace(/-/g, ' '); },
    // Service vs equipment item on a calendar event.
    jobIsEquipment(inq) { return inq.service_type === 'equipment' || !!(inq.equipment_type && String(inq.equipment_type).trim()); },
    jobKind(inq) { return this.jobIsEquipment(inq) ? 'Equipment' : 'Service'; },
    jobLabel(inq) { return (inq.equipment_type && String(inq.equipment_type).trim()) || this.serviceLabel(inq.service_type); },
    fmtClock(d) { return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }); },

    // When scheduling from a quote, hide only the entry being placed (the pick card
    // stands in for it) — NOT the quote's other entry, so a visit + its pickup both show.
    get _excludeEventId() {
        if (!this.pickInquiryId) return null;
        return this.pickTarget === 'pickup' ? `${this.pickInquiryId}:pickup` : this.pickInquiryId;
    },
    get calendarEvents() {
        const ex = this._excludeEventId;
        const sel = this.selectedAssignees;
        const columns = this.columnMode;
        return this.events
            .filter((e) => e.confirmed_date_time)
            .filter((e) => e.event_id !== ex)
            .filter((e) => {
                if (!sel.length) return true;                         // none selected → everyone
                if ((e.assignee_ids || []).some((id) => sel.includes(id))) return true; // a selected employee
                return columns && !(e.assignee_ids && e.assignee_ids.length);           // unassigned (columns mode)
            })
            .map((e) => {
            const start = new Date(e.confirmed_date_time);
            const end = new Date(start.getTime() + (e.expected_duration_minutes || 120) * 60000);
            return { inquiry: e, start, end, key: this.localKey(start) };
        });
    },
    eventsForKey(key) { return this.calendarEvents.filter((e) => e.key === key).sort((a, b) => a.start - b.start); },

    // Assignee quick-filter (calendar + embed).
    toggleAssignee(id) {
        const i = this.selectedAssignees.indexOf(id);
        if (i === -1) this.selectedAssignees.push(id);
        else this.selectedAssignees.splice(i, 1);
    },
    assigneeSelected(id) { return this.selectedAssignees.includes(id); },

    // 2+ employees selected → split the day timeline into one column each (+ Unassigned).
    get columnMode() { return this.viewMode === 'day' && this.selectedAssignees.length > 1; },
    get dayAssigneeColumns() {
        const key = this.localKey(this.currentDate);
        const dayEvents = this.calendarEvents.filter((e) => e.key === key);
        const cols = this.selectedAssignees.map((id) => ({
            id, name: this.employees.find((x) => x.id === id)?.label || 'Employee', isUnassigned: false,
            events: this._layoutColumn(dayEvents.filter((e) => (e.inquiry.assignee_ids || []).includes(id))),
        }));
        // Columns left-to-right in alphabetical order.
        cols.sort((a, b) => a.name.localeCompare(b.name, undefined, { sensitivity: 'base' }));
        // The scheduling popup (embed) only shows the selected employees' columns.
        const unassigned = this.embed ? [] : dayEvents.filter((e) => !(e.inquiry.assignee_ids && e.inquiry.assignee_ids.length));
        if (unassigned.length) {
            cols.push({ id: '__unassigned__', name: 'Unassigned', isUnassigned: true, events: this._layoutColumn(unassigned) });
        }
        return cols;
    },
    // Position a single column's events by time, laning any overlaps within the column.
    _layoutColumn(evs) {
        const dayStart = DAY_START_HOUR * 60, dayEnd = DAY_END_HOUR * 60;
        const sorted = [...evs].sort((a, b) => a.start - b.start || a.end - b.end);
        const laneEnds = [];
        sorted.forEach((e) => {
            let lane = laneEnds.findIndex((end) => end <= e.start);
            if (lane === -1) { lane = laneEnds.length; laneEnds.push(e.end); } else { laneEnds[lane] = e.end; }
            e._lane = lane;
        });
        return sorted.map((e) => {
            const overlap = sorted.filter((o) => o.start < e.end && o.end > e.start);
            const lanes = Math.max(...overlap.map((o) => o._lane)) + 1;
            const startMin = e.start.getHours() * 60 + e.start.getMinutes();
            const endMin = e.end.getHours() * 60 + e.end.getMinutes();
            const top = Math.max(0, (startMin - dayStart) * HOUR_PX / 60);
            const rawH = (Math.min(endMin, dayEnd) - Math.max(startMin, dayStart)) * HOUR_PX / 60;
            const height = Math.max(HOUR_PX * 0.45, rawH);
            const left = (e._lane / lanes) * 100, width = 100 / lanes;
            return { ...e, style: `position:absolute;top:${top}px;height:${height}px;left:calc(${left}% + 2px);width:calc(${width}% - 4px)`, big: height >= HOUR_PX };
        });
    },

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
    // Lane assignment for the day timeline. Returns every item (real events + the
    // drag/pick preview, when picking on this day) with a lane index and a column
    // count, so overlapping items get staggered side-by-side instead of stacking.
    _dayColumns() {
        const key = this.localKey(this.currentDate);
        const items = this.eventsForKey(key).map((e) => ({ ev: e, start: e.start, end: e.end, isPick: false }));
        if (this.pickOnThisDay) {
            const ps = new Date(this.currentDate);
            ps.setHours(0, this.pickedMinutes, 0, 0);
            const pe = new Date(ps.getTime() + this.pickDuration * 60000);
            items.push({ ev: null, start: ps, end: pe, isPick: true });
        }
        items.sort((a, b) => a.start - b.start || a.end - b.end);
        const laneEnds = [];
        items.forEach((it) => {
            let lane = laneEnds.findIndex((end) => end <= it.start);
            if (lane === -1) { lane = laneEnds.length; laneEnds.push(it.end); } else { laneEnds[lane] = it.end; }
            it.lane = lane;
        });
        // Columns = widest lane in the cluster of items this one overlaps, so every
        // item in a run of overlaps shares the same width and they tile cleanly.
        items.forEach((it) => {
            const overlap = items.filter((o) => o.start < it.end && o.end > it.start);
            it.cols = Math.max(...overlap.map((o) => o.lane)) + 1;
        });
        return items;
    },

    get dayLayout() {
        const dayStart = DAY_START_HOUR * 60, dayEnd = DAY_END_HOUR * 60;
        return this._dayColumns().filter((it) => !it.isPick).map((it) => {
            const e = it.ev;
            const startMin = e.start.getHours() * 60 + e.start.getMinutes();
            const endMin = e.end.getHours() * 60 + e.end.getMinutes();
            const top = Math.max(0, (startMin - dayStart) * HOUR_PX / 60);
            const rawH = (Math.min(endMin, dayEnd) - Math.max(startMin, dayStart)) * HOUR_PX / 60;
            const height = Math.max(HOUR_PX * 0.45, rawH);
            const left = (it.lane / it.cols) * 100, width = 100 / it.cols;
            return { ...e, style: `position:absolute;top:${top}px;height:${height}px;left:calc(${left}% + 4px);width:calc(${width}% - 8px)`, big: height >= HOUR_PX };
        });
    },

    isToday(d) { return d && d.toDateString() === new Date().toDateString(); },
    dayKey(d) { return this.localKey(d); },

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
    adminTools: cfg.adminTools || [],   // selected mobile-toolbar tool keys
    quoteFilters: cfg.quoteFilters || [],         // ordered quote-filter keys (priority)
    quoteFilterLabels: cfg.quoteFilterLabels || {}, // key -> label, for the editor
    cardSets: {}, // { fieldKey: [cards] } — populated in init()
    saving: false,
    saved: false,
    dirty: false,
    ready: false, // gates dirty-tracking until after Trix finishes loading content
    error: '',

    // --- Quote-filter priority editor (ordered, include/exclude) -------------
    moveQuoteFilter(i, dir) {
        const j = i + dir;
        if (j < 0 || j >= this.quoteFilters.length) return;
        [this.quoteFilters[i], this.quoteFilters[j]] = [this.quoteFilters[j], this.quoteFilters[i]];
        this.dirty = true;
    },
    removeQuoteFilter(i) { this.quoteFilters.splice(i, 1); this.dirty = true; },
    addQuoteFilter(key) { if (!this.quoteFilters.includes(key)) { this.quoteFilters.push(key); this.dirty = true; } },
    get unusedQuoteFilters() { return Object.keys(this.quoteFilterLabels).filter((k) => !this.quoteFilters.includes(k)); },

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
    agreementTitle: cfg.agreementTitle || '',
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
        return (this.preferred === 'email' ? 'Email' : 'Text') + ' Rental Agreement Link';
    },
    // The single active (non-cancelled) agreement — only one exists at a time.
    get current() { return this.agreements.find((a) => !a.cancelled_at) || null; },

    init() {
        const active = this.agreements.find((a) => a.usable);
        if (active) this.link = active.url;
    },

    // Generate (or reuse) the signing link and immediately text/email it to the customer.
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
            // Persist any unsaved quote edits alongside the agreement (parent listens).
            this.$dispatch('quote-save');
            this.deliver();
        } catch (e) {
            this.error = e.message || 'Error generating the agreement.';
        } finally {
            this.sending = false;
        }
    },

    // Copy the active signing link (button text after one has been generated/sent).
    async copyCurrent() {
        const url = this.link || (this.current && this.current.url) || '';
        if (!url) return;
        try {
            await navigator.clipboard.writeText(url);
            this.copied = true;
            setTimeout(() => { this.copied = false; }, 2000);
        } catch {
            this.error = 'Copy failed — open the link and copy manually.';
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
    // Field View extras: scan-to-pay QR + record an in-person payment.
    field: !!cfg.field,
    qr: '',
    method: '',
    recording: false,
    paidMethod: cfg.paidMethod || '',
    paidAt: cfg.paidAt || '',
    quoted: cfg.quoted ?? null,   // saved quoted price; null/0 ⇒ let them enter it in-field
    amountInput: '',              // in-field amount when nothing was quoted yet

    get contactLabel() {
        return (this.preferred === 'email' ? 'Email' : 'Text') + ' payment link';
    },

    // Is there a usable amount already saved on the quote?
    get hasAmount() { return this.quoted != null && this.quoted !== '' && Number(this.quoted) > 0; },
    // The amount to charge: the saved price, or the one typed in the field.
    _amount() {
        if (this.hasAmount) return Number(this.quoted);
        const v = parseFloat(this.amountInput);
        return (!isNaN(v) && v > 0) ? v : null;
    },

    init() {
        const active = this.links.find((l) => l.usable);
        if (active) { this.link = active.url; this.amount = active.amount; this.makeQr(); }
    },

    // Render a scannable QR of the active payment link (the customer pays on their phone).
    makeQr() {
        if (!this.link) { this.qr = ''; return; }
        try {
            const qr = qrcode(0, 'M');
            qr.addData(this.link);
            qr.make();
            this.qr = qr.createDataURL(5, 12);
        } catch { this.qr = ''; }
    },

    // Record an in-person payment (cash/check/card/Venmo/…) and mark the job paid.
    async recordPaid() {
        if (!this.method) { this.error = 'Pick how the customer paid.'; return; }
        const amt = this._amount();
        if (amt === null) { this.error = 'Enter the amount due.'; return; }
        this.recording = true;
        this.error = '';
        try {
            const body = { payment_method: this.method };
            if (!this.hasAmount) body.amount = amt;   // also save the amount they entered
            const res = await fetch(this.cfg.recordUrl, { method: 'POST', headers: window.jsonHeaders(true), body: JSON.stringify(body) });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Could not record the payment.');
            this.paidMethod = data.payment_method;
            this.paidAt = data.payment_date;
            if (data.quoted_price != null) this.quoted = data.quoted_price;
            this.method = '';
        } catch (e) {
            this.error = e.message || 'Could not record the payment.';
        } finally {
            this.recording = false;
        }
    },

    async send() {
        // In the field, allow generating the link off an amount entered here.
        if (this.field && !this.hasAmount && this._amount() === null) { this.error = 'Enter the amount due first.'; return; }
        this.sending = true;
        this.error = '';
        this.copied = false;
        try {
            const body = (this.field && !this.hasAmount) ? { amount: this._amount() } : {};
            const res = await fetch(this.cfg.createUrl, { method: 'POST', headers: window.jsonHeaders(true), body: JSON.stringify(body) });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Could not generate the payment link.');
            const l = data.payment_link;
            this.link = l.url;
            this.amount = l.amount;
            if (!this.hasAmount && l.amount != null) this.quoted = l.amount;
            const i = this.links.findIndex((x) => x.id === l.id);
            if (i === -1) this.links.unshift(l);
            else this.links[i] = l;
            this.makeQr();
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
    open: false,
    isDrawing: false,
    hasSignature: false,
    submitting: false,
    error: '',
    _canvas: null,
    _onResize: null,
    targetStatus: 'service_performed',  // which field action this signature confirms
    targetLabel: 'Service Performed',

    // Open the full-screen pad for a specific field action (service performed /
    // equipment delivered / equipment picked up); saving sets that status.
    openFor(status, label) {
        this.targetStatus = status;
        this.targetLabel = label;
        this.open = true;
        this.error = '';
        this.hasSignature = false;
        // Keep the canvas backing store matched to its on-screen size if the device
        // rotates or the overlay finishes laying out — a portrait buffer drawn in
        // landscape (or vice-versa) is what squished the captured signature.
        this._onResize = () => this.initPad();
        window.addEventListener('resize', this._onResize);
        window.addEventListener('orientationchange', this._onResize);
        this.$nextTick(() => { this.initPad(); requestAnimationFrame(() => this.initPad()); });
    },
    openPad() { this.openFor('service_performed', 'Service Performed'); },
    close() {
        this.open = false;
        if (this._onResize) {
            window.removeEventListener('resize', this._onResize);
            window.removeEventListener('orientationchange', this._onResize);
            this._onResize = null;
        }
    },

    initPad() {
        const c = this.$refs.canvas;
        if (!c) return;
        // Backing store == displayed size (1:1) so strokes keep the pad's aspect ratio.
        // Only resize on an actual change — resizing the canvas clears it.
        const r = c.getBoundingClientRect();
        const w = Math.round(r.width), h = Math.round(r.height);
        if (!w || !h || (c.width === w && c.height === h)) return;
        c.width = w;
        c.height = h;
        c.getContext('2d').clearRect(0, 0, w, h);
        this.hasSignature = false;
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
            const res = await fetch(this.signUrl, { method: 'POST', headers: window.jsonHeaders(true), body: JSON.stringify({ signature: data, status: this.targetStatus }) });
            if (!res.ok) throw new Error('Could not save the signature.');
            window.location.reload();
        } catch (e) {
            this.error = e.message || 'Save failed.';
            this.submitting = false;
        }
    },
}));

// ---------------------------------------------------------------------------
// etaEstimator — field travel/arrival helper on the job sheet. Uses the device's
// geolocation + a backend OSRM route estimate (free, no key). The drive time is
// editable, and "Send ETA" texts/emails the customer their arrival window.
// ---------------------------------------------------------------------------
Alpine.data('etaEstimator', (cfg = {}) => ({
    estimateUrl: cfg.estimateUrl,
    notifyUrl: cfg.notifyUrl || '',
    notifiedAt: cfg.notifiedAt || '',   // when the customer was last sent their ETA (persisted)
    name: cfg.name || '',
    phone: cfg.phone || '',
    email: cfg.email || '',
    preferred: cfg.preferred === 'email' ? 'email' : 'phone',
    businessName: cfg.businessName || '',
    loading: false,
    error: '',
    calculated: false,
    distanceMi: null,
    travelMin: null,   // editable — the admin/employee can override the estimate

    calculate() {
        this.error = '';
        if (!navigator.geolocation) { this.error = 'Location is not available on this device.'; return; }
        this.loading = true;
        navigator.geolocation.getCurrentPosition(
            async (pos) => {
                try {
                    const res = await fetch(this.estimateUrl, {
                        method: 'POST', headers: window.jsonHeaders(true),
                        body: JSON.stringify({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.error || 'Could not calculate the route.');
                    this.distanceMi = data.distance_miles;
                    this.travelMin = data.duration_minutes;
                    this.calculated = true;
                } catch (e) {
                    this.error = e.message || 'Could not calculate the route.';
                } finally {
                    this.loading = false;
                }
            },
            (err) => {
                this.loading = false;
                this.error = err.code === 1 ? 'Location permission denied — allow location and try again.' : 'Could not get your current location.';
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 }
        );
    },

    get etaLabel() {
        if (this.travelMin == null || this.travelMin === '') return '';
        const d = new Date(Date.now() + Number(this.travelMin) * 60000);
        return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    },

    // Pad the drive time in 5-minute steps (e.g. for a quick stop on the way).
    padTravel(mins) { this.travelMin = Math.max(0, (Number(this.travelMin) || 0) + mins); },

    // Text/email the customer the arrival window via the device's messaging app,
    // then record the notification so the card collapses to "Customer notified …".
    async communicate() {
        if (this.travelMin == null) return;
        const eta = this.etaLabel;
        const msg = `Hi ${this.name || 'there'}, this is ${this.businessName}. We're on our way — estimated arrival around ${eta} (about ${this.travelMin} min out). See you soon!`;
        if (this.preferred === 'email' && !this.email) { this.error = 'No email on file for this customer.'; return; }
        if (this.preferred !== 'email' && !this.phone) { this.error = 'No phone number on file for this customer.'; return; }

        // Record first (best-effort) so it persists across the field reloads.
        if (this.notifyUrl) {
            try {
                const res = await fetch(this.notifyUrl, { method: 'POST', headers: window.jsonHeaders(true) });
                const d = res.ok ? await res.json() : null;
                this.notifiedAt = (d && d.eta_notified_at) || new Date().toISOString();
            } catch { this.notifiedAt = new Date().toISOString(); }
        } else {
            this.notifiedAt = new Date().toISOString();
        }

        if (this.preferred === 'email') {
            window.location.href = `mailto:${encodeURIComponent(this.email)}?subject=${encodeURIComponent('On our way')}&body=${encodeURIComponent(msg)}`;
        } else {
            window.location.href = `sms:${this.phone.replace(/[^\d+]/g, '')}?body=${encodeURIComponent(msg)}`;
        }
    },
    notifiedLabel() {
        if (!this.notifiedAt) return '';
        const d = new Date(this.notifiedAt);
        return isNaN(d.getTime()) ? '' : d.toLocaleString([], { weekday: 'short', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
    },
    // Reopen the calculate/send flow to text an updated ETA.
    resend() { this.notifiedAt = ''; this.error = ''; },
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
    collapsed: false,   // user can collapse the notes section

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
