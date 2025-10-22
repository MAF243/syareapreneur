<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $feedback_type = $_POST['feedback_type'] ?? '';
    $feedback_text = $_POST['feedback_text'] ?? '';

    if (empty($feedback_type) || empty($feedback_text) || ($feedback_type != 'Saran' && $feedback_type != 'Masukan')) {
        $message = "Mohon pilih kategori yang valid dan lengkapi pesan Anda.";
        $message_type = 'error';
    } else {
        $sql = "INSERT INTO user_feedback (user_id, feedback_type, feedback_text) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                feedback_type = VALUES(feedback_type), 
                feedback_text = VALUES(feedback_text),
                updated_at = NOW()";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $user_id, $feedback_type, $feedback_text);

        if ($stmt->execute()) {
            // Periksa apakah ada baris yang terpengaruh (inserted atau updated)
            if ($stmt->affected_rows > 0) {
                $message = "Terima kasih atas masukan dan saran Anda. Kami akan segera meninjaunya.";
                $message_type = 'success';
            } else {
                // Kasus ini terjadi jika data yang dikirim sama persis dengan yang sudah ada di database
                $message = "Tidak ada perubahan pada saran dan masukan Anda.";
                $message_type = 'info';
            }
        } else {
            $message = "Terjadi kesalahan. Silakan coba lagi. Error: " . $stmt->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Saran dan Masukan</title>
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
                        <h1 class="h3 mb-0" style="color:#0b342f">Kirim Saran & Masukan</h1>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                                <div class="form-group">
                                    <label for="feedback_type">Pilih Kategori</label>
                                    <select name="feedback_type" id="feedback_type" class="form-control" required>
                                        <option value="" disabled selected>Pilih...</option>
                                        <option value="Saran">Saran</option>
                                        <option value="Masukan">Masukan</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="feedback_text">Pesan Anda</label>
                                    <textarea name="feedback_text" id="feedback_text" class="form-control" rows="5" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Kirim</button>
                            </form>
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