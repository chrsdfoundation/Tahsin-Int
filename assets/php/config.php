<?php
/**
 * Copy to config.php and edit. config.php is gitignored — never commit live values.
 */
return [
    'site_name'  => 'Tahsin International',
    'tagline'    => 'A symbol of progress, world-class service.',
    'recipients' => [
        'tahsininternationalbd2021@gmail.com',
        // 'mdmotalibhossain@yahoo.com',
    ],
    // MUST be an address on your own cPanel domain or mail will be rejected as spoofed.
    'from_email' => 'noreply@tahsinint.com',
    'from_name'  => 'Tahsin International Website',
    'whatsapp'   => '+880 1716 610665',
    'auto_reply' => true,

    // ------------------------------------------------------------------
    // Admin panel (/admin) credentials.
    // 'admin_user' is the login name.
    // Set EITHER 'admin_pass_hash' (recommended) OR 'admin_pass' (plaintext).
    //
    // Generate a secure hash on your server or locally with PHP:
    //   php -r "echo password_hash('YourStrongPassword', PASSWORD_DEFAULT), PHP_EOL;"
    // then paste the result into 'admin_pass_hash' and remove 'admin_pass'.
    // ------------------------------------------------------------------
    'admin_user'      => 'admin',
    'admin_pass'      => 'Bangla321#',   // used only if admin_pass_hash is empty
    'admin_pass_hash' => '',                        // e.g. '$2y$10$....'  (preferred)
];
