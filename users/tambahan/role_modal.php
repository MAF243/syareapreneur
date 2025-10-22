<?php
$loggedInUser = $_SESSION['user_id'] ?? null;
?>
<div class="modal fade" id="roleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px">
      <div class="modal-header">
        <h5 class="modal-title" id="roleModalTitle">Aktifkan Peran</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <p id="roleModalDesc" class="mb-3"></p>
        <div class="d-flex gap-2">
          <?php if ($loggedInUser): ?>
            <a id="ctaActivate" href="#" class="btn btn-success fw-bold">Aktifkan Sekarang</a>
          <?php else: ?>
            <a id="ctaRegister" href="#" class="btn btn-success fw-bold">Daftar</a>
            <a id="ctaLogin" href="login.php" class="btn btn-outline-secondary">Login</a>
          <?php endif; ?>
        </div>
        <?php if ($loggedInUser): ?>
          <div class="small text-muted mt-2">Peran baru akan ditambahkan ke akun ini.</div>
        <?php else: ?>
          <div class="small text-muted mt-2">Anda bisa menggunakan email yang sama jika sudah punya akun.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', ()=>{
  // buka modal dari sidebar/link apapun yang punya .role-open
  document.querySelectorAll('.role-open').forEach(el=>{
    el.addEventListener('click', (e)=>{
      e.preventDefault();
      const role = el.dataset.role; // 'peminjam' | 'donatur'
      const title = role==='peminjam' ? 'Aktifkan Peran Peminjam' : 'Aktifkan Peran Donatur';
      const desc  = role==='peminjam'
        ? 'Untuk mengajukan pinjaman, aktifkan peran Peminjam pada akun Anda.'
        : 'Untuk berdonasi, aktifkan peran Donatur pada akun Anda.';
      document.getElementById('roleModalTitle').textContent = title;
      document.getElementById('roleModalDesc').textContent  = desc;

      // tautan aksi
      const activate = document.getElementById('ctaActivate');
      if (activate) activate.href = 'upgrade_role.php?role='+role; // endpoint 1-klik

      const reg = document.getElementById('ctaRegister');
      if (reg) reg.href = role==='peminjam' ? 'register_peminjam.php' : 'register_donatur.php';

      const modal = new bootstrap.Modal(document.getElementById('roleModal'));
      modal.show();
    });
  });
});
</script>
