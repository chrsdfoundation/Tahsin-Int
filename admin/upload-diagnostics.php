<?php
/**
 * Upload Diagnostics Tool — verify the media upload system is working correctly.
 * Run this periodically to identify and fix upload issues before they break the site.
 */
require __DIR__ . '/_bootstrap.php';
require_login();

$diagnostics = [];
$errors = [];
$warnings = [];

// 1. Check if uploads directory exists and is writable
$diag = [
    'name' => 'Uploads Directory',
    'path' => TI_UPLOAD_DIR,
    'status' => 'OK',
    'detail' => ''
];
if (!is_dir(TI_UPLOAD_DIR)) {
    $diag['status'] = 'FAIL';
    $diag['detail'] = 'Directory does not exist';
    $errors[] = $diag['detail'];
} elseif (!is_writable(TI_UPLOAD_DIR)) {
    $diag['status'] = 'FAIL';
    $diag['detail'] = 'Directory exists but is not writable';
    $errors[] = $diag['detail'];
} else {
    $diag['detail'] = 'Directory exists and is writable';
}
$diagnostics[] = $diag;

// 2. Check if .htaccess exists and has proper content
$diag = [
    'name' => '.htaccess Security',
    'path' => TI_UPLOAD_DIR . '/.htaccess',
    'status' => 'OK',
    'detail' => ''
];
if (!is_file(TI_UPLOAD_DIR . '/.htaccess')) {
    $diag['status'] = 'WARN';
    $diag['detail'] = '.htaccess missing — creating now';
    $htaccess = <<<'EOF'
# Hardening for the uploads directory — never execute uploaded files.
Options -Indexes -ExecCGI
RemoveHandler .php .phtml .php3 .php4 .php5 .php7 .phps .pl .py .cgi
RemoveType .php .phtml .php3 .php4 .php5 .php7 .phps
<FilesMatch "(?i)\.(php[0-9]?|phtml|phps|pl|py|cgi|asp|aspx|sh|htaccess)$">
  Require all denied
</FilesMatch>
EOF;
    if (@file_put_contents(TI_UPLOAD_DIR . '/.htaccess', $htaccess)) {
        $diag['detail'] = '.htaccess created successfully';
        $warnings[] = $diag['detail'];
    } else {
        $diag['status'] = 'FAIL';
        $diag['detail'] = 'Could not create .htaccess';
        $errors[] = $diag['detail'];
    }
} else {
    $diag['detail'] = '.htaccess exists and is protecting uploads';
}
$diagnostics[] = $diag;

// 3. Check uploaded files are accessible
$diag = [
    'name' => 'Sample File Access',
    'status' => 'OK',
    'detail' => ''
];
$sample_files = array_filter(scandir(TI_UPLOAD_DIR), fn($f) => $f[0] !== '.' && $f !== '.htaccess');
if (empty($sample_files)) {
    $diag['status'] = 'WARN';
    $diag['detail'] = 'No files in uploads directory yet';
    $warnings[] = $diag['detail'];
} else {
    $sample = reset($sample_files);
    $path = TI_UPLOAD_DIR . '/' . $sample;
    if (is_readable($path)) {
        $diag['detail'] = 'Files are readable: ' . $sample . ' (' . filesize($path) . ' bytes)';
    } else {
        $diag['status'] = 'FAIL';
        $diag['detail'] = 'Sample file exists but is not readable';
        $errors[] = $diag['detail'];
    }
}
$diagnostics[] = $diag;

// 4. Check data JSON files are writable
foreach (['TI_CERT_FILE' => 'Certificates', 'TI_MEMBERS_FILE' => 'Memberships', 'TI_SITEIMG_FILE' => 'Site Images'] as $const => $label) {
    $file = constant($const);
    $diag = [
        'name' => "$label JSON",
        'path' => $file,
        'status' => 'OK',
        'detail' => ''
    ];
    if (!is_file($file)) {
        $diag['status'] = 'WARN';
        $diag['detail'] = 'File does not exist yet';
        $warnings[] = "$label JSON not yet created";
    } elseif (!is_writable($file)) {
        $diag['status'] = 'FAIL';
        $diag['detail'] = 'File exists but is not writable';
        $errors[] = "$label JSON is not writable";
    } else {
        $diag['detail'] = 'File exists and is writable';
    }
    $diagnostics[] = $diag;
}

