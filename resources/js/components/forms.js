// Public-facing Alpine components: navbar (login/change-password modals),
// quote form, status lookup, rental agreement.
import Alpine from 'alpinejs';

// ---------------------------------------------------------------------------
// Navbar — admin login + self-service change-password modals.
// Ported from components/layout/Navbar.tsx.
// ---------------------------------------------------------------------------
Alpine.data('navbar', (cfg = {}) => ({
    cfg,
    menuOpen: false,
    mobileOpen: false,
    // login modal
    showLogin: false,
    login: { username: '', password: '', remember: false, error: '', loading: false },
    // change-password modal
    showPwd: false,
    pwd: { current: '', next: '', confirm: '', error: '', success: false, loading: false },

    openLogin() {
        const saved = localStorage.getItem('admin_remembered_username') ?? '';
        this.login = { username: saved, password: '', remember: !!saved, error: '', loading: false };
        this.showLogin = true;
    },

    async submitLogin() {
        this.login.error = '';
        this.login.loading = true;
        try {
            const res = await fetch(this.cfg.loginUrl, {
                method: 'POST',
                headers: window.jsonHeaders(true),
                body: JSON.stringify({ username: this.login.username, password: this.login.password }),
            });
            if (res.ok) {
                if (this.login.remember) localStorage.setItem('admin_remembered_username', this.login.username);
                else localStorage.removeItem('admin_remembered_username');
                const data = await res.json();
                window.location.href = data.mustChangePassword ? this.cfg.changePwdPage : this.cfg.dashboardUrl;
            } else {
                const data = await res.json().catch(() => ({}));
                this.login.error = data.error || 'Login failed.';
            }
        } catch {
            this.login.error = 'Something went wrong. Try again.';
        } finally {
            this.login.loading = false;
        }
    },

    async signOut() {
        this.menuOpen = false;
        await fetch(this.cfg.logoutUrl, { method: 'POST', headers: window.jsonHeaders(true) });
        window.location.href = '/';
    },

    openPwd() {
        this.menuOpen = false;
        this.pwd = { current: '', next: '', confirm: '', error: '', success: false, loading: false };
        this.showPwd = true;
    },

    async submitPwd() {
        this.pwd.error = '';
        this.pwd.success = false;
        if (!this.pwd.current || !this.pwd.next || !this.pwd.confirm) { this.pwd.error = 'Please fill in all fields.'; return; }
        if (this.pwd.next.length < 6) { this.pwd.error = 'New password must be at least 6 characters.'; return; }
        if (this.pwd.next !== this.pwd.confirm) { this.pwd.error = 'New passwords do not match.'; return; }
        this.pwd.loading = true;
        try {
            const res = await fetch(this.cfg.changePwdUrl, {
                method: 'POST',
                headers: window.jsonHeaders(true),
                body: JSON.stringify({ currentPassword: this.pwd.current, newPassword: this.pwd.next }),
            });
            if (res.ok) {
                this.pwd.success = true;
                setTimeout(() => { this.showPwd = false; this.pwd.success = false; }, 1600);
            } else {
                const data = await res.json().catch(() => ({}));
                this.pwd.error = data.error || 'Failed to change password.';
            }
        } catch {
            this.pwd.error = 'Something went wrong. Please try again.';
        } finally {
            this.pwd.loading = false;
        }
    },
}));

// ---------------------------------------------------------------------------
// Quote form — ported from components/forms/QuoteForm.tsx.
// ---------------------------------------------------------------------------
const FALLBACK_SERVICES = [
    { key: 'junk-removal', label: 'Junk Removal' },
    { key: '10yd-dumpster', label: '10 Yard Dumpster Rental' },
    { key: '20yd-dumpster', label: '20 Yard Dumpster Rental' },
    { key: 'equipment', label: 'Equipment Rental' },
    { key: 'other', label: 'Other / Not Sure' },
];

