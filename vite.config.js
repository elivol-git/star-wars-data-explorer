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
    host: '0.0.0.0',
    port: 5173,

    https: {
      key: './docker/ssl/film-planets.test-key.pem',
      cert: './docker/ssl/film-planets.test.pem',
    },

    hmr: {
      host: 'film-planets.test',
      protocol: 'wss',
    },
  },
});