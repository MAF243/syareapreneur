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

// 1. Ambil data pribadi yang sudah ada
$user_data = [
    'full_name' => '',
    'address' => '',
    'phone_number' => '',
    'birth_date' => '',
    'nik' => '',
    'gender' => ''
];
$stmt_details = $conn->prepare("SELECT * FROM user_details WHERE user_id = ?");
$stmt_details->bind_param("i", $user_id);
$stmt_details->execute();
$result_details = $stmt_details->get_result();
$details_exists = $result_details->num_rows > 0;
if ($details_exists) {
    $user_data = $result_details->fetch_assoc();
}
$stmt_details->close();

// 2. Ambil data foto profil yang sudah ada
$profile_picture_path = '';
$stmt_profile = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmt_profile->bind_param("i", $user_id);
$stmt_profile->execute();
$result_profile = $stmt_profile->get_result();
if ($row = $result_profile->fetch_assoc()) {
    $profile_picture_path = $row['profile_picture'];
}
$stmt_profile->close();

// 3. Proses pengiriman formulir
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $has_error = false;

    // A. Proses data pribadi
    $full_name = $_POST['full_name'];
    $address = $_POST['address'];
    $phone_number = $_POST['phone_number'];
    $birth_date = $_POST['birth_date'];
    $nik = $_POST['nik'];
    $gender = $_POST['gender'];

    if ($details_exists) {
        $stmt_update = $conn->prepare("UPDATE user_details SET full_name=?, address=?, phone_number=?, birth_date=?, nik=?, gender=? WHERE user_id=?");
        $stmt_update->bind_param("ssssssi", $full_name, $address, $phone_number, $birth_date, $nik, $gender, $user_id);
        if (!$stmt_update->execute()) {
            $message .= "Terjadi kesalahan saat memperbarui data pribadi. ";
            $has_error = true;
        }
        $stmt_update->close();
    } else {
        $stmt_insert = $conn->prepare("INSERT INTO user_details (user_id, full_name, address, phone_number, birth_date, nik, gender) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("issssss", $user_id, $full_name, $address, $phone_number, $birth_date, $nik, $gender);
        if (!$stmt_insert->execute()) {
            $message .= "Terjadi kesalahan saat menyimpan data pribadi. ";
            $has_error = true;
        }
        $stmt_insert->close();
    }

    // B. Proses unggah foto profil
    if (isset($_FILES["profile_picture"]) && $_FILES["profile_picture"]["error"] == 0) {
        $target_dir = "../uploads/profile_pictures/";
        $file_info = pathinfo($_FILES["profile_picture"]["name"]);
        $file_extension = strtolower($file_info['extension']);
        $unique_filename = uniqid('profile_', true) . '.' . $file_extension;
        $target_file = $target_dir . $unique_filename;
        $uploadOk = 1;

        $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
        if ($check === false) {
            $message .= "File yang diunggah bukan gambar. ";
            $uploadOk = 0;
        }
        if ($_FILES["profile_picture"]["size"] > 5000000) {
            $message .= "Ukuran file terlalu besar. ";
            $uploadOk = 0;
        }
        if ($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg" && $file_extension != "gif") {
            $message .= "Hanya JPG, JPEG, PNG & GIF yang diizinkan. ";
            $uploadOk = 0;
        }

        if ($uploadOk == 1) {
            if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                // Hapus gambar lama jika ada
                if (!empty($profile_picture_path) && file_exists('../' . $profile_picture_path)) {
                    unlink('../' . $profile_picture_path);
                }

                // Simpan path gambar baru ke database
                $relative_path = 'uploads/profile_pictures/' . $unique_filename;
                $stmt_update_photo = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt_update_photo->bind_param("si", $relative_path, $user_id);
                if (!$stmt_update_photo->execute()) {
                    $message .= "Terjadi kesalahan saat menyimpan foto profil. ";
                    $has_error = true;
                }
                $stmt_update_photo->close();
                $profile_picture_path = $relative_path; // Perbarui path untuk ditampilkan
            } else {
                $message .= "Terjadi kesalahan saat mengunggah file. ";
                $has_error = true;
            }
        } else {
            $has_error = true;
        }
    }

    if (!$has_error) {
        $message = "Data dan foto profil berhasil disimpan.";
        $message_type = 'success';
    } else {
        $message_type = 'danger';
        // Pesan error sudah diisi di atas
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Lengkapi Profil</title>
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
                        <h1 class="h3 mb-4" style="color:#0b342f">Lengkapi Profil Anda</h1>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold" style="color:var(--primary)">Formulir Data Pribadi & Foto Profil</h6>
                        </div>
                        <div class="card-body">
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data">

                                <div class="form-group text-center">
                                    <label>Foto Profil Saat Ini:</label>
                                    <div>
                                        <?php
                                        $current_photo_path = !empty($profile_picture_path) ? '../' . htmlspecialchars($profile_picture_path) : 'https://via.placeholder.com/150.png?text=No+Photo';
                                        ?>
                                        <img src="<?php echo $current_photo_path; ?>" alt="Foto Profil" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                    </div>
                                    <label for="profile_picture">Unggah Foto Profil Baru</label>
                                    <input type="file" class="form-control-file" name="profile_picture" id="profile_picture">
                                    <small class="form-text text-muted">Maksimal 5MB. Format: JPG, JPEG, PNG, GIF.</small>
                                </div>
                                <hr>
                                <div class="form-group">
                                    <label for="full_name">Nama Lengkap</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="address">Alamat</label>
                                    <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($user_data['address']); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="phone_number">Nomor Telepon</label>
                                    <input type="tel" class="form-control" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user_data['phone_number']); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="birth_date">Tanggal Lahir</label>
                                    <input type="date" class="form-control" id="birth_date" name="birth_date" value="<?php echo htmlspecialchars($user_data['birth_date']); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="nik">NIK (Nomor Induk Kependudukan)</label>
                                    <input type="text" class="form-control" id="nik" name="nik" value="<?php echo htmlspecialchars($user_data['nik']); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="gender">Jenis Kelamin</label>
                                    <select class="form-control" id="gender" name="gender">
                                        <option value="">Pilih...</option>
                                        <option value="Laki-laki" <?php echo ($user_data['gender'] == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                                        <option value="Perempuan" <?php echo ($user_data['gender'] == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
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
</body>

</html>
<?php $conn->close(); ?>