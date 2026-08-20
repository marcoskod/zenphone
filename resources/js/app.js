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

    get filteredServices() {
        const term = this.serviceFilter.toLowerCase();

        return this.services.filter((item) => item.label.toLowerCase().includes(term));
    },

    get filteredCountries() {
        const term = this.countryFilter.toLowerCase();

        return this.countries.filter((item) => item.name.toLowerCase().includes(term));
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

    selectService(code) {
        this.service = code;
    },

    selectCountry(code) {
        this.country = code;
        this.service = '';
        this.loadServices();
    },

    init() {
        this.loadCountries();

        if (this.country) {
            this.loadServices();
        }
    },
}));

Alpine.start();

// Font Awesome (all.min.css) is loaded via CDN in the main layout's <head>, added in a
// later phase once resources/views/layouts/app.blade.php exists:
// <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
