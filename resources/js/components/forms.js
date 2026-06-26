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
    _photoPending: false,      // true while a selected photo is still being read
    errors: {},

    init() {
        fetch(window.apiUrl('/api/services'))
            .then((r) => r.json())
            .then((d) => { if (d.services && d.services.length) this.serviceOptions = d.services; })
            .catch(() => {});

        // Flat-rate rentals are priced by the day — switch the unit and prefill the
        // included days when such an item is picked.
        this.$watch('selectedEquipment', () => {
            const e = this.selectedEquipmentObj;
            if (e && e.flat_price) {
                this.equipmentRentalUnit = 'days';
                if (!this.equipmentRentalDuration) this.equipmentRentalDuration = e.included_days || '';
            }
        });
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

    get selectedEquipmentObj() { return this.equipmentOptions.find((e) => e.name === this.selectedEquipment) || null; },
    get selectedIsFlatRate() { const e = this.selectedEquipmentObj; return !!(e && e.flat_price); },

    // Equipment dropdown price hint.
    equipPriceHint(opt) {
        if (opt.flat_price) return ' — $' + Number(opt.flat_price).toLocaleString();
        if (opt.avg_cost_per_hour) return ' — ~$' + opt.avg_cost_per_hour + '/hr';
        return '';
    },
    // Flat-rate inclusions / overages (for the breakdown card).
    flatInclusions() {
        const e = this.selectedEquipmentObj;
        if (!e || !e.flat_price) return [];
        const inc = [];
        if (e.included_days) inc.push(`Up to ${e.included_days} day${e.included_days > 1 ? 's' : ''}`);
        inc.push('Delivery', 'Pickup');
        if (e.included_tons) inc.push(`${this.money(e.included_tons)} ton disposal included`);
        return inc;
    },
    flatOverages() {
        const e = this.selectedEquipmentObj;
        if (!e || !e.flat_price) return [];
        const o = [];
        if (e.price_per_additional_ton) o.push(`$${this.money(e.price_per_additional_ton)} per additional ton`);
        if (e.price_per_additional_day && e.included_days) o.push(`$${this.money(e.price_per_additional_day)} per additional day after day ${e.included_days}`);
        return o;
    },

    get computedEstimate() {
        if (this.serviceType !== 'equipment' || !this.selectedEquipment || !this.equipmentRentalDuration) return null;
        const equip = this.selectedEquipmentObj;
        if (!equip) return null;
        const qty = parseFloat(this.equipmentRentalDuration);
        if (!qty || qty <= 0) return null;
        // Flat-rate (dumpster/trailer): base price + extra-day charges. Tonnage
        // overage is billed later (only known at disposal), so it's not added here.
        if (equip.flat_price) {
            const extraDays = Math.max(0, qty - (equip.included_days || 0));
            return Math.round(Number(equip.flat_price) + extraDays * Number(equip.price_per_additional_day || 0));
        }
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
        // Don't hard-reject on MIME type — iPhones often report an empty or non-standard
        // type (e.g. "" or image/heic) for real photos. accept="image/*" filters the picker.
        if (file.type && !file.type.startsWith('image/')) { this.photoError = 'Please upload an image file'; return; }
        this.photoError = null;
        this._photoPending = true;
        const reader = new FileReader();
        reader.onerror = () => { this._photoPending = false; this.photoError = 'Couldn’t read that photo — please try a different one.'; };
        reader.onload = (ev) => {
            const result = ev.target.result;
            const [header, base64] = result.split(',');
            const mime = (header.match(/:(.*?);/) || [])[1] || 'image/jpeg';
            this.photo = { base64, mime, name: file.name };
            this._photoPending = false;
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
        // A photo may still be reading (slow on iPhones for large photos). Wait briefly
        // so a fast tap doesn't post before the photo is attached.
        if (this._photoPending) {
            let waited = 0;
            while (this._photoPending && waited < 8000) {
                await new Promise((r) => setTimeout(r, 150));
                waited += 150;
            }
        }
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

    // Full address — composed from the structured parts (street, city, state, zip),
    // falling back to the stored combined address.
    fullAddress() {
        const i = this.inquiry;
        if (!i) return '';
        if (i.address_street || i.address_city) {
            const tail = [i.address_state, i.zip_code].map((s) => (s || '').trim()).filter(Boolean).join(' ');
            return [i.address_street, i.address_city, tail].map((s) => (s || '').trim()).filter(Boolean).join(', ');
        }
        return i.address || '';
    },
    // One structured address part for the separate fields (legacy records with no
    // parts fall the whole combined address into Street).
    addrPart(which) {
        const i = this.inquiry;
        if (!i) return '';
        if (i.address_street || i.address_city) {
            return ({ street: i.address_street, city: i.address_city, state: i.address_state, zip: i.zip_code }[which]) || '';
        }
        return which === 'street' ? (i.address || '') : '';
    },

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
// quoteDetailsForm — customer "request details" page (one-time tokenized link).
// The customer completes their details, confirms the scheduled date/time + the
// quoted amount, and signs. On submit the quote moves to "Finalize Scheduling".
// The phone number is shown read-only and is never submitted.
// ---------------------------------------------------------------------------
Alpine.data('quoteDetailsForm', (token, needsAgreement = false) => ({
    token,
    needsAgreement,               // server-resolved: item has an attached, unsigned agreement
    data: null,
    loading: true,
    error: '',
    submitting: false,
    submitted: false,
    // signature pads — two slots: 'info' (confirm details) + 'agreement' (rental
    // terms). They share one full-screen pad; _padTarget tracks which it edits.
    isDrawing: false,
    hasSignature: false,
    signatureDataUrl: null,
    agreementHasSignature: false,
    agreementSignatureDataUrl: null,
    showSignaturePad: false,
    _padTarget: 'info',
    _canvas: null, _bigDrawn: false,
    invalidField: '', _flashT: null,
    // editable fields
    firstName: '', lastName: '', email: '', zipCode: '',
    addressStreet: '', addressCity: '', addressState: 'CA',
    preferredDay: '', preferredTime: '', preferredContactMethod: 'phone',
    confirmDatetime: false, confirmAmount: false,
    agreedToTerms: false,         // checked the "I agree to the rental terms" box
    photos: [], photoError: '', _pendingPhotos: 0,   // up to 2 customer photos (JPEG data URLs)

    init() {
        fetch(window.apiUrl(`/api/quote-details/${this.token}`))
            .then(async (res) => {
                const json = await res.json();
                if (!res.ok) {
                    throw new Error(json.cancelled ? 'This link has been cancelled.' : (json.error || 'Failed to load'));
                }
                this.data = json;
                const i = json.inquiry || {};
                this.firstName = (i.name || '').split(' ')[0] || '';
                this.lastName = (i.name || '').split(' ').slice(1).join(' ') || '';
                this.email = i.email || '';
                this.addressStreet = i.address_street || '';
                this.addressCity = i.address_city || '';
                this.addressState = i.address_state || 'CA';
                this.zipCode = i.zip_code || '';
                this.preferredDay = i.preferred_day || '';
                this.preferredTime = i.preferred_time || '';
                this.preferredContactMethod = i.preferred_contact_method === 'email' ? 'email' : 'phone';
            })
            .catch((e) => { this.error = e.message || 'This link is invalid or has expired.'; })
            .finally(() => { this.loading = false; });
    },

    get inquiry() { return this.data?.inquiry ?? null; },
    money(n) { return Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
    // What the quote is for — the service, or the equipment + rental duration.
    forLabel() {
        const i = this.inquiry;
        if (!i) return '';
        if (i.equipment_type) {
            const dur = (i.equipment_rental_duration && i.equipment_rental_unit) ? ` — ${i.equipment_rental_duration} ${i.equipment_rental_unit}` : '';
            return i.equipment_type + dur;
        }
        const labels = { 'junk-removal': 'Junk Removal', '10yd-dumpster': '10 Yard Dumpster Rental', '20yd-dumpster': '20 Yard Dumpster Rental', equipment: 'Equipment Rental', other: 'Other' };
        return labels[i.service_type] || (i.service_type || '').replace(/-/g, ' ');
    },
    confirmedDateTimeLong() {
        const d = this.inquiry?.confirmed_date_time;
        if (!d) return '—';
        const dt = new Date(d);
        return isNaN(dt.getTime()) ? '—' : dt.toLocaleString([], { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
    },

    // multi-select preferences (comma-joined; same format as the admin form)
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
    prefHas(field, value) { return String(this[field] || '').split(',').map((s) => s.trim()).includes(value); },

    // --- Photo upload (up to 2; resized + re-encoded to JPEG so they always load) ---
    addPhotos(event) {
        this.photoError = '';
        const files = Array.from(event.target.files || []);
        for (const file of files) {
            if (this.photos.length + this._pendingPhotos >= 2) { this.photoError = 'You can attach up to 2 photos — remove one to add another.'; break; }
            // Don't hard-reject on MIME type — iPhones often report an empty or non-standard
            // type (e.g. "" or image/heic) for real photos, especially via "Choose Files".
            // accept="image/*" already filters the picker; the Image decode below is the real
            // validator (its onerror catches anything that isn't a usable image).
            if (file.type && !file.type.startsWith('image/')) { this.photoError = `"${file.name}" isn't an image. Please choose a photo (JPG or PNG).`; continue; }
            if (file.size > 10 * 1024 * 1024) { this.photoError = `"${file.name}" is ${(file.size / 1048576).toFixed(1)}MB — please choose a photo under 10MB.`; continue; }
            this._processPhoto(file);
        }
        event.target.value = '';   // allow re-selecting the same file
    },
    _processPhoto(file) {
        this._pendingPhotos++;
        const done = () => { this._pendingPhotos = Math.max(0, this._pendingPhotos - 1); };
        const reader = new FileReader();
        reader.onerror = () => { done(); this.photoError = `Couldn't read "${file.name}". Please try a different photo.`; };
        reader.onload = () => {
            const img = new Image();
            // If the browser can't decode it (e.g. an iPhone HEIC photo on a non-Apple browser), say so clearly.
            img.onerror = () => { done(); this.photoError = `"${file.name}" couldn't be opened. If it's an iPhone HEIC photo, set your camera to "Most Compatible" (JPEG) or upload a screenshot of it instead.`; };
            img.onload = () => {
                done();
                try {
                    const max = 1600;
                    let w = img.naturalWidth || img.width, h = img.naturalHeight || img.height;
                    if (!w || !h) throw new Error('empty');
                    if (w > max || h > max) { const s = max / Math.max(w, h); w = Math.round(w * s); h = Math.round(h * s); }
                    const canvas = document.createElement('canvas');
                    canvas.width = w; canvas.height = h;
                    canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                    const url = canvas.toDataURL('image/jpeg', 0.82);
                    if (this.photos.length < 2) this.photos.push({ url, name: file.name });
                } catch (e) {
                    this.photoError = `Couldn't process "${file.name}". Please try a different photo.`;
                }
            };
            img.src = reader.result;
        };
        reader.readAsDataURL(file);
    },
    removePhoto(i) { this.photos.splice(i, 1); this.photoError = ''; },

    // --- canvas signature (works on the inline pad or the full-screen pad) ---
    coordsOn(canvas, e) {
        const rect = canvas.getBoundingClientRect();
        const sx = canvas.width / rect.width, sy = canvas.height / rect.height;
        const cx = e.touches ? e.touches[0].clientX : e.clientX;
        const cy = e.touches ? e.touches[0].clientY : e.clientY;
        return { x: (cx - rect.left) * sx, y: (cy - rect.top) * sy };
    },
    // The 'agreement' slot uses its own canvas/state; everything else defaults to 'info'.
    startDrawing(e, slot) {
        const canvas = e.currentTarget;
        this._canvas = canvas;
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        if (!ctx) return;
        this.isDrawing = true;
        if (canvas === this.$refs.bigCanvas) {
            this._bigDrawn = true;
        } else if (slot === 'agreement') {
            this.agreementHasSignature = true;
            this.agreementSignatureDataUrl = null;
        } else {
            this.hasSignature = true;
            this.signatureDataUrl = null;
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
    _padCanvas(slot) { return slot === 'agreement' ? this.$refs.agreementCanvas : this.$refs.canvas; },
    clearSignature(slot) {
        const c = this._padCanvas(slot);
        if (c) c.getContext('2d').clearRect(0, 0, c.width, c.height);
        if (slot === 'agreement') { this.agreementHasSignature = false; this.agreementSignatureDataUrl = null; }
        else { this.hasSignature = false; this.signatureDataUrl = null; }
    },
    getSignatureData(slot) {
        if (slot === 'agreement') {
            if (this.agreementSignatureDataUrl) return this.agreementSignatureDataUrl;
            const c = this.$refs.agreementCanvas;
            return (c && this.agreementHasSignature) ? c.toDataURL('image/png') : null;
        }
        if (this.signatureDataUrl) return this.signatureDataUrl;
        const c = this.$refs.canvas;
        return (c && this.hasSignature) ? c.toDataURL('image/png') : null;
    },
    openSignaturePad(slot) {
        this._padTarget = slot === 'agreement' ? 'agreement' : 'info';
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
            if (this._padTarget === 'agreement') { this.agreementSignatureDataUrl = c.toDataURL('image/png'); this.agreementHasSignature = true; }
            else { this.signatureDataUrl = c.toDataURL('image/png'); this.hasSignature = true; }
        }
        this.showSignaturePad = false;
    },

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
        if (!this.firstName.trim()) return this.flag('nameField', 'Please enter your first name.');
        if (!this.lastName.trim()) return this.flag('nameField', 'Please enter your last name.');
        if (!this.addressStreet.trim() || !this.addressCity.trim()) return this.flag('addressField', 'Please enter your street address and city.');
        if (!this.zipCode.trim()) return this.flag('addressField', 'Please enter your zip code.');
        // Email is required when there's an agreement (we email the signed copy), or
        // when the customer chose email as their contact method.
        if (this.needsAgreement && !this.email.trim()) return this.flag('emailField', 'Please enter your email — your signed rental agreement will be sent there.');
        if (this.preferredContactMethod === 'email' && !this.email.trim()) return this.flag('emailField', 'Please enter your email — you chose email as your contact method.');
        if (!this.confirmDatetime || !this.confirmAmount) return this.flag('confirmField', 'Please confirm the scheduled date/time and the quoted amount.');
        if (!this.hasSignature && !this.signatureDataUrl) return this.flag('signatureField', 'Please add your signature confirming your details.');

        // Rental agreement section (only when required).
        if (this.needsAgreement) {
            const ack = this.$refs.ackSection;
            if (ack && [...ack.querySelectorAll('input[type="checkbox"]')].some((c) => !c.checked)) {
                return this.flag('ackSection', 'Please check all of the rental agreement acknowledgments.');
            }
            if (!this.agreementHasSignature && !this.agreementSignatureDataUrl) return this.flag('agreementSignatureField', 'Please sign the rental agreement.');
            if (!this.agreedToTerms) return this.flag('agreedField', 'Please confirm you agree to the rental agreement terms.');
        }
        this.error = '';
        return true;
    },

    async submit() {
        if (!this.validate()) return;
        const signatureData = this.getSignatureData('info');
        if (!signatureData) return this.flag('signatureField', 'Please add your signature.');
        const agreementSignature = this.needsAgreement ? this.getSignatureData('agreement') : null;
        if (this.needsAgreement && !agreementSignature) return this.flag('agreementSignatureField', 'Please sign the rental agreement.');

        this.submitting = true;
        // A photo may still be decoding/re-encoding (slow on iPhones for large photos).
        // Wait briefly so a fast tap doesn't drop the photo or post before it's ready.
        if (this._pendingPhotos > 0) {
            let waited = 0;
            while (this._pendingPhotos > 0 && waited < 8000) {
                await new Promise((r) => setTimeout(r, 150));
                waited += 150;
            }
        }
        this.error = '';
        try {
            const name = [this.firstName.trim(), this.lastName.trim()].filter(Boolean).join(' ');
            const payload = {
                form_data: {
                    name,
                    email: this.email.trim() || null,
                    address_street: this.addressStreet.trim(),
                    address_city: this.addressCity.trim(),
                    address_state: (this.addressState.trim() || 'CA'),
                    zip_code: this.zipCode.trim() || null,
                    preferred_day: this.preferredDay || null,
                    preferred_time: this.preferredTime || null,
                    preferred_contact_method: this.preferredContactMethod,
                    confirm_datetime: true,
                    confirm_amount: true,
                    signed_name: name,
                    photos: this.photos.map((p) => p.url),
                    inquiry_snapshot: this.inquiry,
                    agreed_to_terms: this.needsAgreement ? true : undefined,
                },
                signature_base64: signatureData,
                agreement_signature_base64: agreementSignature,
            };
            const body = JSON.stringify(payload);
            // Photos can make the request exceed the server's upload limit; catch it here
            // with a clear message instead of a failed/oversized POST. (Photos are
            // re-encoded to ~1600px JPEG first, so two of them stay well under this.)
            if (body.length > 12_000_000) {
                throw new Error('Your photos are too large to upload. Please remove a photo (or use a smaller one) and try again.');
            }
            const res = await fetch(window.apiUrl(`/api/quote-details/${this.token}`), {
                method: 'POST', headers: window.jsonHeaders(), body,
            });
            // A rejected (too-large) request returns a non-JSON body — Safari reports that as
            // "The string did not match the expected pattern", so handle it gracefully.
            let json = null;
            try { json = await res.json(); } catch { json = null; }
            if (!res.ok || !json) {
                throw new Error((json && json.error) || (res.status === 413
                    ? 'Your photos are too large to upload. Please remove a photo (or use a smaller one) and try again.'
                    : 'We couldn’t submit your details — please remove any large photos and try again, or call us if it keeps happening.'));
            }
            this.submitted = true;
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
