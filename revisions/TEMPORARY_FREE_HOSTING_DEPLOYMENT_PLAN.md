# Temporary Deployment Plan (Free Hosting)

**Project:** DA HRIS  
**Date:** March 7, 2026  
**Purpose:** Get a temporary public deployment for demo/testing with minimal cost while matching the current PHP + Supabase architecture.

---

## 1) Current Reality Check

This project is a traditional PHP application with multi-role pages under `pages/` and Composer dependencies for PDF, spreadsheet, and mail features.

Important current-state facts:

- The runtime is now **Supabase-first**, not MySQL-first.
- Multiple modules load credentials from the root `.env` file.
- Several pages assume the app is hosted under the `/hris-system` base path.
- The app writes files to local `storage/` directories for documents, payslips, and reports.
- Free static hosts like Netlify/Vercel are still not a fit for the full app.

Implication:

- A temporary deployment still needs **PHP + cURL + writable storage + Composer libraries + outbound HTTPS to Supabase**.
- A free host may be acceptable for demo use, but it is not a stable production option.

---

## 2) Hosting Options (Free Tier)

## Option A — Shared Free PHP Hosting (Fastest Demo Path)

Use this when the goal is to publish the current PHP app with the least rewrite.

**Pros**
- Runs existing PHP pages directly
- Usually supports FTP/File Manager upload
- Lowest-effort path for a temporary live demo

**Cons / Limits**
- Performance and uptime are inconsistent
- Composer CLI may be unavailable, so `vendor/` must usually be uploaded from local
- Large uploads, long-running requests, SMTP, and generated files may hit host limits
- Public folder hardening is weaker than on a managed platform

## Option B — Free-Tier PaaS with Container/Runtime Support

Use this if you can spend more setup effort for a cleaner upgrade path later.

**Pros**
- Better fit for app/runtime configuration
- Easier future migration to a paid plan
- Cleaner environment handling than many shared free hosts

**Cons**
- More setup complexity than shared PHP hosting
- Free tiers often sleep, cold start, or change limits
- Persistent local file storage may still be weak or ephemeral depending on platform

## Option C — Netlify or Vercel (Frontend-only Demo)

Use this only for a UI showcase, not for the current full system.

**Pros**
- Fast previews and CDN delivery
- Simple Git-based deployment

**Cons**
- Not suitable for the current PHP-rendered architecture
- Would require major adaptation or a split frontend/backend deployment

> **Recommendation for now:** Use a PHP-capable free host only for a short-lived demo, and treat it as disposable infrastructure.

---

## 3) Temporary Target Architecture

- **Web App:** PHP host for the current application files
- **Data Layer:** Supabase only
- **Environment Configuration:** root `.env` file on the server
- **File Storage:** local `storage/` directories for temporary hosting, with the option to shift document flows toward Supabase Storage later
- **Mail:** optional for demo; disable or sandbox if SMTP cannot be verified

Notes:

- Do not plan around host MySQL unless you intentionally rewrite parts of the app.
- Supabase URL, service role key, and anon key must be treated as required environment inputs.

---

## 4) Deployment Phases

## Phase 0 — Pre-deploy Hardening (Local)

1. Create a deploy branch such as `deploy/temp-free-hosting`.
2. Review the current root `.env` usage and prepare a deployment-safe `.env` for the host.
3. Confirm required environment values exist:
   - `SUPABASE_URL`
   - `SUPABASE_SERVICE_ROLE_KEY`
   - `SUPABASE_ANON_KEY`
4. Disable visible PHP errors in the deployed environment:
   - `display_errors=Off`
   - `log_errors=On`
5. Confirm writable directories exist:
   - `storage/document/`
   - `storage/payslips/`
   - `storage/reports/`
6. Restrict public access to dev and operational scripts:
   - `tools/`
   - debug runners
   - ad hoc migration/validation scripts
7. Audit hardcoded `/hris-system` paths and decide one of these approaches:
   - deploy the app under `/hris-system`, or
   - patch the app to use a configurable base path before go-live

## Phase 1 — Hosting Setup

