<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');

    session_set_cookie_params([
        'httponly' => true,
        'secure' => false,
        'samesite' => 'Lax',
        'path' => '/'
    ]);

    session_start();
}

require_once __DIR__ . '/../../config/database.php';

require_once __DIR__ . '/view.php';
require_once __DIR__ . '/redirections.php';
require_once __DIR__ . '/flash.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
