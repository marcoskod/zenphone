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

    serviceIcon(code) {
        return SERVICE_ICONS[code] ?? 'fa-solid fa-mobile-screen';
    },

    countryFlag(code) {
        return COUNTRY_FLAGS[code] ?? '🌍';
    },

    async loadServices() {
        if (!this.country) {
            this.services = [];
            return;
        }

        const response = await fetch(`/api/services?country=${encodeURIComponent(this.country)}`);
        const json = await response.json();
        this.services = json.data ?? [];
    },

    async loadCountries() {
        const response = await fetch('/api/countries');
        const json = await response.json();
        this.countries = json.data ?? [];
    },

    async fetchPrice() {
        if (!this.service || !this.country) {
            this.priceUsd = null;
            this.priceFcfa = null;
            return;
        }

        this.loadingPrice = true;

        try {
            const response = await fetch(
                `/api/price?service=${encodeURIComponent(this.service)}&country=${encodeURIComponent(this.country)}`,
            );

            if (!response.ok) {
                this.priceUsd = null;
                this.priceFcfa = null;
                return;
            }

            const json = await response.json();
            this.priceUsd = json.price_usd;
            this.priceFcfa = json.price_fcfa;
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
            const response = await fetch(`/api/orders/${this.orderId}/status`);

            if (!response.ok) {
                return;
            }

            const json = await response.json();
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

        this.countdownInterval = setInterval(() => this.tickCountdown(), 1000);

        if (!this.smsCode && !this.expired) {
            this.pollStatus();
            this.pollInterval = setInterval(() => this.pollStatus(), 5000);
        }
    },
}));

Alpine.start();

// Font Awesome (all.min.css) is loaded via CDN in the main layout's <head>, added in a
// later phase once resources/views/layouts/app.blade.php exists:
// <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
