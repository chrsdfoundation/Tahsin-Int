# Media Management System — Complete Guide

This guide covers uploading, storing, and managing media files for Tahsin International.

## Quick Start

### Upload Files
1. Go to **Media** page
2. Click **Choose File** 
3. Select JPG, PNG, WEBP, GIF, or PDF (max 12 MB)
4. Click **Upload**
5. Copy the path that appears (automatically in clipboard)

### Use Uploaded Files

**For Certificates:**
- Go to **Certificates** page
- Under each certificate, click file picker next to "Upload new"
- Select image from uploads, or paste path like `assets/uploads/filename.jpg`
- Click **Save certificates**

**For Memberships:**
- Go to **Memberships** page
- Edit each membership
- In "Logo" field, click **Choose** or paste path
- Click **Save**

**For Site Photos:**
- Go to **Photos** page
- Upload or paste path for Homepage Hero, About page, etc.
- Click **Save**

## File Storage

- **Location:** `assets/uploads/` (at site root)
- **Access:** Files are automatically made readable but not executable
- **Security:** `.htaccess` prevents PHP code execution
- **Backup:** All uploads are in `assets/uploads/` — back this up regularly

## Supported Formats

| Format | Extension | Use Case |
|--------|-----------|----------|
| JPEG | `.jpg` `.jpeg` | Photographs, certificates |
| PNG | `.png` | Graphics, logos, transparent images |
| WebP | `.webp` | Modern web format, smaller file size |
| GIF | `.gif` | Simple graphics, animations |
| PDF | `.pdf` | Documents, scanned pages |

## File Size Limits

- **Per file:** 12 MB
- **Server limit:** May be 16 MB (see .user.ini)
- **Recommendation:** Compress images before uploading
  - Use TinyPNG for JPG/PNG
  - Use TinyWebP for WebP conversion
  - Keep photos under 500 KB when possible

## Troubleshooting

### "File is too large"
- Compress image before uploading (TinyPNG, TinyJPG)
- Check .user.ini for `upload_max_filesize = 16M` (requires server restart)

### "Invalid file type"
- Check file extension matches actual format
- Re-save file with correct extension in image editor
- Convert to supported format (JPG, PNG, WebP, GIF, PDF)

### "File content does not match extension"
- File is corrupted or mislabeled
- Re-save the file in an image editor
- Try uploading the original file again

### Images not displaying on site
1. Go to **Diagnostics** page (in admin nav)
2. Check status — red means action needed
3. Go to **Setup Uploads** page if directory has issues
4. Go to **Fix Images** to check for broken paths

### Uploads fail silently
1. Check **Diagnostics** page for write permission errors
2. Run **Setup Uploads** to repair directory
3. Check browser console (F12) for network errors
4. If server is cPanel, check MultiPHP INI Editor for upload limits

## Data Files

Images are stored in JSON configuration files:

- **Certificates:** `assets/data/certificates.json`
- **Memberships:** `assets/data/memberships.json`
- **Site Photos:** `assets/data/site-images.json`

**Example path in JSON:**
```json
{
  "certificates": {
    "trade-licence": {
      "image": "assets/uploads/trade-licence-f01b20.jpg",
      "published": true
    }
  }
}
```

## System Tools

### Media Page
- Upload files
- Copy paths for use in forms
- Delete unused files
- View file list

### Diagnostics Page (🔍 Diagnostics)
- Check upload system health
- Verify directory permissions
- Test write capability
- See what's working/broken

### Setup Uploads Page
- Configure directory after server migration
- Fix permission issues
- Create security files
- Test the whole system

### Fix Images Page (⚡ Fix)
- Find broken image paths
- Auto-repair missing references
- Validate all data files
- Remove orphaned paths

## Security

### Executed automatically:
✓ **Filename sanitization** — removes special characters
✓ **MIME type validation** — checks actual file content, not just extension
✓ **Execution prevention** — .htaccess blocks PHP/scripts in uploads folder
✓ **Admin-only access** — you must be logged in to upload
✓ **CSRF protection** — prevents cross-site upload attacks

### Best practices:
- ✓ Keep admin password strong
- ✓ Don't download files from untrusted sources before uploading
- ✓ Scan images with malware detector before uploading (paranoid but safe)
- ✓ Regularly check **Diagnostics** page for security issues

## Performance Tips

1. **Compress before uploading**
   - JPG: TinyPNG or TinyJPG
   - PNG: Use PNG Crush
   - WebP: Use TinyWebP (smallest file size)

2. **Use WebP for modern browsers**
   - 25-30% smaller than JPG
   - Better quality at same file size
   - Supported by all modern browsers

3. **Resize large images**
   - Homepage hero: 1200px wide minimum
   - Certificate scans: 800px wide
   - Logos: 300px wide (preserve aspect ratio)

4. **Batch optimize before uploading**
   - Use ImageMagick or GIMP to batch convert
   - Saves time and disk space

## Maintenance Schedule

- **Weekly:** Check **Diagnostics** page (takes 10 seconds)
- **Monthly:** Check **Fix Images** page for broken paths
- **Quarterly:** Review what's in `assets/uploads/` — delete unused files
- **Yearly:** Back up `assets/uploads/` folder to external storage

## Common Tasks

### Add a new certificate image
1. Media → Upload image
2. Copy the path shown
3. Certificates → Edit "Trade Licence"
4. Paste path in "Image path" field
5. Check "Visible" checkbox
6. Click "Save certificates"

### Add a membership logo
1. Media → Upload logo
2. Copy the path
3. Memberships → Click "+" New
4. Enter name, acronym
5. Click "Choose" in Logo field
6. Paste path or select from list
7. Check "Published"
8. Click "Save"

### Update homepage photo
1. Media → Upload image
2. Copy path
3. Photos → Paste in "Homepage hero" or "About page" field
4. Click "Save"

## Contact Admin

If you see a red error in **Diagnostics** or uploads fail:
1. Note the exact error message
2. Check the error in **Diagnostics** page
3. Try running **Setup Uploads** to repair
4. If still broken, contact server administrator with error message

## Technical Details

**Upload flow:**
```
User selects file (media.php)
     ↓
Browser sends to server
     ↓
PHP validates MIME type (store_upload function)
     ↓
PHP sanitizes filename
     ↓
PHP moves to assets/uploads/
     ↓
Path stored in JSON (certificates.json, etc)
     ↓
Front-end reads JSON and displays image
```

**Image loading:**
```
Browser loads HTML page
     ↓
JavaScript reads data-assets-base (e.g., "./")
     ↓
JavaScript fetches assets/data/certificates.json
     ↓
For each image in JSON:
  - Construct URL: ./assets/uploads/filename.jpg
  - Create <img> tag with that src
  - Browser loads image
```

**Caching:**
- Images cached by browser (long TTL)
- JSON files cached less aggressively (check on each page load)
- If new image doesn't appear: clear browser cache (Ctrl+Shift+Delete)

## Support

**Admin panel:**
- Media page — upload and manage files
- Diagnostics page — check system health
- Setup Uploads — fix configuration issues
- Fix Images — repair broken paths

**Documentation:**
- This file — complete guide
- README-IMAGE-MANAGEMENT.md — detailed guide (similar content)

**No direct email support** — the system is designed to self-diagnose and self-repair.
