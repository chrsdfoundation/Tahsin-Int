<?php
require __DIR__ . '/_bootstrap.php';
require_login();

$CATS = ['grocery' => 'Grocery & Essentials', 'industrial' => 'Industrial & Electrical', 'government' => 'Government Supplies'];
$action = $_GET['action'] ?? 'list';
$items  = load_list(TI_PRODUCTS_FILE, 'products');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { flash('Session expired — please try again.', 'err'); header('Location: products.php'); exit; }

    if (($_POST['do'] ?? '') === 'delete') {
        $slug = (string) ($_POST['slug'] ?? '');
        $items = array_filter($items, fn($p) => ($p['slug'] ?? '') !== $slug);
        save_list(TI_PRODUCTS_FILE, 'products', $items);
        flash('Product deleted.');
        header('Location: products.php'); exit;
    }

    $orig  = (string) ($_POST['orig_slug'] ?? '');
    $title = trim((string) ($_POST['title'] ?? ''));
    if ($title === '') { flash('Title is required.', 'err'); header('Location: products.php?action=new'); exit; }
    $cat = (string) ($_POST['category'] ?? 'grocery');
    if (!isset($CATS[$cat])) $cat = 'grocery';
    $slug = trim((string) ($_POST['slug'] ?? '')); $slug = $slug !== '' ? slugify($slug) : slugify($title);
    $taken = array_map(fn($p) => $p['slug'] ?? '', array_filter($items, fn($p) => ($p['slug'] ?? '') !== $orig));
    $base = $slug; $i = 2; while (in_array($slug, $taken, true)) { $slug = $base . '-' . $i; $i++; }

    $item = [
        'slug' => $slug, 'title' => $title, 'category' => $cat,
        'description' => trim((string) ($_POST['description'] ?? '')),
        'image' => clean_asset_path((string) ($_POST['image'] ?? '')),
        'published' => isset($_POST['published']),
    ];
    $found = false;
    foreach ($items as $k => $p) { if (($p['slug'] ?? '') === $orig) { $items[$k] = $item; $found = true; break; } }
    if (!$found) array_unshift($items, $item);
    save_list(TI_PRODUCTS_FILE, 'products', $items);
    flash('Product saved.');
    header('Location: products.php'); exit;
}

if ($action === 'new' || $action === 'edit') {
    $editing = $action === 'edit';
    $p = ['slug' => '', 'title' => '', 'category' => 'grocery', 'description' => '', 'image' => '', 'published' => true];
    if ($editing) {
        $f = find_by_slug($items, (string) ($_GET['slug'] ?? ''));
        if (!$f) { flash('Product not found.', 'err'); header('Location: products.php'); exit; }
        $p = array_merge($p, $f);
    }
    admin_header($editing ? 'Edit product' : 'New product');
    ?>
    <div class="a-bar"><a class="a-link" href="products.php">← All products</a></div>
    <h1 class="a-h1"><?= $editing ? 'Edit product' : 'New product' ?></h1>
    <form method="post" class="a-form">
      <?= csrf_field() ?>
      <input type="hidden" name="orig_slug" value="<?= h($p['slug']) ?>">
      <label class="a-field">Title <input type="text" name="title" required value="<?= h($p['title']) ?>"></label>
      <div class="a-row">
        <label class="a-field">Category
          <select name="category">
            <?php foreach ($CATS as $k => $label): ?>
              <option value="<?= h($k) ?>" <?= $p['category'] === $k ? 'selected' : '' ?>><?= h($label) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="a-field">URL slug <span class="a-hint">(optional)</span>
          <input type="text" name="slug" value="<?= h($p['slug']) ?>" placeholder="auto from title">
        </label>
      </div>
      <label class="a-field">Image <span class="a-hint">(pick or paste a path)</span>
        <span class="a-pickrow">
          <input id="prod-img" type="text" name="image" value="<?= h($p['image']) ?>" placeholder="assets/uploads/photo.jpg">
          <button type="button" class="a-btn" data-media-pick="#prod-img">Choose…</button>
        </span>
      </label>
      <label class="a-field">Description
        <textarea name="description" rows="4"><?= h($p['description']) ?></textarea>
      </label>
      <label class="a-check"><input type="checkbox" name="published" <?= ($p['published'] ?? true) ? 'checked' : '' ?>> Published</label>
      <div class="a-actions">
        <button class="a-btn a-btn--primary" type="submit">Save product</button>
        <a class="a-btn" href="products.php">Cancel</a>
      </div>
    </form>
    <?php admin_footer(); exit;
}

admin_header('Products');
?>
<div class="a-bar a-bar--between">
  <h1 class="a-h1">Products</h1>
  <a class="a-btn a-btn--primary" href="products.php?action=new">+ New product</a>
</div>
<p class="a-lead">These appear on the public <a href="../products.html" target="_blank" rel="noopener">Products</a> page, filtered by category.</p>
<?php if (!$items): ?>
  <div class="a-empty">No products yet. <a href="products.php?action=new">Add your first →</a></div>
<?php else: ?>
  <table class="a-table">
    <thead><tr><th>Product</th><th>Category</th><th>Image</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($items as $p): ?>
      <tr>
        <td><a href="products.php?action=edit&amp;slug=<?= urlencode($p['slug']) ?>"><?= h($p['title']) ?></a></td>
        <td><?= h($CATS[$p['category'] ?? ''] ?? $p['category'] ?? '') ?></td>
        <td><?php if (!empty($p['image'])): ?><img src="../<?= h($p['image']) ?>" alt="" style="width:56px;height:42px;object-fit:cover;border-radius:5px;border:1px solid var(--border)"><?php else: ?><span class="a-pill a-pill--muted">None</span><?php endif; ?></td>
        <td><?= ($p['published'] ?? true) !== false ? '<span class="a-pill a-pill--ok">Published</span>' : '<span class="a-pill a-pill--muted">Draft</span>' ?></td>
        <td class="a-nowrap">
          <a class="a-link" href="products.php?action=edit&amp;slug=<?= urlencode($p['slug']) ?>">Edit</a>
          <form method="post" class="a-inline" onsubmit="return confirm('Delete this product?');">
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
