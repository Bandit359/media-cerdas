<?php
session_start();
include 'database.php';

// === Cek Login ===
if (!isset($_SESSION['guru'])) {
  header("Location: login.php");
  exit();
}

$nama = $_SESSION['guru'];

// === Ambil Data Guru ===
$query_guru = $conn->query("SELECT * FROM guru WHERE nama_guru = '$nama' LIMIT 1");
$data_guru = $query_guru->fetch_assoc();
$foto = !empty($data_guru['foto']) ? $data_guru['foto'] : null;

$alert = "";

// === Proses Tambah Absen ===
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
  $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
  $deadline = mysqli_real_escape_string($conn, $_POST['deadline']);
  $wajib_kamera = isset($_POST['wajib_kamera']) ? 1 : 0;

  $sql = "INSERT INTO absen (kelas, tanggal, deadline, wajib_kamera, guru)
          VALUES ('$kelas', '$tanggal', '$deadline', '$wajib_kamera', '$nama')";
  if ($conn->query($sql)) {
    $alert = "<div class='bg-green-100 text-green-700 p-3 rounded-md mb-4 fade-in'>
                ✅ Daftar hadir berhasil dibuat!
              </div>";
  } else {
    $alert = "<div class='bg-red-100 text-red-700 p-3 rounded-md mb-4 fade-in'>
                ❌ Gagal membuat daftar hadir.
              </div>";
  }
}

// === Ambil daftar absen yang sudah dibuat ===
$absen_result = $conn->query("SELECT * FROM absen ORDER BY tanggal DESC");