Alpine.data('quoteForm', () => ({
    status: 'idle',            // idle | loading | success | error
    submittedRef: null,
    // fields
    name: '', phone: '', email: '', description: '',
    jobType: 'service',        // 'service' | 'equipment' (pill toggle)
    serviceType: '', zipCode: '', preferredDay: '', preferredTime: '',
    website: '',               // honeypot
    preferredContactMethod: 'phone',
    urgency: 'routine',        // routine | urgent
    // service + equipment catalogs
    serviceOptions: FALLBACK_SERVICES,
    equipmentOptions: [],
    selectedEquipment: '',
    loadingEquipment: false,
    equipmentRentalDuration: '',
    equipmentRentalUnit: 'hours',
    // photo
    photo: null,               // { base64, mime, name }
    photoError: null,
    errors: {},

    init() {
        fetch(window.apiUrl('/api/services'))
            .then((r) => r.json())
            .then((d) => { if (d.services && d.services.length) this.serviceOptions = d.services; })
            .catch(() => {});
    },

    get isEquipment() { return this.jobType === 'equipment'; },

    // Multi-select chips: toggle a value within a comma-separated string field,
    // re-ordered to the canonical day/time order.
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

    // Service choices exclude the catalog's 'equipment' entry (that's the pill).
    get serviceChoices() { return this.serviceOptions.filter((o) => o.key !== 'equipment'); },
    // Catalog price for the chosen service (shown as the estimate).
    get selectedServicePrice() {
        if (this.isEquipment) return null;
        const svc = this.serviceOptions.find((s) => s.key === this.serviceType);
        return (svc && svc.default_price != null) ? svc.default_price : null;
    },

    setJobType(type) {
        if (this.jobType === type) return;
        this.jobType = type;
        if (type === 'equipment') {
            this.serviceType = 'equipment';
            if (this.equipmentOptions.length === 0) {
                this.loadingEquipment = true;
                fetch(window.apiUrl('/api/equipment'))
                    .then((r) => r.json())
                    .then((d) => { this.equipmentOptions = d.equipment || []; })
                    .catch(() => {})
                    .finally(() => { this.loadingEquipment = false; });
            }
        } else {
            if (this.serviceType === 'equipment') this.serviceType = '';
            this.selectedEquipment = '';
            this.equipmentRentalDuration = '';
            this.equipmentRentalUnit = 'hours';
        }
    },

    get computedEstimate() {
        if (this.serviceType !== 'equipment' || !this.selectedEquipment || !this.equipmentRentalDuration) return null;
        const equip = this.equipmentOptions.find((e) => e.name === this.selectedEquipment);
        if (!equip) return null;
        const qty = parseFloat(this.equipmentRentalDuration);
        if (!qty || qty <= 0) return null;
        if (this.equipmentRentalUnit === 'days') {
            if (equip.daily_rate) return Math.round(equip.daily_rate * qty);
            if (equip.avg_cost_per_hour) return Math.round(equip.avg_cost_per_hour * qty * 8);
            return null;
        }
        if (!equip.avg_cost_per_hour) return null;
        return Math.round(equip.avg_cost_per_hour * qty);
    },

    handlePhoto(e) {
        const file = e.target.files?.[0];
        if (!file) return;
        if (file.size > 5 * 1024 * 1024) { this.photoError = 'Photo must be smaller than 5MB'; return; }
        if (!file.type.startsWith('image/')) { this.photoError = 'Please upload an image file'; return; }
        this.photoError = null;
        const reader = new FileReader();
        reader.onload = (ev) => {
            const result = ev.target.result;
            const [header, base64] = result.split(',');
            const mime = (header.match(/:(.*?);/) || [])[1] || 'image/jpeg';
            this.photo = { base64, mime, name: file.name };
        };
        reader.readAsDataURL(file);
    },

    removePhoto() { this.photo = null; this.photoError = null; },

    validate() {
        this.errors = {};
        if (!this.name || this.name.length < 2) this.errors.name = 'Please enter your name';
        if (!this.phone || this.phone.length < 10) this.errors.phone = 'Please enter a valid phone number';
        if (!this.email || !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(this.email)) this.errors.email = 'Please enter a valid email address';
        if (!this.serviceType) this.errors.serviceType = 'Please select a service';
        if (this.isEquipment && !this.selectedEquipment) this.errors.equipment = 'Please select the equipment you need';
        if (!this.zipCode || this.zipCode.length < 5) this.errors.zipCode = 'Please enter a valid zip code';
        return Object.keys(this.errors).length === 0;
    },

    async submit() {
        if (!this.validate()) return;
        this.status = 'loading';
        const payload = {
            name: this.name, phone: this.phone, email: this.email,
            service_type: this.serviceType,
            description: this.description || null,
            photo_base64: this.photo?.base64 || null,
            photo_mime: this.photo?.mime || null,
            website: this.website,
            zip_code: this.zipCode,
            preferred_day: this.preferredDay || null,
            preferred_time: this.preferredTime || null,
            preferred_contact_method: this.preferredContactMethod,
            urgency: this.urgency,
        };
        if (this.serviceType === 'equipment' && this.selectedEquipment) {
            payload.equipment_type = this.selectedEquipment;
            if (this.equipmentRentalDuration) {
                payload.equipment_rental_duration = parseInt(this.equipmentRentalDuration, 10);
                payload.equipment_rental_unit = this.equipmentRentalUnit;
                const finalQuote = this.computedEstimate;
                if (finalQuote && !isNaN(finalQuote)) payload.initial_estimated_quote = Math.round(finalQuote);
            }
        }
        try {
            const res = await fetch(window.apiUrl('/api/quote'), { method: 'POST', headers: window.jsonHeaders(), body: JSON.stringify(payload) });
            const json = await res.json();
            if (!res.ok) throw new Error(json.error || 'Request failed');
            this.submittedRef = json.ref || null;
            this.status = 'success';
        } catch {
            this.status = 'error';
        }
    },

    money(n) { return Number(n).toLocaleString(); },
}));

