import './bootstrap';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

const SERVICE_ICONS = {
    whatsapp: 'fa-brands fa-whatsapp',
    google: 'fa-brands fa-google',
    instagram: 'fa-brands fa-instagram',
    facebook: 'fa-brands fa-facebook',
    telegram: 'fa-brands fa-telegram',
    tiktok: 'fa-brands fa-tiktok',
};

// Flag emojis for the country slugs most likely to appear. Verified against a live,
// unauthenticated call to 5sim's GET /guest/countries during action_05: that endpoint
// uses no separators in slugs (e.g. "ivorycoast", "burkinafaso") and, notably, does not
// list "russia" or "mali"/"niger" at all despite 5sim being a Russian service - so those
// are intentionally omitted rather than mapped to a slug that will never match. Anything
// unrecognized falls back to a globe rather than guessing wrong.
const COUNTRY_FLAGS = {
    usa: '🇺🇸',
    england: '🇬🇧',
    france: '🇫🇷',
    ivorycoast: '🇨🇮',
    senegal: '🇸🇳',
    benin: '🇧🇯',
    cameroon: '🇨🇲',
    nigeria: '🇳🇬',
    ghana: '🇬🇭',
    togo: '🇹🇬',
    burkinafaso: '🇧🇫',
    guinea: '🇬🇳',
};

function serviceIcon(code) {
    return SERVICE_ICONS[code] ?? 'fa-solid fa-mobile-screen';
}

function countryFlag(code) {
    return COUNTRY_FLAGS[code] ?? '🌍';
}

function csrfHeaders(extra = {}) {
    return {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        Accept: 'application/json',
        ...extra,
    };
}

/* ── Shared catalog/order fetchers - used by both the legacy /acheter page's
   purchaseForm and the single-page homepage, so the AJAX logic lives in one place. ── */

async function fetchCountries() {
    const response = await fetch('/api/countries');
    const json = await response.json();

    return json.data ?? [];
}

async function fetchServicesForCountry(country) {
    if (!country) {
        return [];
    }

    const response = await fetch(`/api/services?country=${encodeURIComponent(country)}`);
    const json = await response.json();

    return json.data ?? [];
}

async function fetchPriceFor(service, country) {
    if (!service || !country) {
        return null;
    }

    const response = await fetch(
        `/api/price?service=${encodeURIComponent(service)}&country=${encodeURIComponent(country)}`,
    );

    if (!response.ok) {
        return null;
    }

    return response.json();
}

async function fetchOrderStatus(orderId) {
    const response = await fetch(`/api/orders/${orderId}/status`);

    if (!response.ok) {
        return null;
    }

    return response.json();
}

async function requestCancelOrder(orderId) {
    const response = await fetch(`/commande/${orderId}/annuler`, {
        method: 'POST',
        headers: csrfHeaders(),
    });

    const json = await response.json().catch(() => ({}));

    return { ok: response.ok, json };
}

Alpine.data('purchaseForm', (initialService = '', initialCountry = '') => ({
    service: initialService,
    country: initialCountry,
    services: [],
    countries: [],
    serviceFilter: '',
    countryFilter: '',
    priceUsd: null,
    priceFcfa: null,
    loadingPrice: false,
    showConfirm: false,

    get filteredServices() {
        const term = this.serviceFilter.toLowerCase();

        return this.services.filter((item) => item.label.toLowerCase().includes(term));
    },

    get filteredCountries() {
        const term = this.countryFilter.toLowerCase();

        return this.countries.filter((item) => item.name.toLowerCase().includes(term));
    },

    get selectedServiceLabel() {
        const found = this.services.find((item) => item.code === this.service);

        return found ? found.label : this.service;
    },

    get selectedCountryLabel() {
        const found = this.countries.find((item) => item.code === this.country);

        return found ? found.name : this.country;
    },

    get priceLabel() {
        if (this.loadingPrice) {
            return 'Calcul du prix...';
        }

        return this.priceFcfa !== null
            ? new Intl.NumberFormat('fr-FR').format(this.priceFcfa) + ' FCFA'
            : 'Sélectionnez un service et un pays';
    },

    serviceIcon,
    countryFlag,

    async loadServices() {
        this.services = await fetchServicesForCountry(this.country);
    },

    async loadCountries() {
        this.countries = await fetchCountries();
    },

    async fetchPrice() {
        if (!this.service || !this.country) {
            this.priceUsd = null;
            this.priceFcfa = null;
            return;
        }

        this.loadingPrice = true;

        try {
            const json = await fetchPriceFor(this.service, this.country);
            this.priceUsd = json?.price_usd ?? null;
            this.priceFcfa = json?.price_fcfa ?? null;
        } finally {
            this.loadingPrice = false;
        }
    },

    selectService(code) {
        this.service = code;
        this.fetchPrice();
    },

    selectCountry(code) {
        this.country = code;
        this.service = '';
        this.priceUsd = null;
        this.priceFcfa = null;
        this.loadServices();
    },

    init() {
        this.loadCountries();

        if (this.country) {
            this.loadServices();
        }

        if (this.service && this.country) {
            this.fetchPrice();
        }
    },
}));

