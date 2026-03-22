import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true, // HMR
      server: {
        hmr: {
          host: 'localhost', // or your Docker host IP
        },
      },
    }),
    vue(),
  ],
  server: {
    host: true,       // allow connections from outside container
    port: 5173,
    strictPort: true, // fail if port is busy
    hmr: {
      host: 'localhost',
      protocol: 'ws',
    },
  },
});