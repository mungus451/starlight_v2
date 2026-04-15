import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

const outDir = new URL('../public/spa', import.meta.url).pathname;
const notificationsEntry = new URL('./src/notifications.tsx', import.meta.url).pathname;
const glossaryEntry = new URL('./src/glossary.tsx', import.meta.url).pathname;
const leaderboardEntry = new URL('./src/leaderboard.tsx', import.meta.url).pathname;
const bankEntry = new URL('./src/bank.tsx', import.meta.url).pathname;
const trainingEntry = new URL('./src/training.tsx', import.meta.url).pathname;
const profileEntry = new URL('./src/profile.tsx', import.meta.url).pathname;
const structuresEntry = new URL('./src/structures.tsx', import.meta.url).pathname;

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
                leaderboard: leaderboardEntry,
                bank: bankEntry,
                training: trainingEntry,
                profile: profileEntry,
                structures: structuresEntry,
            },
            output: {
                entryFileNames: '[name].js',
                chunkFileNames: 'chunks/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash][extname]',
            },
        },
    },
});
