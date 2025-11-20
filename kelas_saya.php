<?php
session_start();
include 'database.php';

if (!isset($_SESSION['guru'])) {
  header("Location: login.php");
  exit();
}

$nama = $_SESSION['guru'];
$query_guru = $conn->query("SELECT * FROM guru WHERE nama_guru = '$nama' LIMIT 1");
$data_guru = $query_guru->fetch_assoc();
$id_guru = $data_guru['id'] ?? 0;
$foto = !empty($data_guru['foto']) ? $data_guru['foto'] : null;

$notif_result = $conn->query("SELECT * FROM notifikasi WHERE id_guru = $id_guru ORDER BY tanggal DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelas Saya | SMPN 14</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
  /* === ANIMASI MASUK HALAMAN === */
  @keyframes fadeSlideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .page-enter { animation: fadeSlideUp 0.7s ease-out; }

  /* Animasi fade-in konten */
  .fade-in {
    opacity: 0;
    transform: translateY(10px);
    animation: fadeInEl 0.6s ease forwards;
  }
  @keyframes fadeInEl { to { opacity: 1; transform: translateY(0); } }

  .transition-smooth { transition: all 0.3s ease-in-out; }
  </style>
</head>

<body class="bg-gray-100 font-sans page-enter">
<div class="flex min-h-screen">

  <!-- Sidebar -->
  <aside id="sidebar" class="fixed md:static inset-y-0 left-0 w-64 bg-blue-700 text-white p-5 transform -translate-x-full md:translate-x-0 transition-all duration-500 ease-in-out z-50 md:min-h-screen overflow-y-auto md:shadow-xl">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold">E-Learning</h1>
      <button class="md:hidden text-white text-2xl focus:outline-none" onclick="toggleSidebar()">✕</button>
    </div>
    <nav class="space-y-3">
      <a href="dashboard.php" class="block py-2 px-3 hover:bg-blue-600 rounded transition">🏠 Beranda</a>
      <a href="materi.php" class="block py-2 px-3 hover:bg-blue-600 rounded transition">📚 Kelola Materi</a>
      <a href="tambah_tugas.php" class="block py-2 px-3 hover:bg-blue-600 rounded transition">📝 Kelola Tugas</a>
      <a href="tambah_absen.php" class="block py-2 px-3 hover:bg-blue-600 rounded transition">📅 Kelola Absen</a>
      <a href="kelas_saya.php" class="block py-2 px-3 bg-blue-600 rounded transition">🏫 Kelas Saya</a>
      <a href="atur_soal.php" class="block py-2 px-3 hover:bg-blue-600 rounded transition">🎮 Atur Soal</a>
    </nav>
  </aside>

  <!-- Konten utama -->
  <div class="flex-1 flex flex-col fade-in">
    <!-- Topbar -->
    <header class="bg-blue-700 text-white flex items-center justify-between px-4 py-3 shadow-md sticky top-0 z-40">
      <div class="flex items-center space-x-3">
        <button class="md:hidden text-2xl focus:outline-none" onclick="toggleSidebar()">☰</button>
        <h2 class="text-lg font-semibold">🏫 Kelas Saya</h2>
      </div>

      <div class="flex items-center space-x-3 relative">
        <!-- Tombol Notifikasi -->
        <button id="notifBtn" class="relative bg-blue-600 hover:bg-blue-800 px-3 py-2 rounded transition">
          🔔
          <?php if ($notif_result && $notif_result->num_rows > 0): ?>
            <span class="absolute -top-1 -right-1 bg-red-500 text-xs rounded-full px-1"><?= $notif_result->num_rows ?></span>
          <?php endif; ?>
        </button>

        <!-- Dropdown Notifikasi -->
        <div id="notifDropdown" class="hidden absolute right-0 mt-12 w-72 sm:w-80 bg-white text-gray-800 rounded-lg shadow-lg border z-50 fade-in">
          <div class="p-3 font-semibold border-b bg-blue-50">Notifikasi</div>
          <ul class="max-h-64 overflow-y-auto">
            <?php if ($notif_result && $notif_result->num_rows > 0): ?>
              <?php while ($n = $notif_result->fetch_assoc()): ?>
                <li class="px-3 py-2 border-b text-sm <?= $n['tipe']=='peringatan'?'bg-yellow-50':'bg-blue-50' ?>">
                  <?= htmlspecialchars($n['pesan']) ?><br>
                  <span class="text-xs text-gray-500"><?= date('d M H:i', strtotime($n['tanggal'])) ?></span>
                </li>
              <?php endwhile; ?>
            <?php else: ?>
              <li class="px-3 py-2 text-sm text-gray-500">Belum ada notifikasi.</li>
            <?php endif; ?>
          </ul>
        </div>

        <!-- Profil -->
        <div class="flex items-center space-x-3 relative">
          <span class="hidden sm:block font-semibold"><?= htmlspecialchars($nama) ?></span>
          <button id="profileBtn" class="focus:outline-none">
            <?php if ($foto): ?>
              <img src="uploads/<?= htmlspecialchars($foto) ?>" class="w-10 h-10 rounded-full border-2 border-white object-cover">
            <?php else: ?>
              <div class="bg-white text-blue-700 w-10 h-10 rounded-full flex items-center justify-center font-bold">
                <?= strtoupper(substr($nama, 0, 1)) ?>
              </div>
            <?php endif; ?>
          </button>

          <!-- Dropdown Profil -->
          <div id="profileDropdown" class="hidden absolute right-0 mt-12 w-40 bg-white border rounded-lg shadow-lg z-50 fade-in">
            <a href="profil.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 text-sm">Profil</a>
            <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100 text-sm">Logout</a>
          </div>
        </div>
      </div>
    </header>

    <!-- Konten Utama -->
    <main class="flex-1 p-4 sm:p-6 overflow-y-auto fade-in">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
        <?php
        $kelasList = ['9A', '9B', '9C', '9D', '9E', '9F'];
        foreach ($kelasList as $kelas) {
          $jumlah = $conn->query("SELECT COUNT(*) AS jml FROM siswa WHERE kelas='$kelas'")->fetch_assoc()['jml'] ?? 0;
          echo "
          <div class='bg-white rounded-xl shadow-md hover:shadow-lg transition transform hover:-translate-y-1 p-5 text-center'>
            <h3 class='text-lg sm:text-xl font-semibold text-gray-800 mb-2'>Kelas $kelas</h3>
            <p class='text-gray-500 mb-4 text-sm sm:text-base'>Jumlah siswa: <span class=\"font-bold\">$jumlah</span></p>
            <button onclick=\"lihatSiswa('$kelas')\" class='bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition w-full sm:w-auto'>
              👁️ Lihat Siswa
            </button>
          </div>
          ";
        }
        ?>
      </div>
    </main>
  </div>
</div>

<!-- Script Interaksi -->
<script>
function toggleSidebar() {
  const sidebar = document.getElementById("sidebar");
  sidebar.classList.toggle("-translate-x-full");
  const overlay = document.getElementById("overlay");
  if (overlay) overlay.classList.toggle("hidden");
  else {
    const newOverlay = document.createElement("div");
    newOverlay.id = "overlay";
    newOverlay.className = "fixed inset-0 bg-black bg-opacity-50 z-40 md:hidden";
    newOverlay.onclick = toggleSidebar;
    document.body.appendChild(newOverlay);
  }
}

// === Dropdown Notifikasi & Profil ===
const notifBtn = document.getElementById("notifBtn");
const notifDropdown = document.getElementById("notifDropdown");
const profileBtn = document.getElementById("profileBtn");
const profileDropdown = document.getElementById("profileDropdown");

notifBtn?.addEventListener("click", (e) => {
  e.stopPropagation();
  notifDropdown.classList.toggle("hidden");
  profileDropdown.classList.add("hidden");
});

profileBtn?.addEventListener("click", (e) => {
  e.stopPropagation();
  profileDropdown.classList.toggle("hidden");
  notifDropdown.classList.add("hidden");
});

// Klik di luar dropdown untuk menutup semua
document.addEventListener("click", () => {
  notifDropdown.classList.add("hidden");
  profileDropdown.classList.add("hidden");
});

function lihatSiswa(kelas) {
  // Kalau sudah ada overlay sebelumnya, hapus dulu
  const existing = document.getElementById("daftarSiswa");
  if (existing) existing.remove();

  // Buat overlay
  const overlay = document.createElement("div");
  overlay.id = "daftarSiswa";
  overlay.className = "fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center md:items-start md:pt-20 z-50 backdrop-blur-sm";
  document.body.appendChild(overlay);

  // Ambil isi dari ambil_siswa.php
  fetch(`ambil_siswa.php?kelas=${encodeURIComponent(kelas)}`)
    .then(res => res.text())
    .then(html => {
      overlay.innerHTML = html;

      // Deteksi klik di luar modal untuk tutup
      overlay.addEventListener("click", function(e) {
        const box = overlay.querySelector(".modal-content");
        if (!box.contains(e.target)) {
          const isMobile = window.innerWidth < 768;
          box.classList.add(isMobile ? "animate-slide-out" : "animate-fade-out");
          setTimeout(() => overlay.remove(), 300);
        }
      });
    })
    .catch(err => {
      console.error("Gagal ambil data siswa:", err);
      overlay.remove();
    });
}
</script>
</body>
</html>
