<?php
session_start();
require_once '../config.php';
require_once 'send_email.php';

$uid = (int)($_GET['uid'] ?? 0);
$msg = ''; $ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $uid = (int)($_POST['uid'] ?? 0);
  $otp = trim($_POST['otp'] ?? '');

  $stmt = $conn->prepare("SELECT id, otp_code, expires_at, used_at FROM email_verifications WHERE user_id=? ORDER BY id DESC LIMIT 1");
  $stmt->bind_param('i',$uid);
  $stmt->execute();
  $res = $stmt->get_result();

  if ($row = $res->fetch_assoc()) {
    if (!is_null($row['used_at'])) {
      $msg = "Kode sudah digunakan. Minta kode baru.";
    } elseif (strtotime($row['expires_at']) < time()) {
      $msg = "Kode kadaluarsa. Minta kode baru.";
    } elseif (hash_equals($row['otp_code'], $otp)) {
      // set verified
      $upd1 = $conn->prepare("UPDATE email_verifications SET used_at=NOW() WHERE id=?");
      $upd1->bind_param('i', $row['id']); $upd1->execute(); $upd1->close();

      $upd2 = $conn->prepare("UPDATE users SET email_verified=1 WHERE id=?");
      $upd2->bind_param('i', $uid); $upd2->execute(); $upd2->close();

      $ok = true; $msg = "Email berhasil diverifikasi. Silakan login.";
    } else {
      $msg = "Kode OTP salah.";
    }
  } else {
    $msg = "Kode tidak ditemukan.";
  }
  $stmt->close();

} elseif ($uid > 0 && isset($_GET['resend'])) {
  // Ambil email user
  $ue = $conn->prepare("SELECT email FROM users WHERE id=? LIMIT 1");
  $ue->bind_param('i',$uid); $ue->execute();
  $email = ($ue->get_result()->fetch_assoc()['email'] ?? '');
  $ue->close();

  if ($email) {
    $otp = str_pad((string)random_int(0,999999),6,'0',STR_PAD_LEFT);
    $exp = date('Y-m-d H:i:s', time()+15*60);
    $ins = $conn->prepare("INSERT INTO email_verifications (user_id, otp_code, expires_at) VALUES (?,?,?)");
    $ins->bind_param('iss',$uid,$otp,$exp);
    $ins->execute(); $ins->close();

    @send_verification_email($email, $otp);
    $msg = "Kode baru telah dikirim.";
  } else {
    $msg = "Gagal mengirim ulang kode.";
  }
}
?>
<!doctype html>
<html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Verifikasi Email</title>
<link rel="stylesheet" href="login.css">
</head>
<body class="auth-page">
  <main class="auth-wrapper">
    <section class="auth-card">
      <div class="brand"><div class="logo"></div><h1>Syareapreneur</h1></div>
      <h2 class="auth-title">Verifikasi Email</h2>
      <p class="auth-subtitle">Masukkan 6 digit kode yang kami kirimkan ke email Anda.</p>

      <?php if($msg): ?>
        <div class="message <?= $ok?'success':'error'?>"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <?php if(!$ok): ?>
      <form method="post">
        <input type="hidden" name="uid" value="<?= htmlspecialchars($uid) ?>">
        <div class="form-group">
          <label for="otp">Kode OTP</label>
          <input id="otp" name="otp" class="input" placeholder="123456" pattern="\d{6}" required>
        </div>
        <button class="btn btn-primary" type="submit">Verifikasi</button>
      </form>
      <p class="register-link" style="margin-top:12px;">
        Tidak menerima kode?
        <a href="verify_email.php?uid=<?= $uid ?>&resend=1">Kirim ulang</a>
      </p>
      <?php else: ?>
        <p class="register-link"><a href="login.php">Masuk sekarang</a></p>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>