# Architecture — Hindu Community & Family Tree Portal

> Canonical architecture doc. Supersedes and merges the earlier `ARCHITECTURE.md` and `02_SYSTEM_ARCHITECTURE.md`.
> Stack: **Next.js (Vercel)** + **React Native (Expo)** + **PHP REST API (Hostinger)** + **MySQL 8.0 (Hostinger)**

---

## 1. High-Level Architecture

```
                    ┌──────────────────┐        ┌──────────────────────┐
                    │   Next.js Web     │        │   React Native App    │
                    │   (Vercel)        │        │   (Expo — iOS/Android)│
                    └─────────┬────────┘        └───────────┬───────────┘
                              │                              │
                              │        HTTPS / JSON          │
                              │        JWT Bearer             │
                              └──────────────┬───────────────┘
                                             ▼
                              ┌─────────────────────────────┐
                              │   PHP REST API (Hostinger)    │
                              │   /api/v1/...                 │
                              │   Router → Middleware →        │
                              │   Controller → Service →       │
                              │   Repository (PDO)             │
                              └───────────────┬───────────────┘
                                              ▼
                              ┌─────────────────────────────┐
                              │      MySQL 8.0 (Hostinger)    │
                              └─────────────────────────────┘
                                              │
                              ┌───────────────┴───────────────┐
                              │  File storage (uploads/)        │
                              │  outside public webroot          │
                              └─────────────────────────────┘
```

**Rule that governs everything else:** both clients (web and mobile) talk **only** to the PHP REST API over HTTPS/JSON. Neither client, nor Next.js's own API routes, ever touches MySQL directly. Next.js API routes exist only as a thin BFF (Backend-for-Frontend) for cookie handling — not as a second data layer.

---

## 2. Backend — PHP REST API (Hostinger)

### 2.1 Framework decision
- **PHP 8.2+.** Confirm exact version + whether Composer/SSH is available on your Hostinger plan before committing.
- **If Composer/CLI access is available:** Laravel is the better fit for a project this size (multi-tenant, RBAC, audit logging, file uploads) — you get auth scaffolding, migrations, Eloquent ORM, and validation out of the box.
- **If shared hosting restricts Composer/SSH:** fall back to **Slim Framework 4 + PDO** — lighter, deployable over plain FTP, no CLI dependency.
- Either way: PSR-4 autoloading, PSR-12 style, `.env`-driven config (never committed).

> **Open decision — confirm before Phase 1:** which of the two above, based on actual Hostinger plan capabilities.

### 2.2 Directory structure
```
vanshavali-api/
├── public/
│   └── index.php                 # single entry point
├── src/
│   ├── Controllers/               # AuthController, MemberController, TreeController,
│   │                               # RelationshipController, CommunityController,
│   │                               # EventController, MediaController, AdminController,
│   │                               # AuditController
│   ├── Middleware/                # CorsMiddleware, AuthMiddleware (JWT), RbacMiddleware,
│   │                               # RateLimitMiddleware, TenantScopeMiddleware
│   ├── Models/                    # User, Member, Relationship, Community, Event, MediaAsset
│   ├── Services/                  # AuthService, TreeService, MemberService,
│   │                               # UploadService, AuditService
│   ├── Repositories/               # UserRepository, MemberRepository, RelationshipRepository...
│   ├── Helpers/                    # JwtHelper, Response (JSON envelope builder), Validator
│   └── Config/                     # database.php, cors.php, app.php (all read from .env)
├── migrations/                     # 001_create_users.sql, 002_create_members.sql, ...
├── uploads/                        # ideally moved outside public webroot
├── logs/
├── vendor/
├── .env / .env.example
├── .htaccess                       # Apache rewrite → index.php
└── composer.json
```

### 2.3 Layered request lifecycle
```
HTTP Request → .htaccess rewrite → index.php
  → CORS Middleware (preflight + headers)
  → Router (URI + method → Controller)
  → Auth Middleware (verify JWT)
  → RBAC Middleware (role + tenant check)
  → Controller (parse/validate input, call Service)
  → Service (business logic, authorization rules)
  → Repository (PDO query → MySQL)
  → JSON Response
```
Each layer has one job: Controllers only handle HTTP concerns, Services hold business rules, Repositories only run queries. This separation is what makes RBAC and audit logging enforceable in one place instead of scattered across endpoints.

