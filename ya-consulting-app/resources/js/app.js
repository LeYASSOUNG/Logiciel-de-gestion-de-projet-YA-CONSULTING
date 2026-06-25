import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import VueApexCharts from 'vue3-apexcharts';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import './bootstrap';
import '../css/app.css';

createInertiaApp({
    title: (title) => `${title} Gestion de projet YA Consulting`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(VueApexCharts)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#C9A84C',
    },
});