Alpine.data('orderWaiting', (orderId, expiresAtIso, initialStatus = 'pending', initialSmsCode = null) => ({
    orderId,
    status: initialStatus,
    smsCode: initialSmsCode,
    expiresAtTimestamp: expiresAtIso ? new Date(expiresAtIso).getTime() : Date.now(),
    totalSeconds: 0,
    secondsRemaining: 0,
    countdownInterval: null,
    pollInterval: null,
    copiedPhone: false,
    copiedSms: false,
    timedOut: false,
    cancelling: false,
    cancelError: null,

    async cancelOrder() {
        if (!confirm('Voulez-vous vraiment annuler cette commande ? Le montant sera remboursé sur votre solde.')) {
            return;
        }

        this.cancelling = true;
        this.cancelError = null;

        try {
            const { ok, json } = await requestCancelOrder(this.orderId);

            if (ok) {
                clearInterval(this.countdownInterval);
                clearInterval(this.pollInterval);
                window.location.href = '/dashboard';
                return;
            }

            this.cancelError = json.message ?? "Impossible d'annuler cette commande pour le moment.";
        } finally {
            this.cancelling = false;
        }
    },

    copyPhone(phone) {
        navigator.clipboard.writeText(phone);
        this.copiedPhone = true;
        setTimeout(() => {
            this.copiedPhone = false;
        }, 2000);
    },

    copySms() {
        if (!this.smsCode) {
            return;
        }

        navigator.clipboard.writeText(this.smsCode);
        this.copiedSms = true;
        setTimeout(() => {
            this.copiedSms = false;
        }, 2000);
    },

    get expired() {
        return this.secondsRemaining <= 0;
    },

    get minutes() {
        return Math.floor(this.secondsRemaining / 60);
    },

    get seconds() {
        return String(this.secondsRemaining % 60).padStart(2, '0');
    },

    get progressPercent() {
        if (this.totalSeconds <= 0) {
            return 0;
        }

        return Math.max(0, Math.min(100, (this.secondsRemaining / this.totalSeconds) * 100));
    },

    tickCountdown() {
        const remainingMs = this.expiresAtTimestamp - Date.now();
        this.secondsRemaining = Math.max(0, Math.round(remainingMs / 1000));

        if (this.secondsRemaining <= 0) {
            clearInterval(this.countdownInterval);

            // Reaching zero with no SMS received: stop polling and show the timeout state.
            if (!this.smsCode) {
                this.timedOut = true;
                clearInterval(this.pollInterval);
            }
        }
    },

    async pollStatus() {
        if (this.smsCode || this.expired) {
            clearInterval(this.pollInterval);
            return;
        }

        try {
            const json = await fetchOrderStatus(this.orderId);

            if (!json) {
                return;
            }

            this.status = json.status;

            if (json.sms_code) {
                this.smsCode = json.sms_code;
                clearInterval(this.pollInterval);
            }
        } catch {
            // Transient network hiccup: leave the interval running for the next attempt.
        }
    },

    init() {
        this.secondsRemaining = Math.max(0, Math.round((this.expiresAtTimestamp - Date.now()) / 1000));
        this.totalSeconds = this.secondsRemaining;

        // Covers reloading an already-expired waiting page without waiting for the
        // first 1-second tick to notice.
        if (this.expired && !this.smsCode) {
            this.timedOut = true;
        }

        this.countdownInterval = setInterval(() => this.tickCountdown(), 1000);

        if (!this.smsCode && !this.expired) {
            this.pollStatus();
            this.pollInterval = setInterval(() => this.pollStatus(), 5000);
        }
    },
}));

/* ══════════════════════════════════════════════════════════════════════════
   SINGLE-PAGE HOMEPAGE (Zen_Sms) - card/modal-driven checkout experience.
   See resources/views/home.blade.php. Reuses the fetch helpers above rather
   than duplicating catalog/order AJAX logic.
══════════════════════════════════════════════════════════════════════════ */

