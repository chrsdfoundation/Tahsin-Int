# Deployment guide — Tahsin International

A zero-build static site with a small PHP layer (contact forms + `/admin` CMS).
Runs on any PHP 8.1+ host; written and tested for **cPanel**.

---

## 0. Automated Deployment via GitHub Actions (Recommended)

If you want changes pushed to `main` to automatically deploy to the live site, set up GitHub Actions:

### Setup (one-time)

1. **Gather cPanel SFTP credentials:**
   - cPanel username (often your domain account name)
   - cPanel password
   - SFTP hostname (ask your host, e.g., `sftp.tigroup.com.bd` or an IP)
   - SFTP port (usually 22)
   - Target path (usually `/home/{username}/public_html` for the main domain)

2. **Add GitHub Secrets** (GitHub repo → Settings → Secrets and variables → Actions):
   - `CPANEL_HOST` — SFTP server hostname
   - `CPANEL_USER` — cPanel/FTP username
   - `CPANEL_PASSWORD` — cPanel password
   - `CPANEL_PORT` — SFTP port (default: 22)
   - `DEPLOY_PATH` — Remote directory (e.g., `/home/username/public_html`)

3. **Test SFTP connectivity** (before relying on CI/CD):
   ```bash
   sftp -P 22 username@sftp.tigroup.com.bd
   ```
   Verify you can read and write files.

### How it works

- Every push to `main` triggers the workflow (`.github/workflows/deploy.yml`)
- The workflow:
  1. Checks out your code (including git-ignored files like `assets/uploads/`)
  2. Deploys the entire folder to cPanel via SFTP
  3. Verifies the site is live with an HTTP check
  4. Reports success/failure in the GitHub Actions tab
- Changes appear on the live site within 1–2 minutes

### Rollback

If a deployment breaks the site:
```bash
git revert HEAD  # Reverts the broken commit
git push         # Triggers deployment of the previous version
```

### Troubleshooting

| Problem | Solution |
|---------|----------|
| **Workflow not triggering** | Check that the branch is `main` and the commit is pushed to origin |
| **Deploy fails with auth error** | Verify `CPANEL_*` secrets are correct; test SFTP manually |
| **Site shows old content** | Check the Actions tab for workflow logs; verify SFTP upload completed |
| **Uploaded files missing after deploy** | They should persist on the server (not deleted by SFTP). Check manually via FTP. |
| **Admin login fails after deploy** | Ensure `assets/php/config.php` exists on the server (set up once, manually) |

### Disabling automated deployment

If you want to go back to manual deployment, simply don't push to `main` (use feature branches), or disable the workflow in GitHub (Settings → Actions → Disable this workflow).

---

## 1. Upload the site (Manual Deployment)

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