// ---------------------------------------------------------------------------
// Status lookup — ported from app/status/page.tsx.
// ---------------------------------------------------------------------------
Alpine.data('statusLookup', () => ({
    phone: '', email: '',
    inquiries: [],
    loading: false,
    searched: false,
    error: '',
    tab: 'active',

    get filtered() {
        return this.inquiries.filter((inq) => {
            if (this.tab === 'active') return !['completed', 'cancelled'].includes(inq.status);
            if (this.tab === 'completed') return inq.status === 'completed';
            return true;
        });
    },

    async lookup() {
        if (!this.phone || !this.email) return;
        this.loading = true; this.error = ''; this.searched = true;
        try {
            const res = await fetch(window.apiUrl(`/api/lookup?phone=${encodeURIComponent(this.phone)}&email=${encodeURIComponent(this.email)}`));
            const data = await res.json();
            if (!res.ok) throw new Error(data.error);
            this.inquiries = data.inquiries || [];
        } catch {
            this.error = 'Something went wrong. Please try again or call us.';
            this.inquiries = [];
        } finally {
            this.loading = false;
        }
    },

    // --- display helpers ---
    statusLabel(s) {
        return ({
            new: 'New', left_voicemail: 'Left Voicemail', reviewing: 'Reviewing', quoted: 'Quoted',
            scheduled: 'Scheduled', equipment_delivered: 'Equipment Delivered', equipment_picked_up: 'Equipment Picked Up', service_performed: 'Service Performed', completed: 'Completed', cancelled: 'Cancelled',
        })[s] || 'New';
    },
    statusClass(s) {
        const map = {
            new: 'status-new', left_voicemail: 'status-reviewing', reviewing: 'status-reviewing', quoted: 'status-quoted',
            scheduled: 'status-scheduled', equipment_delivered: 'status-equipment_delivered', equipment_picked_up: 'status-equipment_picked_up', service_performed: 'status-service_performed', completed: 'status-completed', cancelled: 'status-cancelled',
        };
        return map[s] || 'status-new';
    },
    serviceLabel(s) { return (s || '').replace('-', ' '); },
    money(n) { return Number(n).toLocaleString(); },
    date(d) { return d ? new Date(d).toLocaleDateString() : ''; },
    dateLong(d) { return d ? new Date(d).toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' }) : ''; },
    dateTime(d) { return d ? new Date(d).toLocaleString() : ''; },
}));

// ---------------------------------------------------------------------------
// Rental agreement signing — ported from app/rental-agreement/[token]/page.tsx.
// Dependency-free canvas signature (mouse + touch).
// ---------------------------------------------------------------------------
Alpine.data('agreementForm', (token) => ({
    token,
    data: null,
    loading: true,
    error: '',
    submitting: false,
    signed: false,
    // signature canvas
    isDrawing: false,
    hasSignature: false,
    showSignaturePad: false, // full-screen pad (mobile)
    signatureDataUrl: null,  // captured from the full-screen pad
    _canvas: null, _bigDrawn: false,
    // validation
    invalidField: '', _flashT: null,
    // form fields
    agreed: false,
    customerNotes: '',
    pickupDate: '',
    pickupTime: '',

    init() {
        const today = new Date();
        this.pickupDate = today.toISOString().split('T')[0];
        fetch(window.apiUrl(`/api/rental-agreement/${this.token}`))
            .then(async (res) => {
                const json = await res.json();
                if (!res.ok) {
                    throw new Error(json.cancelled
                        ? 'This rental agreement link has been cancelled by the admin.'
                        : (json.error || 'Failed to load agreement'));
                }
                this.data = json;
                // If the admin already scheduled a pickup, prefill + display it
                // (the customer just confirms, rather than choosing a time).
                const pdt = json.inquiry?.pickup_date_time;
                if (pdt && pdt.includes('T')) {
                    this.pickupDate = pdt.split('T')[0];
                    this.pickupTime = (pdt.split('T')[1] || '').substring(0, 5);
                }
            })
            .catch((e) => { this.error = e.message || 'This link is invalid or has expired.'; })
            .finally(() => { this.loading = false; });
    },

    get inquiry() { return this.data?.inquiry ?? null; },
    get hasScheduledPickup() { const p = this.inquiry?.pickup_date_time; return !!(p && p.includes('T') && (p.split('T')[1] || '').length >= 4); },
    scheduledPickupLabel() {
        const p = this.inquiry?.pickup_date_time;
        if (!p) return '';
        const d = new Date(p);
        return isNaN(d.getTime()) ? '' : d.toLocaleString([], { weekday: 'long', month: 'long', day: 'numeric', hour: 'numeric', minute: '2-digit' });
    },
    firstName() { return (this.inquiry?.name || '').split(' ')[0] || ''; },
    lastName() { return (this.inquiry?.name || '').split(' ').slice(1).join(' ') || ''; },

    formatTime12Hour(t) {
        if (!t) return '';
        const [h, m] = t.split(':').map(Number);
        const period = h >= 12 ? 'PM' : 'AM';
        const h12 = h % 12 || 12;
        return `${h12}:${String(m).padStart(2, '0')} ${period}`;
    },

    confirmedDateTimeLong() {
        const d = this.inquiry?.confirmed_date_time;
        if (!d) return '—';
        return new Date(d).toLocaleString([], {
            weekday: 'long', month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit',
        });
    },

    durationLabel() {
        const i = this.inquiry;
        return (i?.equipment_rental_duration && i?.equipment_rental_unit)
            ? `${i.equipment_rental_duration} ${i.equipment_rental_unit}` : '—';
    },

    money(n) { return Number(n).toLocaleString(); },

    // --- canvas signature (works on the inline pad or the full-screen pad) ---
    coordsOn(canvas, e) {
        const rect = canvas.getBoundingClientRect();
        const sx = canvas.width / rect.width, sy = canvas.height / rect.height;
        const cx = e.touches ? e.touches[0].clientX : e.clientX;
        const cy = e.touches ? e.touches[0].clientY : e.clientY;
        return { x: (cx - rect.left) * sx, y: (cy - rect.top) * sy };
    },
    startDrawing(e) {
        const canvas = e.currentTarget;
        this._canvas = canvas;
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        if (!ctx) return;
        this.isDrawing = true;
        if (canvas === this.$refs.bigCanvas) {
            this._bigDrawn = true;
        } else {
            this.hasSignature = true;
            this.signatureDataUrl = null; // drawing inline replaces any captured signature
        }
        const { x, y } = this.coordsOn(canvas, e);
        ctx.strokeStyle = '#1C1C1C';
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';
        ctx.beginPath();
        ctx.moveTo(x, y);
    },
    draw(e) {
        if (!this.isDrawing || !this._canvas) return;
        const ctx = this._canvas.getContext('2d', { willReadFrequently: true });
        const { x, y } = this.coordsOn(this._canvas, e);
        ctx.lineTo(x, y);
        ctx.stroke();
    },
    endDrawing() { this.isDrawing = false; },
    clearSignature() {
        const c = this.$refs.canvas;
        if (c) c.getContext('2d').clearRect(0, 0, c.width, c.height);
        this.hasSignature = false;
        this.signatureDataUrl = null;
    },
    getSignatureData() {
        if (this.signatureDataUrl) return this.signatureDataUrl;
        const c = this.$refs.canvas;
        return (c && this.hasSignature) ? c.toDataURL('image/png') : null;
    },

    // --- full-screen signature pad ---
    openSignaturePad() {
        this.showSignaturePad = true;
        this._bigDrawn = false;
        this.$nextTick(() => {
            const c = this.$refs.bigCanvas;
            if (c) { const r = c.getBoundingClientRect(); c.width = Math.round(r.width); c.height = Math.round(r.height); c.getContext('2d').clearRect(0, 0, c.width, c.height); }
        });
    },
    clearBigPad() {
        const c = this.$refs.bigCanvas;
        if (c) c.getContext('2d').clearRect(0, 0, c.width, c.height);
        this._bigDrawn = false;
    },
    useBigSignature() {
        const c = this.$refs.bigCanvas;
        if (c && this._bigDrawn) {
            this.signatureDataUrl = c.toDataURL('image/png');
            this.hasSignature = true;
        }
        this.showSignaturePad = false;
    },

    // --- validation: scroll to the first incomplete field ---
    flag(ref, msg) {
        this.error = msg;
        this.invalidField = ref;
        this.$nextTick(() => {
            const el = this.$refs[ref];
            if (!el) return;
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            const f = el.matches?.('input,select,textarea') ? el : el.querySelector('input:not([type=hidden]),select,textarea');
            if (f && f.focus) { try { f.focus({ preventScroll: true }); } catch (e) {} }
        });
        clearTimeout(this._flashT);
        this._flashT = setTimeout(() => { this.invalidField = ''; }, 4000);
        return false;
    },
    validate() {
        const ack = this.$refs.ackSection;
        if (ack && [...ack.querySelectorAll('input[type="checkbox"]')].some((c) => !c.checked)) {
            return this.flag('ackSection', 'Please check all of the acknowledgment boxes.');
        }
        if (!this.hasScheduledPickup && !this.pickupTime) return this.flag('pickupTimeField', 'Please choose a pickup time.');
        if (!this.hasSignature && !this.signatureDataUrl) return this.flag('signatureField', 'Please add your signature.');
        if (!this.pickupDate) return this.flag('dateField', 'Please select the date.');
        if (!this.agreed) return this.flag('agreedField', 'Please confirm you agree to the terms.');
        this.error = '';
        return true;
    },

    async submit() {
        if (!this.validate()) return;
        const signatureData = this.getSignatureData();
        if (!signatureData) return this.flag('signatureField', 'Please add your signature.');

        this.submitting = true;
        this.error = '';
        try {
            const payload = {
                form_data: {
                    customer_notes: this.customerNotes || null,
                    agreed_to_terms: true,
                    signed_name: this.inquiry?.name || '',
                    pickup_date: this.pickupDate || null,
                    pickup_time: this.pickupTime ? this.formatTime12Hour(this.pickupTime) : null,
                    pickup_time_24: this.pickupTime || null,
                    pickup_was_scheduled: this.hasScheduledPickup,
                    inquiry_snapshot: this.inquiry,
                },
                signature_base64: signatureData,
            };
            const res = await fetch(window.apiUrl(`/api/rental-agreement/${this.token}`), {
                method: 'POST', headers: window.jsonHeaders(), body: JSON.stringify(payload),
            });
            const json = await res.json();
            if (!res.ok) throw new Error(json.error || 'Failed to submit agreement');
            this.signed = true;
        } catch (e) {
            this.error = e.message || 'Something went wrong. Please try again or call us.';
        } finally {
            this.submitting = false;
        }
    },
}));

// ---------------------------------------------------------------------------
// Public payment page — loads the quote amount for a tokenized link and lets
// the customer complete payment. Placeholder gateway: pay() records payment.
// ---------------------------------------------------------------------------
Alpine.data('paymentForm', (token) => ({
    token,
    data: null,
    loading: true,
    error: '',
    submitting: false,
    paid: false,
    confirming: false,   // returned from Stripe; verifying the payment
    confirmNote: '',

    init() {
        const params = new URLSearchParams(window.location.search);
        const returned = params.get('status');
        const sessionId = params.get('session_id');

        fetch(window.apiUrl(`/api/payment/${this.token}`))
            .then(async (res) => {
                const json = await res.json();
                if (!res.ok) {
                    if (json.alreadyPaid) { this.data = json; this.paid = true; return; }
                    throw new Error(json.error || 'Failed to load payment');
                }
                this.data = json;
                // Coming back from Stripe Checkout — verify and reflect the result.
                if (returned === 'success' && sessionId) {
                    this.confirming = true;
                    this.confirmReturn(sessionId);
                }
            })
            .catch((e) => { this.error = e.message || 'This link is invalid or has expired.'; })
            .finally(() => { this.loading = false; });
    },

    // Poll the confirm endpoint after returning from Stripe. The webhook is the
    // authoritative backstop, so on timeout we show a reassuring "processing"
    // message rather than re-offering the Pay button (avoids a double charge).
    async confirmReturn(sessionId, attempt = 0) {
        try {
            const res = await fetch(window.apiUrl(`/api/payment/${this.token}/confirm`), {
                method: 'POST', headers: window.jsonHeaders(), body: JSON.stringify({ session_id: sessionId }),
            });
            const json = await res.json();
            if (json.paid) { this.paid = true; this.confirming = false; return; }
        } catch { /* retry below */ }

        if (attempt < 3) {
            setTimeout(() => this.confirmReturn(sessionId, attempt + 1), 2000);
        } else {
            this.confirmNote = 'Your payment is processing — a receipt will follow shortly. You can close this page.';
        }
    },

    get inquiry() { return this.data?.inquiry ?? null; },
    get business() { return this.data?.business ?? null; },
    get amount() { return this.data?.payment?.amount ?? 0; },

    money(n) { return Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },

    summaryLine() {
        const i = this.inquiry;
        if (!i) return '—';
        if (i.equipment_type) {
            const dur = (i.equipment_rental_duration && i.equipment_rental_unit)
                ? ` (${i.equipment_rental_duration} ${i.equipment_rental_unit})` : '';
            return `${i.equipment_type}${dur}`;
        }
        return (i.service_type || '—').replace(/-/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    },

    scheduledLabel() {
        const d = this.inquiry?.confirmed_date_time;
        if (!d) return '—';
        return new Date(d).toLocaleString([], {
            weekday: 'short', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit',
        });
    },

    async pay() {
        this.submitting = true;
        this.error = '';
        try {
            const res = await fetch(window.apiUrl(`/api/payment/${this.token}`), {
                method: 'POST', headers: window.jsonHeaders(), body: JSON.stringify({}),
            });
            const json = await res.json();
            if (!res.ok) throw new Error(json.error || 'Payment could not be completed.');
            // Stripe configured → redirect to the hosted checkout. Placeholder → done.
            if (json.checkout_url) { window.location.href = json.checkout_url; return; }
            if (json.success) { this.paid = true; }
        } catch (e) {
            this.error = e.message || 'Something went wrong. Please try again or call us.';
            this.submitting = false;
        }
    },
}));

export {};
