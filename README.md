# Tahsin International — Corporate Website

Production website for **Tahsin International** — Export, Import & Government Supplier, Dhaka, Bangladesh.
First Class Contractor · ABC-category Electrical Contractor · DCCI & BGBA member.

> _A symbol of progress, world-class service._

---

## Tech stack

| Layer | Choice | Why |
|---|---|---|
| Markup | Semantic HTML5 | Accessible landmarks, schema.org ready |
| Styling | Hand-authored CSS, no framework | Zero build, zero CDN dependency, nothing to compile on cPanel |
| Behaviour | Vanilla JavaScript (ES2019, no bundler) | Works from `file://` and from any subdirectory |
| Forms | PHP `mail()` handler | Available on virtually every cPanel plan |
| Fonts | Sora + Inter + Hind Siliguri (Google Fonts) | Self-host into `assets/fonts/` for offline/perf builds |
| Icons | Inline SVG (Lucide geometry, 1.75px stroke) | No icon-font request, no FOUT |

**No build step.** Upload the folder, done.

---

## Brand system

| Token | Hex | Use |
|---|---|---|
| Primary — Navy | `#00008B` | Header, footer, headings, primary buttons |
| Primary Deep | `#000066` | Hover/pressed, dark bands |
| Secondary — Orange | `#FF8C00` | Accent CTA, active nav, stat figures — **once per viewport** |
| Secondary Hover | `#E67E00` | Orange hover |
| Ink | `#0F172A` | Body copy |
| Slate | `#334155` | Secondary copy |
| Muted | `#64748B` | Meta, captions |
| Border | `#D9DEE7` | Card borders, dividers, inputs |
| Surface | `#F1F5F9` | Alternating sections |
| Success | `#15803D` | Active compliance badges |
| Warning | `#B45309` | Renewal-pending badges |
| Error | `#B91C1C` | Validation |

Ratio **60 / 30 / 10** — neutral / navy / orange.

**Type:** Sora 600–700 (display), Inter 400–600 (body/UI, `tabular-nums` for licence numbers), Hind Siliguri 400–700 (বাংলা).
**Radius:** 8px inputs/buttons · 12px cards · 16px panels. **Section padding:** 96px desktop / 56px mobile. **Max width:** 1200px.

---

## Structure

```
.
├── index.html                  # Home
├── about.html                  # Profile · CEO message · vision/mission/values
├── services.html               # Overview of all 11 services
├── services/                   # Six domain detail pages
├── products.html
├── rfq.html
├── projects.html
├── clients.html
├── certificates.html
├── investment.html
├── downloads.html
├── news.html
├── career.html
├── contact.html
├── thank-you.html
├── legal/                      # privacy · terms · cookies
├── 404.html
├── sitemap.xml
├── robots.txt
├── .htaccess                   # compression, caching, HTTPS, clean URLs
└── assets/
    ├── css/main.css
    ├── js/main.js
    ├── images/                 # photography, project & product imagery
    ├── logos/                  # brand marks, favicons, client logos
    ├── docs/                   # certificate scans, catalogues, company profile PDF
    ├── fonts/                  # (optional) self-hosted WOFF2
    └── php/
        ├── contact.php
        └── config.sample.php
```

**All asset paths are relative** (`./assets/...`) — the site runs identically at a domain root or in any subdirectory.

---

## Local development

No tooling required. Either open `index.html` directly, or serve it:

```bash
python3 -m http.server 8000
# or
npx serve .
```

Form handlers need PHP:

```bash
php -S localhost:8000
```

---

## Deployment — cPanel

1. **Configure mail.** Copy `assets/php/config.sample.php` → `assets/php/config.php` and set recipients. `from_email` **must** be an address on your own domain (create it in cPanel → Email Accounts) or mail will be dropped as spoofed.
2. **Upload.** cPanel → *File Manager* → `public_html`. Upload a zip of the repo and *Extract*. Or use FTP/SFTP.
   - For a subdirectory (e.g. `example.com/tahsin`), upload into `public_html/tahsin/` — relative paths mean nothing else changes.
3. **Permissions.** Folders `755`, files `644`. Never `777`.
4. **PHP version.** cPanel → *MultiPHP Manager* → PHP **8.1+**.
5. **SSL.** cPanel → *SSL/TLS Status* → run AutoSSL. The `.htaccess` then forces HTTPS.
6. **Test every form** on the live domain. If `mail()` is disabled by the host, switch `contact.php` to SMTP via PHPMailer using a cPanel mailbox.

### Post-launch

- Submit `sitemap.xml` in Google Search Console.
- Verify the Organization / LocalBusiness structured data with the Rich Results Test.
- Run Lighthouse — target ≥ 90 across all four categories.

---

## Content still required

| Item | Status |
|---|---|
| Hero, office and service photography | ❌ placeholders with photo briefs in place |
| CEO portrait — Md. Motalib Hossain | ❌ |
| Certificate scans (Trade Licence, VAT, IRC, Electrical, DCCI, BGBA) | ❌ |
| Company Profile PDF (lead magnet) | ❌ |
| Product catalogue PDFs, SKUs, category images | ❌ |
| Project records — client, scope, contract year | ❌ carousel is placeholder |
| Client & partner logos (with usage permission) | ❌ |
| বাংলা translations | ❌ EN only in v1; toggle is structured in |
| Legal copy — privacy, terms, cookies | ❌ |

### Data to confirm before launch

- **BIN** appears three ways across source documents — `005401348-0208`, `0054013480208`, `085401348-0208`. Currently using the first. **Confirm.**
- **TIN** appears as `740177532524` and `740177932524`. Certificate No. `154253490627`. **Confirm.**
- **IRC `260326111047523`** was valid to **30 June 2026** and is shown with a "Renewal" badge. Update once renewed.

---

## Contact

Suite 17/7 (Lift-16), Paltan China Town East Tower, 67/1, 68, 70, 70/3 Naya Paltan, Dhaka-1000, Bangladesh
+880 1716 610665 (WhatsApp) · info@tigroup.com.bd

---

© Tahsin International. All rights reserved.
