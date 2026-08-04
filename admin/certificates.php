<?php
require __DIR__ . '/_bootstrap.php';
require_login();

$slots = cert_slots();
$certs = load_certs();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { flash('Session expired — please try again.', 'err'); header('Location: certificates.php'); exit; }

    // Handle file upload if present
    if (!empty($_FILES['upload']) && $_FILES['upload']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload_id = (string) ($_POST['upload_id'] ?? '');
        if (isset($slots[$upload_id])) {
            $r = store_upload($_FILES['upload']);
            if ($r['ok']) {
                flash('Image uploaded: ' . h($r['name']));
                $_POST['image'][$upload_id] = $r['path'];
            } else {
                flash('Upload failed: ' . $r['error'], 'err');
            }
        }
    }

    $out = [];
    foreach ($slots as $id => $label) {
        $img = trim((string) ($_POST['image'][$id] ?? ''));
        // normalise: strip leading slashes/base, keep a site-relative path
        $img = ltrim($img, '/');
        $img = preg_replace('#^\.\./#', '', $img);
        // basic guard: only allow paths inside assets/
        if ($img !== '' && !preg_match('#^assets/[A-Za-z0-9._/-]+$#', $img)) {
            flash('Ignored an invalid path for "' . h($label) . '". Use a path like assets/uploads/file.jpg', 'warn');
            $img = $certs[$id]['image'] ?? '';
        }
        $out[$id] = [
            'image'     => $img,
            'published' => isset($_POST['published'][$id]),
        ];
    }
    save_certs($out);
    flash('Certificates updated.');
    header('Location: certificates.php'); exit;
}

admin_header('Certificates');
?>
<h1 class="a-h1">Certificate scans</h1>
<p class="a-lead">Upload and assign images for each certificate. Images appear as thumbnails on the public <a href="../certificates.html" target="_blank" rel="noopener">Certificates</a> page and homepage.</p>

<form method="post" enctype="multipart/form-data" class="a-form">
  <?= csrf_field() ?>
  <table class="a-table">
    <thead><tr><th>Certificate</th><th>Preview</th><th>Image path</th><th>Upload new</th><th>Visible</th></tr></thead>
    <tbody>
    <?php foreach ($slots as $id => $label):
        $img = (string) ($certs[$id]['image'] ?? '');
        $pub = ($certs[$id]['published'] ?? true) !== false;
    ?>
      <tr>
        <td><strong><?= h($label) ?></strong><div class="a-slug"><?= h($id) ?></div></td>
        <td>
          <?php if ($img): ?>
            <img src="../<?= h($img) ?>" alt="<?= h($label) ?>" style="width:64px;height:80px;object-fit:cover;border:1px solid var(--border);border-radius:6px">
          <?php else: ?>
            <span class="a-pill a-pill--muted">None</span>
          <?php endif; ?>
        </td>
        <td>
          <input type="text" name="image[<?= h($id) ?>]" value="<?= h($img) ?>" placeholder="assets/uploads/file.jpg" style="min-width:200px">
        </td>
        <td style="white-space:nowrap">
          <input type="file" name="upload" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf" onchange="this.form.upload_id.value='<?= h($id) ?>';this.form.submit();" style="max-width:120px">
          <input type="hidden" name="upload_id" value="">
        </td>
        <td style="text-align:center"><input type="checkbox" name="published[<?= h($id) ?>]" <?= $pub ? 'checked' : '' ?>></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <div class="a-actions"><button class="a-btn a-btn--primary" type="submit">Save certificates</button></div>
</form>

<style>
input[type="file"] { padding: 4px; font-size: 12px; }
.a-table input[type="file"] { max-width: 140px; }
</style>

<?php admin_footer();
