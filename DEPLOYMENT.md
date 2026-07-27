# Deployment guide — Tahsin International

A zero-build static site with a small PHP layer (contact forms + `/admin` CMS).
Runs on any PHP 8.1+ host; written and tested for **cPanel**.

---

## 1. Upload the site

**Recommended (cPanel File Manager):**
1. Zip the **entire project folder** (including `assets/uploads/` and `assets/data/`).
2. cPanel → *File Manager* → `public_html` → **Upload** the zip → **Extract**.
   - For a subdirectory install (`example.com/tahsin`), extract into `public_html/tahsin/`.
     All paths are relative, so nothing else changes.

> **Important:** `assets/uploads/` is **git-ignored** (so private form uploads are never
> committed). If you deploy with `git` alone, your uploaded images/scans will be missing.
> Deploy by **zip or FTP of the whole folder** so `assets/uploads/` and `assets/data/*.json`
> go up with the site.

## 2. Configure

Copy `assets/php/config.sample.php` → `assets/php/config.php` and set:

- `recipients` — where form submissions are emailed.
- `from_email` — **must be an address on your own domain** (create it in cPanel → Email
  Accounts), or `mail()` is dropped as spoofed.
- `admin_user` and a password. Prefer a hash:
  ```
  php -r "echo password_hash('YourStrongPassword', PASSWORD_DEFAULT), PHP_EOL;"
  ```
  Paste the result into `admin_pass_hash` (leave `admin_pass` empty).

`config.php` is git-ignored — never commit live credentials.

## 3. PHP & permissions

- cPanel → *MultiPHP Manager* → **PHP 8.1+**.
- Folders `755`, files `644` (never `777`).
- These must be **writable by PHP** (they are at 755/644 since PHP runs as your user) so the
  admin can save content: `assets/data/` and `assets/uploads/`.
- Upload size: `.user.ini` sets 16 MB / 20 MB. Adjust in cPanel → *MultiPHP INI Editor* if needed.

## 4. HTTPS

cPanel → *SSL/TLS Status* → run **AutoSSL**. The included `.htaccess` then forces HTTPS,
enables gzip + far-future caching for `assets/`, and serves clean (extensionless) URLs.

## 5. The admin panel — `/admin`

Visit `https://yourdomain/admin/` (or `/login.php`) and sign in. From there you manage:

| Section | Controls |
|---|---|
| **News** | Blog posts (rich-text editor + media) → `news.html` |
| **Products** | Product cards by category → `products.html` |
| **Projects** | Completed / ongoing projects → `projects.html` + homepage carousel |
| **Certificates** | Assign scans to each licence → `certificates.html` + homepage |
| **Photos** | Hero, “Who we are”, About & CEO photos |
| **Media** | Upload / manage images & PDFs (used by the pickers above) |

Content is stored as JSON in `assets/data/`; uploads in `assets/uploads/`.

## 6. Post-launch checklist

- [ ] Submit `sitemap.xml` in Google Search Console.
- [ ] Test every form on the live domain (contact, RFQ, investment, career). If the host
      disables `mail()`, switch `contact.php` to SMTP via PHPMailer using a cPanel mailbox.
- [ ] Verify the Organization / LocalBusiness data with Google’s Rich Results Test.
- [ ] Run Lighthouse (target ≥ 90 across all four categories).
- [ ] **Confirm before launch:** BIN, TIN and Trade Licence numbers, and the IRC renewal date
      (source documents conflict — see the flags in `README.md`).

## 7. Third-party logos / photography

The **Memberships & partners** section uses self-contained, brand-styled name + emblem tiles
(DCCI, BGBA, FBCCI) — not the organizations’ official trademarked logos. To display an official
logo or a partner/client logo, obtain permission from the owner, upload it in **Media**, and
swap the tile. The same applies to stock photography: use images you own or have licensed.
