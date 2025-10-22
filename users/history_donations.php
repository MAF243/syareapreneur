<?php
session_start();
require_once '../config.php';

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$donations = [];

// Ambil semua riwayat donasi pengguna
$stmt_donations = $conn->prepare("SELECT * FROM donations WHERE user_id = ? ORDER BY tanggal_donasi DESC");
$stmt_donations->bind_param("i", $user_id);
$stmt_donations->execute();
$result_donations = $stmt_donations->get_result();

if ($result_donations->num_rows > 0) {
    while ($row = $result_donations->fetch_assoc()) {
        $donations[] = $row;
    }
}
$stmt_donations->close();

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Riwayat Donasi">
    <meta name="author" content="Tim Anda">
    <title>Riwayat Donasi</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
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
                        <h1 class="h3 mb-4" style="color:#0b342f">Riwayat Donasi</h1>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold" style="color:var(--primary)">Daftar Donasi</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID Donasi</th>
                                            <th>Nominal Donasi</th>
                                            <th>Tanggal Donasi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($donations)): ?>
                                            <?php foreach ($donations as $donation): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($donation['id']); ?></td>
                                                    <td>Rp <?php echo number_format($donation['nominal_donasi'], 0, ',', '.'); ?></td>
                                                    <td><?php echo date('d F Y', strtotime($donation['tanggal_donasi'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center">Belum ada riwayat donasi.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

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