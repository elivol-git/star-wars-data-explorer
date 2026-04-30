import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/style.css',
                'resources/js/app.js'
            ],
            refresh: true,
        }),
        vue(),
    ],

    server: {
        host: true,
        port: 5173,

        // IMPORTANT: browser connects via host machine
        hmr: {
            host: 'localhost',
            protocol: 'ws',
            clientPort: 5173,
        },
    },
});
