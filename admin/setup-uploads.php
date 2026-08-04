<?php
/**
 * Upload System Setup — Configure and repair the media upload system.
 * Run this if uploads aren't working or after server migration.
 */
require __DIR__ . '/_bootstrap.php';
require_login();

$message = null;
$success = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    // Create directory if missing
    if (!is_dir(TI_UPLOAD_DIR)) {
        @mkdir(TI_UPLOAD_DIR, 0755, true);
        $message .= "✓ Created upload directory\n";
    }

    // Set proper permissions (0755 for directory, 0644 for files)
    @chmod(TI_UPLOAD_DIR, 0755);
    $message .= "✓ Set directory permissions to 0755\n";

    // Create/verify .htaccess for security
    $htaccess_path = TI_UPLOAD_DIR . '/.htaccess';
    $htaccess_content = <<<'EOF'
# Hardening for the uploads directory — never execute uploaded files.
Options -Indexes -ExecCGI
RemoveHandler .php .phtml .php3 .php4 .php5 .php7 .phps .pl .py .cgi
RemoveType .php .phtml .php3 .php4 .php5 .php7 .phps
<FilesMatch "(?i)\.(php[0-9]?|phtml|phps|pl|py|cgi|asp|aspx|sh|htaccess)$">
  Require all denied
</FilesMatch>
EOF;

    if (!is_file($htaccess_path) || file_get_contents($htaccess_path) !== $htaccess_content) {
        if (@file_put_contents($htaccess_path, $htaccess_content, LOCK_EX)) {
            $message .= "✓ Created/updated .htaccess security file\n";
        } else {
            $message .= "✗ Could not write .htaccess (check directory permissions)\n";
            $success = false;
        }
    } else {
        $message .= "✓ .htaccess is up-to-date\n";
    }

    // Create .gitkeep so directory is tracked in git
    $gitkeep = TI_UPLOAD_DIR . '/.gitkeep';
    if (!is_file($gitkeep)) {
        @file_put_contents($gitkeep, '');
        $message .= "✓ Created .gitkeep for git tracking\n";
    }

    // Fix permissions on all existing files
    $files = array_filter(scandir(TI_UPLOAD_DIR), fn($f) => $f[0] !== '.');
    if (!empty($files)) {
        foreach ($files as $file) {
            @chmod(TI_UPLOAD_DIR . '/' . $file, 0644);
        }
        $message .= "✓ Fixed permissions on " . count($files) . " existing files\n";
    }

    // Create empty JSON files if missing
    foreach ([TI_CERT_FILE => '{"certificates":{}}', TI_MEMBERS_FILE => '{"items":[]}', TI_SITEIMG_FILE => '{"images":{}}'] as $file => $default) {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (!is_file($file)) {
            @file_put_contents($file, $default, LOCK_EX);
            $message .= "✓ Created " . basename($file) . "\n";
        }
        @chmod($file, 0644);
    }

    // Test write capability
    $test_file = TI_UPLOAD_DIR . '/.test-' . time();
    if (@file_put_contents($test_file, 'test')) {
        @unlink($test_file);
        $message .= "✓ Directory write test passed\n";
    } else {
        $message .= "✗ Cannot write to upload directory — check server permissions\n";
        $success = false;
    }

    flash(
        ($success ? '✓ ' : '⚠️ ') . "Setup complete\n\n" . $message,
        $success ? 'ok' : 'err'
    );
    header('Location: setup-uploads.php');
    exit;
}

admin_header('Upload System Setup');
?>
<h1 class="a-h1">Upload System Setup</h1>
<p class="a-lead">Configure the media upload system. Run this after server migration or if uploads stop working.</p>

<form method="post" style="margin-top:22px">
  <?= csrf_field() ?>
  <button type="submit" class="a-btn a-btn--primary">Run Setup</button>
</form>

<div class="a-note" style="margin-top:22px">
  <strong>What this does:</strong>
  <ul style="margin: 8px 0 0 20px; padding: 0">
    <li>Creates the upload directory if missing</li>
    <li>Sets correct permissions (0755 for directory, 0644 for files)</li>
    <li>Creates .htaccess security file to prevent code execution</li>
    <li>Creates empty JSON data files if missing</li>
    <li>Tests that the directory is writable</li>
  </ul>
</div>

<div class="a-note" style="margin-top:22px; background:#f0fdf4; border-color:#86efac; color:#166534">
  <strong>When to run setup:</strong>
  <ul style="margin: 8px 0 0 20px; padding: 0">
    <li>After uploading the site to a new server</li>
    <li>If uploads suddenly stop working</li>
    <li>When moving between hosting providers</li>
    <li>After a server administrator resets file permissions</li>
  </ul>
</div>

<?php admin_footer();
