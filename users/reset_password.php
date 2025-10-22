<?php
require_once '../config.php';

$message = '';
$msgClass = ''; // success / error
$token = $_GET['token'] ?? '';
$is_valid_token = false;
$user_id = null;

/* --- 1. Verifikasi token --- */
if (!empty($token)) {
  $stmt = $conn->prepare("SELECT id, reset_expires FROM users WHERE reset_token = ? LIMIT 1");
  $stmt->bind_param("s", $token);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $user_id = (int)$user['id'];
    $expires = $user['reset_expires'];

    if (strtotime($expires) > time()) {
      $is_valid_token = true;
    } else {
      $message = "Token sudah kedaluwarsa. Silakan lakukan permintaan reset ulang.";
      $msgClass = 'error';
    }
  } else {
    $message = "Token tidak valid atau tidak ditemukan.";
    $msgClass = 'error';
  }
  $stmt->close();
} else {
  $message = "Token tidak ditemukan.";
  $msgClass = 'error';
}

/* --- 2. Proses kata sandi baru --- */
function pw_ok($p){
  return strlen($p) >= 8 && preg_match('/[A-Z]/',$p) && preg_match('/\d/',$p);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_valid_token) {
  $new_password = $_POST['new_password'] ?? '';
  $confirm_password = $_POST['confirm_password'] ?? '';

  if ($new_password !== $confirm_password) {
    $message = "Kata sandi baru dan konfirmasi tidak cocok.";
    $msgClass = 'error';
  } elseif (!pw_ok($new_password)) {
    $message = "Password minimal 8 karakter, mengandung huruf besar dan angka.";
    $msgClass = 'error';
  } else {
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt_update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
    $stmt_update->bind_param("si", $hashed, $user_id);

    if ($stmt_update->execute()) {
      $message = "Kata sandi Anda berhasil diubah. <a href='login.php'>Login sekarang</a>.";
      $msgClass = 'success';
      $is_valid_token = false;
    } else {
      $message = "Terjadi kesalahan saat memperbarui kata sandi.";
      $msgClass = 'error';
    }
    $stmt_update->close();
  }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password</title>
  <link rel="stylesheet" href="login.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="auth-page">
  <main class="auth-wrapper">
    <section class="auth-card" role="dialog" aria-labelledby="auth-title">
      <div class="brand"><div class="logo"></div><h1>Syareapreneur</h1></div>

      <h2 id="auth-title" class="auth-title">Reset Password</h2>
      <p class="auth-subtitle">Masukkan kata sandi baru Anda di bawah ini.</p>

      <?php if (!empty($message)): ?>
        <div class="message <?= $msgClass ?>"><?= $message ?></div>
      <?php endif; ?>

      <?php if ($is_valid_token): ?>
      <form action="reset_password.php?token=<?= htmlspecialchars($token) ?>" method="post" novalidate>
        <div class="form-group">
          <label for="new_password">Kata Sandi Baru</label>
          <div class="input-wrap">
            <input type="password" id="new_password" name="new_password" class="input" placeholder="Masukkan kata sandi baru" required>
          </div>
        </div>

        <div class="form-group">
          <label for="confirm_password">Konfirmasi Kata Sandi</label>
          <div class="input-wrap">
            <input type="password" id="confirm_password" name="confirm_password" class="input" placeholder="Konfirmasi kata sandi" required>
          </div>
        </div>

        <button type="submit" class="btn btn-primary">Ubah Kata Sandi</button>
      </form>
      <?php endif; ?>

      <p class="register-link" style="margin-top:14px;">
        <a href="login.php">Kembali ke halaman Login</a>
      </p>
    </section>
  </main>
</body>
</html>