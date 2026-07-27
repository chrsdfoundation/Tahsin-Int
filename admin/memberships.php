<?php
require __DIR__ . '/_bootstrap.php';
require_login();

$action = $_GET['action'] ?? 'list';
$items  = load_list(TI_MEMBERS_FILE, 'items');

// give each item a stable slug (derived) for edit/delete
foreach ($items as $k => $it) {
    if (empty($it['slug'])) { $items[$k]['slug'] = slugify(($it['acronym'] ?? '') . '-' . ($it['name'] ?? 'item')); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { flash('Session expired — please try again.', 'err'); header('Location: memberships.php'); exit; }

    if (($_POST['do'] ?? '') === 'delete') {
        $slug = (string) ($_POST['slug'] ?? '');
        $items = array_filter($items, fn($p) => ($p['slug'] ?? '') !== $slug);
        save_list(TI_MEMBERS_FILE, 'items', $items);
        flash('Membership deleted.');
        header('Location: memberships.php'); exit;
    }

    $orig    = (string) ($_POST['orig_slug'] ?? '');
    $acronym = trim((string) ($_POST['acronym'] ?? ''));
    $name    = trim((string) ($_POST['name'] ?? ''));
    if ($acronym === '' && $name === '') { flash('Enter an acronym or a name.', 'err'); header('Location: memberships.php?action=new'); exit; }
    $slug = slugify(($acronym !== '' ? $acronym : $name) . '-' . substr(md5($name), 0, 4));
    if ($orig !== '') $slug = $orig; // keep slug stable when editing

    $item = [
        'slug' => $slug,
        'acronym' => $acronym,
        'name' => $name,
        'logo' => clean_asset_path((string) ($_POST['logo'] ?? '')),
        'published' => isset($_POST['published']),
    ];
    $found = false;
    foreach ($items as $k => $p) { if (($p['slug'] ?? '') === $orig) { $items[$k] = $item; $found = true; break; } }
    if (!$found) $items[] = $item;
    save_list(TI_MEMBERS_FILE, 'items', $items);
    flash('Membership saved.');
    header('Location: memberships.php'); exit;
}

if ($action === 'new' || $action === 'edit') {
    $editing = $action === 'edit';
    $p = ['slug' => '', 'acronym' => '', 'name' => '', 'logo' => '', 'published' => true];
    if ($editing) {
        $f = find_by_slug($items, (string) ($_GET['slug'] ?? ''));
        if (!$f) { flash('Membership not found.', 'err'); header('Location: memberships.php'); exit; }
        $p = array_merge($p, $f);
    }
    admin_header($editing ? 'Edit membership' : 'New membership');
    ?>
    <div class="a-bar"><a class="a-link" href="memberships.php">← All memberships &amp; partners</a></div>
    <h1 class="a-h1"><?= $editing ? 'Edit membership / partner' : 'New membership / partner' ?></h1>
    <form method="post" class="a-form">
      <?= csrf_field() ?>
      <input type="hidden" name="orig_slug" value="<?= h($p['slug']) ?>">
      <div class="a-row">
        <label class="a-field">Short name / acronym <span class="a-hint">(shown when there's no logo)</span>
          <input type="text" name="acronym" value="<?= h($p['acronym']) ?>" placeholder="e.g. DCCI">
        </label>
      </div>
      <label class="a-field">Full name
        <input type="text" name="name" value="<?= h($p['name']) ?>" placeholder="e.g. Dhaka Chamber of Commerce &amp; Industry">
      </label>
      <label class="a-field">Logo <span class="a-hint">(optional — upload in Media, then pick; falls back to the acronym badge)</span>
        <span class="a-pickrow">
          <input id="member-logo" type="text" name="logo" value="<?= h($p['logo']) ?>" placeholder="assets/uploads/logo.png">
          <button type="button" class="a-btn" data-media-pick="#member-logo">Choose…</button>
        </span>
      </label>
      <label class="a-check"><input type="checkbox" name="published" <?= ($p['published'] ?? true) ? 'checked' : '' ?>> Published</label>
      <div class="a-actions">
        <button class="a-btn a-btn--primary" type="submit">Save</button>
        <a class="a-btn" href="memberships.php">Cancel</a>
      </div>
    </form>
    <?php admin_footer(); exit;
}

admin_header('Memberships');
?>
<div class="a-bar a-bar--between">
  <h1 class="a-h1">Memberships &amp; partners</h1>
  <a class="a-btn a-btn--primary" href="memberships.php?action=new">+ New</a>
</div>
<p class="a-lead">These appear in the “Memberships &amp; partners” section on the homepage and the <a href="../clients.html" target="_blank" rel="noopener">Memberships &amp; Partners</a> page (under About Us). Add a logo image or leave it blank to show the acronym badge.</p>
<?php if (!$items): ?>
  <div class="a-empty">Nothing yet. <a href="memberships.php?action=new">Add your first →</a></div>
<?php else: ?>
  <table class="a-table">
    <thead><tr><th>Logo / badge</th><th>Name</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($items as $p): ?>
      <tr>
        <td><?php if (!empty($p['logo'])): ?><img src="../<?= h($p['logo']) ?>" alt="" style="max-width:80px;max-height:40px;object-fit:contain"><?php else: ?><strong><?= h($p['acronym'] ?? '') ?></strong><?php endif; ?></td>
        <td><a href="memberships.php?action=edit&amp;slug=<?= urlencode($p['slug']) ?>"><?= h($p['name'] ?: $p['acronym']) ?></a></td>
        <td><?= ($p['published'] ?? true) !== false ? '<span class="a-pill a-pill--ok">Published</span>' : '<span class="a-pill a-pill--muted">Hidden</span>' ?></td>
        <td class="a-nowrap">
          <a class="a-link" href="memberships.php?action=edit&amp;slug=<?= urlencode($p['slug']) ?>">Edit</a>
          <form method="post" class="a-inline" onsubmit="return confirm('Delete this entry?');">
            <?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="slug" value="<?= h($p['slug']) ?>">
            <button class="a-link a-link--danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
<?php admin_footer();
