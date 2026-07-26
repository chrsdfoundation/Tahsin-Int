<?php
require __DIR__ . '/_bootstrap.php';
require_login();

$slots = cert_slots();
$certs = load_certs();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { flash('Session expired — please try again.', 'err'); header('Location: certificates.php'); exit; }
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
<p class="a-lead">Assign an uploaded image to each certificate. It then appears as a thumbnail and in the pop-up viewer on the public <a href="../certificates.html" target="_blank" rel="noopener">Certificates</a> page and homepage. Upload files first in <a href="media.php">Media</a>, then paste the path here.</p>

<form method="post" class="a-form">
  <?= csrf_field() ?>
  <table class="a-table">
    <thead><tr><th>Certificate</th><th>Preview</th><th>Image path</th><th>Visible</th></tr></thead>
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
          <input type="text" name="image[<?= h($id) ?>]" value="<?= h($img) ?>" placeholder="assets/uploads/file.jpg" style="min-width:240px">
        </td>
        <td style="text-align:center"><input type="checkbox" name="published[<?= h($id) ?>]" <?= $pub ? 'checked' : '' ?>></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <div class="a-actions"><button class="a-btn a-btn--primary" type="submit">Save certificates</button></div>
</form>
<?php admin_footer();
