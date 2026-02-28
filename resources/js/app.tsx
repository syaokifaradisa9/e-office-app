// Main Entry Point
// Global glob refresh for Ticketing Report
// v3
import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = 'e-Office';

const pages = import.meta.glob([
    './pages/**/*.tsx',
    './pages/**/*.jsx',
    '../../Modules/**/resources/assets/js/Pages/**/*.tsx',
    '../../Modules/**/resources/assets/js/Pages/**/*.jsx',
]);

// Helper to normalize paths for comparison
const normalizePath = (path: string) => path.replace(/\\/g, '/').toLowerCase();

/**
 * Pre-calculate page mapping for O(1) lookup
 * This significantly speeds up page resolution during navigation
 */
const pageKeys = Object.keys(pages);
const pageMap = new Map<string, string>();

pageKeys.forEach((key) => {
    const normalizedKey = normalizePath(key);

    // 1. Map by absolute/relative key
    pageMap.set(normalizedKey, key);

    // 2. Map local pages for direct lookup: "pages/dashboard.tsx" -> actual key
    if (normalizedKey.includes('/pages/')) {
        const localPart = normalizedKey.split('/pages/').pop();
        if (localPart) {
            pageMap.set(`local:${localPart}`, key);
        }
    }

    // 3. Map module pages for direct lookup: "inventory/items/index" -> actual key
    // Path pattern: ../../Modules/Inventory/resources/assets/js/Pages/Items/Index.tsx
    if (normalizedKey.includes('/modules/')) {
        const parts = normalizedKey.split('/');
        const modulesIndex = parts.indexOf('modules');
        if (modulesIndex !== -1 && parts.length > modulesIndex + 1) {
            const moduleName = parts[modulesIndex + 1];
            const pagesIdx = parts.indexOf('pages', modulesIndex);
            if (pagesIdx !== -1) {
                const pagePath = parts.slice(pagesIdx + 1).join('/');
                // Key format: "module:inventory/items/index.tsx"
                pageMap.set(`module:${moduleName}/${pagePath}`, key);
            }
        }
    }
});

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => {
        const normalizedName = name.toLowerCase();

        // 1. Search in local pages map
        const localKeyTSX = pageMap.get(`local:${normalizedName}.tsx`);
        const localKeyJSX = pageMap.get(`local:${normalizedName}.jsx`);
        const foundLocal = localKeyTSX || localKeyJSX;

        if (foundLocal) {
            return resolvePageComponent(foundLocal, pages);
        }

        // 2. Search in module pages map
        const moduleKeyTSX = pageMap.get(`module:${normalizedName}.tsx`);
        const moduleKeyJSX = pageMap.get(`module:${normalizedName}.jsx`);
        const foundModule = moduleKeyTSX || moduleKeyJSX;

        if (foundModule) {
            return resolvePageComponent(foundModule, pages);
        }

        // 3. Robust Search (Fallback through all keys)
        const nameWithExtensionTSX = `${normalizedName}.tsx`;
        const nameWithExtensionJSX = `${normalizedName}.jsx`;

        // Search for a key that contains the page path (case-insensitive)
        const robustMatch = pageKeys.find((key) => {
            const lowKey = key.toLowerCase();
            return (
                lowKey.endsWith(`/${nameWithExtensionTSX}`) ||
                lowKey.endsWith(`/${nameWithExtensionJSX}`) ||
                lowKey.includes(`/pages/${nameWithExtensionTSX}`) ||
                lowKey.includes('/pages/' + nameWithExtensionJSX)
            );
        });

        if (robustMatch) {
            console.log('Robust match found:', robustMatch);
            return resolvePageComponent(robustMatch, pages);
        }

        // Manual mapping for Ticketing module (Legacy fallback)
        if (normalizedName.startsWith('ticketing/')) {
            const relativePath = name.split('/').slice(1).join('/'); // e.g. "Maintenance/Checklist"
            const manualKey = `../../Modules/Ticketing/resources/assets/js/Pages/${relativePath}.tsx`;
            if (pages[manualKey]) return resolvePageComponent(manualKey, pages);

            // Try with normalized casing as well
            const manualKeyNormalized = manualKey.toLowerCase();
            const foundKey = Object.keys(pages).find(k => k.toLowerCase() === manualKeyNormalized);
            if (foundKey) return resolvePageComponent(foundKey, pages);
        }

        // 3. Fallback for direct matches or complex cases
        const directMatch = pageMap.get(normalizePath(`./pages/${name}.tsx`)) ||
            pageMap.get(normalizePath(`./pages/${name}.jsx`));

        if (directMatch) {
            return resolvePageComponent(directMatch, pages);
        }

        // Final fallback (standard Inertia behavior)
        return resolvePageComponent(`./pages/${name}.tsx`, pages);
    },
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(<App {...props} />);
    },
    progress: {
        color: '#22c55e', // Hijau menyesuaikan tema primary biasanya
        showSpinner: true,
        delay: 0,
    },
});
