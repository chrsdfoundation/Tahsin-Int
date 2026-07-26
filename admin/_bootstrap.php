<?php
/**
 * Tahsin International — admin panel bootstrap.
 * Shared config, session auth, CSRF, data helpers and HTML chrome.
 */
declare(strict_types=1);

// ---------- Security headers ----------
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

// ---------- Paths ----------
define('TI_ROOT', dirname(__DIR__));
define('TI_DATA_FILE', TI_ROOT . '/assets/data/news.json');
define('TI_CERT_FILE', TI_ROOT . '/assets/data/certificates.json');
define('TI_SITEIMG_FILE', TI_ROOT . '/assets/data/site-images.json');
define('TI_UPLOAD_DIR', TI_ROOT . '/assets/uploads');
define('TI_UPLOAD_URL', 'assets/uploads/');           // relative to site root (stored in posts)
define('TI_MAX_UPLOAD', 12 * 1024 * 1024);            // 12 MB (also raise PHP limits — see .user.ini)
define('TI_ALLOWED_EXT', 'jpg,jpeg,png,webp,gif,pdf');

// ---------- Config ----------
$__cfg_live   = __DIR__ . '/../assets/php/config.php';
$__cfg_sample = __DIR__ . '/../assets/php/config.sample.php';
define('TI_CONFIG_IS_SAMPLE', !file_exists($__cfg_live));
$config = TI_CONFIG_IS_SAMPLE ? require $__cfg_sample : require $__cfg_live;

// ---------- Session ----------
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_name('ti_admin_sess');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// ---------- Helpers ----------
function h(?string $s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

/** Is admin auth usable, or still on insecure defaults? */
function admin_is_configured(array $config): bool {
    if (TI_CONFIG_IS_SAMPLE) return false;
    $hash = trim((string) ($config['admin_pass_hash'] ?? ''));
    $plain = (string) ($config['admin_pass'] ?? '');
    if ($hash !== '') return true;
    if ($plain !== '' && $plain !== 'change-this-password') return true;
    return false;
}

function verify_credentials(array $config, string $user, string $pass): bool {
    $u = (string) ($config['admin_user'] ?? 'admin');
    if (!hash_equals($u, $user)) return false;
    $hash = trim((string) ($config['admin_pass_hash'] ?? ''));
    if ($hash !== '') return password_verify($pass, $hash);
    $plain = (string) ($config['admin_pass'] ?? '');
    if ($plain === '' || $plain === 'change-this-password') return false;
    return hash_equals($plain, $pass);
}

function is_logged_in(): bool { return !empty($_SESSION['ti_admin']); }

function require_login(): void {
    if (!is_logged_in()) { header('Location: login.php'); exit; }
}

/* CSRF */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}
function csrf_ok(): bool {
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
}