### 2.4 API design
- RESTful JSON, versioned under `/api/v1/...` so future breaking changes don't affect existing clients.
- Auth: `POST /auth/register`, `/auth/login`, `/auth/refresh`, `/auth/logout`, `/auth/password/forgot`, `/auth/password/reset`
- Core resources: `/members`, `/relationships`, `/families/{id}/tree`, `/communities`, `/communities/{id}/chapters`, `/events`, `/announcements`, `/media`
- Admin: `/admin/approvals`, `/admin/audit-logs`, `/admin/exports`
- Response envelope:
```json
{ "success": true, "data": {}, "meta": {}, "error": null }
```
- Error shape:
```json
{ "success": false, "data": null, "error": { "code": "VALIDATION_ERROR", "message": "...", "fields": {} } }
```
- Every mutating endpoint (`POST`/`PUT`/`PATCH`/`DELETE`) writes an audit record.

### 2.5 CORS
Cross-origin is unavoidable now that the frontend lives on Vercel and the API on Hostinger — this is the single biggest source of "works locally, breaks in prod" bugs for this stack, so get it right early:
```php
$allowedOrigins = [
    'http://localhost:3000',
    'https://<your-app>.vercel.app',   // Vercel preview deployments
    'https://<your-production-domain>',
];
```
Mobile app requests don't carry an `Origin` header the same way browsers do, so CORS rules apply to the web client only — the API's JWT check is what protects mobile calls.

### 2.6 Multi-tenancy
Every community/chapter is a tenant. **Shared database, `community_id` column on every tenant-scoped table** (`members`, `relationships`, `events`, `media`). A `TenantScopeMiddleware` auto-injects `WHERE community_id = ?` on every query unless the caller is a Platform Administrator. Separate databases per community were considered and rejected — not worth the operational overhead on a single shared Hostinger MySQL instance.

