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

// Best-effort flag emojis for the country slugs most likely to appear (5sim's exact
// slug format wasn't confirmed against the live docs - see FiveSimService's code
// comment); anything unrecognized falls back to a globe rather than guessing wrong.
const COUNTRY_FLAGS = {
    russia: '🇷🇺',
    usa: '🇺🇸',
    england: '🇬🇧',
    uk: '🇬🇧',
    france: '🇫🇷',
    ivory_coast: '🇨🇮',
    senegal: '🇸🇳',
    mali: '🇲🇱',
    benin: '🇧🇯',
    cameroon: '🇨🇲',
    nigeria: '🇳🇬',
    ghana: '🇬🇭',
    togo: '🇹🇬',
    burkina_faso: '🇧🇫',
    niger: '🇳🇪',
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

Alpine.start();

// Font Awesome (all.min.css) is loaded via CDN in the main layout's <head>, added in a
// later phase once resources/views/layouts/app.blade.php exists:
// <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