1. Create the temporary hosting account and domain/subdomain.
2. Upload the project files through FTP or File Manager.
3. Upload the full `vendor/` directory from local if Composer cannot run on the host.
4. Place the root `.env` file on the server outside public download exposure if the host allows it.
5. Verify PHP extensions/features required by the app are available:
   - cURL
   - JSON
   - mbstring
   - OpenSSL
   - file uploads
6. Confirm the document root matches the intended deployed path.

## Phase 2 — Configuration

1. Set production-like values in the root `.env` file.
2. Verify the host can reach Supabase over HTTPS.
3. Validate Linux path behavior and file permissions.
4. Confirm sessions, CSRF behavior, and cookie handling work on the public domain.
5. If the host path is not `/hris-system`, patch base-path assumptions before proceeding.

## Phase 3 — Data and Storage Validation

1. Confirm Supabase schema and policies already required by the app are in place.
2. Create or verify at least one test account per role:
   - admin
   - staff
   - employee
   - applicant
3. Test upload and access flows for:
   - profile photos
   - applicant/employee documents
   - payslips
   - generated reports
4. Validate PDF and spreadsheet generation on the host.
5. Verify graceful handling of upload size or timeout failures.

## Phase 4 — Smoke Test (Live URL)

1. Authentication pages load and submit correctly.
2. Public API endpoints that depend on Supabase respond correctly.
3. Role-based access controls are enforced.
4. Dashboard pages load without visible warnings/notices.
5. Payroll, reports, and document previews work end-to-end.
6. SMTP-backed features are either verified or intentionally disabled.
7. Basic mobile responsiveness is checked on core pages.

## Phase 5 — Temporary Operations

1. Add a `DEPLOYMENT_NOTES.md` containing:
   - live URL
   - deployment date
   - hosting provider
   - owner of credentials
   - current base path assumption
   - known broken or disabled features
2. Schedule lightweight backup steps:
   - `.env` backup in secure private storage
   - Supabase export/backup routine as appropriate
   - backup of critical generated/uploaded files if still stored locally
3. Record free-tier constraints so the team expects interruptions or suspensions.

---

## 5) Rollback Plan

If deployment fails:

1. Keep the local XAMPP environment as the fallback demo environment.
2. Switch the demo back to local LAN/tunnel access if public hosting becomes unstable.
3. Restore the last known-good uploaded package.
4. Revert any host-only environment changes and rotate secrets if exposure is suspected.

---

## 6) Main Risks for This Repo

- Free hosts may suspend inactive or resource-heavy applications.
- Generated PDF/XLSX and large uploads may exceed free-tier limits.
- SMTP may be blocked or unreliable.
- Public `tools/` exposure is a real risk if server rules are not added.
- Hardcoded `/hris-system` paths can break assets, storage links, and downloads when deployed at a different base path.
- If `.env` placement is careless, Supabase credentials become a major security issue.

---

## 7) Recommended Next Upgrade Path After Demo

1. Move to a paid PHP host, VPS, or app platform with predictable uptime and logs.
2. Introduce proper environment management and a configurable application base URL.
3. Reduce dependence on local host storage for user files where practical.
4. Add CI/CD with a repeatable deploy process.
5. Formalize monitoring, backups, and secret rotation.

---

## 8) Minimal Go-Live Checklist

- [ ] Public URL loads correctly
- [ ] Root `.env` is present and not publicly exposed
- [ ] Supabase-backed authentication works for all main roles
- [ ] `/hris-system` path assumption is satisfied or patched
- [ ] Upload, preview, and download flows work for critical files
- [ ] PDF/Excel exports work on the live host
- [ ] Error display is disabled in production
- [ ] `tools/` and debug scripts are not publicly accessible
- [ ] Backup/export notes are documented

---

## 9) Decision Summary

For an **immediate temporary deployment**, the best practical choice is still a **PHP-capable free host**, but only with the following expectations:

- This is for demo/testing only.
- Supabase is required.
- Root `.env` handling must be correct.
- Base-path assumptions must be respected.
- Some features such as SMTP or larger generated files may need to be disabled.

This keeps delivery fast for a short demo while preserving a cleaner path to a proper paid deployment later.
