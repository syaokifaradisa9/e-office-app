import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const pages = import.meta.glob([
    './pages/**/*.tsx',
    './pages/**/*.jsx',
    '../../Modules/**/resources/assets/js/Pages/**/*.tsx',
    '../../Modules/**/resources/assets/js/Pages/**/*.jsx',
    '../../Modules/*/resources/assets/js/Pages/**/*.tsx',
    '../../Modules/*/resources/assets/js/Pages/**/*.jsx',
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
        const pageKeys = Object.keys(pages);

        // 1. Try local pages (exact match)
        const localPath = `./pages/${name}.tsx`;
        if (pages[localPath]) {
            return resolvePageComponent(localPath, pages);
        }

        // 2. Try module pages
        // The name usually comes as "ModuleName/PagePath"
        const parts = name.split('/');
        if (parts.length >= 2) {
            const moduleName = parts[0];
            const pagePath = parts.slice(1).join('/');

            // Look for a match in Modules
            // We look for a key that ends with /Modules/ModuleName/.../Pages/PagePath.tsx
            const targetSuffix = `/modules/${moduleName}/resources/assets/js/pages/${pagePath}.tsx`.toLowerCase();
            const matchingKey = pageKeys.find((key) =>
                key.replace(/\\/g, '/').toLowerCase().endsWith(targetSuffix)
            );

            if (matchingKey) {
                return resolvePageComponent(matchingKey, pages);
            }
        }

        // 3. Case-insensitive fallback for local pages
        const normalizedLocal = localPath.toLowerCase();
        const foundLocalKey = pageKeys.find(
            (key) => key.toLowerCase() === normalizedLocal
        );
        if (foundLocalKey) {
            return resolvePageComponent(foundLocalKey, pages);
        }

        // Fallback to the original resolver behavior which will throw the informed Error if not found
        return resolvePageComponent(`./pages/${name}.tsx`, pages);
    },
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});
