# Frontend (Phase 1)

This workspace hosts the incremental SPA island frontend for Starlight V2.

## Stack

- React 19
- TypeScript (strict)
- Vite

## Commands

```bash
cd frontend
npm install
npm run build
```

The build outputs to `public/spa/`.

## Current entry points

- `src/notifications.tsx` → `public/spa/notifications.js`

## Feature flags

The notifications island is controlled by `FEATURE_SPA_NOTIFICATIONS` in `.env`.

- `false`: existing PHP notifications view only
- `true`: React island mounts on `/notifications` and hides legacy markup after successful mount
