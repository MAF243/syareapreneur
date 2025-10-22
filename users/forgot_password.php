<?php
session_start();
require_once '../config.php';
require_once 'send_email.php';

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$message=''; $msgClass='';

if ($_SERVER['REQUEST_METHOD']==='POST'){
  if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    $message='Sesi kadaluarsa. Muat ulang halaman.'; $msgClass='error';
  } else {
    $email = trim($_POST['email'] ?? '');
    $generic = "Jika email Anda terdaftar, tautan reset kata sandi telah dikirim.";

    $stmt = $conn->prepare("SELECT id, reset_expires FROM users WHERE email=?");
    $stmt->bind_param('s',$email); $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
      $user_id = (int)$row['id'];

      // cooldown 5 menit: cek request terakhir
      $cool = $conn->prepare("SELECT attempted_at FROM login_attempts WHERE email=? ORDER BY attempted_at DESC LIMIT 1");
      $cool->bind_param('s',$email); $cool->execute();
      $last = $cool->get_result()->fetch_assoc()['attempted_at'] ?? null;
      $cool->close();
      if ($last && strtotime($last) > time()-300) {
        $message=$generic; $msgClass='success';
      } else {
        $token = bin2hex(random_bytes(32));
        $hash  = hash('sha256',$token,true);
        $exp   = date('Y-m-d H:i:s', time()+3600);
        $upd = $conn->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE id=?");
        $upd->bind_param('bsi', $hash, $exp, $user_id);
        $upd->send_long_data(0, $hash);
        $upd->execute(); $upd->close();

        if (send_reset_email($email, $token)) {
          $message=$generic; $msgClass='success';
        } else {
          $message="Gagal mengirim email. Silakan coba lagi."; $msgClass='error';
        }

        // catat sebagai attempt (pakai tabel attempts utk throttle utilitas)
        $ip = inet_pton($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'); $succ=1;
        $log = $conn->prepare("INSERT INTO login_attempts(email,ip_addr,attempted_at,success) VALUES (?,?,NOW(),?)");
        $log->bind_param('ssi',$email,$ip,$succ); $log->execute(); $log->close();
      }
    } else { $message=$generic; $msgClass='success'; }

    $stmt->close();
  }
}
?>
<!doctype html><html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Lupa Password</title>
<link rel="stylesheet" href="login.css">
</head>
<body class="auth-page">
<main class="auth-wrapper"><section class="auth-card">
  <div class="brand"><div class="logo"></div><h1>Syareapreneur</h1></div>
  <h2 class="auth-title">Lupa Password</h2>
  <p class="auth-subtitle">Masukkan email Anda untuk menerima tautan reset.</p>
  <?php if($message):?><div class="message <?= $msgClass?>"><?= htmlspecialchars($message)?></div><?php endif;?>
  <form method="post" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'])?>">
    <div class="form-group">
      <label for="email">Email</label>
      <div class="input-wrap"><input type="email" class="input" id="email" name="email" required autofocus></div>
    </div>
    <button class="btn btn-primary">Kirim Tautan Reset</button>
  </form>
  <p class="register-link" style="margin-top:12px;"><a href="login.php">Kembali ke Login</a></p>
</section></main>
</body>
</html>