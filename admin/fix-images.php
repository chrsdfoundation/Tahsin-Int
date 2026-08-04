<?php
/**
 * Image path validator and fixer
 * Run this to check and fix broken image references across all JSON data files
 * Only logged-in admins can access this
 */
require __DIR__ . '/_bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { flash('Session expired — please try again.', 'err'); header('Location: fix-images.php'); exit; }

    // Fix certificates
    $certs = load_certs();
    $fixed = 0;
    foreach ($certs as $id => &$cert) {
        $img = $cert['image'] ?? '';
        if ($img === '') continue;

        // Validate path format
        $img = ltrim($img, '/');
        $img = preg_replace('#^\.\./#', '', $img);
        if (!preg_match('#^assets/[A-Za-z0-9._/-]+$#', $img)) {
            unset($cert['image']);
            $fixed++;
            continue;
        }

        // Check if file exists
        $filepath = TI_ROOT . '/' . $img;
        if (!is_file($filepath)) {
            unset($cert['image']);
            $fixed++;
        } else {
            $cert['image'] = $img;
        }
    }
    if ($fixed > 0) {
        save_certs($certs);
        flash("Fixed $fixed broken certificate image paths.");
    }

    // Fix memberships
    $members = load_list(TI_MEMBERS_FILE, 'items');
    $fixed = 0;
    foreach ($members as &$member) {
        $img = $member['logo'] ?? '';
        if ($img === '') continue;

        // Validate path format
        $img = ltrim($img, '/');
        $img = preg_replace('#^\.\./#', '', $img);
        if (!preg_match('#^assets/[A-Za-z0-9._/-]+$#', $img)) {
            unset($member['logo']);
            $fixed++;
            continue;
        }

        // Check if file exists
        $filepath = TI_ROOT . '/' . $img;
        if (!is_file($filepath)) {
            unset($member['logo']);
            $fixed++;
        } else {
            $member['logo'] = $img;
        }
    }
    if ($fixed > 0) {
        save_list(TI_MEMBERS_FILE, 'items', $members);
        flash("Fixed $fixed broken membership logo paths.");
    }

    // Fix site images
    $siteimgs = load_site_images();
    $fixed = 0;
    foreach ($siteimgs as &$img_entry) {
        $img = $img_entry['image'] ?? '';
        if ($img === '') continue;

        // Validate path format
        $img = ltrim($img, '/');
        $img = preg_replace('#^\.\./#', '', $img);
        if (!preg_match('#^assets/[A-Za-z0-9._/-]+$#', $img)) {
            unset($img_entry['image']);
            $fixed++;
            continue;
        }

        // Check if file exists
        $filepath = TI_ROOT . '/' . $img;
        if (!is_file($filepath)) {
            unset($img_entry['image']);
            $fixed++;
        } else {
            $img_entry['image'] = $img;
        }
    }
    if ($fixed > 0) {
        save_site_images($siteimgs);
        flash("Fixed $fixed broken site image paths.");
    }

    if ($fixed === 0) {
        flash('No broken paths found — all image references are valid!', 'ok');
    }

    header('Location: fix-images.php'); exit;
}

// Scan and report
$issues = [];

// Check certificates
$certs = load_certs();
foreach ($certs as $id => $cert) {
    $img = $cert['image'] ?? '';
    if ($img === '') continue;
    $filepath = TI_ROOT . '/' . ltrim(preg_replace('#^\.\./#', '', ltrim($img, '/')), '/');
    if (!is_file($filepath)) {
        $issues[] = ['type' => 'certificate', 'id' => $id, 'path' => $img, 'missing' => true];
    }
}

// Check memberships
$members = load_list(TI_MEMBERS_FILE, 'items');
foreach ($members as $member) {
    $img = $member['logo'] ?? '';
    if ($img === '') continue;
    $filepath = TI_ROOT . '/' . ltrim(preg_replace('#^\.\./#', '', ltrim($img, '/')), '/');
    if (!is_file($filepath)) {
        $issues[] = ['type' => 'membership', 'id' => $member['slug'] ?? 'unknown', 'path' => $img, 'missing' => true];
    }
}

// Check site images
$siteimgs = load_site_images();
foreach ($siteimgs as $key => $img_entry) {
    $img = $img_entry['image'] ?? '';
    if ($img === '') continue;
    $filepath = TI_ROOT . '/' . ltrim(preg_replace('#^\.\./#', '', ltrim($img, '/')), '/');
    if (!is_file($filepath)) {
        $issues[] = ['type' => 'site-image', 'id' => $key, 'path' => $img, 'missing' => true];
    }
}

admin_header('Image Path Validator');
?>
<div class="a-bar">
  <h1 class="a-h1">Image Path Validator</h1>
  <p class="a-lead" style="margin:0;font-size:14px">Check and fix broken image references across certificates, memberships, and site photos.</p>
</div>

<?php if (count($issues) === 0): ?>
  <div class="a-note" style="background:rgba(34,197,94,.1);border-color:#22c55e">
    <strong style="color:#16a34a">✓ All images OK</strong><br>
    All image paths are valid and files exist.
  </div>
<?php else: ?>
  <div class="a-note" style="background:rgba(239,68,68,.1);border-color:#ef4444">
    <strong style="color:#dc2626">⚠ Found <?= count($issues) ?> broken image path(s)</strong><br>
    Click "Repair images" below to automatically fix these references (will clear missing paths).
  </div>
  <table class="a-table" style="margin:20px 0">
    <thead><tr><th>Type</th><th>ID</th><th>Path</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($issues as $issue): ?>
      <tr style="background:rgba(239,68,68,.05)">
        <td><span class="a-pill"><?= h($issue['type']) ?></span></td>
        <td><code style="font-size:12px"><?= h($issue['id']) ?></code></td>
        <td><code style="font-size:12px;color:#dc2626"><?= h($issue['path']) ?></code></td>
        <td><span class="a-pill a-pill--muted">Missing file</span></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<form method="post" class="a-actions">
  <?= csrf_field() ?>
  <button class="a-btn a-btn--primary" type="submit">Re-validate now</button>
  <?php if (count($issues) > 0): ?>
    <button class="a-btn a-btn--danger" type="submit" name="repair" value="1" onclick="return confirm('This will clear <?= count($issues) ?> broken image paths. Continue?');">Repair broken paths</button>
  <?php endif; ?>
</form>

<div class="a-note">
  <strong>What this tool does:</strong><br>
  • Validates all image paths in certificates.json, memberships.json, and site-images.json<br>
  • Checks that referenced files actually exist in assets/uploads/<br>
  • Reports any broken or missing image references<br>
  • Can automatically remove broken paths (images can be re-assigned in the admin panels)<br><br>
  <strong>To fix broken images:</strong><br>
  1. Upload a new image via <a href="media.php">Media</a><br>
  2. Go to <a href="certificates.php">Certificates</a>, <a href="memberships.php">Memberships</a>, or <a href="photos.php">Photos</a><br>
  3. Paste the new image path or use the file picker<br>
  4. Save your changes
</div>

<?php admin_footer();
