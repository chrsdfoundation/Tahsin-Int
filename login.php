<?php
/**
 * Convenience redirect: the admin panel lives under /admin/.
 * Visiting /login.php sends you to the real admin login.
 */
header('Location: admin/login.php', true, 302);
exit;
