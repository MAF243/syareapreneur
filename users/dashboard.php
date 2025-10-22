<?php
session_start();

// Kalau belum login → ke login
if (empty($_SESSION['user_id'])) {
  header('Location: login.php'); exit();
}

// Kalau dua2nya aktif, arahkan ke role terakhir yang dipakai (kalau ada)
$last = $_SESSION['last_active_role'] ?? null;
if ($last === 'peminjam' && !empty($_SESSION['peminjam'])) {
  header('Location: dashboard_peminjam.php'); exit();
}
if ($last === 'donatur' && !empty($_SESSION['donatur'])) {
  header('Location: dashboard_donatur.php'); exit();
}

// Jika hanya salah satu aktif
if (!empty($_SESSION['peminjam'])) { header('Location: dashboard_peminjam.php'); exit(); }
if (!empty($_SESSION['donatur']))  { header('Location: dashboard_donatur.php');  exit(); }

// Fallback: suruh pilih role / ke login
header('Location: login.php'); exit();
?>