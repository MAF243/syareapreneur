<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['donatur'])) {
    header("Location: login.php"); exit();
}

$user_id  = (int)$_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Pengguna';

/* =======================
   KPI: Total Donasi & Bulan ini
======================= */
$total_donasi = 0; $bulan_ini = 0;
if ($stmt = $conn->prepare("SELECT COALESCE(SUM(nominal_donasi),0) AS total FROM donations WHERE user_id=?")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute(); $r = $stmt->get_result()->fetch_assoc(); $total_donasi = (int)($r['total'] ?? 0);
    $stmt->close();
}
if ($stmt = $conn->prepare("
    SELECT COALESCE(SUM(nominal_donasi),0) AS total
    FROM donations
    WHERE user_id=? AND DATE_FORMAT(tanggal_donasi,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')
")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute(); $r = $stmt->get_result()->fetch_assoc(); $bulan_ini = (int)($r['total'] ?? 0);
    $stmt->close();
}

/* =======================
   KPI lanjutan: AVG & MAX donasi
======================= */
$avg_donasi = 0; $max_donasi = 0;
if ($s=$conn->prepare("SELECT COALESCE(AVG(nominal_donasi),0) a, COALESCE(MAX(nominal_donasi),0) m FROM donations WHERE user_id=?")) {
  $s->bind_param("i",$user_id); $s->execute();
  $rr=$s->get_result()->fetch_assoc();
  $avg_donasi=(int)$rr['a']; $max_donasi=(int)$rr['m'];
  $s->close();
}

/* =======================
   KPI: UMKM terbantu (nyata)
======================= */
$umkm_terbantu = 0;
if ($stmt = $conn->prepare("
    SELECT COUNT(DISTINCT da.loan_id) AS cnt
    FROM donation_allocations da
    JOIN donations d ON d.id = da.donation_id
    WHERE d.user_id = ?
")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute(); $r = $stmt->get_result()->fetch_assoc(); $umkm_terbantu = (int)($r['cnt'] ?? 0);
    $stmt->close();
}

/* =======================
   Riwayat Donasi (tetap)
======================= */
$donations = [];
if ($stmt = $conn->prepare("SELECT nominal_donasi, tanggal_donasi FROM donations WHERE user_id=? ORDER BY tanggal_donasi DESC LIMIT 5")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute(); $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $donations[] = $row;
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard Donatur</title>
  <!-- Vendor -->
  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="css/sb-admin-2.min.css" rel="stylesheet">
  <!-- Tema Dashboard (pastikan hanya 1x, tidak duplikat) -->
  <link href="dashboard-theme.css" rel="stylesheet">
</head>
<body id="page-top">

<div id="wrapper">
  <?php include 'tambahan/sidebar.php'; ?>

  <div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
      <?php include 'tambahan/topbar.php'; ?>

      <div class="container-fluid">
        <div class="page-title">
          <span class="dot"></span>
          <h1 class="h3 mb-0">Selamat Datang, <?= htmlspecialchars($username) ?>!</h1>
        </div>

        <!-- KPI MINI (khusus donatur) -->
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <div class="kpi">
              <div class="kpi-icon"><i class="fas fa-donate" aria-hidden="true"></i></div>
              <div>
                <div class="kpi-label">Total Donasi Anda</div>
                <div class="kpi-value">Rp <?= number_format($total_donasi, 0, ',', '.') ?></div>
                <small class="kpi-sub">Bulan ini: Rp <?= number_format($bulan_ini, 0, ',', '.') ?></small><br>
                <small class="kpi-sub">Rata-rata: Rp <?= number_format($avg_donasi,0,',','.') ?> · Terbesar: Rp <?= number_format($max_donasi,0,',','.') ?></small>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="kpi">
              <div class="kpi-icon"><i class="fas fa-hands-helping" aria-hidden="true"></i></div>
              <div>
                <div class="kpi-label">UMKM Terbantu</div>
                <div class="kpi-value"><?= number_format($umkm_terbantu, 0, ',', '.') ?></div>
                <small class="kpi-sub">Estimasi berdasarkan alokasi donasi</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Riwayat Donasi -->
        <div class="card mb-4">
          <div class="card-body">
            <div class="status">
              <div class="icon"><i class="fas fa-heart"></i></div>
              <div>
                <h5 class="card-title mb-1">Riwayat Donasi</h5>
                <?php if (empty($donations)): ?>
                  <p class="mb-2 text-muted-600">Anda belum berdonasi. Yuk mulai sekarang.</p>
                  <a href="../donation_form.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-donate me-1"></i> Donasi Sekarang
                  </a>
                  <p class="mt-2 mb-0 text-muted-600">Ingin mengajukan pinjaman? <a href="register_peminjam.php">Daftar sebagai Peminjam</a>.</p>
                <?php else: ?>
                  <ul class="list-clean mt-2">
                    <?php foreach ($donations as $d): ?>
                      <li>
                        <i class="fas fa-check-circle text-success"></i>
                        <span>Rp <?= number_format($d['nominal_donasi'], 0, ',', '.') ?> — <?= date('d M Y', strtotime($d['tanggal_donasi'])) ?></span>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Kontribusi -->
        <div class="row g-4">
          <div class="col-lg-12">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title"><i class="fas fa-lightbulb me-2" aria-hidden="true"></i>Kontribusi Anda</h5>
                <div class="d-grid d-md-flex gap-3 mt-3">
                  <a href="testimoni_form.php" class="btn btn-soft btn-lg"><i class="fas fa-comment-dots me-2"></i>Kirim Testimoni</a>
                  <a href="saran_masukan_form.php" class="btn btn-soft btn-lg"><i class="fas fa-paper-plane me-2"></i>Kirim Saran & Masukan</a>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div><!-- /.container-fluid -->
    </div><!-- /#content -->

    <?php include 'tambahan/footer.php'; ?>
  </div><!-- /#content-wrapper -->
</div><!-- /#wrapper -->

<a class="scroll-to-top rounded" href="#page-top" aria-label="Kembali ke atas"><i class="fas fa-angle-up" aria-hidden="true"></i></a>
<?php include 'tambahan/logout_modal.php'; ?>

<!-- Vendor JS -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>

<?php $conn->close(); ?>
</body>
</html>