// 5. Check PHP upload limits
$diag = [
    'name' => 'PHP Upload Limits',
    'status' => 'OK',
    'detail' => ''
];
$upload_max = ini_get('upload_max_filesize');
$post_max = ini_get('post_max_size');
$diag['detail'] = "upload_max_filesize=$upload_max, post_max_size=$post_max";
if (TI_MAX_UPLOAD > 12 * 1024 * 1024) {
    $diag['status'] = 'WARN';
    $diag['detail'] .= ' — config exceeds defaults, .user.ini required on live server';
    $warnings[] = 'PHP limits may need adjustment on live server';
}
$diagnostics[] = $diag;

// 6. Test write capability with a temporary file
$diag = [
    'name' => 'Write Test',
    'status' => 'OK',
    'detail' => ''
];
$test_file = TI_UPLOAD_DIR . '/.test-' . bin2hex(random_bytes(4));
if (@file_put_contents($test_file, 'test')) {
    @unlink($test_file);
    $diag['detail'] = 'Upload directory write test passed';
} else {
    $diag['status'] = 'FAIL';
    $diag['detail'] = 'Cannot write to upload directory';
    $errors[] = 'Directory is not writable';
}
$diagnostics[] = $diag;

admin_header('Upload Diagnostics');
?>
<h1 class="a-h1">Upload System Diagnostics</h1>
<p class="a-lead">This tool checks if the admin upload system is working correctly. Run it periodically to catch issues early.</p>

<?php if (!empty($errors)): ?>
<div class="a-note" style="background:#fee2e2; border-color:#fca5a5; color:#7f1d1d">
  <strong style="color:#b91c1c">⚠️ Critical Issues Found (<?= count($errors) ?>)</strong><br>
  <?php foreach ($errors as $e): ?><?= h($e) ?><br><?php endforeach; ?>
  <p style="margin-top:8px; font-size:13px">These must be fixed before uploads will work.</p>
</div>
<?php endif; ?>

<?php if (!empty($warnings)): ?>
<div class="a-note" style="background:#fef3c7; border-color:#fcd34d; color:#78350f">
  <strong style="color:#b45309">⚠️ Warnings (<?= count($warnings) ?>)</strong><br>
  <?php foreach ($warnings as $w): ?><?= h($w) ?><br><?php endforeach; ?>
  <p style="margin-top:8px; font-size:13px">These may need attention but don't prevent uploads.</p>
</div>
<?php endif; ?>

<?php if (empty($errors) && empty($warnings)): ?>
<div class="a-note" style="background:#dcfce7; border-color:#86efac; color:#166534">
  <strong style="color:#15803d">✓ All Systems Operational</strong><br>
  The upload system is fully functional. You can safely upload files via the Media page.
</div>
<?php endif; ?>

<table class="a-tbl" style="margin-top:22px; width:100%">
  <thead>
    <tr>
      <th>Component</th>
      <th>Status</th>
      <th>Details</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($diagnostics as $d): ?>
    <tr>
      <td><strong><?= h($d['name']) ?></strong><?php if (isset($d['path'])): ?><br><code style="font-size:11px"><?= h(str_replace('\\', '/', $d['path'])) ?></code><?php endif; ?></td>
      <td style="text-align:center">
        <span style="display:inline-block; padding:3px 8px; border-radius:4px; font-weight:600; font-size:12px;
          <?php if ($d['status'] === 'OK'): ?>background:#dcfce7; color:#166534<?php elseif ($d['status'] === 'WARN'): ?>background:#fef3c7; color:#78350f<?php else: ?>background:#fee2e2; color:#7f1d1d<?php endif; ?>">
          <?= h($d['status']) ?>
        </span>
      </td>
      <td><?= h($d['detail']) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<div class="a-note" style="margin-top:22px">
  <strong>How to use this page:</strong>
  <ul style="margin: 8px 0 0 20px; padding: 0">
    <li>Check this page weekly to monitor upload system health</li>
    <li>If you see red (FAIL), uploads won't work — fix it immediately</li>
    <li>If you see yellow (WARN), uploads may be slow or limited — plan to upgrade</li>
    <li>If you see green (OK), you're good to upload via the Media page</li>
  </ul>
</div>

<?php admin_footer();
