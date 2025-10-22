<?php
// Pastikan koneksi database diinisialisasi di awal halaman
require_once 'config.php';

// Pastikan koneksi berhasil sebelum melanjutkan
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Inisialisasi variabel agar selalu tersedia, bahkan jika tidak ada hasil
$testimonies = [];
$blogs = [];

// =======================================================
//  1. Ambil data testimoni dari database
// =======================================================
$stmt_testimoni = $conn->prepare("SELECT u.username, u.profile_picture, f.feedback_text FROM user_feedback f JOIN users u ON f.user_id = u.id WHERE f.feedback_type = 'Testimoni' AND f.status = 'published' ORDER BY f.created_at DESC LIMIT 3");
if ($stmt_testimoni) {
    $stmt_testimoni->execute();
    $result_testimoni = $stmt_testimoni->get_result();
    if ($result_testimoni->num_rows > 0) {
        while($row = $result_testimoni->fetch_assoc()) {
            $testimonies[] = $row;
        }
    }
    $stmt_testimoni->close();
}


// =======================================================
//  2. Ambil data blog terbaru dari database
// =======================================================
$stmt_blogs = $conn->prepare("SELECT id, judul, gambar, isi, tanggal_publikasi, jenis_blog FROM blogs WHERE status = 'published' ORDER BY tanggal_publikasi DESC LIMIT 6");
if ($stmt_blogs) {
    $stmt_blogs->execute();
    $result_blogs = $stmt_blogs->get_result();
    if ($result_blogs->num_rows > 0) {
        while($row = $result_blogs->fetch_assoc()) {
            $blogs[] = $row;
        }
    }
    $stmt_blogs->close();
}

