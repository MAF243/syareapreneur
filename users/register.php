<?php
require_once '../config.php';
// require_once 'send_email.php'; // kalau kamu sudah pakai OTP verifikasi email

$message = '';
$activeRole = $_POST['role'] ?? 'donatur';

function pw_ok($p){
  // kebijakan minimal: 8 char, ada huruf besar & angka
  return strlen($p) >= 8 && preg_match('/[A-Z]/',$p) && preg_match('/\d/',$p);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $email    = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirm  = $_POST['confirm_password'] ?? '';
  $role     = ($activeRole === 'peminjam') ? 'peminjam' : 'donatur';

  if ($username==='' || $email==='' || $password==='' || $confirm==='') {
    $message = "Semua field harus diisi.";
  } elseif ($password !== $confirm) {
    $message = "Konfirmasi password tidak sama.";
  } elseif (!pw_ok($password)) {
    $message = "Password minimal 8 karakter, mengandung huruf besar dan angka.";
  } else {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    try {
      $field = ($role === 'donatur') ? 'donatur' : 'peminjam';
      $stmt = $conn->prepare("INSERT INTO users (username,email,password,{$field}) VALUES (?,?,?,1)");
      $stmt->bind_param('sss',$username,$email,$hashed);

      if ($stmt->execute()) {
        $userId = $stmt->insert_id;
        $stmt->close();

        // Buat folder user
        $base = __DIR__ . '/../uploads';
        if (!is_dir($base)) mkdir($base,0755,true);
        $uname  = preg_replace('/[^\w\-]/','_',$username);
        $userDir = "$base/{$userId}-{$uname}";
        if (!is_dir($userDir)) mkdir($userDir,0755,true);

        // === Kirim OTP verifikasi email (opsional, sudah kamu minta sebelumnya) ===
        $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $exp = date('Y-m-d H:i:s', time()+15*60); // 15 menit
        $ev  = $conn->prepare("INSERT INTO email_verifications (user_id, otp_code, expires_at) VALUES (?,?,?)");
        $ev->bind_param('iss', $userId, $otp, $exp);
        $ev->execute(); $ev->close();
        if (function_exists('send_verification_email')) { @send_verification_email($email, $otp); }

        header("Location: verify_email.php?uid=".$userId);
        exit;
      } else {
        $message = "Error: " . $stmt->error;
        $stmt->close();
      }
    } catch (mysqli_sql_exception $e) {
      $message = "Registrasi gagal: Username atau Email sudah digunakan.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar Akun</title>
  <link rel="stylesheet" href="register.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* meter kekuatan password (pakai warna) – ringan, tak ganggu register.css */
    .pw-meter{height:8px;border-radius:8px;background:#e5e7eb;overflow:hidden;margin-top:8px}
    .pw-meter > span{display:block;height:100%;width:0;background:#ef4444;transition:width .2s ease, background .2s ease}
    .pw-hint{font-size:12px;color:#6b7280;margin-top:6px}
    .match-msg{font-size:12px;margin-top:6px}
    .match-ok{color:#16a34a}.match-bad{color:#b91c1c}
  </style>
</head>
<body class="auth-page">
  <main class="auth-wrapper">
    <section class="auth-card">
      <div class="brand"><div class="logo"></div><h1>Syareapreneur</h1></div>
      <h2 class="auth-title">Daftar sebagai <span data-role-label><?= $activeRole==='peminjam'?'Peminjam':'Donatur' ?></span></h2>
      <p class="auth-subtitle">Lengkapi data di bawah ini untuk mendaftar</p>

      <div class="role-tabs" data-active="<?= htmlspecialchars($activeRole) ?>">
        <div class="slider"></div>
        <button type="button" class="role-btn <?= $activeRole==='donatur'?'active':'' ?>" data-role="donatur">Donatur</button>
        <button type="button" class="role-btn <?= $activeRole==='peminjam'?'active':'' ?>" data-role="peminjam">Peminjam</button>
      </div>

      <?php if($message): ?>
        <div class="message error"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <form action="register.php" method="post" novalidate>
        <input type="hidden" name="role" id="role" value="<?= htmlspecialchars($activeRole) ?>">

        <div class="form-group">
          <label for="username">Username</label>
          <input class="input" id="username" name="username" placeholder="Masukkan username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <input class="input" id="email" type="email" name="email" placeholder="Masukkan email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrap">
            <input class="input" id="password" type="password" name="password" placeholder="Masukkan password" required>
            <button class="toggle-pass" type="button" onclick="togglePass('password')"></button>
          </div>
          <div class="pw-meter"><span id="pwBar"></span></div>
          <div class="pw-hint">Min. 8 karakter, ada huruf besar & angka.</div>
        </div>

        <div class="form-group">
          <label for="confirm_password">Ulangi Password</label>
          <div class="input-wrap">
            <input class="input" id="confirm_password" type="password" name="confirm_password" placeholder="Ulangi password" required>
            <button class="toggle-pass" type="button" onclick="togglePass('confirm_password')"></button>
          </div>
          <div id="matchMsg" class="match-msg"></div>
        </div>

        <button id="submitBtn" type="submit" class="btn btn-primary">Daftar Sekarang</button>
      </form>

      <p class="register-link">Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
    </section>
  </main>

  <script>
    const tabs=document.querySelector('.role-tabs'),
          btns=document.querySelectorAll('.role-btn'),
          roleInput=document.getElementById('role'),
          label=document.querySelector('[data-role-label]');
    btns.forEach(btn=>{
      btn.addEventListener('click',()=>{
        const role=btn.dataset.role;
        tabs.dataset.active=role;
        roleInput.value=role;
        label.textContent=role==='peminjam'?'Peminjam':'Donatur';
        btns.forEach(b=>b.classList.toggle('active',b===btn));
      });
    });

    function togglePass(id){const p=document.getElementById(id);p.type=p.type==='password'?'text':'password';}

    // Meter kekuatan + cocok/tidak
    const pw  = document.getElementById('password');
    const cpw = document.getElementById('confirm_password');
    const bar = document.getElementById('pwBar');
    const msg = document.getElementById('matchMsg');
    const btn = document.getElementById('submitBtn');

    function strength(s){
      let score=0;
      if (s.length>=8) score++;
      if (/[A-Z]/.test(s)) score++;
      if (/\d/.test(s)) score++;
      if (/[^A-Za-z0-9]/.test(s)) score++;
      return score; // 0..4
    }
    function paint(){
      const sc = strength(pw.value);
      const w  = [0,25,50,75,100][sc];
      bar.style.width = w+'%';
      bar.style.background = ['#ef4444','#f59e0b','#f59e0b','#10b981','#0ea5a3'][sc];

      if (cpw.value.length){
        if (pw.value === cpw.value){
          msg.textContent = 'Password cocok.'; msg.className='match-msg match-ok';
        } else {
          msg.textContent = 'Password tidak cocok.'; msg.className='match-msg match-bad';
        }
      } else { msg.textContent=''; }

      // opsional: disable submit kalau lemah/tidak cocok
      btn.disabled = !(pw.value===cpw.value && sc>=3);
    }
    pw.addEventListener('input',paint); cpw.addEventListener('input',paint);
    paint();
  </script>
</body>
</html>
