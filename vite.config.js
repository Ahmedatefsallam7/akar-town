import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { google } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/css/filament/admin/theme.css', 'resources/js/filament/map-picker.js', 'resources/js/filament/meetings-calendar.js', 'resources/css/website.css', 'resources/js/website.js'],
            refresh: true,
            fonts: [
                // Public website: the same Google Fonts and weights as the design, self-hosted.
                google('Archivo', { weights: [300, 400, 500, 600, 700], styles: ['normal'], preload: [{ weight: 400 }] }),
                // Arabic letters only: English words and numbers on Arabic pages fall back to Archivo (as on English pages).
                google('Noto Kufi Arabic', { weights: [300, 400, 500, 600], subsets: ['arabic'] }),
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
