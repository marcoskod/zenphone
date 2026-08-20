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

Alpine.data('purchaseForm', (initialService = '', initialCountry = '') => ({
    service: initialService,
    country: initialCountry,
    services: [],
    serviceFilter: '',

    get filteredServices() {
        const term = this.serviceFilter.toLowerCase();

        return this.services.filter((item) => item.label.toLowerCase().includes(term));
    },

    serviceIcon(code) {
        return SERVICE_ICONS[code] ?? 'fa-solid fa-mobile-screen';
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

    selectService(code) {
        this.service = code;
    },

    init() {
        if (this.country) {
            this.loadServices();
        }
    },
}));

Alpine.start();

// Font Awesome (all.min.css) is loaded via CDN in the main layout's <head>, added in a
// later phase once resources/views/layouts/app.blade.php exists:
// <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
