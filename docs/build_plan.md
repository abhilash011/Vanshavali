# Vanshavali — Phase 0 Build Plan

## Decisions Made
- **Architecture**: Full 3-repo split (PHP API + Next.js Web + React Native Mobile)
- **API Framework**: Slim 4 + PDO (lightweight, Hostinger-compatible)
- **Database**: Local MySQL via XAMPP first, Hostinger for production
- **Product Name**: Vanshavali

## What We're Building Now (Phase 0)

### 1. PHP REST API (`vanshavali-api/`)
- [x] Scaffold Slim 4 project with PSR-4 autoloading
- [x] Directory structure per ARCHITECTURE.md §2.2
- [x] `.env` configuration (database, JWT secret, CORS origins)
- [x] MySQL schema migrations (users, communities, members, relationships, events, media, audit_logs)
- [x] CORS middleware
- [x] JWT auth endpoints (register, login, refresh, logout)
- [x] Basic health-check endpoint
- [x] `.htaccess` rewrite rules
- [x] Migrate existing `members.json` data into the new schema

### 2. Next.js Web (`vanshavali-web/`)
- [x] Scaffold Next.js 16 (App Router, TypeScript)
- [x] Configure environment variables for API URL
- [x] Basic auth pages (login, register) with golden heritage theme
- [x] Protected dashboard layout with real-time stats & member table
- [x] Health-check verification against API
- [x] Rich landing page with live backend health indicator & tree preview

### 3. React Native Mobile (`vanshavali-mobile/`)
- [x] Scaffold Expo project (TypeScript)
- [x] Basic project structure & types
- [x] Configure API connection & services with platform-aware networking
- [x] Interactive lineage explorer screen with auth, member directory, search & stats

### 4. Shared
- [x] OpenAPI 3.0 spec skeleton (`openapi.yaml` - 470 lines)
- [x] Data migration script (`migrations/seed.php` - legacy members.json → MySQL)

## Exit Criteria
> [x] All three repos exist, deploy locally, and health-check requests and data flows between clients and the PHP API succeed.

## Critical Security Notes
- Database credentials moved from PHP source to `.env`
- JWT secret in `.env`, never committed
- CORS whitelist configured properly
- Prepared statements only (no string concatenation for SQL)
