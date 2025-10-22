<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$submission_id = $_GET['id'] ?? null;
$submission = null;
$message = '';
$message_type = '';

if (is_null($submission_id)) {
    $message = "ID pengajuan tidak ditemukan.";
    $message_type = 'danger';
} else {
    $stmt_submission = $conn->prepare("SELECT * FROM p2mu_forms WHERE id = ? AND user_id = ?");
    $stmt_submission->bind_param("ii", $submission_id, $user_id);
    $stmt_submission->execute();
    $result_submission = $stmt_submission->get_result();

    if ($result_submission->num_rows > 0) {
        $submission = $result_submission->fetch_assoc();
    } else {
        $message = "Pengajuan tidak ditemukan atau Anda tidak memiliki akses.";
        $message_type = 'danger';
    }
    $stmt_submission->close();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Detail Pengajuan</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="dashboard-theme.css" rel="stylesheet">
    <style>
        .detail-item {
            margin-bottom: 1.5rem;
        }

        .detail-item h6 {
            color: var(--primary);
            font-weight: bold;
        }
    </style>
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
                        <h1 class="h3 mb-0" style="color:#0b342f">Detail Pengajuan</h1>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if ($submission): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold" style="color:var(--primary)">Informasi Pengajuan</h6>
                            </div>
                            <div class="card-body">
                                <div class="detail-item">
                                    <h6>Status Pengajuan:</h6>
                                    <?php
                                    $status = strtolower($submission['status_pengajuan']);
                                    $alasan_ditolak = $submission['alasan_ditolak'] ?? '';
                                    if ($status === 'menunggu') {
                                        echo '<span class="badge badge-warning">Menunggu</span>';
                                    } elseif ($status === 'ditolak') {
                                        echo '<span class="badge badge-danger">Ditolak</span>';
                                    } elseif ($status === 'diterima') {
                                        echo '<span class="badge badge-success">Diterima</span>';
                                    } else {
                                        echo '<span class="badge badge-secondary">' . htmlspecialchars($submission['status_pengajuan']) . '</span>';
                                    }
                                    ?>
                                </div>
                                <div class="detail-item">
                                    <h6>Tanggal Pengajuan</h6>
                                    <p><?php echo date('d F Y', strtotime($submission['tanggal_pengajuan'])); ?></p>
                                </div>
                                <div class="detail-item">
                                    <h6>Jumlah Pinjaman</h6>
                                    <p>Rp <?php echo number_format($submission['jumlah_pinjam'], 2, ',', '.'); ?></p>
                                </div>
                                <?php if (!empty($submission['admin_message'])): ?>
                                    <div class="detail-item">
                                        <h6>Pesan</h6>
                                        <p>
                                            <?php
                                            $message_with_link = preg_replace('/(nomer whatsapp berikut:\s*)(\+?\d+)/i', '$1<a href="https://wa.me/$2" target="_blank" rel="noopener">$2</a>', htmlspecialchars($submission['admin_message']));
                                            echo $message_with_link;
                                            ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold" style="color:var(--primary)">Data Pemohon</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Nama Lengkap :</strong> <?php echo htmlspecialchars($submission['nama']); ?></p>
                                        <p><strong>Email :</strong> <?php echo htmlspecialchars($submission['email']); ?></p>
                                        <p><strong>No. KTP / SIM :</strong> <?php echo htmlspecialchars($submission['no_ktp']); ?></p>
                                        <p><strong>TTL :</strong> <?php echo htmlspecialchars($submission['ttl']); ?></p>
                                        <p><strong>Status Pernikahan :</strong> <?php echo htmlspecialchars($submission['status_nikah']); ?></p>
                                        <p><strong>Jumlah Anak :</strong> <?php echo htmlspecialchars($submission['jml_anak']); ?></p>
                                        <p><strong>Alamat:</strong> <?php echo nl2br(htmlspecialchars($submission['alamat'])); ?></p>
                                        <p><strong>No. HP / Telp :</strong> <?php echo htmlspecialchars($submission['hp']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Pekerjaan / Status:</strong> <?php echo htmlspecialchars($submission['pekerjaan']); ?></p>
                                        <p><strong>Nama Perusahaan / Usaha:</strong> <?php echo htmlspecialchars($submission['nama_usaha']); ?></p>
                                        <p><strong>Omset Bulanan:</strong> Rp <?php echo number_format($submission['omset'], 0, ',', '.'); ?></p>
                                        <p><strong>Penghasilan Pasangan:</strong> Rp <?php echo number_format($submission['penghasilan_pasangan'], 0, ',', '.'); ?></p>
                                        <p><strong>Pengeluaran Bulanan:</strong> Rp <?php echo number_format($submission['pengeluaran_bulanan'], 0, ',', '.'); ?></p>
                                        <p><strong>Tabungan Bulanan:</strong> Rp <?php echo number_format($submission['menabung'], 0, ',', '.'); ?></p>
                                        <p><strong>Pinjaman/Cicilan Lain (1):</strong> <?php echo htmlspecialchars($submission['pinjaman1_desc']); ?> (Rp <?php echo number_format($submission['pinjaman1_nominal'], 0, ',', '.'); ?>)</p>
                                        <p><strong>Pinjaman/Cicilan Lain (2):</strong> <?php echo htmlspecialchars($submission['pinjaman2_desc']); ?> (Rp <?php echo number_format($submission['pinjaman2_nominal'], 0, ',', '.'); ?>)</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold" style="color:var(--primary)">Data Keuangan & Aset</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Nama Bank:</strong> <?php echo htmlspecialchars($submission['bank_name']); ?></p>
                                        <p><strong>No. Rekening:</strong> <?php echo htmlspecialchars($submission['bank_no']); ?></p>
                                        <p><strong>Saldo Rata-Rata 3 Bulan:</strong> Rp <?php echo number_format($submission['saldo_avg'], 0, ',', '.'); ?></p>
                                        <p><strong>Aset Sudah Lunas:</strong> <?php echo nl2br(htmlspecialchars($submission['aset_lunas'])); ?></p>
                                        <p><strong>Aset Belum Lunas:</strong> <?php echo nl2br(htmlspecialchars($submission['aset_belum'])); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Nama Pasangan (Persetujuan):</strong> <?php echo htmlspecialchars($submission['nama_pasangan']); ?></p>
                                        <p><strong>No. KTP Pasangan:</strong> <?php echo htmlspecialchars($submission['ktp_pasangan']); ?></p>
                                        <p><strong>Nama Penjamin:</strong> <?php echo htmlspecialchars($submission['nama_penjamin']); ?></p>
                                        <p><strong>No. KTP Penjamin:</strong> <?php echo htmlspecialchars($submission['ktp_penjamin']); ?></p>
                                        <p><strong>No. HP Penjamin:</strong> <?php echo htmlspecialchars($submission['telp_penjamin']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold" style="color:var(--primary)">Pernyataan & Komitmen</h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Modal Usaha 100% Digunakan untuk Usaha:</strong> <?php echo ($submission['pstmt_modal'] == 1) ? '<span class="badge badge-success">Ya</span>' : '<span class="badge badge-danger">Tidak</span>'; ?></p>
                                <p><strong>Mengembalikan Pinjaman Sesuai Jadwal:</strong> <?php echo ($submission['pstmt_planning'] == 1) ? '<span class="badge badge-success">Ya</span>' : '<span class="badge badge-danger">Tidak</span>'; ?></p>
                                <p><strong>Menginformasikan Keterlambatan:</strong> <?php echo ($submission['pstmt_komunikasi'] == 1) ? '<span class="badge badge-success">Ya</span>' : '<span class="badge badge-danger">Tidak</span>'; ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <p class="mb-0">Tidak ada data pengajuan yang valid untuk ditampilkan.</p>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php include 'tambahan/footer.php'; ?>
        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <?php include 'tambahan/logout_modal.php'; ?>
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    <?php $conn->close(); ?>
</body>

</html>