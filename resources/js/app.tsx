import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const pages = import.meta.glob([
    './pages/**/*.tsx',
    '../../Modules/*/resources/assets/js/Pages/**/*.tsx',
]);

// Helper to normalize paths for comparison
const normalizePath = (path: string) => path.replace(/\\/g, '/').toLowerCase();
const pageKeys = Object.keys(pages);
const normalizedPageKeys = pageKeys.reduce((acc, key) => {
    acc[normalizePath(key)] = key;
    return acc;
}, {} as Record<string, string>);

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => {
        // 1. Check local pages
        const localPath = `./pages/${name}.tsx`;
        if (pages[localPath]) {
            return resolvePageComponent(localPath, pages);
        }

        // 2. Check module pages
        const [module, ...rest] = name.split('/');
        const pagePath = rest.join('/');
        const fullModulePath = `../../Modules/${module}/resources/assets/js/Pages/${pagePath}.tsx`;

        // Direct match
        if (pages[fullModulePath]) {
            return resolvePageComponent(fullModulePath, pages);
        }

        // Case-insensitive fallback
        const normalizedTarget = normalizePath(fullModulePath);
        const actualKey = normalizedPageKeys[normalizedTarget];

        if (actualKey) {
            return resolvePageComponent(actualKey, pages);
        }

        // Fallback to error
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