// === Ambil notifikasi terbaru (jika tabel notifikasi sudah ada) ===
$notif_result = $conn->query("SELECT * FROM notifikasi ORDER BY tanggal DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar Hadir | SMPN 14</title>
  <script src="https://cdn.tailwindcss.com"></script>

  <style>
  /* === ANIMASI MASUK HALAMAN === */
  @keyframes fadeSlideUp {
    from {
      opacity: 0;
      transform: translateY(20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .page-enter {
    animation: fadeSlideUp 0.7s ease-out;
  }

  /* Animasi fade-in bertahap */
  .fade-in {
    opacity: 0;
    transform: translateY(15px);
    animation: fadeInElement 0.8s ease forwards;
  }
  @keyframes fadeInElement {
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
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
      <a href="dashboard.php" class="block py-2 px-3 hover:bg-blue-600 rounded transition-all duration-300">🏠 Beranda</a>
      <a href="materi.php" class="block py-2 px-3 hover:bg-blue-600 rounded transition-all duration-300">📚 Kelola Materi</a>
      <a href="tambah_tugas.php" class="block py-2 px-3 hover:bg-blue-600 rounded transition-all duration-300">📝 Kelola Tugas</a>
      <a href="tambah_absen.php" class="block py-2 px-3 bg-blue-600 rounded transition-all duration-300">📅 Kelola Absen</a>
      <a href="kelas_saya.php" class="block py-2 px-3 hover:bg-blue-600 rounded transition-all duration-300">🏫 Kelas Saya</a>
      <a href="Kelola_game.php" class="block py-2 px-3 hover:bg-blue-600 rounded transition-all duration-300">🎮 Kelola Game</a>
    </nav>
  </aside>

  <!-- Konten utama -->
  <div class="flex-1 flex flex-col fade-in">
    <!-- Topbar -->
    <header class="bg-blue-700 text-white flex items-center justify-between px-4 py-3 shadow-md sticky top-0 z-40">
      <div class="flex items-center space-x-3">
        <button class="md:hidden text-2xl focus:outline-none" onclick="toggleSidebar()">☰</button>
        <h2 class="text-lg font-semibold">Dashboard Guru</h2>
      </div>

      <div class="flex items-center space-x-3 relative">
        <!-- Tombol Notifikasi -->
        <button id="notifBtn" class="relative bg-blue-600 hover:bg-blue-800 px-3 py-2 rounded transition">
          🔔
          <?php if ($notif_result && $notif_result->num_rows > 0): ?>
            <span class="absolute -top-1 -right-1 bg-red-500 text-xs rounded-full px-1">
              <?= $notif_result->num_rows ?>
            </span>
          <?php endif; ?>
        </button>

        <!-- Dropdown Notifikasi -->
        <div id="notifDropdown" class="hidden absolute right-0 mt-12 w-64 bg-white text-gray-800 rounded-lg shadow-lg border z-50 fade-in">
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

    <!-- Konten -->
    <main class="flex-1 p-4 sm:p-6 overflow-y-auto fade-in">
      <!-- Form Tambah Absen -->
      <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 mb-6 fade-in">
        <h1 class="text-lg sm:text-xl font-bold text-blue-700 mb-4">📝 Buat Daftar Hadir Baru</h1>
        <?= $alert ?>
        <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block font-medium mb-1">Kelas</label>
            <select name="kelas" required class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
              <option value="">-- Pilih Kelas --</option>
              <option>9A</option>
              <option>9B</option>
              <option>9C</option>
              <option>9D</option>
              <option>9E</option>
              <option>9F</option>
            </select>
          </div>
          <div>
            <label class="block font-medium mb-1">Tanggal</label>
            <input type="date" name="tanggal" required class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
          </div>
          <div>
            <label class="block font-medium mb-1">Deadline</label>
            <input type="datetime-local" name="deadline" required class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
          </div>
          <div class="flex items-center mt-6">
            <input type="checkbox" name="wajib_kamera" id="kamera" class="h-4 w-4 text-blue-600 focus:ring-blue-500">
            <label for="kamera" class="ml-2 text-sm font-medium">Wajib Facecam</label>
          </div>
          <div class="sm:col-span-2 text-right mt-2">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition focus:outline-none focus:ring-2 focus:ring-blue-500">
              ✅ Simpan
            </button>
          </div>
        </form>
      </div>

      <!-- Daftar Absen -->
      <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 fade-in">
        <h2 class="text-lg sm:text-xl font-bold text-blue-700 mb-4">📅 Daftar Absen yang Telah Dibuat</h2>
        <div class="hidden md:block overflow-x-auto">
          <table class="w-full border-collapse">
            <thead>
              <tr class="bg-blue-100 text-blue-700 text-left">
                <th class="py-2 px-3 border-b">Kelas</th>
                <th class="py-2 px-3 border-b">Tanggal</th>
                <th class="py-2 px-3 border-b">Deadline</th>
                <th class="py-2 px-3 border-b">Wajib Kamera</th>
                <th class="py-2 px-3 border-b text-center">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($absen_result->num_rows > 0): ?>
                <?php while ($row = $absen_result->fetch_assoc()): ?>
                  <tr class="hover:bg-gray-50 fade-in">
                    <td class="py-2 px-3 border-b"><?= htmlspecialchars($row['kelas']) ?></td>
                    <td class="py-2 px-3 border-b"><?= htmlspecialchars($row['tanggal']) ?></td>
                    <td class="py-2 px-3 border-b"><?= htmlspecialchars($row['deadline']) ?></td>
                    <td class="py-2 px-3 border-b"><?= $row['wajib_kamera'] ? 'Ya' : 'Tidak' ?></td>
                    <td class="py-2 px-3 border-b text-center">
                      <a href="status_siswa.php?id=<?= $row['id'] ?>" class="text-blue-600 hover:underline">📊 Lihat Status</a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="5" class="text-center py-4 text-gray-500">Belum ada daftar hadir.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- Script interaksi -->
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

// === Dropdown Notifikasi ===
const notifBtn = document.getElementById("notifBtn");
const notifDropdown = document.getElementById("notifDropdown");

// === Dropdown Profil ===
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

// === Klik di luar dropdown menutup semua ===
document.addEventListener("click", () => {
  notifDropdown.classList.add("hidden");
  profileDropdown.classList.add("hidden");
});
</script>
</body>
</html>
