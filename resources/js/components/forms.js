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
    serviceType: '', zipCode: '', preferredDay: '', preferredTime: '',
    website: '',               // honeypot
    preferredContactMethod: 'phone',
    // service + equipment catalogs
    serviceOptions: FALLBACK_SERVICES,
    equipmentOptions: [],
    selectedEquipment: '',
    loadingEquipment: false,
    equipmentRentalDuration: '',
    equipmentRentalUnit: 'hours',
    customerSuggestedQuote: '',
    // photo
    photo: null,               // { base64, mime, name }
    photoError: null,
    errors: {},

    init() {
        fetch('/api/services')
            .then((r) => r.json())
            .then((d) => { if (d.services && d.services.length) this.serviceOptions = d.services; })
            .catch(() => {});
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

    onServiceChange() {
        if (this.serviceType === 'equipment') {
            if (this.equipmentOptions.length === 0) {
                this.loadingEquipment = true;
                fetch('/api/equipment')
                    .then((r) => r.json())
                    .then((d) => { this.equipmentOptions = d.equipment || []; })
                    .catch(() => {})
                    .finally(() => { this.loadingEquipment = false; });
            }
        } else {
            this.selectedEquipment = '';
            this.equipmentRentalDuration = '';
            this.equipmentRentalUnit = 'hours';
            this.customerSuggestedQuote = '';
        }
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
        };
        if (this.serviceType === 'equipment' && this.selectedEquipment) {
            payload.equipment_type = this.selectedEquipment;
            if (this.equipmentRentalDuration) {
                payload.equipment_rental_duration = parseInt(this.equipmentRentalDuration, 10);
                payload.equipment_rental_unit = this.equipmentRentalUnit;
                const finalQuote = this.customerSuggestedQuote
                    ? parseFloat(this.customerSuggestedQuote)
                    : this.computedEstimate;
                if (finalQuote && !isNaN(finalQuote)) payload.initial_estimated_quote = Math.round(finalQuote);
            }
        }
        try {
            const res = await fetch('/api/quote', { method: 'POST', headers: window.jsonHeaders(), body: JSON.stringify(payload) });
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
            const res = await fetch(`/api/lookup?phone=${encodeURIComponent(this.phone)}&email=${encodeURIComponent(this.email)}`);
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
            scheduled: 'Scheduled', service_performed: 'Service Performed', completed: 'Completed', cancelled: 'Cancelled',
        })[s] || 'New';
    },
    statusClass(s) {
        const map = {
            new: 'status-new', left_voicemail: 'status-reviewing', reviewing: 'status-reviewing', quoted: 'status-quoted',
            scheduled: 'status-scheduled', service_performed: 'status-service_performed', completed: 'status-completed', cancelled: 'status-cancelled',
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
    // form fields
    agreed: false,
    customerNotes: '',
    pickupDate: '',
    pickupTime: '',

    init() {
        const today = new Date();
        this.pickupDate = today.toISOString().split('T')[0];
        fetch(`/api/rental-agreement/${this.token}`)
            .then(async (res) => {
                const json = await res.json();
                if (!res.ok) {
                    throw new Error(json.cancelled
                        ? 'This rental agreement link has been cancelled by the admin.'
                        : (json.error || 'Failed to load agreement'));
                }
                this.data = json;
            })
            .catch((e) => { this.error = e.message || 'This link is invalid or has expired.'; })
            .finally(() => { this.loading = false; });
    },

    get inquiry() { return this.data?.inquiry ?? null; },
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

    // --- canvas signature ---
    getCtx() {
        const c = this.$refs.canvas;
        return c ? c.getContext('2d', { willReadFrequently: true }) : null;
    },
    scaledCoords(e) {
        const c = this.$refs.canvas;
        const rect = c.getBoundingClientRect();
        const scaleX = c.width / rect.width;
        const scaleY = c.height / rect.height;
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        return { x: (clientX - rect.left) * scaleX, y: (clientY - rect.top) * scaleY };
    },
    startDrawing(e) {
        const ctx = this.getCtx();
        if (!ctx) return;
        this.isDrawing = true;
        this.hasSignature = true;
        const { x, y } = this.scaledCoords(e);
        ctx.strokeStyle = '#1C1C1C';
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';
        ctx.beginPath();
        ctx.moveTo(x, y);
    },
    draw(e) {
        if (!this.isDrawing) return;
        const ctx = this.getCtx();
        if (!ctx) return;
        const { x, y } = this.scaledCoords(e);
        ctx.lineTo(x, y);
        ctx.stroke();
    },
    endDrawing() { this.isDrawing = false; },
    clearSignature() {
        const ctx = this.getCtx();
        const c = this.$refs.canvas;
        if (!ctx || !c) return;
        ctx.clearRect(0, 0, c.width, c.height);
        this.hasSignature = false;
    },
    getSignatureData() {
        const c = this.$refs.canvas;
        if (!c || !this.hasSignature) return null;
        return c.toDataURL('image/png');
    },

    async submit() {
        if (!this.agreed) { alert('Please confirm you have read and agree to the terms.'); return; }
        const signatureData = this.getSignatureData();
        if (!signatureData) { alert('Please provide your signature in the box above.'); return; }
        if (!this.pickupDate || !this.pickupTime) { alert('Please select both the date and pickup time.'); return; }

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
                    inquiry_snapshot: this.inquiry,
                },
                signature_base64: signatureData,
            };
            const res = await fetch(`/api/rental-agreement/${this.token}`, {
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

export {};
