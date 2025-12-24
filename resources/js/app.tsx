import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const pages = import.meta.glob([
    './pages/**/*.tsx',
    '../../Modules/*/resources/assets/js/Pages/**/*.tsx',
]);

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => {
        const page = pages[`./pages/${name}.tsx`];

        if (page) {
            return resolvePageComponent(`./pages/${name}.tsx`, pages);
        }

        const parts = name.split('/');
        const module = parts[0];
        const pagePath = parts.slice(1).join('/');

        const fullModulePath = `../../Modules/${module}/resources/assets/js/Pages/${pagePath}.tsx`;

        return resolvePageComponent(fullModulePath, pages);
    },
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});
