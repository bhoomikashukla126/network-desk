import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';
import { fileURLToPath } from 'url';

const platformRoot = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600, 700],
                }),
            ],
        }),
        vue(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@platform/keyboard': path.resolve(platformRoot, 'shared-keyboard'),
            'vue-i18n': path.resolve(platformRoot, 'node_modules/vue-i18n'),
            vue: path.resolve(platformRoot, 'node_modules/vue'),
            'lucide-vue-next': path.resolve(platformRoot, 'node_modules/lucide-vue-next'),
        },
        dedupe: ['vue', 'vue-i18n', 'vue-router'],
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
