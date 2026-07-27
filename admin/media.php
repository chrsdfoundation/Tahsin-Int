<?php
require __DIR__ . '/_bootstrap.php';
require_login();

if (!is_dir(TI_UPLOAD_DIR)) { @mkdir(TI_UPLOAD_DIR, 0755, true); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // If the whole request exceeded post_max_size, PHP empties $_POST and $_FILES.
    if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        flash('That file is too large for the server (POST limit ' . ini_get('post_max_size')
            . '). Raise upload_max_filesize / post_max_size — see the note below.', 'err');
        header('Location: media.php'); exit;
    }
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
    $r = store_upload($_FILES['file'] ?? null);
    flash($r['ok'] ? ('Uploaded: ' . $r['name']) : $r['error'], $r['ok'] ? 'ok' : 'err');
    header('Location: media.php'); exit;
}

$files = array_values(array_filter(scandir(TI_UPLOAD_DIR), fn($f) => $f[0] !== '.' && $f !== '.htaccess'));
rsort($files);

admin_header('Media');
?>
<h1 class="a-h1">Media library</h1>
<p class="a-lead">Upload images and PDFs, then copy a file's path into a post's <em>Cover image</em> field or body.</p>

<?php
$serverLimit = ini_get('upload_max_filesize');
$appLimitMb  = (int) (TI_MAX_UPLOAD / 1024 / 1024);
?>
<form class="a-upload" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="file" name="file" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf" required>
  <button class="a-btn a-btn--primary" type="submit">Upload</button>
  <span class="a-hint">Up to <?= $appLimitMb ?> MB · JPG, PNG, WEBP, GIF, PDF · server limit <?= h($serverLimit) ?></span>
</form>

<div class="a-note" style="margin-bottom:22px">
  <strong>File size limits.</strong> This server currently accepts uploads up to <strong><?= h($serverLimit) ?></strong>.
  If your image is larger, either shrink it, or raise the limit: the included <code>.user.ini</code> sets
  <code>upload_max_filesize = 16M</code> / <code>post_max_size = 20M</code> (takes effect on most cPanel/PHP-FPM
  hosts within a few minutes). On cPanel you can also use <em>MultiPHP INI Editor</em> to set these values.
</div>

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
