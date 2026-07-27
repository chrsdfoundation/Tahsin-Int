<?php
require __DIR__ . '/_bootstrap.php';
require_login();

$STATUS = ['completed' => 'Completed', 'ongoing' => 'Ongoing'];
$action = $_GET['action'] ?? 'list';
$items  = load_list(TI_PROJECTS_FILE, 'projects');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { flash('Session expired — please try again.', 'err'); header('Location: projects.php'); exit; }

    if (($_POST['do'] ?? '') === 'delete') {
        $slug = (string) ($_POST['slug'] ?? '');
        $items = array_filter($items, fn($p) => ($p['slug'] ?? '') !== $slug);
        save_list(TI_PROJECTS_FILE, 'projects', $items);
        flash('Project deleted.');
        header('Location: projects.php'); exit;
    }

    $orig  = (string) ($_POST['orig_slug'] ?? '');
    $title = trim((string) ($_POST['title'] ?? ''));
    if ($title === '') { flash('Title is required.', 'err'); header('Location: projects.php?action=new'); exit; }
    $status = (string) ($_POST['status'] ?? 'completed');
    if (!isset($STATUS[$status])) $status = 'completed';
    $slug = trim((string) ($_POST['slug'] ?? '')); $slug = $slug !== '' ? slugify($slug) : slugify($title);
    $taken = array_map(fn($p) => $p['slug'] ?? '', array_filter($items, fn($p) => ($p['slug'] ?? '') !== $orig));
    $base = $slug; $i = 2; while (in_array($slug, $taken, true)) { $slug = $base . '-' . $i; $i++; }

    $item = [
        'slug' => $slug, 'title' => $title, 'status' => $status,
        'client' => trim((string) ($_POST['client'] ?? '')),
        'year' => trim((string) ($_POST['year'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'image' => clean_asset_path((string) ($_POST['image'] ?? '')),
        'published' => isset($_POST['published']),
    ];
    $found = false;
    foreach ($items as $k => $p) { if (($p['slug'] ?? '') === $orig) { $items[$k] = $item; $found = true; break; } }
    if (!$found) array_unshift($items, $item);
    save_list(TI_PROJECTS_FILE, 'projects', $items);
    flash('Project saved.');
    header('Location: projects.php'); exit;
}

if ($action === 'new' || $action === 'edit') {
    $editing = $action === 'edit';
    $p = ['slug' => '', 'title' => '', 'status' => 'completed', 'client' => '', 'year' => '', 'description' => '', 'image' => '', 'published' => true];
    if ($editing) {
        $f = find_by_slug($items, (string) ($_GET['slug'] ?? ''));
        if (!$f) { flash('Project not found.', 'err'); header('Location: projects.php'); exit; }
        $p = array_merge($p, $f);
    }
    admin_header($editing ? 'Edit project' : 'New project');
    ?>
    <div class="a-bar"><a class="a-link" href="projects.php">← All projects</a></div>
    <h1 class="a-h1"><?= $editing ? 'Edit project' : 'New project' ?></h1>
    <form method="post" class="a-form">
      <?= csrf_field() ?>
      <input type="hidden" name="orig_slug" value="<?= h($p['slug']) ?>">
      <label class="a-field">Title <input type="text" name="title" required value="<?= h($p['title']) ?>"></label>
      <div class="a-row">
        <label class="a-field">Status
          <select name="status">
            <?php foreach ($STATUS as $k => $label): ?>
              <option value="<?= h($k) ?>" <?= $p['status'] === $k ? 'selected' : '' ?>><?= h($label) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="a-field">Client <input type="text" name="client" value="<?= h($p['client']) ?>" placeholder="Client name"></label>
        <label class="a-field">Year <input type="text" name="year" value="<?= h($p['year']) ?>" placeholder="e.g. 2025"></label>
      </div>
      <label class="a-field">Image <span class="a-hint">(pick or paste a path)</span>
        <span class="a-pickrow">
          <input id="proj-img" type="text" name="image" value="<?= h($p['image']) ?>" placeholder="assets/uploads/photo.jpg">
          <button type="button" class="a-btn" data-media-pick="#proj-img">Choose…</button>
        </span>
      </label>
      <label class="a-field">Description
        <textarea name="description" rows="4"><?= h($p['description']) ?></textarea>
      </label>
      <label class="a-check"><input type="checkbox" name="published" <?= ($p['published'] ?? true) ? 'checked' : '' ?>> Published</label>
      <div class="a-actions">
        <button class="a-btn a-btn--primary" type="submit">Save project</button>
        <a class="a-btn" href="projects.php">Cancel</a>
      </div>
    </form>
    <?php admin_footer(); exit;
}

admin_header('Projects');
?>
<div class="a-bar a-bar--between">
  <h1 class="a-h1">Projects</h1>
  <a class="a-btn a-btn--primary" href="projects.php?action=new">+ New project</a>
</div>
<p class="a-lead">These appear on the public <a href="../projects.html" target="_blank" rel="noopener">Projects</a> page (Completed / Ongoing tabs) and the homepage carousel.</p>
<?php if (!$items): ?>
  <div class="a-empty">No projects yet. <a href="projects.php?action=new">Add your first →</a></div>
<?php else: ?>
  <table class="a-table">
    <thead><tr><th>Project</th><th>Client · Year</th><th>Status</th><th>Image</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($items as $p): ?>
      <tr>
        <td><a href="projects.php?action=edit&amp;slug=<?= urlencode($p['slug']) ?>"><?= h($p['title']) ?></a></td>
        <td><?= h(trim(($p['client'] ?? '') . ' · ' . ($p['year'] ?? ''), ' ·')) ?></td>
        <td><?= h($STATUS[$p['status'] ?? ''] ?? '') ?></td>
        <td><?php if (!empty($p['image'])): ?><img src="../<?= h($p['image']) ?>" alt="" style="width:56px;height:42px;object-fit:cover;border-radius:5px;border:1px solid var(--border)"><?php else: ?><span class="a-pill a-pill--muted">None</span><?php endif; ?></td>
        <td class="a-nowrap">
          <a class="a-link" href="projects.php?action=edit&amp;slug=<?= urlencode($p['slug']) ?>">Edit</a>
          <form method="post" class="a-inline" onsubmit="return confirm('Delete this project?');">
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
