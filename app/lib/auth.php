<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireAuth()
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}