const SHOWCASE_SERVICES = [
    { code: 'whatsapp', label: 'WhatsApp', icon: 'fa-brands fa-whatsapp', priceFrom: 350 },
    { code: 'google', label: 'Google', icon: 'fa-brands fa-google', priceFrom: 400 },
    { code: 'instagram', label: 'Instagram', icon: 'fa-brands fa-instagram', priceFrom: 300 },
    { code: 'tiktok', label: 'TikTok', icon: 'fa-brands fa-tiktok', priceFrom: 380 },
    { code: 'telegram', label: 'Telegram', icon: 'fa-brands fa-telegram', priceFrom: 250 },
];

Alpine.data('zenSinglePage', () => ({
    // ── Auth ──
    authChecked: false,
    authenticated: false,
    user: null,
    authEmail: '',
    authPassword: '',
    authError: null,
    authLoading: false,

    // ── Catalog / order form ──
    showcaseIndex: 0,
    showcaseServices: SHOWCASE_SERVICES,
    countries: [],
    services: [],
    country: '',
    service: '',
    countryFilter: '',
    serviceFilter: '',
    priceFcfa: null,
    loadingPrice: false,
    purchasing: false,
    purchaseError: null,

    // ── Modals ──
    showCountriesModal: false,
    showCountryDropdown: false,
    showWaitingModal: false,
    showSuccessModal: false,
    showErrorModal: false,
    showTopupModal: false,
    showAccountModal: false,
    legalModal: null, // 'faq' | 'contact' | 'about' | 'privacy' | 'cgv' | 'mentions' | null
    errorMessage: '',

    // ── Waiting / SMS ──
    order: null,
    smsCode: null,
    orderStatus: null,
    expiresAtTimestamp: null,
    totalSeconds: 0,
    secondsRemaining: 0,
    countdownInterval: null,
    pollInterval: null,
    waitingTimedOut: false,
    cancelling: false,
    copiedPhone: false,
    copiedSms: false,

    // ── Top-up ──
    topupAmount: 2500,
    topupPresets: [1000, 2500, 5000, 10000],
    topupLoading: false,
    topupError: null,

    // ── Account / dashboard modal ──
    dashboard: null,
    dashboardLoading: false,

    // ── Contact form ──
    contactName: '',
    contactEmail: '',
    contactSubject: '',
    contactMessage: '',
    contactSending: false,
    contactSent: false,

    get filteredCountries() {
        const term = this.countryFilter.toLowerCase();

        return this.countries.filter((item) => item.name.toLowerCase().includes(term));
    },

    get filteredServices() {
        const term = this.serviceFilter.toLowerCase();

        return this.services.filter((item) => item.label.toLowerCase().includes(term));
    },

    get selectedCountryLabel() {
        const found = this.countries.find((item) => item.code === this.country);

        return found ? found.name : 'Choisir un pays';
    },

    get selectedServiceLabel() {
        const found = this.services.find((item) => item.code === this.service);

        return found ? found.label : this.service;
    },

    get priceLabel() {
        if (this.loadingPrice) {
            return 'Calcul du prix...';
        }

        return this.priceFcfa !== null
            ? new Intl.NumberFormat('fr-FR').format(this.priceFcfa) + ' FCFA'
            : '—';
    },

    get canPurchase() {
        return Boolean(this.service && this.country && this.priceFcfa !== null && !this.purchasing);
    },

    serviceIcon,
    countryFlag,

    /* ── Auth ── */
    async loadAuthStatus() {
        const response = await fetch('/api/me');
        const json = await response.json();

        this.authenticated = json.authenticated;
        this.user = json.user;
        this.authChecked = true;
    },

    async quickAuth() {
        this.authError = null;

        if (!this.authEmail || !this.authPassword) {
            this.authError = 'Merci de renseigner votre email et votre mot de passe.';
            return false;
        }

        this.authLoading = true;

        try {
            const response = await fetch('/api/auth/quick', {
                method: 'POST',
                headers: csrfHeaders({ 'Content-Type': 'application/json' }),
                body: JSON.stringify({ email: this.authEmail, password: this.authPassword }),
            });

            const json = await response.json();

            if (!response.ok) {
                this.authError = json.errors?.password?.[0]
                    ?? json.errors?.email?.[0]
                    ?? json.message
                    ?? 'Une erreur est survenue.';
                return false;
            }

            this.authenticated = true;
            this.user = json.user;

            return true;
        } catch {
            this.authError = 'Connexion impossible. Vérifiez votre réseau et réessayez.';
            return false;
        } finally {
            this.authLoading = false;
        }
    },

    /* ── Catalog ── */
    async loadCountries() {
        this.countries = await fetchCountries();
    },

    async selectCountry(code) {
        this.country = code;
        this.service = '';
        this.priceFcfa = null;
        this.showCountryDropdown = false;
        this.services = await fetchServicesForCountry(code);
    },

    async selectService(code) {
        this.service = code;
        this.loadingPrice = true;

        try {
            const json = await fetchPriceFor(this.service, this.country);
            this.priceFcfa = json?.price_fcfa ?? null;
        } finally {
            this.loadingPrice = false;
        }
    },

    /* ── Purchase flow ── */
    async purchase() {
        this.purchaseError = null;

        if (!this.authenticated) {
            const ok = await this.quickAuth();

            if (!ok) {
                return;
            }
        }

        if (!this.canPurchase) {
            return;
        }

        this.purchasing = true;

        try {
            const response = await fetch('/api/purchase', {
                method: 'POST',
                headers: csrfHeaders({ 'Content-Type': 'application/json' }),
                body: JSON.stringify({ service: this.service, country: this.country }),
            });

            const json = await response.json();

            if (!response.ok) {
                if (json.field === 'balance') {
                    this.openTopup();
                    return;
                }

                this.errorMessage = json.message ?? 'Une erreur est survenue. Veuillez réessayer.';
                this.showErrorModal = true;
                return;
            }

            this.order = json;
            this.openWaitingModal(json);
        } catch {
            this.errorMessage = 'Connexion impossible. Vérifiez votre réseau et réessayez.';
            this.showErrorModal = true;
        } finally {
            this.purchasing = false;
        }
    },

    /* ── Waiting modal / SMS polling (mirrors orderWaiting's logic for the modal) ── */
    openWaitingModal(order) {
        this.smsCode = null;
        this.orderStatus = order.status;
        this.waitingTimedOut = false;
        this.expiresAtTimestamp = order.expires_at ? new Date(order.expires_at).getTime() : Date.now();
        this.secondsRemaining = Math.max(0, Math.round((this.expiresAtTimestamp - Date.now()) / 1000));
        this.totalSeconds = this.secondsRemaining;
        this.showWaitingModal = true;

        clearInterval(this.countdownInterval);
        clearInterval(this.pollInterval);

        this.countdownInterval = setInterval(() => this.tickWaitingCountdown(), 1000);
        this.pollInterval = setInterval(() => this.pollWaitingStatus(), 5000);
        this.pollWaitingStatus();
    },

    tickWaitingCountdown() {
        const remainingMs = this.expiresAtTimestamp - Date.now();
        this.secondsRemaining = Math.max(0, Math.round(remainingMs / 1000));

        if (this.secondsRemaining <= 0) {
            clearInterval(this.countdownInterval);

            if (!this.smsCode) {
                this.waitingTimedOut = true;
                clearInterval(this.pollInterval);
            }
        }
    },

    async pollWaitingStatus() {
        if (this.smsCode || this.secondsRemaining <= 0) {
            clearInterval(this.pollInterval);
            return;
        }

        const json = await fetchOrderStatus(this.order.order_id);

        if (!json) {
            return;
        }

        this.orderStatus = json.status;

        if (json.sms_code) {
            this.smsCode = json.sms_code;
            clearInterval(this.pollInterval);
            this.showWaitingModal = false;
            this.showSuccessModal = true;
        }
    },

    get waitingMinutes() {
        return Math.floor(this.secondsRemaining / 60);
    },

    get waitingSeconds() {
        return String(this.secondsRemaining % 60).padStart(2, '0');
    },

    get waitingProgressPercent() {
        if (this.totalSeconds <= 0) {
            return 0;
        }

        return Math.max(0, Math.min(100, (this.secondsRemaining / this.totalSeconds) * 100));
    },

    async cancelWaitingOrder() {
        if (!confirm('Voulez-vous vraiment annuler cette commande ? Le montant sera remboursé sur votre solde.')) {
            return;
        }

        this.cancelling = true;

        try {
            const { ok, json } = await requestCancelOrder(this.order.order_id);

            clearInterval(this.countdownInterval);
            clearInterval(this.pollInterval);

            if (ok) {
                this.showWaitingModal = false;
                await this.loadAuthStatus();
                this.resetOrderForm();
                return;
            }

            this.errorMessage = json.message ?? "Impossible d'annuler cette commande pour le moment.";
            this.showWaitingModal = false;
            this.showErrorModal = true;
        } finally {
            this.cancelling = false;
        }
    },

    retryOtherCountry() {
        this.showWaitingModal = false;
        clearInterval(this.countdownInterval);
        clearInterval(this.pollInterval);
        this.country = '';
        this.services = [];
        this.priceFcfa = null;
        this.showCountryDropdown = true;
    },

    copyPhone() {
        if (!this.order?.phone) {
            return;
        }

        navigator.clipboard.writeText(this.order.phone);
        this.copiedPhone = true;
        setTimeout(() => {
            this.copiedPhone = false;
        }, 2000);
    },

    copySms() {
        if (!this.smsCode) {
            return;
        }

        navigator.clipboard.writeText(this.smsCode);
        this.copiedSms = true;
        setTimeout(() => {
            this.copiedSms = false;
        }, 2000);
    },

    resetOrderForm() {
        this.order = null;
        this.smsCode = null;
        this.service = '';
        this.priceFcfa = null;
    },

    startNewPurchase() {
        this.showSuccessModal = false;
        this.resetOrderForm();
    },

    /* ── Top-up (FedaPay) ── */
    openTopup() {
        this.showErrorModal = false;
        this.topupError = null;
        this.showTopupModal = true;
    },

    async payTopup() {
        this.topupError = null;

        if (typeof FedaPay === 'undefined') {
            this.topupError = 'Le service de paiement est indisponible. Rechargez la page et réessayez.';
            return;
        }

        this.topupLoading = true;

        try {
            const handler = FedaPay.init({
                public_key: window.ZEN_SMS_CONFIG.fedapayPublicKey,
                transaction: {
                    amount: this.topupAmount,
                    description: `Rechargement de solde Zen_Sms (${this.topupAmount} FCFA)`,
                },
                customer: {
                    email: this.user?.email ?? this.authEmail,
                },
                onComplete: (resp) => {
                    this.topupLoading = false;

                    if (resp.reason === FedaPay.APPROVED) {
                        this.confirmTopup(resp.transaction?.id);
                    } else if (resp.reason === FedaPay.DIALOG_DISMISSED) {
                        // user closed manually, nothing to do
                    } else {
                        this.topupError = 'Paiement non finalisé. Réessayez ou contactez le support.';
                    }
                },
            });

            handler.open();
        } catch {
            this.topupLoading = false;
            this.topupError = 'Une erreur inattendue est survenue.';
        }
    },

    async confirmTopup(transactionId) {
        if (!transactionId) {
            this.topupError = 'Transaction introuvable. Contactez le support si le débit a eu lieu.';
            return;
        }

        this.topupLoading = true;

        try {
            const response = await fetch('/api/topup/confirm', {
                method: 'POST',
                headers: csrfHeaders({ 'Content-Type': 'application/json' }),
                body: JSON.stringify({ transaction_id: transactionId }),
            });

            const json = await response.json();

            if (!response.ok) {
                this.topupError = json.message ?? "La confirmation du paiement a échoué. Contactez le support.";
                return;
            }

            if (this.user) {
                this.user.balance = json.balance;
            }

            this.showTopupModal = false;
        } finally {
            this.topupLoading = false;
        }
    },

    /* ── Account / dashboard modal ── */
    async openAccount() {
        this.showAccountModal = true;
        this.dashboardLoading = true;

        try {
            const response = await fetch('/api/dashboard');
            this.dashboard = await response.json();
        } finally {
            this.dashboardLoading = false;
        }
    },

    /* ── Contact form ── */
    async sendContact() {
        this.contactSending = true;
        this.contactSent = false;

        try {
            const response = await fetch('/api/contact', {
                method: 'POST',
                headers: csrfHeaders({ 'Content-Type': 'application/json' }),
                body: JSON.stringify({
                    name: this.contactName,
                    email: this.contactEmail,
                    subject: this.contactSubject,
                    message: this.contactMessage,
                }),
            });

            if (response.ok) {
                this.contactSent = true;
                this.contactName = '';
                this.contactEmail = '';
                this.contactSubject = '';
                this.contactMessage = '';
            }
        } finally {
            this.contactSending = false;
        }
    },

    openLegal(id) {
        this.legalModal = id;
    },

    init() {
        this.loadAuthStatus();
        this.loadCountries();

        setInterval(() => {
            this.showcaseIndex = (this.showcaseIndex + 1) % this.showcaseServices.length;
        }, 3000);
    },
}));

Alpine.start();

// Font Awesome (all.min.css) is loaded via CDN in the main layout's <head>, added in a
// later phase once resources/views/layouts/app.blade.php exists:
// <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
