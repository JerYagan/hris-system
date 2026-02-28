# Temporary Deployment Plan (Free Hosting)

**Project:** DA HRIS  
**Date:** February 28, 2026  
**Purpose:** Get a temporary online deployment for demo/testing while keeping cost at zero (or near-zero free tier).

---

## 1) Quick Reality Check

This project is **PHP-rendered** (`pages/.../*.php`) and uses Composer libraries (`dompdf`, `phpoffice/phpspreadsheet`, `phpmailer`).

- **Netlify/Vercel** are best for static/Jamstack apps.
- They are **not ideal for full traditional PHP hosting** without significant rewrite/workarounds.
- For a temporary full-system deployment, use a host that supports **PHP + file uploads + MySQL/Supabase integration**.

---

## 2) Hosting Options (Free Tier)

## Option A — **Recommended (Fastest for Full PHP Demo): InfinityFree**

Use this when you want to run existing PHP pages with minimal refactor.

**Pros**
- Native PHP hosting
- Free MySQL databases
- Fastest path for a temporary live URL

**Cons / Limits**
- Free-tier performance and reliability limits
- Strict limits for background jobs and large uploads
- Not ideal for production security/performance

## Option B — Netlify or Vercel (Frontend-only Demo)

Use this only if you want a **UI showcase** and can avoid PHP server logic.

**Pros**
- Very easy CI/CD from GitHub
- Good CDN and preview URLs

**Cons**
- Requires converting PHP pages to static output or separate backend APIs
- Not suitable for current full PHP architecture as-is

## Option C — Other Free-Tier PaaS (Render/Koyeb/Fly/Railway)

Use if you can containerize and are okay with setup complexity.

**Pros**
- More modern deployment model
- Better migration path to paid production later

**Cons**
- Free-tier rules change often
- Cold starts/sleeping apps/resources limits
- More DevOps work than shared PHP hosting

> **Recommendation for now:** Start with **Option A (InfinityFree)** for a temporary full-system demo, then migrate later to paid VPS/PaaS.

---

## 3) Temporary Target Architecture

- **Web App:** InfinityFree (PHP app)
- **Data Layer:**
  - If current modules already use Supabase: continue Supabase (recommended for speed)
  - If not yet integrated: use InfinityFree MySQL temporarily
- **File Storage:**
  - Keep uploads small and temporary on host storage, or
  - Use Supabase Storage for better reliability
- **Mail:** Keep `PHPMailer` disabled or sandboxed for demo unless SMTP is configured

---

## 4) Deployment Phases

## Phase 0 — Pre-deploy Hardening (Local)

1. Create a deploy branch: `deploy/temp-free-hosting`.
2. Add environment-based config file (e.g., `config/env.php`) for:
   - DB credentials
   - Supabase URL/keys
   - App URL
   - Upload limits
3. Disable debug output in deployed environment:
   - `display_errors=Off`
   - Log errors to file only
4. Confirm writable folders exist and permissions are correct:
   - `storage/document/`
   - `storage/payslips/`
   - `storage/reports/`
5. Remove dev-only tools from public access (`tools/`, debug scripts) via routing or server rules.

## Phase 1 — Hosting Setup

1. Create InfinityFree account and temporary domain/subdomain.
2. Create MySQL DB (if using host DB) and save credentials.
3. Upload project via File Manager/FTP.
4. Set document root to project root (or `public` folder if you introduce one).
5. Ensure Composer dependencies are present:
   - Prefer uploading `vendor/` from local if composer CLI is unavailable on host.

## Phase 2 — Configuration

1. Set production-like env values in `config/env.php`.
2. Update base URL references for assets and links.
3. If using Supabase, set correct anon/service keys by role scope.
4. Validate include paths and file path separators for Linux hosting (`/`).

## Phase 3 — Data and Storage Validation

1. Run schema migration/import (MySQL or Supabase SQL scripts as needed).
2. Create at least one test account per role:
   - admin, staff, employee, applicant
3. Test upload/download flow for:
   - Documents
   - Payslips
   - Reports
4. Verify max upload size limits and graceful error messages.

## Phase 4 — Smoke Test (Live URL)

1. Authentication pages load and submit correctly.
2. Role-based access controls are enforced.
3. Payroll pages render and data actions work.
4. PDF/Excel generation works (`dompdf`, `phpspreadsheet`).
5. No critical PHP warnings/notices visible in browser.
6. Basic mobile responsiveness check on key pages.

## Phase 5 — Temporary Operations

1. Add a `DEPLOYMENT_NOTES.md` with:
   - Live URL
   - Credentials ownership
   - DB location
   - Known limits/issues
2. Schedule lightweight backup routine:
   - DB export weekly
   - Critical uploaded files backup weekly
3. Track known free-tier constraints for team expectations.

---

## 5) Rollback Plan

If deployment fails:

1. Keep local XAMPP as fallback demo environment.
2. Repoint demo to local LAN/tunnel (temporary) if public host breaks.
3. Restore previous stable upload package from backup zip.

---

## 6) Risk Notes (Temporary Hosting)

- Free hosts can suspend inactive or resource-heavy apps.
- Performance may be inconsistent during peak times.
- Security controls are limited compared to managed production hosting.
- Email deliverability is often restricted on free plans.

---

## 7) Recommended Next Upgrade Path (After Temporary Demo)

1. Move to paid VPS/PaaS (stable CPU/RAM, SSL, cron, logs).
2. Separate app and storage concerns (object storage + DB backups).
3. Add CI/CD from GitHub with staged environments.
4. Formalize secrets management and monitoring.

---

## 8) Minimal Go-Live Checklist

- [ ] App loads from public URL
- [ ] Login works for all main roles
- [ ] Critical CRUD paths pass smoke test
- [ ] Upload and report exports are functional
- [ ] Error display disabled in production
- [ ] Backup export completed and documented

---

## 9) Decision Summary

For **immediate temporary deployment**, choose:

- **Primary:** InfinityFree (full PHP app, least rewrite)
- **Alternative:** Netlify/Vercel only for frontend-only demo build

This keeps delivery fast now and preserves a clean path to proper production hosting later.
