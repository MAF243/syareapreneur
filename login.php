<?php
session_start();
require_once '../config.php';

// Sudah login? lempar sesuai role
if (!empty($_SESSION['donatur'])) {
  header('Location: dashboard_donatur.php');
  exit();
}
if (!empty($_SESSION['peminjam'])) {
  header('Location: dashboard_peminjam.php');
  exit();
}

// CSRF
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// ==== Captcha: generate untuk GET & saat refresh
function regen_captcha()
{
  $a = random_int(1, 9);
  $b = random_int(1, 9);
  $_SESSION['captcha_q'] = "$a + $b = ?";
  $_SESSION['captcha_answer'] = (string)($a + $b);
}
if (!isset($_SESSION['captcha_answer']) || isset($_GET['regen'])) regen_captcha();

$message    = $_GET['message'] ?? '';
$activeRole = $_POST['role'] ?? 'peminjam';
$rememberEmail = $_COOKIE['remember_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // CSRF
  if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    $message = 'Sesi kadaluarsa. Muat ulang halaman.';
  } else {
    // Captcha dulu
    $cap = trim($_POST['captcha'] ?? '');
    if ($cap === '' || !hash_equals($_SESSION['captcha_answer'] ?? '', $cap)) {
      $message = 'Captcha salah. Coba lagi.';
      regen_captcha();
    } else {
      $email    = trim($_POST['email'] ?? '');
      $password = $_POST['password'] ?? '';
      $role     = $_POST['role'] === 'donatur' ? 'donatur' : 'peminjam';

      // === THROTTLE: 5 gagal / 15 menit per email/IP
      $ip = inet_pton($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
      $windowMins = 15;
      $maxFail = 5;

      $thr = $conn->prepare("
        SELECT COUNT(*) AS fails FROM login_attempts
        WHERE success=0 AND attempted_at >= (NOW() - INTERVAL ? MINUTE)
          AND (email = ? OR ip_addr = ?)
      ");
      $thr->bind_param('iss', $windowMins, $email, $ip);
      $thr->execute();
      $fails = (int)($thr->get_result()->fetch_assoc()['fails'] ?? 0);
      $thr->close();

      if ($fails >= $maxFail) {
        $message = 'Terlalu banyak percobaan. Coba lagi dalam beberapa menit.';
      } else {
        // Query by role
        if ($role === 'donatur') {
          $stmt = $conn->prepare("SELECT id,username,password,donatur,email_verified FROM users WHERE email=? AND donatur=1 LIMIT 1");
        } else {
          $stmt = $conn->prepare("SELECT id,username,password,peminjam,email_verified FROM users WHERE email=? AND peminjam=1 LIMIT 1");
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
          $user = $res->fetch_assoc();
          if (password_verify($password, $user['password'])) {
            //if (isset($user['email_verified']) && (int)$user['email_verified'] !== 1) {
            // $message = 'Verifikasi email dulu ya. Cek inbox/kode OTP.';
            // } else {
            // sukses
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['donatur']  = ($role === 'donatur');
            $_SESSION['peminjam'] = ($role === 'peminjam');
            $_SESSION['last_active_role'] = $role;

            // folder user
            $base = realpath(__DIR__ . '/../uploads');
            if ($base === false) {
              @mkdir(__DIR__ . '/../uploads', 0755, true);
              $base = realpath(__DIR__ . '/../uploads');
            }
            $uname   = preg_replace('/[^\w\-]/', '_', $user['username']);
            $userDir = $base . DIRECTORY_SEPARATOR . $user['id'] . '-' . $uname;
            if (!is_dir($userDir)) {
              @mkdir($userDir, 0755, true);
            }

            // remember email
            if (!empty($_POST['remember'])) {
              setcookie('remember_email', $email, time() + 60 * 60 * 24 * 30, '/', '', false, true);
            } else {
              setcookie('remember_email', '', time() - 3600, '/');
            }

            // log sukses
            $log = $conn->prepare("INSERT INTO login_attempts (email, ip_addr, attempted_at, success) VALUES (?,?,NOW(),1)");
            $log->bind_param('ss', $email, $ip);
            $log->execute();
            $log->close();

            header('Location: ' . ($role === 'donatur' ? 'dashboard_donatur.php' : 'dashboard_peminjam.php'));
            exit();
          }
        } else {
          $message = "Email atau password salah.";
        }
        //} else {
        //$message = "Akun tidak ditemukan atau belum mengaktifkan peran tersebut.";
        //}
        $stmt->close();

        // log gagal
        $log = $conn->prepare("INSERT INTO login_attempts (email, ip_addr, attempted_at, success) VALUES (?,?,NOW(),0)");
        $log->bind_param('ss', $email, $ip);
        $log->execute();
        $log->close();
      }
      // regenerate captcha untuk percobaan berikutnya
      regen_captcha();
    }
  }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <link rel="stylesheet" href="login.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="auth-page">
  <main class="auth-wrapper">
    <section class="auth-card" role="dialog" aria-labelledby="auth-title" aria-describedby="auth-subtitle">
      <div class="brand" aria-hidden="true">
        <div class="logo"></div>
        <h1>Syareapreneur</h1>
      </div>

      <h2 id="auth-title" class="auth-title">Masuk sebagai <span data-role-label><?= $activeRole === 'donatur' ? 'Donatur' : 'Peminjam' ?></span></h2>
      <p id="auth-subtitle" class="auth-subtitle">Silakan masuk untuk melanjutkan</p>

      <div class="role-tabs" data-active="<?= htmlspecialchars($activeRole) ?>" aria-label="Pilih peran">
        <div class="slider" aria-hidden="true"></div>
        <button type="button" class="role-btn <?= $activeRole === 'donatur' ? 'active' : '' ?>" data-role="donatur">Donatur</button>
        <button type="button" class="role-btn <?= $activeRole === 'peminjam' ? 'active' : '' ?>" data-role="peminjam">Peminjam</button>
      </div>

      <?php if (!empty($message)): ?>
        <div class="message <?= (stripos($message, 'salah') !== false || stripos($message, 'tidak') !== false || stripos($message, 'banyak') !== false) ? 'error' : 'success'; ?>" role="alert">
          <?= htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <form id="auth-form" action="login.php" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="role" id="role" value="<?= htmlspecialchars($activeRole) ?>">

        <div class="form-group">
          <label for="email">Email</label>
          <div class="input-wrap">
            <input type="email" id="email" name="email" class="input" placeholder="Masukkan email"
              autocomplete="email" required autofocus value="<?= htmlspecialchars($_POST['email'] ?? $rememberEmail) ?>">
          </div>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrap">
            <input type="password" id="password" name="password" class="input" placeholder="Masukkan password"
              autocomplete="current-password" required>
            <button class="toggle-pass" type="button" aria-label="Tampilkan/sembunyikan password"
              onclick="const p=document.getElementById('password'); p.type=p.type==='password'?'text':'password'; this.setAttribute('aria-pressed',p.type==='text')"></button>
          </div>
        </div>

        <!-- Captcha -->
        <div class="form-group">
          <label for="captcha">Captcha</label>
          <div class="input-wrap">
            <input type="text" id="captcha" name="captcha" class="input"
              placeholder="<?= htmlspecialchars($_SESSION['captcha_q'] ?? 'Hitung di sini') ?>" required>
          </div>
          <div class="pw-hint" style="display:flex;align-items:center;gap:10px;margin-top:6px">
            <span><?= htmlspecialchars($_SESSION['captcha_q'] ?? '') ?></span>
            <a href="login.php?regen=1" style="text-decoration:none">Muat ulang</a>
          </div>
        </div>

        <div class="form-extra">
          <label><input type="checkbox" name="remember" style="margin-right:8px;"> Ingat saya</label>
          <a href="forgot_password.php">Lupa password?</a>
        </div>

        <button type="submit" class="btn btn-primary">Masuk</button>
      </form>

      <p class="register-link">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
    </section>
  </main>

  <script>
    // Switch role
    (function() {
      const tabs = document.querySelector('.role-tabs');
      const btns = document.querySelectorAll('.role-btn');
      const roleInput = document.getElementById('role');
      const label = document.querySelector('[data-role-label]');

      function setRole(role) {
        tabs.dataset.active = role;
        roleInput.value = role;
        label.textContent = role === 'donatur' ? 'Donatur' : 'Peminjam';
        btns.forEach(b => b.classList.toggle('active', b.dataset.role === role));
      }
      btns.forEach(btn => {
        btn.addEventListener('click', () => {
          const role = btn.dataset.role;
          document.querySelector('.auth-card').animate(
            [{
              opacity: 1,
              transform: 'translateY(0)'
            }, {
              opacity: 1,
              transform: 'translateY(-2px)'
            }, {
              opacity: 1,
              transform: 'translateY(0)'
            }], {
              duration: 180,
              easing: 'ease-out'
            }
          );
          setRole(role);
          document.getElementById('email').focus();
        });
      });
    })();
  </script>
</body>

</html>