<?php
session_start();
require_once '../config.php';

// Wajib login + role peminjam
if (!isset($_SESSION['user_id']) || empty($_SESSION['peminjam'])) {
    header("Location: login.php");
    exit();
}

$user_id  = (int)$_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Pengguna';

// --- Ambil pengajuan P2MU terbaru (kalau ada) ---
$submission = null;
if ($st = $conn->prepare("SELECT * FROM p2mu_forms WHERE user_id = ? ORDER BY tanggal_pengajuan DESC LIMIT 1")) {
    $st->bind_param("i", $user_id);
    $st->execute();
    $submission = $st->get_result()->fetch_assoc();
    $st->close();
}

$submissionId = $submission['id'] ?? null;
$submissionAmount = (int)($submission['jumlah_pinjam']?? $submission['nominal_pengajuan']?? $submission['total_pinjaman']?? 0);

// --- KPI: jumlah pinjaman aktif ---
$activeLoans = 0;
if ($st = $conn->prepare("SELECT COUNT(*) AS c FROM loans WHERE user_id=? AND status='active'")) {
    $st->bind_param("i", $user_id);
    $st->execute();
    $activeLoans = (int)$st->get_result()->fetch_assoc()['c'];
    $st->close();
}

// --- KPI: Tagihan bulan ini ---
$billThisMonth = 0;
$sqlBill = "
  SELECT COALESCE(SUM(li.amount),0) AS amt
  FROM loan_installments li
  JOIN loans l ON l.id = li.loan_id
  WHERE l.user_id = ?
    AND li.status = 'unpaid'
    AND YEAR(li.due_date) = YEAR(CURDATE())
    AND MONTH(li.due_date) = MONTH(CURDATE())
";
if ($st = $conn->prepare($sqlBill)) {
    $st->bind_param("i", $user_id);
    $st->execute();
    $billThisMonth = (int)$st->get_result()->fetch_assoc()['amt'];
    $st->close();
}

// --- Loan aktif terbaru (kalau ada) ---
$loan = null;
if ($st = $conn->prepare("SELECT * FROM loans WHERE user_id=? AND status='active' ORDER BY id DESC LIMIT 1")) {
    $st->bind_param("i", $user_id);
    $st->execute();
    $loan = $st->get_result()->fetch_assoc();
    $st->close();
}

// Variabel untuk progres & grafik
$progress = [
    'total_cnt'   => 0,
    'paid_cnt'    => 0,
    'progressPct' => 0,
    'next_due'    => null,
    'next_amt'    => 0
];
$principal_left = 0;
$overdue        = 0;

// Untuk grafik riwayat: labels & series
$chart_labels = [];
$chart_remaining = [];  // sisa saldo setelah tiap periode
$chart_paid_cum  = [];  // total dibayar kumulatif (opsional)