/* Flash messages */
function flash(string $msg, string $type = 'ok'): void {
    $_SESSION['flash'][] = ['m' => $msg, 't' => $type];
}
function render_flash(): string {
    $out = '';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $out .= '<div class="flash flash--' . h($f['t']) . '">' . h($f['m']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $out;
}

/* Slug */
function slugify(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim((string) $s, '-');
    return $s !== '' ? $s : 'post-' . substr(md5($s . mt_rand()), 0, 6);
}

/* News data */
function load_posts(): array {
    if (!is_file(TI_DATA_FILE)) return [];
    $data = json_decode((string) file_get_contents(TI_DATA_FILE), true);
    return is_array($data['posts'] ?? null) ? $data['posts'] : [];
}
function save_posts(array $posts): bool {
    if (!is_dir(dirname(TI_DATA_FILE))) { @mkdir(dirname(TI_DATA_FILE), 0755, true); }
    $json = json_encode(['posts' => array_values($posts)], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return file_put_contents(TI_DATA_FILE, $json, LOCK_EX) !== false;
}
function find_post(array $posts, string $slug): ?array {
    foreach ($posts as $p) { if (($p['slug'] ?? '') === $slug) return $p; }
    return null;
}

/* Certificate scans (id => ['image'=>..., 'published'=>bool]) */
function cert_slots(): array {
    return [
        'electrical'      => 'Electrical Contractor Licence',
        'irc'             => 'Import Registration Certificate (IRC)',
        'trade-licence'   => 'Trade Licence',
        'vat-bin'         => 'VAT Registration (BIN)',
        'tin'             => 'TIN Certificate',
        'dcci'            => 'DCCI Membership',
        'bgba'            => 'BGBA Membership',
        'company-profile' => 'Company Profile',
    ];
}
function load_certs(): array {
    if (!is_file(TI_CERT_FILE)) return [];
    $d = json_decode((string) file_get_contents(TI_CERT_FILE), true);
    return is_array($d['certificates'] ?? null) ? $d['certificates'] : [];
}
function save_certs(array $certs): bool {
    if (!is_dir(dirname(TI_CERT_FILE))) { @mkdir(dirname(TI_CERT_FILE), 0755, true); }
    $json = json_encode(['certificates' => $certs], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return file_put_contents(TI_CERT_FILE, $json, LOCK_EX) !== false;
}

/* Site photo slots (id => label). Keyed by each placeholder's data-photo attribute. */
function photo_slots(): array {
    return [
        'hero'          => 'Homepage — hero background',
        'office'        => 'Homepage — “Who we are” photo',
        'about-profile' => 'About page — company profile photo',
        'ceo-portrait'  => 'About page — CEO portrait',
    ];
}
function load_site_images(): array {
    if (!is_file(TI_SITEIMG_FILE)) return [];
    $d = json_decode((string) file_get_contents(TI_SITEIMG_FILE), true);
    return is_array($d['images'] ?? null) ? $d['images'] : [];
}
function save_site_images(array $images): bool {
    if (!is_dir(dirname(TI_SITEIMG_FILE))) { @mkdir(dirname(TI_SITEIMG_FILE), 0755, true); }
    $json = json_encode(['images' => $images], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return file_put_contents(TI_SITEIMG_FILE, $json, LOCK_EX) !== false;
}

/* Very small HTML sanitiser for post bodies (defence-in-depth even though admin is trusted). */
function clean_body(string $html): string {
    // strip script/style/iframe/on* handlers
    $html = preg_replace('#<\s*(script|style|iframe|object|embed|form)[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html);
    $html = preg_replace('#<\s*(script|style|iframe|object|embed|form)[^>]*>#is', '', $html);
    $html = preg_replace('#\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);
    $html = preg_replace('#(href|src)\s*=\s*("|\')\s*javascript:[^"\']*("|\')#i', '$1="#"', $html);
    return $html;
}

/* ---------- HTML chrome ---------- */
function admin_header(string $title): void {
    $u = is_logged_in() ? (string) ($_SESSION['ti_admin'] ?? '') : '';
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<meta name="robots" content="noindex, nofollow">';
    echo '<title>' . h($title) . ' · Tahsin Admin</title>';
    echo '<link rel="icon" href="../assets/logos/favicon.svg" type="image/svg+xml">';
    echo '<link rel="stylesheet" href="admin.css"></head><body>';
    if (is_logged_in()) {
        echo '<header class="a-top"><a class="a-brand" href="index.php"><span>Tahsin</span> Admin</a>';
        echo '<nav class="a-nav">';
        echo '<a href="index.php">Dashboard</a><a href="posts.php">News</a><a href="certificates.php">Certificates</a><a href="photos.php">Photos</a><a href="media.php">Media</a>';
        echo '<a class="a-out" href="logout.php">Log out' . ($u ? ' (' . h($u) . ')' : '') . '</a>';
        echo '</nav></header>';
    }
    echo '<main class="a-main">' . render_flash();
}
function admin_footer(): void {
    echo '</main><footer class="a-foot">Tahsin International — admin · <a href="../index.html" target="_blank" rel="noopener">View site ↗</a></footer></body></html>';
}
