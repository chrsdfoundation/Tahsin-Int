<?php
require __DIR__ . '/_bootstrap.php';

if (is_logged_in()) { header('Location: index.php'); exit; }

$error = '';
$configured = admin_is_configured($config);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        $error = 'Session expired — please try again.';
    } elseif (!$configured) {
        $error = 'Admin login is not configured yet. See the note below.';
    } else {
        $user = trim((string) ($_POST['user'] ?? ''));
        $pass = (string) ($_POST['pass'] ?? '');
        // basic throttle
        $wait = ($_SESSION['login_after'] ?? 0) - time();
        if ($wait > 0) {
            $error = 'Too many attempts. Wait ' . $wait . 's.';
        } elseif (verify_credentials($config, $user, $pass)) {
            session_regenerate_id(true);
            $_SESSION['ti_admin'] = $user;
            unset($_SESSION['login_fails'], $_SESSION['login_after']);
            header('Location: index.php');
            exit;
        } else {
            $_SESSION['login_fails'] = ($_SESSION['login_fails'] ?? 0) + 1;
            if ($_SESSION['login_fails'] >= 5) { $_SESSION['login_after'] = time() + 30; $_SESSION['login_fails'] = 0; }
            $error = 'Incorrect username or password.';
        }
    }
}

admin_header('Sign in');
?>
<div class="a-login">
  <div class="a-card">
    <div class="a-logo" aria-hidden="true">
      <svg width="40" height="40" viewBox="0 0 260 320"><rect x="0" y="0" width="104" height="40" fill="#00008B"/><path d="M0,68 L0,263 Q0,300 42,300 L82,300 Q47,295 47,263 L47,68 Z" fill="#00008B"/><rect x="122" y="0" width="104" height="40" fill="#FF8C00"/><path d="M122,68 L226,68 L226,300 L162,300 Q122,300 122,260 L122,68 Z" fill="#FF8C00"/></svg>
    </div>
    <h1>Tahsin Admin</h1>
    <p class="a-sub">Sign in to manage news and media.</p>
    <?php if ($error): ?><div class="flash flash--err"><?= h($error) ?></div><?php endif; ?>
    <?php if (!$configured): ?>
      <div class="flash flash--warn">
        Admin is not configured. Copy <code>assets/php/config.sample.php</code> to
        <code>assets/php/config.php</code> and set <code>admin_user</code> and
        <code>admin_pass_hash</code> (or <code>admin_pass</code>).
      </div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <label class="a-field">Username
        <input type="text" name="user" required autofocus>
      </label>
      <label class="a-field">Password
        <input type="password" name="pass" required>
      </label>
      <button class="a-btn a-btn--primary" type="submit">Sign in</button>
    </form>
    <p class="a-back"><a href="../index.html">← Back to website</a></p>
  </div>
</div>
<?php admin_footer();
