import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny, google } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/filament/admin/theme.css', 'resources/js/filament/map-picker.js', 'resources/js/filament/meetings-calendar.js', 'resources/css/website.css', 'resources/js/website.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
                // Public website: the same Google Fonts and weights as the design, self-hosted.
                // Only the normal style is preloaded: the italic is used once (the home hero's tagline).
                google('Archivo', { weights: [300, 400, 500, 600, 700], styles: ['normal', 'italic'], preload: [{ weight: 400 }] }),
                google('Noto Kufi Arabic', { weights: [300, 400, 500, 600], subsets: ['arabic', 'latin'] }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