### 2.7 Authentication & session strategy
- **JWT access tokens (short-lived, ~15 min) + refresh tokens.**
- **Web:** refresh token in an `httpOnly`, `Secure`, `SameSite=Strict` cookie, set via a Next.js API route (not directly by the PHP API, since the API's cookie would be on a different domain than the site — this is why the BFF layer exists). Access token held in memory only, never `localStorage`.
- **Mobile:** access + refresh tokens stored in **Keychain (iOS)** / **Keystore (Android)** via `expo-secure-store` — never `AsyncStorage` in plaintext.
- Passwords: `password_hash()` with `PASSWORD_ARGON2ID` if the Hostinger PHP build supports it, else `PASSWORD_BCRYPT`.
- Login throttling per account + per IP.
- Email verification on registration; password reset via signed, expiring tokens.
- 2FA (TOTP) — deferred to post-launch.

### 2.8 Authorization (RBAC)
Roles: Visitor, Member, Family Coordinator, Content Moderator, Community Administrator, Platform Administrator.
- `role` + `community_id` stored on the user account — a Community Administrator is only an admin within their own community.
- Enforced via Policy classes (Laravel) or Gate-style middleware (Slim) on every controller action.
- Field-level profile visibility (private / family-only / community-only / public) is enforced **server-side at query time** — never trust the client to hide a field.

### 2.9 Family tree data model
- `members` — a person record, which may or may not be linked to a `user_account`.
- `relationships` — `member_id`, `related_member_id`, `relationship_type` (parent, child, spouse, sibling, guardian, adopted, step, unknown), `verification_status` (proposed/approved/rejected), `effective_date`.
- Modeled as a **graph** (nodes + typed edges), not a strict hierarchy — this is what lets deceased, unknown-parent, adopted, step, and blended-family cases render correctly without forcing a rigid tree shape.
- The API returns graph JSON; layout (positioning nodes visually) happens client-side (D3.js on web, `react-native-svg` on mobile) — never computed server-side.
- Shared-data edits go through a `family_edit_proposals` table when community policy requires approval, otherwise direct + audit-logged.

### 2.10 Media uploads
- Validate MIME type by content-sniffing (not extension), enforce size limits, re-encode images server-side and strip EXIF/GPS metadata (privacy-critical for minors' photos), store outside the public webroot, serve via an authenticated/signed endpoint.
- Consent flag stored per asset (`media_assets.consent_confirmed`).

### 2.11 Audit logging
`audit_logs`: `actor_user_id`, `action`, `entity_type`, `entity_id`, `before_state` (JSON), `after_state` (JSON), `ip_address`, `user_agent`, `created_at`. Written centrally through an `AuditService` — never scattered manually across controllers — for auth events, approvals/rejections, family-tree changes, admin actions, and data exports.

### 2.12 Security layers
| Layer | Control |
|---|---|
| Transport | HTTPS everywhere (TLS 1.2+), HSTS |
| Secrets | `.env`, excluded from git, never in PHP source |
| Auth | JWT short-lived + rotating refresh, revocation list for logout-everywhere |
| CORS | Explicit origin whitelist |
| Input | Server-side validation on every endpoint regardless of frontend validation |
| SQL | PDO prepared statements / ORM only — no string concatenation |
| RBAC | Role + tenant middleware on every route |
| File upload | MIME sniffing, size limit, re-encode, rename, store outside webroot |
| Rate limiting | Per-IP + per-account throttling on auth and write endpoints |
| Audit | All mutations logged |
| CSP | Content-Security-Policy headers |
| DB user | Least-privilege MySQL user (no DROP/ALTER in the runtime credential) |

### 2.13 Backups
Daily Hostinger MySQL backups + a weekly off-site export (Hostinger backups alone shouldn't be the only copy) + a documented, tested restore runbook before any go-live.

---

## 3. Frontend — Web (Next.js on Vercel)

### 3.1 App Router structure
```
src/
├── app/
│   ├── (public)/            page.tsx, about/, communities/, events/, contact/
│   ├── (auth)/               login/, register/, forgot-password/
│   ├── (dashboard)/          layout.tsx, dashboard/, profile/, family-tree/, members/, settings/
│   ├── (admin)/               layout.tsx, admin/, admin/members/, admin/content/, admin/audit/
│   ├── api/auth/[...nextauth]/route.ts    # BFF — talks to PHP API, sets httpOnly cookies
│   ├── layout.tsx, loading.tsx, error.tsx, not-found.tsx
├── components/  ui/  layout/  forms/  tree/  shared/
├── hooks/
├── lib/  api-client.ts  auth.ts  constants.ts  utils.ts
├── stores/                   # Zustand
├── types/                    # generated from the API's OpenAPI spec
└── styles/
```

### 3.2 State management
| State | Tool |
|---|---|
| Server state (API data) | TanStack Query (React Query) |
| Client UI state | Zustand |
| Auth session | NextAuth.js (Auth.js) v5, credentials provider pointing at the PHP API |
| Forms | React Hook Form + Zod |
| URL state | Next.js router + searchParams |

### 3.3 Other decisions
- Styling: Tailwind CSS + Radix UI primitives (or shadcn/ui, which is built on Radix) for accessible components — WCAG 2.1 AA.
- Family tree visualization: D3.js or `react-d3-tree`.
- i18n: `next-intl`, English first, structured so additional Indian languages are just new locale files.
- SSR/ISR for public pages (landing, events, galleries) for SEO and fast mobile-network loads; CSR for authenticated dashboard/admin areas.
- Animation: Framer Motion, used sparingly (layout transitions, not decoration).

---

## 4. Frontend — Mobile (React Native / Expo)

- **Expo, managed workflow** — fastest path to shipping both platforms from one codebase for a small team; move to bare workflow only if a specific native module forces it.
- Navigation: React Navigation.
- Data layer: same TanStack Query pattern as web, for consistency and shared mental model.
- Token storage: `expo-secure-store` (Keychain/Keystore) — never AsyncStorage for tokens.
- Family tree visualization: `react-native-svg` + a graph layout approach suited to a small screen (pinch-zoom, tap-to-expand branch) rather than the pan/zoom-heavy desktop interaction.
- Push notifications (Expo Notifications) and biometric unlock (Face ID/Touch ID) — both post-launch, not v1.
- Offline: cache last-fetched data via TanStack Query persistence so the app isn't blank with no signal; write-queue + background sync is a later phase, not v1.
- Release path: Expo EAS Build → TestFlight (iOS) / Play Console internal testing (Android) → public store release.

---

## 5. Cross-Client Communication Flow

```
Next.js (Vercel)                                    PHP API (Hostinger)
─────────────────                                    ──────────────────
1. User submits login form
2. NextAuth credentials provider  ──────────────►   POST /api/v1/auth/login
3.                                 ◄──────────────   { access_token, refresh_token, user }
4. Access token → memory; refresh token → httpOnly cookie (set by Next.js route)
5. Subsequent calls: Authorization: Bearer <token>  ──────────────►  GET /api/v1/members
6.                                 ◄──────────────   { data: [...], meta: {...} }
```
React Native follows the same request/response contract — it authenticates directly against the same `/api/v1/auth/*` endpoints and stores tokens in secure device storage instead of cookies (there's no BFF layer on mobile; the API is called directly).

## 6. Shared API Contract

Maintain a single **OpenAPI 3.0 spec** (`openapi.yaml`) as the source of truth for every endpoint's request/response shape. Generate TypeScript types from it (`openapi-typescript`) for both Next.js and React Native, so both clients share one typed contract and fail loudly at compile time if the backend shape changes — this is what keeps three codebases (API, web, mobile) from drifting apart.

---

## 7. Deployment Topology

```
                    INTERNET
              ┌───────────┴───────────┐
              ▼                       ▼
   ┌─────────────────────┐   ┌─────────────────────┐
   │  Vercel Edge         │   │  Hostinger           │
   │  (CDN + Serverless)  │   │  (Apache + PHP)       │
   │                      │   │                       │
   │  Next.js SSR/SSG     │◄─►│  PHP REST API         │
   │  Static assets       │   │  File uploads          │
   │  BFF API routes      │   │  MySQL 8.0             │
   └─────────────────────┘   │  Cron jobs (backups)   │
                              └─────────────────────┘
              ▲
              │  same PHP API
              ▼
   ┌─────────────────────┐
   │  React Native app     │
   │  (App Store /          │
   │   Play Store)          │
   └─────────────────────┘
```

Environments:

| Environment | Web (Vercel) | API (Hostinger) | Database |
|---|---|---|---|
| Local | `localhost:3000` | `localhost/vanshavali-api` | Local MySQL (XAMPP/Sail) |
| Staging | `<app>-staging.vercel.app` | `api-staging.<domain>.com` | Hostinger staging DB |
| Production | `<domain>.com` | `api.<domain>.com` | Hostinger production DB |

---

## 8. Scalability Considerations

| Concern | v1 solution | Future scale-up |
|---|---|---|
| DB load | Single MySQL instance | Read replicas, connection pooling |
| API throughput | Hostinger shared/VPS PHP | Hostinger VPS upgrade, or migrate to cloud |
| Image storage | Hostinger disk | Cloudinary / S3-compatible |
| CDN | Vercel Edge (web only) | Cloudflare in front of the API |
| Caching | React Query (client) + OPcache (PHP) | Redis at the API layer |
| Search | MySQL FULLTEXT | Algolia / Meilisearch |
| Tree rendering | Client-side D3.js / react-native-svg | Virtualized rendering for very large trees |

---

## 9. Non-Functional Requirement Mapping (traceability to BRD §9)

| BRD requirement | Architectural answer |
|---|---|
| Accessibility (WCAG 2.1 AA) | Radix/shadcn primitives + semantic HTML on web; RN accessibility props on mobile |
| Performance on mobile networks | SSR/ISR public pages, image optimization, paginated/lazy-loaded tree branches |
| Scalability | Multi-tenant single DB with indexed `community_id`; stateless PHP API |
| Maintainability | `.env` config, OpenAPI contract, versioned migrations, CI checks |
| Privacy for minors | Explicit `is_minor` flag with a hard-coded visibility floor, enforced server-side regardless of user setting |

---

## 10. Open Decisions to Confirm Before Phase 1

1. Laravel vs Slim 4 — depends on confirmed Hostinger Composer/SSH/CLI access.
2. Product name — BRD refers to it generically; `01_PROJECT_PLAN.md` used "Vanshavali." Confirm before it's baked into domains, package names, and app store listings.
3. Transactional email provider — Hostinger SMTP vs SendGrid/Postmark (affects verification/reset email deliverability).
4. Expo managed vs bare workflow (managed is the default recommendation above).
5. Which Indian languages beyond English are needed at launch vs later.
