<?php
/** Authenticated AJAX upload endpoint (for the editor's media picker). Returns JSON. */
require __DIR__ . '/_bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) { http_response_code(403); echo json_encode(['ok' => false, 'error' => 'Not signed in.']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok' => false, 'error' => 'Method not allowed.']); exit; }

// Oversized POST empties superglobals.
if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    echo json_encode(['ok' => false, 'error' => 'File too large for the server (POST limit ' . ini_get('post_max_size') . ').']); exit;
}
if (!csrf_ok()) { http_response_code(400); echo json_encode(['ok' => false, 'error' => 'Session expired — reload the page.']); exit; }

$r = store_upload($_FILES['file'] ?? null);
echo json_encode($r);
