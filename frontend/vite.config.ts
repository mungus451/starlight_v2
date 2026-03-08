import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'node:path';

export default defineConfig({
    plugins: [react()],
    build: {
        outDir: path.resolve(__dirname, '../public/spa'),
        emptyOutDir: false,
        sourcemap: true,
        rollupOptions: {
            input: {
                notifications: path.resolve(__dirname, 'src/notifications.tsx'),
            },
            output: {
                entryFileNames: '[name].js',
                chunkFileNames: 'chunks/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash][extname]',
            },
        },
    },
});
