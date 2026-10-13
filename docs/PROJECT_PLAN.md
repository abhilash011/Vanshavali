# Project Plan & Phase Tracker — Hindu Community & Family Tree Portal

> Canonical plan doc. Supersedes and merges the earlier `01_PROJECT_PLAN.md` and `PHASE_TASKS.md`.
> Update the `Status` column as you go: `Not started` / `In progress` / `Blocked` / `Done`.

---

## 1. Repository Strategy

Two separate repos (recommended, given Vercel + Hostinger split deployment):
```
<project>-web/       → Next.js, deploys to Vercel
<project>-api/        → PHP, deploys to Hostinger via Git/FTP
<project>-mobile/     → React Native (Expo), builds via EAS
```
Each repo deploys independently and has its own CI pipeline — a monorepo isn't worth the added tooling complexity for a two-person or solo build against two different hosting targets.

---

## Phase 0 — Foundation (Week 1–2)

| # | Task | Status |
|---|---|---|
| 0.1 | Confirm Hostinger plan capabilities: PHP version, Composer/SSH access, memory limits, cron support | Not started |
| 0.2 | Decide Laravel vs Slim 4 for the API backend, based on 0.1 | Not started |
| 0.3 | Confirm final product name (currently placeholder "Vanshavali" vs generic BRD naming) | Not started |
| 0.4 | Decide Expo managed vs bare React Native workflow | Not started |
| 0.5 | Set up three GitHub repos: web, api, mobile — with branch protection | Not started |
| 0.6 | Scaffold Next.js 14 project (TypeScript, Tailwind, ESLint, Prettier) | Not started |
| 0.7 | Scaffold PHP API project structure locally (XAMPP or Docker/Sail) | Not started |
| 0.8 | Design and write MySQL schema migrations (users, communities, chapters, members, relationships, events, media, audit_logs, roles) | Not started |
| 0.9 | Configure CORS, JWT, and a basic auth endpoint — confirm the handshake works end-to-end | Not started |
| 0.10 | Configure Vercel project + environment variables | Not started |
| 0.11 | Configure Hostinger deployment pipeline (Git-based or SFTP) + staging subdomain | Not started |
| 0.12 | Draft initial OpenAPI 3.0 spec skeleton (auth, members, families, events) | Not started |
| 0.13 | Set up `.env` structure and secrets management on all three repos (never committed) | Not started |

**Exit criteria:** Both repos exist, deploy to staging, and a health-check request from Next.js to the PHP API succeeds over HTTPS.

---

## Phase 1 — Core Authentication, RBAC & Profiles (Week 3–5)

| # | Task | Status |
|---|---|---|
| 1.1 | Implement `community_id` multi-tenant scoping middleware | Done |
| 1.2 | API: registration endpoint + validation + duplicate-account prevention | Done |
| 1.3 | API: email verification flow | Done |
| 1.4 | API: login (JWT access + refresh token) | Done |
| 1.5 | API: logout / refresh / revoke-token endpoints | Done |
| 1.6 | API: password reset (signed, expiring token via email) | Done |
| 1.7 | API: login throttling / brute-force protection | Done |
| 1.8 | API: RBAC roles — Visitor, Member, Family Coordinator, Content Moderator, Community Admin, Platform Admin | Done |
| 1.9 | API: Policy/Gate layer enforcing role + tenant scope on every endpoint | Done |
| 1.10 | API: central `AuditService` + `audit_logs` writes for all auth events | Done |
| 1.11 | API: Profile CRUD endpoints (self-service) | Done |
| 1.12 | Security pass: HTTPS enforced, prepared statements only, input validation on every route | Done |
| 1.13 | Automated tests for auth + RBAC (happy path + abuse cases) | Not started |
| 1.14 | Web: auth pages (login, register, forgot password) | Done |
| 1.15 | Web: protected dashboard layout + route middleware | Done |
| 1.16 | Web: NextAuth.js integration with the PHP JWT API | Done |
| 1.17 | Web: RBAC-aware UI (hide/disable actions by role) | Done |

**Exit criteria:** A user can register, verify email, log in from the web app, get a scoped JWT, and be blocked from another community's data.

---

## Phase 2 — Family Tree Engine (Week 6–9)

