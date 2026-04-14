<?php
// middleware/checkRole.php
session_start();
function checkRole($role) {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== $role) {
        header('Location: /siakadstisdayahamal/auth/login.php');
        exit;
    }
}
