<?php
$profile_picture_path = 'https://via.placeholder.com/60x60.png?text=User';
$user_id = $_SESSION['user_id'] ?? null;

if ($user_id) {
    if ($conn && $conn->ping()) {
        if ($stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id=?")) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            if ($row = $stmt->get_result()->fetch_assoc()) {
                if (!empty($row['profile_picture'])) {
                    $profile_picture_path = '../' . htmlspecialchars($row['profile_picture']);
                }
            }
            $stmt->close();
        }
    }
}

/* ===========================
   Notifikasi H-1 angsuran  (JOIN -> loans, bukan li.user_id)
=========================== */
$notif_items = [];
$notif_count = 0;
if ($user_id && $conn && $conn->ping()) {
    $sql = "SELECT li.id, li.due_date, li.amount
            FROM loan_installments li
            JOIN loans l ON l.id = li.loan_id
            WHERE l.user_id = ?
              AND li.status = 'unpaid'
              AND li.due_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
            ORDER BY li.due_date ASC";
    if ($st = $conn->prepare($sql)) {
        $st->bind_param("i", $user_id);
        $st->execute();
        $res = $st->get_result();
        while ($r = $res->fetch_assoc()) {
            $notif_items[] = $r;
        }
        $st->close();
    }
    $notif_count = count($notif_items);
}
?>
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3" aria-label="Toggle sidebar">
        <i class="fa fa-bars"></i>
    </button>

    <ul class="navbar-nav ml-auto">

        <!-- Notifikasi -->
        <li class="nav-item dropdown no-arrow mx-1">
            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button"
               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Notifikasi">
                <i class="fas fa-bell fa-fw" aria-hidden="true"></i>
                <?php if ($notif_count > 0): ?>
                    <span class="badge badge-danger badge-counter" aria-live="polite"><?= $notif_count; ?></span>
                <?php endif; ?>
            </a>
            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
                 aria-labelledby="alertsDropdown" style="min-width: 320px">
                <h6 class="dropdown-header">Pengingat Pembayaran (H-1)</h6>

                <?php if ($notif_count === 0): ?>
                    <a class="dropdown-item d-flex align-items-center" href="#">
                        <div class="mr-3">
                            <div class="icon-circle bg-secondary">
                                <i class="fas fa-check text-white"></i>
                            </div>
                        </div>
                        <div>
                            <span class="small text-gray-500">Tidak ada pengingat</span>
                            <div>Tidak ada angsuran jatuh tempo besok.</div>
                        </div>
                    </a>
                <?php else: ?>
                    <?php foreach ($notif_items as $n): ?>
                        <a class="dropdown-item d-flex align-items-center" href="history_installments.php">
                            <div class="mr-3">
                                <div class="icon-circle bg-warning">
                                    <i class="fas fa-exclamation-triangle text-white"></i>
                                </div>
                            </div>
                            <div>
                                <span class="small text-gray-500">
                                    Jatuh Tempo: <?= date('d M Y', strtotime($n['due_date'])); ?>
                                </span>
                                <div>Tagihan sebesar <strong>Rp <?= number_format($n['amount'],0,',','.'); ?></strong></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>

                <a class="dropdown-item text-center small text-gray-500" href="history_installments.php">Lihat semua</a>
            </div>
        </li>

        <div class="topbar-divider d-none d-sm-block"></div>

        <!-- User -->
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Menu pengguna">
                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                    <?= htmlspecialchars($_SESSION['username']); ?>
                </span>
                <img class="img-profile rounded-circle" src="<?= $profile_picture_path; ?>" alt="Foto profil">
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                <a class="dropdown-item" href="personal_data_form.php">
                    <i class="fas fa-user-edit fa-sm fa-fw mr-2 text-gray-400"></i> Lengkapi Profil
                </a>
                <a class="dropdown-item" href="history_submissions.php">
                    <i class="fas fa-file-alt fa-sm fa-fw mr-2 text-gray-400"></i> Riwayat Pengajuan
                </a>
                <a class="dropdown-item" href="history_donations.php">
                    <i class="fas fa-hand-holding-usd fa-sm fa-fw mr-2 text-gray-400"></i> Riwayat Donasi
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Keluar
                </a>
            </div>
        </li>
    </ul>
</nav>