| # | Task | Status |
|---|---|---|
| 2.1 | API: field-level visibility settings (private/family-only/community-only/public), enforced server-side | Done |
| 2.2 | API: profile correction/removal request flow | Done |
| 2.3 | Design `relationships` graph model (parent/child/spouse/sibling/guardian/adopted/step/unknown) | Done |
| 2.4 | API: relationship CRUD endpoints | Done |
| 2.5 | API: family-tree read endpoint returning graph JSON (nodes + edges) for a given root member | Done |
| 2.6 | API: proposal → approval workflow for shared family-tree edits | Done |
| 2.7 | Handle deceased / unknown / adopted / blended-family edge cases in data model + API | Done |
| 2.8 | API: member directory search (name, region) | Done |
| 2.9 | Ensure private tree data is not publicly searchable by default (server-enforced) | Done |
| 2.10 | API: media upload endpoint with type/size validation + EXIF stripping | Done |
| 2.11 | Consent flag capture for photo uploads | Done |
| 2.12 | Audit logging for all family-tree changes | Done |
| 2.13 | Web: interactive tree visualization (D3.js / react-d3-tree), pan/zoom/expand | Done |
| 2.14 | Web: add/edit member forms with relationship linking | Done |
| 2.15 | Web: profile image upload UI | Done |

**Exit criteria:** A member can view/edit their profile, propose a family member, and see a rendered graph of relationships with correct visibility enforcement — on the web app.

---

## Phase 3 — Community & Content (Week 10–12)

| # | Task | Status |
|---|---|---|
| 3.1 | API: communities/chapters CRUD | Done |
| 3.2 | API: events & announcements CRUD (create, edit, schedule, publish, archive) | Done |
| 3.3 | API: media gallery endpoints | Done |
| 3.4 | Web: public community landing pages (SSR/ISR) | Done |
| 3.5 | Web: events calendar and detail pages | Done |
| 3.6 | Web: announcements feed | Done |
| 3.7 | Web: photo gallery with lightbox | Done |
| 3.8 | Web: member directory / search UI | Done |

**Exit criteria:** A visitor can browse public community content, events, and galleries without logging in.

---

## Phase 4 — Mobile App (React Native / Expo) (Week 13–15)

| # | Task | Status |
|---|---|---|
| 4.1 | Scaffold Expo app + TypeScript + React Navigation | Not started |
| 4.2 | Generate/consume TS client from the shared OpenAPI spec | Not started |
| 4.3 | Secure token storage (`expo-secure-store`, Keychain/Keystore) | Not started |
| 4.4 | Login / registration / password reset screens | Not started |
| 4.5 | Profile view/edit screens | Not started |
| 4.6 | Mobile-optimized family-tree view (`react-native-svg`, pinch-zoom/tap-to-expand) | Not started |
| 4.7 | Events / announcements screens | Not started |
| 4.8 | Mobile accessibility pass (accessibility labels, font scaling, contrast) | Not started |
| 4.9 | Expo EAS Build configuration | Not started |
| 4.10 | TestFlight (iOS) internal testing setup | Not started |
| 4.11 | Play Console internal testing setup (Android) | Not started |

**Exit criteria:** Installable internal test builds on both platforms with auth, profile, and tree viewing working end-to-end against the same API used by the web app.

---

## Phase 5 — Administration (Week 16–17)

| # | Task | Status |
|---|---|---|
| 5.1 | API: admin endpoints (member approval, content moderation) | Not started |
| 5.2 | API: data export (CSV/PDF), scoped, logged, permission-checked | Not started |
| 5.3 | Web: admin dashboard — pending member approvals | Not started |
| 5.4 | Web: admin dashboard — pending family-tree edit approvals | Not started |
| 5.5 | Web: content moderation queue (announcements, photos, gallery) | Not started |
| 5.6 | Multi-community/chapter admin boundary enforcement — tested explicitly | Not started |
| 5.7 | Platform Administrator cross-community oversight view | Not started |
| 5.8 | Web: admin activity summary / basic reporting dashboard | Not started |
| 5.9 | Web: audit log viewer for admins | Not started |

**Exit criteria:** Community admins can manage their own community's members/content without seeing other communities' data; platform admin has oversight across all.

---

## Phase 6 — Privacy, Inclusion & Governance Hardening

| # | Task | Status |
|---|---|---|
| 6.1 | Enhanced privacy for minors — hard floor: never public by default | Not started |
| 6.2 | Caste/sensitive fields fully optional, access-controlled, never used in matching/ranking logic | Not started |
| 6.3 | Privacy request / correction / dispute handling workflow | Not started |
| 6.4 | Inappropriate-content reporting flow | Not started |
| 6.5 | Publish Terms of Use + Privacy Notice, gate registration behind acceptance | Not started |
| 6.6 | Legal/privacy review for target regions | Not started |
| 6.7 | Explicit code review confirming no discriminatory profiling/matching logic exists anywhere | Not started |

