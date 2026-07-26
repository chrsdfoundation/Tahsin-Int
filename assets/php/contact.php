<?php
/**
 * Tahsin International — generic form handler (cPanel / PHP mail()).
 * Handles: contact, RFQ, investment proposal, product inquiry, career.
 * Configure recipients in config.php (copy config.sample.php).
 */
declare(strict_types=1);

$config = file_exists(__DIR__ . '/config.php')
    ? require __DIR__ . '/config.php'
    : require __DIR__ . '/config.sample.php';

header('X-Content-Type-Options: nosniff');

function fail(string $msg, int $code = 400): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    fail('Method not allowed', 405);
}

// --- Honeypot: bots fill hidden fields, humans do not ---
if (!empty($_POST['company_website'])) {
    http_response_code(204);
    exit;
}

// --- Simple rate limit: one submission per IP per 30s ---
$ip   = preg_replace('/[^a-zA-Z0-9._:]/', '_', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
$lock = sys_get_temp_dir() . '/ti_form_' . md5($ip);
if (file_exists($lock) && (time() - filemtime($lock)) < 30) {
    fail('Please wait a moment before submitting again.', 429);
}
touch($lock);

// --- Collect & sanitise ---
$clean = static function (string $key, int $max = 2000): string {
    $v = trim((string) ($_POST[$key] ?? ''));
    $v = str_replace(["\r", "\n", "%0a", "%0d"], ' ', $v); // header-injection guard
    return mb_substr(strip_tags($v), 0, $max);
};

$name    = $clean('name', 120);
$contact = $clean('contact', 160);
$topic   = $clean('topic', 120);
$message = trim(strip_tags((string) ($_POST['message'] ?? '')));

if ($name === '' || $contact === '' || $message === '') {
    fail('Please complete all required fields.');
}
if (mb_strlen($message) > 5000) {
    fail('Message is too long.');
}

$isEmail   = (bool) filter_var($contact, FILTER_VALIDATE_EMAIL);
$formName  = $clean('form', 60) ?: 'Website contact';

// --- Extra fields (RFQ, investment, career, product inquiry, etc.) ---
// Any submitted field that is not a reserved/core field is included in the email,
// so richer forms deliver their data even without JavaScript.
$reserved = ['form', 'name', 'contact', 'topic', 'message', 'company_website'];
$extraLines = '';
foreach ($_POST as $key => $val) {
    if (in_array($key, $reserved, true) || !is_string($val)) {
        continue;
    }
    $val = trim($val);
    if ($val === '') {
        continue;
    }
    $val = str_replace(["\r", "\n"], ' ', mb_substr(strip_tags($val), 0, 500));
    $label = ucwords(str_replace(['_', '-'], ' ', preg_replace('/[^a-zA-Z0-9_\- ]/', '', $key)));
    $extraLines .= str_pad($label . ':', 10) . $val . "\n";
}

// --- Compose ---
$subject = sprintf('[%s] %s — %s', $config['site_name'], $formName, $name);
$body    = "New submission from the Tahsin International website\n"
         . str_repeat('-', 52) . "\n"
         . "Form:     {$formName}\n"
         . "Name:     {$name}\n"
         . "Contact:  {$contact}\n"
         . ($topic !== '' ? "Topic:    {$topic}\n" : '')
         . $extraLines
         . "IP:       {$ip}\n"
         . "Time:     " . date('Y-m-d H:i:s T') . "\n"
         . str_repeat('-', 52) . "\n\n"
         . $message . "\n";

$headers = [
    'From: ' . $config['from_name'] . ' <' . $config['from_email'] . '>',
    'Content-Type: text/plain; charset=utf-8',
    'X-Mailer: PHP/' . phpversion(),
];
if ($isEmail) {
    $headers[] = 'Reply-To: ' . $name . ' <' . $contact . '>';
}

$sent = @mail(implode(', ', $config['recipients']), $subject, $body, implode("\r\n", $headers));

// --- Optional auto-reply to the enquirer ---
if ($sent && $isEmail && !empty($config['auto_reply'])) {
    @mail(
        $contact,
        'We received your message — ' . $config['site_name'],
        "Thank you for contacting {$config['site_name']}.\n\n"
        . "We have received your message and will respond within one business day.\n\n"
        . "For anything urgent, WhatsApp us on {$config['whatsapp']}.\n\n"
        . "— {$config['site_name']}\n{$config['tagline']}\n",
        implode("\r\n", [
            'From: ' . $config['from_name'] . ' <' . $config['from_email'] . '>',
            'Content-Type: text/plain; charset=utf-8',
        ])
    );
}

// --- Respond: JSON for fetch(), redirect for a plain form post ---
$wantsJson = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
    || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

if ($wantsJson) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => $sent]);
    exit;
}

header('Location: ' . ($sent ? '../../thank-you.html' : '../../contact.html?error=1'), true, 303);
exit;
