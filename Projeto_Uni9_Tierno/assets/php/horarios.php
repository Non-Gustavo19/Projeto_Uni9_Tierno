<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite' => 'Lax']);
    session_start();
}
require_once __DIR__ . '/config.php';
if (empty($_SESSION['access_token'])) {
    redirect('login.php?modo=login');
}
redirect('barbeiro.php');
