# Phased SPA + PWA Migration Plan (90 Days)

This plan uses a strangler approach: keep PHP routes and views live, then replace page sections with SPA islands domain-by-domain.

## Outcomes

- Preserve current MVC-S backend boundaries (Controllers → Services → Repositories).
- Add API parity per domain before replacing UI.
- Roll out behind feature flags with instant rollback to server-rendered views.
- Ship installable PWA capabilities (manifest, service worker, offline strategy, push) in parallel.

## First Domain: Notifications

Notifications are the best first slice because they already include JSON endpoints and low-risk UX impact.

Existing routes in [public/index.php](../public/index.php):
- `GET /notifications`
- `GET /notifications/check`
- `GET /notifications/preferences`
- `POST /notifications/read/{id}`
- `POST /notifications/read-all`

## 90-Day Timeline

### Phase 1 (Days 1-21): Foundation

- Choose frontend stack (Vite + React + TypeScript recommended).
- Add frontend build pipeline and static asset serving from `public/`.
- Define API conventions (`/api/v1/...`, JSON envelope, error shape).
- Add feature flag system for per-domain SPA enablement.
- Add request/response logging and frontend error telemetry.
- Add baseline PWA assets: `manifest.webmanifest`, app icons, and metadata wiring.
- Define service worker strategy (app shell precache + runtime caching rules).

### Phase 2 (Days 22-45): Notifications Slice (Parity)

- Create notifications API controllers that delegate to existing Services.
- Build Notifications SPA island mounted on notifications page.
- Match server behavior for pagination, unread count, mark read, mark all read, and preferences.
- Ship to internal users behind `feature_spa_notifications`.
- Implement service worker with conservative caching:
  - precache static assets and SPA shell,
  - network-first for API,
  - stale-while-revalidate for images/fonts.
- Add push notification permission UX and subscription lifecycle (subscribe/unsubscribe).

### Phase 3 (Days 46-70): Settings + Leaderboard Slices

- Add profile/settings partial SPA (start with notification preferences + profile basics).
- Add leaderboard SPA widget with paging/sorting parity.
- Keep battle/economy actions server-rendered until parity confidence increases.
- Add offline read behavior for migrated domains (view cached leaderboard/settings state).
- Add background sync policy only for safe/idempotent writes.

### Phase 4 (Days 71-90): Bank Slice + Hardening

- Add bank transaction UI slice (deposit/withdraw/transfer) with strict parity.
- Add contract tests for all migrated API endpoints.
- Validate rollback drills and finalize runbook.
- Decide go/no-go for expanding to combat/alliance domains.
- Complete PWA hardening:
  - offline fallback page,
  - cache versioning and invalidation runbook,
  - installability and Lighthouse CI gates.

## Parallel PWA Track (Across All Phases)

### Workstreams

- **Installability**: manifest, icons, name/short_name, theme/background colors, display mode.
- **Offline Model**: app shell cache + explicit per-domain data policy.
- **Runtime Caching**:
  - API: network-first with short timeout fallback,
  - static assets: cache-first,
  - media: stale-while-revalidate.
- **Push**: browser permission UX, token storage/rotation, unsubscribe semantics.
- **Observability**: service worker lifecycle events and cache hit/miss telemetry.

### Non-Goals (Initial)

- Full offline gameplay for combat/economy writes.
- Queueing non-idempotent financial/combat actions while offline.

## Phase 1 Implementation (Now)

Current implementation in this repo:

- Frontend workspace: `frontend/` (Vite + React + strict TypeScript)
- First island entrypoint: `frontend/src/notifications.tsx`
- Build output: `public/spa/notifications.js`
- API adapter routes: `/api/v1/notifications*` and `/api/v1/notification-preferences`
- Feature flag: `FEATURE_SPA_NOTIFICATIONS`

Build commands:

```bash
cd frontend
npm install
npm run build
```

Then set in `.env`:

```env
FEATURE_SPA_NOTIFICATIONS=true
```

## API Parity Contract (Notifications)

Implement API routes that map to existing service methods:

- `GET /api/v1/notifications?page={n}&per_page={n}`
- `GET /api/v1/notifications/unread`
- `POST /api/v1/notifications/{id}/read`
- `POST /api/v1/notifications/read-all`
- `GET /api/v1/notification-preferences`
- `POST /api/v1/notification-preferences`

Requirements:
- Session auth required (same middleware behavior as web routes).
- CSRF required on mutating endpoints.
- Response and error formats must be stable and documented.
- API responses should include cache-control semantics compatible with the service worker policy.

## Definition of Done (Per Domain)

Use this template for each slice (Notifications, Settings, Leaderboard, Bank):

1. **API parity**: all required read/write actions implemented.
2. **Business parity**: service outcomes/messages match legacy behavior.
3. **Validation parity**: same auth, CSRF, and input rules.
4. **UX parity**: same user flows and edge-case behavior.
5. **Telemetry**: request timing, error rate, and client exceptions tracked.
6. **Feature flag**: domain can be toggled on/off without deploy.
7. **Rollback proof**: fallback to PHP view tested.
8. **Tests**: contract tests + smoke tests passing in CI.
9. **PWA parity**: domain behavior defined for online/offline and refresh/reconnect states.

## PWA Acceptance Gates

- Manifest is valid and install prompt can be triggered on supported browsers.
- Service worker update flow verified (new version activates cleanly).
- Lighthouse CI thresholds pass for migrated pages:
  - Performance ≥ 80
  - Accessibility ≥ 90
  - Best Practices ≥ 90
  - PWA checks pass
- Offline smoke test passes for app shell and at least one migrated read-only view.

## Per-Sprint Execution Template

- Select one domain and lock scope.
- Write endpoint contract doc first.
- Implement API adapter controllers only (no business logic in controllers).
- Build SPA components for one page section at a time.
- Implement corresponding PWA behavior for the same slice (cache/read/offline UX).
- Run parity checklist and ship behind flag.
- Observe metrics for one week before widening rollout.

## Risks and Controls

- **Risk:** hidden behavior differences between PHP views and SPA.
  - **Control:** parity matrix and side-by-side UAT before flag rollout.
- **Risk:** API drift during refactors.
  - **Control:** contract tests and semantic versioning for `/api/v1`.
- **Risk:** rollout regressions.
  - **Control:** feature flags + instant fallback to server-rendered routes.
- **Risk:** stale cached API responses causing incorrect UI state.
  - **Control:** cache TTL policy, versioned caches, and explicit revalidation on focus/reconnect.
- **Risk:** push notification permission fatigue.
  - **Control:** soft-ask UX before native prompt and settings-based opt-out.
