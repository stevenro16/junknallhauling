# CLAUDE.md — Junk N All Hauling

Guidance for working in this repository (and the hard-won notes for deploying it).

---

## 1. What this is

A marketing website **and** lightweight admin/CRM for **Junk N All Hauling**, a junk-removal,
dumpster-rental, light-demolition and equipment-rental business serving the Inland Empire (CA).

- **Public site:** home, services/pricing, about, reviews, contact (quote request with photo upload),
  and a customer "check status" lookup. Plus a tokenized rental-agreement signing page.
- **Admin portal** (`/admin`): inquiry pipeline, calendar (day / 3-day / 5-day / week, with an embedded
  day-picker for scheduling visits + equipment pickups), analytics, customer search/reports, service &
  equipment catalogs, admin-account management. Custom session auth (not Laravel's `auth` scaffolding).
- **Employee portal** (`/admin/my-schedule`): field staff log in to a day/3-day/week calendar of *only*
  their assigned visits & pickups, open a job sheet, add notes, and advance status / collect an arrival
  signature in the field. Role-gated — employees can't reach the admin CRM.

It was **ported from an earlier Next.js app**, which is why the public JSON API under `/api/*` mirrors
the old Next.js route shape and the Alpine front-end calls it with `fetch()`.

Business identity / vocabulary lives in **`config/business.php`** (name, phone, service areas, service
options, inquiry status flow + labels) — the single source of truth, referenced throughout the views.

---

## 2. Tech stack

| Layer | Choice |
|------|--------|
| PHP | 8.3 |
| Framework | Laravel 13.x |
| Front-end build | Vite 8 + `laravel-vite-plugin` |
| CSS | Tailwind CSS v4 (`@tailwindcss/vite`, config lives in `resources/css/app.css` via `@theme`) |
| JS | Alpine.js 3 (+ `@alpinejs/persist`), Leaflet (admin map) |
| Fonts | Geist / Geist Mono via `laravel-vite-plugin/fonts` (Bunny) |
| DB (dev) | SQLite (`database/database.sqlite`) |
| DB (prod) | MySQL |
| Sessions / cache / queue | **database-backed** (see `.env`) — these tables must exist in prod |
| Payments | Stripe Checkout (`stripe/stripe-php`) on the public pay page; QR via `qrcode-generator` (npm) |
| Dev tooling | Pint (format), PHPUnit 12, Pail (logs), Collision |

---

## 3. Repository layout

```
app/
  Http/Controllers/
    Public/      ContactController, StatusController, RentalAgreementController, Payment  (render Blade)
    Api/         Service, Equipment, Lookup, Quote, Ip, RentalAgreement, Payment         (stateless JSON)
    Admin/       Auth, Dashboard, Inquiry, InquiryApi, Calendar, EmployeeCalendar, FieldView, Customer,
                 EodReport, ServiceCatalog, Equipment, AdminAccount, SiteContent, PaymentLink, Address
  Http/Middleware/
    EnsureAdmin.php           (alias: 'admin')          — session login guard (any role)
    EnsureAdminRole.php       (alias: 'role.admin')     — admin-only gate (employees → my-schedule/403)
    RequirePasswordChange.php (alias: 'admin.password') — forces first-login pw change
  Models/        Admin, Inquiry, InquiryStatusHistory, InquiryComment, RentalAgreement,
                 EquipmentType, ServiceCatalog, SiteContent, PaymentLink, User
config/business.php           Business identity + service/status vocab
routes/
  web.php        Public pages + /admin/* (session-guarded)
  api.php        Public /api/* (stateless, no CSRF) — registered with /api prefix
resources/
  css/app.css    Tailwind v4 theme (brand: charcoal + gold #F8C820/#EAB308) + utilities + scroll-reveal CSS
  js/app.js      Alpine bootstrap + shared helpers (csrfToken, jsonHeaders, appBaseUrl, apiUrl)
  js/components/ forms.js (quote/status/rental), admin.js, map.js (Leaflet), reveal.js (scroll animations)
  views/
    layouts/     public.blade.php, admin.blade.php, bare.blade.php
    partials/    navbar, footer, admin/*
    public/      home, services, about, reviews, contact, status, rental-agreement
    admin/       dashboard, calendar, calendar-embed, my-schedule (+ employee-job), eod-report,
                 login, inquiries/*, change-password
    public/      …, payment (Stripe pay page)
database/
  migrations/    users, cache, jobs (framework) + inquiries, inquiry_status_history,
                 inquiry_comments, admins, equipment_types, service_catalog,
                 rental_agreements, site_content, payment_links + incremental adds:
                 employee role/assignment, service-visit log, pickup date/duration/assignee,
                 urgency, admin active flag, payment_links.stripe_session_id
  seeders/       AdminSeeder, EquipmentTypeSeeder, ServiceCatalogSeeder, DemoInquirySeeder
  sqlite-to-mysql.php   Generator: dumps SQLite -> MySQL .sql for phpMyAdmin import (see §7)
```

Routing wiring + middleware aliases + `trustProxies(*)` + JSON exceptions for `api/*` are in
`bootstrap/app.php`. Health check at `/up`.

---

## 4. Local development

Requirements: PHP 8.3, Composer, Node/npm.

```bash
composer install
cp .env.example .env           # then ensure DB_CONNECTION=sqlite
php artisan key:generate
php artisan migrate --seed     # creates + seeds the SQLite db
npm install
```

Run it (two options):

```bash
# Everything at once (server + queue + logs + vite) via the composer script:
composer dev

# …or individually:
php artisan serve              # http://127.0.0.1:8000
npm run dev                    # Vite dev server / HMR
```

Other:

```bash
npm run build                  # production assets -> public/build (REQUIRED for prod, see §8)
php artisan test               # PHPUnit
./vendor/bin/pint              # format (Laravel Pint)
php artisan tinker
```

---

## 5. Application architecture notes

- **Public API (`routes/api.php`)** is **stateless — no session, no CSRF**. The Alpine front-end POSTs to
  `/api/quote` etc. with `window.jsonHeaders()` (no CSRF token). Exceptions there render as JSON
  (`shouldRenderJsonWhen api/*`).
- **Admin auth is custom**, not Laravel's `auth`. `EnsureAdmin` guards `/admin/*` via session; new accounts
  are forced through a password change (`RequirePasswordChange`) before reaching the app. The admin JSON
  API is under `/admin/api/*` (session + CSRF).
- **Roles.** `admins.role` is `admin` | `employee`. `EnsureAdminRole` (`role.admin`) wraps the admin-only
  routes; employees are redirected to **My Schedule** (or 403 on JSON). New employees default to password
  `model123!` and must set a password + email on first login. Visits/pickups carry
  `assigned_employee_id` / `pickup_assigned_employee_id`; an employee's calendar shows only their own.
- **Field View (`/admin/field`, admin-only).** `FieldViewController` reuses the employee field experience
  (`admin.my-schedule` calendar + `admin.employee-job` job sheet) across **all** scheduled jobs, with
  admin extras: a payment panel (record in person + scan-to-pay QR — see Payments below), a "jump to full
  quote" button, and a manual status dropdown. The two field views share `employee-job.blade.php`,
  parameterized via `$routeBase` / `$adminField` (defaults keep the employee usage unchanged). The job
  sheet captures arrival/departure stamps and a full-screen customer **signature** (`serviceSignature`),
  which marks the job `service_performed`.
- **IDs:** business tables (`inquiries`, `admins`, `equipment_types`, `service_catalog`,
  `rental_agreements`, `inquiry_status_history`) use **string UUID/ULID** primary keys. Framework tables
  (`users`, `jobs`, `failed_jobs`, `migrations`) use auto-increment integers.
- **Inquiry status flow:** the main pipeline (`status_options`) is
  `new → reviewing → quoted → scheduled → service_performed → completed` (+ `cancelled`). Off-path labels
  also exist (`status_labels`): `left_voicemail`, and the equipment-rental track
  `equipment_delivered` / `equipment_picked_up`. Defined in `config/business.php`; status history is
  tracked in `inquiry_status_history`. Quotes also carry an `urgency` flag (`routine` | `urgent`) set on
  the public form.
- **Behind a proxy:** GoDaddy/Cloudflare sit in front, so `trustProxies(at: '*')` is set; `$request->ip()`
  (used by `/api/ip` and rental-agreement signing) resolves the real client IP.

### Payments
Three ways a quote gets marked paid; all converge on the inquiry's `payment_method` + `payment_date`
(and the `payment_links` row's `paid_at`):

1. **Admin records it manually** — the quote edit page's payment section, or the **Field View** payment
   panel ("Mark Paid" with a method: Cash/Check/Card/Venmo/Zelle). Field View posts to
   `admin.field.payment` (`FieldViewController::recordPayment`), which also settles any open link.
2. **Customer pays online** — admin generates a **payment link** (`payment_links`, tokenized) for the
   saved `quoted_price` and sends it (text/email) or shows a **QR** (Field View). The link opens the
   public `/pay/{token}` page (`public.payment` + `paymentForm` in `forms.js`).
3. **Stripe Checkout** (the real gateway) — `Api\PaymentController` drives `/pay/{token}`:
   - `pay()` creates a **Checkout Session** with inline `price_data` (the quote amount in cents — **no
     Stripe product catalog**; every quote is a one-off `unit_amount`) and returns the hosted `checkout_url`.
   - Customer returns to `/pay/{token}?status=success&session_id=…`; `confirm()` retrieves the session and
     records payment if `payment_status==='paid'`. The **webhook** (`POST /api/stripe/webhook`,
     signature-verified, `checkout.session.completed`) is the authoritative backstop. `markPaid()` is
     idempotent (SQL `whereNull('paid_at')` one-time guard), so confirm + webhook can't double-record.
   - **Config:** `STRIPE_KEY` / `STRIPE_SECRET` / `STRIPE_WEBHOOK_SECRET` in `.env` → `config/services.php`
     `stripe`. **If `STRIPE_SECRET` is empty, the pay page falls back to a non-charging placeholder** (so
     dev/tests run without keys). Prod webhook endpoint:
     `https://rapidinsightdesigns.com/junknallhauling/api/stripe/webhook`.
   - **Deploying Stripe needs `composer install`** on the server (the `stripe/stripe-php` SDK is new and
     `vendor/` is gitignored) plus the `payment_links.stripe_session_id` migration.

---

## 6. Front-end conventions

### Subfolder-safe URLs (CRITICAL)
The app is deployed under a **subfolder** in production (`/junknallhauling`), so **never hard-code
root-absolute paths** (`/images/...`, `/api/...`, `/admin/...`). They resolve against the domain root and
404.

- **Blade:** use `asset('images/x.jpg')`, `url('/...')`, `route('name')` — they include the base path
  automatically. (`asset()` uses the request root, which includes the subfolder.)
- **JS:** the base URL is exposed via `<meta name="app-base-url" content="{{ url('/') }}">` (in all three
  layouts) and read in `app.js` as `window.appBaseUrl`. Build URLs with the helper:
  ```js
  fetch(window.apiUrl('/api/services'))         // -> https://host/junknallhauling/api/services
  `<a href="${window.appBaseUrl}/admin/inquiries/${id}">`
  ```
  Do **not** write `fetch('/api/...')`.

### Tailwind v4
- No `tailwind.config.js`. Theme tokens are declared in `resources/css/app.css` under `@theme`, and reusable
  classes via `@utility` (`btn-primary`, `btn-outline`, `card`, `service-card`, `container-wide`,
  `section-label`, …). Brand palette: **charcoal** (`#1C1C1C`/`charcoal-*`) + **gold** (`#F8C820`,
  `#EAB308` — exposed as the `orange-*` scale + `brand-yellow`/`brand-gold`).

### Scroll-reveal animations
- Add `data-reveal` (optionally `="up|down|left|right|scale"`) to any element to fade/slide it in on scroll;
  `data-reveal-delay="120"` (ms) staggers grouped items. Implemented in `resources/js/components/reveal.js`
  (IntersectionObserver) + the CSS block in `app.css`. Fully disabled under `prefers-reduced-motion`, and
  shows content immediately if `IntersectionObserver` is unavailable (never hides content).

### Alpine
- Components are registered in `resources/js/components/*` and consumed via `x-data="quoteForm()"`,
  `statusLookup()`, `adminShell({...})`, etc. Shared helpers (`window.csrfToken`, `jsonHeaders`,
  `appBaseUrl`, `apiUrl`) live at the top of `app.js`.

---

## 7. Database: SQLite (dev) → MySQL (prod)

Dev uses SQLite (`database/database.sqlite`, **gitignored** — contains real/seed data). Production uses
MySQL.

To move data to production, regenerate the importable dump and load it via phpMyAdmin:

```bash
php database/sqlite-to-mysql.php          # writes database/sqlite-to-mysql.sql
```

- The generator introspects the live SQLite schema and emits MySQL-compatible DDL + INSERTs
  (utf8mb4, InnoDB, FK-checks toggled, `DROP TABLE IF EXISTS` so re-import is clean).
- Type mapping: `varchar→VARCHAR(255)`, `text→LONGTEXT` (so base64 photos don't truncate),
  `tinyint(1)→TINYINT(1)`, `numeric→DECIMAL(15,2)`, integer PKs → `BIGINT UNSIGNED AUTO_INCREMENT`.
- **Transient tables** (`cache`, `cache_locks`, `sessions`, `jobs`, `job_batches`, `failed_jobs`,
  `password_reset_tokens`) get schema only, no rows.
- ⚠️ **The generated `database/sqlite-to-mysql.sql` is gitignored** — it contains customer PII + the admin
  password hash. Never commit or share it publicly. Import over HTTPS only.
- The dump includes the `migrations` table rows, so `php artisan migrate` on prod reports nothing pending.

Sessions/cache/queue are DB-backed, so their tables must exist in prod (they're created by migrations / the
dump).

---

## 8. Production deployment (GoDaddy cPanel shared hosting)

> Read §9 before touching anything — the layout here is unusual and bit us hard.

### Environment / paths (current)
> Real host IP, SSH user, and cPanel account name are **redacted** from this public repo. Replace
> `<server-ip>` and `<cpanel-user>` below with the actual values from your private deployment notes /
> password manager.

- **Host:** GoDaddy cPanel, Linux + Apache, PHP 8.3. SSH: `ssh -i <key> <cpanel-user>@<server-ip>`
  (account `<cpanel-user>`, home `/home/<cpanel-user>`).
- **App / git repo:** `/home/<cpanel-user>/repositories/junknallhauling`
- **Web docroot for `rapidinsightdesigns.com`:** `/home/<cpanel-user>/public_html` ← **the TOP-LEVEL one**
  (NOT `/home/<cpanel-user>/rapidinsightdesigns/public_html`). This docroot is itself a Laravel front
  controller serving the `rfc` app; other apps are mounted as symlinks beside it (e.g. `datatel`).
- **Live URL:** `https://rapidinsightdesigns.com/junknallhauling`
- **Mount (symlink):**
  `/home/<cpanel-user>/public_html/junknallhauling → /home/<cpanel-user>/repositories/junknallhauling/public`

### There is no Node on the server
`npm`/`node` are not installed on the host, so **`public/build/` is committed to git**
(the one Laravel-default ignore we deliberately removed). Build locally (`npm run build`),
commit the result, and the server gets the assets on `git pull` — no manual upload.

### First-time setup (the confirmed-working recipe)
```bash
# 0) get code on the server (git clone into ~/repositories/junknallhauling), then:
cd /home/<cpanel-user>/repositories/junknallhauling

# 1) PHP deps (vendor/ is gitignored)
composer install --no-dev --optimize-autoloader     # if composer unavailable, upload vendor/ via FTP

# 2) env: copy/create .env, then:
php artisan key:generate --force                    # --force because APP_ENV=production
#    set in .env:  APP_ENV=production  APP_DEBUG=false
#                  APP_URL=https://rapidinsightdesigns.com/junknallhauling
#                  DB_CONNECTION=mysql + real MySQL DB/USER/PASS (see §9)

# 3) database: import database/sqlite-to-mysql.sql via phpMyAdmin (see §7)

# 4) storage symlink + writable dirs
php artisan storage:link
chmod -R 775 storage bootstrap/cache

# 5) >>> THE PERMISSION FIX <<< let Apache (user 'nobody') traverse into the repo
chmod 711 /home/<cpanel-user>/repositories/junknallhauling

# 6) mount the app's public/ into the web docroot
ln -s /home/<cpanel-user>/repositories/junknallhauling/public /home/<cpanel-user>/public_html/junknallhauling

# 7) caches
php artisan config:clear   # (or config:cache once .env is final)
```

Front-end assets are **committed** (`public/build/`), so `git pull` delivers them — just
remember to `npm run build` locally and commit before pushing when JS/CSS/Blade-asset
refs change.

### Redeploy (code/asset change)
1. If JS/CSS/Blade-asset refs changed: `npm run build` **locally** and commit `public/build/`.
2. `git pull` on the server (brings code **and** the built assets).
3. On the server:
   ```bash
   cd /home/<cpanel-user>/repositories/junknallhauling
   php artisan view:clear && php artisan config:clear && php artisan route:clear
   ```
4. Hard-refresh the browser (Vite output filenames are hashed, so the manifest points to the new ones).

---

## 9. Deployment gotchas / lessons learned

These are the things that cost real time. Check them first when a deploy "doesn't work."

1. **Repo directory must be traversable by Apache (the #1 cause of 404s).**
   The repo root was `drwx------` (700). Apache serves static files / evaluates `-d`/`-f` as user
   **`nobody`**, which cannot traverse a 700 dir — so requests fell through to the parent app and 404'd.
   Fix: `chmod 711 /home/<cpanel-user>/repositories/junknallhauling`. (Compare a working app like `datatel`
   with `ls -ld`.) Diagnose with: `curl -i https://rapidinsightdesigns.com/junknallhauling/<a-real-static-file>`
   — a Laravel/PHP 404 for a file that exists ⇒ the request never reached the folder.

2. **Know the REAL docroot.** `rapidinsightdesigns.com` serves from the **top-level** `~/public_html`, not
   `~/rapidinsightdesigns/public_html` (which also exists and is a red herring). `/etc/userdatadomains`
   was not readable; the reliable tell was that the known-good `datatel` symlink lives in `~/public_html`
   and loads. Put the junknallhauling symlink in the **same** docroot as the working apps.

3. **Symlink + `+FollowSymLinks`.** The docroot `.htaccess` must allow symlink following (the top-level
   `~/public_html/.htaccess` already has `Options +FollowSymLinks`). The app mounts via a symlink to its
   `public/` dir — mirror exactly how `datatel` is set up.

4. **No passthrough rule needed in the docroot.** Because the symlinked dir is traversable (gotcha #1),
   the docroot front controller's `RewriteCond %{REQUEST_FILENAME} !-d` is false for `/junknallhauling/…`,
   so it skips and serves the subfolder. (An earlier `RewriteRule ^junknallhauling … - [L]` was added to
   `~/rapidinsightdesigns/public_html/.htaccess` — that's the wrong/unused docroot; it's harmless and can
   be removed.)

5. **`APP_KEY` must be set** or every page 500s (`MissingAppKeyException`).
   `php artisan key:generate --force` (prod needs `--force`), then `php artisan config:clear`.

6. **Subfolder paths** — see §6. Symptom: site loads but images/logo 404 and the quote form / status
   lookup silently fail. Cause: hard-coded `/images/…` or `fetch('/api/…')`. Use `asset()` / `url()` /
   `window.apiUrl()`.

7. **Database user privileges.** A `SQLSTATE[HY000] [1044] Access denied for user '…' to database
   'junknallhauling'` means the `.env` DB user has no grant on that DB (we'd copied another app's `.env`).
   In cPanel → MySQL Databases, attach a user to the junknallhauling DB with ALL PRIVILEGES and set
   `DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD`, then `php artisan config:clear` and verify with
   `php artisan migrate:status`. Note cPanel usually **prefixes** DB/user names (e.g. `acct_dbname`).

8. **`APP_DEBUG=false` in production** once things work (it exposes stack traces + config publicly), then
   `php artisan config:clear`.

9. **Config cache vs `.env`.** `php artisan config:cache` bakes in `.env` values; if you later edit `.env`,
   you must `config:clear`/re-cache or the old values persist (a sneaky source of "wrong APP_URL").

10. **Static `test.txt` trick** for isolating Apache vs Laravel issues: drop a file in `public/`, `curl` it.
    200 = docroot/symlink/perms fine (problem is app-level); 404 = Apache isn't serving the folder
    (perms/docroot/symlink).

11. **The GoDaddy WAF turns oversized responses into a 404 (the multi-hour bug).** GoDaddy runs
    **ModSecurity**, and it only inspects responses up to ~512 KB (`SecResponseBodyLimit`); anything
    **larger is rejected** and served as the docroot app's 404 page (old-Laravel "404 | Not Found",
    *no* `junknallhauling-session` cookie). Symptom: **certain records 404 on the field sheet / report,
    others load** — because pages that **inline base64 images** (customer photos, field photos,
    signatures) balloon past the limit only when that record has big photos. It is **not** the DB, the
    route, or a cache. **Never inline `data:…;base64,…` images in a server-rendered page.** Serve stored
    images as files via `route('admin.job-image', [$id, $kind, $index])`
    (`EmployeeCalendarController::jobImage`, kinds: `photos|arrival|departure|signature|legacy`) so the
    HTML stays small; image responses (`image/*`) aren't subject to the text-response limit. Diagnose by
    logging `strlen(view(...)->render())` — if the app returns 200 with a >0.5 MB body but the browser
    sees 404, it's this. (Also: the WAF's **request** rules false-positive on some admin requests; the
    docroot `.htaccess` disables OWASP CRS XSS rules `941100-941350` for the sibling apps, and
    `public/.htaccess` mirrors that with a broad `SecRuleRemoveById "920000-959999"` — note this host
    **only honors `SecRuleRemoveById`**; `SecRuleEngine`/`<If>` in `.htaccess` returns a **405**.)

---

## 10. Quick command reference

```bash
# Local
composer dev                 # server + queue + logs + vite
php artisan migrate --seed
npm run build
php artisan test
./vendor/bin/pint

# DB export for prod
php database/sqlite-to-mysql.php       # -> database/sqlite-to-mysql.sql (gitignored, PII)

# On the server
php artisan view:clear && php artisan config:clear && php artisan route:clear
php artisan migrate:status             # verify DB connection/creds
chmod 711 ~/repositories/junknallhauling   # if a fresh repo 404s
```

---

## 11. Conventions for changes

- Match the surrounding style; run **Pint** before considering PHP changes done.
- **Never inline base64 images in a server-rendered page.** Render stored photos/signatures via
  `route('admin.job-image', [$id, $kind, $index])`, not `<img src="data:…;base64,…">`. Inlined images
  bloat the HTML past the host WAF's ~512 KB response limit, which 404s the whole page (see §9 #11).
- Any **new image / asset / endpoint reference** must be subfolder-safe (§6) or it will break in production.
- Touching JS/CSS/asset-referencing Blade ⇒ remember it needs a **local `npm run build` + upload of
  `public/build/`** to take effect in prod (no Node on the server).
- Keep secrets out of git: `.env`, `database/*.sqlite`, and `database/sqlite-to-mysql.sql` are gitignored —
  keep it that way (the repo is public on GitHub: `stevenro16/junknallhauling`).
```
