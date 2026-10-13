# Hindu Community & Family Tree Portal

A secure, multi-community platform for Hindu families and community organisations to maintain family trees, member profiles, and community content — rebuilt from a monolithic PHP/JSON prototype into a decoupled, production-grade system.

This is a rebuild in progress. The original single-file PHP prototype (`register.php`, `members.json`, session-based login) is being replaced entirely by the architecture below — nothing in this README describes legacy code that still exists in the live system.

## Stack

- **Web frontend:** Next.js 14+ (App Router), TypeScript, deployed on Vercel
- **Mobile apps:** React Native (Expo), Android + iOS
- **API backend:** PHP 8.2+ REST API, deployed on Hostinger
- **Database:** MySQL 8.0 (Hostinger)
- **Auth:** JWT access + refresh tokens, RBAC by role and community

See [`ARCHITECTURE.md`](./ARCHITECTURE.md) for the full technical design and [`PROJECT_PLAN.md`](./PROJECT_PLAN.md) for the phase-by-phase build plan and progress tracker.

## Repositories

This project is split across three repos (see `ARCHITECTURE.md` §1 for why):

```
<project>-web/       Next.js frontend → Vercel
<project>-api/        PHP REST API → Hostinger
<project>-mobile/     React Native app → App Store / Play Store
```

## Project Documents

| Document | Purpose |
|---|---|
| [`BRD.md`](./BRD.md) | Business requirements — what the platform needs to do and why |
| [`ARCHITECTURE.md`](./ARCHITECTURE.md) | System architecture, data model, security design, deployment topology |
| [`PROJECT_PLAN.md`](./PROJECT_PLAN.md) | Phase-by-phase build plan, risk register, definition of done — the live progress tracker |

## Local Setup

Setup instructions will live in each repo's own README once the repos are scaffolded (Phase 0 of `PROJECT_PLAN.md`):
- `<project>-api/README.md` — PHP API local environment, `.env` setup, migrations
- `<project>-web/README.md` — Next.js local dev, environment variables
- `<project>-mobile/README.md` — Expo local dev, EAS build setup

## Status

Pre-Phase 0. See `PROJECT_PLAN.md` for current progress against each phase.

## License

No license file yet. Add one before any public release or distribution.
