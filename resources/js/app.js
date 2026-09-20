import './bootstrap';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

const SERVICE_ICONS = {
    whatsapp: 'fa-brands fa-whatsapp',
    google: 'fa-brands fa-google',
    openai: 'fa-solid fa-robot',
    instagram: 'fa-brands fa-instagram',
    facebook: 'fa-brands fa-facebook',
    telegram: 'fa-brands fa-telegram',
    tiktok: 'fa-brands fa-tiktok',
    twitter: 'fa-brands fa-x-twitter',
    snapchat: 'fa-brands fa-snapchat',
    discord: 'fa-brands fa-discord',
    paypal: 'fa-brands fa-paypal',
    amazon: 'fa-brands fa-amazon',
    linkedin: 'fa-brands fa-linkedin',
    apple: 'fa-brands fa-apple',
    microsoft: 'fa-brands fa-microsoft',
    airbnb: 'fa-brands fa-airbnb',
    uber: 'fa-brands fa-uber',
    twitch: 'fa-brands fa-twitch',
    steam: 'fa-brands fa-steam',
    tinder: 'fa-solid fa-heart',
    line: 'fa-brands fa-line',
    viber: 'fa-brands fa-viber',
    skype: 'fa-brands fa-skype',
    wechat: 'fa-brands fa-weixin',
    reddit: 'fa-brands fa-reddit',
    pinterest: 'fa-brands fa-pinterest',
    ebay: 'fa-brands fa-ebay',
};

// The order customers reach for first - shown by default instead of the full 150+
// service catalog, so the common case (WhatsApp, Telegram...) never requires scrolling
// or searching. Filtered down to whatever the selected country actually offers.
const POPULAR_SERVICES = [
    'whatsapp', 'telegram', 'google', 'instagram', 'facebook',
    'tiktok', 'twitter', 'snapchat', 'discord', 'openai', 'amazon', 'tinder',
];

// country code -> ISO 3166 alpha-2, filled from /api/countries. A flag emoji is just the
// two ISO letters shifted into the regional-indicator block, so no hand-kept table.
const COUNTRY_ISO = {};

function isoFlag(iso) {
    if (!/^[A-Za-z]{2}$/.test(iso ?? '')) {
        return '🌍';
    }

    return String.fromCodePoint(...[...iso.toUpperCase()].map((ch) => 0x1f1e6 + ch.charCodeAt(0) - 65));
}

function serviceIcon(code) {
    return SERVICE_ICONS[code] ?? 'fa-solid fa-mobile-screen';
}

function countryFlag(code) {
    return isoFlag(COUNTRY_ISO[code]);
}

// Tracks the one order currently being waited on, so a reload/relaunch (page refresh,
// browser crash, tab closed mid-payment) doesn't strand the customer with a paid-for
// number and no way back to it short of digging through order history. localStorage is
// wrapped in try/catch throughout: private browsing, disabled storage, and older
// browsers can all make it throw or silently no-op, and none of that should ever break
// the purchase flow itself - resuming is a convenience, not a requirement.
const ACTIVE_ORDER_KEY = 'zen_sms_active_order';

function saveActiveOrder(order) {
    try {
        localStorage.setItem(ACTIVE_ORDER_KEY, JSON.stringify({ id: order.order_id, expires_at: order.expires_at }));
    } catch {
        // storage unavailable - resuming after a reload just won't work
    }
}

function clearActiveOrder() {
    try {
        localStorage.removeItem(ACTIVE_ORDER_KEY);
    } catch {
        // see saveActiveOrder
    }
}

