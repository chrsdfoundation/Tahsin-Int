<?php
require __DIR__ . '/_bootstrap.php';
require_login();

$slots  = photo_slots();
$images = load_site_images();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { flash('Session expired — please try again.', 'err'); header('Location: photos.php'); exit; }
    $out = [];
    foreach ($slots as $id => $label) {
        $img = trim((string) ($_POST['image'][$id] ?? ''));
        $img = ltrim($img, '/');
        $img = preg_replace('#^\.\./#', '', $img);
        if ($img !== '' && !preg_match('#^assets/[A-Za-z0-9._/-]+$#', $img)) {
            flash('Ignored an invalid path for "' . h($label) . '". Use a path like assets/uploads/file.jpg', 'warn');
            $img = $images[$id]['image'] ?? '';
        }
        $out[$id] = [
            'image' => $img,
            'alt'   => trim((string) ($_POST['alt'][$id] ?? '')),
        ];
    }
    save_site_images($out);
    flash('Photos updated.');
    header('Location: photos.php'); exit;
}

admin_header('Photos');
?>
<h1 class="a-h1">Site photos</h1>
<p class="a-lead">Assign an uploaded image to each photo area. It replaces the “Photo brief” placeholder on the live site. Upload files first in <a href="media.php">Media</a>, then paste the path here. Always add descriptive <em>alt text</em> for accessibility.</p>

<form method="post" class="a-form">
  <?= csrf_field() ?>
  <?php foreach ($slots as $id => $label):
      $img = (string) ($images[$id]['image'] ?? '');
      $alt = (string) ($images[$id]['alt'] ?? '');
  ?>
    <div style="display:grid;grid-template-columns:120px 1fr;gap:16px;align-items:start;padding-bottom:18px;border-bottom:1px solid var(--border)">
      <div>
        <strong style="font-size:14px"><?= h($label) ?></strong>
        <div class="a-slug"><?= h($id) ?></div>
        <div style="margin-top:8px">
          <?php if ($img): ?>
            <img src="../<?= h($img) ?>" alt="" style="width:110px;height:82px;object-fit:cover;border:1px solid var(--border);border-radius:8px">
          <?php else: ?>
            <span class="a-pill a-pill--muted">None</span>
          <?php endif; ?>
        </div>
      </div>
      <div style="display:flex;flex-direction:column;gap:10px">
        <label class="a-field">Image path
          <input type="text" name="image[<?= h($id) ?>]" value="<?= h($img) ?>" placeholder="assets/uploads/file.jpg">
        </label>
        <label class="a-field">Alt text <span class="a-hint">(describe the image)</span>
          <input type="text" name="alt[<?= h($id) ?>]" value="<?= h($alt) ?>" placeholder="e.g. Tahsin International office in Naya Paltan">
        </label>
      </div>
    </div>
  <?php endforeach; ?>
  <div class="a-actions"><button class="a-btn a-btn--primary" type="submit">Save photos</button></div>
</form>
<?php admin_footer();
