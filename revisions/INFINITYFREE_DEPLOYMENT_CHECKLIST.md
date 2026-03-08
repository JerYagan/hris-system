# InfinityFree Deployment Checklist

**Project:** DA HRIS  
**Date:** March 7, 2026  
**Use Case:** Temporary demo deployment on InfinityFree for the current PHP + Supabase codebase.

---
<!-- 7B3SNy1Mm9gHCKM -->

## 1) Fit Assessment

InfinityFree is usable for a short demo of this repo because it can host PHP files and writable directories, but it should be treated as a fragile temporary environment.

This repo currently expects:

- PHP-rendered pages under `pages/`
- Supabase connectivity over HTTPS
- root `.env` loading
- local writable directories under `storage/`
- Composer libraries already present in `vendor/`
- several URLs and storage paths that assume `/hris-system` as the app base path

That means the deployment goal is not "perfect hosting." The goal is "stable enough for a short demo without changing architecture."

---

## 2) Recommended InfinityFree Folder Layout

Use this layout if possible:

- InfinityFree account root
  - `htdocs/`
    - `hris-system/`
      - project files here

Reason:

- The current codebase hardcodes `/hris-system` in several places.
- Deploying the app under `https://your-subdomain.infinityfreeapp.com/hris-system/` reduces the amount of patching required.

If you instead deploy directly at `htdocs/`, expect path issues unless the app is patched first.

---

## 3) Pre-Upload Checklist

- [ ] Confirm local app is working on the branch you want to deploy
- [ ] Confirm `vendor/` exists locally
- [ ] Prepare a production/demo `.env` file
- [ ] Confirm the following keys are present:
  - `SUPABASE_URL`
  - `SUPABASE_SERVICE_ROLE_KEY`
  - `SUPABASE_ANON_KEY`
- [ ] Confirm these directories exist locally:
  - `storage/document/`
  - `storage/payslips/`
  - `storage/reports/`
- [ ] Decide whether SMTP features will be enabled or intentionally disabled
- [ ] Decide whether the live URL will include `/hris-system`

---

## 4) Files to Upload

Upload the full application, including:

- `pages/`
- `assets/`
- `api/`
- `storage/`
- `vendor/`
- root PHP entry files such as `index.html`
- the root `.env` file

Notes:

- Do not rely on Composer running on InfinityFree.
- Upload `vendor/` from your local machine.
- Keep `tools/` out of public reach if possible. If you must upload it, block access with server rules.

---

## 5) InfinityFree Setup Steps

1. Create the InfinityFree account and subdomain.
2. Open the hosting control panel.
3. Use File Manager or FTP to upload the project.
4. Create the `htdocs/hris-system/` folder if you are preserving the current base path.
5. Upload the project into that folder.
6. Verify the following directories are present after upload:
   - `storage/document/`
   - `storage/payslips/`
   - `storage/reports/`
7. Upload the prepared `.env` file.
8. Confirm file permissions are sufficient for PHP to write into `storage/`.

---

## 6) Required Runtime Checks

Before testing business flows, confirm these basics:

- [ ] PHP pages render without a 500 error
- [ ] The host can make outbound HTTPS requests to Supabase
- [ ] `vendor/autoload.php` is present
- [ ] cURL-dependent requests succeed
- [ ] sessions are working
- [ ] file uploads are accepted by the host

If any of these fail, the app will not behave correctly regardless of UI status.

---

## 7) Access Hardening for InfinityFree

Add server rules to reduce exposure of sensitive files and folders.

Minimum goals:

- deny direct web access to `.env`
- deny direct web access to `tools/`
- deny direct web access to ad hoc debug scripts
- prevent directory listing if the host allows that setting

Suggested `.htaccess` rules for the deployed app root:

```apache
Options -Indexes

<Files ".env">
  Require all denied
</Files>

<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteRule ^tools/ - [F,L,NC]
</IfModule>
```

If InfinityFree rejects part of the syntax, adjust to the subset supported by the host and retest immediately.

---

## 8) App-Specific Configuration Notes

### Base Path

The current repo assumes `/hris-system` in multiple places.

Recommended temporary approach:

- deploy under `/hris-system`

Alternative:

- patch the application to use a configurable base path before deployment

### Environment File

The app currently reads a root `.env` file. Do not create a separate `config/env.php` unless you also change the application code.

### Database Layer

For this repo, treat Supabase as required.

Do not plan on switching to InfinityFree MySQL unless you are deliberately rewriting parts of the app.

### Mail

InfinityFree may not be reliable for SMTP-dependent flows.

Recommended demo stance:

- leave email features disabled unless they are explicitly needed and tested

---

## 9) Smoke Test Order

Run these checks in order after upload:

1. Open the public URL.
2. Open the public API path used by the homepage or careers flow.
3. Test login for:
   - admin
   - staff
   - employee
   - applicant
4. Open one dashboard per role.
5. Test one document upload flow.
6. Test one document preview/download flow.
7. Test one payslip download flow.
8. Test one generated report flow.
9. Confirm no visible PHP warnings/notices appear.
10. Confirm blocked paths cannot be opened directly:
   - `.env`
   - `tools/`

---

## 10) Likely InfinityFree Failure Points

Expect these to be the first things that break:

- large file uploads
- PDF generation timeouts
- spreadsheet export timeouts
- SMTP connectivity
- permissions on `storage/`
- path issues when deployed outside `/hris-system`
- 500 errors caused by missing `vendor/` files or missing PHP capabilities

When troubleshooting, check these first before changing application code.

---

## 11) Minimal Go-Live Criteria

Only call the deployment demo-ready if all of these are true:

- [ ] Public URL loads correctly
- [ ] Login works for the intended demo roles
- [ ] Supabase-backed data loads on core pages
- [ ] At least one upload/download flow works
- [ ] At least one report or payslip file can be generated or served
- [ ] `.env` is not publicly accessible
- [ ] `tools/` is not publicly accessible
- [ ] Visible PHP errors are disabled

---

## 12) Recommended Demo Notes

Record these immediately after deployment:

- live URL
- deployment date/time
- branch used
- whether the app is hosted under `/hris-system`
- which roles were tested
- which features were intentionally disabled
- any known InfinityFree-specific limitations encountered

---

## 13) Exit Strategy

Use InfinityFree only as a short-term demo host.

After the demo, either:

1. move to a paid PHP/VPS/PaaS environment, or
2. patch the app for configurable base URL and cleaner storage handling before the next public deployment