// Tutup koneksi setelah selesai
$conn->close();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peminjaman Syariah Berkah</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="gaya.css">
</head>
<body>
    <header class="site-header">
        <div class="container nav-container">
            <a class="brand" href="#">
            <span class="brand-mark"></span>
            <span class="brand-name">Peminjaman Syariah</span>
            </a>

            <button class="nav-toggle" aria-label="Buka menu">
            <span class="hamburger"></span>
            </button>

            <nav class="nav">
            <ul class="nav-list">
                <li><a href="#keunggulan">Keunggulan</a></li>
                <li><a href="#alur">Alur</a></li>
                <li><a href="#testimoni">Testimoni</a></li>
                <li><a href="#donasi">Donasi</a></li>
                <li><a href="#blog">Blog</a></li>
            </ul>
            <div class="nav-cta">
                <a href="users/register.php" class="btn btn-outline">Registrasi</a>
                <a href="users/login.php" class="btn btn-primary">Login</a>
            </div>
            </nav>
        </div>
    </header>
    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-touch="true" data-bs-interval="6000">
        <!-- indikator bulat -->
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
        </div>

        <div class="carousel-inner">
            <!-- Slide 1: konten hero kamu (center, full-height) -->
            <div class="carousel-item active">
                <section class="hero hero-slide hero-slide-1">
                    <div class="container">
                    <div class="hero-content">
                        <h1>Solusi Finansial Halal dan Transparan</h1>
                        <p>Dapatkan pembiayaan sesuai prinsip syariah. Tanpa riba, proses cepat, dan akad yang jelas. Wujudkan impian Anda dengan keberkahan.</p>
                        <a href="p2mu_form.php" class="cta-button">Ajukan Sekarang</a>
                    </div>
                    </div>
                </section>
            </div>

            <!-- Slide 2: ajakan donasi -->
            <div class="carousel-item">
                <section class="hero hero-slide hero-slide-2">
                    <div class="container">
                    <div class="hero-content">
                        <h1>Dukung UMKM Tumbuh Tanpa Riba</h1>
                        <p>Donasi Anda menumbuhkan ekonomi umat. Transparan, amanah, dan berdampak langsung.</p>
                        <a href="donation_form.php" class="cta-button cta-outline">Donasi Sekarang</a>
                    </div>
                    </div>
                </section>
            </div>

            <!-- Slide 3: dorong ke blog -->
            <div class="carousel-item">
                <section class="hero hero-slide hero-slide-3">
                    <div class="container">
                    <div class="hero-content">
                        <h1>Belajar Ekonomi Syariah</h1>
                        <p>Baca artikel & berita seputar syariah, keuangan halal, dan kisah penerima manfaat.</p>
                        <a href="articles.php" class="cta-button">Lihat Artikel</a>
                    </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- panah navigasi -->
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev" aria-label="Sebelumnya">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next" aria-label="Berikutnya">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
        </button>
    </div>

    <section class="features" id="keunggulan">
        <div class="container">
            <h2>Keunggulan Peminjaman Syariah Kami</h2>
            <div class="features-grid">
                <div class="feature-item">
                    <img src="https://via.placeholder.com/60.png?text=✨" alt="Tanpa Riba">
                    <h3>Tanpa Riba</h3>
                    <p>Pembiayaan dilakukan berdasarkan prinsip syariah tanpa adanya bunga (riba) yang memberatkan, memastikan transaksi Anda halal dan berkah.</p>
                </div>
                <div class="feature-item">
                    <img src="https://via.placeholder.com/60.png?text=🚀" alt="Proses Cepat">
                    <h3>Proses Cepat & Mudah</h3>
                    <p>Pengajuan pembiayaan dapat dilakukan secara online dengan dokumen yang minim. Kami memastikan prosesnya cepat dan transparan.</p>
                </div>
                <div class="feature-item">
                    <img src="https://via.placeholder.com/60.png?text=🤝" alt="Akad Jelas">
                    <h3>Akad Jelas & Transparan</h3>
                    <p>Setiap transaksi didukung dengan akad yang jelas dan sesuai syariat. Tidak ada biaya tersembunyi, semua transparan sejak awal.</p>
                </div>
            </div>
        </div>
    </section>
    <section class="steps" id="alur">
        <div class="container">
            <h2 class="section-title">Alur Peminjaman yang Mudah</h2>
            <div class="steps-grid">
                <div class="step-item">
                    <div class="step-number">1</div>
                    <h3>Isi Formulir Online</h3>
                    <p>Mulai dengan mengisi formulir pengajuan singkat di website kami. Siapkan dokumen-dokumen dasar yang diperlukan.</p>
                </div>
                <div class="step-item">
                    <div class="step-number">2</div>
                    <h3>Verifikasi & Akad</h3>
                    <p>Tim kami akan melakukan verifikasi data. Setelah disetujui, kita akan melakukan akad sesuai prinsip syariah.</p>
                </div>
                <div class="step-item">
                    <div class="step-number">3</div>
                    <h3>Dana Dicairkan</h3>
                    <p>Setelah akad selesai, dana pembiayaan akan segera dicairkan ke rekening Anda. Amanah dan cepat!</p>
                </div>
            </div>
        </div>
    </section>
    <section class="testimoni" id="testimoni">
        <div class="container">
            <h2>Pendapat dari para pengguna yang telah merasakan manfaat Peminjaman Syariah Berkah.</h2>
            
            <div class="testimoni-list">
                <?php if (!empty($testimonies)): ?>
                    <?php foreach ($testimonies as $row): ?>
                        <div class="testimoni-item">
                            <img src="<?php echo htmlspecialchars($row['profile_picture']); ?>" alt="Foto Profil" class="testimoni-profile-photo">
                            <p>"<?php echo htmlspecialchars($row['feedback_text']); ?>"</p>
                            <p class="testimoni-name">- <?php echo htmlspecialchars($row['username']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Belum ada testimoni yang diterbitkan.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <section class="donation" id="donasi">
    <div class="container">
        <div class="donation-content">
            <h2>Dukung Pergerakan Ekonomi Mikro Syariah!</h2>
            <p>Jadilah bagian dari kebaikan. Donasi Anda membantu para pelaku usaha mikro mendapatkan modal tanpa riba, mewujudkan keberkahan dalam setiap transaksi.</p>
            <a href="donation_form.php" class="cta-button">Donasi Sekarang</a>
        </div>
    </div>
    </section>
    <section class="blog" id="blog">
        <div class="container">
            <h2>Berita & Artikel</h2>
            <div class="blog-grid">
                <?php if (!empty($blogs)): ?>
                    <?php foreach ($blogs as $blog): ?>
                        <div class="blog-item">
                            <?php if (!empty($blog['gambar'])): ?>
                                <img src="<?php echo htmlspecialchars($blog['gambar']); ?>" alt="Gambar Blog" class="blog-image">
                            <?php endif; ?>
                            <h3><?php echo htmlspecialchars($blog['judul']); ?></h3>
                            <p class="blog-meta">
                                <?php echo htmlspecialchars($blog['jenis_blog']); ?> | 
                                <?php echo date('d M Y', strtotime($blog['tanggal_publikasi'])); ?>
                            </p>
                            <p><?php echo substr(htmlspecialchars(strip_tags($blog['isi'])), 0, 150) . '...'; ?></p>
                            
                            <?php
                            // Tentukan URL detail berdasarkan jenis konten
                            $detail_url = '';
                            if ($blog['jenis_blog'] === 'artikel') {
                                $detail_url = 'articles_detail.php';
                            } elseif ($blog['jenis_blog'] === 'berita') {
                                $detail_url = 'news_detail.php';
                            }
                            ?>
                            <a href="<?php echo $detail_url; ?>?id=<?php echo $blog['id']; ?>" class="read-more btn btn-link">Baca Selengkapnya</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center">Belum ada artikel atau berita yang diterbitkan.</p>
                <?php endif; ?>
            </div>
            <div class="blog-links">
            <a href="blogs.php?page=berita" class="btn btn-primary">Lihat Berita</a>
            <a href="blogs.php" class="btn btn-primary">Lihat Artikel</a>
        </div>
        </div>
    </section>
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-info">
                    <h3>Peminjaman Syariah Berkah</h3>
                    <p>Solusi finansial halal dan tepercaya untuk mewujudkan impian Anda. Proses mudah, akad jelas, tanpa riba.</p>
                </div>
                <div class="footer-links">
                    <h3>Tautan Cepat</h3>
                    <ul>
                        <li><a href="#keunggulan">Keunggulan</a></li>
                        <li><a href="#alur">Alur Peminjaman</a></li>
                        <li><a href="#testimoni">Testimoni</a></li>
                        <li><a href="#">Ajukan Sekarang</a></li>
                        <li><a href="#donasi">Donasi</a></li>
                        <li><a href="#blog">Blog</a></li>
                    </ul>
                </div>
                <div class="footer-social">
                    <h3>Ikuti Kami</h3>
                    <ul>
                        <li><a href="#">Facebook</a></li>
                        <li><a href="#">Instagram</a></li>
                        <li><a href="#">YouTube</a></li>
                    </ul>
                </div>
                <div class="footer-contact">
                    <h3>Hubungi Kami</h3>
                    <ul>
                        <li><a href="#">CS</a></li>
                        <li><a href="#">Email</a></li>
                        <li><a href="#">WhatsApp</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Peminjaman Syariah Berkah. Semua Hak Cipta Dilindungi.</p>
            </div>
        </div>
    </footer>   
    <script>
        document.addEventListener('DOMContentLoaded', function(){
        const header = document.querySelector('.site-header');
        const toggle = document.querySelector('.nav-toggle');

        // Toggle menu mobile
        toggle?.addEventListener('click', ()=> header.classList.toggle('nav-open'));

        // Tutup menu saat klik link
        const navLinks = document.querySelectorAll('.nav-list a[href^="#"]');
        navLinks.forEach(a => a.addEventListener('click', ()=> header.classList.remove('nav-open')));

        // Shadow saat scroll
        const onScroll = () => header.classList.toggle('scrolled', window.scrollY > 4);
        onScroll(); window.addEventListener('scroll', onScroll);

        // ScrollSpy: aktifkan link sesuai section yang terlihat (kelas .active dinetralkan di CSS)
        const sections = [...navLinks].map(a => document.querySelector(a.getAttribute('href'))).filter(Boolean);
        const setActive = (id) => {
            navLinks.forEach(a => a.classList.toggle('active', a.getAttribute('href') === '#' + id));
        };
        const io = new IntersectionObserver((entries)=>{
            entries.forEach(en => { if (en.isIntersecting) setActive(en.target.id); });
        }, { rootMargin: `-${getComputedStyle(document.documentElement).getPropertyValue('--header-h').trim()} 0px -60% 0px`, threshold: 0.1 });
        sections.forEach(sec => io.observe(sec));
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>