// Hitung progres & grafik dari data nyata
if ($loan) {
    $loan_id = (int)$loan['id'];

    // ringkasan progres
    if ($q = $conn->prepare("
        SELECT 
          COUNT(*) AS total_cnt,
          SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) AS paid_cnt
        FROM loan_installments
        WHERE loan_id = ?"))
    {
        $q->bind_param("i", $loan_id);
        $q->execute();
        $prog = $q->get_result()->fetch_assoc();
        $q->close();

        $total_cnt = (int)($prog['total_cnt'] ?? 0);
        $paid_cnt  = (int)($prog['paid_cnt'] ?? 0);
        $progressPct = $total_cnt ? round($paid_cnt / $total_cnt * 100) : 0;

        $progress['total_cnt']   = $total_cnt;
        $progress['paid_cnt']    = $paid_cnt;
        $progress['progressPct'] = $progressPct;
    }

    // tagihan berikutnya (unpaid terdekat)
    if ($q = $conn->prepare("
        SELECT due_date, amount 
        FROM loan_installments 
        WHERE loan_id=? AND status='unpaid' 
        ORDER BY due_date ASC LIMIT 1"))
    {
        $q->bind_param("i", $loan_id);
        $q->execute();
        if ($row = $q->get_result()->fetch_assoc()) {
            $progress['next_due'] = $row['due_date'];
            $progress['next_amt'] = (int)$row['amount'];
        }
        $q->close();
    }

    // sisa pokok & tunggakan
    if ($s=$conn->prepare("SELECT COALESCE(SUM(amount),0) s FROM loan_installments WHERE loan_id=? AND status='unpaid'")) {
        $s->bind_param("i",$loan_id); $s->execute();
        $principal_left = (int)($s->get_result()->fetch_assoc()['s'] ?? 0);
        $s->close();
    }
    if ($s=$conn->prepare("SELECT COALESCE(SUM(amount),0) s FROM loan_installments WHERE loan_id=? AND status='unpaid' AND due_date < CURDATE()")) {
        $s->bind_param("i",$loan_id); $s->execute();
        $overdue = (int)($s->get_result()->fetch_assoc()['s'] ?? 0);
        $s->close();
    }

    // === Data untuk grafik riwayat ===
    $installments = [];
    if ($q = $conn->prepare("SELECT due_date, amount, status FROM loan_installments WHERE loan_id=? ORDER BY due_date ASC, id ASC")) {
        $q->bind_param("i",$loan_id);
        $q->execute();
        $res = $q->get_result();
        while($row=$res->fetch_assoc()) { $installments[] = $row; }
        $q->close();
    }

    // total pinjaman
    $total_amount = 0;
    if (!empty($installments)) {
        foreach($installments as $it){ $total_amount += (int)$it['amount']; }
    }

    $paid_cum = 0;
    $remaining = $total_amount;
    foreach($installments as $it){
        $label = date('M Y', strtotime($it['due_date']));
        $chart_labels[] = $label;

        if ($it['status'] === 'paid') { $paid_cum += (int)$it['amount']; }
        $remaining = max(0, $total_amount - $paid_cum);

        $chart_paid_cum[]  = $paid_cum;
        $chart_remaining[] = $remaining;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Dashboard Peminjam">
    <title>Dashboard Peminjam</title>

    <!-- Vendor & Theme -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="dashboard-theme.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>
<body id="page-top">

<div id="wrapper">
    <?php include 'tambahan/sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <?php include 'tambahan/topbar.php'; ?>

            <div class="container-fluid">

                <!-- Judul halaman -->
                <div class="page-title">
                    <span class="dot"></span>
                    <h1 class="h3 mb-0">Selamat Datang, <?= htmlspecialchars($username) ?>!</h1>
                </div>

                <!-- KPI Mini -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="kpi">
                            <div class="kpi-icon"><i class="fas fa-clipboard-list" aria-hidden="true"></i></div>
                            <div>
                                <div class="kpi-label">Pinjaman Aktif</div>
                                <div class="kpi-value"><?= number_format($activeLoans, 0, ',', '.') ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="kpi">
                            <div class="kpi-icon"><i class="fas fa-file-invoice-dollar" aria-hidden="true"></i></div>
                            <div>
                                <div class="kpi-label">Tagihan Bulan Ini</div>
                                <div class="kpi-value">Rp <?= number_format($billThisMonth, 0, ',', '.') ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Blok Status Pengajuan / Progres Pinjaman -->
                <div class="row">
                    <div class="col-xl-12 mb-4">
                        <?php if ($loan): ?>
                            <!-- SUDAH PENCAIRAN: tampilkan progres -->
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <div class="status mb-3">
                                        <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
                                        <div>
                                            <h5 class="mb-1">Progres Pembayaran</h5>
                                            <p class="mb-0 text-muted">
                                                Pinjaman aktif sejak:
                                                <strong><?= date('d M Y', strtotime($loan['start_date'])) ?></strong>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="progress mb-2" style="height:12px; border-radius:10px;">
                                        <div class="progress-bar bg-success" role="progressbar"
                                             style="width: <?= $progress['progressPct'] ?>%;"
                                             aria-valuenow="<?= $progress['progressPct'] ?>"
                                             aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted mb-2">
                                        <small><strong><?= $progress['paid_cnt'] ?></strong> / <?= $progress['total_cnt'] ?> angsuran terbayar</small>
                                        <small><?= $progress['progressPct'] ?>%</small>
                                    </div>

                                    <!-- Sisa pokok & tunggakan -->
                                    <div class="d-flex gap-3 flex-wrap text-muted mb-3">
                                      <small>Sisa pokok: <strong>Rp <?= number_format($principal_left,0,',','.') ?></strong></small>
                                      <?php if($overdue>0): ?>
                                        <small class="text-danger">Tunggakan: <strong>Rp <?= number_format($overdue,0,',','.') ?></strong></small>
                                      <?php endif; ?>
                                    </div>

                                    <div class="d-flex flex-wrap gap-2">
                                        <?php if ($progress['next_due']): ?>
                                            <a href="upload_bukti.php?loan_id=<?= (int)$loan['id'] ?>" class="btn btn-primary" style="border-radius:10px">
                                              Bayar sekarang
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($progress['progressPct'] >= 80): ?>
                                            <a href="../p2mu_form.php" class="btn btn-soft" style="border-radius:10px">
                                              Ajukan Pinjaman Lagi
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                        <?php else: ?>
                            <!-- BELUM PENCAIRAN: status pengajuan + pesan admin (WA link) -->
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <div class="status mb-2">
                                        <div class="icon"><i class="fas fa-file-alt"></i></div>
                                        <div>
                                            <h5 class="mb-1">Status Pengajuan P2MU</h5>
                                            <div class="h5 mb-2">
                                                Status:
                                                <?php
                                                $status = strtolower($submission['status_pengajuan'] ?? 'menunggu');
                                                if ($status === 'menunggu') {
                                                    echo '<span class="badge badge-warning">Menunggu</span>';
                                                } elseif ($status === 'ditolak') {
                                                    echo '<span class="badge badge-danger">Ditolak</span>';
                                                } elseif ($status === 'diterima') {
                                                    echo '<span class="badge badge-success">Diterima</span>';
                                                } else {
                                                    echo '<span class="badge badge-secondary">'.htmlspecialchars($submission['status_pengajuan'] ?? '-').'</span>';
                                                }
                                                ?>
                                            </div>
                                            <p class="mb-1 text-muted">
                                                Tanggal Pengajuan:
                                                <strong>
                                                    <?= !empty($submission['tanggal_pengajuan']) ? date('d F Y', strtotime($submission['tanggal_pengajuan'])) : '-' ?>
                                                </strong>
                                            </p>
                                            <p class="mb-1 text-muted">
                                                Jumlah Pengajuan:
                                                <strong>Rp <?= number_format($submissionAmount, 0, ',', '.') ?></strong>
                                            </p>
                                            <?php if ($status === 'menunggu'): ?>
                                                <div class="mt-2">
                                                    <a class="btn btn-outline-primary btn-sm" style="border-radius:10px"
                                                    href="view_user_submission.php<?= $submissionId ? ('?id='.$submissionId) : '' ?>">
                                                    <i class="fas fa-eye me-1"></i> Lihat Pengajuan
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <?php if (!empty($submission['admin_message'])): ?>
                                        <div class="alert alert-info mb-0" style="border-radius:12px">
                                            <p class="mb-1">Pesan dari Admin:</p>
                                            <p class="mb-0">
                                                <?php
                                                // ========= WA template link (ditambahkan) =========
                                                // Siapkan data template
                                                $amountInt = 0;
                                                if (!empty($submission['jumlah_pinjam'])) {
                                                    $amountInt = (int)$submission['jumlah_pinjam'];
                                                } elseif (!empty($loan['principal_amount'])) {
                                                    $amountInt = (int)$loan['principal_amount'];
                                                }
                                                $amountStr = $amountInt > 0 ? 'Rp ' . number_format($amountInt, 0, ',', '.') : '-';
                                                $dateStr   = !empty($submission['tanggal_pengajuan'])
                                                            ? date('d M Y', strtotime($submission['tanggal_pengajuan']))
                                                            : '-';

                                                $tplEncoded = rawurlencode(
                                                    "Assalamualaikum,\n".
                                                    "Saya {$username} ingin melakukan pencairan dana.\n".
                                                    "Jumlah pinjaman: {$amountStr}\n".
                                                    "Tanggal pengajuan: {$dateStr}\n".
                                                    "Mohon arahan proses selanjutnya. Terima kasih."
                                                );

                                                // Teks admin yang sudah di-escape
                                                $msg = htmlspecialchars($submission['admin_message']);

                                                // Ganti nomor setelah frasa menjadi link WA + chip style
                                                $msgWithLink = preg_replace_callback(
                                                    '/(nomer whatsapp berikut:\s*)(\+?\d[\d\s\-().]*)/i',
                                                    function($m) use ($tplEncoded){
                                                        $label = trim($m[2]);                             // tampil di UI
                                                        $num   = preg_replace('/\D+/', '', $label);       // hanya digit

                                                        if (strpos($num, '0') === 0) {                    // 08.. -> 62..
                                                            $num = '62' . substr($num, 1);
                                                        }
                                                        if ($num === '') { $num = $label; }               // fallback

                                                        $href = "https://wa.me/{$num}?text={$tplEncoded}";
                                                        // pakai chip dengan ikon WhatsApp
                                                        $chip = '<a class="wa-link" href="'.$href.'" target="_blank" rel="noopener">'.
                                                                '<span class="wa-chip"><i class="fab fa-whatsapp"></i> '.$label.'</span></a>';

                                                        return $m[1] . $chip;
                                                    },
                                                    $msg
                                                );

                                                echo $msgWithLink;
                                                // ========= /WA template link =========
                                                ?>
                                            </p>
                                        </div>
                                        <?php if ($status === 'ditolak'): ?>
                                            <a href="../p2mu_form.php" class="btn btn-warning mt-3">Ajukan Ulang Formulir</a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if ($status === 'ditolak'): ?>
                                            <a href="../p2mu_form.php" class="btn btn-warning mt-3">Ajukan Ulang Formulir</a>
                                        <?php elseif (!$submission): ?>
                                            <p class="mt-2 mb-0">Anda belum mengajukan pinjaman. Ajukan Peminjaman Modal Usaha sekarang.</p>
                                            <a href="../p2mu_form.php" class="btn btn-primary mt-2">Ajukan Pinjaman</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- === Riwayat Angsuran (kiri) + Kontribusi Anda (kanan) === -->
                <div class="row mt-2">
                    <!-- KIRI: Riwayat Angsuran -->
                    <div class="col-md-7 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-chart-line me-2" aria-hidden="true"></i>Riwayat Angsuran</h5>
                                <hr>
                                <div class="chart-wrap">
                                    <canvas id="loanChart" aria-label="Grafik riwayat angsuran"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KANAN: Kontribusi Anda -->
                    <div class="col-md-5 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-lightbulb me-2" aria-hidden="true"></i>Kontribusi Anda</h5>
                                <hr>
                                <div class="d-flex flex-column gap-3">
                                    <a href="testimoni_form.php" class="btn btn-soft btn-lg">
                                        <i class="fas fa-comment-dots me-2" aria-hidden="true"></i>Kirim Testimoni
                                    </a>
                                    <a href="saran_masukan_form.php" class="btn btn-soft btn-lg">
                                        <i class="fas fa-paper-plane me-2" aria-hidden="true"></i>Kirim Saran &amp; Masukan
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- /row -->

            </div><!-- /container-fluid -->
        </div><!-- /content -->

        <?php include 'tambahan/footer.php'; ?>
    </div><!-- /content-wrapper -->
</div><!-- /wrapper -->

<a class="scroll-to-top rounded" href="#page-top" aria-label="Kembali ke atas">
    <i class="fas fa-angle-up" aria-hidden="true"></i>
</a>

<?php include 'tambahan/logout_modal.php'; ?>

<!-- Vendor scripts -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>

<!-- Chart dari data nyata -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const labels         = <?= json_encode($chart_labels ?: []); ?>;
    const remainingData  = <?= json_encode($chart_remaining ?: []); ?>;
    const paidCumData    = <?= json_encode($chart_paid_cum ?: []); ?>;

    const ctx = document.getElementById('loanChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Sisa Saldo',
                    data: remainingData,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, .15)',
                    tension: .3,
                    fill: true
                },
                {
                    label: 'Total Terbayar (kumulatif)',
                    data: paidCumData,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, .12)',
                    tension: .3,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: true } },
            scales: { y: { beginAtZero: true } }
        }
    });
});
</script>
<?php $conn->close(); ?>
</body>
</html>