<?php
/** Authenticated JSON list of uploaded media (for the editor's media picker). */
require __DIR__ . '/_bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
if (!is_logged_in()) { http_response_code(403); echo json_encode(['ok' => false, 'error' => 'auth']); exit; }

$files = is_dir(TI_UPLOAD_DIR)
    ? array_values(array_filter(scandir(TI_UPLOAD_DIR), fn($f) => $f[0] !== '.' && $f !== '.htaccess'))
    : [];
rsort($files);
$out = [];
foreach ($files as $f) {
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    $out[] = [
        'name'    => $f,
        'path'    => TI_UPLOAD_URL . $f,
        'isImage' => in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true),
    ];
}
echo json_encode(['ok' => true, 'files' => $out]);
