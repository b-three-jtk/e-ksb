import './bootstrap';
import '../css/app.css';
import ThemeProvider from '@/Layouts/Admin/ThemeProvider.vue'
import SidebarProvider from '@/Layouts/Admin/SidebarProvider.vue'
import "vue3-toastify/dist/index.css";
import '@vuepic/vue-datepicker/dist/main.css'
import '../css/app.css'

import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
        return pages[`./Pages/${name}.vue`]
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .component('ThemeProvider', ThemeProvider)
            .component('SidebarProvider', SidebarProvider)
            .mount(el)
    },
})
