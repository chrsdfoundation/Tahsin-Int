<?php
require __DIR__ . '/_bootstrap.php';
require_login();

$action = $_GET['action'] ?? 'list';
$posts  = load_posts();

/* ---------- Handle POST (save / delete) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { flash('Session expired — please try again.', 'err'); header('Location: posts.php'); exit; }

    if (($_POST['do'] ?? '') === 'delete') {
        $slug = (string) ($_POST['slug'] ?? '');
        $posts = array_filter($posts, fn($p) => ($p['slug'] ?? '') !== $slug);
        save_posts($posts);
        flash('Post deleted.');
        header('Location: posts.php'); exit;
    }

    // Save (create or update)
    $orig = (string) ($_POST['orig_slug'] ?? '');
    $title = trim((string) ($_POST['title'] ?? ''));
    if ($title === '') { flash('Title is required.', 'err'); header('Location: posts.php?action=new'); exit; }

    $slug = trim((string) ($_POST['slug'] ?? ''));
    $slug = $slug !== '' ? slugify($slug) : slugify($title);
    // ensure unique (unless editing same post)
    $taken = array_map(fn($p) => $p['slug'] ?? '', array_filter($posts, fn($p) => ($p['slug'] ?? '') !== $orig));
    $base = $slug; $i = 2;
    while (in_array($slug, $taken, true)) { $slug = $base . '-' . $i; $i++; }

    $post = [
        'slug'      => $slug,
        'title'     => $title,
        'date'      => trim((string) ($_POST['date'] ?? '')) ?: date('Y-m-d'),
        'author'    => trim((string) ($_POST['author'] ?? 'Tahsin International')),
        'cover'     => trim((string) ($_POST['cover'] ?? '')),
        'excerpt'   => trim((string) ($_POST['excerpt'] ?? '')),
        'body'      => clean_body((string) ($_POST['body'] ?? '')),
        'published' => isset($_POST['published']),
    ];

    // upsert
    $found = false;
    foreach ($posts as $k => $p) {
        if (($p['slug'] ?? '') === $orig) { $posts[$k] = $post; $found = true; break; }
    }
    if (!$found) { array_unshift($posts, $post); }
    save_posts($posts);
    flash('Post saved.');
    header('Location: posts.php'); exit;
}

/* ---------- Edit / New form ---------- */
if ($action === 'new' || $action === 'edit') {
    $editing = $action === 'edit';
    $p = ['slug' => '', 'title' => '', 'date' => date('Y-m-d'), 'author' => 'Tahsin International',
          'cover' => '', 'excerpt' => '', 'body' => '', 'published' => true];
    if ($editing) {
        $found = find_post($posts, (string) ($_GET['slug'] ?? ''));
        if (!$found) { flash('Post not found.', 'err'); header('Location: posts.php'); exit; }
        $p = array_merge($p, $found);
    }
    admin_header($editing ? 'Edit post' : 'New post');
    ?>
    <div class="a-bar"><a class="a-link" href="posts.php">← All posts</a></div>
    <h1 class="a-h1"><?= $editing ? 'Edit post' : 'New post' ?></h1>
    <form method="post" class="a-form">
      <?= csrf_field() ?>
      <input type="hidden" name="orig_slug" value="<?= h($p['slug']) ?>">
      <label class="a-field">Title
        <input type="text" name="title" required value="<?= h($p['title']) ?>">
      </label>
      <div class="a-row">
        <label class="a-field">URL slug <span class="a-hint">(leave blank to auto-generate)</span>
          <input type="text" name="slug" value="<?= h($p['slug']) ?>" placeholder="auto from title">
        </label>
        <label class="a-field">Date
          <input type="date" name="date" value="<?= h($p['date']) ?>">
        </label>
      </div>
      <div class="a-row">
        <label class="a-field">Author
          <input type="text" name="author" value="<?= h($p['author']) ?>">
        </label>
        <label class="a-field">Cover image path <span class="a-hint">(from Media)</span>
          <input type="text" name="cover" value="<?= h($p['cover']) ?>" placeholder="assets/uploads/photo.jpg">
        </label>
      </div>
      <label class="a-field">Excerpt <span class="a-hint">(shown in the list)</span>
        <textarea name="excerpt" rows="2"><?= h($p['excerpt']) ?></textarea>
      </label>
      <label class="a-field">Body <span class="a-hint">(HTML allowed: &lt;p&gt; &lt;h2&gt; &lt;ul&gt; &lt;a&gt; &lt;img&gt; &lt;strong&gt; &lt;blockquote&gt;)</span>
        <textarea name="body" rows="16" class="a-mono"><?= h($p['body']) ?></textarea>
      </label>
      <label class="a-check"><input type="checkbox" name="published" <?= ($p['published'] ?? true) ? 'checked' : '' ?>> Published (visible on the site)</label>
      <div class="a-actions">
        <button class="a-btn a-btn--primary" type="submit">Save post</button>
        <a class="a-btn" href="posts.php">Cancel</a>
      </div>
    </form>
    <?php
    admin_footer();
    exit;
}

/* ---------- List ---------- */
usort($posts, fn($a, $b) => strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? '')));
admin_header('News');
?>
<div class="a-bar a-bar--between">
  <h1 class="a-h1">News &amp; Blog</h1>
  <a class="a-btn a-btn--primary" href="posts.php?action=new">+ New post</a>
</div>

<?php if (!$posts): ?>
  <div class="a-empty">No posts yet. <a href="posts.php?action=new">Create your first post →</a></div>
<?php else: ?>
  <table class="a-table">
    <thead><tr><th>Title</th><th>Date</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($posts as $p): ?>
      <tr>
        <td>
          <a href="posts.php?action=edit&amp;slug=<?= urlencode($p['slug']) ?>"><?= h($p['title']) ?></a>
          <div class="a-slug">/news.html?post=<?= h($p['slug']) ?></div>
        </td>
        <td class="a-nowrap"><?= h($p['date'] ?? '') ?></td>
        <td>
          <?php if (($p['published'] ?? true) !== false): ?>
            <span class="a-pill a-pill--ok">Published</span>
          <?php else: ?>
            <span class="a-pill a-pill--muted">Draft</span>
          <?php endif; ?>
        </td>
        <td class="a-nowrap">
          <a class="a-link" href="posts.php?action=edit&amp;slug=<?= urlencode($p['slug']) ?>">Edit</a>
          <a class="a-link" href="../news.html?post=<?= urlencode($p['slug']) ?>" target="_blank" rel="noopener">View</a>
          <form method="post" class="a-inline" onsubmit="return confirm('Delete this post?');">
            <?= csrf_field() ?>
            <input type="hidden" name="do" value="delete">
            <input type="hidden" name="slug" value="<?= h($p['slug']) ?>">
            <button class="a-link a-link--danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
<?php admin_footer();
