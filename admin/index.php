<?php
require __DIR__ . '/_bootstrap.php';
require_login();

$posts = load_posts();
$published = array_filter($posts, fn($p) => ($p['published'] ?? true) !== false);
$media = is_dir(TI_UPLOAD_DIR)
    ? array_values(array_filter(scandir(TI_UPLOAD_DIR), fn($f) => $f[0] !== '.'))
    : [];

admin_header('Dashboard');
?>
<h1 class="a-h1">Dashboard</h1>
<p class="a-lead">Welcome back. Manage your News &amp; Blog posts and media library below.</p>

<div class="a-cards">
  <div class="a-stat"><div class="a-stat__n"><?= count($posts) ?></div><div class="a-stat__l">Total posts</div></div>
  <div class="a-stat"><div class="a-stat__n"><?= count($published) ?></div><div class="a-stat__l">Published</div></div>
  <div class="a-stat"><div class="a-stat__n"><?= count($media) ?></div><div class="a-stat__l">Media files</div></div>
</div>

<div class="a-grid2">
  <a class="a-panel" href="posts.php">
    <h2>News &amp; Blog</h2>
    <p>Create, edit and publish articles that appear on the public News page.</p>
    <span class="a-link">Manage posts →</span>
  </a>
  <a class="a-panel" href="media.php">
    <h2>Media library</h2>
    <p>Upload images and PDFs for posts, catalogues and the logo, then copy their paths.</p>
    <span class="a-link">Manage media →</span>
  </a>
</div>

<div class="a-note">
  <strong>Note.</strong> This panel manages the News/Blog and media library. The site header logo is a
  built-in vector mark; to change it, replace the files in <code>assets/logos/</code>. Global text such as
  address and phone lives in the page templates.
</div>
<?php admin_footer();
