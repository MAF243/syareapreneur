<?php
$isPeminjam = !empty($_SESSION['peminjam']);
$isDonatur  = !empty($_SESSION['donatur']);
?>
<ul class="navbar-nav bg-success sidebar sidebar-dark accordion sidebar-pro" id="accordionSidebar">

    <!-- Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-hand-holding-usd"></i>
        </div>
        <div class="sidebar-brand-text mx-3">Berkah</div>
    </a>

    <hr class="sidebar-divider my-0">

    <li class="nav-item active">
        <a class="nav-link" href="dashboard.php">
            <i class="fas fa-fw fa-tachometer-alt"></i><span>Dashboard</span>
        </a>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Fitur</div>

    <!-- Pengajuan Pinjaman -->
    <li class="nav-item">
        <?php if ($isPeminjam): ?>
            <a class="nav-link" href="../p2mu_form.php">
                <i class="fas fa-fw fa-file-alt"></i><span>Pengajuan Pinjaman</span>
            </a>
        <?php else: ?>
            <a class="nav-link" href="#" data-role="peminjam"
               data-toggle="modal" data-target="#roleModal"  <!-- BS4 -->
               data-bs-toggle="modal" data-bs-target="#roleModal"> <!-- BS5 -->
                <i class="fas fa-fw fa-file-alt"></i><span>Pengajuan Pinjaman</span>
            </a>
            <div class="locked-hint">Klik untuk aktifkan peran Peminjam.</div>
        <?php endif; ?>
    </li>

    <!-- Donasi -->
    <li class="nav-item">
        <?php if ($isDonatur): ?>
            <a class="nav-link" href="../donation_form.php">
                <i class="fas fa-fw fa-hand-holding-usd"></i><span>Donasi</span>
            </a>
        <?php else: ?>
            <a class="nav-link" href="#" data-role="donatur"
               data-toggle="modal" data-target="#roleModal"
               data-bs-toggle="modal" data-bs-target="#roleModal">
                <i class="fas fa-fw fa-hand-holding-usd"></i><span>Donasi</span>
            </a>
            <div class="locked-hint">Klik untuk aktifkan peran Donatur.</div>
        <?php endif; ?>
    </li>

    <!-- Testimoni -->
    <li class="nav-item">
        <a class="nav-link" href="testimoni_form.php">
            <i class="fas fa-fw fa-comments"></i><span>Testimoni</span>
        </a>
    </li>

    <!-- Saran & Masukan -->
    <li class="nav-item">
        <a class="nav-link" href="saran_masukan_form.php">
            <i class="fas fa-fw fa-lightbulb"></i><span>Saran & Masukan</span>
        </a>
    </li>

    <hr class="sidebar-divider d-none d-md-block">

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>
</ul>

<!-- Modal Pilih Peran -->
<div class="modal fade" id="roleModal" tabindex="-1" role="dialog" aria-labelledby="roleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content" style="border-radius:16px">
      <div class="modal-header">
        <h5 class="modal-title" id="roleModalLabel">Aktifkan Peran</h5>
        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">×</span>
        </button>
      </div>
      <div class="modal-body">
        <p id="roleModalText" class="mb-3">Pilih peran untuk melanjutkan.</p>
        <a id="btnDaftarRole" href="#" class="btn btn-success btn-block" style="border-radius:12px;font-weight:700">
          Lanjutkan
        </a>
        <p class="mt-3 text-muted" style="font-size:12px">
          Anda bisa menggunakan akun yang sama untuk kedua peran.
        </p>
      </div>
    </div>
  </div>
</div>

<script>
// Fallback JS agar modal selalu jalan (BS4/BS5)
(function(){
  function showRoleModal(role){
    var t = document.getElementById('roleModalText');
    var b = document.getElementById('btnDaftarRole');
    if (!t || !b) return;

    if (role === 'peminjam'){
      t.innerHTML = 'Fitur <strong>Pengajuan Pinjaman</strong> khusus untuk <strong>Peminjam</strong>. Aktifkan peran ini untuk melanjutkan.';
      b.textContent = 'Daftar sebagai Peminjam';
      b.href = 'register_peminjam.php';
    } else {
      t.innerHTML = 'Fitur <strong>Donasi</strong> khusus untuk <strong>Donatur</strong>. Aktifkan peran ini untuk melanjutkan.';
      b.textContent = 'Daftar sebagai Donatur';
      b.href = 'register_donatur.php';
    }

    if (window.bootstrap && bootstrap.Modal) {
      new bootstrap.Modal(document.getElementById('roleModal')).show();
    } else if (window.jQuery && $('#roleModal').modal) {
      $('#roleModal').modal('show');
    }
  }

  document.addEventListener('click', function(e){
    var a = e.target.closest('a[data-role]');
    if (!a) return;
    e.preventDefault();
    showRoleModal(a.getAttribute('data-role'));
  });
})();
</script>