**Exit criteria:** Governance requirements in BRD §10 are demonstrably satisfied, ready for stakeholder sign-off.

---

## Phase 7 — Security, Performance & Reliability Hardening (Week 18)

| # | Task | Status |
|---|---|---|
| 7.1 | Full security review: rate limiting, CSRF, XSS, SQLi, session handling (OWASP Top 10 for the API) | Not started |
| 7.2 | Least-privilege MySQL DB user configured on Hostinger | Not started |
| 7.3 | Automated daily DB backups + tested restore runbook | Not started |
| 7.4 | Off-site backup copy (outside Hostinger) scheduled | Not started |
| 7.5 | Load/performance test family-tree view with large family datasets | Not started |
| 7.6 | Dependency audit (`composer audit`, `npm audit`) integrated into CI | Not started |
| 7.7 | Accessibility audit — WCAG 2.1 AA, web and mobile | Not started |
| 7.8 | i18n verification — English + at least one additional locale wired end-to-end | Not started |
| 7.9 | SEO pass on public pages (metadata, OG tags, sitemap) | Not started |
| 7.10 | Monitoring/error tracking set up (e.g. Sentry) for API + both frontends | Not started |
| 7.11 | Penetration test or third-party security review (if budget allows) | Not started |
| 7.12 | End-to-end test suite covering auth, tree editing, approvals | Not started |

**Exit criteria:** System passes an internal security/reliability checklist before any multi-community public launch.

---

## Phase 8 — Launch Readiness

| # | Task | Status |
|---|---|---|
| 8.1 | BRD §16 stakeholder review and formal sign-off | Not started |
| 8.2 | Onboard first pilot community with real data | Not started |
| 8.3 | Admin/moderator training material | Not started |
| 8.4 | Success-measure tracking dashboard (BRD §13 metrics) | Not started |
| 8.5 | Production deployment + DNS configuration (web + API) | Not started |
| 8.6 | App Store + Play Store public release | Not started |
| 8.7 | Documentation handover | Not started |
| 8.8 | Post-launch monitoring window + rapid-fix triage process | Not started |

---

## Phase 9 — Post-Launch / Advanced Features (Future)

| # | Task | Status |
|---|---|---|
| 9.1 | Push notifications for events/announcements (mobile) | Not started |
| 9.2 | Offline support + write-queue sync on mobile | Not started |
| 9.3 | 2FA (TOTP) for accounts | Not started |
| 9.4 | Additional Indian language packs | Not started |
| 9.5 | Advanced admin analytics/reporting | Not started |
| 9.6 | Donation/payment module (explicitly out of scope for v1 per BRD §5) | Not started |

---

## Risk Register

| Risk | Impact | Likelihood | Mitigation |
|---|---|---|---|
| CORS issues between Vercel & Hostinger | High | High | Whitelist exact Vercel domains, handle preflight correctly in PHP |
| Hostinger PHP version/extension limitations | Medium | Medium | Verify PHP 8.2+, required extensions (`pdo_mysql`, `openssl`, `mbstring`) before Phase 0 exit |
| JWT token security (XSS/CSRF) | High | Medium | httpOnly cookies via the Next.js BFF, short-lived access tokens, secure device storage on mobile |
| Large family tree performance | Medium | Medium | Paginated API, lazy-loaded branches, client-side rendering optimizations |
| File upload abuse | High | Low | MIME-sniff validation, size limits, re-encoding, renaming, storage outside webroot |
| Hostinger shared hosting CPU/RAM limits | Medium | Medium | Optimize queries early; budget for a VPS upgrade if needed |
| Migrating existing `members.json` data to MySQL | Low | High | Write and test a dedicated migration script against the current legacy data before cutover |
| Web/mobile client drift from API changes | Medium | Medium | Shared OpenAPI spec + generated TS types as the single contract |

---

## Definition of Done

A feature is "done" when:

- [ ] API endpoint implemented with input validation and error handling
- [ ] Frontend UI completed with responsive design and loading/error states
- [ ] RBAC enforced on both frontend (route guards / hidden actions) and backend (middleware)
- [ ] Unit tests pass for API logic
- [ ] Integration tested against the real API (not mocked)
- [ ] Accessibility checked (keyboard nav, contrast, labels)
- [ ] Code reviewed and merged to `main`
- [ ] Deployed to staging and manually verified
