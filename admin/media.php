<?php
require __DIR__ . '/_bootstrap.php';
require_login();

$allowed = explode(',', TI_ALLOWED_EXT);
$allowedMime = [
    'image/jpeg' => 'jpg', 'image/pjpeg' => 'jpg',
    'image/png' => 'png', 'image/webp' => 'webp',
    'image/gif' => 'gif', 'application/pdf' => 'pdf',
];

if (!is_dir(TI_UPLOAD_DIR)) { @mkdir(TI_UPLOAD_DIR, 0755, true); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { flash('Session expired — please try again.', 'err'); header('Location: media.php'); exit; }

    // Delete
    if (($_POST['do'] ?? '') === 'delete') {
        $name = basename((string) ($_POST['file'] ?? ''));
        $path = TI_UPLOAD_DIR . '/' . $name;
        if ($name !== '' && is_file($path) && strpos(realpath($path) ?: '', realpath(TI_UPLOAD_DIR)) === 0) {
            @unlink($path);
            flash('File deleted.');
        } else {
            flash('File not found.', 'err');
        }
        header('Location: media.php'); exit;
    }

    // Upload
    $f = $_FILES['file'] ?? null;
    if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
        flash('Please choose a file.', 'err');
    } elseif ($f['error'] !== UPLOAD_ERR_OK) {
        flash('Upload failed (error ' . (int) $f['error'] . ').', 'err');
    } elseif ($f['size'] > TI_MAX_UPLOAD) {
        flash('File is too large (max 5 MB).', 'err');
    } else {
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($f['tmp_name']);
        if (!in_array($ext, $allowed, true) || !isset($allowedMime[$mime])) {
            flash('Only JPG, PNG, WEBP, GIF or PDF files are allowed.', 'err');
        } elseif ($allowedMime[$mime] !== ($ext === 'jpeg' ? 'jpg' : $ext)) {
            flash('File content does not match its extension.', 'err');
        } else {
            $base = strtolower(pathinfo($f['name'], PATHINFO_FILENAME));
            $base = preg_replace('/[^a-z0-9._-]+/', '-', $base);
            $base = trim((string) $base, '-.') ?: 'file';
            $base = substr($base, 0, 60);
            $final = $base . '-' . bin2hex(random_bytes(3)) . '.' . $allowedMime[$mime];
            if (move_uploaded_file($f['tmp_name'], TI_UPLOAD_DIR . '/' . $final)) {
                @chmod(TI_UPLOAD_DIR . '/' . $final, 0644);
                flash('Uploaded: ' . $final);
            } else {
                flash('Could not save the file.', 'err');
            }
        }
    }
    header('Location: media.php'); exit;
}

$files = array_values(array_filter(scandir(TI_UPLOAD_DIR), fn($f) => $f[0] !== '.' && $f !== '.htaccess'));
rsort($files);

admin_header('Media');
?>
<h1 class="a-h1">Media library</h1>
<p class="a-lead">Upload images and PDFs, then copy a file's path into a post's <em>Cover image</em> field or body.</p>

<form class="a-upload" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="file" name="file" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf" required>
  <button class="a-btn a-btn--primary" type="submit">Upload</button>
  <span class="a-hint">Max 5 MB · JPG, PNG, WEBP, GIF, PDF</span>
</form>

<?php if (!$files): ?>
  <div class="a-empty">No files yet. Upload your first image above.</div>
<?php else: ?>
  <div class="a-media">
    <?php foreach ($files as $file):
        $path = TI_UPLOAD_URL . $file;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
    ?>
      <div class="a-mcard">
        <div class="a-mthumb">
          <?php if ($isImg): ?>
            <img src="../<?= h($path) ?>" alt="<?= h($file) ?>" loading="lazy">
          <?php else: ?>
            <div class="a-mdoc"><?= strtoupper(h($ext)) ?></div>
          <?php endif; ?>
        </div>
        <div class="a-mname" title="<?= h($file) ?>"><?= h($file) ?></div>
        <div class="a-mpath"><code><?= h($path) ?></code></div>
        <div class="a-mactions">
          <button type="button" class="a-link" data-copy="<?= h($path) ?>">Copy path</button>
          <a class="a-link" href="../<?= h($path) ?>" target="_blank" rel="noopener">Open</a>
          <form method="post" class="a-inline" onsubmit="return confirm('Delete this file?');">
            <?= csrf_field() ?>
            <input type="hidden" name="do" value="delete">
            <input type="hidden" name="file" value="<?= h($file) ?>">
            <button class="a-link a-link--danger" type="submit">Delete</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<script>
document.addEventListener('click', function (e) {
  var b = e.target.closest('[data-copy]');
  if (!b) return;
  var v = b.getAttribute('data-copy');
  (navigator.clipboard ? navigator.clipboard.writeText(v) : Promise.reject()).then(
    function () { b.textContent = 'Copied!'; setTimeout(function(){ b.textContent = 'Copy path'; }, 1500); },
    function () { window.prompt('Copy this path:', v); }
  );
});
</script>
<?php admin_footer();