function loadActiveOrder() {
    try {
        const raw = localStorage.getItem(ACTIVE_ORDER_KEY);

        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

// navigator.clipboard requires a secure context and is missing on older browsers
// (Safari < 13.1, most pre-Chromium Android WebViews). Falls back to a hidden textarea +
// execCommand so "copier" still works everywhere the app itself runs.
async function copyText(text) {
    if (!text) {
        return false;
    }

    if (navigator.clipboard?.writeText) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch {
            // fall through to the legacy path below
        }
    }

    try {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();
        const ok = document.execCommand('copy');
        document.body.removeChild(textarea);
        return ok;
    } catch {
        return false;
    }
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

// Every GET call in this file goes through here: it forces `Accept: application/json`
// (without it, a server-side error renders Laravel's HTML error page instead of a JSON
// one, which then throws a confusing "Unexpected token '<'" deep inside .json()) and
// swallows any failure - bad network, a non-JSON response, the request throwing outright
// - into a plain `null`, so a transient hiccup degrades a widget instead of crashing the
// page with an uncaught promise rejection.
async function getJson(url) {
    try {
        const response = await fetch(url, { headers: { Accept: 'application/json' } });

        if (!response.ok) {
            return null;
        }

        return await response.json();
    } catch {
        return null;
    }
}

async function fetchCountries() {
    const json = await getJson('/api/countries');
    const countries = json?.data ?? [];

    countries.forEach((c) => {
        COUNTRY_ISO[c.code] = c.iso;
    });

    return countries;
}

async function fetchServicesForCountry(country) {
    if (!country) {
        return [];
    }

    const json = await getJson(`/api/services?country=${encodeURIComponent(country)}`);

    return json?.data ?? [];
}

async function fetchPriceFor(service, country) {
    if (!service || !country) {
        return null;
    }

    return getJson(`/api/price?service=${encodeURIComponent(service)}&country=${encodeURIComponent(country)}`);
}

async function fetchOrderStatus(orderId) {
    return getJson(`/api/orders/${orderId}/status`);
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

    async copyPhone(phone) {
        if (!(await copyText(phone))) {
            return;
        }

        this.copiedPhone = true;
        setTimeout(() => {
            this.copiedPhone = false;
        }, 2000);
    },

    async copySms() {
        if (!(await copyText(this.smsCode))) {
            return;
        }

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
    { code: 'whatsapp', label: 'WhatsApp', icon: 'fa-brands fa-whatsapp', priceFrom: null },
    { code: 'google', label: 'Google', icon: 'fa-brands fa-google', priceFrom: null },
    { code: 'instagram', label: 'Instagram', icon: 'fa-brands fa-instagram', priceFrom: null },
    { code: 'tiktok', label: 'TikTok', icon: 'fa-brands fa-tiktok', priceFrom: null },
    { code: 'telegram', label: 'Telegram', icon: 'fa-brands fa-telegram', priceFrom: null },
];

// The marketing carousel quotes real prices (Benin, the home market) instead of hard-coded
// numbers that would silently go stale whenever the supplier or exchange rate moves.
const SHOWCASE_COUNTRY = 'benin';

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
    showAllServices: false,
    loadingServices: false,
    priceFcfa: null,
    loadingPrice: false,
    purchasing: false,
    purchaseError: null,
    pendingPurchaseId: null,

    // ── Modals ──
    showCountriesModal: false,
    showCountryDropdown: false,
    showWaitingModal: false,
    showSuccessModal: false,
    showErrorModal: false,
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

    get popularServices() {
        return POPULAR_SERVICES
            .map((code) => this.services.find((item) => item.code === code))
            .filter(Boolean);
    },

    get filteredServices() {
        const term = this.serviceFilter.toLowerCase();

        // Typing always searches the full catalog (150+ services) - only the empty,
        // untouched state shows the curated shortlist. That keeps the default view
        // short (no scrolling to find WhatsApp) without ever hiding a real service
        // from someone who searches for it by name.
        if (!term && !this.showAllServices) {
            return this.popularServices;
        }

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
        const json = await getJson('/api/me');

        this.authenticated = json?.authenticated ?? false;
        this.user = json?.user ?? null;
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

            // The server just rotated the session's CSRF token (session regenerate on
            // login/register); refresh the meta tag so the very next fetch() - typically
            // the purchase that triggered this inline auth - doesn't 419.
            if (json.csrf_token) {
                document.querySelector('meta[name="csrf-token"]').setAttribute('content', json.csrf_token);
            }

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

    async loadShowcasePrices() {
        const offered = await fetchServicesForCountry(SHOWCASE_COUNTRY);

        this.showcaseServices = this.showcaseServices.map((s) => ({
            ...s,
            priceFrom: offered.find((o) => o.code === s.code)?.price_fcfa ?? null,
        }));
    },

    async selectCountry(code) {
        this.country = code;
        this.service = '';
        this.priceFcfa = null;
        this.showCountryDropdown = false;
        this.serviceFilter = '';
        this.showAllServices = false;
        this.loadingServices = true;

        try {
            this.services = await fetchServicesForCountry(code);
        } finally {
            this.loadingServices = false;
        }
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
            const response = await fetch('/api/purchase/pay-init', {
                method: 'POST',
                headers: csrfHeaders({ 'Content-Type': 'application/json' }),
                body: JSON.stringify({ service: this.service, country: this.country }),
            });

            const json = await response.json();

            if (!response.ok) {
                this.errorMessage = json.message ?? 'Une erreur est survenue. Veuillez réessayer.';
                this.showErrorModal = true;
                this.purchasing = false;
                return;
            }

            if (json.paid_with === 'balance') {
                this.order = json;
                this.openWaitingModal(json);
                this.purchasing = false;
                return;
            }

            // paid_with === 'fedapay': price locked in as a PendingPurchase, open the
            // FedaPay checkout widget for that exact amount - no top-up step, no amount
            // picker, the customer pays for this number and nothing else. `purchasing`
            // is deliberately NOT reset here: handler.open() below returns as soon as the
            // widget opens, long before the customer actually finishes paying, and
            // payForOrder()/confirmOrderPayment() own clearing it from here so the button
            // stays in its loading state - and can't be double-clicked into opening a
            // second FedaPay dialog - for the whole payment, not just this first request.
            this.pendingPurchaseId = json.pending_purchase_id;
            this.payForOrder(json.amount, json.description);
        } catch {
            this.errorMessage = 'Connexion impossible. Vérifiez votre réseau et réessayez.';
            this.showErrorModal = true;
            this.purchasing = false;
        }
    },

    /* ── Direct payment (FedaPay) for a single order - replaces any wallet top-up step ── */
    async payForOrder(amount, description) {
        if (typeof FedaPay === 'undefined') {
            this.errorMessage = 'Le service de paiement est indisponible. Rechargez la page et réessayez.';
            this.showErrorModal = true;
            this.purchasing = false;
            return;
        }

        try {
            const handler = FedaPay.init({
                public_key: window.ZEN_SMS_CONFIG.fedapayPublicKey,
                // custom_metadata lets the FedaPay webhook map a paid transaction back to
                // this purchase even if this browser never gets to call pay-confirm.
                transaction: { amount, description, custom_metadata: { pending_purchase_id: this.pendingPurchaseId } },
                customer: { email: this.user?.email ?? this.authEmail },
                onComplete: (resp) => {
                    if (resp.reason === FedaPay.APPROVED) {
                        this.confirmOrderPayment(resp.transaction?.id);
                    } else if (resp.reason === FedaPay.DIALOG_DISMISSED) {
                        // user closed the widget manually - free to try again.
                        this.purchasing = false;
                    } else {
                        this.errorMessage = 'Paiement non finalisé. Réessayez ou contactez le support.';
                        this.showErrorModal = true;
                        this.purchasing = false;
                    }
                },
            });

            handler.open();
        } catch {
            this.errorMessage = 'Une erreur inattendue est survenue lors du paiement.';
            this.showErrorModal = true;
            this.purchasing = false;
        }
    },

    async confirmOrderPayment(transactionId) {
        if (!transactionId) {
            this.errorMessage = 'Transaction introuvable. Contactez le support si le débit a eu lieu.';
            this.showErrorModal = true;
            this.purchasing = false;
            return;
        }

        // `purchasing` is already true from purchase() and stays true through this call
        // too - the loading state is continuous from the first click to this final step.
        try {
            const response = await fetch('/api/purchase/pay-confirm', {
                method: 'POST',
                headers: csrfHeaders({ 'Content-Type': 'application/json' }),
                body: JSON.stringify({ pending_purchase_id: this.pendingPurchaseId, transaction_id: transactionId }),
            });

            const json = await response.json();

            if (!response.ok) {
                this.errorMessage = json.message ?? "La confirmation du paiement a échoué. Contactez le support.";
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
        this.order = order;
        this.smsCode = null;
        this.orderStatus = order.status;
        this.waitingTimedOut = false;
        this.expiresAtTimestamp = order.expires_at ? new Date(order.expires_at).getTime() : Date.now();
        this.secondsRemaining = Math.max(0, Math.round((this.expiresAtTimestamp - Date.now()) / 1000));
        this.totalSeconds = this.secondsRemaining;
        this.showWaitingModal = true;
        saveActiveOrder(order);

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
                clearActiveOrder();
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
            clearActiveOrder();
        }
    },

    /* ── Resume an order left waiting across a reload/relaunch (see ACTIVE_ORDER_KEY) ── */
    async resumeActiveOrderIfAny() {
        const stored = loadActiveOrder();

        if (!stored || !this.authenticated) {
            return;
        }

        if (stored.expires_at && new Date(stored.expires_at).getTime() <= Date.now()) {
            clearActiveOrder();
            return;
        }

        const json = await fetchOrderStatus(stored.id);

        if (!json) {
            // Network hiccup or the order id no longer resolves (e.g. deleted account) -
            // leave the pointer in place so a later reload with a working connection can
            // still try to resume it, rather than silently dropping it here.
            return;
        }

        if (json.sms_code) {
            this.order = { order_id: stored.id, phone: json.phone, status: json.status };
            this.smsCode = json.sms_code;
            this.showSuccessModal = true;
            clearActiveOrder();
            return;
        }

        if (['cancelled', 'canceled', 'failed', 'timeout'].includes(json.status)) {
            clearActiveOrder();
            return;
        }

        this.openWaitingModal({
            order_id: stored.id,
            phone: json.phone,
            status: json.status,
            expires_at: json.expires_at ?? stored.expires_at,
        });
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
                clearActiveOrder();
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
        clearActiveOrder();
        this.country = '';
        this.services = [];
        this.priceFcfa = null;
        this.showCountryDropdown = true;
    },

    async copyPhone() {
        if (!(await copyText(this.order?.phone))) {
            return;
        }

        this.copiedPhone = true;
        setTimeout(() => {
            this.copiedPhone = false;
        }, 2000);
    },

    async copySms() {
        if (!(await copyText(this.smsCode))) {
            return;
        }

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
        this.loadCountries();
        this.loadShowcasePrices();
        // resumeActiveOrderIfAny() needs authenticated/user resolved first, but must not
        // block the catalog load above (kept firing in parallel, as before).
        this.loadAuthStatus().then(() => this.resumeActiveOrderIfAny());

        setInterval(() => {
            this.showcaseIndex = (this.showcaseIndex + 1) % this.showcaseServices.length;
        }, 3000);
    },
}));

Alpine.start();

// Font Awesome (all.min.css) is loaded via CDN in the main layout's <head>, added in a
// later phase once resources/views/layouts/app.blade.php exists:
// <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
