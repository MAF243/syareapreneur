<?php
session_start();
require_once '../config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$role = ($_GET['role'] ?? '');
$col  = $role==='peminjam' ? 'peminjam' : ($role==='donatur' ? 'donatur' : null);
if (!$col) { header('Location: dashboard.php'); exit; }

$stmt = $conn->prepare("UPDATE users SET {$col}=1 WHERE id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute(); $stmt->close();

$_SESSION[$col] = true;
header('Location: '.($col==='donatur' ? 'dashboard_donatur.php' : 'dashboard_peminjam.php'));
exit;
