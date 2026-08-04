# Image Management Guide for Tahsin International Admin

This guide explains how to upload and manage images (certificates, membership logos, site photos) in the Tahsin International admin panel.

## Quick Start

### Uploading Images

1. Go to **Media** section
2. Click **Choose File** and select your image (JPG, PNG, WEBP, GIF, or PDF)
3. Click **Upload**
4. The file path is automatically copied to your clipboard (click "Copy path" if needed)

### Assigning Images to Certificates

1. Go to **Certificates** section
2. For each certificate you want to add an image to:
   - **Option A (Recommended):** Click the file picker under "Upload new" column and select an image
   - **Option B:** Paste the path from Media (format: `assets/uploads/filename.ext`) into the "Image path" field
3. Make sure the certificate is marked as "Visible" (checkbox)
4. Click **Save certificates**

### Assigning Images to Memberships

1. Go to **Memberships** section
2. Click **+ New** or **Edit** on an existing membership
3. In the "Logo" field, click **Choose...** to pick from uploaded files
4. Or paste the image path directly
5. Check "Published" to make it visible
6. Click **Save**

### Assigning Images to Site Photos (Hero, About, etc.)

1. Go to **Photos** section
2. Each slot (Homepage Hero, About Page, etc.) can have one image
3. Upload or paste the image path
4. Click **Save**

## Troubleshooting

### Images Not Displaying?

1. Go to **⚡ Fix Images** (in the admin navigation)
2. Click **Re-validate now** to check for broken paths
3. If broken paths are found, click **Repair broken paths** to clean them up
4. Re-upload the images and reassign them

### File Upload Errors

- **"File is too large"** → Compress/resize your image before uploading. The limit is 12 MB per file.
- **"Only JPG, PNG, WEBP, GIF or PDF files are allowed"** → Make sure your file has the correct extension
- **"File content does not match its extension"** → The file is corrupted or mislabeled. Try re-saving it.

## File Specifications

| Property | Requirement |
|----------|------------|
| Formats | JPG, PNG, WEBP, GIF, PDF |
| Max Size | 12 MB per file |
| Certificates | Preferably 200×250px or larger (scanned documents) |
| Logos | SVG preferred, or PNG/WEBP at 300×150px minimum |
| Photos | At least 1200px wide for best quality |

## Technical Details

### Image Storage

- All uploaded files are stored in `assets/uploads/`
- Filenames are automatically sanitized and randomized (e.g., `trade-licence-f01b20.jpg`)
- Files are protected from execution via Apache security rules
- Long-term caching is enabled for performance

### Image References

Images are referenced in JSON data files:

- **Certificates:** `assets/data/certificates.json`
- **Memberships:** `assets/data/memberships.json`  
- **Site Photos:** `assets/data/site-images.json`

The front-end JavaScript (`assets/js/main.js`) automatically loads these files and displays images in the correct locations.

### Security

- Only logged-in admins can upload
- File types are validated by both MIME type and extension
- Uploaded files cannot be executed as code
- All paths are validated before being stored

## Best Practices

1. **Keep file sizes small** - Compress images before uploading (use tools like TinyPNG)
2. **Use descriptive names** - Original filenames help you identify images later
3. **Validate regularly** - Run "⚡ Fix Images" monthly to catch broken references
4. **Backup uploads** - The `assets/uploads/` directory contains all media
5. **Test after uploading** - View the public page to confirm images display correctly

## Need Help?

If images aren't displaying after you've uploaded them:

1. Check the browser's Developer Tools (F12) → Network tab for 404 errors
2. Run the image validator to check for broken paths
3. Make sure the certificate/membership/photo is marked as "Published"
4. Clear your browser cache (Ctrl+Shift+Delete) and reload
