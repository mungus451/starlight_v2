import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

const outDir = new URL('../public/spa', import.meta.url).pathname;
const notificationsEntry = new URL('./src/notifications.tsx', import.meta.url).pathname;
const glossaryEntry = new URL('./src/glossary.tsx', import.meta.url).pathname;

export default defineConfig({
    plugins: [react()],
    build: {
        outDir,
        emptyOutDir: false,
        sourcemap: false,
        rollupOptions: {
            input: {
                notifications: notificationsEntry,
                glossary: glossaryEntry,
            },
            output: {
                entryFileNames: '[name].js',
                chunkFileNames: 'chunks/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash][extname]',
            },
        },
    },
});
