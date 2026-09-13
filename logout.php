<?php
require_once __DIR__ . '/includes/functions.php';

session_unset();
session_destroy();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

set_flash('info', 'You have been logged out.');
header('Location: /login.php');
exit;
